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

$time_before = microtime();
// A modulok definiálása a beilleszthetőségükhöz
Core::$PLUGINS['moduls']['sm_form'] = "SendMessage::modul_sm_form";
Core::$PLUGINS['moduls']['sm_sendmessage'] = "SendMessage::modul_sm_sendmessage";
// Az előfuttatási funkciók beállítása
// Az utófuttatási funkciók beállítása
Core::insert_postfunction("core_set_content","SendMessage","after_core_set_content");
Core::insert_postfunction("site_load_mains","SendMessage","after_site_load_mains");
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
//	Core::insert_script("/plugins/contract_management/js/jquery.cm.js");
	// A CMS-kez készített modulok definiálása
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A menü kibővítése
//	Core::$CMS["MENUS"]["HOME"]["items"]["NEWS"] = "Hírek";
	// A menüpontokhoz elérhető jogok
//	Core::$CMS["RIGHTS"]["NEWS"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új");
	// Felsorolások definiálása
	Core::$CMS["ENUMERATIONS"]["USER.OPTION_EMAIL_FROM_SENDMESSAGE"] = array("1"=>"IGEN","0"=>"Nem");
	// A listák definiálása
//	Core::$CMS["LISTS"]["NEWS"] = array(
//	  		"title" => "Hírek",
//	  		"class" => "CmsList",
//	  		"table" => "news_news",
//			"fields" => array("id","title","date_create"),
//			"types" => array("id","text","date"),
//			"headers" => array("Kulcs","Cím","Dátum"),
//			"default_field" => "date_create",
//			"default_direction" => "desc",
//			"extra_commands" => array(
//				array("right"=>"new","icon"=>"/plugins/fuge2/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=NEWS&todo=new"),
//				));
	// A szerkesztők definiálása
	Cms::insert_cms_editor_row("USER",array("title"=>"E-mail értesítő: üzenetek","name"=>"editor31","field"=>"option_email_from_sendmessage","type"=>"radio","default"=>0));
//	Core::$CMS["EDITORS"]["NEWS"] = array(
//			"title" => "Hír szerkesztő",
//			"class" => "CmsEditor",
//			"clone_enabled" => TRUE,
//			"table" => "news_news",
//			"rows" => array(
//				array("title"=>"Cím",					"name"=>"editor1",	"field"=>"title",		"type"=>"editor_text",		"default"=>"nevenincs","maxlength"=>128),
//				array("title"=>"Forrás (megnevezés)",	"name"=>"editor2",	"field"=>"source",		"type"=>"editor_text",		"default"=>"","maxlength"=>128),
//				array("title"=>"Forrás (URL)",			"name"=>"editor3",	"field"=>"source_url",	"type"=>"editor_text",		"default"=>"","maxlength"=>256),
//				array("title"=>"Rövid leírás",			"name"=>"editor4",	"field"=>"quote",		"type"=>"editor_memo",		"default"=>"","maxlength"=>512,"row"=>5),
//				array("title"=>"Leírás",				"name"=>"editor5",	"field"=>"description",	"type"=>"editor_ckeditor",	"default"=>"","toolbar"=>"Full"),
//				array("title"=>"CMS napló",				"name"=>"editor99",							"type"=>"label_cms"),
//				)
//			);
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","sendmessage/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>