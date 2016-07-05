<?php
	error_reporting(E_ERROR);
	require_once 'zip.php';
	$charset = "GB2312"; //GB2312 UTF-8

	if ($_POST['downfile']){
		// die("<script>alert(".print_r($_POST,true).")</script>");
		// die(print_r($_POST,true));
		$url="favicon.ico";
		downfile($url);
		die();
	}else if ($_GET['downfile']) {
		$url = $_GET['downfile'];
		downfile($url);
		die();
	}elseif ($_GET['getpackage']) {
		if (true) {
			getpackage($_GET['getpackage']);
		}else{
			$zip = new PHPZip(); 
			$dirpath = $_GET['getpackage'];
			$zipfilepath = dirname($dirpath). '/' . basename($dirpath).date("Y-m-d")."_".md5(time())."_src.zip";
			$zip -> downloadZip($dirpath,$zipfilepath);
		}
		die();
	}

	header("Content-Type:text/html;charset=$charset"); //tell the php that the charset is utf-8
	header ("Cache-Control: no-store, no-cache,must-revalidate ");
	header('Pragma:no-cache');
	header("Expires:0");

	date_default_timezone_set("Asia/Shanghai"); //设置时区 跟默认字符编码有关

	function downfile($fileurl)
	{
		$filename=$fileurl;
		$desname = basename($filename);
		$file   =   fopen($filename, "rb");
		Header( "Content-type:   application/octet-stream ");
		Header( "Accept-Ranges:   bytes ");
		Header( "Content-Disposition: attachment; filename= $desname");


		$contents = "";
		while (!feof($file)) {
		  $contents .= fread($file, 8192);
		}
		echo $contents;
		fclose($file);
	}

	function list_file($dir){
		global $charset;
		$list = scandir($dir); // 得到该文件下的所有文件和文件夹
		echo "<ul>";
		foreach($list as $file){//遍历
			// echo "dir is $dir, file is $file<BR>";
			$transdir = $charset != "GB2312" ? iconv("UTF-8", "GB2312", $dir) : iconv("GB2312", "UTF-8", $dir);
			$transfile = $charset != "GB2312" ? iconv("UTF-8", "GB2312", $file) : iconv("GB2312", "UTF-8", $file);
			$file_location=$transdir."/".$transfile;//生成路径 &&$file!=".."
			if(is_dir($file_location) && $file!="."){
				//判断是不是文件夹
				if ($file == ".." && $dir ==".") {
					echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=".'..'."><b>$file</b></font></li>";
				}else if ($file == ".." && basename($dir) != "..") {
					echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=".dirname($dir)."><b>$file</b></font></li>";
				}else{
					echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=".$file_location."><b>$file</b></font></li>";
				}
				// echo "------------------------sign in $file_location------------------";
				// list_file($file_location); //继续遍历
			}else{
				if ( $file!="." && $file!="..") {
					echo "<li ><a href=?downfile="."$dir/$file"." target=_self>$file</li>";
				}
			}
		}
		echo "</ul>";
		$text = "打包下载";
		$transtext = $charset == "GB2312" ? iconv("UTF-8", "GB2312", $text) : iconv("GB2312", "UTF-8", $text);
		echo "<li><a href=?getpackage=".$dir.">". $transtext ."</a></li>";
		// 
	}

	//获取文件列表
	function zip_list_dir($dir){
    	$result = array();
    	if (is_dir($dir)){
    		$file_dir = scandir($dir);
    		foreach($file_dir as $file){
    			// echo "$file<br>";
    			$fullefilepath = $dir."/".$file;
    			if ($file == '.' || $file == '..'){
    				continue;
    			}
    			elseif (is_dir($fullefilepath)){
    				// echo "is_dir<br>";
    				$result = array_merge($result, zip_list_dir($fullefilepath.'/'));
    			}
    			else{
    				array_push($result, $fullefilepath);
    			}
    		}
    	}
    	// echo print_r($result,true)."<br>";
    	return $result;
    }

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
    	chdir($dir);
		if (1) {
			$zip = new ZipArchive();
			$bname = basename($dir);
			$fname = "";
			if ($bname != "..") {
				$fname = $bname;
			}
		    $filename = '../'.$fname."_".date("Ymd")."_".md5(time())."_src.zip";
		    // die($filename);
		    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
		      exit("无法创建 <$filename>\n");
		    }
		    $files = listdir();
		    foreach($files as $path)
		    {
		      $zip->addFile($path,str_replace("./","",str_replace("\\","/",$path)));
		    }
		    $zip->close();
    	}else {
			$filename = "../" .$dir."/bak.zip"; //最终生成的文件名（含路径）
	    	//获取列表
			$datalist=zip_list_dir($dir);
			if(!file_exists($filename)){
			//重新生成文件
			    $zip = new ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
			    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
			        exit('无法打开文件，或者文件创建失败');
			    }
			    foreach( $datalist as $val){
			        if(file_exists($val)){
			            $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下   
			        }
			    }
			    $zip->close();//关闭
			}
		}
		if(!file_exists($filename)){
		    exit("无法找到文件"); //即使创建，仍有可能失败。。。。
		}
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename='.basename($filename)); //文件名
		header("Content-Type: application/zip"); //zip格式的
		header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
		header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小
		@readfile($filename);
    }


?>
<!DOCTYPE html "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset?>" />
	<title>directory</title>
	<script src="jquery-1.11.3.min.js"></script>
	<style type="text/css">
	body {padding: 0;margin: 0;font:bold 16px "仿宋", Verdana, Arial, Helvetica, sans-serif; background:#EEE; text-align:left;overflow: auto;}
	ul, body,li{margin-left: 10px;}
	.selected{background-color: #FCE;}
	</style>
	<script type="text/javascript">
		$(function(){
			$('.lidirectory').click(function(){
				$(this).siblings().removeClass("selected");
				// $("#tab_content>div").hide();
				$(this).addClass("selected");
				// $($(this).attr("action-data")).show();
			});
			$('.downfile').click(function(){
				// var fileurl = $('.downfile').find('a').attr('href');
				var fileurl = $(this).text();
				// location.href=getRootPath() + '/test.php?downfile=' +  fileurl;
				location.href=self.location + '?downfile=' +  fileurl;
				if (typeof(fileurl) != 'undefined') {
					$.post('test.php',{'downfile':fileurl},function(data){
						// alert(data);return;
					});
					$.post(self.location,{'downfile':fileurl},function(data){alert(data);return;});
				};
			});
		});

		function getRootPath() {
			var strFullPath = window.document.location.href;
			var strPath = window.document.location.pathname;
			var pos = strFullPath.indexOf(strPath);
			var prePath = strFullPath.substring(0, pos);
			var postPath = strPath.substring(0, strPath.substr(1).indexOf('/') + 1);
			// alert("postPath" + postPath);
			return (prePath + postPath);
		}

		function getPath() {
			var strPath = window.document.location.pathname;
			var postPath = strPath.substring(0, strPath.substr(1).indexOf('/') + 1);
			// return ("postPath" + postPath);
			return postPath;
		}
	</script>
</head>
<body>
<?php
	if ($_GET['dirurl']) {
		$url = $_GET['dirurl'];
		list_file($url);
		die();
	}
	list_file();
?>
</body>
</html>