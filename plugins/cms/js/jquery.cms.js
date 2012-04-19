<!--
/*
 * FÜGE² - Tartalomkezelés - Webáruház - Ügyviteli rendszer
 * Copyright (C) 2008-2010; PTI Kft.
 * http://www.pti.hu
 *
 * Ez a programkönyvtár szabad szoftver; terjeszthető illetve módosítható a
 * Free Software Foundation által kiadott GNU Lesser General Public License
 * dokumentumban leírtak, akár a licenc 2.1-es, akár (tetszőleges) későbbi
 * változata szerint.
 *
 * Ez a programkönyvtár abban a reményben kerül közreadásra, hogy hasznos lesz,
 * de minden egyéb GARANCIA NÉLKÜL, az ELADHATÓSÁGRA vagy VALAMELY CÉLRA VALÓ
 * ALKALMAZHATÓSÁGRA való származtatott garanciát is beleértve. További
 * részleteket a GNU Lesser General Public License tartalmaz.
 *
 * A felhasználónak a programmal együtt meg kell kapnia a GNU Lesser
 * General Public License egy példányát; ha mégsem kapta meg, akkor
 * ezt a Free Software Foundationnak küldött levélben jelezze
 * (cím: Free Software Foundation Inc., 59 Temple Place, Suite 330,
 * Boston, MA 02111-1307, USA.)
 */

var _returnField = null;

jQuery.fn.extend({
	ptiSendForm : function(form_selector) {
		if ($(form_selector) != null) {
			this.load("/ajax.php?"+$(form_selector).serialize());
		}
	},
	ptiBrowseServer: function() {
		_returnField = this;

		var finder = new CKFinder() ;
		finder.basePath = '/include/ckfinder' ;
		finder.selectActionFunction = function(fileUrl, data) {
			if (_returnField != null) {
				$(_returnField).val(fileUrl);
			}
		};
		finder.popup();
	},
	ptiShowDialog: function(url,dlg_title) {
		var _api = $(this).dialog({
			autoOpen:true,
		  	modal:true,
		  	draggable:true,
		  	height:'auto',
		  	width:640,
		  	resizable:false,
		  	closeOnEscape:false,
		  	title:dlg_title,
		  	show:'drop',
		  	hide:'drop',
		  	buttons:{
		  		'OK': function() {
		  			var form = _api.find('form');
		  			if (form.length > 0) {
		  				$(_api).ptiSendForm('#'+form[0].id);
					}
		  			else {
		  				_api.dialog('close');
		  			}
		  		},
				'Mégsem': function() {
		  			_api.dialog('close');
				}
			},
			close: function() {
				$(this).dialog('destroy');
			}
		}).load(url);
		return false;
	},
	ptiExpander: function() {
		$.each(this, function(index, element) {
			var cookie = $.jCookie("pti-expander-"+this.id);
			var domid = "#" + $(this).attr("rel");
			if (cookie == 1) {
				// Ha van ilyen süti, akkor ezt a menüt kibontjuk
				$(domid).show();
				$(this).html("-");
			} else {
				// Ha nincs ilyen süti. akkor elrejtjük a menüt
				$(domid).hide();
				$(this).html("+");
			}
			$(this).click(function(){
				var domid = "#" + $(this).attr("rel");
				if ($(this).html() == "+") {
					// A tartalom megjelenítése
					$(domid).show("blind");
					$(this).html("-");
					$.jCookie("pti-expander-"+this.id,1);
				} else {
					// A tartalom elrejtése
					$(domid).hide("blind");
					$(this).html("+");
					$.jCookie("pti-expander-"+this.id,null);
				}
			});
		});
	}
});
-->
