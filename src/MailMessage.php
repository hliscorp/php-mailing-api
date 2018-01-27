<?php
require_once("MailAddress.php");
require_once("MailException.php");

/**
 * Encapsulates mail sending on top of PHP mail function
 */
class MailMessage
{
    private $subject;
    private $to = array();
    private $from;
    private $sender;
    private $replyTo;
    private $cc = array();
    private $bcc = array();
    private $customHeaders = array();
    
    private $contentType;
    private $charset;
    private $message;
    private $attachments = array();

    /**
     * MailMessage constructor.
     * @param string $subject Subject of email.
     * @param string $body Email body.
     */
    public function __construct($subject, $body) {
        $this->subject = $subject;
        $this->message = $body;
    }

    /**
     * Adds address to send mail to
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function addTo($email, $name=null) {
        $this->to[] = new MailAddress($email, $name);
    }

    /**
     * Sets sender's address.
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function setFrom($email, $name=null) {
        $this->from = new MailAddress($email, $name);
    }

    /**
     * Sets message submitter, in case mail agent is sending on behalf of someone else.
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function setSender($email, $name=null) {
        $this->sender = new MailAddress($email, $name);
    }

    /**
     * Sets address recipients must use on replies to message.
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function setReplyTo($email, $name=null) {
        $this->replyTo = new MailAddress($email, $name);
    }

    /**
     * Adds address to publicly send a copy of message to
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function addCC($email, $name=null) {
        $this->cc[] = new MailAddress($email, $name);
    }

    /**
     * Adds address to discreetly send a copy of message to (invisible to others)
     *
     * @param string $email Value of email address.
     * @param string $name Name of person who holds email address (optional).
     */
    public function addBCC($email, $name=null) {
        $this->bcc[] = new MailAddress($email, $name);
    }

    /**
     * Sets email content type (useful when it's different from text/plain) and charset (useful when it's different from iso-8859-1).
     *
     * @param string $contentType
     * @param string $charset
     */
    public function setContentType($contentType, $charset) {
        $this->contentType = $contentType;
        $this->charset = $charset;
    }

    /**
     * Adds custom mail header
     *
     * @param string $name Value of header name.
     * @param string $value Value of header content.
     */
    public function addCustomHeader($name, $value) {
        $this->customHeaders[] = $name.": ".$value;
    }

    /**
     * Adds attachment
     *
     * @param string $filePath Location of attached file
     */
    public function addAttachment($filePath) {
        if(!file_exists($filePath)) throw new MailException("Attached file doesn't exist!");
        $this->attachments[] = $filePath;
    }

    /**
     * Sends mail to recipients
     */
    public function send() {
        if(empty($this->to)) throw new MailException("You must add at least one recipient to mail message!");
        
        // create separator to be used in multipart content (if any)
        $separator = md5(uniqid(time()));
        
        // compile headers
        $headers = array();
        if(!empty($this->attachments)) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
            $headers[] = "Content-Transfer-Encoding: 7bit";
            $headers[] = "This is a MIME encoded message";
        } else {
            if($this->contentType) {
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-type:".$this->contentType."; charset=\"".$this->charset."\"";
            }
        }
        if(!empty($this->from)) {
            $headers[] = "From: ".$this->from;
        }
        if(!empty($this->sender)) {
            $headers[] = "Sender: ". $this->sender;
        }
        if(!empty($this->replyTo)) {
            $headers[] = "Reply-To: ".$this->replyTo;
        }
        if(!empty($this->cc)) {
            $headers[] = "Cc: ".implode(",", $this->cc);
        }
        if(!empty($this->bcc)) {
            $headers[] = "Bcc: ".implode(",", $this->bcc);
        }
        if(!empty($this->customHeaders)) {
            $headers = array_merge($headers, $this->customHeaders);
        }

        // compile body
        $body = "";
        if(!empty($this->attachments)) {
            $bodyParts = array();
            
            // add message body
            $bodyParts[] = "--".$separator;
            $bodyParts[] = "Content-Type: ".($this->contentType?$this->contentType:"text/plain")."; charset=\"".($this->charset?$this->charset:"iso-8859-1")."\"";
            $bodyParts[] = "Content-Transfer-Encoding: 8bit";
            $bodyParts[] = $this->message;
            
            // add attachments
            foreach($this->attachments as $filePath) {
                $bodyParts[] = "--".$separator;
                $bodyParts[] = "Content-Type: ".mime_content_type($filePath)."; name=\"".basename($filePath) ."\"";
                $bodyParts[] = "Content-Transfer-Encoding: base64";
                $bodyParts[] = "Content-Disposition: attachment";
                $bodyParts[] = chunk_split(base64_encode(file_get_contents($filePath)));
            }
            $bodyParts[] = "--".$separator."--";
            $body = implode("\r\n", $bodyParts);
        } else {
            $body = $this->message;
        }

        // send mail
        $result = mail(implode(",",$this->to), $this->subject, $body, implode("\r\n", $headers));
        if(!$result) throw new MailException("Send failed!");
    }
}

