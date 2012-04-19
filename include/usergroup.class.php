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

class UserGroup extends Core
{
	public function __construct($data = NULL)
	{
		$tb = microtime();

		if (!is_array($data))
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user_group` WHERE `id`=".$data;
			$result = Sql::query($query);
			$obj = Sql::fetch_array($result);
		}
		else
			$obj = $data;

		if (!is_null($obj))
		{
			$keys = array_keys($obj);
			foreach ($keys as $key)
			{
				if (!is_int($key))
					$this->props[$key] = $obj[$key];
			}
		}

		$this->log("ctor()",$tb);
	}
}
?>