<?PHP

namespace MailLibrary;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

require_once __DIR__ . '/autoload.php';

class UsefulLib
{

    /**
     * Check and allow only phone codes 
     * return boolean as true or false
     */
    public function checkAllowedPhoneNumberCode($requestPhoneNumber)
    {
        // Clean the input phone number by removing all non-numeric characters
        $cleanedPhoneNumber = preg_replace('/[^0-9]/', '', $requestPhoneNumber);

        // Check if the phone number has at least 10 digits
        if (strlen($cleanedPhoneNumber) < 10) {
            return false;
        }

        // Extract the first three digits (area code)
        $phoneAreaCode = substr($cleanedPhoneNumber, 0, 3);

        // Define the allowed area codes for Ontario
        $ontario = [905, 289, 365, 519, 226, 548, 705, 249, 613, 343, 807, 416, 647, 437];
        $allowedPhoneCodeArr = $ontario;

        // Remove duplicate values
        $allowedPhoneCodeArr = array_unique($allowedPhoneCodeArr);

        // Sort the array
        sort($allowedPhoneCodeArr);

        // Check if the extracted area code is in the allowed list
        if (in_array((int)$phoneAreaCode, $allowedPhoneCodeArr)) {
            return true;
        }

        return false;
    }


    /**
     * Google Recaptha Keys
     * return array of Google Keys 
     */
    public function getGoogleRecaptchaKeys()
    {
        $siteKey = '6Ldrk6YbAAAAAAtCM-2oLC7Ltlz3-RHXOHXvk6X_';
        $secretKey = '6Ldrk6YbAAAAAF1gH5vKIhzCc9hmeAOil76GKoxg';

        return ['SITE_KEY' => $siteKey, 'SECRET_KEY' => $secretKey];
    }


    /**
     * Set SMTP Keys.
     * return array of SMTP keys
     */
    public function getSmtpSettings()
    {
        //Send messages using PHP's mail() function if send_mail_using = 'mail' if smtp_auth=false.
        $result = array('smtp_auth' => false, 'send_mail_using' => 'mail');

        //New SMTP Key 
        $result['host'] = 'smtp.gmail.com';
        $result['username'] = 'taswebmasterteam@gmail.com';
        $result['password'] = 'tgujpxszupxpcadr';
        $result['encryption'] = 'tls';
        $result['port'] = 587;
        $result['timeout'] = 45;

        $result['smtp_debug'] = 0;
        //if false then SMTP settings are ignored
        $result['smtp_auth'] = false;
        
        //..Uncomment ...To override send_mail_using & if want to send message via sendMail
        if($result['smtp_auth'] == false) $result['send_mail_using'] = 'sendmail'; 

        return $result;
    }


    /**
     * Set BREVO API KEY
     * return array of BREVO API KEY
     */
    public function getBrevoApiSettings()
    {
        $result = array('api_key' => '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');
        return $result;
    }
}
