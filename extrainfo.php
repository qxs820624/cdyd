 <?php
	error_reporting(E_ERROR);
	set_time_limit(0); //脚本不超时
	if ($_GET['fileurl']) {
		$url = $_GET['fileurl'];
		getfile($url);
		die($url);
	}else if ($_GET['getpackage']) {
		getpackage($_GET['getpackage']);
		die();
	}
	require_once 'Classes/PHPExcel.php';
	require_once 'Classes/PHPExcel/IOFactory.php';
	require_once 'classfiletype.php';
	require_once 'globalconfig.php';
	require_once 'dbconn.php';
	require_once 'dboper.php';
	require_once 'webexceloper.php';

	header("Content-Type:text/html;charset=utf-8"); //tell the php that the charset is utf-8
	header ("Cache-Control: no-store, no-cache,must-revalidate ");
	header('Pragma:no-cache');
	header("Expires:0");

	$Cols  = 0;
	$file_url = $overtimefile;   // excel file name to load


	$objPHPExcel="";
	$objReader="";
	$PHPExcel="";
	$yellowclos4save = "";
	$redclos4save = "";

	if ($_POST["exporttoserver"]){
		// print_r($_POST);
		$password = $_POST['password'];
		// if($password != $admin_password){
		$query=varifypassword($admin_no, $password);
		// echo $query;
		$row = mysql_fetch_array($query);
		// print_r($row);
		if( empty($row ) ){
			echo "只有管理员才能导出，请输入正确的密码，你输入的密码为$password 。";
		}else{
			// echo "导出到服务器<br>";
			$fn = create_excel_with_db();
			//testsave();
			echo "$fn";
		}
		mysql_close($con);
		die();
	}else if ($_POST["exporttolocal"]){
		$ret=create_excel_with_db();
		mysql_close($con);
		die($ret);
	}elseif ($GET['exporttolocal']) {
		$ret=create_excel_with_db();
		mysql_close($con);
		echo "<script>location.href='".$ret."';</script>";
		die;
	}
?>

