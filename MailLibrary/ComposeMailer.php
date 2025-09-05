<?PHP

namespace MailLibrary;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

require_once __DIR__ . '/autoload.php';

use MailLibrary\UsefulLib;
use MailLibrary\MailLibrary;

class ComposeMailer
{
    var $name;
    var $email;
    var $message;
    var $form_random_key;
    var $error_message;
    var $errors = [];
    var $toRecipents = [];
    var $ccRecipents = [];
    var $bccRecipents = [];
    var $replyEmail = false;
    var $fileupload_fields = [];
    var $usefulLib;
    var $mailer;
    var $template;


    function __construct($serviceType, $fromArr, $to, $replyEmail='' , $cc = '', $bcc = '')
    {
        $this->errors = array();
        $this->fileupload_fields = array();

        $this->usefulLib = new UsefulLib();
        $config = [];
        
        if ($serviceType == 'phpmailer') {
            $config = $this->usefulLib->getSmtpSettings();
        }else if ($serviceType == 'brevoAPI') {
            $config = $this->usefulLib->getBrevoApiSettings();
        }
        
        $config['from_email'] = $fromArr['email'];
        $config['from_name'] = $fromArr['name'];

        $this->toRecipents = $to;
        if ($replyEmail) $this->replyEmail = $replyEmail;
        if ($cc) $this->ccRecipents = $cc;
        if ($cc) $this->bccRecipents = $bcc;

        $this->mailer = new MailLibrary($serviceType, $config);
        // $this->mailer->CharSet = 'utf-8';
        $this->template = new TemplateClass($this->mailer); // Initialize TemplateClass
    }

    function containsRussianText($string) {
        // Check if the string contains characters in the Russian Cyrillic Unicode range
        return preg_match('/[\x{0400}-\x{04FF}]/u', $string);
    }


    function setFormRandomKey($key)
    {
        $this->form_random_key = $key;
    }

    function getKey()
    {
        return $this->form_random_key . $_SERVER['SERVER_NAME'] . $_SERVER['REMOTE_ADDR'];
    }
    function getSpamTrapInputName()
    {
        return 'sp' . md5('KHGdnbvsgst' . $this->getKey());
    }
    function getFormIDInputValue()
    {
        return md5('jhgahTsajhg' . $this->getKey());
    }

    function getFormIDInputName()
    {
        $rand = md5('TygshRt' . $this->getKey());
        $rand = substr($rand, 0, 20);
        return 'id' . $rand;
    }

    function isInternalVariable($varname)
    {
        $arr_interanl_vars = array(
            'scaptcha',
            'submitted',
            $this->getSpamTrapInputName(),
            $this->getFormIDInputName()
        );
        if (in_array($varname, $arr_interanl_vars)) {
            return true;
        }
        return false;
    }


    function sanitize($str, $remove_nl = true)
    {
        $str = stripslashes($str);
        if ($remove_nl) {
            $injections = array(
                '/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i'
            );
            $str = preg_replace($injections, '', $str);
        }
        return $str;
    }



    function processFormToSender($validateRequiredDataArr = [], $vresponse = null, $customMessageBody='', $optnArr=[])
    {
        // echo "Delivery_Option:".$_POST['Delivery_Option'];
        // echo "<pre>";
        // print_r($_POST);
        // die("OK");
        if (!isset($_POST['submitted'])) {
            return false;
        }

        if (!$this->validate($validateRequiredDataArr)) {
            $this->error_message = implode('<br/>', $this->errors);
            return false;
        }
        
        //Check the post data
        if(isset($_POST) && is_array($_POST) && count($_POST)>0){
            foreach ($_POST as $key => $value) {
                if ($key != 'g-recaptcha-response' || $key != 'termsandcond' || $key !='submitted') {
                    if (is_array($_POST[$key])) {
                        foreach ($_POST[$key] as $data) {
                            if( $this->containsRussianText(trim($data)) ){
                                $this->add_error("Russian text is not not allowed to submit form.");
                                //Don`t allow the spamers to submit the russian so just refresh page without any error message.
                                $referer = $_SERVER['HTTP_REFERER'];
                                header("Location: $referer");
                                return false;
                            }
                        }
                    }else{
                        if( $this->containsRussianText(trim($value)) ){
                            $this->add_error("Russian text is not not allowed to submit form.");
                            //Don`t allow the spamers to submit the russian so just refresh page without any error message.
                            $referer = $_SERVER['HTTP_REFERER'];
                            header("Location: $referer");
                            return false;
                        }
                    }
                }
            }
        }

        // die("SENT....");
        $this->collectData();

        $ret = $this->sendFormSubmissionToSender($vresponse = null, $customMessageBody, $optnArr);
        return $ret;
    }

