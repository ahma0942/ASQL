<?php
class asql
{
	const RD=__DIR__."\\";
	const AC="!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~ ";
	const AF=array("min"=>array("val","err"),"ai","charset"=>array(0,1,2),"key","max"=>array("val","err"),"err");
	const ERRTYPEINDEX=array("Notice","Error");
	private $err=array();
	
	public function allowed()
	{
		return self::AC;
	}
	
	private function is_allowed($str)
	{
		if(preg_match("#^[a-zA-Z0-9!\"\#$%&'()*+,-\./:;<=>?@\[\]^_`{|}~ ]+$#",$str)) return true;
		else return false;
	}
	
	private function _translate_errors($str)
	{
		if(is_array($str))
		{
			if($str[0]==0) $ret=array(0,"Table ".$str[1]." already exists");
			elseif($str[0]==1) $ret=array(1,"Could not create table directory for ".$str[1]);
			elseif($str[0]==2) $ret=array(1,"Could not create table metadata for table ".$str[1]);
			elseif($str[0]==3) $ret=array(1,"Could not create table ".$str[1]);
			elseif($str[0]==4) $ret=array(1,"Must define 1 AutoIncrement column for table ".$str[1]);
			elseif($str[0]==5) $ret=array(1,"Could not create binary-table for field ".$str[2]." in table ".$str[1]);
			elseif($str[0]==6) $ret=array(1,"Could not create binary-table for table ".$str[1]);
			elseif($str[0]==7) $ret=array(0,"Metadata for table ".$str[1]." already exists");
			elseif($str[0]==8) $ret=array(1,"Could not find metadata for table ".$str[1]." or the file might be empty");
			elseif($str[0]==9) $ret=array(1,"Could not decode metadata for table ".$str[1].". Metadata might be corrupt. Consider using table_fix()");
			
			$ret[]=debug_backtrace()[1]['file'];
			$ret[]=debug_backtrace()[1]['line'];
		}
		return $ret;
	}
	
	private function _create_table($table)
	{
		if(file_exists(self::RD.'tables\\'.$table.'\\'.$table.'.txt')) return array(0,$table);
		$t=true;
		if(!file_exists(self::RD.'tables\\'.$table.'\\')) $t=mkdir(self::RD.'tables\\'.$table.'\\');
		if($t===false) return array(1,$table);
		if(!file_exists(self::RD.'tables\\'.$table.'\\metadata.txt')) $t=fopen(self::RD.'tables\\'.$table.'\\metadata.txt','a');
		if($t===false) return array(2,$table);
		if(!file_exists(self::RD.'tables\\'.$table.'\\'.$table.'.txt')) $t=fopen(self::RD.'tables\\'.$table.'\\'.$table.'.txt','a');
		if($t===false) return array(3,$table);
		
		return true;
	}
	
	private function _create_fields($table,$fields)
	{
		if(filesize(self::RD.'tables\\'.$table.'\\metadata.txt')!=0) return array(7,$table);
		
		$ai=false;
		$t=true;
		foreach($fields as $name=>$row) if(isset($row['ai'])) $ai=true;
		if(!$ai) return array(4,$table);
		
		foreach($fields as $name=>$row){
			if(!isset($row['ai']) AND (!isset($row['binary']) OR $row['binary']===true)){
				if(!file_exists(self::RD.'tables\\'.$table.'\\binary\\')) $t=mkdir(self::RD.'tables\\'.$table.'\\binary\\');
				if($t===false) return array(6,$table);
				if(!file_exists(self::RD.'tables\\'.$table.'\\binary\\'.$name.'.txt')) $t=fopen(self::RD.'tables\\'.$table.'\\binary\\'.$name.'.txt','a');
				if($t===false) return array(5,$table,$name);
			}
		}
		
		if(filesize(self::RD.'tables\\'.$table.'\\metadata.txt')==0) $t=file_put_contents(self::RD.'tables\\'.$table.'\\metadata.txt',json_encode($fields));
		if($t===false) return array(5,$table,$name);
		
		return true;
	}
	
