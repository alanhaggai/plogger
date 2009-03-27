function checkAll(form){
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].checked == true){
				form.elements[i].checked = false;
			}
			else{
				form.elements[i].checked = true;
			}
		}
	}
}
	
		
function ThumbPreviewPopup(page) {
	var winl = (screen.width-400)/2;
	var wint = (screen.height-400)/2;
	var settings  ='height='+'400'+',';
	settings +='width='+'410'+',';
	settings +='top='+wint+',';
	settings +='left='+winl+',';
	settings +='scrollbars=no,';
	settings +='location=no,';
	settings +='menubar=no,';
	settings +='toolbar=no,';
	settings +='resizable=yes';
	OpenWin = this.open(page, "Preview", settings);
} 

function focus_first_input() {
	fields = document.getElementsByTagName('input');
	if (fields.length > 0) {
		fields[0].focus();
	}
}

function updateThumbPreview(selectObj) {
  var thumb = selectObj.options[selectObj.selectedIndex].style.backgroundImage;
  selectObj.style.backgroundImage = thumb;
}

var importThumbCounter = 0;

function onImportThumbComplete(request) {
	var picDic = 'pic_' + importThumbs[importThumbCounter];
	Element.update(picDic,request.responseText);
	var progress = (importThumbCounter + 1)/ importThumbs.length * 100;
	Element.update('progress',Math.round(progress) + '%');
	if (importThumbCounter < importThumbs.length) {
		importThumbCounter++;
		requestImportThumb();
	}
};

function requestImportThumb() {
	new Ajax.Request('plog-thumb.php', {method: 'get', onComplete: onImportThumbComplete, parameters: 'img=' + importThumbs[importThumbCounter]});
};

function checkArchive(fileInput) {

	// check the extension of the chosen file, if it is a zip file
	// we want to disable the caption and description fields because
	// these are going to be set on the import page.
	
	var filePath = fileInput.value;
	var fileParts = new Array();
	
	fileParts = filePath.split('.');
	var fileExtension = fileParts[fileParts.length-1];
	
	if (fileExtension.toLowerCase() == 'zip') {
		document.getElementById('caption').disabled = true;
		document.getElementById('description').disabled = true;
		document.getElementById('caption').style.background = "#fafafa";
		document.getElementById('description').style.background = "#fafafa"; 
	}
	else {
		document.getElementById('caption').disabled = false;
		document.getElementById('description').disabled = false;
		document.getElementById('caption').style.background = "#ffffff";
		document.getElementById('description').style.background = "#ffffff"; 
	}	
	
}

function toggle(obj) {
	var el = document.getElementById(obj);
	if ( el.style.display != 'none' ) {
		el.style.display = 'none';
	}
	else {
		el.style.display = '';
	}

}