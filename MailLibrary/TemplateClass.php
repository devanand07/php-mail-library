<?PHP

namespace MailLibrary;

/**
 * Created by Tas Technologies
 * Autor: Bedh Prakash Roy
 */

class TemplateClass
{
    private $mailer;

    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }


    function getMailStyle()
    {
        $retstr = "\n<style>" .
            "body,.label,.value { font-family:Arial,Verdana; } " .
            ".label {font-weight:bold; margin-top:5px; font-size:1em; color:#333;} " .
            ".value {margin-bottom:15px;font-size:1.0em;padding-left:5px;} " .
            "</style>\n";
        return $retstr;
    }

    public function getHTMLHeaderPart()
    {
        $retstr = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' . "\n" .
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title></title>' .
            '';
        $retstr .= $this->getMailStyle();
        $retstr .= '<table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#fff; font-family:Arial, Helvetica, sans-serif; font-size:12px; border:1px solid #006; color:#000;" width="650">
    <tbody>
        <tr>
            <td align="center" style="padding:10px;" valign="top">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    <tr>
                        <td align="center" style="background: #fff;"><img src="https://bickfordvet.com/images/logo.png" /></td>
                        
                        
                    </tr>
                </tbody>
            </table>
            </td>
        </tr>';
        $retstr .= '</head><body>';
        return $retstr;
    }

    public function getHTMLFooterPart()
    {
        $retstr = '<tr>
			<td align="center" bgcolor="#005b7e" height="70" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#ffffff;font-weight:normal; " valign="middle">
			<table border="0" cellpadding="0" cellspacing="0" width="95%">
				<tbody>
					<tr>
						<td width="53%">Call Us: <a href="tel:6473478387" value="6473478387" target="_blank" style="color:#fff"> (647) 347-8387 (VETS)</a><br />
						
						Email Id: <a href="mailto:info@bickfordvet.com" style="color:#fff;"> info@bickfordvet.com</a>
						&nbsp;</td>
						
						<td align="right" width="47%">  Address: 807 Bloor St W, Toronto, ON
             </td>
					</tr>
				</tbody>
			</table>
			</td>
		</tr>
	</tbody>
</table>
</body>
</html>';
        return $retstr;
    }

    public function composeFormtoEmailToSender($formsubmission, $vpurpose = null)
    {
        $header = $this->getHTMLHeaderPart();
        $footer = $this->getHTMLFooterPart();

        $message = $header;
        $message .= '<tr>
			<td style="padding:10px 20px; " valign="top">
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td height="20" style="background-color:#ECECEC;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:7px 0 6px 13px;text-transform:uppercase; border:1px solid #ECECEC; border-bottom:0px;">Detail:</td>
					</tr>
					<tr>
						<td bgcolor="#fff" style="padding:10px 10px; border:1px solid #ECECEC;">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tbody>
								<tr>
									<td valign="top" width="80%">
									<table cellpadding="0" cellspacing="0" width="100%">
										<tbody>';
        $message .= $formsubmission;
        $vresponse = "We have received your Form Submission our Staff will be contacting you within 24 hours.";
        if (!empty($vpurpose) && trim($vpurpose) == 'Business Immigration')
            $vresponse = "We acknowledge receipt of your Business Immigration Assessment Questionnaire. Your information will remain confidential with us and will be used for your case assessment purposes only. We will revert to you soon. If we assess that you appear to qualify in any of the programs, our licensed Canadian Immigration Practitioner will book a Zoom video call with you to discuss his assessment on a one to one basis.";
        $message .= '</tbody></table><tr><td style="width:100%"><h1>Thank you for contacting us.</h1><br /><br /> ' . $vresponse . ' <br /><br /><strong>Have a great day ahead!</strong></tr></td>';
        $message .= '</tbody>
									</table>
									</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td bgcolor="#FFFFFF" height="5">&nbsp;</td>
					</tr>
			</td>
		</tr>';
        $message .= $footer;
        return $message;
    }

    public function sendFormSubmissionToSender($toRecipients, $recipentName = 'User', $formsubmission = 'TESTING MESSAGE BODY',  $vresponse = '', $attachments = [], $replyEmail=false, $cc = [], $bcc = [], $independentTemplate=false, $optnArr=[])
    {
        $responseArr = ['result' => false, 'message' => '', 'data' => ''];
        
        $subject = "Mail from $recipentName";
        if(!empty($optnArr['subject'])){
            $subject = $optnArr['subject'];
        }
        $message = $independentTemplate ? $formsubmission : $this->composeFormtoEmailToSender($formsubmission, $vresponse);
        $altBody = @html_entity_decode($message, ENT_QUOTES, "UTF-8");

        $result = $this->mailer->send($toRecipients, $subject, $message, $altBody, $replyEmail, $cc, $bcc, $attachments);
        // Check result and display appropriate message
        if ($result === true) {
            $responseArr['result'] = true;
            $responseArr['message'] = 'Email sent successfully!';
        } else {
            $responseArr['message'] = 'Error! Email not sent.';
            $responseArr['data'] = $result;
        }

        return $responseArr;
    }
}
