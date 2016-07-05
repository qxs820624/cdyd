<?php
	error_reporting(E_ERROR);
	header("Content-Type:text/html;charset=utf-8"); //tell the php that the charset is utf-8
	header ("Cache-Control: no-store, no-cache,must-revalidate ");
	header('Pragma:no-cache');
	header("Expires:0");

	######### 需要修改的配置
	$holidaysnum = 6;     	// 假期的天数 [需要修改为overtime.xls中假期的天数] 例如：3（元旦）+7（春节）+3（清明）
	$holidaynumber = 2;	   	// 假期数目 元旦、春节、清明共3个假期
	$numperpage = 99;      	// 每次加载的记录数以及第一次显示的记录数

	$table_seq = "20160501";				// 数据表序号， 每次修改后必须加1
	$db_domain = "127.0.0.1";		// 数据库的IP
	$db_admin_name = "root";		//数据库管理员名称
	$db_passwd = "";				//数据库密码
	$db_name="overtime";			//数据库的名称

	$db_chractor = "UTF8";
	// $db_chractor = "GBK";

	// $db_domain = "121.42.14.102";			// 数据库的IP
	// $db_admin_name = "qdm106869534";		//数据库管理员名称
	// $db_passwd = "qxs828415";				//数据库密码
	// $db_name="qdm106869534_db";				//数据库的名称

	######### 需要修改的配置结束 ############
	$logfile = "err".$table_seq.".txt";

	$charset = "GB2312"; //GB2312 UTF-8 字符编码
	$debug = false;
	$jqfile = "jquery-1.11.3.min.js";
	$ico_url = "ico/chinamobile_64X64.ico";  //网站logo
	$overtime_webtitle = "常德移动加班时间统计"; //加班信息的网站标题
	$login_webtitle = "常德移动员工登陆"; //登陆的网站标题
	$staffinfo_webtitle = "常德移动员工信息"; //填写员工信息的网站标题
	// 普通用户是否可以保存到本地
	$save_local = false; //true 表示普通用户可以保存到本地， false 表示普通用户不可以保存到本地
	

	########### 员工登录界面 ###########
	// 是否可以填写员工信息
	$style_fillinfo = "inline";    		// 填写员工信息的样式 不显示就使用 "none" ，显示就使用"inline"
	$bt_fillinfo = "";    			// 填写员工信息 禁用就使用 "disabled" ，  使能就留为空格或者空 ""
	$bt_fillextra = "";    			// 填写加班信息 禁用就使用 "disabled" ，  使能就留为空格或者空 ""
	$bt_export_staff = "";  		// 导出员工信息 禁用就使用 "disabled" ，  使能就留为空格或者空 ""
	$bt_export_extra = "";  		// 导出加班信息 禁用就使用 "disabled" ，  使能就留为空格或者空 ""
	$bt_import = "disabled";    		// 初始化员工信息 禁用就使用 "disabled" ，  使能就留为空格或者空 ""
	$bt_reset = "";    			// 重置密码 禁用就使用 "disabled" ，  使能就留为空格或者空 ""

	##############  数据库相关

	if ($numperpage < 30) {
		$numperpage = 30;
	}
	//数据库模糊查询还是精确查询
	$uselike = true;   //true表示模糊查询，false表示精确查询，模糊的意思是如果部门中包含“渠道中心”，则会列出所有的部门名称中有“渠道中心”的部门，包括渠道中心（城区）、渠道中心（鼎城）、渠道中心（德山）等
	//是否显示所有的加班数据
	$show_all_info = false; //true 表示显示所有，不管是什么部门的加班数据全部显示出来

	//连接数据库
	$con = '';
	
	$table_remark = "remark".$table_seq;
	$table_firstline = "firstline".$table_seq;
	$table_secondline = "secondline".$table_seq;
	$table_records = "records".$table_seq;
	$table_users = "users";
	$table_validusers = "validusers".$table_seq;

	$timezone="Asia/Shanghai";		//时区


	//管理员登陆参数
	$admin_no = '36270000';			//管理员编号
	$admin_name = 'admin';			//管理员名称
	$admin_password = 'qwe!@#';		//管理员默认密码
	$orig_password = '123456';		//员工默认登陆密码

	// 创建用户表的sql语句
	$sql_create_table_users = "create table IF NOT EXISTS $table_users(id int(11) primary key NOT NULL auto_increment,username varchar(40) NOT NULL,userno varchar(20) NOT NULL,password varchar(80) NOT NULL,idno varchar(20),bankcardno varchar(20) ,bankcardname varchar(20),bankname varchar(256),sheet_name  varchar(256),UNIQUE(`userno`))";

	#############  页面跳转网页设置
	$staffinfofile="staffinfo.php";
	$extrainfofile="extrainfo.php";

	############# 初始化excel表格名称
	$stafffile = "staff20160501.xls";
	$overtimefile = "overtime20160501.xls";

	############## 员工信息表格设置
	$usernolength = 11;
	$usernamelength = 4;
	$passwordlength = 17;
	$idnolength = 18;
	$bankcardnolength=19;
	$bankcardnamelength=20;
	$banknamelength=20;

	############## 加班信息表

	$sum_cloumn = 2;       	// 假期和的列数 最后两列

	$remark_cloumn = array(
		'1' => 'remarks',
		'2' => 'remarkslen'
	);

	#####    第一行数据列名称
	//假期
	$firstline_cloumn = array(
		'reason',
		'reason_length',
	);
	for($i = 1; $i <= $holidaynumber; $i++) {
		array_push($firstline_cloumn, "holiday".$i);
		array_push($firstline_cloumn, "holiday".$i."len");
	};
	array_push($firstline_cloumn, "sum4yellow");
	array_push($firstline_cloumn, "sum4red");

	###### 天数 和 记录数
	$days = array();
	$records = array();
	for($i = 1; $i <= $holidaysnum; $i++) {
		array_push($days, "day".$i);
		array_push($records, "records".$i);
	};

	#####   第二行列名称
	//上市员工表 [需要修改为overtime.xls中第三行假期的列数] 下同
	$listed_sencondline_column_base = array(
		'serialno',
		'department',
		'employeeid',
		'employeename',
		'employeebackid',
		'classify',
	);
	$listed_sencondline_column = array_merge($listed_sencondline_column_base,$days);
	//劳务派遣员工表
	$labor_sencondline_column_base = array(
		'serialno',
		'department',
		'employeeid',
		'phone',
		'employeename',
		'onjob',
	);
	$labor_sencondline_column = array_merge($labor_sencondline_column_base,$days);

	//乡话员工表
	$dialect_sencondline_column_base = array(
		'serialno',
		'department',
		'employeename',
		'publicnumber',
	);
	$dialect_sencondline_column = array_merge($dialect_sencondline_column_base,$days);

	$sencondline_column = array(
		'listed' => $listed_sencondline_column,
		'labor' => $labor_sencondline_column,
		'dialect' => $dialect_sencondline_column,
	);

	$listed_fix_cloumn = count($listed_sencondline_column_base);      // 上市员工固定列数
	$labor_fix_cloumn = count($labor_sencondline_column_base);       // 劳务派遣员工固定列数
	$dialect_fix_cloumn = count($dialect_sencondline_column_base);     // 乡话员工固定列数

	$fix_cloumn = array(
		'0' => $listed_fix_cloumn,
		'1' => $labor_fix_cloumn,
		'2' => $dialect_fix_cloumn,
	);

	#### 这些员工的部门不在上市员工中， 需要查询的话找不到部门，所以特意列出来，但是
	$id_not_in_listed = array('0' =>array(	'idname'=>'publicnumber',
										'id' => '13975686565',
										'tab' => 'dialect',),
							'1' =>  array(	'idname'=>'publicnumber',
										'id' => '13908410833',
										'tab' => 'dialect',
									)
							);  //这些id对应的部门在上市员工中没有相应的部门

	####    加班记录表
	$record_sum_array = array("yellowrecords","redrecords");
	//上市员工记录表[需要修改为overtime.xls中第四行至最后一行假期的列数] 下同
	$overtime_listedrecords = array_merge_recursive($listed_sencondline_column_base, $records, $record_sum_array);
	//劳务派遣员工记录表
	$overtime_laborrecords = array_merge_recursive($labor_sencondline_column_base, $records, $record_sum_array);
	//乡话员工记录表
	$overtime_dialectrecords = array_merge_recursive($dialect_sencondline_column_base, $records, $record_sum_array);

	$overtime_records_cloumn = array(
		'listed' => $overtime_listedrecords,
		'labor' => $overtime_laborrecords,
		'dialect' => $overtime_dialectrecords
		);

	########### 员工信息表
	$staffinfo_rowElem = array(
		'id',
		'userno',
		'username',
		'idno',
		'bankcardno',
		'bankcardname' ,
		'bankname',
		'sheet_name',
	);

	// 表头
	$staffinfo_headline = "('姓名','员工编号','密码','身份证号码','银行卡号','银行卡户名','开户行','表名')";

	############## excel表 sheet名称
	$sheetcount = 3; //excel中sheet的数目，如果有变换，数目需要修改
	//表名
	$listed_CN="上市员工";
	$labor_CN="劳务派遣制员工";
	$dialect_CN="乡话员工";

	/*-----------转码-----------*/
	// $listed_CN=iconv("gb2312","utf-8",$listed_CN);
	// $labor_CN=iconv("gb2312","utf-8",$labor_CN);
	// $dialect_CN=iconv("gb2312","utf-8",$dialect_CN);

	$sheetname  = array("listed",   		// 上市公司员工
						"labor",    		// 劳务派遣员工
						"dialect", );  	//乡话员工
	$sheetname_CN  = array('listed' => "$listed_CN",	// 上市公司员工
						'labor' => "$labor_CN", 		// 劳务派遣员工
						'dialect' => "$dialect_CN",); 	//乡话员工

	// [需要修改为实际的编号、姓名所在的列序号，编号从0开始]
	$userinfo_column  = array("listed" => array(2,3),   		// 上市公司员工 编号、姓名所在的列，从0开始
						"labor" => array(2,4),    			// 劳务派遣员工 编号、姓名所在的列，从0开始
						"dialect" => array(3,2) );  		//乡话员工 编号、姓名所在的列，从0开始

	#############  SQL 语句------创建加班信息table
	// 第一行table [需要修改为实际的假期数目]
	//holidays的格式为1970-01-01；1970-01-02；1970-01-03；1970-04-05；1970-04-06；1970-04-07；1970-05-01；1970-05-02；1970-05-03；1970-10-01；1970-10-02；1970-10-03；1970-10-04；1970-10-05；1970-10-06；1970-10-07；
	for($i = 1; $i <= $holidaynumber; $i++){
		$sql_holiday = $sql_holiday . " `holiday{$i}` varchar(200) NOT NULL, `holiday{$i}len` int(11),";
	}

	$create_listed_firstline = "CREATE TABLE IF NOT EXISTS `listed{$table_firstline}`(`id` int(11) NOT NULL primary key auto_increment, `reason` varchar(40) NOT NULL,`reason_length` int(4)," . $sql_holiday . "`sum4yellow` varchar(20) NOT NULL,`sum4red` varchar(20) NOT NULL,UNIQUE(`reason`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";  //`holidaysnum` int(4), `sumofyellowlen` int(4), `sumofredlen` int(4),
	$create_labor_firstline = "CREATE TABLE IF NOT EXISTS `labor{$table_firstline}`(`id` int(11) NOT NULL primary key auto_increment, `reason` varchar(40) NOT NULL,`reason_length` int(4)," . $sql_holiday . "`sum4yellow` varchar(20) NOT NULL,`sum4red` varchar(20) NOT NULL,UNIQUE(`reason`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	$create_dialect_firstline = "CREATE TABLE IF NOT EXISTS `dialect{$table_firstline}`(`id` int(11) NOT NULL primary key auto_increment, `reason` varchar(40) NOT NULL,`reason_length` int(4)," . $sql_holiday . "`sum4yellow` varchar(20) NOT NULL,`sum4red` varchar(20) NOT NULL,UNIQUE(`reason`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	// echo "create_listed_firstline $create_listed_firstline<BR><BR>";
	// echo "create_labor_firstline $create_labor_firstline<BR><BR>";
	// echo "create_dialect_firstline $create_dialect_firstline<BR><BR>";

	$create_firstline = array(
		"listed" => $create_listed_firstline,
		"labor" => $create_labor_firstline,
		"dialect" => $create_dialect_firstline,
		);

	// 第二行 [需要修改为实际的days]
	for($i = 1; $i <= $holidaysnum; $i++){
		$sql_day = $sql_day . " `day{$i}` varchar(30) NOT NULL,";
	}

	$create_listed_secondline = "CREATE TABLE IF NOT EXISTS `listed{$table_secondline}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10) NOT NULL,`department` varchar(40) NOT NULL,`employeeid` varchar(20) NOT NULL , `employeename` varchar(20) NOT NULL, `employeebackid` varchar(20) NOT NULL,`classify` varchar(30) NOT NULL," . $sql_day . " yellowdays varchar(100),reddays varchar(100))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	$create_labor_secondline = "CREATE TABLE IF NOT EXISTS `labor{$table_secondline}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10) NOT NULL,`department` varchar(40) NOT NULL,`employeeid` varchar(20) NOT NULL , `phone` varchar(20) NOT NULL,`employeename` varchar(20) NOT NULL, `onjob` varchar(30) NOT NULL," . $sql_day . " yellowdays varchar(100),reddays varchar(100))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	$create_dialect_secondline = "CREATE TABLE IF NOT EXISTS `dialect{$table_secondline}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10) NOT NULL,`department` varchar(40) NOT NULL, `employeename` varchar(20) NOT NULL, `publicnumber` varchar(30) NOT NULL," . $sql_day . " yellowdays varchar(100),reddays varchar(100))ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	// echo "sql_day $sql_day<BR><BR>";
	// echo "create_listed_secondline $create_listed_secondline<BR><BR>";
	// echo "create_labor_secondline $create_labor_secondline<BR><BR>";
	// echo "create_dialect_secondline $create_dialect_secondline<BR><BR>";

	$create_secondline = array(
		"listed" => $create_listed_secondline,
		"labor" => $create_labor_secondline,
		"dialect" => $create_dialect_secondline,
		);

	// 记录 table [需要修改为实际的 records]

	for($i = 1; $i <= $holidaysnum; $i++){
		$sql_records = $sql_records . " `records{$i}` FLOAT(10),";
	}

	$create_listed_records = "CREATE TABLE IF NOT EXISTS `listed{$table_records}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10),`department` varchar(40) NOT NULL,`employeeid` varchar(20) NOT NULL , `employeename` varchar(20) NOT NULL, `employeebackid` varchar(20) NOT NULL,`classify` varchar(30) NOT NULL," . $sql_records . " yellowrecords FLOAT(10), redrecords FLOAT(10),UNIQUE(`employeeid`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	$create_labor_records = "CREATE TABLE IF NOT EXISTS `labor{$table_records}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10) NOT NULL,`department` varchar(40) NOT NULL,`employeeid` varchar(20) NOT NULL , `phone` varchar(20) NOT NULL,`employeename` varchar(20) NOT NULL, `onjob` varchar(30) NOT NULL," . $sql_records . " yellowrecords FLOAT(10), redrecords FLOAT(10),UNIQUE(`employeeid`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	$create_dialect_records = "CREATE TABLE IF NOT EXISTS `dialect{$table_records}`(`id` int(11) NOT NULL primary key auto_increment, `serialno` varchar(10) NOT NULL,`department` varchar(40) NOT NULL, `employeename` varchar(20) NOT NULL, `publicnumber` varchar(30) NOT NULL," . $sql_records . " yellowrecords FLOAT(10), redrecords FLOAT(10),UNIQUE(`publicnumber`))ENGINE=InnoDB DEFAULT CHARSET=utf8;";

	// echo "sql_records $sql_records<BR><BR>";
	// echo "create_listed_records $create_listed_records<BR><BR>";
	// echo "create_labor_records $create_labor_records<BR><BR>";
	// echo "create_dialect_records $create_dialect_records<BR><BR>";


	$create_records = array(
		"listed" => $create_listed_records,
		"labor" => $create_labor_records,
		"dialect" => $create_dialect_records,
		);

	date_default_timezone_set($timezone); //设置时间


	############## 写日志  #########
	function writeinfo($str, $infofile=""){
		global $logfile;
		if ($infofile == "") {
			$infofile=$logfile;
		}
		error_log(date('Y-m-d H:i:s', time()) . " " . $str . PHP_EOL, 3, $infofile, "info");
		// file_put_contents($infofile, $str.PHP_EOL,FILE_APPEND);
	}

	#### 是否合法用户
	function isvaliduser($employeeid)
	{	
		global $table_validusers,$con;	//

		if (!$con) {
			getcon();
		}

		$sql_getvalidusers = "select employeeid from {$table_validusers} where employeeid='".$employeeid."'";
		// die($sql_getvalidusers);
		$res = mysql_query($sql_getvalidusers);
		if (!$res) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$table_validusers} error: " . mysql_error());
		}
		$user = "";
		while ($validuser = mysql_fetch_assoc($res)) {
			// print_r($validuser);
			// die($sql_getvalidusers);
			$user = $validuser[employeeid];
		}
		if (empty($user)) {
			$employeeid = "0";
		}
		return $employeeid;
	}


	################################         判断是否是法定假日 每年不一样 需要修改   ################################
	function isofficialholiday($daystr)
	{
		// 2016年
		switch($daystr)
		{
			//元旦节
			case "-01-01":
			case "-1-1":
			//春节
			case "-02-07":
			case "-02-08":
			case "-02-09":
			case "-2-8":
			case "-2-9":
			case "-2-7":
			//清明节
			case "-04-04":
			case "-4-4":
			//劳动节
			case "-05-01":
			case "-5-1":
			//端午节
			case "-06-09":
			case "-6-9":
			//中秋节
			case "-09-15":
			case "-9-15":
			//国庆节
			case "-10-01":
			case "-10-02":
			case "-10-03":
			case "-10-1":
			case "-10-2":
			case "-10-3":
			//抗战胜利日
			case "-09-03":
			case "-9-3":
				return 1;
				// break;
			default:
				return 0;
				// break;
		}
		return 0;
	}
	##############  获取浏览器编码方式
	function detectencoding($q){
		$encode = mb_detect_encoding($q, array('GB2312','GBK','UTF-8')); //http://php.net/manual/zh/function.mb-detect-encoding.php
		echo $encode."<br/>";
		if($encode=="GB2312")
		{
		    $q = iconv("GBK","UTF-8",$q);
		}
		else if($encode=="GBK")
		{
		    $q = iconv("GBK","UTF-8",$q);
		}
		else if($encode=="EUC-CN")
		{
		    $q = iconv("GBK","UTF-8",$q);
		}
		else//CP936
		{
		    //$q = iconv("GB2312","UTF-8",$q);
		}
	}

	##############  获取浏览器类型
	function getbrowsertype(){
		// echo $_SERVER["HTTP_USER_AGENT"];
		if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE 8.0"))
			echo "Internet Explorer 8.0";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE 7.0"))
			echo "Internet Explorer 7.0";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE 6.0"))
			echo "Internet Explorer 6.0";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"Firefox/3"))
			echo "Firefox 3";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"Firefox/2"))
			echo "Firefox 2";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"Chrome"))
			echo "Google Chrome";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"Safari"))
			echo "Safari";
		else if(strpos($_SERVER["HTTP_USER_AGENT"],"Opera"))
			echo "Opera";
		else echo $_SERVER["HTTP_USER_AGENT"];
	}

	function getbrowserlanguage(){
		// echo $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		// print_r($_SERVER);
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 7); //只取前7位，这样只判断最优先的语言。如果取前5位，可能出现en,zh的情况，影响判断。
		if (preg_match("/zh-cn/i", $lang) || preg_match("/zh-Hans/i", $lang) || preg_match("/zh-SG/i", $lang) || preg_match("/zh-chs/i", $lang))
			echo "简体中文";
		else if (preg_match("/zh-TW/i", $lang) || preg_match("/zh-Hant/i", $lang) || preg_match("/zh-CHT/i", $lang) || preg_match("/zh-MO/i", $lang) || preg_match("/zh-HK/i", $lang) || preg_match("/-zh-SG/i", $lang))
			echo "繁體中文";
		else if (preg_match("/en/i", $lang))
			echo "English";
		else if (preg_match("/fr/i", $lang))
			echo "French";
		else if (preg_match("/de/i", $lang))
			echo "German";
		else if (preg_match("/jp/i", $lang))
			echo "Japanese";
		else if (preg_match("/ko/i", $lang))
			echo "Korean";
		else if (preg_match("/es/i", $lang))
			echo "Spanish";
		else if (preg_match("/sv/i", $lang))
			echo "Swedish";
		else echo $_SERVER["HTTP_ACCEPT_LANGUAGE"];
	}


	function getPreferredLanguage() {
	$langs = array();
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		// break up string into pieces (languages and q factors)
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)s*(;s*qs*=s*(1|0.[0-9]+))?/i',
				$_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
		if (count($lang_parse[1])) {
			// create a list like "en" => 0.8
			$langs = array_combine($lang_parse[1], $lang_parse[4]);
			// set default to 1 for any without q factor
			foreach ($langs as $lang => $val) {
				if ($val === '') $langs[$lang] = 1;
			}
			// sort list based on value
			arsort($langs, SORT_NUMERIC);
		}
	}
	//extract most important (first)
	foreach ($langs as $lang => $val) { break; }
	//if complex language simplify it
	if (stristr($lang,"-")) {$tmp = explode("-",$lang); $lang = $tmp[0]; }
	return $lang;
}

	$bankArr = array(
	'中国农业发展银行'=>'中国农业发展银行', 
	'国家开发银行'=>'国家开发银行',
	'中国进出口银行'=>'中国进出口银行',
	'中国工商银行'=>'中国工商银行', 
	'中国农业银行'=>'中国农业银行',
	'中国银行'=>'中国银行',
	'中国建设银行'=>'中国建设银行',
	'交通银行'=>'交通银行',
	'中信银行'=>'中信银行',
	'中国光大银行'=>'中国光大银行',
	'华夏银行'=>'华夏银行',
	'中国民生银行'=>'中国民生银行',
	'招商银行'=>'招商银行',
	'兴业银行'=>'兴业银行',
	'广发银行'=>'广发银行',
	'平安银行'=>'平安银行',
	'上海浦东发展银行'=>'上海浦东发展银行',
	'恒丰银行'=>'恒丰银行',
	'浙商银行'=>'浙商银行',
	'渤海银行'=>'渤海银行',
	'中国邮政储蓄银行'=>'中国邮政储蓄银行',
	'城市商业银行'=>'城市商业银行',
	'北京银行'=>'北京银行',
	'天津银行'=>'天津银行',
	'河北银行'=>'河北银行',
	'沧州银行'=>'沧州银行',
	'唐山市商业银行'=>'唐山市商业银行',
	'承德银行'=>'承德银行',
	'张家口市商业银行'=>'张家口市商业银行',
	'秦皇岛银行'=>'秦皇岛银行',
	'邢台银行'=>'邢台银行',
	'廊坊银行'=>'廊坊银行',
	'保定银行'=>'保定银行',
	'邯郸银行'=>'邯郸银行',
	'衡水银行'=>'衡水银行',
	'晋商银行'=>'晋商银行',
	'大同市商业银行'=>'大同市商业银行',
	'长治银行'=>'长治银行',
	'晋城银行'=>'晋城银行',
	'晋中银行'=>'晋中银行',
	'阳泉市商业银行'=>'阳泉市商业银行',
	'包商银行'=>'包商银行',
	'内蒙古银行'=>'内蒙古银行',
	'乌海银行'=>'乌海银行',
	'鄂尔多斯银行'=>'鄂尔多斯银行',
	'盛京银行'=>'盛京银行',
	'鞍山银行'=>'鞍山银行',
	'抚顺银行'=>'抚顺银行',
	'本溪市商业银行'=>'本溪市商业银行',
	'丹东银行'=>'丹东银行',
	'锦州银行'=>'锦州银行',
	'营口银行'=>'营口银行',
	'阜新银行'=>'阜新银行',
	'辽阳银行'=>'辽阳银行',
	'铁岭银行'=>'铁岭银行',
	'朝阳银行'=>'朝阳银行',
	'盘锦市商业银行'=>'盘锦市商业银行',
	'葫芦岛银行'=>'葫芦岛银行',
	'营口沿海银行'=>'营口沿海银行',
	'吉林银行'=>'吉林银行',
	'哈尔滨银行'=>'哈尔滨银行',
	'龙江银行'=>'龙江银行',
	'上海银行'=>'上海银行',
	'江苏银行'=>'江苏银行',
	'南京银行'=>'南京银行',
	'苏州银行'=>'苏州银行',
	'江苏长江商业银行'=>'江苏长江商业银行',
	'金华银行'=>'金华银行',
	'稠州银行'=>'稠州银行',
	'杭州银行'=>'杭州银行',
	'湖州银行'=>'湖州银行',
	'嘉兴银行'=>'嘉兴银行',
	'宁波银行'=>'宁波银行',
	'绍兴银行'=>'绍兴银行',
	'台州商行'=>'台州商行',
	'温州银行'=>'温州银行',
	'民泰商行'=>'民泰商行',
	'泰隆商行'=>'泰隆商行',
	'徽商银行'=>'徽商银行',
	'福建海峡银行'=>'福建海峡银行',
	'泉州银行'=>'泉州银行',
	'景德镇市商业银行'=>'景德镇市商业银行',
	'北京银行南昌分行'=>'北京银行南昌分行',
	'赣州商行'=>'赣州商行',
	'九江银行'=>'九江银行',
	'南昌银行'=>'南昌银行',
	'上饶银行'=>'上饶银行',
	'齐鲁银行'=>'齐鲁银行',
	'齐商银行'=>'齐商银行',
	'枣庄市商业银行'=>'枣庄市商业银行',
	'东营市商业银行'=>'东营市商业银行',
	'潍坊银行'=>'潍坊银行',
	'济宁银行'=>'济宁银行',
	'泰山市商业银行'=>'泰山市商业银行',
	'威海市商业银行'=>'威海市商业银行',
	'日照银行'=>'日照银行',
	'莱商银行'=>'莱商银行',
	'临商银行'=>'临商银行',
	'德州银行'=>'德州银行',
	'烟台银行'=>'烟台银行',
	'山东省城市商业银行合作联盟有限公司'=>'山东省城市商业银行合作联盟有限公司',
	'洛阳银行'=>'洛阳银行',
	'郑州银行'=>'郑州银行',
	'开封市商业银行'=>'开封市商业银行',
	'南阳市商业银行'=>'南阳市商业银行',
	'三门峡市商业银行'=>'三门峡市商业银行',
	'信阳银行'=>'信阳银行',
	'驻马店银行'=>'驻马店银行',
	'焦作市商业银行'=>'焦作市商业银行',
	'新乡银行'=>'新乡银行',
	'湖北银行'=>'湖北银行',
	'汉口银行'=>'汉口银行',
	'华融湘江银行股份有限公司'=>'华融湘江银行股份有限公司',
	'长沙银行股份有限公司'=>'长沙银行股份有限公司',
	'广州银行'=>'广州银行',
	'东莞银行'=>'东莞银行',
	'广东南粤银行'=>'广东南粤银行',
	'广东华兴银行'=>'广东华兴银行',
	'珠海华润银行'=>'珠海华润银行',
	'广西北部湾银行'=>'广西北部湾银行',
	'柳州银行'=>'柳州银行',
	'桂林银行'=>'桂林银行',
	'重庆银行'=>'重庆银行',
	'重庆三峡银行'=>'重庆三峡银行',
	'成都银行'=>'成都银行',
	'自贡市商业银行'=>'自贡市商业银行',
	'攀枝花市商业银行'=>'攀枝花市商业银行',
	'泸州市商业银行'=>'泸州市商业银行',
	'德阳银行'=>'德阳银行',
	'绵阳市商业银行'=>'绵阳市商业银行',
	'乐山市商业银行'=>'乐山市商业银行',
	'南充市商业银行'=>'南充市商业银行',
	'宜宾市商业银行'=>'宜宾市商业银行',
	'凉山州商业银行'=>'凉山州商业银行',
	'遂宁市商业银行'=>'遂宁市商业银行',
	'雅安市商业银行'=>'雅安市商业银行',
	'达州市商业银行'=>'达州市商业银行',
	'贵州银行'=>'贵州银行',
	'贵阳银行'=>'贵阳银行',
	'富滇银行'=>'富滇银行',
	'曲靖市商业银行'=>'曲靖市商业银行',
	'玉溪市商业银行'=>'玉溪市商业银行',
	'西藏银行股份有限公司'=>'西藏银行股份有限公司',
	'长安银行'=>'长安银行',
	'西安银行'=>'西安银行',
	'兰州银行'=>'兰州银行',
	'青海银行股份有限公司'=>'青海银行股份有限公司',
	'宁夏银行'=>'宁夏银行',
	'昆仑银行股份有限公司'=>'昆仑银行股份有限公司',
	'乌鲁木齐商行'=>'乌鲁木齐商行',
	'大连银行'=>'大连银行',
	'宁波银行股份有限公司'=>'宁波银行股份有限公司',
	'宁波东海银行股份有限公司'=>'宁波东海银行股份有限公司',
	'宁波通商银行股份有限公司'=>'宁波通商银行股份有限公司',
	'厦门银行股份有限公司'=>'厦门银行股份有限公司',
	'厦门国际银行'=>'厦门国际银行',
	'青岛银行'=>'青岛银行',
	'北京农村商业银行股份有限公司'=>'北京农村商业银行股份有限公司',
	'天津农村商业银行'=>'天津农村商业银行',
	'天津滨海农村商业银行'=>'天津滨海农村商业银行',
	'沧州融信农村商业银行股份有限公司'=>'沧州融信农村商业银行股份有限公司',
	'鄂尔多斯农村商业银行'=>'鄂尔多斯农村商业银行',
	'沈阳农村商业银行'=>'沈阳农村商业银行',
	'吉林九台农村商业银行股份有限公司'=>'吉林九台农村商业银行股份有限公司',
	'长春农村商业银行股份有限公司'=>'长春农村商业银行股份有限公司',
	'上海农商行'=>'上海农商行',
	'江苏高淳农村商业银行'=>'江苏高淳农村商业银行',
	'江苏溧水农村商业银行'=>'江苏溧水农村商业银行',
	'徐州淮海农村商业银行'=>'徐州淮海农村商业银行',
	'江苏新沂农村商业银行'=>'江苏新沂农村商业银行',
	'江苏邳州农村商业银行'=>'江苏邳州农村商业银行',
	'江苏宜兴农村商业银行'=>'江苏宜兴农村商业银行',
	'江苏扬中农村商业银行'=>'江苏扬中农村商业银行',
	'江苏丹阳农村商业银行'=>'江苏丹阳农村商业银行',
	'江苏句容农村商业银行'=>'江苏句容农村商业银行',
	'江苏镇江农村商业银行'=>'江苏镇江农村商业银行',
	'江苏宝应农村商业银行'=>'江苏宝应农村商业银行',
	'江苏仪征农村商业银行'=>'江苏仪征农村商业银行',
	'江苏高邮农村商业银行'=>'江苏高邮农村商业银行',
	'江苏江都农村商业银行'=>'江苏江都农村商业银行',
	'江苏金湖农村商业银行'=>'江苏金湖农村商业银行',
	'江苏盱眙农村商业银行'=>'江苏盱眙农村商业银行',
	'江苏涟水农村商业银行'=>'江苏涟水农村商业银行',
	'江苏淮安农村商业银行'=>'江苏淮安农村商业银行',
	'连云港东方农村商业银行'=>'连云港东方农村商业银行',
	'江苏赣榆农村商业银行'=>'江苏赣榆农村商业银行',
	'江苏沭阳农村商业银行'=>'江苏沭阳农村商业银行',
	'江苏泗洪农村商业银行'=>'江苏泗洪农村商业银行',
	'江苏泗阳农村商业银行'=>'江苏泗阳农村商业银行',
	'江苏民丰农村商业银行'=>'江苏民丰农村商业银行',
	'江苏兴化农村商业银行'=>'江苏兴化农村商业银行',
	'江苏泰州农村商业银行'=>'江苏泰州农村商业银行',
	'江苏姜堰农村商业银行'=>'江苏姜堰农村商业银行',
	'江苏东台农村商业银行'=>'江苏东台农村商业银行',
	'江苏响水农村商业银行'=>'江苏响水农村商业银行',
	'江苏大丰农村商业银行'=>'江苏大丰农村商业银行',
	''=>'',); //定义下拉表单元素数组

?>