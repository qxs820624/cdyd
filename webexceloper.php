<?php
	//webexceloper.php
	require_once('globalconfig.php');
	require_once('dbconn.php');

	if ($_POST['page']) {
		$page = intval($_POST['page']);  //获取请求的页数
		// print_r($_POST);
	}elseif ($_GET['page']) {
		$page = intval($_GET['page']);  //获取请求的页数
		// print_r($_GET);
	}
	else{
		$page = 0;  //获取请求的页数
	}
	$pageret = "";
	$start = $page*$numperpage;				//15个一页

	// return;

	if ($_POST['page']){
		// $pageret = print_r($_POST);
		// echo $_POST['page'].",id: ".$_POST['id'];
		$arraysize=count($sheetname);
		$index =  getIndex($sheetname,$_POST['id']);
		if ($arraysize == $index) {
			return;
		}
		$employeeid = $_POST['employeeid'];
		echo json_encode(create_tabcard_with_db($employeeid,$index));
		return;
	}else if ( $_GET['page']) {
		$arraysize=count($sheetname);
		$index =  getIndex($sheetname,$_GET['id']);
		if ($arraysize == $index) {
			return;
		}
		$employeeid = $_GET['employeeid'];
		echo json_encode(create_tabcard_with_db($employeeid,$index));
		return;
	}

	################################         获取索引      ################################
	function getIndex($array,$value='')
	{
		global $pageret;
		$tmp = $array;
		$index = 0;
		// $pageret .= "array:".print_r($array)."\n";
		// $pageret .= "count:".count($array)."\n";
		// $pageret .= "value:".$value."\n";
		while ( $val = array_shift($array)) {
		// $pageret .= "val:".$val."\n";
			if ($val == $value) {
				break;
			}else{
				$index++;
			}
		}
		// $pageret .= "index:".$index."\n";
		return $index;
	}

	################################         获取颜色      ################################
	function getcolor($clono, $yellowclos,$redclos,$whichrow = 2){
		if($debug) {
			echo "$clono  ";
			print_r( $yellowclos);
			echo "&nbsp;";
			print_r( $redclos);
			echo "<br>";
			$color = "normal";
			$getclor = 0;
		}
		for ($i=0; $i < count($yellowclos) - 1; $i++) {
			if ($clono == $yellowclos[$i]) {
				$color = "yellow";
				return $color;
			}
		}
		if(!$getclor){
			for ($j=0; $j < count($redclos) - 1; $j++) {
				if ($clono == $redclos[$j]) {
					$color = "red";
					return $color;
				}
			}
		}
		$color = "gray";
		if($debug) echo "$color <br>";
		return $color;
	}

	################################         从数据库描述网页表格      #########################
	function create_table_with_db($employeeid)
	{
		global $sheetcount, $sheetname,$sheetname_CN,$admin_no;
		$tabname_tdb = array();
		$alltab = '<div id="box">
    			  <div id="tab_content">';
		for ($i=0; $i < $sheetcount; $i++) {
			$alltab .=  '<div id="'.$sheetname[$i].'" style="'.($i==$sheetcount - 1?"":"display:none;").'">';
			// print_r($sheetname);
			// echo "sheetname $i {$sheetname[$i]}<BR>";
			$tabname_tdb = create_tabcard_with_db($employeeid, $i);
			// $tabname_tdb = create_tabcard_with_db($admin_no, $i);
			$alltab .=  $tabname_tdb['table'];
			$alltab .=  "</div>"; // 结束 id=$sheetname[$i] 表格
		}
		$alltab .=   '</div>'; // 结束tab_content 表格
		//输出tab选项卡

		for ($i=0; $i < $sheetcount; $i++) {
	    	$divli .= '<li  class="'.$sheetname[$i].' '.($i==$sheetcount-1?"selected":"").' " action-data="#'.$sheetname[$i].'">'.$sheetname_CN[$sheetname[$i]].'</li>'; // 设置id 为了方便查找是哪个选项卡<a href="#'.$sheetname[$i].'">'.$sheetname_CN[$sheetname[$i]].'</a>
	    }

		$alltab .=  '<ul id="tab_nav">'.$divli.'</ul>';
		// $alltab .=  '<div class="nodata"></div>';
		$alltab .=  '</div>'; // 结束box
		echo $alltab;

		// echo '<script>window.location.hash = "#'.$tabname_tdb.'";</script>';  // location.reload();
		// $redirect_fragment_url ="refresh:1;url=http://".$_SERVER['HTTP_REFERER']."#".$tabname_tdb ;
		// error_log($redirect_fragment_url,3,"./err.txt","webtabname");
		// header($redirect_fragment_url);
		// error_log(print_r($_SERVER, true),3,"./err.txt","webtabname");
		// error_log("http://".$_SERVER['REQUEST_URI']."#".$tabname_tdb,3,"./err.txt","webtabname");
		// echo "<script>location.href='".$_SERVER['REQUEST_URI']."#".$tabname_tdb."';</script>";
		if ($_GET[initfile]) {
			echo "<script>location.href='".$_SERVER['HTTP_REFERER']."#".$tabname_tdb['tabname']."';</script>";
		}else{
			echo "<script>location.href='http://".$_SERVER['HTTP_HOST']."/".trim($_SERVER['REQUEST_URI'],'/')."#".$tabname_tdb[tabname]."';</script>";
		}
		return;
	}

	########  获取每个 sheet 作为选项卡
	function create_tabcard_with_db($employeeid, $sheet_index){
		global $debug;
		global $admin_no, $uselike, $show_all_info;
		global $sheetcount, $sheetname,$sheetname_CN,$holidaysnum,$fix_cloumn,$sum_cloumn,$overtime_records_cloumn,$sencondline_column;
		global $start, $id_not_in_listed;
		global $numperpage,$firstline_cloumn;
		global $table_seq,$table_firstline, $table_secondline, $table_records,$table_remark;
		global $holidaynumber;

		$tab_content = $sheetname[$sheet_index];
		//生成表格
		$str = '<div class=="table-head"><table  style="overflow:hidden;border:1px solid #000000;"><colgroup>
            </colgroup><thead  id="thead'.$tab_content.'">';
		############  读取备注,生成备注表格
		//读取备注,生成备注表格
		$getremark  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_remark}";
		// writeinfo($getremark);
		$result = mysql_query($getremark);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$sheetname[$sheet_index]}{$table_remark} error: " . mysql_error());
		}
		//$firstline=mysql_fetch_array($result,MYSQL_ASSOC );
		//$rownum=mysql_num_rows($result);
		//echo "<br>$rownum<br>";
		while ($remark = mysql_fetch_assoc($result)) {
			$str .=  "<tr>";
			$str .= "<td class={$sheetname[$sheet_index]}remark width=600 colspan={$remark[remarkslen]}>{$remark[remarks]}</td> ";
			$str .= "</tr>";
		}
		############  读取第一行,生成第一行的表格
		//读取第一行,生成第一行的表格
		$getfirstline  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_firstline}";
		// writeinfo($getfirstline);
		$result = mysql_query($getfirstline);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . " get {$sheetname[$sheet_index]}{$table_firstline} error: " . mysql_error());
		}

		// print($firstline_cols);
		while ($firstline = mysql_fetch_assoc($result)) {
			// class {$sheetname[$sheet_index]}
			$str .=  "<tr>";
			$str .=  "<th class='record firstline' colspan={$firstline[reason_length]}>{$firstline[reason]}</th> ";
			
			for($k = 1; $k <= $holidaynumber ; $k++){
				$a = "holiday{$k}len";
				$b = "holiday{$k}";
				$holidaystr .=  "<th class='record firstline' colspan=".$firstline[$a].">".$firstline[$b]."</th> ";
			}
			// writeinfo($holidaystr);
			$str .=  $holidaystr;
			$str .=  "<th class=yellow rowspan=2>{$firstline[sum4yellow]}</th> ";
			$str .=  "<th class=red rowspan=2>{$firstline[sum4red]}</th> ";
			$str .=  "</tr>";
		}

		############  读取第二行,生成第二行的表格
		$getsecondline  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_secondline}";
		$result = mysql_query($getsecondline);
		// writeinfo($getsecondline);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . "  get {$sheetname[$sheet_index]}{$table_secondline} error: " . mysql_error());
		}
		while ($secondline = mysql_fetch_assoc($result)) {
			if($debug) {print_r($secondline); echo "<br>";}
			$yellowclos = explode(",",$secondline[yellowdays]);
			// writeinfo(print_r($secondline, true));
			$redclos = explode(",",$secondline[reddays]);
			$str .=  "<tr>";

			$secondline_array = $sencondline_column[$sheetname[$sheet_index]]; // 下标从0开始
			$clono = $fix_cloumn[$sheet_index];
			$for_end_num = count($secondline_array);

			for($k = 0; $k < $for_end_num; $k++){
				if ($k < 2) {
					$w = $k + 1;
					$str .=  "<th class='record normal w{$w}' scope=col>".$secondline[$secondline_array[$k]]."</th> ";
				}elseif ($k >= 2 && $k < $clono) {
					$str .=  "<th class='record normal' scope=col>".$secondline[$secondline_array[$k]]."</th> ";
				}else{
					$color = getcolor($clono, $yellowclos,$redclos);$clono ++;
					$str .=  '<th class='.$color.' scope=col>'.$secondline[$secondline_array[$k]].'</th>';
				}
			}
			$str .=  "</tr>";
		}
		$str .= "</thead></table>
			</div>"; //前面的表格固定
		############  读取员工记录,生成其他表格
		//获取记录总数 count
		$getrecordscount  = "SELECT count(0) FROM {$sheetname[$sheet_index]}{$table_records}";
		// writeinfo("getrecordscount ".$getrecordscount);
		$result = mysql_query($getrecordscount);
		if (!$result) {
			die("<br>" . __LINE__ . __METHOD__ . "  get the count of {$sheetname[$sheet_index]}{$table_records} error: " . mysql_error());
		}
		$count = 0;
		if(mysql_num_rows( $result)){
			$rs=mysql_fetch_array($result);
			//统计结果
			$count=$rs[0];
		}else{
			$count=0;
		}
		// 如果start 大于 pagenum 则返回 bottom
		if ($start > $count) {
			// error_log("count:".print_r($count,true).",start:".$start."\r\n",3,"err.txt","count");
			return json_encode('bottom');
		}
		if ($show_all_info) {
			$employeeid = $admin_no;
		}
		$str .= '<div id="'.$tab_content.'tbody" class="table-body"><table  style="border:1px solid #000000;"  id="table'.$tab_content.'"><colgroup>
             <col style="width: 50px;" /><col /></colgroup><tbody id="tbody'.$tab_content.'">';
		if ($start >= 0) {
			// error_log("employeeid:$employeeid,admin_no:$admin_no\r\n",3,"./err.txt","");
			if ($employeeid == $admin_no) {
				$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records} limit $start,$numperpage";
			}else
			{
				$notinlisted = false;
				$tab = "";
				foreach ($id_not_in_listed as $key => $value) {
					if(in_array($employeeid, $value)){
						$notinlisted = true;
						$tab = $value['tab'];
						$idname = $value['idname'];
						break;
					}
				}
				// writeinfo("notinlisted $notinlisted, uselike $uselike from $start to $numperpage ");
				if($notinlisted){
					writeinfo("notinlisted is $notinlisted, employeeid is $employeeid, uselike $uselike,  from $start to $numperpage ");
					if ($uselike) {
						$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records} where department LIKE concat('%',(SELECT department FROM {$tab}{$table_records}  where $idname='{$employeeid}'),'%')  limit $start,$numperpage";
					}else{
						$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records} where department=(SELECT department FROM {$tab}{$table_records}  where $idname='{$employeeid}')  limit $start,$numperpage";
					}
				}else{
					if ($uselike) {
						$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records} where department LIKE concat('%',(SELECT department FROM listed{$table_records}  where employeeid='{$employeeid}'),'%')  limit $start,$numperpage";
					}else{
						$getrecords  = "SELECT * FROM {$sheetname[$sheet_index]}{$table_records} where department=(SELECT department FROM listed{$table_records}  where employeeid='{$employeeid}')  limit $start,$numperpage";
					}
				}
			}
			// writeinfo("getrecords ".$getrecords);
			$result = mysql_query($getrecords);
			if (!$result) {
				die("<br>" . __LINE__ . __METHOD__ . "  get {$sheetname[$sheet_index]}{$table_records} error: " . mysql_error());
			}

			$tabname="labor";
			$recordspage = array();
			$i = 0;
			$recordspage[$i][recordsnum]=$holidaysnum;
			$recordspage[$i][fix_cloumns]=$fix_cloumn[$sheet_index];
			$recordspage[$i][sum_cloumn]=$sum_cloumn;
			$recordspage[$i][table_cloumn]=$overtime_records_cloumn[$sheetname[$sheet_index]];
			// error_log(print_r(json_encode($recordspage[$i]),true),3,"./err.txt","recordspage");

			$i++;
			while($records=mysql_fetch_array($result,MYSQL_ASSOC )){
				$tabname = $sheetname[$sheet_index];
				$str .=  "<tr>";

				$records_array = $overtime_records_cloumn[$sheetname[$sheet_index]]; // 下标从0开始
				$clono = $fix_cloumn[$sheet_index];
				$for_end_num = count($records_array);
				// $th_end_num = $clono;

				for($k = 0; $k < $for_end_num; $k++){
					if ($k < 2) {
						$w = $k + 1;
						$str .=  "<th class='record w{$w}' scope=col>".$records[$records_array[$k]]."</th> ";
					}elseif ($k >= 2 && $k < $clono) {
						$str .=  "<th class=record scope=col>".$records[$records_array[$k]]."</th> ";
					}elseif ($k >= 2 && $k < $for_end_num - $sum_cloumn) { // 记录
						$color = getcolor($clono, $yellowclos,$redclos);$clono ++;
						$str .=  "<td class=color_".$color.' scope=col>'.$records[$records_array[$k]].'</td>';
						$recordspage[$i]["color{$recordsnum}"] = 'color_'.$color;
					}else{
						$str .=  "<td class='span1' scope=col>" . $records[$records_array[$k]] . "</td> ";
					}
					$recordspage[$i][$records_array[$k]] = $records[$records_array[$k]];
				}

				$i++;

			}
		}
		$str .= "</tbody></table></div>";
		$retval[table]=$str;
		$retval[tabname] = $tabname;
		if($_POST['page'] || $_GET['page']){
			return $recordspage;
		}else{
			return $retval;
		}
	}
?>