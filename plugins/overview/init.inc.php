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

// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A szükséges CSS-ek betöltése
	Core::$CSS[] = "/plugins/overview/templates/cms.css";
	// A szükséges JavaScript-ek betöltése
	// A CMS-kez készített modulok definiálása
	Core::$PLUGINS['moduls']['overview'] = "Overview::modul_overview";
	// Az utófuttatási funkciók beállítása - a modulok feldolgozásához
	Core::insert_prefunction("core_set_content","Overview","before_core_set_content");
	// Az alapértelmezett áttekintő konfigurálása
	Overview::$BOXES = array(
		"left" => array(
//			"orders" => array(
//				"title" => "Megrendelések",
//				"class" => "OverviewOrders"
//			)
		),
		"right" => array(
			"help" => array(
				"title" => "",
				"class" => "Core",
				"template" => "/plugins/overview/templates/overview_help.html",
				"template_item" => "",
				"url" => ""
			)
		));
	// A menü kibővítése
	// A menüpontokhoz elérhető jogok
	// Felsorolások definiálása
	// A listák definiálása
	// A felhasználó szerkesztő kiegészítése az extra funkciók eléréséhez
	// A szerkesztő kiegészítése az extra funkciók eléréséhez
	// A szerkesztők definiálása
}
if (Core::check_plugin("cms") && Core::check_plugin("webshop"))
{
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A modulok definiálása a beilleszthetőségükhöz
	// Az előfuttatási funkciók beállítása
	// Az utófuttatási funkciók beállítása
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","overview/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>