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

Sql::$HOST = 		"localhost";
Sql::$USERNAME = 	"www_eskanzen";
Sql::$DATABASE = 	"www_energiaskanzen_2012";
Sql::$PASSWORD = 	"aqdaPNp7XCr5VBP6";
// Az üzemmód definiálása
// Az üzzemmód lehet DEBUG, TEST vagy LIVE
// Egyes osztályok ez alapján eltérően működhetnek
Core::$DEBUG_LEVEL = 0;
Core::$MODE = isset($_GET["debug"]) ? "DEBUG" : "DEBUG";
// Az élesített módban az üzemmód alapértelmezése RELEASE:
// Core::$MODE = isset($_GET["debug"]) ? "DEBUG" : "RELEASE";

set_error_handler("Core::error_handler");
?>
