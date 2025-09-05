<?php

namespace MailLibrary\Services;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendors/PHPMailer/src/Exception.php';
require 'vendors/PHPMailer/src/PHPMailer.php';
require 'vendors/PHPMailer/src/SMTP.php';

class PHPMailerService {
    private $mailer;

    public function __construct($config) {
        $this->mailer = new PHPMailer(true);
        if($config['smtp_auth'] === true){
            //smtp Mail what ever is set
            $this->mailer->isSMTP();
            $this->mailer->Host       = $config['host'] ?? 'smtp.notfound.com';
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $config['username'] ?? 'user@notfound.com';
            $this->mailer->Password   = $config['password'] ?? 'notfound';
            $this->mailer->SMTPSecure = $config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = $config['port'] ?? 587;
            $this->mailer->Timeout    = $config['timeout'];
        }else{
            //set send mail type if auth fails...
            $send_mail_using = $config['send_mail_using'] ?? 'mail';
            switch ($send_mail_using) {
                case 'mail':
                    $this->mailer->isMail(); //Send messages using PHP's mail() function.
                    break;
                    
                case 'sendmail':
                    $this->mailer->isSendmail();  //Send messages using $Sendmail.
                    break;
                
                default:
                    $this->mailer->isMail();
                    break;
            }
        }

        $this->mailer->SMTPDebug = $config['smtp_debug'] ?? 0; // Debug level for troubleshooting

        $this->mailer->setFrom($config['from_email'], $config['from_name']);
    }

    /**
     * Send email with multiple recipients, CC, BCC, and attachments
     *
     * @param array|string $to          Single email or array of recipient emails
     * @param string $subject           Email subject
     * @param string $body              HTML content of the email
     * @param string $altBody           Plain text alternative content
     * @param array|string|null $cc     Single or array of CC emails
     * @param array|string|null $bcc    Single or array of BCC emails
     * @param array $attachments        Array of file paths for attachments
     * @return string|bool              Returns true on success or an error message on failure
     */
    public function send($to, $subject, $body, $altBody = '', $replyEmail=null, $cc = null, $bcc = null, $attachments = []) {
        try {
            // Handle multiple recipients
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }
            //set reply to
            if($replyEmail) {
                $this->mailer->addReplyTo($replyEmail);
            }

            // Handle CC
            if ($cc) {
                if (is_array($cc)) {
                    foreach ($cc as $ccAddress) {
                        $this->mailer->addCC($ccAddress);
                    }
                } else {
                    $this->mailer->addCC($cc);
                }
            }

            // Handle BCC
            if ($bcc) {
                if (is_array($bcc)) {
                    foreach ($bcc as $bccAddress) {
                        $this->mailer->addBCC($bccAddress);
                    }
                } else {
                    $this->mailer->addBCC($bcc);
                }
            }

            // Handle attachments
            if ($attachments) {
                foreach ($attachments as $filePath) {
                    $this->mailer->addAttachment($filePath);
                }
            }

            // Set email format to HTML
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = $altBody;
            // $this->mailer->SMTPDebug = 0; // Debug level for troubleshooting
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            return $this->mailer->send() ? true : 'Failed to send email';
        } catch (Exception $e) {
            return 'PHPMailer Error: ' . $this->mailer->ErrorInfo;
        }
    }
}