    function add_error($error)
    {
        array_push($this->errors, $error);
    }

    function validate_email($email)
    {
        return preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/", trim($email));
    }

    function validate_phone($phone)
    {
        //return preg_match("/^[-.0-9]$/", trim($phone));
        if (preg_match("/^[1-9][0-9]{0,15}$/", trim($phone))) {
            return true;
        } else {
            return false;
        }
    }

    function validate($validateRequiredDataArr = [])
    {
        $ret = true;
        $errorCount = 0;
        $validateFileNameArr = [];
        if ($validateRequiredDataArr && count($validateRequiredDataArr) > 0) {
            foreach ($validateRequiredDataArr as $name => $valueArr) {
                $required = $valueArr['required'] ?? false;
                if ($valueArr['type'] == 'array') {
                    if ($required  && count($_POST[$name]) == 0) {
                        $this->add_error(ucwords(implode(' ', explode("_", $name))) . " is Required.");
                        ++$errorCount;
                    }
                } elseif ($valueArr['type'] != 'file') {

                    if(!empty($_POST[$name]) && trim(($_POST[$name])) !='' ){
                        //check the russian word
                        if($this->containsRussianText(trim(($_POST[$name])))){
                            $this->add_error("Russian text is not not allowed to submit form.");
                            ++$errorCount;
                            //Don`t allow the spamers to submit the russian so just refresh page without any error message.
                            $referer = $_SERVER['HTTP_REFERER'];
                            header("Location: $referer");
                        }
                    }

                    if ($required &&  empty(trim($_POST[$name]))) {
                        $this->add_error(ucwords(implode(' ', explode("_", $name))) . " is Required.");
                        ++$errorCount;
                    } elseif (isset($valueArr['checkMaxChar']) && strlen($_POST[$name]) > $valueArr['checkMaxChar']) {
                        $this->add_error(ucwords(implode(' ', explode("_", $name))) . " Digit Exceeds.");
                        ++$errorCount;
                    } elseif (isset($valueArr['validate']) && $valueArr['validate']) {
                        switch ($valueArr['validate']) {
                            case 'phone':
                                if (!$this->validate_phone($_POST[$name])) {
                                    $this->add_error("Please provide a valid phone no.");
                                    ++$errorCount;
                                }
                                break;
                            case 'phonecode':
                                if (!$this->usefulLib->checkAllowedPhoneNumberCode($_POST[$name])) {
                                    $this->add_error("Phone no. not allowed to submit form.");
                                    ++$errorCount;
                                    //Don`t allow the spamers to predict the phone validations so just refresh page without any error message.
                                    $referer = $_SERVER['HTTP_REFERER'];
                                    header("Location: $referer");
                                }
                                break;
                            case 'restrictspecialchar':
                                $restrictArr = $valueArr['restrictStringArr'] ?? '';
                                if ($restrictArr && count($restrictArr)>0) {
                                    foreach ($restrictArr as $key => $restrictString) {
                                        $checkRes = stripos($_POST[$name],$restrictString);
                                        if($checkRes && $checkRes >=0){
                                            $this->add_error("special char not allowed to submit form & found at ". $checkRes );
                                            ++$errorCount;
                                            //Don`t allow the spamers to predict the phone validations so just refresh page without any error message.
                                            $referer = $_SERVER['HTTP_REFERER'];
                                            header("Location: $referer");
                                            // break;
                                        }
                                    }
                                }
                                
                                //restrict more special chars
                                $pattern = '/^[a-zA-Z0-9\s.,\'-]+$/';
                                // Validate the input
                                if (!preg_match($pattern, $_POST[$name])) {
                                    $this->add_error("Invalid input! Only English letters, numbers, spaces, and specific characters (.,'-) are allowed " );
                                    ++$errorCount;
                                    //Don`t allow the spamers to predict the phone validations so just refresh page without any error message.
                                    $referer = $_SERVER['HTTP_REFERER'];
                                    echo "<script>window.location.href='".$referer."';</script>";
                                    exit;
                                }
                                break;
                            case 'email':
                                if (!$this->validate_email($_POST[$name])) {
                                    $this->add_error("Please provide a valid email address.");
                                    ++$errorCount;
                                }
                                break;

                            default:
                                $this->add_error(ucwords(implode(' ', explode("_", $name))) . " validate error.");
                                ++$errorCount;
                                break;
                        }
                    }
                } elseif ($valueArr['type'] == 'file') {
                    array_push($validateFileNameArr, $name);
                    $requiredFileNameArr[$name] = $required;
                }
            }
        }


        if ($errorCount > 0) $ret = false;
        if ($ret  && count($validateFileNameArr) > 0) {
            $ret = $this->validateFileUploads($validateFileNameArr, $requiredFileNameArr);
        }

        // print_r($errorCount);
        // die();

        //Google recaptcha validations
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $captcha = $_POST['g-recaptcha-response'];
            //your site secret key
            $googleRecaptchaKeys = $this->usefulLib->getGoogleRecaptchaKeys();
            $secretKey = $googleRecaptchaKeys['SECRET_KEY'];
            $ip = $_SERVER['REMOTE_ADDR'];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'secret' => $secretKey,
                    'response' => $captcha,
                    'remoteip' => $ip
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $output = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($output);
            if (!$responseData->success) {
                $this->add_error("You are spammer ! Google recaptcha verification failed.");
                $ret = false;
            }
        } else {            
            if(!empty($_POST['scaptcha']) && $_POST['scaptcha']){
                $ret = true;
            }else{
                $this->add_error("Please confirm you are not a robot.");
                $ret = false;
            }
        }


