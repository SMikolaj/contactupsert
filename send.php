<?php 
// Pear library includes
// You should have the pear lib installed
include_once('Mail.php');
include_once('Mail/mime.php');

//Settings 
$max_allowed_file_size = 2000000; // size in KB 
$allowed_extensions = array("jpg", "jpeg", "gif", "bmp");
$upload_folder = './uploads/'; //<-- this folder must be writeable by the script
$your_email = 'mail@inyourdomain.com';//<<--  adres email z którego będziemy wysyłać wiadomości - do którego mamy uprawnienia SMTP

ini_set("display_errors", "1");
ERROR_REPORTING(E_ALL);

function do_post_request($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
                array(
                     'Content-Type: application/json',
                     'Content-Length: ' . strlen($data)
                )
    );
    return curl_exec($ch);
}
$errors ='';

if(isset($_POST['submit']))
{
	$name = $_POST['name'];
    $email = $_POST['email'];
	$clientId = '****************'; //TU PROSZĘ WPISAĆ CLIENTID (SALESmanago > MENU > Ustawienia > Integracja)
    $apiKey = 'FUAYC8W4GYVUIKER'; //Losowy ciąg znaków
    $apiSecret = '****************'; //TU PROSZĘ WPISAĆ APISECRET (SALESmanago > MENU > Ustawienia> Integracja)
	$endpoint = "www.salesmanago.pl"; //PROSZĘ WPISAĆ ENDPOINT - (SALESmanago > MENU > Ustawienia > Integracja
	
	$data = array( 
		'clientId' => $clientId,
		'apiKey' => $apiKey, 
		'requestTime' => time(), 
		'sha' => sha1($apiKey . $clientId . $apiSecret), 
		'contact' => array(
			 'email' => $email, 
			 'name' => $name, 
			 'state' => 'CUSTOMER',
			  ), 
		'owner' => '************', //TU PROSZĘ DODAĆ EMAIL WŁAŚCICIELA KONTAKTU - konto istniejące w SALESmanago
		'tags' => array('Formularz_rejestracji'), 
		'removeTags' => array('Tag_do_usuniecia'),
		'properties' => array('page' => 'rejestracja'), 
		'lang' => 'PL',
		'useApiDoubleOptIn' => true,
		'forceOptIn' => true,
		'forceOptOut' => false,
		'forcePhoneOptIn' => true,  
		'forcePhoneOptOut' => false 
		);

    $json = json_encode($data);

    $result = do_post_request('http://' . $endpoint .'/api/contact/upsert', $json);
	$r = json_decode($result);
	
	//Get the uploaded file information
	$name_of_uploaded_file =  basename($_FILES['uploaded_file']['name']);
	
	//get the file extension of the file
	$type_of_uploaded_file = substr($name_of_uploaded_file, 
							strrpos($name_of_uploaded_file, '.') + 1);
	
	$size_of_uploaded_file = $_FILES["uploaded_file"]["size"]/1024;
	
	///------------Do Validations-------------
	if(empty($_POST['name'])||empty($_POST['email']))
	{
		$errors .= "\n Name and Email are required fields. ";	
	}
	
	if($size_of_uploaded_file > $max_allowed_file_size ) 
	{
		$errors .= "\n Size of file should be less than $max_allowed_file_size";
	}
	
	//------ Validate the file extension -----
	$allowed_ext = false;
	for($i=0; $i<sizeof($allowed_extensions); $i++) 
	{ 
		if(strcasecmp($allowed_extensions[$i],$type_of_uploaded_file) == 0)
		{
			$allowed_ext = true;		
		}
	}
	
	if(!$allowed_ext)
	{
		$errors .= "\n The uploaded file is not supported file type. ".
		" Only the following file types are supported: ".implode(',',$allowed_extensions);
	}
	
	//send the email 
	if(empty($errors))
	{
		//copy the temp. uploaded file to uploads folder
		$path_of_uploaded_file = $upload_folder . $name_of_uploaded_file;
		$tmp_path = $_FILES["uploaded_file"]["tmp_name"];
		
		if(is_uploaded_file($tmp_path))
		{
		    if(!copy($tmp_path,$path_of_uploaded_file))
		    {
		    	$errors .= '\n error while copying the uploaded file';
		    }
		}
// SEND MAIL 
$from = "Admin <admin@sm-shop.pl>";
$to = "User <mikolaj.janus.benhauer@gmail.com>"; // ustawiamy adres na jaki mają być przesyłane wiadomości wysłane z formularza
$subject = "Hi!"; // temat wiadomości 

$name = $_POST['name'];
$visitor_email = $_POST['email'];
$user_message = $_POST['message'];
$from = $your_email;
$text = "A user  $name has sent you this message:\n $user_message";

$host = "***********"; // dane hosta serwera SMTP
$username = "***********"; // login SMTP
$password = "***********"; // hasło SMTP
 
$message = new Mail_mime(); 
$message->setTXTBody($text); 
$message->addAttachment($path_of_uploaded_file);
$body = $message->get();
$extraheaders = array("From"=>$from, "Subject"=>$subject,"Reply-To"=>$visitor_email);
$headers = $message->headers($extraheaders);
$smtp = Mail::factory('smtp',
array ('host' => $host,
     'auth' => true,
     'username' => $username,
     'password' => $password));
$mail = $smtp->send($to, $headers, $body);
 
 if (PEAR::isError($mail)) {
   echo("<p>" . $mail->getMessage() . "</p>");
  } else {
   echo("<p>Message successfully sent!</p>");
	} 
	}
}
///////////////////////////Functions/////////////////
// Function to validate against any email injection attempts
function IsInjected($str)
{
  $injections = array('(\n+)',
              '(\r+)',
              '(\t+)',
              '(%0A+)',
              '(%0D+)',
              '(%08+)',
              '(%09+)'
              );
  $inject = join('|', $injections);
  $inject = "/$inject/i";
  if(preg_match($inject,$str))
    {
    return true;
  }
  else
    {
    return false;
  }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html>
<head>
	<title>SALESmanago - file upload form with email and API request</title>
<!-- define some style elements-->
<style>
label,a, body 
{
	font-family : Arial, Helvetica, sans-serif;
	font-size : 12px; 
}

</style>	
<!-- a helper script for vaidating the form-->
<script language="JavaScript" src="scripts/gen_validatorv31.js" type="text/javascript"></script>	
<link rel="stylesheet" type="text/css" href="form.css">
</head>
<body>
<?php
if(!empty($errors))
{
	echo nl2br($errors);
}
?>
<div class="wrapper">
<div class="right">
<form name="email_form_with_php" method="post" action="send.php" ENCTYPE="multipart/form-data">
    <table width="400" border="0" cellpadding="0" cellspacing="1">
        <tr>
            <td><input name="name" type="text" id="name" size="50" placeholder="Imię i nazwisko"></td>
        </tr>
        <tr>
            <td><input name="email" type="text" id="email" size="50" placeholder="Email" required></td>
        </tr>
		<tr>
            <td><textarea name="message" type="text" id="message" placeholder="Wiadomość"></textarea></td>
        </tr>
		<tr>
		    <td><input type="file" id="plik" name="uploaded_file"/></td>
		</tr>
        <tr>
            <td>
                <input type="submit" id="submit" name="submit" value="Wyślij">
            </td>
        </tr>
    </table>
</form>
</div>
</div>	
<script language="JavaScript">
// Code for validating the form
// Visit http://www.javascript-coder.com/html-form/javascript-form-validation.phtml
// for details
var frmvalidator  = new Validator("email_form_with_php");
frmvalidator.addValidation("name","req","Please provide your name"); 
frmvalidator.addValidation("email","req","Please provide your email"); 
frmvalidator.addValidation("email","email","Please enter a valid email address"); 
</script>
<noscript>
<small><a href='http://www.html-form-guide.com/email-form/php-email-form-attachment.html'
>How to attach file to email in PHP</a> article page.</small>
</noscript>

</body>
</html>