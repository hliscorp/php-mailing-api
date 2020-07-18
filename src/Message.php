<?php
namespace Lucinda\Mail;

/**
 * Encapsulates mail sending on top of PHP mail function
 */
class Message
{
    private $subject = "";
    private $to = array();
    private $from;
    private $sender;
    private $replyTo;
    private $cc = array();
    private $bcc = array();
    private $date;
    private $messageID;
    private $unsubscribe = array();
    private $customHeaders = array();
    private $dkim;
    
    private $contentType;
    private $charset;
    private $message;
    private $attachments = array();

    /**
     * Message constructor.
     *
     * @param string $subject Subject of email.
     * @param string $body Email body.
     */
    public function __construct($subject, $body)
    {
        $this->subject = $subject;
        $this->message = $body;
    }

    /**
     * Adds address to send mail to
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function addTo(Address $address)
    {
        $this->to[] = $address;
    }

    /**
     * Sets sender's address.
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function setFrom(Address $address)
    {
        $this->from = $address;
    }

    /**
     * Sets message submitter, in case mail agent is sending on behalf of someone else.
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function setSender(Address $address)
    {
        $this->sender = $address;
    }

    /**
     * Sets address recipients must use on replies to message.
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function setReplyTo(Address $address)
    {
        $this->replyTo = $address;
    }

    /**
     * Adds address to publicly send a copy of message to
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function addCC(Address $address)
    {
        $this->cc[] = $address;
    }

    /**
     * Adds address to discreetly send a copy of message to (invisible to others)
     *
     * @param Address $address Value of email and optional name of person email belongs to
     */
    public function addBCC(Address $address)
    {
        $this->bcc[] = $address;
    }

    /**
     * Sets email content type (useful when it's different from text/plain) and charset (useful when it's different from iso-8859-1).
     *
     * @param string $contentType
     * @param string $charset
     */
    public function setContentType($contentType, $charset)
    {
        $this->contentType = $contentType;
        $this->charset = $charset;
    }
    
    /**
     * Sets custom date of message
     *
     * @param int $unixTime
     */
    public function setDate($unixTime)
    {
        $this->date = $unixTime;
    }
    
    /**
     * Sets message ID header based on your domain name
     *
     * @param string $domainName
     */
    public function setMessageID($domainName)
    {
        $this->messageID = md5(uniqid("msgid-"))."@".$domainName;
    }
    
    /**
     * Sets email/url to be used/clicked if target wants to unsubscribe
     *
     * @param string $email
     * @param string $url
     * @throws Exception
     */
    public function setListUnsubscribe($email=null, $url=null)
    {
        if (!$email && !$url) {
            throw new Exception("You must set either an email or an url!");
        }
        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email address is invalid!");
            }
            $this->unsubscribe[] = "mailto:".$email;
        }
        if ($url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Url is invalid!");
            }
            $this->unsubscribe[] = $url;
        }
    }
    
    /**
     * Adds custom mail header
     *
     * @param string $name Value of header name.
     * @param string $value Value of header content.
     */
    public function addCustomHeader($name, $value)
    {
        $this->customHeaders[] = $name.": ".$value;
    }

    /**
     * Adds attachment
     *
     * @param string $filePath Location of attached file
     */
    public function addAttachment($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("Attached file doesn't exist!");
        }
        $this->attachments[] = $filePath;
    }
    // Selector used in your DKIM DNS record, e.g. : selector._domainkey.MAIL_DKIM_DOMAIN
    
    /**
     * Sets a DKIM-Signature header to vouch for domain name authenticity
     *
     * @param string $rsaPrivateKey RSA private key
     * @param string $rsaPassphrase RSA passphrase
     * @param string $domainName Domain name
     * @param string $dnsSelector DNS selector (http://knowledge.ondmarc.redsift.com/en/articles/2137267-what-is-a-dkim-selector#:~:text=A%20DKIM%20selector%20is%20a,technical%20headers%20of%20an%20email.)
     * @param string[] $signedHeaders Headers names to sign request with (MUST EXIST!)
     */
    public function setDKIMSignature($rsaPrivateKey, $rsaPassphrase, $domainName, $dnsSelector, $signedHeaders)
    {
        $this->dkim = new DKIM($rsaPrivateKey, $rsaPassphrase, $domainName, $dnsSelector, $signedHeaders);
    }

    /**
     * Sends mail to recipients
     */
    public function send()
    {
        if (empty($this->to)) {
            throw new Exception("You must add at least one recipient to mail message!");
        }
        
        $separator = md5(uniqid(time()));
        $to = implode(",", $this->to);
        $body = $this->getBody($separator);
        $headers = $this->getHeaders($separator);
                
        // apply DKIM signature to entire message, if available
        if ($this->dkim) {
            $headers = $this->dkim->getSignature($to, $this->subject, $body, $headers).$headers;
        }
        
        $result = mail($to, $this->subject, $body, $headers);
        if (!$result) {
            throw new Exception("Send failed!");
        }
    }
    
    /**
     * Compiles email headers to send
     *
     * @param string $separator Separator to use in case attachments are sent     *
     * @param string $body Email message body.
     * @return array Headers to send
     */
    private function getHeaders($separator)
    {
        $headers = array();
        if (!empty($this->attachments)) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
            $headers[] = "Content-Transfer-Encoding: 7bit";
            $headers[] = "This is a MIME encoded message";
        } else {
            if ($this->contentType) {
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-type:".$this->contentType."; charset=\"".$this->charset."\"";
            }
        }
        $headers[] = "Date: ".date("r (T)", ($this->date?$this->date:time()));
        if ($this->messageID) {
            $headers[] = "Message-ID: ".$this->messageID;
        }
        if (!empty($this->unsubscribe)) {
            $headers[] = "List-Unsubscribe: ".("<".implode(">, <", $this->unsubscribe).">");
            $headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
        }
        if (!empty($this->from)) {
            $headers[] = "From: ".$this->from;
        }
        if (!empty($this->sender)) {
            $headers[] = "Sender: ". $this->sender;
        }
        if (!empty($this->replyTo)) {
            $headers[] = "Reply-To: ".$this->replyTo;
        }
        if (!empty($this->cc)) {
            $headers[] = "Cc: ".implode(",", $this->cc);
        }
        if (!empty($this->bcc)) {
            $headers[] = "Bcc: ".implode(",", $this->bcc);
        }
        if (!empty($this->customHeaders)) {
            $headers = array_merge($headers, $this->customHeaders);
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Compiles message body to send
     *
     * @param string $separator Separator to use in case attachments are sent
     * @return string Message body to send.
     */
    private function getBody($separator)
    {
        $body = preg_replace('/(?<!\r)\n/', "\r\n", $this->message);
        if (!empty($this->attachments)) {
            $bodyParts = array();
            
            // add message body
            $bodyParts[] = "--".$separator;
            $bodyParts[] = "Content-Type: ".$this->contentType."; charset=\"".$this->charset."\"";
            $bodyParts[] = "Content-Transfer-Encoding: 8bit";
            $bodyParts[] = $body;
            
            // add attachments
            foreach ($this->attachments as $filePath) {
                $bodyParts[] = "--".$separator;
                $bodyParts[] = "Content-Type: ".mime_content_type($filePath)."; name=\"".basename($filePath) ."\"";
                $bodyParts[] = "Content-Transfer-Encoding: base64";
                $bodyParts[] = "Content-Disposition: attachment";
                $bodyParts[] = chunk_split(base64_encode(file_get_contents($filePath)));
            }
            $bodyParts[] = "--".$separator."--";
            $body = implode("\r\n", $bodyParts);
        }
        return $body;
    }
}
