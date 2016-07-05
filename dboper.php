<?php
	//dboper.php
	require_once('globalconfig.php');
	require_once('dbconn.php');

	$dept_arr = array();

	// ================         函数          ====================
	function varifypassword($userno, $password){
		$con = "";
		//连接数据库
		global $db_domain, $db_name, $db_admin_name, $db_passwd,$db_chractor;
		global $table_validusers,$table_users;

		// die("db_domain:" . $db_domain." ,db_admin_name: " . $db_admin_name . " ,db_passwd: " . $db_passwd);
		$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
		if(!$con ){
			die("<br>" . __LINE__ . __METHOD__ . ' 连接数据库失败Could not connect: ' . mysql_error());
		}
		if(mysql_select_db($db_name, $con)==false) die("error:".mysql_error());
		mysql_query("set names $db_chractor");

		$query="";
		$md5password = md5(md5(md5(md5($password))));
		// error_log("con:" . $con .", userno:" . $userno .", password:".$password.", md5password:" . $md5password, 3, "./err.txt","password");

		$sql="select * from {$table_users} where userno='$userno' and password='{$md5password}'";

		$query=mysql_query($sql, $con);
		if (!$query) {
			die("<div>员工编号(公免号码)和密码不匹配: ".mysql_error()."</div>");
		}
		mysql_close($con);
		return $query;
	}
	################################         从excel描述网页表格      ################################
	function create_db_with_excel($objPHPExcel,$file_url,$employeeid,$inituser=false){
		global $sheetname_CN,$sheetname,$sheetcount,$admin_no;
		global $table_validusers;

		for ($i=0; $i < $sheetcount; $i++) {
			// echo "$i sheetcount $sheetcount <br>";

	        $objPHPExcel = new PHPExcel();
			$file_types = explode(".",$file_url);
			//echo "{$file_types}<br>";
			$file_type = $file_types [count ( $file_types ) - 1];
			if (strtolower ( $file_type ) == "xls")
			{
				$objReader=PHPExcel_IOFactory::createReader('Excel5');//use excel2007 for 2007 format
			}
			else if (strtolower ( $file_type ) == "xlsx")
			{
				$objReader=PHPExcel_IOFactory::createReader('Excel2007');//use excel2007 for 2007 format
			}
			$objPHPExcel=$objReader->load($file_url);//$file_url即Excel文件的路径
			if(!isset($objPHPExcel)){
				die("<br>" . __LINE__ . __METHOD__ . "无法解析文件");
			}
			# 获取第i个工作表
			create_db_with_sheet($objPHPExcel,$i,$inituser);
			unset($objPHPExcel);
			$objPHPExcel = '';
		}
		$sqlinsert_validusers = "INSERT IGNORE INTO `{$table_validusers}`(`employeeid`,`name`,`department`)VALUES('$admin_no','admin','人事部门') ON DUPLICATE KEY UPDATE `employeeid`='$admin_no'";
		// die($sqlinsert_validusers);
		$resultinsert = mysql_query($sqlinsert_validusers);
		if (!$resultinsert) {
			die("<br>" . __LINE__ . __METHOD__ . " insert {$table_validusers} error with :".mysql_error());
		}
		if ($_GET['initfile']) {
			$redirect_url ="refresh:1;url=".$_SERVER['PHP_SELF']."?employeeid=".$employeeid ;
			// error_log($redirect_url,3,"./err.txt","webtabname");
			header($redirect_url);
		}else{
			echo "<script> location.reload();</script>";  // alert('数据库初始化完毕');
		}
	}
	// 删除数据库
	function dropdatabase($value='')
	{
		global $con, $db_name,$db_chractor;
		// 清除数据库，删除database
		$sqldropdb = "DROP DATABASE IF EXISTS `$db_name`";
		mysql_query($sqldropdb);
		//set charset
		mysql_query("SET names $db_chractor");
	}

	function create_db_with_sheet($objPHPExcel,$sheet_index,$inituser=false){
		global $debug;
		global $sheetname, $sheetcount, $sheetname_CN;
		global $table_seq,$table_firstline, $table_secondline, $table_records,$table_remark,$table_users;
		global $overtime_records_cloumn, $remark_cloumn, $firstline_cloumn,$sencondline_column,$fix_cloumn;
		global $userinfo_column,$staffinfo_headline;

		$md5password = md5(md5(md5(md5("123456"))));

		$sheet=$objPHPExcel->getSheet($sheet_index);//获取第sheet_index个工作表
		$objPHPExcel->setActiveSheetIndex($sheet_index);
		$sheet_name = array_search($sheet->getTitle(),$sheetname_CN);
		// echo "<br>" . __LINE__ . __METHOD__ . " sheet_name $sheet_name sheet_index $sheet_index <BR>";

		$highestRow=$sheet->getHighestRow();//取得总行数
		$highestColumn=$sheet->getHighestColumn();//取得总列数
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		// echo "一共 {$highestRow} 行， $highestColumn 列<br>";
		//echo "{$highestColumnIndex} <br>";
		if(0){
			$inthigh = hexdec(bin2hex($highestColumn));
			//echo "{$inthigh} <br>";
			$basechar = hexdec(bin2hex('A'));
			//echo "{$basechar} <br>";
			eval("\$Clos = $inthigh - $basechar + 1;");
			if ($debug) echo "一共 {$Clos} 列"."<br>";
			//date("t",mktime(0,0,0,$month,1,$year));
			//$year = date('Y');
		}else{
			$Clos = $highestColumnIndex;
		}

		//读取第一行,生成第一行的表格
		$j = 0;
		$j++;
		$remarkline = array();
		$remarkline = get_remark_from_excel($objPHPExcel,$j,$highestColumn);

		$sqlinsert_remark = "INSERT IGNORE INTO `".$sheet_name."{$table_remark}`(`remarks`,`remarkslen`) VALUES (
		'".$remarkline[1]."',".$remarkline[2].")";
		$result = mysql_query($sqlinsert_remark);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " insert into {$sheet_name}{$table_remark} error with :".mysql_error());
		}
		// echo "<br>" . __LINE__ . __METHOD__ . "get_remark_from_excel<BR>";

		// 及时清除变量， 释放内存
		unset($remarkline);
		unset($sqlinsert_remark);
		unset($result);

		$j++;
		$firstline = array();
		$firstline = get_firstline_from_excel($objPHPExcel,$j,$highestColumn);
		if($debug) {print_r($firstline);echo "<br>";}

		$array1Elem = $firstline_cloumn;
		$line1arraysize = count($array1Elem);
		// writeinfo($line1arraysize);
		$line1valsize = count($firstline) - 1;
		$sqlinsert_firstline = "INSERT IGNORE INTO `{$sheet_name}{$table_firstline}`(";
		for ($iline1=0; $iline1 <= $line1valsize; $iline1++) { // 下标从0开始
			$sqlinsert_firstline .= " `$array1Elem[$iline1]`,";
			// writeinfo($iline1."   ".$sqlinsert_firstline);
		}
		$sqlinsert_firstline = rtrim($sqlinsert_firstline,",");//去掉最后一个,
		$sqlinsert_firstline .= ")VALUES (";
		for ($iline1=0; $iline1 <= $line1valsize; $iline1++) {// 从第二个数据开始
			$sqlinsert_firstline .= " '{$firstline[$iline1]}',";
		}
		$sqlinsert_firstline = rtrim($sqlinsert_firstline,",");
		$sqlinsert_firstline .= ");";

		$resultinsert = mysql_query($sqlinsert_firstline);
		if (!$resultinsert) {
			die("<br>". __FILE__. " " . __LINE__. " " .__METHOD__ . " insert into {$sheet_name}{$table_firstline} error with :".mysql_error());
		}

		// echo "<br>" . __LINE__ . __METHOD__ . "get_firstline_from_excel<BR>";

		// 及时清除变量， 释放内存
		unset($firstline);
		unset($sqlinsert);
		unset($resultinsert);

		//读取第二行,生成第二行的表格
		$j++;
		$secondline = get_secondline_from_excel($objPHPExcel,$j,$Clos,$highestColumn);

		$yellowcloumns = explode(",",$secondline[sizeof($secondline)-2]);
		$redcloumns = explode(",",$secondline[sizeof($secondline)-1]);


		########################  insert into secondline table
		$array2Elem = $sencondline_column[$sheetname[$sheet_index]];
		$line2arraysize = count($array2Elem);
		$line2valsize = count($secondline);
		$sqlinsert_secondline = "INSERT IGNORE INTO `{$sheet_name}{$table_secondline}`(";
		for ($iline2=0; $iline2 < $line2arraysize; $iline2++) { // 下标从1开始
			$sqlinsert_secondline .= " `$array2Elem[$iline2]`,";
		}
		$sqlinsert_secondline .= "`yellowdays`,`reddays`)VALUES (";
		for ($iline2=0; $iline2 < $line2valsize; $iline2++) {
			$sqlinsert_secondline .= " '{$secondline[$iline2]}',";
		}
		$sqlinsert_secondline = rtrim($sqlinsert_secondline,","); //去掉最后一个,
		$sqlinsert_secondline .= ");";
		// writeinfo($sqlinsert_secondline);

		$resultinsert = mysql_query($sqlinsert_secondline);
		if (!$resultinsert) {
			die("<br>" . __LINE__ . __METHOD__ . " insert {$sheet_name}{$table_secondline}($sheet_index) error with :".mysql_error());
		}

		// echo "<br>" . __LINE__ . __METHOD__ . "get_secondline_from_excel<BR>";

		// 及时清除变量， 释放内存
		unset($secondline);
		unset($sqlinsert_secondline);
		unset($resultinsert);

		// echo "memory: ".convert(memory_get_usage(true))."<BR>";
		##########       获取records
		// echo "records_num $records_num <BR>";
		$recordline = get_records_from_excel($objPHPExcel,$j, $highestRow, $highestColumn, $Clos, $redcloumns, $yellowcloumns, $employeeid, $sheet_index);

		// $records_num=count($recordline,COUNT_RECURSIVE); // 这个值不对，二维数组的值不对,打印的是所有的数组数目
		// $records_num=count($recordline,COUNT_NORMAL); // 这个值打印的是二维数组的第一个下标
		$records_num=$recordline['recordsnum'];
		// echo "$records_num <br>";
		$recordval = "";
		$userval = "";
		for ($i=0; $i < $records_num; $i++) {
			// print_r($recordline[$i]);
			$len = sizeof($recordline[$i]);
			// echo "<BR>len $len <BR>";
			$recordval .= "(";
			for ($j=0; $j < $len; $j++) {
				$recordval .= "'{$recordline[$i][$j]}',";
			}
			$recordval = rtrim($recordval,","); //去掉本纪录的最后一个,
			$recordval .= "),";

			$userval .= "('".$recordline[$i][$userinfo_column[$sheetname[$sheet_index]][1]]."','".$recordline[$i][$userinfo_column[$sheetname[$sheet_index]][0]]."','{$md5password}','','','','','{$sheetname[$sheet_index]}'),";
		}
		$recordval = rtrim($recordval,","); //去掉最后一个,
		$userval .= $staffinfo_headline;
		writeinfo($userval);
		//插入records到数据库

		########################  insert into records table

		$array3Elem = $overtime_records_cloumn[$sheetname[$sheet_index]];
		$line3arraysize = count($array3Elem);
		$sqlinsert_records = "INSERT IGNORE INTO `{$sheet_name}{$table_records}`(";
		for ($iline3=0; $iline3 < $line3arraysize; $iline3++) { // 下标从0开始
			$sqlinsert_records .= " `$array3Elem[$iline3]`,";
		}
		$sqlinsert_records = rtrim($sqlinsert_records,","); //去掉最后一个,
		$sqlinsert_records .= ")VALUES ";
		$sqlinsert = $sqlinsert_records.$recordval;
		// writeinfo($sqlinsert);
		mysql_query("START TRANSACTION"); //启用事务
		$resultinsert = mysql_query($sqlinsert);
		if (!$resultinsert) {
			die("<br>" . __LINE__ . __METHOD__ . " insert {$sheet_name}{$table_records}($sheet_index) error with :".mysql_error());
		}
		mysql_query("COMMIT");

		// 插入用户
		if ($inituser) {
			// echo $values;exit;
			// 如果存在则不替换, 否则需要修改密码
			$sql = "INSERT IGNORE INTO {$table_users}(`username`,`userno`,`password`,`idno`,`bankcardno`,`bankcardname`,`bankname`,`sheet_name`) values ".$userval;

			$queryres = mysql_query($sql);
			if (!$queryres) {
				die("insert '{$userno}' into {$table_users} error: ".mysql_error());
			}

		}

		// 修改标志
		// records 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_records}init',true)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_records}init error: ".mysql_error());
		}

		// die($sql);
		// 首行 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_firstline}init',true)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_firstline}init error: ".mysql_error());
		}

		// 第二行 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_secondline}init',true)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_secondline}init error: ".mysql_error());
		}
		unset($recordval);
		unset($sqlinsert);
		unset($resultinsert);
		unset($recordline);
	}


	################################         获取备注      ################################
	function get_remark_from_excel($objPHPExcel,$j,$highestColumn)
	{
		$str = "";
		for($m = 0, $n='A';$n<=$highestColumn;$m++,$n++)
		{
			$str .=$objPHPExcel->getActiveSheet()->getCell("$n$j")->getValue()."\\";//读取单元格
			// echo "$n $str";
		}
		//echo $str;
		$strs=explode("\\",$str);
		//echo count($strs)."<br>";
		$strnum = count($strs);
		$z = 0;
		$cellcloumn = 0; //
		$linecell = 0; //记录第一行数据
		for($z = 0;$z < $strnum;$z++)
		{
			$itw = 0;
			$ipan = 0;
			$istr = "";

			for(;$z < $strnum;$z++)
			{
				//echo $z." ".$strs[$z]."<br>";
				if($strs[$z] != ""){
					$istr = $strs[$z];
					$linecell++;
					$cellcloumn = $z;
					$remark[$linecell] = $istr;
				}
				$itw += 100;
				$ipan ++;
				if($z == $strnum - 2 || ($z < $strnum && $strs[$z+1] != ""))
				{
					//echo $itw." ".$ipan." "."<br>";
					$cellcloumn = $z - $cellcloumn + 1;
					if ($cellcloumn != 1) {
						$linecell++;
						if($debug) echo "$linecell<br>";
						$remark[$linecell] = $cellcloumn;
					}
					break;
				}
			}
		}
		return $remark;
	}

	################################         获取第一行      ################################
	function get_firstline_from_excel($objPHPExcel,$j,$highestColumn)
	{
		$str = "";
		for($m = 0, $n='A';$n<=$highestColumn;$m++,$n++)
		{
			$str .=$objPHPExcel->getActiveSheet()->getCell("$n$j")->getValue()."\\";//读取单元格
			// echo "$n $str";
		}
		//echo $str;
		$strs=explode("\\",$str);
		//echo count($strs)."<br>";
		$strnum = count($strs);
		$z = 0;
		$cellcloumn = 0; //
		$linecell = 0; //记录第一行数据

		for($z = 0;$z < $strnum;$z++)
		{
			$itw = 0;
			$ipan = 0;
			$istr = "";

			for(;$z < $strnum;$z++)
			{
				//echo $z." ".$strs[$z]."<br>";
				if($strs[$z] != ""){
					$istr = $strs[$z];
					$cellcloumn = $z;
					$firstline[$linecell] = $istr;
					$linecell++;
				}
				$itw += 100;
				$ipan ++;
				if($z == $strnum - 2 || ($z < $strnum && $strs[$z+1] != ""))
				{
					//echo $itw." ".$ipan." "."<br>";
					$cellcloumn = $z - $cellcloumn + 1;
					if ($cellcloumn != 1) {
						if($debug) echo "$linecell<br>";
						$firstline[$linecell] = $cellcloumn;
						$linecell++;
					}
				}
			}
		}
		unset($str);
		// print_r($firstline);exit();
		return $firstline;
	}

	#####################     获取第二行   ####################
	function get_secondline_from_excel($objPHPExcel,$j,$Clos,$highestColumn)
	{
		$str = "";
		$datatype = "";
		$secondlinecell = 0;
		$secondlinecellstr = "";
		for($m = 0, $n='A';$n<=$highestColumn;$m++,$n++)
		{
			$str .=$objPHPExcel->getActiveSheet()->getCell("$n$j")->getValue()."\\";//读取单元格
		}
		// print($Clos);
		//explode:函数把字符串分割为数组。
		$strs=explode("\\",$str);
		$datatypes=explode("\\",$datatype);
		$redcloumn = "";
		$yellowcloumn = "";
		$regex="'\d{4}-\d{1,2}-\d{1,2}'is"; // 提取日期的正则表达式
		for($l = 0;$l < $Clos - 2;$l++)
		{
			if($strs[$l] != "" && preg_match("/^[0-9]+$/", substr($strs[$l],0,4)))
			{
				// echo "{$strs[$l]}   $Clos<br>";
				preg_match_all($regex,$strs[$l],$matches);
				$wholedate = $matches[0];
				$mouthday=substr($wholedate[0],4);
				// echo "matches[0][0] {$matches[0][0]},$mouthday aaa <br>";
				$od = isofficialholiday($mouthday);
				if ($od) {
					$redcloumn .= $l.",";
				}else{
					$yellowcloumn .= $l.",";
				}
				$secondlinecellstr = $strs[$l];
			}
			else
			{
				$secondlinecellstr = $strs[$l];
			}
			$secondline[$l] = $secondlinecellstr;
		}

		//记住红底和黄底的列数

		$secondline[$l] = $yellowcloumn;
		$secondline[$l + 1] = $redcloumn;
		// print_r($secondline);die();
		unset($str);
		// print_r($secondline);exit();
		return $secondline;
	}

	##################      获取record  ####################
	function get_records_from_excel($objPHPExcel, $j, $highestRow, $highestColumn, $Clos, $redcloumns, $yellowcloumns, $employeeid, $sheet_index)
	{
		global $sheetname,$admin_no;
		global $table_validusers, $table_users;
		$records = array();
		$recordsnum = 0;
		$olddept="";
		$newdept="";
		global $dept_arr ;
		$validusers = array();
		$validusersnum = 0;
		// echo "$j highestRow:$highestRow, highestColumn:$highestColumn, Clos:$Clos <br>";
		//循环读取excel文件other line,读取一条,插入一条
		for($j += 1;$j<=$highestRow;$j++){ //从第3行开始读取数据
			$str='';
			for($k='A';$k<=$highestColumn;$k++){
			//从A列读取数据
				$str.=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getCalculatedValue().'\\';//读取单元格的值
			}

			//explode:函数把字符串分割为数组。
			$strs=explode("\\",$str);
			if (empty($strs[0])) {
				break;
			}
			$tcw = 50;
			for($l = 0;$l < $Clos;$l++)
			{
				// 为了判断是否日期，是日期的话就写ajax变量
				$date = 0;
				for($i = 0;$l != 0 && ($redcloumns[$i] != "" || $yellowcloumns[$i] != "");$i++)
				{
					//echo $l." ".$redcloumns[$i]." ".$yellowcloumns[$i] ."<br>";
					if( $l == $redcloumns[$i]  ||  $l == $yellowcloumns[$i]) {
						//echo "it is a DATE NOT NULL record<br>";
						$date = 1;
						break;
					}
				}

				if($date  && $strs[$l] != "" )
				{
					//ajax
					$recordline[$l] = $strs[$l];
				}
				else if($date  && $strs[$l] == "" )
				{
					$recordline[$l] = "0";

				}
				else if($strs[$l] != "") //如果单元格不为空
				{
					$recordline[$l] = $strs[$l];
				}
				else
				{
					//echo "$l    $Clos <br>";
					if ($l > $Clos - 3) {
						$recordline[$l] = "0";
					}else{
						$recordline[$l] = " ";
					}
				}
			}

			// 获取部门第一人
			$newdept=$recordline[1];   //部门值， 讨论的规则是部门第一个人为统计者
			// writeinfo(print_r($recordline,true));
			if(substr_count($newdept,'(') > 0){
				$dept = $newdept.split('(');
				$newdept=$dept[0];
			} else if (substr_count($newdept,'（') > 0){
				// writeinfo('（');
				$dept = explode("（",$newdept);
				// writeinfo(print_r($dept,true));
				$newdept=$dept[0];
				// writeinfo(print_r($newdept,true));
			}
			// writeinfo(print_r($newdept,true));
			if (!in_array($newdept, $dept_arr)) {
				$new_validusers = array();
				if ($sheetname[$sheet_index] == 'dialect') {
					$new_validusers["employeeid"] = $recordline[3]; //乡话员工第四个为编号
					$new_validusers["name"] = $recordline[2];
				}else if ($sheetname[$sheet_index] == 'listed') {
					$new_validusers["employeeid"] = $recordline[2];
					$new_validusers["name"] = $recordline[3];
				}else{
					$new_validusers["employeeid"] = $recordline[2];
					$new_validusers["name"] = $recordline[4];
				}
				$new_validusers["department"] = $newdept;
				$validusers[$validusersnum] = $new_validusers;
				// writeinfo(print_r($new_validusers,true));

				$validusersnum++;
				$dept_arr[count($dept_arr)] = $newdept;
			}

			// 获取部门第一人
			// $newdept=$recordline[1];   //部门值， 讨论的规则是部门第一个人为统计者
			// if($newdept != $olddept){
			// 	if ($sheetname[$sheet_index] != 'dialect') {
			// 		$validusers[$validusersnum] = $recordline[2];
			// 	}else{
			// 		$validusers[$validusersnum] = $recordline[3]; //乡话员工第四个为编号
			// 	}
			// 	$validusersnum++;
			// 	$olddept = $newdept;                          //只有不等厚才能赋值，相等后就一直相等不需要赋值
			// }
			// print_r($recordline);

			$records[$recordsnum] = $recordline;
			$recordsnum++;
			unset($str);
			unset($strs);
		}
		// writeinfo(print_r($validusers,true));
		// writeinfo(print_r($dept_arr,true));
		// echo "recordsnum $recordsnum <br>";
		$records['recordsnum'] = $recordsnum;  // 实际的记录数目由此返回

		// 数据库操作 如果大于0
		if ($validusersnum > 0) {
			for ($userno=0; $userno < $validusersnum; $userno++) {
				$validusersval .= "({$validusers[$userno]['employeeid']},'".$validusers[$userno]['name']."','".$validusers[$userno]['department']."'),";
			}
			// $validusersval .= "($admin_no),";
			$validusersval = rtrim($validusersval,",");
			$sqlinsert_validusers = "INSERT IGNORE INTO `{$table_validusers}`(`employeeid`,`name`,`department`)VALUES".$validusersval; 
			//." ON DUPLICATE KEY UPDATE `employeeid`='$validusersval'";
			
			// writeinfo(print_r($sqlinsert_validusers,true));
			$resultinsert = mysql_query($sqlinsert_validusers);
			if (!$resultinsert) {
				die("<br>" . __LINE__ . __METHOD__ . "<br>" . __LINE__ . "  $sqlinsert_validusers error with :".mysql_error());
			}
		}
		return $records;
	}

	################################         判断数据库是否已经初始化      ################################
	function isdbinited(){
		global $con,$db_name,$db_chractor,$table_seq,$table_records;
		global $debug;

		//drop database $db_name
		if ($debug) {
			$sqldropdb = "DROP DATABASE IF EXISTS `". $db_name ."`";
			mysql_query($sqldropdb,$con);
		}

		if(mysql_select_db($db_name,$con) == true){
			$row = "";
			if($debug) echo "Database $db_name existed"."<br>";
			//select $db_name
			mysql_select_db($db_name,$con);
			//set charset
			mysql_query("SET names $db_chractor");
			$sql = "select flagval from flags where flagname='dialect{$table_records}init'";
			// writeinfo($sql);
			$queryres = mysql_query($sql);
			if (!$queryres) {
				echo "mysql_errno".mysql_errno()."<br>";
				if (1146 == mysql_errno()) {     //1146   ER_NO_SUCH_TABLE
					print("create table flags dialect{$table_records} error: ".mysql_error().", then create table and inittable.<br>");
					// DO INIT
				}
				else{
					die("<br>" . __LINE__ . __METHOD__ . " ". mysql_errno().mysql_error());
				}
			}else {
				$row = mysql_fetch_array($queryres);
				if ($row['flagval'] == '0'  || $row['flagval'] == ''  ) {
				}else{
					return 1;
				}
			}
		}
		return 0;
	}

	################################         初始化数据库      ################################
	function initdb_overtime(){
		global $sheetcount, $sql_create_table_users, $admin_name;
		global $con;
		global $debug;
		global $admin_password,$db_chractor;
		global $admin_no;
		global $con,$db_domain,$db_admin_name,$db_passwd,$db_name;
		global $table_validusers,$table_users;

		//创建数据库
		$sqlcreatedb = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		if(mysql_query($sqlcreatedb, $con)){
			if($debug) echo "Database $db_name created<br>";
		}
		//select $db_name 选择数据库
		mysql_select_db($db_name,$con);
		//set charset 设置字符串
		mysql_query("SET names $db_chractor");
		//初始化 各个sheet的数据
		for ($i=0; $i < $sheetcount; $i++) {
			//0  初始化上市公司员工数据库
			//1  初始化劳务派遣员工数据库
			//2  初始化乡话员工数据库
			init_each_sheet($i);
		}
		// echo "initdb_overtime<br>"."<br>" . __LINE__;
		//创建 validusers 表， 只有这些用户可以修改加班信息。
		$sql_validusers = "CREATE TABLE IF NOT EXISTS {$table_validusers}(`id` int(11) NOT NULL primary key auto_increment, `employeeid` varchar(40) NOT NULL,`name` varchar(40) NOT NULL,`department` varchar(40) NOT NULL,UNIQUE(`employeeid`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$queryres = mysql_query($sql_validusers,$con);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . "  create {$table_validusers} error: ".mysql_error());
		}

		// 创建users表
		$queryres = mysql_query($sql_create_table_users);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table {$table_users} error: ".mysql_error());
		}

		// 创建管理员用户
		$md5adminpassword = md5(md5(md5(md5("$admin_password"))));
		// 如果存在则不替换
		$sql = "INSERT IGNORE INTO {$table_users}(`username`,`userno`,`password`) values('$admin_name', '$admin_no','{$md5adminpassword}')";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " insert '{$userno}' into {$table_users} error: ".mysql_error());
		}
	}

	function init_each_sheet($sheet_index='0')
	{
		global $con;
		global $debug;
		global $admin_password;
		global $admin_no;
		global $con,$db_domain,$db_admin_name,$db_passwd;
		global $table_seq,$table_firstline, $table_secondline, $table_records,$table_remark;
		global $sheetname, $sheetcount, $sheetname_CN;
		global $remark_cloumn,$fix_cloumn,$create_firstline,$create_secondline,$create_records;

		$sql_remark = "CREATE TABLE IF NOT EXISTS {$sheetname[$sheet_index]}{$table_remark}(`id` int(11) NOT NULL primary key auto_increment, `remarks` varchar(500) NOT NULL,remarkslen int(10),UNIQUE(`id`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$queryres = mysql_query($sql_remark,$con);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . "  create {$sheetname[$sheet_index]}{$table_remark} error: ".mysql_error());
		}

		########################  create fistrline table
		$queryres = mysql_query($create_firstline[$sheetname[$sheet_index]],$con);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . "  CREATE {$sheetname[$sheet_index]}{$table_firstline} error: ".mysql_error());
		}

		########################  create secondline table
		$queryres = mysql_query($create_secondline[$sheetname[$sheet_index]],$con);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . "  CREATE {$sheetname[$sheet_index]}{$table_secondline} error: ".mysql_error());
		}

		########################  create records table
		$queryres = mysql_query($create_records[$sheetname[$sheet_index]],$con); //holidaysnum int(4), `holidays` varchar(500) NOT NULL, `holidayslen` int(11),
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . "  CREATE {$sheetname[$sheet_index]}{$table_records} error: ".mysql_error());
		}

		####################### 初始化标志
		// 如果没有创建flags表，则创建
		$sql = "create table IF NOT EXISTS flags(id int primary key auto_increment, flagname varchar(40) unique key NOT NULL, flagval boolean NOT NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags error: ".mysql_error());
		}
		// records 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_records}init',false)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_records}init error: ".mysql_error());
		}

		// 首行 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_firstline}init',false)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_firstline}init error: ".mysql_error());
		}
		// 第二行 初始化标志
		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$sheetname[$sheet_index]}{$table_secondline}init',false)";
		// echo $sql . "<br>";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("<br>" . __LINE__ . __METHOD__ . " create table flags {$sheetname[$sheet_index]}{$table_secondline}init error: ".mysql_error());
		}
	}

?>