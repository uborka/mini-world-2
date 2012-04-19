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

class UserRightList extends Core
{
	public function __construct($user_group_id)
	{
		$tb = microtime();

		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user_right`
			WHERE `user_group_id`=".$user_group_id."
			ORDER BY `menu`";
		$result = Sql::query($query);
		if (Sql::num_rows($result))
		{
			while ($o = Sql::fetch_array($result))
			{
				$this->items[$o["menu"]] = $o["rights"];
			}
		}

		$this->log("ctor()",$tb);
	}

	public function check($menu, $right)
	{
		$tb = microtime();

		$return = false;
		
		if (array_key_exists(strtoupper($menu),$this->items))
			$return = strpos($this->items[strtoupper($menu)],strtolower($right)) !== false;

		$this->log("check($menu)",$tb);

		return $return;
	}
}
?>