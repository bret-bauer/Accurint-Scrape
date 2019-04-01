<?php
date_default_timezone_set("America/Chicago");
chdir('/www/rpf/html/ln');
set_time_limit(1770);   // 2 9:30 minutes
$thread=1;

if(isset($_GET['thread'])) $thread=$_GET['thread'];
if(isset($argv[1])) $thread=$argv[1];
include("sendmail.php");
function AddLog($mess) {
	global $thread;
	file_put_contents("cron_log_$thread.txt",date("Y-m-d g:i:sa")." ($thread) - ".$mess."\r\n",FILE_APPEND);
	echo "($thread) $mess".chr(13).chr(10);	
	// echo $mess."<br>";
}
function JobLog($job,$mess) {
	global $thread;
	file_put_contents("JOB_$job/job_log.txt",$mess."\r\n",FILE_APPEND);
	echo "($thread) $mess".chr(13).chr(10);	
	// echo $mess."<br>";
}

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

function CvtSpace($s) {
$ans=str_replace(" ","<SP>",$s);
return($ans);
}

include("dbopen_new.php");
// grab acct info
$sql="SELECT * FROM accounts WHERE id=1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
$acct_info = $check->fetch_assoc();

$debug=true;
AddLog("===== Begin Scraping Accurint ===== ".date("Y-m-d g:i:sa"));

$sql="SELECT * FROM jobs WHERE live=1 ORDER BY id DESC LIMIT 1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);

if(! $check->num_rows) {
	$db->close();
	AddLog("No jobs running.".chr(13).chr(10));
	sleep(10);
	die();
}
$job_info = $check->fetch_assoc();
$job_id=$job_info['id'];

if( $thread==1 and (date("i")=="01" or date("i")=="31") ) {
	AddLog("Running thread balance of remaining debtors...");
	$sql="SELECT id FROM ssn WHERE job_id=$job_id  AND status=0";
	$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$qty=$check->num_rows;
	if($qty < 1000) {
		$tc=1;
		$ab=0;
		while($info = $check->fetch_assoc()) {
			$did=$info['id'];
			$sql="UPDATE ssn SET thread=$tc WHERE id=$did";
			$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
			$tc++;
			if($tc > 2) $tc=1;
			$ab++;
		}
		AddLog("$ab balanced - done.   $qty records left.");
	}
	else
	{
		AddLog("no need to balance at this time. $qty records left.");
	}
}

// check to see if  any records for this thread to process
$sql="SELECT id FROM ssn WHERE job_id=$job_id AND status=0 AND thread=$thread LIMIT 1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
if(! $check->num_rows) {
	$db->close();
	AddLog("No records to process for this thread. Job aborted.");
	sleep(10);
	die();
}

// get total records left for this job
$sql="SELECT id FROM ssn WHERE job_id=$job_id AND status=0";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
$qty_left=$check->num_rows;

AddLog("Running job $job_id");

//create job folder if not there
$mydir="c:/www/rpf/html/ln/JOB_".$job_id."/";
$win_mydir="c:\www\\rpf\html\ln\JOB_".$job_id;
if (! is_dir($mydir) ) mkdir($mydir,0777,TRUE);

$deduct=0; 

if(! file_exists("JOB_$job_id/job_log.txt")) {
	JobLog($job_id,"JOB_$job_id Accurint Scrape started ".date("Y-m-d g:i:sa"));
	// SendMail("Bret Bauer", "bretbauer@yahoo.com", "Accurint JOB_$job_id just started", "job started ".date("Y-m-d g:i:sa")." - ".$qty_left." debtors");
	if($thread==1) {
		$warmup=0;
		while($warmup < 3) {
			$temp=file_get_contents("lexisnexis1_77882.png");
			file_put_contents("lexisnexis1.png",$temp);
			file_put_contents("token1.txt","00000");
			sleep(4);
			$temp=file_get_contents("token1.txt");
			if($temp=="77882") $warmup=9;
			$warmup++;
			$deduct++;
		}
	}
}
// 02/06/2018  - last pw Pita1112
$user_id=$acct_info['acct_1'];  $user_pass=$acct_info['pass_1'];
if($thread==2) { $user_id=$acct_info['acct_2'];  $user_pass=$acct_info['pass_2']; }  