<?php
	echo '<!DOCTYPE HTML> 
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="'.$ico_url.'" type="image/x-icon" />
		<title>'.$overtime_webtitle.'</title>
		<script src="'. $jqfile.'" ></script>
		<script type="text/javascript" src="overtime.js"></script>
		<!-- <style type="text/css"></style> -->
		<link rel="stylesheet" type="text/css" href="overtime.css">
	</head>
	<body>';
	################  这些代码必须放在前面，执行后不改变原来的页面   #############
	if ($_GET['dirurl']) {
		$dir = $_GET['dirurl'];
		list_file($dir);
		die();
	}
	
	// error_log(__LINE__.": \r\n",3,"err.txt","test");
	// echo "post" . print_r($_POST)."<BR>";
	// echo "_GET" . print_r($_GET)."<BR>";
	if (empty($_POST["userno"])) {
			$usernoErr = "是必填的";
			$err = true;
			//exit;
	} else if(!is_numeric(test_input($_POST["userno"]))){
		if ($_POST["userno"] == "admin") {
			$_POST["userno"] = "$admin_no";
		}else{
			$usernoErr = "员工编号(公免号码)必须为数字";
			$err = true;
		}
	}
	// writeinfo(print_r($_POST, true));
	if ($_POST["records1"] !=''){
		if ($con) {
			mysql_close($con);
		}
		$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
		if(!$con ){
			die("<br>" . __LINE__ . __METHOD__ . ' 连接数据库失败,Could not connect: ' . mysql_error());
		}
		$webtab = $_POST["webtabname"];
		if (empty($webtab)) {
			// error_log(print_r($_SERVER, true)."\n",3,"./err.txt","webtabname");
			$url = parse_url($_SERVER[HTTP_REFERER]);
			$webtab = $url[fragment];
			// error_log($webtab."\n",3,"./err.txt","webtabname");
			if (empty($webtab)) {
				$webtab = "listed";
			}
		}
		// error_log($webtab,3,"./err.txt","webtabname");
		mysql_select_db($db_name, $con);
		//set charset
		mysql_query("SET names $db_chractor");
		if (strstr($webtab,"#")) {
			$webtab = ltrim($webtab, "#");
		}

		// writeinfo($webtab);
		$sql="update ".$webtab."{$table_records} set ";
		$record_set = "";
		for($i = 1; $i <= $holidaysnum; $i++){
			$record_set = $record_set . "records$i= '".$_POST["records$i"]. "',";
		}
		$sql= $sql . $record_set;
		$sql= $sql . " yellowrecords='".$_POST[yellowrecords]."', redrecords='".$_POST[redrecords]."'   where serialno='".$_POST[serialno]."';";
		// writeinfo($sql);
		
		$result=mysql_query($sql,$con);
		if($result){
			echo "保存成功";
		}else{
			echo $sql ;
			echo "保存失败";
		}
		mysql_close($con);
		exit();
	}else if ($_POST["submitrecords"]){
		echo "<script>if(confirm('你确定提交吗？')){alert('提交成功');location.href='".$staffinfofile."';}</script>";
	}
	// error_log(__LINE__.": \r\n",3,"err.txt","test");
	################  这些代码必须放在前面，执行后不改变原来的页面 结束  #########################
	################         主逻辑部分开始         ##############################################
	if ($con) {
		mysql_close($con);
	}
	$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
	if(!$con ){
		die("<br>" . __LINE__ . __METHOD__ . '  连接数据库.'.$db_domain.$db_admin_name.$db_passwd.' 失败: ' . mysql_error());
	}

	//set charset
	$employeeid = "0";
	mysql_query("SET names $db_chractor");
	$res = isdbinited();
	// error_log(__LINE__.": \r\n",3,"err.txt","test");
	if ($res == 1) {
		# db existed
		// echo "employeeid {$_GET['employeeid']}<br>";
		// print_r($_GET);
		// error_log(__LINE__.": \r\n",3,"err.txt","test");
		if ($_GET['employeeid']) {
			writeinfo(print_r($_SERVER,true));

			$employeeid = $_GET['employeeid'];
			$employeeid = isvaliduser($employeeid);
			// die($employeeid);
			// writeinfo($employeeid);
			create_table_with_db($employeeid);
		}else{
			// echo "employeeid 2"; //跳转到登录页面
			echo "<script>location.href='".dirname($_SERVER['PHP_SELF'])."/".$staffinfofile."';</script>";
			return;
			// create_table_with_db("0");
		}
	} else {
		//判断excel文件类型
		// error_log(__LINE__.": \r\n",3,"err.txt","test");
		if ($_GET["initfile"] ) {
			// writeinfo(print_r($_GET,true));
			if (file_exists($_GET["initfile"])) {
				$file_url = $_GET["initfile"];
				$employeeid = $admin_no;
				dropdatabase();
				// error_log(print_r($_SERVER,true),3,"./err.txt","initfile");
			}else{die("源文件{$_GET["initfile"]}不存在，取消操作");}
		}

        // phpExcel大数据量情况下内存溢出解决 保存在php://temp
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
		$cacheSettings = array( 'memoryCacheSize'=>'32MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        ini_set('memory_limit','128M'); // 设置php缓存为600M

		if ($_GET['employeeid']) {
			$employeeid = $_GET['employeeid'];
		}
		initdb_overtime();
		create_db_with_excel($objPHPExcel,$file_url,$employeeid);
		$employeeid = isvaliduser($employeeid);
		//
		if ($employeeid == "0") {
			//跳转到登录页面
			echo "<script>location.href='".dirname($_SERVER['PHP_SELF'])."/".$staffinfofile."';</script>";
			return;
		}
		create_table_with_db($employeeid);
	}

	// echo "employeeid  ".$employeeid;
	if ($employeeid == $admin_no){
		printforms($employeeid);
	}else if ($employeeid != "0"){
		printsubmit($employeeid);
	}else if ($employeeid == "0"){
		//跳转到登录页面
		echo "<script>location.href='".dirname($_SERVER['PHP_SELF'])."/".$staffinfofile."';</script>";
		return;
	}
	mysql_close($con);
	$con = '';

	################         以下为函数实现部分         ##############################################
	################################      把日期表格转换为时间字符串  ################################
	function numericdateformat($formatcode){
		$val = 0;
		$formatcodes = explode("-",$formatcode);
		if (count($formatcodes) == 3   || countI(explode("/",$formatcode)) == 3) {
			//$value=gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($formatcode));   //int to string
			$val = PHPExcel_Shared_Date::FormattedPHPToExcel($formatcodes[0], $formatcodes[1], $formatcodes[2]);    //int gregoriantojd ( int $month , int $day , int $year )
		}
		//echo $val."\n";
		return $val;
	}

	################################        设置颜色     ################################
	function getcolor4save($clono, $yellowclos4save,$redclos4save){
		for ($i=0; $i < count($yellowclos4save) - 1; $i++) {
			if ($clono == $yellowclos4save[$i]) {
				$color = "FFFFFF00"; //yellow
				$getclor = 1;
				return $color;
			}
		}
		for ($j=0; $j < count($redclos4save) - 1; $j++) {
			if ($clono == $redclos4save[$j]) {
				$color = "FFFF0000"; //red
				return $color;
			}
		}
		$color = "0000FFFF"; //blue
		return $color;
	}

	function setcolor($objStyle, $colorval) {
		//设置填充颜色
		$objFill = $objStyle->getFill();
		$objFill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objFill->getStartColor()->setARGB($colorval);
	}
	function convert($size){
		$unit=array('b','kb','mb','gb','tb','pb');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}

	function getfile($fileurl)
	{
		$filename=$fileurl;
		$desname = basename($filename);
		$file   =   fopen($filename, "rb");
		Header( "Content-type:   application/force-download ");
		Header( "Accept-Ranges:  bytes ");
		Header( "Content-Disposition: attachment; filename= ".$desname);
		header( "Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件  
		header('Content-Length: '. filesize($desname)); //告诉浏览器，文件大小
		$contents = "";
		while (!feof($file)) {
		  $contents .= fread($file, 8192);
		}
		echo $contents;
		die($contents);
		fclose($file);
	}

	function list_file($dir){
		global $charset;
		$list = scandir($dir); // 得到该文件下的所有文件和文件夹
		$text = "目录为粉红色";
		echo $charset != "GB2312" ? iconv("UTF-8", "GB2312//IGNORE", $text) : $text;
		echo "<ul id='ullfile' style='overflow:auto;height:590px'>";
		foreach($list as $file){//遍历
			$transdir = $charset != "GB2312" ? iconv("UTF-8", "GB2312//IGNORE", $dir) : iconv("GB2312", "UTF-8//IGNORE", $dir);
			$transfile = $charset != "GB2312" ? iconv("UTF-8", "GB2312//IGNORE", $file) : iconv("GB2312", "UTF-8//IGNORE", $file);
			$file_location=$dir.DIRECTORY_SEPARATOR.$file;//生成路径
			if(is_dir($file_location) && $file!="." ){  // && $file!=".."
				//判断是不是文件夹
				if ($file == ".." && $dir ==".") {
					echo "<li class='lidirectory'><font color=pink><a href=?dirurl=".'..'."><b>$transfile</b></font></li>";
				}else if ($file == ".." && basename($dir) != "..") {
					echo "<li class='lidirectory'><font color=pink><a href=?dirurl=".dirname($dir)."><b>$transfile</b></font></li>";
				}else{
					echo "<li class='lidirectory'><font color=pink><a href=?dirurl=".$file_location."><b>$transfile</b></font></li>";
				}
				// list_file($file_location); //继续遍历
			}else{
				if ( $file!="." && $file!="..") {
					echo "<li class='showfile'><a href=?fileurl=".$file_location." target=_self>$transfile</a></li>";
				}
			}
		}
		echo "</ul>";
		$text = "打包下载";
		$transtext = $charset != "GB2312" ? iconv("UTF-8", "GB2312//IGNORE", $text) : $text;
		echo "<li style='float:left;'><a href=?getpackage=".$dir.">". $transtext ."</a></li>";
	}
	// 保留完整的目录结构
    function listdir($start_dir='.') {
    	$files = array();
    	if (is_dir($start_dir)) {
    		$fh = opendir($start_dir);
    		while (($file = readdir($fh)) !== false) {
    			if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
    			$filepath = $start_dir . '/' . $file;
    			if ( is_dir($filepath) ){
    				$files = array_merge($files, listdir($filepath));
    			}else{
    				array_push($files, $filepath);
    			}
    		}
    		closedir($fh);
    	} else {
    		$files = false;
    	}
    	return $files;
    }

    function getpackage($dir)
    {
    	if (!is_dir($dir)) {
    		die("$dir 不是目录");
    	}
    	$oldworkdir = getcwd();
    	foreach (glob('*.zip') as $file) {
    		// echo $file . PHP_EOL;
    		unlink($file);
    	}
    	chdir($dir);

		$zip = new ZipArchive();
		$bname = basename($dir);
		$fname = "";
		if ($bname != "..") {
			$fname = $bname;
		}
	    $filename = '../'.$fname."_".date("Ymd")."_".md5(time())."_src.zip";
	    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
	      exit("无法创建 <$filename>\n");
	    }
	    $files = listdir();
	    foreach($files as $path)
	    {
	      $zip->addFile($path,str_replace("./","",str_replace("\\","/",$path)));
	    }
	    $zip->close();

		if(!file_exists($filename)){
		    exit("无法找到文件"); //即使创建，仍有可能失败。。。。
		}
		$dest = $oldworkdir.DIRECTORY_SEPARATOR.basename($filename);
		// error_log($dest.PHP_EOL,3,"./err.txt","cwd");
		rename($filename, $dest); // 移到文件所在的目录
		echo "<script>location.href='".basename($dest)."';</script>"; //直接下载
    }

	################################         从数据库写入表格      ################################
	function create_excel_with_db()
	{
		set_time_limit(0); //脚本不超时
		global $yellowclos4save, $redclos4save;
		global $db_domain,$db_admin_name,$db_passwd;
		global $sheetname, $sheetcount, $sheetname_CN;
		//生成表格
		//新建
		$PHPExcel = new PHPExcel();
		//设置文档基本属性
		$PHPExcel->getProperties()->setCreator("yangxin")
					->setLastModifiedBy("yangxin")
					->setTitle("overtime")
					->setSubject("overtime,chinamobile")
					->setDescription("to stastics the overtime times of offers in chinamobile of Chnagede")
					->setKeywords("overtime")
					->setCategory("overtime");
		//缺省情况下，PHPExcel会自动创建第一个sheet被设置SheetIndex=0
		for ($i=0; $i < $sheetcount; $i++) {
			create_sheet_with_db($PHPExcel,$i);
		}

		$outputFileName = 'overtime';
		$date = date("Y-m-d_His",time());
		$outputFileName .= "_{$date}.xls";
		$outputFileName = iconv("utf-8", "gb2312", $outputFileName);
		$ua = $_SERVER["HTTP_USER_AGENT"];
		// error_log($ua,3,"./err.txt","HTTP_USER_AGENT");

		$PHPExcel->setActiveSheetIndex(0);
		$objWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
		

		header('Pragma:public');
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Type:application/force-download");
		header("Content-Type:application/download");
		header("Content-Type:application/octet-stream;charset=utf-8");
		header("Content-Disposition:attachment;filename={$outputFileName}");
		header("Content-Transfer-Encoding:binary");
		header("Last-Modified:".gmdate("D,d-M-Y H:i:s")."GMT");
		header("Cache-Control:must-revalidate,post-check=0,pre-check=0,max-age=0");
		header("Pragma:no-cache");
		//到文件
		$objWriter->save($outputFileName);
		// error_log(print_r($_POST,true),3,"./err.txt","exporttolocal");
		if($_POST['exporttolocal']){
			//文件通过浏览器下载
			// 输入文件标签
			$excelfileurl="http://".$_SERVER['HTTP_HOST']."/".dirname(trim($_SERVER['REQUEST_URI'],'/'))."/".$outputFileName;
			// error_log($excelfileurl,3,"./err.txt","excelfileurl");
			return $excelfileurl;
		}else if($_POST['exporttoserver']){
			//脚本方式运行，保存在当前目录
			return "导出  ".$outputFileName." 到服务器 ".dirname(__FILE__)."/"." 成功!";
		}
	}

	################ 导出sheet
	function create_sheet_with_db($PHPExcel,$sheet_index){
		global $yellowclos4save, $redclos4save;
		global $db_domain,$db_admin_name,$db_passwd,$db_name,$db_chractor;
		global $sheetname, $sheetcount, $sheetname_CN;
		global $table_seq,$table_firstline, $table_secondline, $table_records,$table_remark;
		global $overtime_records_cloumn, $remark_cloumn, $firstline_cloumn,$sencondline_column,$fix_cloumn;

		if ($sheetname[$sheet_index] != 'listed') {
			$PHPExcel->createSheet();
		}
		$PHPExcel->setActiveSheetIndex($sheet_index);
		$objActSheet=$PHPExcel->getActiveSheet();
		$objActSheet->setTitle("{$sheetname_CN[$sheetname[$sheet_index]]}");
		if ($con) {
			mysql_close($con);
		}
		$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
		if(!$con ){
			die("<br>" . __LINE__ . __METHOD__ . " Could not connect: " . mysql_error());
		}
		mysql_select_db($db_name, $con);
		//set charset
		mysql_query("SET names $db_chractor");

		//设置参数
		// 读取备注信息
		$rowindex = 0;
		$rowindex++;
		$getremark  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_remark}";
		$result = mysql_query($getremark);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get listedfirstline eror: " . mysql_error());
		}

		while ($remarkline = mysql_fetch_assoc($result)) {
			//设值
			$colstr = 'A';
			$colindex = $colstr.$rowindex;
			$PHPExcel->getActiveSheet()->setCellValue($colindex ,$remarkline[$remark_cloumn[1]]);
			for ($j=0;  $j < $remarkline[$remark_cloumn[2]] - 1; $j++,$colstr++);
			$colnew = $colstr.$rowindex;
			$PHPExcel->getActiveSheet()->mergeCells($colindex . ":" .$colnew);

		}

		$rowindex++;
		//读取第一行,生成第一行的表格
		$getfirstline  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_firstline}";
		$result = mysql_query($getfirstline);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$sheetname[$sheet_index]}{$table_firstline} eror: " . mysql_error());
		}
		//echo "get firstline<br>";
		while ($firstline = mysql_fetch_assoc($result)) {
			//设值
			$colstr = 'A';
			$firstline_cloumn_number = count($firstline_cloumn);
			// writeinfo("firstline  cloumn number: ".$firstline_cloumn_number);
			for ($i=0; $i < $firstline_cloumn_number; $i++) {
				$colindex = $colstr.$rowindex;

				$PHPExcel->getActiveSheet()->setCellValue($colindex ,$firstline[$firstline_cloumn[$i]]);
				// echo "{$firstline_cloumn[$i]}                 {$firstline[$firstline_cloumn[$i]]}<br>";
				if($i < $firstline_cloumn_number - 2){
					$i++;
					for ($j=0;  $j < $firstline[$firstline_cloumn[$i]] - 1; $j++,$colstr++);
					// echo "{$firstline_cloumn[$i]}                 {$firstline[$firstline_cloumn[$i]]}<br>";
					//合并单元格
					$colnew = $colstr.$rowindex;
					// writeinfo("$colindex : $colnew , {$firstline[$firstline_cloumn[$i]]}");
					$PHPExcel->getActiveSheet()->mergeCells($colindex . ":" .$colnew);
					setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),"0000EE00"); // green
				}else{
					//合并单元格
					$rowindexnext = $rowindex + 1;
					$colnew = $colstr.$rowindexnext;
					// writeinfo($colindex . ":" .$colnew);
					$PHPExcel->getActiveSheet()->mergeCells($colindex . ":" .$colnew);
					if ($i == $firstline_cloumn_number - 2) {
						setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),"FFFFFF00"); //yellow
					}else{
						setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),"FFFF0000"); //red
					}

				}
				$colstr++;
			}
		}

		//读取第二行的数据
		$rowindex++;
		$getsecondline  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_secondline}";
		$result = mysql_query($getsecondline);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$sheetname[$sheet_index]}{$table_secondline} eror: " . mysql_error());
		}
		// echo "<br><br>get getsecondline<br>";
		$array2Elem = $sencondline_column[$sheetname[$sheet_index]];

		while ($secondline = mysql_fetch_assoc($result)) {
			$yellowclos4save = explode(",",$secondline[yellowdays]);
			$redclos4save = explode(",",$secondline[reddays]);
			$mindataindex = min($yellowclos4save[0],$redclos4save[0]);
			//echo "$mindataindex";
			$colstr = 'A';
			$clono = 0;
			for ($i=0; $i < count($array2Elem); $i++) { 
				$colindex = $colstr.$rowindex;
				$formatcode  = $secondline[$array2Elem[$i]];
				//echo "$formatcode";
				// 判断是不是日期格式：yyyy-MM-dd ---- 时间范围：1700-01-01 ---- 2099-12-31
				$regexpdate ="/^[0-9]{4}(\-|\/)[0-9]{1,2}(\\1)[0-9]{1,2}(|\s+[0-9]{1,2}(|:[0-9]{1,2}(|:[0-9]{1,2})))$/";
				if (preg_match($regexpdate, $formatcode)) {
					$value =  numericdateformat($formatcode);
					$PHPExcel->getActiveSheet()->setCellValue($colindex,$value);
					//$PHPExcel->getActiveSheet()->setCellValue($colindex,$formatcode);
					$PHPExcel->getActiveSheet()->getCell($colindex)->getWorksheet()->getStyle($colindex) ->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
					$PHPExcel->getActiveSheet()->getCell($colindex)->setDataType(PHPExcel_Cell_DataType::TYPE_NUMERIC);
				}else{
					$PHPExcel->getActiveSheet()->setCellValue($colindex,$formatcode);
				}
				$colstr++;
				$color = getcolor4save($clono, $yellowclos4save,$redclos4save);
				setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),$color );
				$clono ++;
			}
		}
		//恢复数据记录
		$rowindex++;
		$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records}";
		$result = mysql_query($getrecords);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$sheetname[$sheet_index]}{$table_records} eror: " . mysql_error());
		}
		$array3Elem = $overtime_records_cloumn[$sheetname[$sheet_index]];

		$fixclonum = $fix_cloumn[$sheet_index]; // 固定的列数 显示为灰色
		$arraysize = count($array3Elem);
		$last2ndindex = $arraysize - 2; //倒数第二列的列数
		$last1stindex = $arraysize - 1; //倒数第一列的列数

		$rowno = $rowindex;
		while($records=mysql_fetch_array($result,MYSQL_ASSOC )){
			$colstr = 'A';
			for ($i=0; $i <  $arraysize; $i++) {
				$colindex = $colstr.$rowno;
				switch ($i) {
					case $last2ndindex:
						$cellval = "=SUM(";
						for ($k=0; $k < count($yellowclos4save) - 1; $k++) {
							$iindex = 'A';
							for ($j=0; $j < $yellowclos4save[$k]; $j++) {
								$iindex ++;
							}
							$cellsumindex = $iindex.$rowno;
							$cellval = $cellval .$cellsumindex;
							if ($k < count($yellowclos4save) - 2) {
								$cellval = $cellval .",";
							}
						}
						$cellval = $cellval . ")";
						//echo "$cellval \n";
						break;
					case $last1stindex :
						$cellval = "=SUM(";
						for ($k=0; $k < count($redclos4save) - 1; $k++) {
							$iindex = 'A';
							for ($j=0; $j < $redclos4save[$k]; $j++) {
								$iindex ++;
							}
							$cellsumindex = $iindex.$rowno;
							$cellval = $cellval .$cellsumindex;
							if ($k < count($redclos4save) - 2) {
								$cellval = $cellval .",";
							}
						}
						$cellval = $cellval . ")";
						//echo "$cellval \n";
						break;
					default:
						if ($records[$array3Elem[$i]] == '') {
							$cellval = "0";
						}else{
							$cellval = $records[$array3Elem[$i]];
						}
						break;
				}
				$PHPExcel->getActiveSheet()->setCellValue($colindex, $cellval);
				if($i < $fixclonum){
					setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),"A9A9A9A9"); //dakgray
				}else if(($rowno %2) == 0){
					setcolor($PHPExcel->getActiveSheet()->getStyle($colindex),"C0C0C0FF"); //gray
				}
				$colstr++;
			}
			$rowno++;
		}

		mysql_close($con);

	}//create_excel_with_db  end


	################################         创建富文本单元表格      ################################
	function createrichtextcell($objPHPExcel, $text,$index){
		$objRichText = new PHPExcel_RichText( $objPHPExcel->getActiveSheet()->getCell("{$index}") );
		$objRichText->createText("$text");
		$objPayable = $objRichText->createTextRun('payable within thirty days after the end of the month');
		$objPayable->getFont()->setBold(true);
		$objPayable->getFont()->setItalic(true);
		$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_DARKGREEN ) );
		$objRichText->createText(' ');
	}

	################################         转换excel表格的时间格式      ################################
	function excelTime($date, $time = false) {
		if(function_exists('GregorianToJD')){
			if (is_numeric( $date )) {
				$jd = GregorianToJD( 1, 1, 1970 );

				$gregorian = JDToGregorian( $jd + intval ( $date) - 25569 );

				$date= explode( '/', $gregorian );

				$date_str = str_pad( $date [2], 4, '0', STR_PAD_LEFT )

				."-". str_pad( $date [0], 2, '0', STR_PAD_LEFT )

				."-". str_pad( $date [1], 2, '0', STR_PAD_LEFT )

				. ($time ? " 00:00:00" : '');

				return $date_str;
			}
		}else{
			$date=$date >25568?$date:25569;
			/*There was a bug if Converting DATE NOT NULL before 1-1-1970 (tstamp 0)*/
			$ofs=(70 * 365 + 17+2) * 86400;
			$date= date("Y-m-d",($date* 86400) - $ofs).($time ? " 00:00:00" : '');
		}
		return $date;
	}

	function test(){
		global $overtimefile;
		$fileName =$overtimefile;
		$path = '替换成文件的具体路径';
		$filePath = $path.$fileName;
		$PHPExcel = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		//为了可以读取所有版本Excel文件
		if(!$PHPReader->canRead($filePath)) {
			$PHPReader = new PHPExcel_Reader_Excel5();
			if(!$PHPReader->canRead($filePath)){
				echo '未发现Excel文件！';
				return;
			}
		}
		//不需要读取整个Excel文件而获取所有工作表数组的函数，感觉这个函数很有用，找了半天才找到
		$sheetNames  = $PHPReader->listWorksheetNames($filePath);
		//读取Excel文件
		$PHPExcel = $PHPReader->load($filePath);
		//获取工作表的数目
		$sheetCount = $PHPExcel->getSheetCount();
		//选择第一个工作表
		$currentSheet = $PHPExcel->getSheet(0);
		//取得一共有多少列
		$allColumn = $currentSheet->getHighestColumn();
		//取得一共有多少行
		$allRow = $currentSheet->getHighestRow();
		//循环读取数据，默认编码是utf8，这里转换成gbk输出
		for($currentRow = 1;$currentRow<=$allRow;$currentRow++) {
			for($currentColumn='A';$currentColumn<=$allColumn;$currentColumn++){
				$address = $currentColumn.$currentRow;
				echo iconv( 'utf-8','gbk', $currentSheet->getCell($address)->getValue() )."\t";
			}
			echo "<br />";
		}
	}

	################################         创建底部按钮      ################################
	function printforms($employeeid)
	{
		echo '
		<div class="uploadform"><form enctype="multipart/form-data" method="post"  name="uploadfile" action="'.$_SERVER['PHP_SELF'] .'?employeeid='.$employeeid.'">
		<div style="font-size:15px;"><label size=50px>上传文件：</label><input class="fileinputbt" type="file" name="upfile" /></div>
		<div style="font-size:15px;"><label size=50px>请输入密码(上传)：</label><input type="password" name="password" value=""></div>
		<div style=""><input type="submit" style="margin-right:50px;font-size:15px;color:orange;width:100px;height:30px" name="upload" value="替换加班时间"/>
		<input type="button" name="exporttolocal" style="margin-right:50px;font-size:15px;color:orange;width:100px;height:30px" value="保存到本地" size="150"  onClick="submittolocal()" />
		</div>
		</form><p class="info" style="font-size:12px;color:blue;height:24px" ></p></div>
		';
	}

	################################         创建提交      ################################
	function printsubmit($employeeid)
	{
		global $save_local;
		# code...提交
		echo '
		<div class="submitform"><form enctype="multipart/form-data" method="post"  name="submitrecords" action="'.$_SERVER['PHP_SELF'] .'?employeeid='.$employeeid.'">
		<tr><td colspan="2">
		<div align="center">
		<input type="submit" name="submitrecords" style="margin-right:50px;font-size:15px;color:orange;width:100px;height:30px" value="提&nbsp;&nbsp;交" size="150" />
		'. ($save_local ? '
				<input type="button" name="exporttolocal" style="margin-right:50px;font-size:15px;color:orange;width:100px;height:30px" value="保存到本地" size="150"  onClick="submittolocal()" />':'') .'
		</div></td> </tr>
		</form></div>
		';
	}

	################################         上传excel并初始化数据库      ################################
	if(is_uploaded_file($_FILES['upfile']['tmp_name'])){
		$upfile=$_FILES["upfile"];
		if (($_POST[submit] == "上传" ) ||$_POST["upload"]){
			$password = $_POST['password'];
			$query=varifypassword($admin_no, $password);
			$row = mysql_fetch_array($query);
			if( empty($row ) ){
				die("<div style='font-size:15px;color:red;height:24px'>Authorization Failed.密码错误</div>");
			}
		}
		//获取数组里面的值
		$name=$upfile["name"];//上传文件的文件名
		$type=$upfile["type"];//上传文件的类型
		$size=$upfile["size"];//上传文件的大小
		$tmp_name=$upfile["tmp_name"];//上传文件的临时存放路径

		$isxlsdoc = false;
		// echo (cFileTypeCheck::getFileType($tmp_name) . "              " . $type);
		if (cFileTypeCheck::getFileType($tmp_name) == "xls/doc") {
			$isxlsdoc = true;
		}
		//判断是否为图片
		switch ($type){
			case 'application/download':
			case 'application/octet-stream':
			case 'application/kset':
			case 'application/vnd.ms-excel':
				$okType=true;
				break;
			case 'image/pjpeg':
			case 'image/jpeg':
			case 'image/gif':
			case 'image/png':
				$okType=false;
				break;
		}

		if($okType && $isxlsdoc){
			/**
			* 0:文件上传成功<br/>
			* 1：超过了文件大小，在php.ini文件中设置<br/>
			* 2：超过了文件的大小MAX_FILE_SIZE选项指定的值<br/>
			* 3：文件只有部分被上传<br/>
			* 4：没有文件被上传<br/>
			* 5：上传文件大小为0
			*/
			$error=$upfile["error"];//上传后系统返回的值
			$upinfo =  "================================================<br/>";
			$upinfo .=   "上传文件名称是：".$name."<br/>";
			$upinfo .=   "上传文件类型是：".$type."<br/>";
			$upinfo .=   "上传文件大小是：".$size."<br/>";
			$upinfo .=   "上传后系统返回的值是：".$error."<br/>";
			$upinfo .=   "上传文件的临时存放路径是：".$tmp_name."<br/>";

			$upinfo .=   "开始移动上传文件<br/>";
			//把上传的临时文件移动到up目录下面
			$destination="./up/".microtime(1)."_".$name;
			if (!file_exists('./up')){
				mkdir ("./up");
				$upinfo .=   '创建文件夹test成功<br/>';
			} else {
				$upinfo .=   '需创建的文件夹up已经存在<br/>';
			}
			if(rename($tmp_name,$destination)){
				$upinfo .=   "================================================<br/>";
				$upinfo .=   "上传信息：<br/>";
				if($error==0){
					$upinfo .=   "<br/>文件上传成功啦！<br/>";
					$upinfo .=   "存放路径是：$destination <br/>";
				}elseif ($error==1){
					$upinfo .=   "超过了文件大小，在php.ini文件中设置<br/>";
				}elseif ($error==2){
					$upinfo .=   "超过了文件的大小MAX_FILE_SIZE选项指定的值<br/>";
				}elseif ($error==3){
					$upinfo .=   "文件只有部分被上传<br/>";
				}elseif ($error==4){
					$upinfo .=   "没有文件被上传<br/>";
				}else{
					$upinfo .=   "上传文件大小为0<br/>";
				}
			}else{
				echo $upinfo;
				die("移动文件失败啦！<br/>");
			}
			echo '<script>$(".info").show().html("'.$upinfo.'");</script>';
			//(初始化)删除数据库overtime
			if($con == ''){
				$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
				if(!$con ){
					die("<br>" . __LINE__ . __METHOD__ . ' 连接数据库失败: ' . mysql_error());
				}
			}
			// writeinfo("here".$destination."  href:".$_SERVER['PHP_SELF']);
			####  refreshpage();
			dropdatabase();
			echo "<script>location.href='".$_SERVER['PHP_SELF']."?initfile={$destination}';</script>";   //alert('更新数据库和页面');
		}else{
			echo "<div style='position:absolute;left:100px'><label style='margin-right:400px;font-size:15px;color:red;height:30px'>请上传excel 97-2003格式文件</label></div>";
		}
		die();
	}else if ($_POST['upload'])
	{
		die("<div style='position:absolute;left:100px'><h2 style='margin-right:480px;color:red;' >你没有选择文件</h2></div>");
	}
	echo "</body>
</html>";
?>

