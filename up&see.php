<?php

error_reporting(E_ERROR);
set_time_limit(0); //脚本不超时

header("Content-Type:text/html;charset=utf-8"); //tell the php that the charset is utf-8
header ("Cache-Control: no-store, no-cache,must-revalidate ");
header('Pragma:no-cache');
header("Expires:0");

require_once(__DIR__ ."/php-console/src/PhpConsole/__autoload.php");
// $charset = "GB2312"; //GB2312 UTF-8

$zhCN = substr_count(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 'zh-cn');
// $zhCN =  stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'],"zh-cn");
// echo $_SERVER['HTTP_ACCEPT_LANGUAGE']."<br>";
// echo strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'])."<br>";

$handler = PhpConsole\Handler::getInstance();
/* You can override default Handler behavior: 
$handler->setHandleErrors(false); // disable errors handling 
$handler->setHandleExceptions(false); // disable exceptions handling 
$handler->setCallOldHandlers(false); // disable passing errors & exceptions to prviously defined handlers */ 
$handler->start(); // initialize handlers
$handler->debug("aaaa","aaa");



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

if ($_GET['dirurl']) {
	$url = $_GET['dirurl'];
	list_file($url);
	die();
}

print_page();

// list_file();


if ($_POST) {
	// ;
	// echo print_r($_POST,true)."\r\n<BR>";
	// echo print_r($_FILES["file"],true)."\r\n<BR>";
	$destination = getcwd()."/upload";
	foreach($_FILES['file']['tmp_name'] as $k=>$v){
		echo "foreach $k=>$v <BR>";
		echo $_FILES["file"]["type"][$k];
		// 我们增加了对文件上传的限制。用户只能上传 .gif 或 .jpeg 文件，文件大小必须小于 20 kb：
		if ((($_FILES["file"]["type"][$k] == "image/gif")
		|| ($_FILES["file"]["type"][$k] == "image/jpeg")
		|| ($_FILES["file"]["type"][$k] == "application/octet-stream")
		|| ($_FILES["file"]["type"][$k] == "application/x-zip-compressed") //压缩包
		|| ($_FILES["file"]["type"][$k] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document") // word
		|| ($_FILES["file"]["type"][$k] == "application/vnd.ms-excel") //excel表格
		|| ($_FILES["file"]["type"][$k] == "text/plain")	// text文件
		|| ($_FILES["file"]["type"][$k] == "image/pjpeg"))
		&& ($_FILES["file"]["size"][$k] < 3000000)){
			if ($_FILES["file"]["error"][$k] > 0)
			{
				echo "Error: " . $_FILES["file"]["error"][$k] . "<br />";
			}
			else
			{
				print_fileinfo($_FILES,$k);
				if ($_POST["dest"]) {
					$destination = $_POST["dest"];
				}
				$filename = $_FILES["file"]["name"][$k];
				$dest = $destination.DIRECTORY_SEPARATOR.$filename;
				$dest_file = $dest;
				if($zhCN != 0) {
					// echo '<br />你的是中文操作系统<br />';
					$encode = mb_detect_encoding($filename, array("ASCII","UTF-8","GB2312","GBK","BIG5")); 
					// echo "<br />$filename 的编码是:". $encode. "<br />";
					$dest_file = $destination.DIRECTORY_SEPARATOR.iconv("UTF-8","gb2312",$filename);
				}

				if (file_exists($dest_file))
				{
					echo $dest . " already exists, then delete it. <br />";
					unlink($dest_file);
				}

				if (!file_exists($destination)){
					create_folders($destination);
				}
				move_uploaded_file($_FILES["file"]["tmp_name"][$k],
				$dest_file);
				echo "Stored in: " .$dest. "<br />";
			}
		}else{
			print_fileinfo($_FILES,$k);
			echo "<b style='text-color:red;'>请选择gif/jpeg文件上传，并且不能大于3M</b>";
		}

	}
}

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
	global $charset,$zhCN,$handler;
	$list = scandir($dir); // 得到该文件下的所有文件和文件夹
	print_dir_head();
	echo "<ul>";
	foreach($list as $file){//遍历
		$encode = mb_detect_encoding($file, array("ASCII","UTF-8","GB2312","GBK","BIG5")); 
		// echo "dir is $dir, file is $file<BR>encode $encode<BR>";
		// $charset != "GB2312" 替换成了 $zhCN !== 0
		$transdir = $zhCN == 0 ? iconv($encode, "UTF-8//IGNORE", $dir) : iconv($encode, "GB2312//IGNORE", $dir);
		$handler->debug('get transdir '.$transdir, 'upsee');

		$transfile =  $zhCN == 0 ? iconv($encode, "UTF-8//IGNORE", $file) : iconv($encode, "GB2312//IGNORE", $file);
		$handler->debug('get transfile '.$transfile, 'upsee');

		$file_location=$transdir."/".$transfile;//生成路径 &&$file!=".."
		$handler->debug('get file_location '.$file_location, 'upsee');
		if(is_dir($file_location) && $file!="."){
			// if($zhCN != 0) {
			// 	$file = iconv("UTF-8","gb2312",$file);
			// 	echo $file;
			// }
			//判断是不是文件夹
			if ($file == ".." && $dir ==".") {
				echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=../><b>$file</b></font></li>";
			}else if ($file == ".." && basename($dir) != "..") {
				echo dirname($transdir)."     file  $file<br>";
				echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=".dirname($transdir)."/><b>$file</b></font></li>";
			}else{
				echo "$file_location<br>";
				echo "<li class='lidirectory'><font color=\"#ff00cc\"><a href=?dirurl=".$file_location."/><b>$file</b></font></li>";
			}
			// echo "------------------------sign in $file_location------------------";
			// list_file($file_location); //继续遍历
		}else{
			if ( $file!="." && $file!="..") {
				echo "<li ><a href=?downfile="."$dir/$file"." target=_self/>$file</li>";
			}
		}
	}
	echo "</ul>";
	$text = "打包下载";
	// $transtext = $charset == "GB2312" ? iconv("UTF-8", "GB2312", $text) : iconv("GB2312", "UTF-8", $text);
	$transtext = $text;
	echo "<li><a href=?getpackage=".$dir.">". $transtext ."</a></li><br><br>";
	echo '<form action="'.$_SERVER["PHP_SELF"].'" method="post" accept-charset="utf-8">
		<input type="submit" name="btupload" value="上传文件">
	</form>';
	print_dir_body();
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


function create_folders($dir, $mode = 0777){ 
	// return is_dir($dir) and (create_folders(dirname($dir)) and mkdir($dir, 0777)); 
	if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
	if (!create_folders(dirname($dir), $mode)) return FALSE;
	return @create_folders($dir, $mode);
}

function print_page(){
	echo '<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
			<title>上传文件</title>
		</head>
		<body>
			<form action="'.$_SERVER["PHP_SELF"].'" method="post"
				enctype="multipart/form-data">
				<label for="file">上传文件(可多选):</label>
				<input type="file" name="file[]" id="file" multiple="true" style="border:1px solid red"/> 
				<br />
				<label for="fileto">保存到路径:</label>
				<input type="text" name="dest" id="text" /> 
				<br /><br />
				<input type="submit" style= "margin-left:100px" name="submit" value="上传" />
				<br /><br />
			</form>
			<form action="'.$_SERVER["PHP_SELF"].'" method="get" accept-charset="utf-8">
				浏览目录: <input type="text" name="dirurl" />
				<input type ="submit" value="浏览" />
			</form>
		</body>
	</html>';
}

function print_fileinfo($FILES, $k = 0){
	echo "Upload: " . $FILES["file"]["name"][$k] . "<br />";
	echo "Type: " . $FILES["file"]["type"][$k] . "<br />";
	echo "Size: " . ($FILES["file"]["size"][$k] / 1024) . " Kb<br />";
	echo "Temp file: " . $FILES["file"]["tmp_name"][$k] . "<br />";
}

function print_dir_head(){
	echo "<!DOCTYPE html \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html>
<head>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=<?php echo $charset?>\" />
	<title>directory</title>
	<script src=\"jquery-1.11.3.min.js\"></script>
	<style type=\"text/css\">
	body {padding: 0;margin: 0;font:bold 16px \"仿宋\", Verdana, Arial, Helvetica, sans-serif; background:#EEE; text-align:left;overflow: auto;}
	ul, body,li{margin-left: 10px;}
	.selected{background-color: #FCE;}
	</style>
	<script type=\"text/javascript\">
		$(function(){
			$('.lidirectory').click(function(){
				$(this).siblings().removeClass(\"selected\");
				$(this).addClass(\"selected\");		});
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
			return (prePath + postPath);
		}

		function getPath() {
			var strPath = window.document.location.pathname;
			var postPath = strPath.substring(0, strPath.substr(1).indexOf('/') + 1);
			return postPath;
		}
	</script>
</head>
<body>";
}

function print_dir_body(){
	echo "</body>
	</html>";
}

?>