// try to pass CAPTCHA
$macro="VERSION BUILD=8961227 RECORDER=FX
SET !REPLAYSPEED MEDIUM
SET !TIMEOUT_STEP 1
TAB T=1
TAB CLOSEALLOTHERS
URL GOTO=https://secure.accurint.com/app/bps/main
WAIT SECONDS = 2
SCREENSHOT TYPE=Browser FOLDER=C:\www\\rpf\html\ln FILE=lexisnexis$thread.png
TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:LOGIN ATTR=ID:login_id CONTENT=$user_id
WAIT SECONDS = 5
SET !DATASOURCE C:\www\\rpf\html\ln\\token$thread.txt
TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:LOGIN ATTR=ID:vari_char CONTENT={{!COL1}}
WAIT SECONDS = 3
CLICK X=556 Y=307
SET !ENCRYPTION NO
WAIT SECONDS = 1
SAVEAS TYPE=TXT FOLDER=C:\www\\rpf\html\ln\log FILE=last_captcha$thread.txt
TAG POS=1 TYPE=INPUT:PASSWORD FORM=NAME:LOGIN ATTR=ID:_password CONTENT=$user_pass
WAIT SECONDS = 1
CLICK X=547 Y=169
WAIT SECONDS = 1
SET !ERRORIGNORE YES
TAG POS=1 TYPE=A ATTR=TXT:Person<SP>Search<SP>Plus
WAIT SECONDS = 1
TAG POS=1 TYPE=SELECT FORM=NAME:GLB ATTR=NAME:DPPA CONTENT=%3
WAIT SECONDS = 1
TAG POS=1 TYPE=SELECT FORM=NAME:GLB ATTR=NAME:GLB_PURPOSE CONTENT=%1
WAIT SECONDS = 1
TAG POS=1 TYPE=SELECT FORM=NAME:GLB ATTR=NAME:DMF_PURPOSE CONTENT=%00
WAIT SECONDS = 1
TAG POS=1 TYPE=INPUT:BUTTON FORM=NAME:GLB ATTR=NAME:confirm_button";
$macro.=chr(10);

// build iMacro
$minf=42-$deduct; $maxf=43-$deduct;
$how_many=rand($minf,$maxf);
$cur_min=date("i");
if($cur_min > 2 AND $cur_min < 30) $how_many=$how_many-intval($cur_min*1.6);
if($cur_min > 32 AND $cur_min < 59) $how_many=$how_many-intval(($cur_min-30)*1.6);

$this_batch=array();
// start loop here of SSN's to process
$sql="SELECT * FROM ssn WHERE job_id=$job_id AND status=0 AND thread=$thread LIMIT $how_many";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
$qty=$check->num_rows;
AddLog("Building iMacro for $qty debtors");
while($info = $check->fetch_assoc()) {
	$this_batch[]=$info['id'];   // save list of debtors processed
	$file_name=ValChars($info['debt_id'])."_".ValChars($info['party_id'])."_".ValChars($info['first_name'])."_".ValChars($info['last_name']);
	$file_name=str_replace(" ","_",$file_name);
	// add debtor searches
	$macro.="WAIT SECONDS = 2".chr(10);
	// clear form
	$macro.="TAG POS=3 TYPE=INPUT:BUTTON FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:BUTTON".chr(10);
	$macro.="SET !REPLAYSPEED FAST".chr(10);
	if($info['ssn']) {
		//search by ssn
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:SSN CONTENT=".$info['ssn'].chr(10);
	}
	else
	{
		// search by name and address
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:LAST_NAME CONTENT=".CvtSpace($info['last_name']).chr(10);
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:FIRST_NAME CONTENT=".CvtSpace($info['first_name']).chr(10);
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:STREET_ADDRESS CONTENT=".CvtSpace($info['address']).chr(10);
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:CITY CONTENT=".CvtSpace($info['city']).chr(10);
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:STATE CONTENT=".$info['state'].chr(10);
		$macro.="TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:ZIP CONTENT=".$info['zip'].chr(10); 
	}
	$macro.="WAIT SECONDS = 2".chr(10);
	$macro.="TAG POS=1 TYPE=INPUT:BUTTON FORM=NAME:PERSON_SEARCH_PLUS ATTR=NAME:BUTTON".chr(10);
	$macro.="WAIT SECONDS = 5".chr(10); 
	$macro.="SET !REPLAYSPEED SLOW".chr(10);
	$macro.="TAG POS=1 TYPE=A ATTR=TXT:Export<SP>to<SP>Excel".chr(10);
	$macro.="TAB T=2".chr(10);
	if($thread==1) $macro.="ONDOWNLOAD FOLDER=$win_mydir FILE=$file_name".chr(10);
	if($thread==2) $macro.="ONDOWNLOAD FOLDER=$win_mydir FILE=$file_name".".csv".chr(10);
	$macro.="TAG POS=1 TYPE=INPUT:BUTTON FORM=NAME:SEARCH_DOWNLOAD ATTR=NAME:DOWNLOAD_BUTTON".chr(10);
	$macro.="WAIT SECONDS = 4".chr(10);
	$macro.="TAG POS=2 TYPE=INPUT:BUTTON FORM=NAME:SEARCH_DOWNLOAD ATTR=NAME:DOWNLOAD_BUTTON".chr(10);
	$macro.="WAIT SECONDS = 2".chr(10);
	$macro.="SET !REPLAYSPEED MEDIUM".chr(10);
	$macro.="TAB T=1".chr(10);
	$macro.="WAIT SECONDS = 1".chr(10);
	$macro.="TAG POS=1 TYPE=A ATTR=TXT:New<SP>Search".chr(10);
}

