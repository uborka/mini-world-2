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
// A szükséges CSS-ek betöltése
// A szükséges JavaScript-ek betöltése
// A modulok definiálása a beilleszthetőségükhöz
Core::$PLUGINS['moduls']['bannerbox'] = "BannerBox::modul_bannerbox";
// Az előfuttatási funkciók beállítása
// Az utófuttatási funkciók beállítása
// A statikus munkamenet változók bővítése
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A CMS-kez készített modulok definiálása
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A menü kibővítése
	Core::$CMS["MENUS"]["HOME"]["items"]["BAN_BANNERS"] = "Reklámok (bannerek)";
	// A menüpontokhoz elérhető jogok
	Core::$CMS["RIGHTS"]["BAN_BANNERS"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új");
	// Felsorolások definiálása
	Core::$CMS["ENUMERATIONS"]["BAN_BANNERS.POSITION"] = array(
		1=>"Nyitólap (superbanner)",
		2=>"Nyitólap (full banners)",
		3=>"Nyitólap (leaderboard)",
		4=>"Oldalsáv",
		5=>"Lábléc (támogatók)",
		6=>"Lábléc (partnerek)",
		7=>"Cikk oldalsáv ()");
	Core::$CMS["ENUMERATIONS"]["BAN_BANNERS.OPTION_TYPE"] = array(
		"image"=>"Kép","flash"=>"Adobe Flash");
	Core::$CMS["ENUMERATIONS"]["BAN_BANNERS.BEARING"] =
		array("1"=>"1 - alacsony","2"=>"2","3"=>"3","4"=>"4","5"=>"5 - közepes","6"=>"6","7"=>"7","8"=>"8","9"=>"9 - magas");
	// A listák definiálása
	Core::$CMS["LISTS"]["BAN_BANNERS"] = array(
	  		"title" => "Reklámok",
	  		"class" => "CmsList",
	  		"table" => "ban_banner",
			"fields" => array("id","option_type","title","position","bearing"),
			"types" => array("id","enum","text","enum","int"),
			"headers" => array("Kulcs","Típus","Cím","Pozíció","Súly"),
			"default_field" => "position",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=BAN_BANNERS&todo=new"),
				));
	// A szerkesztő kiegészítése az extra funkciók eléréséhez
	// A szerkesztők definiálása
	Core::$CMS["EDITORS"]["BAN_BANNERS"] = array(
			"title" => "Reklám szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => TRUE,
			"table" => "ban_banner",
			"rows" => array(
				array("title"=>"Típus",						"name"=>"editor1",	"field"=>"option_type",		"type"=>"radio",		"default"=>"image"),
				array("title"=>"Pozíció",					"name"=>"editor2",	"field"=>"position",		"type"=>"radio",		"default"=>4),
				array("title"=>"Megnevezés",				"name"=>"editor3",	"field"=>"title",			"type"=>"editor_text",	"default"=>"nevenincs","maxlength"=>128),
				array("title"=>"Kép",						"name"=>"editor4",	"field"=>"image",			"type"=>"editor_image",	"default"=>"/items/images/spacer.gif","maxlength"=>512),
				array("title"=>"Szélesség (képpont)",		"name"=>"editor5",	"field"=>"width",			"type"=>"editor_int",	"default"=>"0"),
				array("title"=>"Magasság (képpont)",		"name"=>"editor6",	"field"=>"height",			"type"=>"editor_int",	"default"=>"0"),
				array("title"=>"Ugrópont",					"name"=>"editor7",	"field"=>"link",			"type"=>"editor_text",	"default"=>"","maxlength"=>255),
				array("title"=>"Súly",						"name"=>"editor8",	"field"=>"bearing",			"type"=>"radio",		"default"=>5),
				array("title"=>"CMS napló",					"name"=>"editor99",								"type"=>"label_cms"),
				));
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","banners/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>