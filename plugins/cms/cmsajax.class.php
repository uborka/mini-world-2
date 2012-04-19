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

class CmsAjax
{
	public static function after_core_set_content($content)
	{
		if (empty($content))
		{
			$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "cmsautocomplete":
				case "cmsclearapc":
					$content = "{modul:$action}";
					break;
			}
		}
		
		return $content;
	}
	
	public static function modul_cmsautocomplete()
	{
		// A bemeneti paraméterek feldolgozása
		$table = $_SESSION["table"];
		$keywords = $_SESSION["term"];
		
		$items = "";
		$query = "SELECT `id`,`title` " .
				"FROM `".Core::$TABLE_PREFIX."$table` " .
				"WHERE `title` LIKE '$keywords%' ORDER BY `title`";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			while ($o = Sql::fetch_array($result))
			{
				if (strlen($items) > 0) $items .= ",";
				$items .= '"'.$o["title"].'"';
			}
		}
		
		$return = new Core();
		$return->template = "[".$items."]";
		
		return $return;
	}
	
	public static function modul_cmsclearapc()
	{
		$return = NULL;
		
		if (function_exists("apc_sma_info"))
		{
			$return = new Core();
			$return->load_template("/plugins/cms/templates/cmsclearapc.html");
			$return->message = "";
			
			$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
			
			switch ($todo) 
			{
				case "clear":
					if (apc_clear_cache("user"))
						$return->message = $return->create_message("Az APC gyorsítótárat sikeresen törölte.","ok");
					else
						$return->message = $return->create_message("Az APC gyorsítótár törlése sikertelen.","error");
					break;
			}
			
			$info = apc_sma_info();

			$return->num_seg = $info["num_seg"];
			$return->seg_size = number_format($info["seg_size"],0,""," ");
			$return->avail_mem = number_format($info["avail_mem"],0,""," ");
			$return->block_nums = count($info["block_lists"]);
		}
		else
		{
			$return = new Core();
			$return->load_template("/plugins/cms/templates/cmsclearapc_notsupported.html");
		}
		
		return $return;
	}
}
?>
