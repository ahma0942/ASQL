<?php
session_start();
include '../include/mobile_detect.php';
include '../php/funcs.php';
include '../php/vars.php';
if(!isset($_POST['type'])) die("Error: 0");
if(!isset($_SESSION['privileges']) OR $_SESSION['privileges']!="owner") die("Error: 1; Privileges");
$out="";
$error=FALSE;
$error_num=0;
$type=$_POST['type'];
if($type=="table_fix")
{
	$table=$_POST['table'];
	$check=$_POST['check'];
	if($check!="check" AND $check!="fix")
	{
		$error_num++;
		$error.="Check must be set<br />";
	}
	if($table=="" OR !isset($table))
	{
		$error_num++;
		$error.="Table must be set<br />";
	}
	elseif(!file_exists("../tables/$table.txt"))
	{
		$error_num++;
		$error.="Cannot find table '$table'<br />";
	}
	
	if($error) die($error);
	
	if($check=="check") $check=FALSE;
	else $check=TRUE;
	$sql=$asql->table_fix($table,$check);
	if($asql->error()) $error.=$asql->error();
	elseif($sql=="") $sql="Table checked. No errors found.<br />";
	$out=$sql;
	if($error) die($error);
	else GOTO END;
}
elseif($type=="fields_get")
{
	$table=$_POST['table'];
	$output=$_POST['output'];
	$format=$_POST['format'];
	if($table=="" OR !isset($table))
	{
		$error_num++;
		$error.="Table must be set<br />";
	}
	elseif(!file_exists($root_dir."tables/$table.txt"))
	{
		$error_num++;
		$error.="Cannot find table '$table'<br />";
	}
	
	$sql=$asql->fields($table);
	
	if($error) die($error);
	else
	{
		if(isset($format) AND $format=="option")
		{
			$out="";
			foreach($sql as $field) $out.="<option value='$field'>".ucfirst($field)."</option>";
			$sql=$out;
		}
		
		if(isset($output) AND $output!="")
		{
			?><script>
			$('<?php echo $output; ?>').html("<?php echo $sql; ?>");
			$("<?php echo $output; ?>").show();
			</script><?php
		}
		else
		{
			?><script>
			$('#output_tab').html("<?php echo $sql; ?>");
			if($("#output_tab2").parent().parent().attr("class")!="selected") $('#output_tab2').html('Output <b>1</b>');
			</script><?php
		}
	}
}
elseif($type=="asql")
{
	$state=$_POST['asql'];
	$table=$_POST['table'];
	if($state=="select" OR $state=="update" OR $state=="delete") $where=$_POST['where'];
	if($state=="update" OR $state=="insert") $set=$_POST['set'];
	if($state!="select" AND $state!="update" AND $state!="insert" AND $state!="delete")
	{
		$error_num++;
		$error.="Please choose a valid Statement<br />";
	}
	
	if(isset($where))
	{
		foreach($where as $field=>$row)
		{
			foreach($row as $num=>$val)
			{
				if($field=="enc")
				{
					if($val=="md5") $where['val'][$num]=md5($where['val'][$num]);
				}
			}
		}
		$where=array_combine($where['field'],$where['val']);
	}
	if(isset($set))
	{
		foreach($set as $field=>$row)
		{
			foreach($row as $num=>$val)
			{
				if($field=="enc")
				{
					if($val=="md5") $set['val'][$num]=md5($set['val'][$num]);
				}
			}
		}
		$set=array_combine($set['field'],$set['val']);
	}
	
	if($state=="select" OR $state=="delete") $sql=$asql->$state($table,$where);
	elseif($state=="insert") $sql=$asql->$state($table,$set);
	elseif($state=="update") $sql=$asql->$state($table,$set,$where);
	
	if(!$sql)
	{
		$error_num++;
		$error.=$asql->error();
		die($error);
	}
	else
	{
		if($state=="select")
		{
			$out.="<table border='1'>";
			foreach($sql as $num=>$row) foreach($row as $field=>$value) $out.="<tr><td>$field</td><td>$value</td></tr>";
			$out.="</table>";
		}
		elseif($state=="update")
		{
			$out.="<font color='green'>Successfully Updated!</font><br />Set ";
			foreach($set as $field=>$value) $out.="$field=$value ";
			$out.="Where ";
			foreach($where as $field=>$value) $out.="$field=$value ";
		}
		elseif($state=="delete")
		{
			$out.="<font color='green'>Successfully Deleted!</font><br />Delete Where ";
			foreach($where as $field=>$value) $out.="$field=$value ";
		}
	}
  GOTO END;
}
elseif($type=="extra")
{
	$table=$_POST['table'];
	$type2=$_POST['type2'];
	if($type2=="delete_field")
	{
		$field=$_POST['fields'];
		$sql=$asql->delete_field($table,$field);
		if(!$sql)
		{
			$error_num++;
			$error.=$asql->error();
			die($error);
		}
		else
		{
			$out.="<font color='green'>Successfully Deleted Field: '<font color='red'>$field</font>'</font><br />";
			GOTO END;
		}
	}
	elseif($type2=="get_field")
	{
		$sql=$asql->fields($table);
		$out.="<table border='1'>";
		foreach($sql as $field) $out.="<tr><td>$field</td></tr>";
		$out.="</table>";
		GOTO END;
	}
}

END:
die($out);
?>
