<?php
/*
 * PTI WSM - Web Shop Motor 2.0
 * Shop Partner List osztály
 * Copyright (C) 2008; PTI Kft.;
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
class BannerBox extends Core
{
	public function __construct($position = 1)
	{
		// Az oldal sablonjnak betöltése
		$this->load_template("bannerbox.html");
		$this->items = array();
			
		// A lekérdezés összeállítása
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."ban_banner`
			WHERE `position` =  $position
			ORDER BY `bearing` DESC,`title` ASC";
		$result = Sql::query($query);
		// Az eredmnyek beolvassa az $items tömbbe
		if ($result)
		{
			while ($o = Sql::fetch_array($result))
			{
				$item = new Core($o);
				$item->load_template("bannerbox_item.html");
				
				switch ($item->option_type)
				{
					case "image":
						$item->html_code = "<a href=\"$item->link\"><img src=\"$item->image\" width=\"$item->width\" height=\"$item->height\" border=\"0\" alt=\"$item->title\" title=\"$item->title\" /></a>";
						break;
					case "flash":
						$item->html_code = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"".$item->width."\" height=\"".$item->height."\" id=\"partner".$item->id."\" align=\"middle\">" .
							"<param name=\"allowScriptAccess\" value=\"sameDomain\" />" .
							"<param name=\"movie\" value=\"".$_SERVER["HOST"].$item->image."\" />" .
							"<param name=\"quality\" value=\"high\" />" .
							"<param name=\"bgcolor\" value=\"#000000\" />" .
							"<embed src=\"".$_SERVER["HOST"].$item->image."\" quality=\"high\" bgcolor=\"#000000\" width=\"".$item->width."\" height=\"".$item->height."\" name=\"partner".$item->id."\" align=\"middle\" allowScriptAccess=\"sameDomain\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" />" .
							"</object>";
						break;
					default:
						$item->code = "";
						break;
				}
				
				$this->items[] = $item;
			}
		}
	}
	
	public static function modul_bannerbox($modul_variable)
	{
		$box = NULL;
		
		if (strpos($modul_variable,":") !== FALSE)
		{
			$mv = explode(":",$modul_variable);
			if (is_numeric($mv[1]))
				$box = new BannerBox((int)$mv[1]);
		}
		else
			$box = new BannerBox();
		
		return $box;
	}
}
?>