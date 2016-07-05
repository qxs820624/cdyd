if (!Array.prototype.indexOf)
{
  Array.prototype.indexOf = function(elt /*, from*/)
  {
    var len = this.length >>> 0;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0)
      from += len;

    for (; from < len; from++)
    {
      if (from in this &&
          this[from] === elt)
        return from;
    }
    return -1;
  };
}

//弹出一个输入框，输入一段文字，可以提交
function prom(pass) {
	var userpass = prompt('请输入员工编号，默认密码是'+pass + ',或者用冒号隔开用户名和密码，且冒号不能作为密码，例如:', '36270000:qwe!@#'); //将输入的内容赋给变量 name ，
	//这里需要注意的是，prompt有两个参数，前面是提示的话，后面是当对话框出来后，在对话框里的默认值
	if (userpass)//如果返回的有内容
	{
		var tot = userpass.split(":", 2);
		userno=tot[0];
		if (tot[1]) {
			pass=tot[1];
		};
		// alert("userpass--" + userpass + " ,tot: " + tot + " ,userno: " + userno + ", pass:" + pass);
		// return;
		if(confirm('您确定要重置 ' + userno + ' 的密码为 ' + pass + '吗？')){
			// alert(self.location);
			$.post(self.location, {'post_resetpassword':userno,'password':pass}, function(data){alert(data);});
		};
	}
}

function js_resetpass(pass){
	prom(pass);
}

function stop(){
	return false;
}

function enable(){
	return true;
}

function setBodyProperty(vFlag){
	if (vFlag) {
		window.onselectstart=stop;			//禁用选择
		window.oncopy=stop;      			//禁止复制
		window.onpaste=stop;   				//禁止粘贴
		window.oncut=stop;   				//禁止剪切
		window.ondragstart=stop;   			//禁止拖拉
		document.oncontextmenu=stop;		//禁止右键
		// document.onkeydown=stop;			//禁止键盘
		// document.onmousedown=stop;			//禁止鼠标
	}else{
		window.onselectstart=enable;			//禁用选择
		window.oncopy=enable;      			//禁止复制
		window.onpaste=enable;   				//禁止粘贴
		window.oncut=enable;   				//禁止剪切
		window.ondragstart=enable;   			//禁止拖拉
		document.oncontextmenu=enable;		//禁止右键
		document.onkeydown=enable;			//禁止键盘
		document.onmousedown=enable;			//禁止鼠标
	};
}


function addListener(element, e, fn) {
	if (element.addEventListener) {
		element.addEventListener(e, fn, false);
	} else {
		element.attachEvent('on' + e, fn);
	}
}

function setbuttonenabled(){
	document.getElementById('fillinfo').disabled=false;
	document.getElementById('divrs').style.display='block'
	document.getElementById('inputup').style.display='inline';
	document.getElementById('fillinfo').style.display='inline';;
	document.getElementById('ex').style.display='inline';
	document.getElementById('initusersdb').style.display='inline';
	document.getElementById('log2ex').style.display='inline';
	document.getElementById('iexportextrainfo').style.display='inline';
}

function setbuttondisabled(){
	document.getElementById('fillinfo').style.display='inline';
	// document.getElementById('fillinfo').disabled=true;
	document.getElementById('divrs').style.display='none'
	document.getElementById('inputup').style.display='none';
	document.getElementById('ex').style.display='none';
	document.getElementById('initusersdb').style.display='none';
	document.getElementById('log2ex').style.display='inline';
	document.getElementById('iexportextrainfo').style.display='none';
}

function CheckFile(obj) {
    var array = new Array('xls');  //可以上传的文件类型 'gif', 'jpeg', 'png', 'jpg'
    if (obj.value == '') {
        alert("让选择要上传的excel97-2003!");
        return false;
    }
    else {
        var fileContentType = obj.value.match(/^(.*)(\.)(.{1,8})$/)[3]; //这个文件类型正则很有用：）
        var isExists = false;
        for (var i in array) {
            if (fileContentType.toLowerCase() == array[i].toLowerCase()) {
                isExists = true;
                return true;
                var userno = document.getElementById('no').value;
                var password = document.getElementById('pas').value;
                var upload = document.getElementById('initusersdb').value;
                $.post(self.location,{userno:userno, password:password, upload:upload},function(data){
                	alert(data);
                });
            }
        }
        if (isExists == false) {
            obj.value = null;
            alert("上传excel类型不正确!");
            return false;
        }
        return false;
    }
}


/*
// 初始化员工表

$('.replaceconfigbt').click(function ()
{
	var aaa = $('.fileinputbt').click();
	console.log(aaa);
});

function js_reset_staffinfo(elementId) {
    var file1 = document.getElementById(elementId);
    var reqstr = file1.files[0];
    console.log(reqstr);
    var request = {
            requestId: 'reset_staffinfo',
            sessionId: '1234567890',
            userName:  'qxx',
            password:  '123',
            request:   reqstr,
    };
    // var encoded = $.toJSON( request );
    // var jsonStr = encoded;
    // var actionStr = $("#actionPath").val();
    if($('#'+elementId).val()){
		// $.ajax({
		//     url : self.location,
		//     type : 'POST',
		//     data : request,
		//     dataType : 'json',
		//     //contentType : 'application/json',
		//     success : function(data, status, xhr) {
		// //         Do Anything After get Return data
		// //          $.each(data.payload, function(index){
		// //              $("#result").append("</br>" + data.payload[index].beanStr);
		// //          });
		//     },
		//     Error : function(xhr, error, exception) {
		//         // handle the error.
		//         alert(exception.toString());
		//     }
		// });
		$.post(self.location,request,function(data){})
     }else{
         $.messager.alert('信息提示','文件类型不合法,只能是 jpg、gif、bmp、png、jpeg 类型！');
     }
}

function checkpost()
{
	alert(document.myform.userno.value);
	if (myform.name.value=='')
	{
		alert('请填写用户编号(公免号码)');
		myform.name.focus();
		return false;
	}
	is_numeric(myform.name.value) or die('提供的参数不是数字');    //preg_match('/^\d+$/i', 'abcd123') or die('提供的数据不是数字');

	if (myform.password.value=='')
	{
		alert('请填写密码');
		myform.password.focus();
		return false;
	}
}


function OnResetClick()
{
	var iu = document.getElementById('inputup').value;
	if (iu == '') {
		document.getElementById('inputup').click();
	}
}

*/
