<?php
date_default_timezone_set("America/Chicago");
chdir('/www/rpf/html/ln');
include("dbopen_new.php");
$sql="SELECT * FROM jobs WHERE live=1 ORDER BY id DESC LIMIT 1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
$job_info = $check->fetch_assoc();
if($job_info['live']) {
	echo "<center><font size=5>Working on ".$job_info['title'];
	$rd=$job_info['recs_done'];
	if($rd < 1) $rd=1;
	if($job_info['done_flag']) {
		echo "<br>".$job_info['recs_done']." of ".$job_info['recs_total']." processed.";
		echo "<br>Job Finished.";
		$sql="UPDATE jobs SET done_flag=0,live=0 WHERE id=".$job_info['id'];
		$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	}
	else
	{
		$tr=$job_info['recs_total'];
		if($tr) {$per=$rd/$tr; $per=intval($per*100); } else { $per=0; }
		echo "<br>Processing record $rd of ".$job_info['recs_total']." - $per% complete</font><br>";
		echo "Started at ".substr(fix_timestamp($job_info['started']),-7)." - Time now ".date("g:ia");
	}
}
else
{
	echo "<center><h1>No Job Currently Running.</h1>";
}
$db->close();

function fix_timestamp($str) {
if($str=="") return("");
$ans=substr($str,5,5)."-".substr($str,0,4);
$ampm="am";
$hr=(int) substr($str,11,2);
$min=substr($str,14,2);
if($hr > 11) {$ampm="pm";}
if($hr > 12) {$hr=$hr-12; }
if($hr==0) { $hr="12"; $ampm="am"; }
$ans.=" ".$hr.":".$min.$ampm;
if(strlen($ans) < 5) $ans="";
return($ans);
}

?>