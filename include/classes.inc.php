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

// Az egész rendszer magját képező Core osztály betöltése
include "core.class.php";
// A felhasználókat kezelő User osztály definiálása
include "user.class.php";
// Az SQL kapcsolatért felelős Sql osztály definiálása
include "sql(pdo).class.php";
// include "sql.class.php";

// A többi osztályt betöltő autoload függvény definiálása
function __autoload($c)
{
	$exists = FALSE;
	foreach (Core::$PLUGINS['autoload'] as $folder)
	{
		if (!$exists)
		{
		    $file = $folder."/".strtolower($c).".class.php";
		    if (file_exists($file))
		    {
		        include($file);
		        $exists = TRUE;
		    }
		}
	}
    if (!$exists)
    {
        $debug = debug_backtrace();
        $error = "Unable to load: " . '"'.$file.'"'. "<br/>\n";
        $error .= 'File: '.$debug[0]['file']."<br/> Line: ".$debug[0]['line']."<br/>\n";
        trigger_error($error, E_USER_ERROR);
    }
}
?>