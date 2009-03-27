function validate_checkboxes(field){
	var valid = false;
	
	for (i = 0; i < field.length; i++) {
			if (field[i].checked == true)
				 valid = true;
	}
	
	if (valid == false) {
		 alert('Nothing is selected!');
		 return false;
	}
	else
		 return true;
	
}	

function hide_details(){
	var httpRequest;
	
	if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest();
	else if (window.ActiveXObject) httpRequest = new ActiveXObject('Msxml2.XMLHTTP');
	
	httpRequest.open('GET', 'set_session_var.php?var=details&val=0', true);
	
	httpRequest.onreadystatechange = function (evt) {
		if (httpRequest.readyState == 4) {
		}
	};
	
	httpRequest.send(null);
	
	document.getElementById('exif_data').style.display = 'none';
	document.getElementById('exif_toggle').innerHTML = '<a accesskey="d" href="javascript:void(0);" onclick="show_details();">Show details</a>';
}

function show_details(){
	var httpRequest;
	
	if (window.XMLHttpRequest) httpRequest = new XMLHttpRequest();
	else if (window.ActiveXObject) httpRequest = new ActiveXObject('Msxml2.XMLHTTP');
	
	httpRequest.open('GET', 'set_session_var.php?var=details&val=1', true);
	
	httpRequest.onreadystatechange = function (evt) {
		if (httpRequest.readyState == 4) {
		}
	};
	
	httpRequest.send(null);
	
	document.getElementById('exif_data').style.display = '';
	document.getElementById('exif_toggle').innerHTML = '<a accesskey="d" href="javascript:void(0);" onclick="hide_details();">Hide details</a>';
}

function display_overlay(img, content) {

	// change position of overlay div
	// move to upper left hand corner of image
	
	// only display overlay if more than one comment
	if (parseInt(content) > 0) {
	  	var position = getElementPosition(img);
	  	
	  	var top_pos = position.top;
	  	var left_pos = position.left;
	  		  	
	  	document.getElementById('overlay').style.visibility = 'hidden';
	  	document.getElementById('overlay').innerHTML = content;
	  	document.getElementById('overlay').style.left = left_pos + "px";
	  	document.getElementById('overlay').style.top = top_pos + "px";
	  	document.getElementById('overlay').style.visibility = 'visible';
	  	document.getElementById('overlay').style.zIndex = document.getElementById(img).zIndex + 1;
	}

}

function getElementPosition(elemID) {
    var offsetTrail = document.getElementById(elemID);
    var offsetLeft = 0;
    var offsetTop = 0;
    while (offsetTrail) {
        offsetLeft += offsetTrail.offsetLeft;
        offsetTop += offsetTrail.offsetTop;
        offsetTrail = offsetTrail.offsetParent;
    }
    if (navigator.userAgent.indexOf("Mac") != -1 && 
        typeof document.body.leftMargin != "undefined") {
        offsetLeft += document.body.leftMargin;
        offsetTop += document.body.topMargin;
    }
    return {left:offsetLeft, top:offsetTop};
}

function pform_action(act) {
	pf = document.getElementById('fprocess');
	pf.action = 'plog-process.php';
	act_el = document.getElementById('fp_action');
	act_el.value = act;
	pf.submit();

}
