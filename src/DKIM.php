<?php
namespace Lucinda\Mail;

/**
 * Creates a DKIM-Signature header according to RFC specifications using algorithms based on:
 * https://github.com/louisameline/php-mail-signature
 */
class DKIM
{
    const DKIM_HASH = "sha256";

    private string $privateKey;
    private string $domain;
    private string $selector;
    private array $signedHeaders = [];
    
    /**
     * Sets up necessary ingredients for DKIM generation before headers/subject/body are set
     * 
     * @param string $privateKey RSA private key
     * @param string $passphrase RSA passphrase
     * @param string $domain Domain name
     * @param string $selector DNS selector
     * @param string[] $signedHeaders Headers names to sign request with (MUST EXIST!)
     */
    public function __construct(string $privateKey, string $passphrase, string $domain, string $selector, array $signedHeaders)
    {
        $this->privateKey = openssl_pkey_get_private($privateKey, $passphrase);
        $this->domain = $domain;
        $this->selector = $selector;
        
        // lowercase header names automatically
        foreach($signedHeaders as $key => $value){
            $signedHeaders[$key] = strtolower($value);
        }
        $this->signedHeaders = $signedHeaders;
    }
    
    /**
     * Gets DKIM-Signature header
     * 
     * @param string $to Destination(s) of email message
     * @param string $subject Subject of email message
     * @param string $body Body of email message
     * @param string $headers Headers email message will come with
     * @throws Exception If a DKIM signature header could not be created
     * @return string DKIM-Signature header name and value
     */
    public function getSignature(string $to, string $subject, string $body, string $headers): string
    {                
        // to and subject must exist already
        $headers .= (mb_substr($headers, mb_strlen($headers, 'UTF-8')-2, 2, 'UTF-8') == "\r\n"?'':"\r\n");
        $headers .= 'To: '.$to."\r\n";
        $headers .= 'Subject: '.$subject."\r\n";
        
        // get canonical version of headers to be used for signature
        $canonicalizedHeaders = $this->canonicalizeHeaders($headers);
        if (empty($canonicalizedHeaders)) {
            throw new Exception('No headers found to sign the e-mail with');
        }
        
        // get canonical version of body to be used for signature
        $canonicalizedBody = $this->canonicalizeBody($body);
        
        // creates DKIM header
        $dkim_header = 'DKIM-Signature: '.
            'v=1;'."\r\n\t".
            'a=rsa-' . self::DKIM_HASH . ';'."\r\n\t".
            'q=dns/txt;'."\r\n\t".
            's='.$this->selector.';'."\r\n\t".
            't='.time().';'."\r\n\t".
            'c=relaxed/relaxed;'."\r\n\t".
            'h='.implode(':', array_keys($canonicalizedHeaders)).';'."\r\n\t".
            'd='.$this->domain.';'."\r\n\t".
            'bh='.rtrim(chunk_split(base64_encode(pack("H*", hash(self::DKIM_HASH, $canonicalizedBody))), 64, "\r\n\t")).';'."\r\n\t".
            'b=';
        
        // canonicalizes DKIM header
        $canonicalized_dkim_header = $this->canonicalizeHeaders($dkim_header);
        
        // appends signature to DKIM header
        $signature = '';
        $signing_algorithm = OPENSSL_ALGO_SHA256;
        $to_be_signed = implode("\r\n", $canonicalizedHeaders)."\r\n".$canonicalized_dkim_header['dkim-signature'];
        if (openssl_sign($to_be_signed, $signature, $this->privateKey, $signing_algorithm)) {
            $dkim_header .= rtrim(chunk_split(base64_encode($signature), 64, "\r\n\t"))."\r\n";
        } else {
            throw new Exception("Could not sign e-mail with DKIM");
        }
        
        return $dkim_header;
    }
    
    /**
     * Gets canonicalized version of email message body
     * 
     * @param string $body Body of email message
     * @return string Canonicalized email message body
     */
    private function canonicalizeBody(string $body): string
    {
        // relaxed canonicalization
        $lines = explode("\r\n", $body);
        foreach ($lines as $key => $value) {
            // ignore WSP at the end of lines
            $value = rtrim($value);
            
            // ignore multiple WSP inside the line
            $lines[$key] = preg_replace('/\s+/', ' ', $value);
        }
        $body = implode("\r\n", $lines);
        
        // remove multiple trailing CRLF
        while (mb_substr($body, mb_strlen($body, 'UTF-8')-4, 4, 'UTF-8') == "\r\n\r\n") {
            $body = mb_substr($body, 0, mb_strlen($body, 'UTF-8')-2, 'UTF-8');
        }
        
        // must end with CRLF anyway
        if (mb_substr($body, mb_strlen($body, 'UTF-8')-2, 2, 'UTF-8') != "\r\n") {
            $body .= "\r\n";
        }
        
        return $body;
    }
    
    /**
     * Gets canonicalized headers to send
     * 
     * @param string $sHeaders List of headers to send in email
     * @return string[] Headers canonicalized and converted to array
     */
    private function canonicalizeHeaders(string $sHeaders): array
    {
        $aHeaders = array();
        
        // a header value which is spread over several lines must be 1-lined
        $sHeaders = preg_replace("/\n\s+/", " ", $sHeaders);
        
        $lines = explode("\r\n", $sHeaders);
        foreach ($lines as $line) {
            $line = preg_replace("/\s+/", ' ', $line);
            if (!empty($line)) {
                $line = explode(':', $line, 2);
                $header_type = trim(strtolower($line[0]));
                $header_value = trim($line[1]);
                if (in_array($header_type, $this->signedHeaders) || $header_type == 'dkim-signature') {
                    $aHeaders[$header_type] = $header_type.':'.$header_value;
                }
            }
        }
        
        return $aHeaders;
    }
}
