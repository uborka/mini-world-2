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
// Az SQL lekérdezésekhez a GROUP_CONCAT hosszának beállítása
// A szükséges CSS-ek betöltése
// A szükséges JavaScript-ek betöltése
// A modulok definiálása a beilleszthetőségükhöz
//Core::$PLUGINS['moduls']['mw_top_images'] = "MWLog::modul_mw_top_images";
Core::$PLUGINS['moduls']['mw_image_views'] = "MWLog::modul_mw_image_views";
// Az előfuttatási funkciók beállítása
// Az utófuttatási funkciók beállítása
Core::insert_postfunction("mw_image_modul","MWLog","mw_image_modul");
//Core::insert_postfunction("modul_mw_user","MWLog","after_modul_mw_user");
// A statikus munkamenet változók bővítése
// A CMS kiegészítése
if (Core::check_plugin("cms"))
{
	// A CMS-kez készített modulok definiálása
	// Az CMS utófuttatási funkciók beállítása - a modulok feldolgozásához
	// A szükséges CSS-ek betöltése
	// A szükséges JavaScript-ek betöltése
	// A menü kibővítése
	// A menüpontokhoz elérhető jogok
	// Felsorolások definiálása
	// A listák definiálása
	// A szerkesztő kiegészítése az extra funkciók eléréséhez
	// A szerkesztők definiálása
}
// Nyomkövetés
if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
{
	$tb = array_sum(explode(' ', $time_before));
	$time_after = array_sum(explode(' ', microtime()));
	$total_execution_time = $time_after - $tb;
	Core::$TIMES .= sprintf("%-42s","mw_log/init.inc:")
		."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
}
?>