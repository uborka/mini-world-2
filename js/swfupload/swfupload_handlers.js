/*
	A simple class for displaying file information and progress
	Note: This is a demonstration only and not part of SWFUpload.
	Note: Some have had problems adapting this class in IE7. It may not be suitable for your application.
*/

// Constructor
// file is a SWFUpload file object
// targetID is the HTML element id attribute that the FileProgress HTML structure will be added to.
// Instantiating a new FileProgress object with an existing file will reuse/update the existing DOM elements
function FileProgress(file, targetID) {
	this.fileProgressID = file.id;

	this.opacity = 100;
	this.height = 0;
	

	this.fileProgressWrapper = document.getElementById(this.fileProgressID);
	if (!this.fileProgressWrapper) {
		this.fileProgressWrapper = document.createElement("div");
		this.fileProgressWrapper.className = "progressWrapper";
		this.fileProgressWrapper.id = this.fileProgressID;

		this.fileProgressElement = document.createElement("div");
		this.fileProgressElement.className = "progressContainer";

		var progressCancel = document.createElement("a");
		progressCancel.className = "progressCancel";
		progressCancel.href = "#";
		progressCancel.style.visibility = "hidden";
		progressCancel.appendChild(document.createTextNode(" "));

		var progressText = document.createElement("div");
		progressText.className = "progressName";
		progressText.appendChild(document.createTextNode(file.name));

		var progressBar = document.createElement("div");
		progressBar.className = "progressBarInProgress";

		var progressStatus = document.createElement("div");
		progressStatus.className = "progressBarStatus";
		progressStatus.innerHTML = "&nbsp;";

		this.fileProgressElement.appendChild(progressCancel);
		this.fileProgressElement.appendChild(progressText);
		this.fileProgressElement.appendChild(progressStatus);
		this.fileProgressElement.appendChild(progressBar);

		this.fileProgressWrapper.appendChild(this.fileProgressElement);

		document.getElementById(targetID).appendChild(this.fileProgressWrapper);
	} else {
		this.fileProgressElement = this.fileProgressWrapper.firstChild;
		this.reset();
	}

	this.height = this.fileProgressWrapper.offsetHeight;
	this.setTimer(null);


}

FileProgress.prototype.setTimer = function (timer) {
	this.fileProgressElement["FP_TIMER"] = timer;
};
FileProgress.prototype.getTimer = function (timer) {
	return this.fileProgressElement["FP_TIMER"] || null;
};

FileProgress.prototype.reset = function () {
	this.fileProgressElement.className = "progressContainer";

	this.fileProgressElement.childNodes[2].innerHTML = "&nbsp;";
	this.fileProgressElement.childNodes[2].className = "progressBarStatus";
	
	this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
	this.fileProgressElement.childNodes[3].style.width = "0%";
	
	this.appear();	
};

FileProgress.prototype.setProgress = function (percentage) {
	this.fileProgressElement.className = "progressContainer progress_in";
	this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
	this.fileProgressElement.childNodes[3].style.width = percentage + "%";

	this.appear();	
};
FileProgress.prototype.setComplete = function () {
	this.fileProgressElement.className = "progressContainer progress_complete";
	this.fileProgressElement.childNodes[3].className = "progressBarComplete";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	this.setTimer(setTimeout(function () {
		oSelf.disappear();
	}, 10000));
};
FileProgress.prototype.setError = function () {
	this.fileProgressElement.className = "progressContainer progress_error";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	this.setTimer(setTimeout(function () {
		oSelf.disappear();
	}, 5000));
};
FileProgress.prototype.setCancelled = function () {
	this.fileProgressElement.className = "progressContainer";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	this.setTimer(setTimeout(function () {
		oSelf.disappear();
	}, 2000));
};
FileProgress.prototype.setStatus = function (status) {
	this.fileProgressElement.childNodes[2].innerHTML = status;
};

// Show/Hide the cancel button
FileProgress.prototype.toggleCancel = function (show, swfUploadInstance) {
	this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
	if (swfUploadInstance) {
		var fileID = this.fileProgressID;
		this.fileProgressElement.childNodes[0].onclick = function () {
			swfUploadInstance.cancelUpload(fileID);
			return false;
		};
	}
};