        //Terms and condition check

        return $ret;
    }



    function addFileUploadField($file_field_name, $accepted_types, $max_size)
    {

        $this->fileupload_fields[] =
            array(
                "name" => $file_field_name,
                "file_types" => $accepted_types,
                "maxsize" => $max_size
            );
    }

    function validateFileType($field_name, $valid_filetypes)
    {
        $ret = true;
        $info = pathinfo($_FILES[$field_name]['name']);
        $extn = $info['extension'];
        $extn = strtolower($extn);

        $arr_valid_filetypes = explode(',', $valid_filetypes);
        if (!in_array($extn, $arr_valid_filetypes)) {
            $this->add_error("Valid file types are for $field_name: $valid_filetypes");
            $ret = false;
        }
        return $ret;
    }

    function validateFileSize($field_name, $max_size)
    {
        $size_of_uploaded_file =
            $_FILES[$field_name]["size"] / 1024; //size in KBs
        if ($size_of_uploaded_file > $max_size) {
            $this->add_error("The file is too big. File size should be less than $max_size KB");
            return false;
        }
        return true;
    }

    function isFileUploaded($field_name)
    {
        if (empty($_FILES[$field_name]['name'])) {
            return false;
        }
        if (!is_uploaded_file($_FILES[$field_name]['tmp_name'])) {
            return false;
        }
        return true;
    }

    function validateFileUploads($validateFileNameArr = [], $requiredFileNameArr = [])
    {
        $errorCount = 0;
        foreach ($this->fileupload_fields as $upld_field) {
            $is_valid_file = true;
            $field_name = $upld_field["name"];
            if (in_array($field_name, $validateFileNameArr)) {

                $valid_filetypes = $upld_field["file_types"];

                //Check required, if set to check required....
                if ($requiredFileNameArr[$field_name] && empty($_FILES[$field_name]['name'])) {
                    $this->add_error("Please upload the file; i,e:" . $field_name);
                    $is_valid_file = false;
                    ++$errorCount;
                    continue;
                }

                //Check if valid and not empty(file has value ),then check if it can be uploaded via Post in the server or not...
                if ($is_valid_file && !empty($_FILES[$field_name]['name']) && !$this->isFileUploaded($field_name)) {
                    $this->add_error("Internal Error can`t upload the file via HTTP Post; i,e:" . $field_name);
                    $is_valid_file = false;
                    ++$errorCount;
                    continue;
                }

                if ($is_valid_file && !empty($_FILES[$field_name]['name']) && $_FILES[$field_name]["error"] != 0) {
                    $this->add_error("Error in file upload; Error code:" . $_FILES[$field_name]["error"]);
                    $is_valid_file = false;
                    ++$errorCount;
                    continue;
                }

                if (
                    $is_valid_file && !empty($_FILES[$field_name]['name']) &&
                    !empty($valid_filetypes) &&
                    !$this->validateFileType($field_name, $valid_filetypes)
                ) {
                    $is_valid_file = false;
                    ++$errorCount;
                    continue;
                }

                if (
                    $is_valid_file && !empty($_FILES[$field_name]['name']) &&
                    !empty($upld_field["maxsize"]) &&
                    $upld_field["maxsize"] > 0
                ) {
                    if (!$this->validateFileSize($field_name, $upld_field["maxsize"])) {
                        $is_valid_file = false;
                        ++$errorCount;
                        continue;
                    }
                }
            }
        }

        return ($errorCount == 0) ? true : false;
    }

    /*Collects clean data from the $_POST array and keeps in internal variables.*/
    function collectData()
    {
        $this->name = $this->sanitize($_POST['name'] ?? $_POST['first_name'] ?? $_POST['first_Name'] ?? $_POST['First_Name']  ?? $_POST['First_name'] ??  $_POST['firstName'] ?? $_POST['firstname'] ?? $_POST['FirstName'] ?? 'User');
        $this->name = trim($this->name) ?? 'User';
        $this->email = $this->sanitize($_POST['email'] ?? $_POST['Email'] ?? $_POST['to']  ?? $_POST['To'] ?? '');
        $this->message = $this->sanitize($_POST['message'] ?? $_POST['Message'] ?? $_POST['comment'] ?? $_POST['Comment'] ?? $_POST['comments'] ?? $_POST['Comments'] ?? '');
    }

    function formSubmissionToMail()
    {
        $ret_str = '';
        foreach ($_POST as $key => $value) {
            if (is_array($_POST[$key])) {
                //echo $key."<br>";
                //if($key=='Canada_business_immigration'){
                $value = '<ul>';
                foreach ($_POST[$key] as $data) {
                    $value .= '<li>' . $data . '</li>';
                }
                $value .= '</ul>';
                //}
                //else
                //   $value = implode(", ",$_POST[$key]);	
            }
            if ($key != 'g-recaptcha-response' && $key != 'termsandcond') {
                if (!$this->isInternalVariable($key)) {
                    $key = str_replace("_", " ", $key);
                    $key = ucfirst($key);
                    $ret_str .= '<tr><td height="20" style="padding-left:10px;" width="40%"><b>' . $key . '</b></td><td width="5%">:</td><td width="65%">' . $value . '</td></tr>';
                }
            }
        }
        foreach ($this->fileupload_fields as $upload_field) {
            $field_name = $upload_field["name"];
            if (!$this->isFileUploaded($field_name)) {
                continue;
            }

            $filename = basename($_FILES[$field_name]['name']);
            $ret_str .= '<tr><td height="20" style="padding-left:10px;" width="40%">File upload `' . $field_name . '` :</td><td width="5%">:</td><td width="65%">' . $filename . '</td></tr>';
        }
        return $ret_str;
    }


    function attachFiles()
    {
        $uploads = [];
        $uploadDir = __DIR__ . '/uploads/'; // Directory to store the uploaded files

        // Ensure the uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Iterate through each uploaded file
        foreach ($this->fileupload_fields as $key => $upld_field) {
            $field_name = $upld_field["name"];
            $fileName = basename($_FILES[$field_name]['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES[$field_name]["tmp_name"], $filePath)) {
                $uploads[] = $filePath;  // Add the file path to the uploads array
            } else {
                $errors[] = "Failed to upload the file '$fileName'.";
            }
        }

        return $uploads;
    }

    function sendFormSubmissionToSender($vresponse = null, $messageBody ='', $optnArr=[])
    {
        array_push($this->toRecipents, $this->email); //attach the user email
        
        $independentTemplate = false;
        if($messageBody){
            $formsubmission = $messageBody;
             $independentTemplate = true;
        }else{
            $formsubmission = $this->formSubmissionToMail();
        }
        $attachments = $this->attachFiles();

        $responseArr = $this->template->sendFormSubmissionToSender(
            $this->toRecipents,
            $this->name,
            $formsubmission,
            $vresponse,
            $attachments,
            $this->replyEmail,
            $this->ccRecipents, 
            $this->bccRecipents,
            $independentTemplate,
            $optnArr
        );

        if ($responseArr['result'] === true) {
            return true;
        } else {
            $this->add_error($responseArr['data']);
            return false;
        }
    }
}
