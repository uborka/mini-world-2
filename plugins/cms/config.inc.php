<?php
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

// Az oldal konfigurációs állományának betöltése
include($_SERVER["DOCUMENT_ROOT"]."/config.inc.php");
// A CMS-hez szükséges extra fájlok bellítása
Core::$PLUGINS['autoload'][] = Core::$DOCROOT.Core::$RELPATH."/plugins/cms";

Core::$SCRIPTS[] = Core::$RELPATH."/js/jquery.ui/jquery-ui-1.8.min.js";
Core::$SCRIPTS[] = Core::$RELPATH."/js/jquery.ui/jquery-ui-i18n.min.js";
Core::$SCRIPTS[] = Core::$RELPATH."/js/jquery.plugins/jquery.cookies.min.js";
Core::$SCRIPTS[] = Core::$RELPATH."/js/jquery.plugins/jquery.notifybar.min.js";

Core::$SCRIPTS[] = Core::$RELPATH."/plugins/cms/js/jquery.cms.js";
// A CMS konfigurálása
$id = (empty($_SESSION["id"])) ? 0 : $_SESSION["id"];

// A szerkesztő kiegészítése az extra funkciók eléréséhez
CmsEditor::$EDITOR_FUNCTIONS["rightlist"] = "CmsUserGroupEditor::generate_rightlist";