	public function create_table($table,$fields=FALSE)
	{
		$chk=$this->_create_table($table);
		if($chk!==true){
			$this->err[]=$this->_translate_errors($chk);
			if(!empty($this->err) && $this->err[count($this->err)-1][0]!=0) return false;
		}
		
		if(is_array($fields))
		{
			$chk=$this->_create_fields($table,$fields);
			if($chk!==true){
				$this->err[]=$this->_translate_errors($chk);
				if(!empty($this->err) && $this->err[count($this->err)-1][0]!=0) return false;
			}
		}
		clearstatcache();
		return true;
	}
	
	public function insert($table,$set)
	{
		$dir=self::RD.'tables\\'.$table.'\\';
		if(file_exists($dir.'metadata.txt') AND filesize($dir.'metadata.txt')!=0) $reading=file_get_contents($dir.'metadata.txt');
		else
		{
			$this->err[]=$this->_translate_errors(array(8,$table));
			return false;
		}
		
		if(!json_decode($reading))
		{
			$this->err[]=$this->_translate_errors(array(9,$table));
			return false;
		}
		else $reading=json_decode($reading,true);
		return $reading;
	}
	
	public function table_exists($table)
	{
		return (file_exists(self::RD.'tables\\'.$table.'\\'.'metadata.txt') AND filesize(self::RD.'tables\\'.$table.'\\'.'metadata.txt')!=0);
	}
	
	function table_fix($table,$check=false)
	{
		$this->err=false;
		$end_file_result="";
		if(file_exists(self::RD.'tables/'.$table.'.txt')) $reading=fopen(self::RD.'tables/'.$table.'.txt','r');
		else $this->err.="Error: File 'tables/$table.txt' does not exist.<br>";
		if($this->err) return false;
		$line1=fgets($reading);
		$end_file_result=$line1;
		$int=substr_count($line1,"|");
		$line_num=1;
		while(!feof($reading))
		{
			$line_num++;
			$line=fgets($reading);
			$int2=substr_count($line,"|");
			if(!$check) $end_file_result.=$line;
			$line=str_replace(array("\r", "\n"), '', $line);
			if($line=="" OR $line=="\n")
			{
				$output.="Problem detected at line number '$line_num' in table '$table': Line is empty<br>";
				if($check) continue;
			}
			if($int2>$int)
			{
				if($check)
				{
					/*$str=split_nth($line,"|",$int);
					$line=$str[0]."|";*/
				}
				else $output.="Problem detected at line number '$line_num' in table '$table': $int '|' is needed, $int2 is given<br>";
			}
			elseif($int2<$int)
			{
				$output.="Problem detected at line number '$line_num' in table '$table': $int '|' is needed, only $int2 is given.<br>";
				if($int-1==$int2 OR $int-2==$int2)
				{
					if($check) $output.="Trying to fix the problem...<br>";
					else $output.="Trying to find more info about the problem...<br>";
					
					$check2=true;
					if(substr($line,0,1)!="|")
					{
						$check2=false;
						if($check)
						{
							$line="|".$line;
							$output.="<font color='green'>Problem fixed successfully</font><br>";
						}
						else $output.="<font color='green'>More info found:</font> Missing '|' in the beginning of the line<br>";
					}
					if(substr($line,-1)!="|")
					{
						$check2=false;
						if($check)
						{
							$line.="|";
							$output.="<font color='green'>Problem fixed successfully</font><br>";
						}
						else $output.="<font color='green'>More info found:</font> Missing '|' in the end of the line<br>";
					}
					
					if($check2)
					{
						if($check) $output.="<font color='red'>Unable to fix the problem</font><br>";
						else $output.="<font color='red'>Unable to find more info about problem</font><a onclick='$(this).find('span').show();'>Show</a><br>";
					}
				}
			}
			$line.="\n";
			if($check)$end_file_result.=$line;
		}
		fclose($reading);
		$writing=fopen(self::RD.'tables/'.$table.'.txt','w');
		fputs($writing,$end_file_result);
		fclose($writing);
		return $output;
	}
	
