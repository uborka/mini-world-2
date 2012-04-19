/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	config.language = 'hu';
	config.uiColor = '#eee';
	config.dialog_backgroundCoverColor = '#000';
	config.pasteFromWordIgnoreFontFace = true;
	config.pasteFromWordKeepsStructure = false;
	config.pasteFromWordRemoveStyle = true;
	config.filebrowserBrowseUrl = '/include/ckfinder/ckfinder.html';
	config.filebrowserImageBrowseUrl = '/include/ckfinder/ckfinder.html?Type=images';
	config.filebrowserFlashBrowseUrl = '/include/ckfinder/ckfinder.html?Type=flash';
	config.filebrowserUploadUrl = '/include/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=files';
	config.filebrowserImageUploadUrl = '/include/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=images';
	config.filebrowserFlashUploadUrl = '/include/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=flash';
	
	config.removePlugins = 'scayt';
	
	config.toolbar_Editor =
	[
	    ['Source'],
	    ['Cut','Copy','PasteText','PasteFromWord'],
	    ['Find','Replace','-','SelectAll','RemoveFormat'],
	    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Anchor'],
	    '/',
	    ['Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks','-','About']
	];
	
	config.toolbar_Basic =
	[
	    ['Bold', 'Italic', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
	    ['Cut','Copy','PasteText','PasteFromWord','RemoveFormat'],
	    ['About']
	];
	
	config.toolbar_BasicWImg =
		[
		    ['Bold', 'Italic', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
		    ['Cut','Copy','PasteText','PasteFromWord','RemoveFormat'],
		    ['Image'],
		    ['About']
		];
};
