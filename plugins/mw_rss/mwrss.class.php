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
class MWRss extends Core
{
	public function __construct()
	{
	}
	
	public static function after_site_init($site)
	{
		$site->mw_rss = '<link rel="alternate" type="application/rss+xml" title="MiniWorld | Legfrissebb képek" href="'
			. Core::$HTTP_HOST . '/rss.php?a=mw_rss" />';
	}

	public static function after_core_set_content($content)
	{
		if (empty($content))
		{
			$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;

			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "mw_rss":
					$content = "{modul:$action}";
					break;
			}
		}
		return $content;
	}

	public static function modul_mw_rss($modul_variable)
	{
		$sql = "SELECT * FROM ".Core::$TABLE_PREFIX."mw_image
				WHERE option_accepted = 1
				ORDER BY date_create
				DESC LIMIT 0,16";
		$box = new MWAlbum($sql);
		// Az eredménylistára rátöltjük az RSS sablonjait
		$box->load_templates("/plugins/mw_rss/templates/rss.xml","/plugins/mw_rss/templates/rss_item.xml");
		
		$box->title = "MiniWorld | Legfrissebb képek";
		$box->url = Core::$HTTP_HOST;
		$box->description = "A MiniWorld legfrissebb képeinek listája.";
		$box->publish_date = date("r");

		foreach ($box->items as $item)
		{
			$item->host = Core::$HTTP_HOST;
			$item->year = date("Y");
			$item->publish_date = date("r", $item->date_create);
		}
		
		return $box;
	}

}
?>