	function delete_field($table,$field)
	{
		$this->err=false;
		$end_file_result="";
		if(file_exists(self::RD.'tables/'.$table.'.txt')) $reading=fopen(self::RD.'tables/'.$table.'.txt','r');
		else $this->err.="Error: File 'tables/$table.txt' does not exist.<br>";
		if($reading) $line1=explode("|",fgets($reading));
		else $this->err.="Error: No file specified<br>";
		if($this->err) return false;
		$fields=array();
		foreach($line1 as $value) if($value!="" AND $value!="\n") $fields[]=$value;
		
		$check=false;
		$i=0;
		foreach($fields as $name)
		{
			$i++;
			if($field==$name)
			{
				$check=true;
				$num=$i;
				break;
			}
		}
		if(!$check) $this->err.="Error: Cannot find field '$field'.<br>";
		if($this->err) return false;
		
		$new_line="|";
		$i=0;
		foreach($fields as $name)
		{
			$i++;
			if($i!=$num) $new_line.=$name."|";
		}
		
		$end_file_result.=$new_line."\n";
		while(!feof($reading))
		{
			$line=fgets($reading);
			if(substr($line,0,1)==";") continue;
			$line=explode("|",$line);
			$i=0;
			$line_new=array();
			$new_line="";
			foreach($line as $value) if($value!="" AND $value!="\n") $line_new[]=$value;
			foreach($line_new as $name)
			{
				$i++;
				if($i!=$num) $new_line.="|".$name;
			}
			$end_file_result.=$new_line."\n";
		}
		fclose($reading);
		$writing=fopen(self::RD.'tables/'.$table.'.txt','w');
		fputs($writing,$end_file_result);
		fclose($writing);
		return true;
	}
	
	function fields($table)
	{
		$this->err=false;
		if(file_exists(self::RD.'tables/'.$table.'.txt')) $reading=fopen(self::RD.'tables/'.$table.'.txt','r');
		else $this->err.="Error: File 'tables/$table.txt' does not exist.<br>";
		if($reading) $line1=explode("|",fgets($reading));
		else $this->err.="Error: No file specified<br>";
		if($this->err) return false;
		$fields=array();
		foreach($line1 as $value) if($value!="" AND $value!="\n") $fields[]=$value;
		return $fields;
	}
	
	function select($table,$where)
	{
		$this->err=false;
		if(file_exists(self::RD.'tables/'.$table.'.txt')) $reading=fopen(self::RD.'tables/'.$table.'.txt','r');
		else $this->err.="Error: File 'tables/$table.txt' does not exist.<br>";
		if(isset($reading)) $line1=explode("|",fgets($reading));
		else $this->err.="Error: No file specified<br>";
		if($where!="*") if(!is_array($where)) $this->err.="Error: WHERE must be Array<br>";
		if($this->err) return false;
		
		$fields=array();
		foreach($line1 as $value) if($value!="" AND $value!="\n") $fields[]=$value;
		if($where=="*")
		{
			$this->err=false;
			$i2=0;
			while(!feof($reading))
			{
				$i=0;
				$i2++;
				$line=fgets($reading);
				if(substr($line,0,1)==";") continue;
				$line=explode("|",$line);
				
				foreach($fields as $field)
				{
					$i++;
					$end_file_result[$i2][$field]=$line[$i];
				}
				$returned=true;
			}
			GOTO END_OF_SELECT;
		}
		
		$fw=array();
		$vw=array();
		$array_f_v_w=array();
		$i_where=0;
		foreach($where as $name=>$value)
		{
			if(strpos($name,"|") OR strpos($value,'|'))
			{
				$this->err.="Error: WHERE must not contain '|'";
				break;
			}
			$i_where++;
			$fw[]=$name;
			$vw[]=$value;
			$array_f_v_w[$name]=$value;
		}
		if($this->err) return false;
		
		$check=false;
		$final_fields_where=array();
		foreach($fw as $value)
		{
			$i=0;
			foreach($fields as $value1)
			{
				$i++;
				if($value==$value1)
				{
					$check=true;
					$final_fields_where[$i]=$array_f_v_w[$value];
				}
			}
			if(!$check) $this->err.="No such Field: '$value' in WHERE<br>";
			$check=false;
		}
		
		if($this->err) return false;
		$returned=false;
		$end_file_result=array();
		$i2=0;
		while(!feof($reading))
		{
			$line=fgets($reading);
			if(substr($line,0,1)==";") continue;
			if($line=="" OR $line==" " OR $line=="\n") continue;
			$line=explode("|",$line);
			$check=true;
			foreach($final_fields_where as $id=>$value) if($line[$id]!=$value) $check=false;
			if(!$check) continue;
			$i2++;
			$i=0;
			foreach($fields as $field)
			{
				$i++;
				$end_file_result[$i2][$field]=$line[$i];
			}
			$returned=true;
		}
		
		END_OF_SELECT:
		if($returned) return $end_file_result;
		else
		{
			if($where=="*") $this->err.="Error: Cannot table is empty";
			else
			{
				$this->err.="Error: cannot find ";
				foreach($final_fields_where as $id=>$value) $this->err.=$fields[$id-1]."=".$value." ";
				$this->err.="in WHERE";
			}
			return false;
		}
	}
	
