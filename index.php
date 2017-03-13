<?php
session_start();
$root_dir=dirname(__FILE__)."\\";
include "funcs.php";
include "asql/asql.php";

$allowed_chars="!\"#$%&'()*+,-./:;<=>?@[]^_`{|}~ 0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
$asql=new asql;
$q['id']['ai']=TRUE;
$q['user']['charset']=$allowed_chars;
$q['user']['key']=TRUE;
$q['user']['min']['val']=6;
$q['user']['min']['err']="Username is too short";
$q['user']['max']['val']=16;
$q['user']['max']['err']="Username is too long";
$q['pass']['charset']=$allowed_chars;
$q['pass']['min']['val']=8;
$q['pass']['min']['err']="Password is too short";
$q['pass']['max']['val']=30;
$q['pass']['max']['err']="Password is too long";

if(!$asql->table_exists("users"))
{
	if($asql->create_table("users",$q)) echo "Created Table<br/>";
	else die($asql->error());
}
else echo "Table already exists<br/>";
echo "<pre><h2>";
print_r($asql->insert("users","test"));
echo $asql->error();
?>