FileProgress.prototype.appear = function () {
	if (this.getTimer() !== null) {
		clearTimeout(this.getTimer());
		this.setTimer(null);
	}
	
	if (this.fileProgressWrapper.filters) {
		try {
			this.fileProgressWrapper.filters.item("DXImageTransform.Microsoft.Alpha").opacity = 100;
		} catch (e) {
			// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
			this.fileProgressWrapper.style.filter = "progid:DXImageTransform.Microsoft.Alpha(opacity=100)";
		}
	} else {
		this.fileProgressWrapper.style.opacity = 1;
	}
		
	this.fileProgressWrapper.style.height = "";
	
	this.height = this.fileProgressWrapper.offsetHeight;
	this.opacity = 100;
	this.fileProgressWrapper.style.display = "";
	
};

// Fades out and clips away the FileProgress box.
FileProgress.prototype.disappear = function () {

	var reduceOpacityBy = 15;
	var reduceHeightBy = 4;
	var rate = 30;	// 15 fps

	if (this.opacity > 0) {
		this.opacity -= reduceOpacityBy;
		if (this.opacity < 0) {
			this.opacity = 0;
		}

		if (this.fileProgressWrapper.filters) {
			try {
				this.fileProgressWrapper.filters.item("DXImageTransform.Microsoft.Alpha").opacity = this.opacity;
			} catch (e) {
				// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
				this.fileProgressWrapper.style.filter = "progid:DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")";
			}
		} else {
			this.fileProgressWrapper.style.opacity = this.opacity / 100;
		}
	}

	if (this.height > 0) {
		this.height -= reduceHeightBy;
		if (this.height < 0) {
			this.height = 0;
		}

		this.fileProgressWrapper.style.height = this.height + "px";
	}

	if (this.height > 0 || this.opacity > 0) {
		var oSelf = this;
		this.setTimer(setTimeout(function () {
			oSelf.disappear();
		}, rate));
	} else {
		this.fileProgressWrapper.style.display = "none";
		this.setTimer(null);
	}
};

/* Demo Note:  This demo uses a FileProgress class that handles the UI for displaying the file name and percent complete.
The FileProgress class is not part of SWFUpload.
*/


/* **********************
   Event Handlers
   These are my custom event handlers to make my
   web application behave the way I went when SWFUpload
   completes different tasks.  These aren't part of the SWFUpload
   package.  They are part of my application.  Without these none
   of the actions SWFUpload makes will show up in my application.
   ********************** */
function fileQueued(file) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("Várakozik...");
		progress.toggleCancel(true, this);

	} catch (ex) {
		this.debug(ex);
	}

}

function fileQueueError(file, errorCode, message) {
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			progress.setStatus("File is too big.");
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			progress.setStatus("Cannot upload Zero Byte files.");
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			progress.setStatus("Invalid File Type.");
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		default:
			if (file !== null) {
				progress.setStatus("Unhandled Error");
			}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		if (numFilesSelected > 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = false;
		}
		
		/* I want auto start the upload and I can do that here */
		this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}

