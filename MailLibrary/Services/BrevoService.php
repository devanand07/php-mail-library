<?php

namespace MailLibrary\Services;

// Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// ini_set('max_execution_time', 60); // Increase to 60 seconds for testing

// Adjust the path to autoload.php based on your project structure
require __DIR__ . '/vendors/brevo/vendor/autoload.php';

use Exception;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;
class BrevoService {
    private $apiInstance;
    private $fromName;
    private $fromEmail;

    public function __construct($config) {
        $apiKey = $config['api_key'] ?? '';
        $clientConfig = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $this->apiInstance = new TransactionalEmailsApi(new Client(), $clientConfig);

        // $this->mailer->setFrom($config['from_email'], $config['from_name']);
        $this->fromEmail = $config['from_email'];
        $this->fromName = $config['from_name'];
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
        $email = [
            'to' => [],
            'sender' => [
                'email' => $this->fromEmail, // Use your verified sender email
                'name' => $this->fromName,
            ],
            'subject' => $subject,
            'htmlContent' => $body,
            'textContent' => $altBody,
        ];

        // Handle multiple recipients
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $email['to'][] = ['email' => $recipient];
            }
        } else {
            $email['to'][] = ['email' => $to];
        }

        // Add reply-to email
        if ($replyEmail) {
            $email['replyTo'] = ['email' => $replyEmail];
        }

        // Handle CC
        if ($cc) {
            $email['cc'] = [];
            if (is_array($cc)) {
                foreach ($cc as $ccAddress) {
                    $email['cc'][] = ['email' => $ccAddress];
                }
            } else {
                $email['cc'][] = ['email' => $cc];
            }
        }

        // Handle BCC
        if ($bcc) {
            $email['bcc'] = [];
            if (is_array($bcc)) {
                foreach ($bcc as $bccAddress) {
                    $email['bcc'][] = ['email' => $bccAddress];
                }
            } else {
                $email['bcc'][] = ['email' => $bcc];
            }
        }

        // Handle attachments        
        if ($attachments) {
		$email['attachment'] = [];
		foreach ($attachments as $filePath) {
		    if (file_exists($filePath)) {
			$fileContent = file_get_contents($filePath);
			$base64 = base64_encode($fileContent);
			$mimeType = mime_content_type($filePath);

			$email['attachment'][] = [
			    'name' => basename($filePath),
			    'content' => $base64,
			    'type' => $mimeType,
			];
		    } else {
			error_log("File not found: $filePath");
		    }
		}
	}

        

        // Send the email
        try {
            $response = $this->apiInstance->sendTransacEmail($email);
            return $response['messageId'] ? true : 'Failed to send email';
        } catch (Exception $e) {
            return 'Brevo API Error: ' . $e->getMessage();
        }
    }
}
