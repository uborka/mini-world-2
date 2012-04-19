<?php
/*
 * PTI WPM - Web Page Motor 3.0
 * Copyright (c) 2010; PTI Kft.
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
Core::insert_script("/plugins/rss_comb/js/jquery.rss.js");
// A modulok definiálása a beilleszthetőségükhöz
Core::$PLUGINS['moduls']['rss_comb'] = "RssComb::modul_rss_comb";
// Az előfuttatási funkciók beállítása
// Az utófuttatási funkciók beállítása
Core::insert_postfunction("core_set_content","RssComb","after_core_set_content");
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A CMS-kez készített modulok definiálása
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A menü kibővítése
	CMS::insert_cms_menu("ADDONS","RSS_COMB","Hírtallozó");
	// A menüpontokhoz elérhető jogok
	CMS::insert_cms_right("RSS_COMB",array("access"=>"Hozzáférés","edit"=>"Szerkesztés","delete"=>"Törlés","new"=>"Új"));
	// Felsorolások definiálása
	CMS::insert_cms_enumeration("RSS_COMB","OPTION_ENABLED",
		array("0"=>"Nem engedélyezett","1"=>"Engedélyezett"));
		/*
		CMS::insert_cms_enumeration("RSS_COMB","BEARING",
		array("1"=>"1 - legalacsonyabb","2"=>"2","3"=>"3","4"=>"4","5"=>"5 - közepes", "6"=>"6","7"=>"7","8"=>"8","9"=>"9 - legnagyobb"));
		*/
	// A listák definiálása
	CMS::insert_cms_list("RSS_COMB",
		"Hírtallozó",
		"CmsList",
		"rss_comb",
		array("id","source","option_enabled"),
		array("id","text","enum"),
		array("Kulcs","Forrás","Engedélyezve"),
		"id",
		"asc",
		array(
			array("right"=>"new",
				  "icon"=>"/plugins/cms/templates/images/icon_new.gif",
				  "title"=>"Új...",
				  "url"=>"?a=cmseditor&todo=new&m=RSS_COMB"),
		));

	// A szerkesztők definiálása
	CMS::insert_cms_editor("RSS_COMB",
		"Hírtallozó szerkesztő",
		"CmsEditor",
		"rss_comb",
		TRUE,
		array(
			array("title"=>"Forrás",					"name"=>"editor1",	"field"=>"source",				"type"=>"editor_text",	"default"=>"","maxlength"=>256),
			array("title"=>"Engedélyezve",				"name"=>"editor2",	"field"=>"option_enabled",		"type"=>"radio",		"default"=>1),
			array("title"=>"CMS napló",					"name"=>"editor99",									"type"=>"label_cms"),
		));
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","rss_comb/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>