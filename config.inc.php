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

// A dokumentum root beállítása
// A PHP által megadott útvonal végéről levágjuk a / jelet
// így minden hivatkozás /-el kezdődhet a programon belül
if (strlen($_SERVER["DOCUMENT_ROOT"]) == 0)
	Core::$DOCROOT = ".";
elseif (strrpos($_SERVER["DOCUMENT_ROOT"],"/") == strlen($_SERVER["DOCUMENT_ROOT"]))
	Core::$DOCROOT = substr($_SERVER["DOCUMENT_ROOT"],0,strlen($_SERVER["DOCUMENT_ROOT"])-1);
else
	Core::$DOCROOT = $_SERVER["DOCUMENT_ROOT"];
// Az autoload függvényhez az alap include és phpmailer mappa hozzáadása
Core::$PLUGINS['autoload'][] = Core::$DOCROOT."/include";
Core::$PLUGINS['autoload'][] = Core::$DOCROOT."/include/phpmailer";
// A jQuery és jQuery UI kiválasztott verzióinak beállítása
// Core::$SCRIPTS[] = "http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js";
Core::$SCRIPTS[] = "/js/jquery/jquery-1.4.2.min.js";
// Az adatbázis hozzáférés adatainak betöltése
include Core::$DOCROOT."/access.inc.php";
// A szerver HTTP és HTTPS URL-jei
Core::$HTTP_HOST = "http://".$_SERVER["HTTP_HOST"];
Core::$HTTPS_HOST = "https://".$_SERVER["HTTP_HOST"];

Core::$TABLE_PREFIX = "es_";
// Az SVN revízió beállítása
if (function_exists("svn_status"))
{
	// $status = svn_status($_SERVER['DOCUMENT_ROOT']);
	$status = Core::$DOCROOT;
	Core::$MAIN['SVN'] = $status["revision"];
}
else
	Core::$MAIN['SVN'] = "1";

User::$COMPULSORY_FIELDS = array("username","fullname","email","password","retype","phone");
User::$NON_COMPULSORY_FIELDS = array("company","taxnumber");
?>