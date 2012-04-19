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
Core::$PLUGINS['moduls']['mw_user'] = "MiniWorld::modul_mw_user";
Core::$PLUGINS['moduls']['mw_categories'] = "MWCategory::modul_mw_categories";
Core::$PLUGINS['moduls']['mw_image_category_select'] = "MWCategory::modul_mw_image_category_select";
Core::$PLUGINS['moduls']['mw_album_category'] = "MWAlbum::modul_mw_album_category";
Core::$PLUGINS['moduls']['mw_album_user'] = "MWAlbum::modul_mw_album_user";
Core::$PLUGINS['moduls']['mw_album_user_by_category'] = "MWAlbum::modul_mw_album_user_by_category";
Core::$PLUGINS['moduls']['mw_album_new_minis'] = "MWAlbum::modul_mw_album_new_minis";
Core::$PLUGINS['moduls']['mw_album_top_minis'] = "MWAlbum::modul_mw_album_top_minis";
Core::$PLUGINS['moduls']['mw_image'] = "MWImage::modul_mw_image";
Core::$PLUGINS['moduls']['mw_image_details'] = "MWImage::modul_mw_image_details";
Core::$PLUGINS['moduls']['mw_image_editor'] = "MWImage::modul_mw_image_editor";
Core::$PLUGINS['moduls']['mw_image_upload'] = "MWImage::modul_mw_image_upload";
Core::$PLUGINS['moduls']['mw_image_delete'] = "MWImage::modul_mw_image_delete";
Core::$PLUGINS['moduls']['mw_image_download'] = "MWImage::modul_mw_image_download";
// Az előfuttatási funkciók beállítása
Core::insert_prefunction("core_set_content","MiniWorld","before_core_set_content");
// Az utófuttatási funkciók beállítása
Core::insert_postfunction("user_init","MiniWorld","after_user_init");
Core::insert_postfunction("site_permalink","MiniWorld","after_site_permalink");
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A CMS-kez készített modulok definiálása
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A menü kibővítése
	Core::$CMS["MENUS"]["MINIWORLD"] = array(
		"title"=>"Mini-World",
		"items"=>array(
			"MW_CATEGORY"=>"Kategóriák",
			"MW_IMAGE"=>"Képek",
			"MW_IMAGE_UNSEEN"=>"Átnézésre váró képek"
		)
	);
	// A menüpontokhoz elérhető jogok
	Core::$CMS["RIGHTS"]["MW_CATEGORY"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés","new"=>"Új");
	Core::$CMS["RIGHTS"]["MW_IMAGE"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés");
	Core::$CMS["RIGHTS"]["MW_IMAGE_UNSEEN"] = array("access"=>"Hozzáférés","edit"=>"Szerkesztés");
	// Felsorolások definiálása
	Core::$CMS["ENUMERATIONS"]["MW_IMAGE.OPTION_ACCEPTED"] = array(1=>"Elfogadott",0=>"MODERÁLT");
	// A listák definiálása
	Core::$CMS["LISTS"]["MW_CATEGORY"] = array(
	  		"title" => "Kategóriák",
	  		"class" => "CmsList",
	  		"table" => "mw_category",
			"fields" => array("id","title"),
			"types" => array("id","text"),
			"headers" => array("Kulcs","Cím"),
			"default_field" => "id",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=MW_CATEGORY&todo=new"),
				));
	Core::$CMS["LISTS"]["MW_IMAGE"] = array(
	  		"title" => "Képek",
	  		"class" => "CmsList",
	  		"table" => "mw_image",
			"fields" => array("id","user_id","title"),
			"types" => array("id","user","text"),
			"headers" => array("Kulcs","Alkotó","Cím"),
			"default_field" => "id",
			"default_direction" => "asc",
			"extra_commands" => array(
				array("right"=>"new","icon"=>"/plugins/cms/templates/images/icon_new.gif","title"=>"Új...","url"=>"?a=cmseditor&m=MW_IMAGE&todo=new"),
				));
	// A szerkesztők definiálása
	Core::$CMS["EDITORS"]["MW_CATEGORY"] = array(
			"title" => "Kategória szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => TRUE,
			"table" => "mw_category",
			"rows" => array(
				array("title"=>"Megnevezés",				"name"=>"editor1",	"field"=>"title",				"type"=>"editor_text",		"default"=>"nevenincs","maxlength"=>64),
				array("title"=>"SEO Permalink",				"name"=>"editor2",	"field"=>"permalink",			"type"=>"editor_text",		"default"=>"","maxlength"=>64),
//				array("title"=>"SEO META kulcsszavak",		"name"=>"editor3",	"field"=>"meta_keywords",		"type"=>"editor_memo",		"default"=>"","maxlenth"=>256,"rownums"=>3),
//				array("title"=>"SEO META leírás",			"name"=>"editor4",	"field"=>"meta_description",	"type"=>"editor_memo",		"default"=>"","maxlength"=>156,"rownums"=>3),
				array("title"=>"CMS napló",					"name"=>"editor99",									"type"=>"label_cms"),
				)
			);
	Core::$CMS["EDITORS"]["MW_IMAGE"] = array(
			"title" => "Kép szerkesztő",
			"class" => "CmsEditor",
			"clone_enabled" => FALSE,
			"table" => "mw_image",
			"rows" => array(
				array("title"=>"Alkotó",		"name"=>"editor1",	"field"=>"user_id",				"type"=>"label_user",		"default"=>"nevenincs","maxlength"=>64),
				array("title"=>"Kategória",		"name"=>"editor2",	"field"=>"mw_category_id",		"type"=>"select_by_id",		"default"=>5,"table"=>"mw_category"),
				array("title"=>"Megnevezés",	"name"=>"editor3",	"field"=>"title",				"type"=>"editor_text",		"default"=>"nevenincs","maxlength"=>128),
				array("title"=>"Leírás",		"name"=>"editor4",	"field"=>"desc",				"type"=>"editor_memo",		"default"=>"","maxlength"=>2048),
				array("title"=>"Kép",			"name"=>"editor5",	"field"=>"image_url",			"type"=>"editor_image",		"default"=>"","maxlength"=>512),
				array("title"=>"Permalink",		"name"=>"editor6",	"field"=>"permalink",			"type"=>"editor_text",		"default"=>"","maxlength"=>32),
				array("title"=>"Elfogadva",		"name"=>"editor7",	"field"=>"option_accepted",		"type"=>"radio",			"default"=>"N"),
//				array("title"=>"SEO META kulcsszavak",		"name"=>"editor3",	"field"=>"meta_keywords",		"type"=>"editor_memo",		"default"=>"","maxlenth"=>256,"rownums"=>3),
//				array("title"=>"SEO META leírás",			"name"=>"editor4",	"field"=>"meta_description",	"type"=>"editor_memo",		"default"=>"","maxlength"=>156,"rownums"=>3),
				array("title"=>"CMS napló",		"name"=>"editor99",									"type"=>"label_cms"),
				)
			);
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","miniworld/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>