// all done sign out
$macro.="WAIT SECONDS = 1".chr(10);
$macro.="TAG POS=1 TYPE=A ATTR=TXT:Sign<SP>Out".chr(10);
// shut down firefox
$macro.="WAIT SECONDS = 1".chr(10);
$macro.="SET !REPLAYSPEED FAST".chr(10);
$macro.="TAB CLOSEALLOTHERS".chr(10);
$macro.="TAB T=1".chr(10);
$macro.="TAB CLOSE".chr(10);
// write out macro file
file_put_contents("c:\Users\Administrator\Documents\iMacros\Macros\scrape_job".$thread.".iim",$macro);

// execute firefox and macro
if($thread==1) AddLog("Starting Firefox and Running Macro for $qty debtors");
if($thread==2) AddLog("Starting Palemoon and Running Macro for $qty debtors");
if(file_exists("C:/www/rpf/html/ln/log/last_captcha".$thread.".txt")) {
	unlink("C:/www/rpf/html/ln/log/last_captcha".$thread.".txt");
}
if($thread==1) AddLog("About to start Firefox");
if($thread==2) AddLog("About to start Palemoon");
sleep(1);

if($thread==1) {
	exec('"C:\Program Files (x86)\Mozilla Firefox\firefox.exe" imacros://run/?m=scrape_job'.$thread.'.iim');
}
if($thread==2) {
	exec('"C:\Program Files (x86)\Pale Moon\palemoon.exe" imacros://run/?m=scrape_job'.$thread.'.iim');
}
if($thread==1) AddLog("Done with Firefox");
if($thread==2) AddLog("Done with Palemoon");
sleep(1);

AddLog("Updating record status based on scrape");	
sleep(1);
$all_hit=0; $miss_sql=array();

// select list again and look for CSV files - make sure at least one hit
foreach ($this_batch as $rec_id) {
	$sql="SELECT * FROM ssn WHERE id = $rec_id";
	$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$info = $check->fetch_assoc();
	$file_name=ValChars($info['debt_id'])."_".ValChars($info['party_id'])."_".ValChars($info['first_name'])."_".ValChars($info['last_name']);
	$file_name=str_replace(" ","_",$file_name);
	$tit=$file_name;
	$phones=array();
	if(file_exists($mydir.$file_name.".csv")) {
		$all_hit++;
	}
}

// check if last record or so is a no hit but continnue anyway to fininsh job
$sql="SELECT id FROM ssn WHERE job_id=$job_id  AND status=0";
$end_chk=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
if($end_chk->num_rows < 5) $all_hit=1;

