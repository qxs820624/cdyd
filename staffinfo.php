<?php
	error_reporting(E_ERROR);
	set_time_limit(0); //脚本不超时
	require_once 'Classes/PHPExcel.php';
	require_once 'Classes/PHPExcel/IOFactory.php';
	require_once 'Classes/PHPExcel/Reader/Excel5.php';
	require_once 'Classes/PHPExcel/Writer/Excel2007.php';
	require_once 'globalconfig.php';
	require_once 'dboper.php';
	header("Content-Type:text/html;charset=utf-8");
	header("Cache-Control: no-store, no-cache,must-revalidate " );
	header('Pragma:no-cache');
	header("Expires:0");

	$stafffile = $overtimefile;

	// 此处不能放开，否则导出excel的时候会出错，格式不对，它会把这段文字输出到excel中，导致格式不对
	// echo "<!DOCTYPE html><meta http-equiv='expires' content='Sunday 26 October 2008 01:00 GMT' />";
	// echo "<meta http-equiv='pragma' content='no-cache' />";
	// echo '<META HTTP-EQUIV="expires" CONTENT="0">';
	// 定义变量并设置为空值
	$usernoErr = $passwordErr = $confirmpasswordErr = $usernameErr = $idnoErr = $bankcardnoErr = $bankcardnameErr = $banknameErr = "";
	$userno = $password = $confirmpassword = $username = $idno = $bankcardno = $bankcardname = $bankname ="";
	$err = false;
	$con =  "";
	// writeinfo(print_r($_POST,true));
	// writeinfo($_FILES["inputup"]["type"]);
	// if(preg_match("/^[0-9]+$/","1llll1232")) echo "Y";else echo "N";
	// exit;
	//database 数据库
	// $db_domain = "localhost";
	// $db_admin_name = "root";
	// $db_passwd = "";

	// $admin_no = '36270000';
	// $admin_password = 'qwe!@#';

	// getbrowsertype();
	// getbrowserlanguage();

	session_start();
	// echo "post" . print_r($_POST)."<BR>";
	// echo "_GET" . print_r($_GET)."<BR>";
	if ($_GET['initusers'] != '') {
		$stafffile = $_GET['initusers'];
		getcon();
		printlogin();
		echo "<script>location.href='".$_SERVER['PHP_SELF']."';</script>";
	}
	if ($_POST['post_resetpassword']) {
		fuc_resetpassword($_POST['post_resetpassword'],$_POST['password']);
		die;
	}
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (empty($_POST["userno"])) {
			$usernoErr = "是必填的";
			$err = true;
			//exit;
		} else if(!is_numeric(test_input($_POST["userno"]))){
			if ($_POST["userno"] == $admin_name) {
				$_POST["userno"] = $admin_no;
			}else{
				$usernoErr = "员工编号(公免号码)必须为数字";
				$err = true;
			}
		}
		if ($_POST['nexportextrainfo']) {
			$extrainfofileurl="http://".$_SERVER['HTTP_HOST']."/".dirname(trim($_SERVER['REQUEST_URI'],'/'))."/".$extrainfofile."?exporttolocal=1";
			echo "<script>location.href='".$extrainfofileurl."';</script>";
			post($extrainfofileurl,"{'exporttolocal':'1'}");
			die();
		}else{
			if (empty($_POST["password"])) {
				$passwordErr = "密码是必填的";
				$err = true;
				//exit;
			} else if (strlen(test_input($_POST["password"])) > 16){
				$passwordErr = "密码长度必须为小于17位";
				$err = true;
			}
		}
		if($_POST['login']  || $_POST['loginextrainfo']){
			if (!$err) {
				getcon();
				$passwordErr = checklogin();
			}
			getcon();
			printlogin();
			die("<span class='error'>{$usernoErr}<br>{$usernameErr}<br>{$passwordErr}<br>{$idnoErr}<br>{$confirmpasswordErr}<br>{$bankcardnoErr}<br>{$bankcardnameErr}<br>{$banknameErr}<br></span>");
		}else if($_POST['modify']){
			$staffinfo = array(
				'username' => '',
				'userno' => '',
				'password' => '',
				'idno' => '',
				'bankcardno' => '',
				'bankcardname' => '',
				'bankname' => '',
			);

			$staffinfo['userno'] = $_POST["userno"];
			$staffinfo['password'] = $_POST["password"];
			getcon();
			// echo "{$staffinfo[password]}<br>";
			$query = varifypassword($staffinfo['userno'], $staffinfo['password']);
			if($row = mysql_fetch_array($query)){
				// print_r($row);
			}else{
				printstaffinfo($staffinfo);
				die("<span class='error'>员工编号(公免号码)和旧密码不匹配,请重试</span>");
			}

			if (!empty($_POST["newpassword"])) {
				if (strcmp($_POST["newpassword"],$_POST["confirmpassword"]) != 0) {
					$confirmpasswordErr="两次密码不一致";
					$err = true;
				}else{
					$staffinfo['password'] = $_POST["confirmpassword"];
				}
			}
			if (!$err) {
				updateinfo($staffinfo,1);
			}
			printstaffinfo($staffinfo);
			die("<span class='error'>{$usernoErr}<br>{$usernameErr}<br>{$passwordErr}<br>{$idnoErr}<br>{$confirmpasswordErr}<br>{$bankcardnoErr}<br>{$bankcardnameErr}<br>{$banknameErr}<br></span>");
			//die("modify 密码是必填的");
		}else if($_POST['mysubmit']){
			$staffinfo = array(
				'username' => '',
				'userno' => '',
				'password' => '',
				'idno' => '',
				'bankcardno' => '',
				'bankcardname' => '',
				'bankname' => '',
			);
			if (empty($_POST["idno"])) {
				$idnoErr = "身份证号是必填的";
				$err = true;
			}else if(!isCreditNo($_POST["idno"])){ //if (strlen(test_input($_POST["idno"])) != 18) {
				//echo strlen(test_input($_POST["idno"]));
				$idnoErr = "身份证号不对";   //格式长度必须为18位
				$err = true;
			}
			$staffinfo['userno'] = $_POST["userno"];
			$staffinfo['idno'] = $_POST["idno"];
			if (empty($_POST["username"])) {
				$usernameErr = "姓名是必填的";
				$err = true;
			}
			if (empty($_POST["bankcardno"])) {
				$usernameErr = "银行卡号是必填的";
				$err = true;
			}else if (!preg_match("/^[0-9]+$/",$_POST["bankcardno"])) {
				$usernameErr = "银行卡号必须全是数字";
				$err = true;
			}
			if (empty($_POST["bankcardname"])) {
				$usernameErr = "银行卡户名是必填的";
				$err = true;
			}
			if (empty($_POST["bankname"])) {
				$usernameErr = "开户行是必填的";
				$err = true;
			}

			$staffinfo['bankcardno'] = $_POST["bankcardno"];
			$staffinfo['bankcardname'] = $_POST["bankcardname"];
			$staffinfo['bankname'] = $_POST["bankname"];

			$staffinfo['username'] = $_POST["username"];
			// $staffinfo['password'] = $_POST["password"];
			getcon();
			if (!$err) {
				updateinfo($staffinfo,0);
			}
			printstaffinfo($staffinfo);
			die("<span class='error'>{$usernoErr}<br>{$usernameErr}<br>{$passwordErr}<br>{$idnoErr}<br>{$confirmpasswordErr}<br>{$bankcardnoErr}<br>{$bankcardnameErr}<br>{$banknameErr}<br></span>");
			//die("modify 密码是必填的");
		}else if($_POST['export']){
			getcon();
			$userno = $_POST['userno'];
			if ($userno != "$admin_no") {
				$usernoErr = "非管理员不能进行导出操作";
				printlogin();
			}
			else{
				$userno = $_POST['userno'];
				$password = $_POST['password'];

				$query = varifypassword($admin_no, $_POST['password']);
				// echo "$query  $userno   $password<br>";
				if($row = mysql_fetch_array($query)){
					exporttoexcel();
				}
				else{
					echo "<span class='error'>登录失败，用户编号(公免号码)和密码不匹配</span><BR><BR>";
				}
			}
			die("<span class='error'>{$usernoErr}<br>{$usernameErr}<br>{$passwordErr}<br>{$idnoErr}<br>{$confirmpasswordErr}<br>{$bankcardnoErr}<br>{$bankcardnameErr}<br>{$banknameErr}<br></span>");
		}else if($_POST['upload']){
			$rs = null;
			$upfile=null;
			if ($_FILES['upfile']) {
				$rs = is_uploaded_file($_FILES['upfile']['tmp_name']);
				$upfile=$_FILES["upfile"];
			}else{
				$rs = is_uploaded_file($_POST['upfile']);
				$upfile=$_POST["upfile"];
			}
			getcon();
			printlogin();
			if($rs){
				$userno = $_POST['userno'];
				if ($userno != "$admin_no") {
					$usernoErr = "非管理员不能进行导出操作";
				}
				else{
					$userno = $_POST['userno'];
					$password = $_POST['password'];

					$query = varifypassword($admin_no, $_POST['password']);
					// echo "$query  $userno   $password<br>";
					if($row = mysql_fetch_array($query)){
						//获取数组里面的值
						$name=$upfile["name"];//上传文件的文件名
						$type=$upfile["type"];//上传文件的类型
						$size=$upfile["size"];//上传文件的大小
						$tmp_name=$upfile["tmp_name"];//上传文件的临时存放路径
						// print_r($upfile);
						$okType=false;
						//判断是否为图片
						switch ($type){
							case 'application/download':
							case 'application/kset':
							case 'application/vnd.ms-excel':$okType=true;                //
							break;
							case 'image/pjpeg':
							case 'image/jpeg':
							case 'image/gif':
							case 'image/png':$okType=false;
							break;
						}

						if($okType){
							/**
							* 0:文件上传成功<br/>
							* 1：超过了文件大小，在php.ini文件中设置<br/>
							* 2：超过了文件的大小MAX_FILE_SIZE选项指定的值<br/>
							* 3：文件只有部分被上传<br/>
							* 4：没有文件被上传<br/>
							* 5：上传文件大小为0
							*/
							$error=$upfile["error"];//上传后系统返回的值
							echo "<textarea";
							echo "================================================\r\n";
							echo "上传文件名称是：".$name."\r\n";
							echo "上传文件类型是：".$type."\r\n";
							echo "上传文件大小是：".$size."\r\n";
							echo "上传后系统返回的值是：".$error."\r\n";
							echo "上传文件的临时存放路径是：".$tmp_name."\r\n";

							echo "开始移动上传文件\r\n";
							//把上传的临时文件移动到default目录下面
							$destination="./up/".$stafffile;
							if(move_uploaded_file($tmp_name,$destination)){
								echo "================================================\r\n";
								echo "上传信息：\r\n";
								if($error==0){
									echo "\r\n文件上传成功啦！\r\n";
									echo "存放路径是：$destination \r\n";
								}elseif ($error==1){
									echo "超过了文件大小，在php.ini文件中设置\r\n";
								}elseif ($error==2){
									echo "超过了文件的大小MAX_FILE_SIZE选项指定的值\r\n";
								}elseif ($error==3){
									echo "文件只有部分被上传\r\n";
								}elseif ($error==4){
									echo "没有文件被上传\r\n";
								}else{
									echo "上传文件大小为0\r\n";
								}
							}else{
								echo "文件上传失败啦！\r\n";
							}
							echo "</textarea>";
							//(初始化)删除数据库overtime
							if($con == ''){
								$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
								if(!$con ){
									die('连接数据库失败: ' . mysql_error());
								}
							}
							$sqldropdb = "DROP TABLE IF EXISTS `{$table_users}`";
							mysql_query($sqldropdb);
							$sql = "UPDATE flags SET flagval=flase where flagname='{$table_users}init'";
							$queryres = mysql_query($sql);
					 		//set charset
							mysql_query("SET names $db_chractor");

							echo "<script>location.href='".$_SERVER['PHP_SELF']."?initusers=".$destination."';</script>";
							// <script>alert('更新数据库');
						}else{
							echo "<div style='position:absolute;left:100px'><label style='margin-right:400px;font-size:15px;color:red;height:30px'>请上传excel 97-2003格式文件</label></div>";
						}
					}
					else{
						echo "<span class='error'>登录失败，用户编号(公免号码)和密码不匹配</span><BR><BR>";
					}
				}
				die("<span class='error'>{$usernoErr}<br>{$usernameErr}<br>{$passwordErr}<br>{$idnoErr}<br>{$confirmpasswordErr}<br>{$bankcardnoErr}<br>{$bankcardnameErr}<br>{$banknameErr}<br></span>");
			}else if($_POST['upload']){
				die("<div style='position:absolute;left:100px'><h2 style='margin-right:480px;color:red;' >你没有选择文件</h2></div>");
			}
		}else if ($_POST['rconf']) { // 替换配置文件
			$rs = null;
			$upfile=null;
			// print_r($_POST);
			// die(print_r($_FILES));
			if ($_FILES['upconfig']) {
				$rs = is_uploaded_file($_FILES['upconfig']['tmp_name']);
				$upfile=$_FILES["upconfig"];
			}else{
				$rs = is_uploaded_file($_POST['upconfig']);
				$upfile=$_POST["upconfig"];
			}
			getcon();
			printstaffinfo($_POST);
			//获取数组里面的值
			$name=$upfile["name"];//上传文件的文件名
			$type=$upfile["type"];//上传文件的类型
			$size=$upfile["size"];//上传文件的大小
			$tmp_name=$upfile["tmp_name"];//上传文件的临时存放路径
			$error=$upfile["error"];//上传后系统返回的值
			echo "<textarea>";
			echo "================================================\r\n";
			echo "上传文件名称是：".$name."\r\n";
			echo "上传文件类型是：".$type."\r\n";
			echo "上传文件大小是：".$size."\r\n";
			echo "上传后系统返回的值是：".$error."\r\n";
			echo "上传文件的临时存放路径是：".$tmp_name."\r\n";

			echo "开始移动上传文件\r\n";
			//把上传的临时文件移动到default目录下面
			$utfname =iconv("utf-8","gb2312",$name);
			if (file_exists($destination)) {
	            unlink($destination);
	        }
			$destination=$utfname;
			// die($destination);
			if(move_uploaded_file($tmp_name,$destination)){
				echo "================================================\r\n";
				echo "上传信息：\r\n";
				if($error==0){
					echo "文件上传成功啦！\r\n";
					echo "存放路径是：$destination \r\n";
				}elseif ($error==1){
					echo "超过了文件大小，在php.ini文件中设置\r\n";
				}elseif ($error==2){
					echo "超过了文件的大小MAX_FILE_SIZE选项指定的值\r\n";
				}elseif ($error==3){
					echo "文件只有部分被上传\r\n";
				}elseif ($error==4){
					echo "没有文件被上传\r\n";
				}else{
					echo "上传文件大小为0\r\n";
				}
			}else{
				rename($tmp_name,$destination);
				if (!file_exists($destination)) {
					echo "文件上传失败，错误信息：".$_FILES['upconfig']['error']."<br>";
	        	}
			}
			echo "</textarea>";
			die();
		}
	}

	if($_SESSION['name'] && $_SESSION['password'])
	{
		getcon();
		$sql="select * from {$table_users} where userno='$_SESSION[name]' and  password='$_SESSION[password]'";
		$query=mysql_query($sql);
		if(mysql_fetch_array($query)){
			echo '你的用户已经登陆了';
			exit;
		}
	}
	
	getcon();
	printlogin();
	// if($_POST['submit1']){
	//	header("location:regist.php");
	// }
	mysql_close($con);

 	function post($url, $post_data = '', $timeout = 5){
	 	//curl
	 	$ch = curl_init();
	 	curl_setopt ($ch, CURLOPT_URL, $url);
	 	curl_setopt ($ch, CURLOPT_POST, 1);
	 	if($post_data != ''){
	 		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	 	}
	 	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	 	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	 	curl_setopt($ch, CURLOPT_HEADER, false);
	 	$file_contents = curl_exec($ch);
	 	curl_close($ch);
	 	return $file_contents;
 	}

	function getcon(){
		global $con;
		//连接数据库
		global $db_domain;
		global $db_name,$db_admin_name,$db_chractor;
		global $db_passwd;
		global $stafffile;
		global $table_validusers,$table_users;

		global $sheetname, $table_records;

		if ($con) {
			mysql_close($con);
		}
		$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
		if(!$con ){
			die('连接数据库失败Could not connect: ' . mysql_error());
		}
		//chuangjian shujuku
		$sqlcreatedb = "CREATE DATABASE IF NOT EXISTS `".$db_name."` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		if(mysql_query($sqlcreatedb, $con)){
			if($debug) echo "Database $db_name created<br>";
		}
		mysql_select_db($db_name, $con);
		mysql_query("set names $db_chractor");

		// 如果找不到则把user初始化标志置位
		$sqlGetCount= "select count(*) from {$sheetname[0]}{$table_records}";
		$result = mysql_query($sqlGetCount);
		if (!$result) {
			die("<br>" . __LINE__ ." ". __METHOD__ . " get count from {$sheetname[0]}{$table_records} error: " . mysql_error());
		}
		$counts=0;
		if($row = mysql_fetch_array($result)){
			$counts=$row[0];
		}
		if ($counts == 0) {
			inittable($stafffile);
		}else{
			$sql = "select flagval from flags where flagname='{$table_users}init'";
			$queryres = mysql_query($sql);
			// echo "getcon $queryres <br>";
			if (!$queryres) {
				// echo mysql_errno()."<br>";
				if (1146 == mysql_errno()) {     //1146   ER_NO_SUCH_TABLE
					inittable($stafffile);
				}
				else{
					die(mysql_errno().mysql_error());
				}
			}else if($row = mysql_fetch_array($queryres)){
				if ($row['flagval'] == '0') {
					// echo '<BR>';
					inittable($stafffile);
				}
			}else{
				inittable($stafffile);
			}
		}
		$sql = "select * from {$table_users}";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			inittable($stafffile);
		}

	}


	function test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		// echo "$data<br>";
		return $data;
	}

	function inittable($stafffile){
		global $admin_password;
		global $admin_no,$admin_name,$db_chractor;
		global $con,$db_name,$db_domain,$db_admin_name,$db_passwd,$sql_create_table_users;
		global $sheetname;
		global $table_validusers,$table_users;

		$objPHPExcel="";
		$file_types = explode(".",$stafffile);
		// print_r($file_types);
		$file_type = $file_types [count ( $file_types ) - 1];
		// echo "{$file_type}<br>";

		if (!file_exists($stafffile)) {
			die($stafffile."NOT 存在");
		}

		$suffix = strtolower ( $file_type );
		// echo "$suffix<br>";
		if ($suffix == "xls")
		{
			$objReader=PHPExcel_IOFactory::createReader('Excel5');//use excel2007 for 2007 format
		}
		else if ($suffix == "xlsx")
		{
			$objReader=PHPExcel_IOFactory::createReader('Excel2007');//use excel2007 for 2007 format
		}
		if(!isset($objPHPExcel)) 
			die("无法解析文件");

		####  创建database， tables。
		if (!$con) {
			$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
			if(!$con ){
				die('连接数据库失败Could not connect: ' . mysql_error());
			}
			//创建数据库overtime
			$sqlcreatedb = "CREATE DATABASE IF NOT EXISTS `".$db_name."` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
			if(mysql_query($sqlcreatedb, $con)){
				if($debug) echo "Database $db_name created<br>";
			}
		}
		mysql_select_db($db_name, $con);
		mysql_query("set names $db_chractor");
		mysql_query("DROP TABLE IF EXISTS {$table_users}");


		$queryres = mysql_query($sql_create_table_users);
		if (!$queryres) {
			die("__LINE__ create table {$table_users} error: ".mysql_error());
		}

		##### 插入管理员的数据
		$md5admin_password = md5(md5(md5(md5("$admin_password"))));
		// 如果存在则不替换
		$sql = "INSERT IGNORE INTO {$table_users}(`username`,`userno`,`password`,`idno`) values('".$admin_name."', '".$admin_no."','{$md5admin_password}','')";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("insert $admin_no into {$table_users} error: ".mysql_error());
		}
		##### 开始插入excel数据
		if (true) {
			initdb_overtime();
			create_db_with_excel("",$stafffile,"0",true);
		}else{
			$objPHPExcel=$objReader->load($stafffile);//$file_url即Excel文件的路径
			//获取工作表的数目
			$sheetCount = $objPHPExcel->getSheetCount();

			for ($i=0; $i < $sheetCount; $i++) {
				# 获取第i个工作表
				$sheet=$objPHPExcel->getSheet($i);
				// echo "$sheetname[$i] <br>";
				init_each_table($sheet,$sheetname[$i]);
			}
		}
		#### 写入初始化完成标志
		$sql = "create table IF NOT EXISTS flags(id int primary key auto_increment, flagname varchar(30) unique key NOT NULL, flagval boolean NOT NULL)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("create table flags error: ".mysql_error());
		}

		$sql = "REPLACE INTO  flags(`flagname`, `flagval`) values('{$table_users}init',true)";
		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("create table flags error: ".mysql_error());
		}
	}

	function init_each_table($sheet,$sheet_name){
		global $admin_password,$orig_password;
		global $admin_no;
		global $con;
		global $staffinfo_rowElem;
		global $table_validusers,$table_users;

		$highestRow=$sheet->getHighestRow();//取得总行数

		$highestColumn=$sheet->getHighestColumn();//取得总列数
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$Clos = $highestColumnIndex;
		// echo "$highestRow    $highestColumn   $highestColumnIndex<br> ";

		$password = md5(md5(md5(md5($orig_password))));
		// echo "{$admin_password}    {$md5admin_password}<br>";
		for ($i=1; $i < $highestRow; $i++) {
			for($k = 0,$j='A';$j<=$highestColumn;$j++,$k++)
			{
				//$str .=$objPHPExcel->getActiveSheet()->getCell("$n$j")->getValue()."\\";//读取单元格
				// echo "j=$j     k=$k<br>";
				$staffinfo_rowElem[$k] =$sheet->getCell("$j$i")->getCalculatedValue();//读取单元格
				// echo "$staffinfo_rowElem[$k]<br>";
			}
			$values .="('$staffinfo_rowElem[2]', '$staffinfo_rowElem[1]','{$password}','$staffinfo_rowElem[3]','$staffinfo_rowElem[4]','$staffinfo_rowElem[5]','$staffinfo_rowElem[6]','$sheet_name'),";
			// print_r($staffinfo_rowElem);
			// echo "<br>";
		}
		$values = rtrim($values, ","); //去掉最后一个,
		// echo $values;exit;
		// 如果存在则不替换，否则密码被替换了
		$sql = "INSERT IGNORE INTO {$table_users}(`username`,`userno`,`password`,`idno`,`bankcardno`,`bankcardname`,`bankname`,`sheet_name`) values ".$values;

		$queryres = mysql_query($sql);
		if (!$queryres) {
			die("insert '{$userno}' into {$table_users} error: ".mysql_error());
		}

		// die('提供的数据不是数字');
	}

	function fuc_resetpassword($idno,$password){
		global $con;
		global $db_name,$orig_password,$db_domain,
			   $db_admin_name,$db_passwd,$db_chractor;
		global $table_validusers,$table_users;

		if (!$con) {
			$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
			if(!$con ){
				die("<br>" . __LINE__ . __METHOD__ . ' 连接数据库失败Could not connect: ' . mysql_error());
			}
			if(mysql_select_db($db_name, $con)==false) die("error:".mysql_error());
			mysql_query("set names $db_chractor");
		}
		// 先看看是否有这个用户
		$getrecordscount  = "SELECT count(0) FROM {$table_users} where userno='{$idno}'";
		$result = mysql_query($getrecordscount);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . "  get the count of $idno error: " . mysql_error());
		}
		$count = 0;
		if(mysql_num_rows( $result)){
			$rs=mysql_fetch_array($result);
			//统计结果
			$count=$rs[0];
		}
		if (!$count) {
			die("没有这个员工，请检查员工编号".$idno."是否输入正确");
		}
		// 修改密码， MD5加密存储
		$md5password = md5(md5(md5(md5($password))));
		$sqlrest = "UPDATE {$table_users} SET password='{$md5password}' WHERE userno='{$idno}'";
		$result = mysql_query($sqlrest,$con);
		if ($result){
			die("重置员工".$idno."密码为".$password."成功!");
		}else{
			die("重置员工".$idno."密码为".$password."失败!");
		}
	}

	function updateinfo($staffinfo, $uppasswd){
		global $con;
		global $table_validusers,$table_users;

		$newpassword = md5(md5(md5(md5("{$staffinfo['password']}"))));
		if($uppasswd) {
			$sql = "UPDATE {$table_users} SET password='{$newpassword}' WHERE userno='{$staffinfo[userno]}'";
		}else {
			$sql = "UPDATE {$table_users} SET idno='{$staffinfo[idno]}',username='{$staffinfo[username]}',bankcardno='{$staffinfo[bankcardno]}',bankcardname='{$staffinfo[bankcardname]}',bankname='{$staffinfo[bankname]}' WHERE userno='{$staffinfo[userno]}'";
		}
		$res = mysql_query($sql,$con);
		// echo "$sql<br>$res<br>";
		if ($res) {
			if ($uppasswd) {
				die("修改密码完成,请记住你的信息:<br>
					你的员工编号(公免号码)是:<DD>&#9;{$staffinfo[userno]}<br>
					你的新密码是:<DD>&#9;{$staffinfo[password]}<br>");
			}else {
				die("修改完成,请记住你的信息:<DD>&#9;<br>
					你的员工编号(公免号码)是:<DD>&#9;{$staffinfo[userno]}<br>
					你的姓名是:<DD>&#9;{$staffinfo[username]}<br>
					你的身份证号是:<DD>&#9;{$staffinfo[idno]}<br>
					你的银行卡号是:<DD>&#9;{$staffinfo[bankcardno]}<br>
					你的银行卡户名是:<DD>&#9;{$staffinfo[bankcardname]}<br>
					你的开户行是:<DD>&#9;{$staffinfo[bankname]}<br>");
			}
		}else{
			print('更新数据信息失败,请重试<br>');
		}
	}

	function checklogin(){
		global $extrainfofile;
		
		//echo "{$_POST['userno']}   {$_POST['password']}<br>";
		$query = varifypassword($_POST['userno'], $_POST['password']);
		//echo "$query <br>";
		if($row = mysql_fetch_array($query)){
			// $_SESSION['userno'] = $_POST['userno'];
			// $_SESSION['password'] = $_POST['password'];
			// print_r($_SESSION);
			// print_r($row);
			if($_POST['loginextrainfo']) {
				// die($con);
				if (!$con) {
					getcon();
				}
				if(isvaliduser($_POST['userno']) == "0"){
					die("非法用户不能修改加班信息！");
				}
				$head="refresh:3;url=http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$extrainfofile."?employeeid={$_POST['userno']}";
				// echo "$head";
				header($head);
				echo '提交成功!<br>三秒后自动跳到加班统计页面......';
			}else{
				printstaffinfo($row);
			}
			die;
		}
		else{
			return "<span class='error'>登录失败，用户编号(公免号码)或密码错误</span><BR><BR>";
		}
	}


	function printlogin(){
		global $usernolength ;
		global $usernamelength;
		global $passwordlength;
		global $idnolength;
		global $bankcardnolength;
		global $bankcardnamelength;
		global $banknamelength;
		global $overtimefile;
		global $extrainfofile, $style_fillinfo;
		global $jqfile, $admin_no,$login_webtitle,$ico_url, $admin_name,$orig_password,$con;
		global $bt_fillinfo, $bt_fillextra, $bt_import, $bt_reset, $bt_export_staff, $bt_export_extra;
		global $table_validusers,$table_users;

		if (!$con) {
				getcon();
		}

		$sql_getvalidusers= "select * from {$table_validusers}";
		// writeinfo("userno ".$sql_getvalidusers);
		$res = mysql_query($sql_getvalidusers);
		if (!$res) {
			if (1146 == mysql_errno() ) {
				// print_r($_SERVER);
				$head="refresh:3;url=http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$extrainfofile;
				header($head);
				return;
				die("数据库没有初始化，请联系管理员初始化数据库。或者在浏览器中输入以下路径初始化：http://{$_SERVER['HTTP_HOST']}".dirname($_SERVER['PHP_SELF'])."/".$extrainfofile);
			}else{
					die("<br>" . __LINE__ . __METHOD__ . " get {$table_validusers} error: " . mysql_errno() . mysql_error());
			}
		}
		$users = "";
		while ($validuser = mysql_fetch_assoc($res)) {
			// print_r($validuser);
			$users .= "'{$validuser[employeeid]}',";
		}
		$users = rtrim($users,",");
		// die("$users <br>");

		echo "
			<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
			<link rel='shortcut icon' href='".$ico_url."' type='image/x-icon' />
			<title>".$login_webtitle."</title>
			<head>
			<script src='".$jqfile."'></script>
			<script src='staffinfo.js'></script>
			<link rel='stylesheet' type='text/css' href='overtime.css'>
			<SCRIPT>
				function exportextrainfotolocal(){
					alert('点击确定，开始导出excel到本地，请耐心等待');
					$.post('".$extrainfofile."',{'exporttolocal':'1'},function(data){location.href=data;});
				}
				var valid_users=[".$users."];
				var add = function(){
					setBodyProperty(true);

					var vno = document.getElementById('no');
					var vpas = document.getElementById('pas');
					var vup = document.getElementById('initusersdb');
					//alert(vno);
					addListener(vno, 'focus', function () {
							setbuttondisabled();
							document.getElementById('fillinfo').disabled=".($bt_fillinfo == "disabled"?"true":"false").";
					});

					addListener(vno, 'blur', function () {
						if (vno.value == '".$admin_name."' || vno.value == '".$admin_no."') {
							setbuttonenabled();
						}else if ((valid_users.indexOf(vno.value) != '-1')) {
							log2ex.style.display='inline';
						}else{
							log2ex.style.display='none';
						}
					});
					addListener(vpas, 'blur', function () {
						if (vno.value == '".$admin_name."' || vno.value == '".$admin_no."') {
							setbuttonenabled();
						}else if ((valid_users.indexOf(vno.value) != '-1')) {
							divrs.style.display='none';
							inputup.style.display='none';
							log2ex.style.display='inline';
						}else{
							setbuttondisabled();
							log2ex.style.display='none';
							document.getElementById('fillinfo').disabled=".($bt_fillinfo == "disabled"?"true":"false").";
						}
					});
				};
			</SCRIPT>
			<style>
			.error {color: #FF0000;}
			</style>
			</head>
			<body bgcolor='#dbdbf8' onLoad='add()'>
			<div class='login prompt'><span >*</span> 必填&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
			<div class='login prompt'><span >*</span> '编号'这项乡话员工请填公免号码,其他员工请填员工编号</div>
			<form enctype='multipart/form-data'  method='post' action=''>
			<div class='login'><b>编号:</b><input id='no' type='text' size=20 maxlength=$usernolength name='userno' value='".$_POST[userno]."'/><span class='error'>* 员工编号(公免号码)$usernoErr</span></div>
			<div class='login'><b>密码:</b><input id='pas' type='password' size=20 maxlength=$passwordlength name='password' value='".$_POST[password]."'/><span class='error'>* $passwordErr</span></div>
			<div class='login divbt' ><input class='inputbt' id='fillinfo' type='submit' style='display:".$style_fillinfo.";' name='login' value='填写员工信息' ".$bt_fillinfo."/><input class='inputbt' id='log2ex' style='display:inline;' type='submit' name='loginextrainfo' value='填写加班信息' ".$bt_fillextra."/></div>
			<div class='login divbt' ><input class='inputbt' id='ex' type='submit' style='display:none;'  name='export' value='导出员工信息' style='display:inline;' ".$bt_export_staff."/><input class='inputbt' id='iexportextrainfo' style='display:none;' type='button' name='nexportextrainfo' value='导出加班信息' onClick='exportextrainfotolocal()' ".$bt_export_extra."/></div>
			<div id='divrs' class='login divbt'>
			<input class='fileinputbt' id='inputup' style='font-size:15px;display:none' type='file' name='upfile' onchange='CheckFile(this);'".$bt_import."/>
			<input class='inputbt resetstaffinfo' id='initusersdb' type='submit' style='display:none;color:orange;' name='upload' value='初始化员工信息' ".$bt_import." '/></div>
			</form>
			</body>
		";
	}
	// onClick='OnResetClick()
	function printstaffinfo($row){
		global $usernolength ;
		global$usernamelength;
		global$passwordlength;
		global$idnolength, $bt_reset, $orig_password, $admin_no,$jqfile;
		global$bankcardnolength;
		global$bankcardnamelength;
		global$banknamelength, $staffinfo_webtitle, $bankArr;

		$select_elements = "<select name='some'>";
		foreach($bankArr as $value => $showstr) {
			$select = $value == $some ? ' selected' : '';
			$select_elements = $select_elements."<option value=\"$value\" $select>$showstr</option>";
		}
		$select_elements = $select_elements."</select>";

		// error_log(print_r($row,true),3,"./err.txt","");
		// writeinfo($row['userno'] . "/////////" .$admin_no);
		echo "
			<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
			<link rel='shortcut icon' href='".$ico_url."' type='image/x-icon' />
			<head>
				<link rel='stylesheet' type='text/css' href='overtime.css'>
				<script src='".$jqfile."'></script>
				<script src='staffinfo.js'></script>
				<title>".$staffinfo_webtitle."</title>
				<style>
					.error {color: #FF0000;}
					/* tooltip */
					#tooltip{
					position:absolute;
					border:1px solid #333;
					background:#f7f5d1;
					padding:1px;
					color:#333;
					display:none;
					}
				</style>
				<script>
					/*
					$(function(){
						var x = 10;
						var y = 20;
						$('a.tooltip').mouseover(function(e){
						this.myTitle = this.title;
						this.title = '';
						var tooltip = '<div id='tooltip'>'+ this.myTitle +'<\/div>'; //创建 div 元素
						$('body').append(tooltip);//把它追加到文档中
						$('#tooltip')
						.css({
						'top': (e.pageY+y) + 'px',
						'left': (e.pageX+x)  + 'px'
						}).show('fast'); //设置x坐标和y坐标，并且显示
						}).mouseout(function(){
						this.title = this.myTitle;
						$('#tooltip').remove();   //移除
						}).mousemove(function(e){
						$('#tooltip')
						.css({
						'top': (e.pageY+y) + 'px',
						'left': (e.pageX+x)  + 'px'
						});
						});
					});
					*/
					function addListener(element, e, fn) {
						if (element.addEventListener) {
							element.addEventListener(e, fn, false);
						} else {
							element.attachEvent('on' + e, fn);
						}
					}

					var add = function(){
						/*var valb = document.getElementsByName('username')[0];
						var valc = document.getElementsByName('idno')[0];
						addListener(valb, 'click', function () {
							b.style.display = 'inline';
						})
						addListener(valb, 'blur', function () {
							b.style.display = 'none';
						})
						addListener(valc, 'click', function () {
							c.style.display = 'inline';
						})
						addListener(valc, 'blur', function () {
							c.style.display = 'none';
						})
						addListener(vald, 'blur', function () {
							d.style.display = 'none';
						})
						addListener(vald, 'click', function () {
							d.style.display = 'inline';
						})
						*/

						var valc = document.getElementsByName('idno')[0];
						var vald = document.getElementsByName('password')[0];
						var vale = document.getElementsByName('newpassword')[0];
						var valf = document.getElementsByName('confirmpassword')[0];
						var valg = document.getElementsByName('modify')[0];

						var valh = document.getElementsByName('bankcardno')[0];
						var vali = document.getElementsByName('bankcardname')[0];
						var valj = document.getElementsByName('bankname')[0];
						var valk = document.getElementsByName('mysubmit')[0];

						addListener(valc, 'focus', function () {
							c.style.display = 'inline';
						})
						addListener(valc, 'blur', function () {
							c.style.display = 'none';
						})
						addListener(vale, 'focus', function () {
							e.style.display = 'inline';
						})
						addListener(vale, 'blur', function () {
							e.style.display = 'none';
						})
						addListener(valf, 'focus', function () {
							f.style.display = 'inline';
						})
						addListener(valf, 'blur', function () {
							if (vale.value != valf.value){
								/*alert(vale.value);
								alert(valf.value);*/
								f.innerHTML = '两次密码不一致';
								f.style.display = 'inline';
							}
							else
								f.style.display = 'none';
						})
						addListener(vald, 'focus', function () {
							d.innerHTML = '*';
							d.style.display = 'inline';
						})
						addListener(valg, 'focus', function () {
							if(!vald.value){
									d.innerHTML = '* 密码不能为空';
									d.style.display = 'inline';
							}
						})
						addListener(valh, 'blur', function () {
							h.style.display = 'none';
						})
						addListener(vali, 'blur', function () {
							i.style.display = 'none';
						})
						addListener(valj, 'blur', function () {
							j.style.display = 'none';
						})
						addListener(valh, 'focus', function () {
							if(!valh.value){
									h.innerHTML = ' 银行卡号不能为空';
									h.style.display = 'inline';
							}
						})
						addListener(vali, 'focus', function () {
							if(!vali.value){
									i.innerHTML = ' 银行户名不能为空';
									i.style.display = 'inline';
							}
						})
						addListener(valj, 'focus', function () {
							if(!valj.value){
									j.innerHTML = ' 开户行不能为空';
									j.style.display = 'inline';
							}
						})
						addListener(valk, 'focus', function () {
							if(!valh.value){
									h.innerHTML = ' 银行卡号不能为空';
									h.style.display = 'inline';
							}
							if(!vali.value){
									i.innerHTML = ' 银行户名不能为空';
									i.style.display = 'inline';
							}
							if(!valj.value){
									j.innerHTML = ' 开户行不能为空';
									j.style.display = 'inline';
							}
						})
					};
					/*
					function show(){
						document.getElementById('passwordprompt').style.display='密码长度必须小于17位!';
					}
					function hide(){
						document.getElementById('passwordprompt').style.display='none';
					}
					*/
				</script>
			</head>
			<body  onLoad='add()'>
				<b>员工信息确认</b><br><br>
				<a><span class='error'>*</span> 必填&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a><br>
				<form enctype='multipart/form-data' method='post' >
					<div>
						<label for='userno' class='staffinfo'>员工编号:</label>
						<input type='text' maxlength=$usernolength name='userno' id='userno' readonly='readonly' value='".$row[userno]."' tabindex='1' />
						<span class='error' id='a'>*</span>
					</div>
					<div>
						<label for='username' class='staffinfo'>姓名:</label>
						<input type='text' maxlength=$usernamelength name='username' id='username' value='".$row[username]."' tabindex='1' />
						<span class='error' id='b'>*</span>
					</div>
					<div>
						<label for='idno' class='staffinfo'>身份证号:</label>
						<input type='text' maxlength=$idnolength name='idno' id='idno' value='".$row[idno]."' tabindex='1' />
						<span class='error'>*</span><span class='error' id='c' style='display:none;'>身份证号不能为空</span>
					</div>
					<div>
						<label for='bankcardno' class='staffinfo'>银行卡号:</label>
						<input type='text' maxlength=$bankcardnolength name='bankcardno' id='bankcardno' value='".$row[bankcardno]."' tabindex='1' />
						<span class='error'>*</span><span class='error' id='h'></span>
					</div>
					<div>
						<label for='bankcardname' class='staffinfo'>银行户名:</label>
						<input type='text' maxlength=$bankcardnamelength name='bankcardname' id='bankcardname' value='".$row[bankcardname]."' tabindex='1' />
						<span class='error'>*</span><span class='error' id='i'></span>
					</div>
					<div>
						<label for='bankname' class='staffinfo'>开户行:</label>
						<input type='text' maxlength=$banknamelength name='bankname' id='bankname' value='".$row[bankname]."' tabindex='1' />
						<span class='error'>*</span><span class='error' id='j'></span>".$select_elements."
					</div>
					<div>
						<input class='inputbtinfo' type='submit' name='mysubmit' value='提交' focus='addListener()'/>
					</div>
					<br>
					<br>
					<br>
					<br>
					<b>修改密码</b>
					<br>
					<br>
					<div>
						<label for='password' class='staffinfo'>密码:</label>
						<input type='password' maxlength=$passwordlength name='password' id='password' value='' tabindex='1'/>
						<span class='error' id='d'>*</span>
					</div>
					<div>
						<label for='newpassword' class='staffinfo'>新密码:</label>
						<input type='password' maxlength=$passwordlength name='newpassword' id='newpassword' value='".$row[newpassword]."' tabindex='1' focus='addListener()'/>
						<span id='e' class='error' style='display: none'>密码长度必须小于17位!</span>
					</div>
					<div>
						<label for='confirmpassword' class='staffinfo'>确认密码:</label>
						<input type='password' maxlength=$passwordlength name='confirmpassword' id='confirmpassword' value='".$row[confirmpassword]."' tabindex='1' focus='addListener()'/>$confirmpasswordErr
						<span id='f' class='error' style='display: none'>密码长度必须小于17位!</span><!-- <?php echo $confirmpasswordErr;?> -->
					</div>
					<!--
					<div>
						<h4>Radio Button Choice</h4>
						<label for='radio-choice-1'>Choice 1</label>
						<input type='radio' name='radio-choice-1' id='radio-choice-1' tabindex='2' value='choice-1' />

						<label for='radio-choice-2'>Choice 2</label>
						<input type='radio' name='radio-choice-2' id='radio-choice-2' tabindex='3' value='choice-2' />
					</div>

					<div>
						<label for='select-choice'>Select Dropdown Choice:</label>
						<select name='select-choice' id='select-choice'>
							<option value='Choice 1'>Choice 1</option>
							<option value='Choice 2'>Choice 2</option>
							<option value='Choice 3'>Choice 3</option>
						</select>
					</div>

					<div>
						<label for='textarea'>Textarea:</label>
						<textarea cols='40' rows='8' name='textarea' id='textarea'></textarea>
					</div>

					<div>
						<label for='checkbox'>Checkbox:</label>
						<input type='checkbox' name='checkbox' id='checkbox' />
					</div>
					-->
					<div>
						<input class='inputbtinfo' type='submit' name='modify' value='修改' focus='addListener()'/>
					</div>
					".
					($row['userno'] == $admin_no?
					"<div>
						<input class='inputbtinfo cresetpass' id='resetpassword' style='display:inline;' type='button' value='重置员工密码' ".$bt_reset." onClick=\"js_resetpass(".$orig_password.")\"/>
					</div>
					<div id='divupconfig'>
					<input class='cupconfig fileinputbt' style='display:inline;height:30px' id='inputupconfig' type='file' name='upconfig' />
					<!-- onchange='uploadFile(\"inputupconfig\");' -->
					<input class='replaceconfigbt' type='submit' value='替换配置' name='rconf'/>
					</div>
					" : "" )."
				</form>
			</body>";
	}


	function exporttoexcel(){
		global $con;
		global $sheetname;
		set_time_limit(0); //脚本不超时
		//生成表格
		//新建
		$PHPExcel = new PHPExcel();
		//$objWriter =new PHPExcel_Writer_Excel5($PHPExcel);
		//设置文档基本属性
		$objProps=$PHPExcel->getProperties();
		$objProps->setCreator("yangxin")->setLastModifiedBy("yangxin")
					->setTitle("staffinfo")
					->setSubject("overtime,chinamobile")
					->setDescription("to stastics the overtime times of offers in chinamobile of Chnagede")
					->setKeywords("staffinfo")
					->setCategory("staffinfo");
		#print_r($objProps);
		#echo "string";


		getcon();
		#####   开始写入一个表

		// $PHPExcel->setActiveSheetIndex(0);
		// $objActSheet=$PHPExcel->getActiveSheet();
		// print_r($sheetname);
		for ($i=0; $i < 3; $i++){
			export_sheet($PHPExcel,$sheetname[$i]);
		}

		mysql_close($con);

		//设置导出文件名
		$outputFileName = 'staff';
		$date = date("Y-m-d_H:i:s",time());
		$outputFileName .= "_{$date}.xls";
		$outputFileName = iconv("utf-8", "gb2312", $outputFileName);

		//脚本方式运行，保存在当前目录
		$objWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel5');
		//到文件通过文件路径再用Ajax无刷新页面
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Type:application/force-download");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header("Content-Disposition:attachment;filename={$outputFileName}");
		header("Content-Transfer-Encoding:binary");
		header("Last-Modified:".gmdate("D,d-M-Y H:i:s")."GMT");
		header("Cache-Control:must-revalidate,post-check=0,pre-check=0,max-age=0");
		header("Pragma:no-cache");
		//到文件
		// $objWriter->save($outputFileName);
		$objWriter->save("php://output");
		// die("导出为  ".dirname(__FILE__)."/".$outputFileName." 成功!");
		exit;
	}

	function export_sheet($PHPExcel,$sheet_name){
		global $sheetname;
		global $sheetname_CN;
		global $staffinfo_rowElem;
		global $table_users;
		//创建一个新的工作空间(sheet)
		if ($sheet_name != 'listed') {
			$PHPExcel->createSheet();
		}
		$key = array_search($sheet_name, $sheetname);
		// echo "$sheet_name key is $key ....<br>sheetname_CN $sheetname_CN[$sheet_name]...";print_r($sheetname_CN);exit;
		$PHPExcel->setactivesheetindex($key);

		// 写入表头
		$getstaff  = "SELECT * FROM {$table_users} where userno='员工编号'";
		$result = mysql_query($getstaff);
		if (!$result) {
			die("get staff eror: " . mysql_error());
		}
		$rowno = 1;
		$clonum = count($staffinfo_rowElem) - 1; //列数， 去掉最后一列表示是哪个sheet的数据
		while ($staff = mysql_fetch_assoc($result)) {
			//设值
			// print_r($staff);exit;
			// 如果是admin则跳过，继续往下
			if ($staff['username'] == 'admin') {
				continue;
			}
			$colstr = 'A';
			for ($i=1; $i < $clonum; $i++) {
				$colindex = $colstr.$rowno;
				// $PHPExcel->getActiveSheet()->getCell($colindex)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
				// $PHPExcel->getActiveSheet()->setCellValue($colindex ,$staff[$staffinfo_rowElem[$i]]);
				$PHPExcel->getActiveSheet()->setCellValueExplicit($colindex ,$staff[$staffinfo_rowElem[$i]],PHPExcel_Cell_DataType::TYPE_STRING);
				$colstr++;
			}
			$rowno++;
		}

		//写入多行数据
		$getstaff  = "SELECT * FROM {$table_users} where sheet_name='".$sheet_name."' and userno!='员工编号'";
		$result = mysql_query($getstaff);
		if (!$result) {
			die("get staff eror: " . mysql_error());
		}

		$clonum = count($staffinfo_rowElem) - 1; //列数， 去掉最后一列表示是哪个sheet的数据
		while ($staff = mysql_fetch_assoc($result)) {
			//设值
			// print_r($staff);exit;
			// 如果是admin则跳过，继续往下
			if ($staff['username'] == 'admin') {
				continue;
			}
			$colstr = 'A';
			for ($i=1; $i < $clonum; $i++) {
				$colindex = $colstr.$rowno;
				$PHPExcel->getActiveSheet()->setCellValueExplicit($colindex ,$staff[$staffinfo_rowElem[$i]],PHPExcel_Cell_DataType::TYPE_STRING);
				$colstr++;
			}
			$rowno++;
		}
		//重命名表
		$PHPExcel->getActiveSheet()->setTitle($sheetname_CN[$sheet_name]);
	}

	function isCreditNo($vStr)
	{
		$vCity = array(
			'11','12','13','14','15','21','22',
			'23','31','32','33','34','35','36',
			'37','41','42','43','44','45','46',
			'50','51','52','53','54','61','62',
			'63','64','65','71','81','82','91'
			);

		if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;

		if (!in_array(substr($vStr, 0, 2), $vCity)) return false;

		$vStr = preg_replace('/[xX]$/i', 'a', $vStr);
		$vLength = strlen($vStr);

		if ($vLength == 18)
		{
			$vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
		} else {
			$vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
		}

		if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
		if ($vLength == 18)
		{
			$vSum = 0;

			for ($i = 17 ; $i >= 0 ; $i--)
			{
				$vSubStr = substr($vStr, 17 - $i, 1);
				$vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
			}

			if($vSum % 11 != 1) return false;
		}

		return true;
	}

	function md5_encrypt($plain_text, $password, $iv_len = 16){
		$plain_text .= "\x85";
		$n = strlen($plain_text);
		if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
		$i = 0;
		$enc_text = get_rnd_iv($iv_len);
		$iv = substr($password ^ $enc_text, 0, 512);
		while ($i < $n) {
			$block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
			$enc_text .= $block;
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return base64_encode($enc_text);
	}

	function md5_decrypt($enc_text, $password, $iv_len = 16){
		$enc_text = base64_decode($enc_text);
		$n = strlen($enc_text);
		$i = $iv_len;
		$plain_text = '';
		$iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
		while ($i < $n) {
			$block = substr($enc_text, $i, 16);
			$plain_text .= $block ^ pack('H*', md5($iv));
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return preg_replace('/\\x85\\x00*$/', '', $plain_text);
	}

	function get_rnd_iv($iv_len){
		$iv = '';
		while ($iv_len-- > 0) {
			$iv .= chr(mt_rand() & 0xff);
		}
		//echo "$iv <br>";
		return $iv;
	}

	function testmd5()
	{
		$plain_text = 'very secret string is abc d e f gh ';
		//$password = 'very secret password';
		$password = '~!@#$%^&*()<>:";\'\[\{\}\]';
		print "plain text is: [${plain_text}]<br />\n";
		print "password is: [${password}]<br />\n";

		$enc_text = md5_encrypt($plain_text, $password);
		print "encrypted text is: [${enc_text}]<br />\n";

		$plain_text2 = md5_decrypt($enc_text, $password);
		print "decrypted text is: [${plain_text2}]<br />\n";
		exit;
	}

?>

