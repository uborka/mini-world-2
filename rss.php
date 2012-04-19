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

$tb = microtime();
// Az alaposztályok betöltése és az auttoload definiálása
include "./include/classes.inc.php";
// Az egyes beállítások és paramétek inicializálása
include "./config.inc.php";
// A hibák és figyelmeztetések naplózását átirányítjuk
set_error_handler("Core::error_handler");
// Az oldalt készítő objektum példányosítása
$site = new AjaxSite();
// Az oldal kimenetnek elkészítése
$site->time_before = $tb;
$site->show();
// A kapcsolat bontása az SQL szerverrel
Sql::disconnect();
?>