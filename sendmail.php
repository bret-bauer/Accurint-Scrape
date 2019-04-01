<?php
if (! function_exists('SendMail')) { 

function SendMail($to_name, $to_email, $sub, $html,$cc_list="")
{
require_once 'PHPMailerAutoload.php';
//Create a new PHPMailer instance
$mail = new PHPMailer();
$mail->isSMTP();					 	//Tell PHPMailer to use SMTP
$mail->SMTPDebug = 0;					//Enable SMTP debugging 0 = off (for production use) 1 = client messages 2 = client and server messages
$mail->Debugoutput = 'html'; 				//Ask for HTML-friendly debug output
$mail->Host = "smtp.1and1.com"; 			//Set the hostname of the mail server
$mail->Port = 587;				 		//Set the SMTP port number - likely to be 25, 465 or 587
$mail->SMTPAuth = true;				 	//Whether to use SMTP authentication
$mail->Username = "do-not-reply@allytitle.com"; 	//Username to use for SMTP authentication
$mail->Password = "Ally$2015";				//Password to use for SMTP authentication
//Set who the message is to be sent from
$mail->setFrom("do-not-reply@allytitle.com","LexisNexis Scrape");
//Set an alternative reply-to address
$mail->addReplyTo("do-not-reply@allytitle.com","LexisNexis Scrape");
//Set who the message is to be sent to
$mail->addAddress($to_email,$to_name);
if($cclist) {
	$temp=explode(",",$cclist);
	foreach($temp as $cc) {
		$mail->addAddress($cc,"");	
	}
}
//Set the subject line
$mail->Subject = $sub;
$mail->AltBody = "This email can only be viewed as HTML";
$mail->msgHTML($html);
if (!$mail->send()) {
	$mail_err=$mail->ErrorInfo;
	$ans="error";
} else {
	$ans="ok";
}
return($ans);
}

}