	function delete($table,$where)
	{
		$this->err=false;
		if(!is_array($where)) $this->err.="Error: WHERE must be Array<br>";
		$end_file_result="";
		$file=self::RD.'tables/'.$table;
		if(file_exists($file.'.txt')) $reading=fopen($file.'.txt','r');
		else $this->err.="Error: File '$file.txt' does not exist.<br>";
		if($reading) $line1=explode("|",fgets($reading));
		else $this->err.="Error: No file specified<br>";
		if($this->err) return false;
		
		$end_file_result.=implode("|",$line1);
		$fields=array();
		foreach($line1 as $value) if($value!="" AND $value!="\n") $fields[]=$value;
		
		$fw=array();
		$vw=array();
		$array_f_v_w=array();
		$i_where=0;
		foreach($where as $name=>$value)
		{
			if(strpos($name,"|") OR strpos($value,'|'))
			{
				$this->err.="Error: WHERE must not contain '|'";
				break;
			}
			$i_where++;
			$fw[]=$name;
			$vw[]=$value;
			$array_f_v_w[$name]=$value;
		}
		if($this->err) return false;
		
		$check=false;
		$final_fields_where=array();
		foreach($fw as $value)
		{
			$i=0;
			foreach($fields as $value1)
			{
				$i++;
				if($value==$value1)
				{
					$check=true;
					$final_fields_where[$i]=$array_f_v_w[$value];
				}
			}
			if(!$check) $this->err.="No such Field: '$value' in WHERE<br>";
			$check=false;
		}
		
		if($this->err) return false;
		$deleted=false;
		while(!feof($reading))
		{
			$line=fgets($reading);
			if(substr($line,0,1)==";") continue;
			$line=explode("|",$line);
			$check=true;
			foreach($final_fields_where as $id=>$value) if($line[$id]!=$value) $check=false;
			if(!$check)
			{
				$end_file_result.=implode("|",$line);
				continue;
			}
			//============LOG
			file_put_contents(self::RD.'pages/log.txt','[DELETE]'.(isset($_SESSION['username']) ? $_SESSION['username'] : '').strip_nl(implode("|",$line))."\n",FILE_APPEND | LOCK_EX);
			//============LOG
			$deleted=true;
			continue;
		}
		$writing=fopen($file.'.tmp','w');
		fputs($writing,$end_file_result);
		fclose($writing);
		fclose($reading);
		
		if($deleted)
		{
			rename($file.'.tmp', $file.'.txt');
			return true;
		}
		else
		{
			unlink($file.'.tmp');
			$this->err.="Error: cannot find ";
			foreach($final_fields_where as $id=>$value) $this->err.=$fields[$id-1]."=".$value." ";
			$this->err.="in WHERE";
			return false;
		}
	}
	
