<?php
date_default_timezone_set("America/Chicago");
session_start();
if($_SESSION['admin'] != "yes") die("access denied");
$mess="";
include("dbopen_new.php");

if(isset($_POST['acct_1'])) {
	$sql="UPDATE accounts SET ";
	$x=1;
	while($x <= 2) {
		$sql.="acct_".$x."='".$_POST["acct_".$x]."',";
		$sql.="pass_".$x."='".$_POST["pass_".$x]."',";
		$x++;
	}
	$sql=substr($sql, 0, -1);
	$sql.=",last_update='".date("Y-m-d")."'";
	$sql.=" WHERE id=1";
	$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
	$mess="<font color='green'>Account Changes Saved</font>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Lexis Nexis Scrape Admin</title>
<meta charset="utf-8">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div id="container">
<?php include("menu.php"); ?>
<center>
<br>
<h3>LexisNexis Accurint Accounts</h3>
<?php echo $mess; ?>
<table width="400">
<form method="post">
<tr>
<th width="50%">Account</th>
<th width="50%">Password</th>
</tr>
<?php
$sql="SELECT * FROM accounts WHERE id=1";
$check=$db->query($sql) or trigger_error("$sql - Error: ".mysqli_error($db), E_USER_ERROR);
$info = $check->fetch_assoc();
$x=1;
while($x <= 2) {
	echo "<tr>";
	$acct="acct_".$x;  $pass="pass_".$x;
	echo "<td align='left'><input type='text' name='$acct' size=10 value='".$info[$acct]."' style='width: 190px;'></td>";
	echo "<td align='left'><input type='text' name='$pass' size=16 value='".$info[$pass]."' style='width: 190px;'></td>";
	echo "</tr>";
	$x++;
}
$db->close();
?>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td>Last Update</td><td><b><?php echo $info['last_update']; ?></b></td></tr>

<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" value=" Save "></td></tr>
</form>
</table>

</div>
</body>
</html>
