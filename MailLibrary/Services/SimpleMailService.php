<?php

namespace MailLibrary\Services;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

 class SimpleMailService {
    public $fromEmail;
    public $fromName;

    public function __construct($config) {
        $host = $_SERVER['SERVER_NAME'];
        $from = "noreply@$host";

        $this->fromName = $config['from_name'] ?? $from;
        $this->fromEmail = $config['from_email'] ?? $from;
    }

    public function send($to, $subject, $body, $altBody = '', $replyEmail=null, $cc = null, $bcc = null, $attachments = []) {
        // To support multiple recipients, CC, BCC, and attachments
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: <'.$this->fromEmail.'>' . "\r\n";
        
        // Add Reply-To if provided
        if ($replyEmail) {
            $headers .= 'Reply-To: <' . $replyEmail . '>' . "\r\n";
        }

        // Handle multiple recipients for "To"
        if (is_array($to)) {
            $to = implode(',', $to); // Convert array to comma-separated string
        }

        // Add CC if provided
        if ($cc) {
            if (is_array($cc)) {
                $cc = implode(',', $cc); // Convert array to comma-separated string
            }
            $headers .= 'Cc: ' . $cc . "\r\n";
        }

        // Add BCC if provided
        if ($bcc) {
            if (is_array($bcc)) {
                $bcc = implode(',', $bcc); // Convert array to comma-separated string
            }
            $headers .= 'Bcc: ' . $bcc . "\r\n";
        }

        // Handle attachments
        if (!empty($attachments)) {
            $boundary = md5(uniqid(time())); // Unique boundary for the email

            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"" . "\r\n";

            // Start of the message body
            $body = "--$boundary\r\n"
                  . "Content-Type: text/html; charset=UTF-8\r\n"
                  . "Content-Transfer-Encoding: base64\r\n\r\n"
                  . chunk_split(base64_encode($body));

            // Add attachments
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $fileName = basename($attachment);
                    $fileContent = file_get_contents($attachment);
                    $fileContent = chunk_split(base64_encode($fileContent));
                    
                    // Attachment section
                    $body .= "\r\n--$boundary\r\n"
                           . "Content-Type: application/octet-stream; name=\"$fileName\"\r\n"
                           . "Content-Transfer-Encoding: base64\r\n"
                           . "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n"
                           . $fileContent;
                }
            }

            // End of the multipart message
            $body .= "\r\n--$boundary--";
        } else {
            // If no attachments, it's just a plain HTML email
            $body = $body;
        }

        // Send the email
        $result = mail($to, $subject, $body, $headers);

        // If mail fails, log the error
        if (!$result) {
            error_log("Failed to send email using SimpleMailService. Check server mail configuration.");
        }

        return $result;
    }
}