Core::$CMS = array(
	"MENUS" => array(
	  	// Az oldal tartalmi részéhez kapcsolódó menük
		"HOME" => array(
			"title"=>"Tartalom",
			"items"=>array(
				"PAGE"=>"Oldalak")
			),
		"USERS" => array(
			"title"=>"Felhasználók",
			"items"=>array(
				"USER"=>array("title"=>"Felhasználók","tip"=>"A felhasználók blokkolása, adatainak módosítása, értsítők beállítása."),
				"USER_GROUP"=>"Felhasználói csoportok")
		),
	  	// A rendszerrel kapcsolatos menük
	  	"ADDONS" => array(
			"title"=>"Kiegészítők",
			"items"=>array(
				"PLUGIN"=>"Bővítmények",
				"THEME"=>"Sablonok",
				)
			),
		"SYSTEM" => array(
			"title"=>"Rendszer",
			"items"=>array(
				"PARAM"=>"Beállítások",
				"LANG"=>"Nyelvi címkék",
				"FILEBROWSER"=>array("title"=>"Fájlböngésző","url"=>Core::$HTTP_HOST."/admin/?a=cmsbrowser&m=FILEBROWSER"),
				"LOG"=>"Naplók")
			)
		),
	// Az egyes menüpontokhoz elérhető jogok
	"RIGHTS" => array(
		"PAGE" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"),
		"USER" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új","email"=>"E-mail küldés"),
		"USER_GROUP" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"),
		"PLUGIN" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés"),
		"THEME" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"),
		"PARAM" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"),
		"LANG" => array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"),
		"FILEBROWSER" => array("access"=>"Hozzáférés"),
		"LOG" => array("access"=>"Hozzáférés"),
	),
	// A rendszerben előforduló megszámlálások értelmezése illetve, megnevezései
	"ENUMERATIONS" => array(
	  	// PAGES
	  	"PAGE.BEARING" => array("1"=>"1 - alacsony","2"=>"2","3"=>"3","4"=>"4","5"=>"5 - közepes","6"=>"6","7"=>"7","8"=>"8","9"=>"9 - magas"),
		"PAGE.OPTION_MAINMENU" => array("0" => "Nem jelenik meg","1" => "MEGJELENIK"),
		"PAGE.OPTION_SUBMENU" => array("0" => "Nem jelenik meg","1" => "MEGJELENIK"),
		"PAGE.OPTION_BASEMENU" => array("0" => "Nem jelenik meg","1" => "MEGJELENIK"),
		// PLUGINS
		"PLUGIN.OPTION_ACTIVE" => array("0" => "Nincs aktiválva", "1" => "AKTÍV"),
		// USER_GROUPS
		"USER_GROUP.IS_ADMIN" => array("1"=>"VAN","0"=>"Nincs"),
		// USERS
		"USER.OPTION_BANNED" => array("1"=>"IGEN","0"=>"Nem"),
		"USER.OPTION_NEWSLETTER" => array("1"=>"Feliratkozva","0"=>"NINCS FELIRATKOZVA"),
		"USER.OPTION_EMAIL_FROM_REGISTRATION" => array("1"=>"IGEN","0"=>"Nem"),
		),
	// Az admin felület listáinak konfigurálása.
	"LISTS" => array(
		"LANG" => array(
	  		"title" => "Nyelvi cimkék",
	  		"class" => "CmsList",
	  		"table" => "core_lang",
			"fields" => array("id","name","value"),
			"types" => array("id","text","text"),
			"headers" => array("Kulcs","Név","Érték"),
			"default_field" => "name",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=LANG&todo=new"),
				)),
		"LOG" => array(
			"title" => "Naplók",
			),
	  	"PAGE" => array(
	  		"title" => "Oldalak",
	  		"class" => "CmsList",
	  		"table" => "core_page",
			"fields" => array("id","title","date_modify","option_basemenu","is_system"),
			"types" => array("id","text","date","enum","is_system"),
			"headers" => array("Kulcs","Cím","Módosítva","Alapmenü"),
			"default_field" => "id",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=PAGE&todo=new"),
				)),
	  	"PARAM" => array(
	  		"title" => "Beállítások",
	  		"class" => "CmsParams",
	  		"table" => "core_param",
	  		"tabs" => array(
	  			"general" => array(
	  				"title"=>"Általános",
	  				"template"=>"/plugins/cms/templates/cmsparams_general.html"
				)),
			),
		"PLUGIN" => array(
			"title" => "Bővítmények",
	  		"class" => "CmsPlugins",
	  		"table" => "core_plugin",
			),
		"THEME" => array(
	  		"title" => "Sablonok",
	  		"class" => "CmsList",
	  		"table" => "core_theme",
			"fields" => array("id","title","template_path","date_modify","is_system"),
			"types" => array("id","text","text","date","is_system"),
			"headers" => array("Kulcs","Megnevezés","URL","Utolsó módosítás"),
			"default_field" => "title",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=THEME&todo=new"),
				)),
		"USER_GROUP" => array(
	  		"title" => "Felhasználói csoportok",
	  		"class" => "CmsList",
	  		"table" => "core_user_group",
			"fields" => array("id","title","is_admin","is_system"),
			"types" => array("id","text","enum","is_system"),
			"headers" => array("Kulcs","Megnevezés","CMS hozzáférés"),
			"default_field" => "title",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&todo=new&m=USER_GROUP"),
				)),
		"USER" => array(
	  		"title" => "Felhasználók",
	  		"class" => "CmsList",
	  		"table" => "core_user",
			"fields" => array("id","username","fullname","email","phone","date_last_login","user_group_id"),
			"types" => array("id","text","text","email","text","date","included"),
			"headers" => array("Kulcs","Azonosító","Név","E-mail","Telefon","Utolsó belépés","Csoport"),
			"default_field" => "username",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&todo=new&m=USER"),
				)),
		),
	// Az admin felület szerkesztőinek konfigurálása.
	"EDITORS" => array(
		// "LOGS"
		"LANG" => array(
			"title" => "Nyelvi cimke szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => FALSE,
			"table" => "core_lang",
			"rows" => array(
				array("title"=>"Azonosító",	"name"=>"editor1",	"field"=>"name",	"type"=>"editor_text",	"default"=>"noname","maxlength"=>64),
				array("title"=>"Érték",		"name"=>"editor2",	"field"=>"value",	"type"=>"editor_memo",	"default"=>"","maxlength"=>1024,"rownums"=>3),
				array("title"=>"CMS napló",	"name"=>"editor99",						"type"=>"label_cms"),
				)
			),
		"PAGE" => array(
			"title" => "Oldalszerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => TRUE,
			"table" => "core_page",
			"rows" => array(
				array("title"=>"Megnevezés",				"name"=>"editor1",	"field"=>"title",				"type"=>"editor_text",		"default"=>"nevenincs","maxlength"=>64),
				array("title"=>"Sablon",					"name"=>"editor2",	"field"=>"theme_id",			"type"=>"select_by_id",		"default"=>1,"table"=>"core_theme"),
				array("title"=>"Ősoldal",					"name"=>"editor3",	"field"=>"parent_id",			"type"=>"select_parent",	"default"=>0,"table"=>"core_page"),
				array("title"=>"Kép",						"name"=>"editor4",	"field"=>"image",				"type"=>"editor_image",		"default"=>"/images/spacer.gif","maxlength"=>512),
				array("title"=>"Idézet",					"name"=>"editor5",	"field"=>"quote",				"type"=>"editor_memo",		"default"=>"","maxlength"=>512,"rownums"=>3),
				array("title"=>"Leírás",					"name"=>"editor6",	"field"=>"description",			"type"=>"editor_ckeditor",	"default"=>"","toolbar"=>"Editor"),
				array("title"=>"Ugrópont",					"name"=>"editor7",	"field"=>"link",				"type"=>"editor_text",		"default"=>"","maxlength"=>128),
				array("title"=>"Súly",						"name"=>"editor8",	"field"=>"bearing",				"type"=>"radio",			"default"=>"5"),
				array("title"=>"Hivatkozott oldalak",		"name"=>"editor9",	"field"=>"refered_page_ids",	"type"=>"dnd",				"default"=>"","table"=>"core_page"),
//				array("title"=>"Főmenüben megjelenik",		"name"=>"editor10",	"field"=>"option_mainmenu",		"type"=>"radio",			"default"=>0),
//				array("title"=>"Almenüben megjelenik",		"name"=>"editor11",	"field"=>"option_submenu",		"type"=>"radio",			"default"=>0),
				array("title"=>"Alapmenüben megjelenik",	"name"=>"editor12",	"field"=>"option_basemenu",		"type"=>"radio",			"default"=>0),
				array("title"=>"SEO Permalink",				"name"=>"editor13",	"field"=>"permalink",			"type"=>"editor_text",		"default"=>"","maxlength"=>512),
				array("title"=>"SEO META kulcsszavak",		"name"=>"editor14",	"field"=>"meta_keywords",		"type"=>"editor_memo",		"default"=>"","maxlenth"=>256,"rownums"=>3),
				array("title"=>"SEO META leírás",			"name"=>"editor15",	"field"=>"meta_description",	"type"=>"editor_memo",		"default"=>"","maxlength"=>156,"rownums"=>3),
				array("title"=>"CMS napló",					"name"=>"editor99",									"type"=>"label_cms"),
				)
			),
		"PARAM" => array(
			"title" => "Beállítás",
			"class" => "CmsEditor",
			"clone_enabled" => FALSE,
			"table" => "core_param",
			"rows" => array(
				array("title"=>"Azonosító",	"name"=>"editor1",	"field"=>"name",		"type"=>"label",		"default"=>"nevenincs"),
				array("title"=>"Leírás",	"name"=>"editor2",	"field"=>"description",	"type"=>"label",		"default"=>"nincs leírás"),
				array("title"=>"Érték",		"name"=>"editor3",	"field"=>"value",		"type"=>"editor_text",	"default"=>"","maxlength"=>256,),
				array("title"=>"CMS napló",	"name"=>"editor99",							"type"=>"label_cms"),
				)
			),
		"THEME" => array(
			"title" => "Beállítás szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => TRUE,
			"table" => "core_theme",
			"rows" => array(
		  		array("title"=>"Megnevezés",		"name"=>"editor1",	"field"=>"title",			"type"=>"editor_text",	"default"=>"noname","maxlength"=>32),
				array("title"=>"Sablon útvonala",	"name"=>"editor2",	"field"=>"template_path",	"type"=>"editor_text",	"default"=>"/items/templates/","maxlength"=>512),
				array("title"=>"CMS napló",			"name"=>"editor99",								"type"=>"label_cms"),
				)
			),
	  	"USER_GROUP" => array(
	  		"title" => "Felhasználói csoport szerkesztő",
			"class" => "CmsUserGroupEditor",
			"clone_enabled" => FALSE,
			"table" => "core_user_group",
			"rows" => array(
				array("title"=>"Megnevezés",	"name"=>"editor1",	"field"=>"title",	"type"=>"editor_text",	"default"=>"nevenincs","maxlength"=>32),
				array("title"=>"CMS hozzáférés","name"=>"editor2",	"field"=>"is_admin","type"=>"radio",		"default"=>0),
				array("title"=>"Jogok",			"name"=>"editor3",						"type"=>"rightlist"),
				array("title"=>"CMS napló",		"name"=>"editor99",						"type"=>"label_cms"),
				),
			"after_update" => "CmsUserGroupEditor::after_userdata_update"
			),
		"USER" => array(
			"title" => "Felhasználó szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => FALSE,
			"table" => "core_user",
			"rows" => array(
				array("title"=>"Kulcs",								"name"=>"editor1",	"field"=>"id",								"type"=>"label",			"default"=>"NULL"),
				array("title"=>"Azonosító",							"name"=>"editor2",	"field"=>"username",						"type"=>"editor_text",		"default"=>"nevenics","maxlength"=>32),
				array("title"=>"Jelszó",							"name"=>"editor3",	"field"=>"password",						"type"=>"editor_password",	"default"=>"","maxlength"=>32),
				array("title"=>"Név",								"name"=>"editor4",	"field"=>"fullname",						"type"=>"editor_text",		"default"=>"nevenincs","maxlength"=>64),
				array("title"=>"E-mail cím",						"name"=>"editor6",	"field"=>"email",							"type"=>"editor_text",		"default"=>"","maxlength"=>128),
				array("title"=>"Csoport",							"name"=>"editor7",	"field"=>"user_group_id",					"type"=>"select_by_id",		"default"=>1,"table"=>"core_user_group"),
//				array("title"=>"Telefon",							"name"=>"editor8",	"field"=>"phone",							"type"=>"editor_text",		"default"=>"-","maxlength"=>16),
//				array("title"=>"Cégnév",							"name"=>"editor9",	"field"=>"company",							"type"=>"editor_text",		"default"=>"nincs cégadat","maxlength"=>128),
//				array("title"=>"Adószám",							"name"=>"editor10",	"field"=>"taxnumber",						"type"=>"editor_text",		"default"=>"N/A","maxlength"=>128),
//				array("title"=>"Fax",								"name"=>"editor11",	"field"=>"fax",								"type"=>"editor_text",		"default"=>"","maxlength"=>16),
//				array("title"=>"Postacím",							"name"=>"editor12",	"field"=>"address",							"type"=>"editor_memo",		"default"=>"","maxlength"=>256,"rows"=>3),
				array("title"=>"Blokkolt felhasználó",				"name"=>"editor13",	"field"=>"option_banned",					"type"=>"radio",			"default"=>0),
				array("title"=>"Hírlevélre feliratkozva",			"name"=>"editor14",	"field"=>"option_newsletter",				"type"=>"radio",			"default"=>0),
				array("title"=>"E-mail értesítő: regisztrációról",	"name"=>"editor15",	"field"=>"option_email_from_registration",	"type"=>"radio",			"default"=>0),
				array("title"=>"Regisztráció dátuma",				"name"=>"editor16",	"field"=>"date_registered",					"type"=>"label_date"),
				array("title"=>"Utolsó belépés dátuma",				"name"=>"editor17",	"field"=>"date_last_login",					"type"=>"label_date"),
				array("title"=>"CMS napló",							"name"=>"editor99",												"type"=>"label_cms"),
				),
			"after_update" => "CmsUserGroupEditor::after_userdata_update"
			),
		)
	);
?>