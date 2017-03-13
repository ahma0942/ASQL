<?php
session_start();
include '../include/mobile_detect.php';
include '../php/funcs.php';
include '../php/vars.php';
if(!$_POST['type']) die("Error: 0");
if(!$session_username) die("Error: 1");
$type=$_POST['type'];
$error=FALSE;
if($type=="edit")
{
  $name=$_POST['name'];
  $device=$_POST['device'];
  $udid=$_POST['udid'];
  $game=$_POST['game'];
  
//  if(strlen($name)>15) $error.="Max length for name is <b>"
}
elseif($type=="change_pass")
{
	$op=$_POST['op'];
	$np=$_POST['np'];
	$cp=$_POST['cp'];
	
	if(md5($op)!=$session_password) $error.="Old password is incorrect <br />";
	if($np!=$cp) $error.="New password and confirm password, must be identical <br />";
	if($error) die("<script>\nboard('$error','td',7,'html');\n</script>");
	
	$sql1['password']=md5($np);
	$sql2['username']=$session_username;
	$check=$asql->update("members",$sql1,$sql2);
	if(!$check) die("<script>\nboard('".$asql->error()."','td',0,'html');\n</script>");
	else
	{
		$_SESSION['password']=md5($np);
		$cp=stripslashes($cp);
		die("<script>\nboard('<font color=\'green\'>Password successfully changed to: <b>$cp</b></font>','td',0,'html');\n</script>");
	}
}
?>
