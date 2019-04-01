<?php
date_default_timezone_set("America/Chicago");
session_start();
if($_SESSION['admin'] != "yes") die("access denied");

function ValChars($stf)
{
$valid_chars="00123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_. ".CHR(34);
$ans="";
$lgth=strlen($stf);
for ($cnt = 0; $cnt < $lgth; $cnt++) {
	$char = substr($stf, $cnt, 1);
	if($char=="0") {
		$ans.="0";
	}
	else
	{
		if(strpos($valid_chars,$char) != false) { $ans.=$char; }
	}
}
return $ans;
}

if(isset($_FILES['uploadfile'])) {

$ext = strtoupper(strrchr($_FILES['uploadfile']['name'], '.'));
$mess = "<big><font size=5 color='red'>You can only upload CSV files.</font></big>";

if ($ext==".CSV")
   {
   if ($_FILES["uploadfile"]["error"] > 0)
      {
      $mess = "<font size=5 color='red>".$_FILES["uploadfile"]["error"]."</font>";
      }
   else
      {
      include("dbopen_new.php");
      $mydir = "c:/www/rpf/html/ln/csv_files";
      // if (! is_dir($mydir) ) { mkdir($mydir,0777,TRUE); }
      $temp=ValChars($_FILES["uploadfile"]["name"]);
	$temp=str_replace(".csv","",$temp);
	$temp=str_replace(".CSV","",$temp);      
	$r=rand(10000, 99999);
	$rid=dechex($r);
      $fname=$temp."_".strtoupper($rid).".csv";
      move_uploaded_file($_FILES["uploadfile"]["tmp_name"],$mydir."/".$fname);
      
	if(! isset($_POST['tlo_acct'])) $_POST['tlo_acct']="";
	// create job record
	if(! $_POST['title']) $_POST['title']="Job ".date("m-d-Y g:ia");
	$sql="INSERT INTO jobs (title,file,email,tlo_acct,uploaded) VALUES ('".MQ($_POST['title'])."','".MQ($fname)."',";
	$sql.="'".MQ($_POST['email'])."','".MQ($_POST['tlo_acct'])."','".date("Y-m-d H:i:s")."')";      
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$job_id=$db->insert_id;
	// add ssn's to table
	$rec_cnt=0;
	if (($handle = fopen($mydir."/".$fname, "r")) !== FALSE) {
		// assign to different threads for concurrent processing
		$thread=1;
		$data = fgetcsv($handle, 500, ",");   // pull of header
		while (($data = fgetcsv($handle, 500, ",")) !== FALSE) { 
			$debt_id=MQ($data[0]);
			$party_id=MQ($data[1]);
			$ssn=MQ(preg_replace('/[^0-9]/', '', $data[2]));
			$fn=MQ($data[3]);
			$ln=MQ($data[4]);
			$addr=MQ($data[5]);
			$city=MQ($data[6]);
			$state=MQ($data[7]);
			$zip=MQ($data[8]);			
			$sql="INSERT INTO ssn (job_id,debt_id,party_id,ssn,first_name,last_name,address,city,state,zip,thread) VALUES(";
			$sql.="$job_id,'$debt_id','$party_id','$ssn','$fn','$ln','$addr','$city','$state','$zip',$thread)";
			$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
			$rec_cnt++;
			$thread++;
			if($thread > 2) $thread=1;
		}
	}
	// update total records in job
	$sql="UPDATE jobs SET recs_total=$rec_cnt WHERE id=$job_id";
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);

      $db->close();
      $mess="<font color='green'>File uploaded: ".ValChars($_FILES["uploadfile"]["name"])." - Added $rec_cnt records.</font>";
}
}  
}

// clean string
function MQ($s) {
	global $db;
	return $db->real_escape_string($s);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Transunion TLO Scrape Admin</title>
<meta charset="utf-8">
<link rel="stylesheet" href="admin.css">

</head>
<body onload="document.forms.upload.title.focus();">
<div id="container">
<?php include("menu.php"); ?>
<center>
<br><br>
<form name="upload" method="post" action="upload.php" enctype="multipart/form-data" target="_self" >
Job Title<br>
<input name="title" type="text" size="30"><br><br>
Notification Email<br>
<input name="email" type="text" size="30" value="davidbrooks@usecapital.com"><br><br>
<!--
TLO Acct<br>
<input name="tlo_acct" type="text" size="30" value="CA.ONE"><br><br>
-->
Filename:<br>
<input type="file" name="uploadfile" id="uploadfile" size="50"><br><br>
<input type="submit" name="submit" value=" Submit File " onclick="upload.submit.value='Please wait...';return true"><br><br>
</form>
<br><br>
<?php
if($mess) echo $mess;
?>
<h3>Note: CSV columns must be formatted as follow:<br>
debt_id, party_id, ssn, first_name, last_name,address,city,state,zip</h3>
</div>
</body>
</html>

