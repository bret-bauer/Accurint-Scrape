<?php
session_start();
if(isset($_POST['password'])) {
	if($_POST['password']=="Capital2016!") {
		$_SESSION['admin']="yes";
		 header("Location: home.php");
		die();
	}
}
if(isset($_GET['logout'])) unset($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>LexisNexis Accurint Scrape Admin</title>
<meta charset="utf-8">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div id="container">
<div style="background: #E66E53; padding: 10px; color: #fff;   font: bold 20px/22px sans-serif; border-top-right-radius: 15px; border-top-left-radius:15px;">LexisNexis Accurint Scrape Admin</div>
<center>
<br><br><br><br>
<form name="login" method="post" action="index.php" target="_self">
<table width="50%" cellpadding=4>
<tr>
<td align="right" width="40%">User</td>
<td align="left" width="60%"><input type="text" name="user" length=20></td>
</tr>
<tr>
<td align="right">Password</td>
<td align="left"><input type="password" name="password" length=20></td>
</tr>
<tr><td colspan=2></td></tr>
<tr>
<td align="right">&nbsp;</td>
<td align="left"><input type="submit" value=" Submit "></td>
</tr>
</table>
</form>
</div>
</body>
