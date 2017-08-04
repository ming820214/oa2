"use strict";
var IPaddr	= 'http://121.42.236.42:88/oa2/';
//var IPaddr	= 'http://192.168.2.211/oa2/';

HTMLElement.prototype.$ = function( el ){
	return this.querySelector(el);
}
HTMLElement.prototype.$$ = function( el ){
	return this.querySelectorAll(el);
}


window.addEventListener('error', function( err ){
	alert('错误信息：' + err.message + '\n' + '地址：' + decodeURIComponent(err.filename) + '\n' + '行号：' + err.lineno );
}, false);


Document.prototype.$ = Document.prototype.querySelector;
Document.prototype.$$ = Document.prototype.querySelectorAll;
// Document.prototype.$$ = Document.prototype.querySelectorAll;

var AJAX	= function( op, fn ){
	var Url 		= op.url || '',
		RequestType = op.type ? op.type.toUpperCase() : null || 'GET',
		Data		= op.data || '',
		DataType	= op.dataType ? op.dataType.toUpperCase() : null || 'JSON',
		Success		= typeof op.success ==='function' ? op.success : function(){},
		ErrorEvent  = typeof op.error ==='function' ? op.error : function(){},
		timeout		= op.timeOut || 30000;


	var XHR = new XMLHttpRequest();
	XHR.timeout = timeout;
	XHR.onreadystatechange = function(){
		var responseData;
		if( this.readyState === 4 ){
			if( this.status === 200 ){
				switch (DataType) {
					case 'JSON':
						responseData = JSON.parse( XHR.responseText );
						Success( responseData );
						if( typeof fn === 'function'){
							fn();
						}
						break;
					default:
						console.error('Parse response data error!');
						return;
				}	
			} else {
				ErrorEvent( this.status );
			}
		} 
	}

	switch( RequestType ){
		case 'GET':
			XHR.open( RequestType , Url + Data );
			XHR.send();
		break;
		case 'POST':
			XHR.open( RequestType , Url );
			XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			XHR.send( Data );
		break;
	}
}


/*
 * 自动隐藏信息框
 * @param msg 信息内容
 * @param sec 信息框多少秒后自动消失,默认为4.5s
 */
function Toast( msg, sec, fn ){
	msg = msg || "";
	var browserWidth			= window.innerWidth || document.documentElement.clientWidth,
		ID						= "HiddenMessageDialog" + new Date().getTime();
	var dialogBoxWidth			= (function(){
										var len = msg.length , w = 0;
										for (var i = 0; i < len; i++) {
											w += msg.charCodeAt(i) <= 255 ? 9.6 : 19.2;
										}
										return  w + 40 >= browserWidth  ? browserWidth : w + 40;
								  })();
	var dialogbox 				= document.createElement("div");
	dialogbox.style.position	= "fixed";
	dialogbox.style.background	= "rgb(44,50,50)";
	dialogbox.style.width		= dialogBoxWidth + "px";
	dialogbox.style.height		= "50px";
	dialogbox.style.left		= (browserWidth - dialogBoxWidth ) /2  + "px";
	dialogbox.style.bottom		= "60px";
	dialogbox.style.fontFamily	= "微软雅黑";
	dialogbox.style.textAlign	= "center";
	dialogbox.style.lineHeight	= "50px";
	dialogbox.style.fontSize	= "1.2em";
	dialogbox.style.color		= "#DDDDDD";
	dialogbox.style.borderRadius= "25px";
	dialogbox.style.webkitBorderRadius = "25px"; 
	dialogbox.style.mozBorderRadius = "25px"; 
	dialogbox.style.zIndex		= "999999999";
	dialogbox.setAttribute( "id" , ID );
	var textNode = document.createTextNode( msg );
	dialogbox.appendChild( textNode );
	document.body.appendChild( dialogbox );
	setTimeout(function(){
		var timer = 100;
		var tt = setInterval(function(){
			dialogbox.style.opacity = timer / 100 + "";
			dialogbox.style.webkitOpacity = timer / 100 + "";
			dialogbox.style.mozOpacity = timer / 100 + "";
			timer -= 20;
			(timer<0) && (document.body.removeChild(document.getElementById(ID)),typeof fn === 'function' && fn(),clearInterval(tt));
		},100);
	},  sec * 1000 || 4500 );
}

//Cookie设置方法
//————BEGIN————
var Cookie = {
	//设置Cookie 参数sExpires为设置过期天数，负数为清除cookie;
	set:function(sName,sValue,sExpires,sPath){
		var sCookie=sName+"="+encodeURIComponent(sValue)+";";
		if(sExpires){
			var d=new Date();
			d.setTime(d.getTime()+sExpires*24*60*60*1000);
			sCookie+="expires="+d.toGMTString()+";";
		}else if(sExpires<0){
			var d=new Date(0);
			sCookie+="expires="+d.toGMTString()+";";
		}
		if(!sPath){
			sCookie+="path="+"\/"+";";
		}else{
			sCookie+="path="+sPath+";";
		}
		document.cookie=sCookie;
	},
	//读取Cookie值
	get:function(sName){
		var sREG="(?:;)?"+sName+"=([^;]*);?";
		var oREG=new RegExp(sREG);
		if(oREG.test(document.cookie)){
			return decodeURIComponent(RegExp["$1"]);
		}else{
			return null;
		}
	},
	clear:function(sName){
		this.set(sName,"",-1);
	}
};


JSON.toString = function(data,fn){
						fn = fn || null;
						return JSON.stringify(data,fn,'\t');
					}
					