function uploadStart(file) {
	try {
		/* I don't want to do any file validation or anything,  I'll just update the UI and
		return true to indicate that the upload should start.
		It's important to update the UI here because in Linux no uploadProgress events are called. The best
		we can do is say we are uploading.
		 */
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("Feltöltés...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {}
	
	return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal) {
	try {
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setProgress(percent);
		progress.setStatus("Feltöltés...");
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadSuccess(file, serverData) 
{
	try 
	{
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("Kész.");
		progress.toggleCancel(false);
		// TODO A kép betöltése a képlistába AJAX-al
//		KeresekAPI.loadUploadedImages(this.customSettings.imageTargetID,
//			this.customSettings.sessionID,
//			"hu");
		new Ajax.Updater(this.customSettings.imageTargetID,'/ajax.php', 
		{
			parameters: 'a=uploaded_images&language=hu&session='+this.customSettings.sessionID,
		  	method: 'post'
		});
	} 
	catch (ex) 
	{
		this.debug(ex);
	}
}

function uploadError(file, errorCode, message) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			progress.setStatus("Upload Error: " + message);
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			progress.setStatus("Upload Failed.");
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			progress.setStatus("Server (IO) Error");
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			progress.setStatus("Security Error");
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			progress.setStatus("Upload limit exceeded.");
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			progress.setStatus("Failed Validation.  Upload skipped.");
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			// If there aren't any files left (they were all cancelled) disable the cancel button
			if (this.getStats().files_queued === 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			progress.setStatus("Cancelled");
			progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			progress.setStatus("Stopped");
			break;
		default:
			progress.setStatus("Unhandled Error: " + errorCode);
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function uploadComplete(file) 
{
	if (this.getStats().files_queued === 0) 
	{
		document.getElementById(this.customSettings.cancelButtonId).disabled = true;
	}
}

// This event comes from the Queue Plugin
function queueComplete(numFilesUploaded) {
	var status = document.getElementById("swfupload_status");
	// status.innerHTML = numFilesUploaded + " file" + (numFilesUploaded === 1 ? "" : "s") + " uploaded.";
	status.innerHTML = numFilesUploaded + " kép feltöltve.";
}

function initUpload(sid)
{
	// Az SWFUpload objektumának definiálása
	var swfu = new SWFUpload({
		flash_url : "/js/swfupload/flash/swfupload.swf",
		upload_url: "/upload.php",
		file_post_name : "Filedata",
		post_params: 
		{
			"PHPSESSID" : sid
		},
		file_size_limit : "100 MB",
		file_types : "*.jpg;*.gif;*.png",
		file_types_description : "Minden kép",
		file_upload_limit : 100,
		file_queue_limit : 0,
		custom_settings : 
		{
			progressTarget : "swfupload_progress",
			cancelButtonId : "swfupload_cancelall",
			imageTargetID : "swfupload_images",
			sessionID : sid
		},
		debug: false,

		// Button settings
		button_image_url: "/images/swfupload/swfupload_button.gif",
		button_width: "160",
		button_height: "22",
		button_placeholder_id: "swfupload_browse",
		button_text: '<span class="swfupload_browse">Képek feltöltése...</span>',
		button_text_style: ".swfupload_browse {color:#000;cursor:pointer;font-family:Verdana,'Trebuchet MS',Lucida,Arial;font-size:12px;}",
		button_text_left_padding: 12,
		button_text_top_padding: 3,
		
		// The event handler functions are defined in handlers.js
		file_queued_handler : fileQueued,
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,
		queue_complete_handler : queueComplete,	// Queue plugin event
	});
}

function loadUploadedImages(target_domid, session_id)
{
	$(target_domid).innerHTML = '<div class="loading">Betöltés...</div>';
	new Ajax.Request('/ajax.php', 
	{
		parameters: 'a=images&todo=load&sid='+session_id,
	  	method: 'post',
		evalScripts: true,
	  	onSuccess: function(response) 
	  	{
	    	// A visszakapott HTML beírása a cél DOM elembe
			$(target_domid).innerHTML = response.responseText;
	  	}
	});
}

function deleteUploadedImage(target_domid, session_id, image) 
{
	if (confirm('Biztos törölni szeretné a képet?')) 
	{
		// AJAX-al töröljük a képet
		new Ajax.Request('/ajax.php', {
			parameters: 'a=images&todo=delete&image=' + image,
			method: 'post',
			evalScripts: true,
			onSuccess: function(response){
				// Majd újratöltjük a képek listáját
				loadUploadedImages(target_domid, session_id);
			}
		});
	}
}

function setDefaultUploadedImage(image, normal_class, default_class) 
{
	img = $('img_'+image);
	radio = $('defimg_'+image);
	
	if (img!=null)
	{
		// Az alapértelmezett RADIO átállítása
		radio.checked = true;
		// Az összes kép stílusának átállítása normálra
		var images = $$('.'+default_class);
		images.each(function(i) {$(i.className=normal_class);});
		// A kiválasztott kép stílusának átállítása
		img.className = default_class;
	}
}