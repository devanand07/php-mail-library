<?php 
/*
    Form Submission library.
    This program is free library published under the
    terms of the General Public License.
    See this page for more info:
    https://github.com/devanand07/php-mail-library
*/


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// ini_set('max_execution_time', 60); // Increase to 60 seconds for testing

require_once __DIR__ . '/assets/MailLibrary/ComposeMailer.php';
require_once __DIR__ . '/assets/MailLibrary/UsefulLib.php';


$usefulLib = new \MailLibrary\UsefulLib();
$googleRecaptchaKeys = $usefulLib->getGoogleRecaptchaKeys();

// Error messages initialization
$errorMessages = "";


if(isset($_POST['submitted']))
{
	
	$validateRequiredDataArr = [
		'name'=> ['type'=>'text','required'=>true,'checkMaxChar'=>50],
		'email'=> ['type'=>'email','required'=>true,'checkMaxChar'=>50,'validate'=>'email'],
        'phone' => ['type'=>'text','required'=>true],
        // 'Resume1' => ['type'=>'file','required'=>true],
	];

    $fromArr = ['name'=>'TAS Website','email'=>'info@tastechnologies.com'];
    $toArr = ['bedh@tastechnologies.com'];
//    $toArr = ['info@tastechnologies.com','bestnbest2u@gmail.com','testing@tastechnologies.com'];
    // $cc = ['bedh007@gmail.com'];
    // $bcc = ['bedh.zcb@gmail.com', 'authversova@gmail.com'];
    $composeMailer = new \MailLibrary\ComposeMailer('phpmailer',$fromArr, $toArr, $cc ='', $bcc='');
    
//    $composeMailer->AddFileUploadField('UploadPicture1','jpg,jpeg,gif,png,bmp,doc,docx,pdf',2024);
  //  $composeMailer->AddFileUploadField('UploadPicture2','jpg,jpeg,gif,png,bmp,doc,docx,pdf',2024);

    $result = $composeMailer->processFormToSender($validateRequiredDataArr);
    if ($result === true) {
        // echo 'Email sent successfully!';
        header("Location:thankyou.html");
        exit;
    } else {
        $errorMessages = $composeMailer->error_message; // Store errors to be displayed
    }
}

?>

<form method='post' enctype="multipart/form-data" name="contactus" id='contactus' accept-charset='UTF-8'  onsubmit="return checkformVal(this);">


<input type='hidden' name='submitted' id='submitted' value='1'/>
                

                <div><span class='error'><?php echo $errorMessages; ?></span></div>
<div class="row">
<div class="col-md-6">
<div class="form-group">
<input type="text" class="form-control" name="name" placeholder="Your Name" required>
</div>
</div>
<div class="col-md-6">
<div class="form-group">
<input type="email" class="form-control" name="email" placeholder="Your Email" required>
</div>
</div>
</div>
<div class="form-group">
<input type="text" class="form-control" name="phone" placeholder="Your Phone" required>
</div>
<div class="form-group">
<textarea name="message" cols="30" rows="5" class="form-control" placeholder="Write Your Message"></textarea>
</div>
	<div class="form-group">
<div class="g-recaptcha my-4" data-sitekey="<?= $googleRecaptchaKeys['SITE_KEY']; ?>"></div>	
</div>
<button type="submit" class="theme-btn theme-btn2">Send
Message <i class="far fa-paper-plane"></i></button>
<div class="col-md-12 mt-3">
<div class="form-messege text-success"></div>
</div>
</form>
