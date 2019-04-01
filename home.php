<?php
date_default_timezone_set("America/Chicago");
session_start();
if($_SESSION['admin'] != "yes") die("access denied");
$stat_mess="";
include("dbopen_new.php");
if(isset($_GET['stop'])) {
	$sql="UPDATE jobs SET live=0,started='' WHERE id=".$_GET['stop'];
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$stat_mess="<font color='green'>Job Being Stopped</font>";
}
if(isset($_GET['start'])) {
	$sql="SELECT live FROM jobs WHERE live=1 ORDER BY id DESC LIMIT 1";
	$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	if($check->num_rows) {
		$stat_mess="<font color='red'>Job already running.  Can not start another.</font>";
	}
	else
	{
		$sql="UPDATE jobs SET live=1,started='".date("Y-m-d H:i:s")."' WHERE id=".$_GET['start'];
		$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
		$stat_mess="<font color='green'>Job Starting</font>";
	}
}

if(isset($_GET['zip'])) {
  $files_to_zip = array(0);
  // move to dir so zip will only contain files, not whole path
  $zip_dir="JOB_".$_GET['zip'];
  chdir("c:/www/rpf/html/ln/".$zip_dir);
  $dir = opendir(".");
  $bytes=0;
   while (($file = readdir($dir)) !== false)
  {
       $ext = substr($file,-4);
	if ($file != "." && $file != ".." && $ext != ".zip") {
		$bytes=$bytes + filesize($file);
		// if($bytes < 50000000) array_push($files_to_zip,$file);
		array_push($files_to_zip,$file);
	}
   }
   closedir($dir);
   $result = create_zip($files_to_zip,$zip_dir.".zip",True);
   if($result == True) {
	// erase files stored in ZIP
	foreach ($files_to_zip as $file)
	{
		// unlink($file);
	}
	// push ZIP file to download
	$file=$zip_dir.".zip";

	if (file_exists($file)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile($file);
		exit;	
	}	
   }
   chdir("..");
   die();
}

// creates a compressed zip file 
function create_zip($files = array(),$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		$zip->open($destination, ZipArchive::CREATE);
		//if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
		//	return false;
		//}
		//add the files
		foreach($valid_files as $file) {
			$zip->addFile($file,$file);
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		
		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>NexisLesxis Accurint Scrape Admin</title>
<meta charset="utf-8">
<link rel="stylesheet" href="admin.css">
<script src="jquery-1.7.1.min.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript"> 
var div;
$(document).ready(function(){
	div = $('#live');
	setInterval(updateMsg, 15000);    /* every 15 seconds */
});

function updateMsg() {
	$.ajax({
		url: "live_status.php",
		cache: false,
		success: function(html){ $(div).html(html); 
			var n1=html.indexOf("Paused");
			var n2=html.indexOf("Finished");
			if(n1 > 0 || n2 > 0) { location.reload(); }
		}
        }); 
}
</script>
</head>
<body>
<div id="container">
<?php include("menu.php"); ?>
<center>
<br>
<h3>Current Job</h3>
<div id="live" style="background:rgba(255,255,255,0.75); overflow:auto; height:100px; text-align:left; padding-top:15px; vertical-align: text-top;  border-radius: 5px;" >
<center><h2><img src="wait.gif" border=0> Please wait.  Connecting to Server.</h2><?php echo $stat_mess; ?></center>
</div>
<br />
<h3>Job History</h3>
<div style="background:rgba(255,255,255,0.75); overflow:auto; height:300px; text-align:left; padding:5px; vertical-align: text-top;  border-radius: 5px;" >
<table width="100%">
<tr>
<th width="5%">Action</th>
<th width="5%">ID</th>
<th width="25%">Job Title</th>
<th width="5%">Records</th>
<th width="5%">Done</th>
<th width="16%">Created</th>
<th width="16%">Started</th>
<th width="16%">Completed</th>
</tr>
<?php
$sql="SELECT * FROM jobs ORDER BY id DESC LIMIT 100";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
while($info = $check->fetch_assoc()) {
	$actions="<a href='home.php?start=".$info['id']."'><img src='play.png' border=0 title='start job'></a>";
	if($info['live']) $actions="<a href='home.php?stop=".$info['id']."'><img src='stop.png' border=0 title='stop job'></a>";
	if($info['completed']) $actions="<a href='home.php?zip=".$info['id']."'><img src='zip.png' border=0 title='download zip file of completed job'></a>";
	echo "<tr>";
	echo "<td align='center'>".$actions."</td>";
	echo "<td align='center'>".$info['id']."</td>";
	echo "<td>".$info['title']."</td>";	
	echo "<td align='center'>".$info['recs_total']."</td>";
	echo "<td align='center'>".$info['recs_done']."</td>";
	echo "<td>".fix_timestamp($info['uploaded'])."</td>";
	echo "<td>".fix_timestamp($info['started'])."</td>";
	echo "<td>".fix_timestamp($info['completed'])."</td>";
	echo "</tr>";
}
$db->close();

function fix_timestamp($str) {
if($str=="") return("");
$ans=substr($str,5,5)."-".substr($str,0,4);
$ampm="a";
$hr=(int) substr($str,11,2);
$min=substr($str,14,2);
if($hr > 11) {$ampm="p";}
if($hr > 12) {$hr=$hr-12; }
if($hr==0) { $hr="12"; $ampm="a"; }
$ans.=" ".$hr.":".$min.$ampm;
if(strlen($ans) < 5) $ans="";
return($ans);
}

?>
</table>
</div>

</div>
</body>
</html>