if($all_hit) {

// select list again and look for CSV files
foreach ($this_batch as $rec_id) {
	$sql="SELECT * FROM ssn WHERE id = $rec_id";
	$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$info = $check->fetch_assoc();
	$file_name=ValChars($info['debt_id'])."_".ValChars($info['party_id'])."_".ValChars($info['first_name'])."_".ValChars($info['last_name']);
	$file_name=str_replace(" ","_",$file_name);
	$tit=$file_name;
	$phones=array();
	if(file_exists($mydir.$file_name.".csv")) {
		$tit.="-HIT ";
		$all_hit++;
		// mark record as hit
		$sql="UPDATE ssn SET status=1 WHERE id = $rec_id";
		$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
		// extract phone numbers for CSV export
		$csvData=file_get_contents($mydir.$file_name.".csv");
		$lines = explode("\n", $csvData);
		foreach ($lines as $line) {
			if(stristr($line,"- EST") or stristr($line,"- CST") or stristr($line,"- MST") or stristr($line,"- PST")) {
				$hit=strpos($line,"- EST"); 
				if($hit) $phones[]=preg_replace('/[^0-9]/', '',substr($line,$hit-13,12));
				$hit=strpos($line,"- CST");
				if($hit) $phones[]=preg_replace('/[^0-9]/', '',substr($line,$hit-13,12));
				$hit=strpos($line,"- MST");
				if($hit) $phones[]=preg_replace('/[^0-9]/', '',substr($line,$hit-13,12));
				$hit=strpos($line,"- PST");
				if($hit) $phones[]=preg_replace('/[^0-9]/', '',substr($line,$hit-13,12));
			}
		}
		foreach($phones as $phone) { $tit.=$phone."  "; }
	}
	else
	{
		$tit.="-NO HIT ";
		$miss_sql[]="UPDATE ssn SET status=2 WHERE id = $rec_id";
		file_put_contents($mydir.$file_name.".csv","NO HIT");
	}
	AddLog($tit);
}   // end of foreach
}  // end of if $all_hit

if($all_hit) {
	// bump counter of debtors processed
	$sql="SELECT recs_done FROM jobs WHERE id=$job_id";
	$bc=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$b_info = $bc->fetch_assoc();
	$tmp=$b_info['recs_done'];
	$tmp=$tmp+$how_many;
	$sql="UPDATE jobs SET recs_done=$tmp WHERE id=$job_id";
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	// set missed files
	foreach($miss_sql as $sql) {
		$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	}
}
else
{

	AddLog("=== ENTIRE GROUP FAILED - PROBABLE LOGIN FAILURE ===");
	$err_cnt=0;
	if(file_exists($mydir."error_count.txt")) {
		$tempi=file_get_contents($mydir."error_count.txt");
		$err_cnt=(int)$tempi;
	}
	$err_cnt++;
	file_put_contents($mydir."error_count.txt",$err_cnt);
	if($err_cnt % 10 ==0) {
		$msg="Multiple login failures.  Log in manually and check for expired password(s) or special message alert page.<br><br>";
		$msg.="If passwords need updating change them and then update the Accurint scrape using the 'Accounts' page.<br><br>";
		$msg.="If there is a pop up message page when you manaully log in, click 'Ok' to proceed and then log out. ";
		$msg.="Do this for both Accurint accounts.  The scrape will  begin working properly again.";
		$msg.="<br><br>Thanks,<br>Accurint Robot";
		SendMail("David Brooks", "davidbrooks@usecapital.com","Accurint JOB_$job_id Error", $msg);
		SendMail("Bret Bauer", "bretbauer@yahoo.com","Accurint JOB_$job_id Error", $msg);
	}
}