	function update($table,$set,$where)
	{
		$this->err=false;
		if(!is_array($set)) $this->err.="Error: SET must be Array<br>";
		if(!is_array($where)) $this->err.="Error: WHERE must be Array<br>";
		$end_file_result="";
		if(file_exists(self::RD.'tables/'.$table.'.txt')) $reading=fopen(self::RD.'tables/'.$table.'.txt','r');
		else $this->err.="Error: File 'tables/$table.txt' does not exist.<br>";
		if($reading) $line1=explode("|",fgets($reading));
		else $this->err.="Error: No file specified<br>";
		if($this->err) return false;
		
		$f=array();
		$v=array();
		$array_f_v=array();
		$i_set=0;
		foreach($set as $name=>$value)
		{
			if(strpos($name,"|") OR strpos($value,'|'))
			{
				$this->err.="Error: SET must not contain '|'";
				break;
			}
			$i_set++;
			$f[]=$name;
			$v[]=$value;
			$array_f_v[$name]=$value;
		}
		
		$fw=array();
		$vw=array();
		$array_f_v_w=array();
		$i_where=0;
		foreach($where as $name=>$value)
		{
			if(strpos($name,"|") OR strpos($value,'|'))
			{
				$this->err.="Error: WHERE must not contain '|'";
				break;
			}
			$i_where++;
			$fw[]=$name;
			$vw[]=$value;
			$array_f_v_w[$name]=$value;
		}
		if($this->err) return false;
		
		$end_file_result.=implode("|",$line1);
		$fields=array();
		foreach($line1 as $value) if($value!="" AND $value!="\n") $fields[]=$value;
		
		$check=false;
		$final_fields=array();
		foreach($f as $value)
		{
			$i=0;
			foreach($fields as $value1)
			{
				$i++;
				if($value==$value1)
				{
					$check=true;
					$final_fields[$i]=$array_f_v[$value];
				}
			}
			if(!$check) $this->err.="No such Field: '$value' in SET<br>";
			$check=false;
		}
		
		$check=false;
		$final_fields_where=array();
		foreach($fw as $value)
		{
			$i=0;
			foreach($fields as $value1)
			{
				$i++;
				if($value==$value1)
				{
					$check=true;
					$final_fields_where[$i]=$array_f_v_w[$value];
				}
			}
			if(!$check) $this->err.="No such Field: '$value' in WHERE<br>";
			$check=false;
		}
		
		if($this->err) return false;
		$replaced=false;
		while(!feof($reading))
		{
			$line=fgets($reading);
			if(substr($line,0,1)==";")
			{
				$end_file_result.=$line;
				continue;
			}
			$line=explode("|",$line);
			$check=true;
			foreach($final_fields_where as $id=>$value) if($line[$id]!=$value) $check=false;
			if(!$check)
			{
				$end_file_result.=implode("|",$line);
				continue;
			}
			
			$i=0;
			$new_line="";
			foreach($fields as $field)
			{
				$i++;
				$check=false;
				$new_line.="|";
				foreach($final_fields as $id=>$value)
				{
					if($i==$id)
					{
						$new_line.=$value;
						$check=true;
					}
				}
				if(!$check) $new_line.=$line[$i];
			}
			$end_file_result.=$new_line;
			$replaced=true;
			
			//============LOG
			file_put_contents(self::RD.'pages/log.txt','[UPDATE]#'.(isset($_SESSION['username']) ? $_SESSION['username'] : '').'#'.strip_nl(implode("|",$line).' => '.$new_line)."\n",FILE_APPEND | LOCK_EX);
			//============LOG
		}
		$writing=fopen(self::RD.'tables/'.$table.'.tmp','w');
		fputs($writing,$end_file_result);
		fclose($writing);
		fclose($reading);
		
		if($replaced)
		{
			rename(self::RD.'tables/'.$table.'.tmp', self::RD.'tables/'.$table.'.txt');
			return true;
		}
		else
		{
			unlink(self::RD.'tables/'.$table.'.tmp');
			$this->err.="Error: cannot find ";
			foreach($final_fields_where as $id=>$value) $this->err.=$fields[$id-1]."=".$value." ";
			$this->err.="in WHERE";
			return false;
		}
	}
	
	private function in_array_r($needle, $haystack, $strict = false) {
		foreach ($haystack as $item) {
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) return true;
		}
		return false;
	}
	
	public function is_err($chk=false)
	{
		if($chk===false AND !empty($this->err)) return true;
		elseif($chk==1 AND $this->in_array_r(1,$this->err)) return true;
		elseif($chk==0 AND $this->in_array_r(0,$this->err)) return true;
		return false;
	}
	
	function error()
	{
		$str="";
		for($i=1;$i<=count($this->err);$i++) $str.="<div style='padding:5px'><b>".self::ERRTYPEINDEX[$this->err[$i-1][0]]."</b>: ".$this->err[$i-1][1]."<br/>\n<b>File</b>: ".$this->err[$i-1][2]."; <b>Line</b>: ".$this->err[$i-1][3]."<br/>\n</div>";
		return "<div class='error'>$str<div>";
	}
}
?>
