<?php

namespace MailLibrary;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

require_once __DIR__ . '/autoload.php';

use MailLibrary\Services\BrevoService;
use MailLibrary\Services\PHPMailerService;
use MailLibrary\Services\SimpleMailService;

class MailLibrary {
    private $service;

    public function __construct($serviceType, $config = []) {
        switch ($serviceType) {
            case 'phpmailer':
                $this->service = new PHPMailerService($config);
                break;
            case 'simplemail':
                $this->service = new SimpleMailService($config);
                break;
            case 'brevoAPI':
                $this->service = new BrevoService($config);
                break;
            default:
                throw new \Exception("Unsupported mail service: $serviceType");
        }
    }


    public function send($to, $subject, $body, $altBody = '', $replyEmail = null, $cc = null, $bcc = null, $attachments = []) {
        // Call the service's send method to send the email
        $result = $this->service->send($to, $subject, $body, $altBody, $replyEmail, $cc, $bcc, $attachments);

        // If the email was sent successfully, delete the uploaded files
        if ($result === true && !empty($attachments)) {
            $this->deleteUploadedFiles($attachments);
        }

        return $result;
    }

    /**
     * Deletes the uploaded files after the email is sent
     * 
     * @param array $attachments List of file paths to be deleted
     */
    private function deleteUploadedFiles($attachments) {
        foreach ($attachments as $filePath) {
            // Check if the file exists before attempting to delete it
            if (file_exists($filePath)) {
                unlink($filePath);  // Delete the file
            }
        }
    }
}