// check to see if entire job is complete
$sql="SELECT * FROM ssn WHERE job_id=$job_id AND status=0 LIMIT 1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
if(! $check->num_rows) {
	$sql="UPDATE jobs SET done_flag=1, completed='".date("Y-m-d H:i:s")."' WHERE id=$job_id";
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	if($debug) AddLog("Job $job_id is finished.  No more records to process.");
	JobLog($job_id,"JOB_$job_id Accurint Scrape completed ".date("Y-m-d g:i:sa"));
	$temp=file_get_contents("JOB_$job_id/job_log.txt");
	$html=nl2br($temp);
	// get total records
	$sql="SELECT id FROM ssn WHERE job_id=$job_id";
	$tchk=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$tot_recs=$tchk->num_rows;
	// get NO-HITS
	$sql="SELECT id FROM ssn WHERE job_id=$job_id AND status=2";
	$tchk=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$tot_nohits=$tchk->num_rows;
	$tot_good=$tot_recs  - $tot_nohits;
	$html.="<br>$tot_recs total records processed.  $tot_good found and $tot_nohits NO HITS";
	SendMail("Bret Bauer", "bretbauer@yahoo.com", "Accurint JOB_$job_id just finished", $html);
	if($job_info['email'])	SendMail("", $job_info['email'],"Accurint JOB_$job_id just finished", $html);
	// set done and total the same
	$sql="UPDATE jobs SET recs_done=$tot_recs WHERE id=$job_id";
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);

	// create CSV export file if not there
	$ex_header="debt_id,party_id, ssn, first_name, last_name,phone_1,phone_2,phone_3,phone_4,phone_5".chr(13).chr(10);
	if(! file_exists($mydir."job_".$job_id.".csv")) file_put_contents($mydir."job_".$job_id.".csv",$ex_header);

	// create CSV file with phone numbers
	$sql="SELECT * FROM ssn WHERE job_id=$job_id";
	$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	while($info = $check->fetch_assoc()) {
		$file_name=ValChars($info['debt_id'])."_".ValChars($info['party_id'])."_".ValChars($info['first_name'])."_".ValChars($info['last_name']);
		$file_name=str_replace(" ","_",$file_name);
		$phones=array();
		if(file_exists($mydir.$file_name.".csv")) {
			$csvData=file_get_contents($mydir.$file_name.".csv");
			$lines = explode("\n", $csvData);
			foreach ($lines as $line) {
				if(stristr($line," - ")) {
					$hit=strpos($line," - "); 
					if($hit) $temp=preg_replace('/[^0-9]/', '',substr($line,$hit-12,12));
					if(strlen($temp) > 9) $phones[]=$temp; 
				}
			}
		}
		if($phones) {
			$csv='"'.$info['debt_id'].'","'.$info['party_id'].'","***-**-'.substr($info['ssn'],-4).'","'.$info['first_name'].'","'.$info['last_name'].'",';
			if(isset($phones[0])) { $csv.='"'.$phones[0].'",'; } else { $csv.='"",'; }
			if(isset($phones[1])) { $csv.='"'.$phones[1].'",'; } else { $csv.='"",'; }		
			if(isset($phones[2])) { $csv.='"'.$phones[2].'",'; } else { $csv.='"",'; }
			if(isset($phones[3])) { $csv.='"'.$phones[3].'",'; } else { $csv.='"",'; }
			if(isset($phones[4])) { $csv.='"'.$phones[4].'"'; } else { $csv.='""'; }
			if($csv) file_put_contents($mydir."job_".$job_id.".csv",$csv.chr(13).chr(10),FILE_APPEND);
		}
	}
}

$db->close();

echo "shutting down CRON script";	
sleep(10);
die();


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

function fetch( $url, $z=null ) {
	$timeout=10;
        $ch =  curl_init();

        $useragent = isset($z['useragent']) ? $z['useragent'] : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2';

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	if( isset($z['post']) ) curl_setopt( $ch, CURLOPT_POST, $z['post'] );
        if( isset($z['post_fields']) )   curl_setopt( $ch, CURLOPT_POSTFIELDS, $z['post_fields'] );
        if( isset($z['refer']) )   curl_setopt( $ch, CURLOPT_REFERER, $z['refer'] );
        curl_setopt( $ch, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, ( isset($z['timeout']) ? $z['timeout'] : 10 ) );
        curl_setopt( $ch, CURLOPT_COOKIEJAR,  $z['cookiefile'] );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $z['cookiefile'] );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER ,false); 
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_ENCODING, "" );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		    
	//curl_setopt($ch, CURLOPT_VERBOSE, true);
	//$verbose = fopen('php://temp', 'rw+');
	//curl_setopt($ch, CURLOPT_STDERR, $verbose); 
	    
	$result = curl_exec( $ch );
	// $response = curl_getinfo( $ch );
	    
	//rewind($verbose);
	//$verboseLog = stream_get_contents($verbose);
	//echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n"; 
        curl_close( $ch );
			
        return $result;
    }
   
function GrabIt($data,$key,$stopper,$max=100,$raw=false) {
$ans="";
$pos=strpos($data,$key);
if($pos > 0) {
	$snip=substr($data,$pos+strlen($key),$max);
	$end=strpos($snip,$stopper);
	$ans=substr($snip,0,$end);
	$ans=trim( preg_replace( '/\s+/', ' ', $ans ) );  // remove crlf
	if(! $raw) {
		$ans=strip_tags($ans);
		$ans=str_replace(chr(34).">","",$ans);
	}
	// $ans = preg_replace('/[^a-zA-Z0-9\s\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?]/', '', $ans);
}
return($ans);
}   

?>