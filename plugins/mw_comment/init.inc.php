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
Core::$PLUGINS['moduls']['mw_image_comment_nums'] = "MWComment::modul_mw_image_comment_nums";
Core::$PLUGINS['moduls']['mw_image_comments'] = "MWComment::modul_mw_image_comments";
Core::$PLUGINS['moduls']['mw_image_comment_form'] = "MWComment::modul_mw_image_comment_form";
Core::$PLUGINS['moduls']['mw_image_comment'] = "MWComment::modul_mw_image_comment";
Core::$PLUGINS['moduls']['mw_unviewed_comments'] = "MWComment::modul_mw_unviewed_comments";
Core::$PLUGINS['moduls']['mw_image_rate'] = "MWRate::modul_mw_image_rate";
Core::$PLUGINS['moduls']['mw_image_rates'] = "MWRate::modul_mw_image_rates";
Core::$PLUGINS['moduls']['mw_top_categories'] = "MWRate::modul_mw_top_categories";
Core::$PLUGINS['moduls']['mw_album_top_minis'] = "MWRate::modul_mw_album_top_minis";
// Az előfuttatási funkciók beállítása
Core::insert_prefunction("core_set_content","MWComment","before_core_set_content");
Core::insert_prefunction("core_set_content","MWRate","before_core_set_content");
// Az utófuttatási funkciók beállítása
Core::insert_postfunction("site_permalink","MWRate","after_site_permalink");
Core::insert_postfunction("mw_image_init","MWRate","after_mw_image_init");
//Core::insert_postfunction("user_init","MWComment","after_user_init");
Core::insert_postfunction("user_init","MWRate","after_user_init");
Core::insert_postfunction("modul_mw_user","MWRate","after_modul_mw_user");
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A CMS-kez készített modulok definiálása
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A menü kibővítése
	Core::$CMS["MENUS"]["MINIWORLD"]["items"]["MW_RANK"] = "Rangok";
	// A menüpontokhoz elérhető jogok
	Core::$CMS["RIGHTS"]["MW_RANK"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés","new"=>"Új");
	// Felsorolások definiálása
	// A listák definiálása
	Core::$CMS["LISTS"]["MW_RANK"] = array(
	  		"title" => "Kategóriák",
	  		"class" => "CmsList",
	  		"table" => "mw_rank",
			"fields" => array("id","rate","title"),
			"types" => array("id","int","text"),
			"headers" => array("Kulcs","Minimum értékelés","Cím"),
			"default_field" => "id",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=MW_RANK&todo=new"),
				));
	// A szerkesztők definiálása
	Core::$CMS["EDITORS"]["MW_RANK"] = array(
			"title" => "Rang szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => TRUE,
			"table" => "mw_rank",
			"rows" => array(
				array("title"=>"Minimum értékelés",	"name"=>"editor1",	"field"=>"rate",	"type"=>"editor_int",	"default"=>"0"),
				array("title"=>"Megnevezés",		"name"=>"editor2",	"field"=>"title",	"type"=>"editor_text",	"default"=>"nevenincs","maxlength"=>32),
				array("title"=>"CMS napló",			"name"=>"editor99",						"type"=>"label_cms"),
				)
			);
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","mw_comment/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>
