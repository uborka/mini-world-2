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
class RssFeeds extends Core
{
	public function __construct()
	{
		$tb = microtime();
		$this->init();
		$this->log("ctor",$tb);
	}

	public function init()
	{
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."rss_feed` WHERE `option_enabled` = 1 ORDER BY `bearing` DESC, `title` ASC";
		$result = Sql::query($query);
		if(Sql::num_rows($result)>0 )
		{
			$this->load_template("rss_feeds.html");
			$this->items = array();
			while($o = Sql::fetch_array($result))
			{
				$item = new RssFeed($o);
//				$item->load_template("rss_feed.html");
//				foreach($item->items as $it)
//				{
//					$it->load_template("rss_feed_item.html");
//				}
				
				$this->items[]=$item;
			}
		}
	}

	public static function after_core_set_content($content)
	{
		if (empty($content))
		{
			$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;

			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "rss_feeds":
				case "rss_feed":
					$content = "{modul:$action}";
					break;
			}
		}

		return $content;
	}

	public static function modul_rss_feed($modul_variable)
	{
		$box = null;
		
		if (strpos($modul_variable,":") !== FALSE)
		{
			$mv = explode(":",$modul_variable);
			if (is_numeric($mv[1]))
				$box = new RssFeed((int)$mv[1]);
		}
		
		return $box;
	}

	public static function modul_rss_feeds($modul_variable)
	{
		$box = new RssFeeds();
		
		return $box;
	}
}
?>
