<?php
session_start();
include '../include/mobile_detect.php';
include '../php/funcs.php';
include '../php/vars.php';
if(!isset($_POST['type'])) die("Error: 0");
$error=FALSE;
$type=$_POST['type'];
if($type=="register")
{
	exit;
	$user=$_POST['user'];
	$pass=$_POST['pass'];
	$pass2=$_POST['pass2'];
	if($user=="") $error.="Error: Username is not set<br>";
	if($pass=="") $error.="Error: Password is not set<br>";
	if($pass2=="") $error.="Error: Confirm Password is not set<br>";
	if($pass!=$pass2) $error.="Error: Password and Confirm Password, doesn't match<br>";
	$pass=md5($pass);
	if(strpos($user,'|')) $error.="Error: Username may not contain '|'<br>";
	if(strpos($pass,'|')) $error.="Error: Password may not contain '|'<br>";
	if($error)
	{
		$script="board('$error','span','3','html');";
		script($script);
		exit;
	}
	$sql["username"]=$user;
	$check=$asql->select("members",$sql);
	if($check) die(script('board("The entered username is already in use. Please choose another one, thank you.","span","3","html");'));
	$sql["password"]=$pass;
//	$row=$asql->insert("members",$sql);
	if(!$row) die(script('board("Something went wrong. Please try again later.","span","3","html");'));
	$sql=$asql->fields("members");
	foreach($sql as $field) $_SESSION[$field]=$row[1][$field];
	$td="Logged in as $user. ";
	$div="Redirecting in: ";
	$span=3;
	?>
	<script>
	var secs="<?php echo $span; ?>";
	board("<?php echo $td; ?>","td",secs,"prepend");
	board_add("<?php echo $div; ?>","div","prepend");
	board_add(secs,"span","html");
	var interval=setInterval(function(){
		secs--;
		board_add(secs,"span","html");
		if(secs=="0")
		{
			clearInterval(interval);
		}
	},1000);
	
	setTimeout(function(){
		$("body").load("?page=account");
	},secs*1000);
	</script>
	<?php
}
elseif($type=="add_acct")
{
	$name=$_POST['name'];
	$device=$_POST['device'];
	$udid=$_POST['udid'];
	$game=$_POST['game'];
}
?>
