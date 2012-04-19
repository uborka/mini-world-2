<?php
/*
 * FÜGE² - Tartalomkezelés - Webáruház - Ügyviteli rendszer
 * Copyright (C) 2008-2012; PTI Kft.
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

class MWCategory extends Core
{
	public function __construct($data)
	{
		// Ha a DATA egy rekord
		if (is_array($data))
		{
			parent::__construct($data);
		}
		// Ha a DATA egy kulcs
		elseif (is_numeric($data))
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_category` WHERE (`id` = $data)";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				parent::__construct(Sql::fetch_array($result));
			}
		}
	}
	
	public static function search_by_permalink($permalink)
	{
		$return = 0;

		// Az oldal megkeresése a permalink-je alapján
		$query = "SELECT `id` FROM `".Core::$TABLE_PREFIX."mw_category` WHERE `permalink` = '$permalink'";
		$result = Sql::query($query);
		if (Sql::num_rows($result) == 1)
		{
			$o = Sql::fetch_array($result);
			$return = $o["id"];
		}

		return $return;
	}
	
	public static function modul_mw_image_category_select()
	{
		$box = NULL;
		
		if (!empty($_SESSION['imgid']))
		{
			$image = new MWImage((int)$_SESSION['imgid']);
			
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_category` ORDER BY `title`";
			$result = Sql::query($query);
			if (Sql::num_rows($result))
			{
				$box = new Core();
				$box->items = array();
				$box->load_template("mw_image_category_select.html");
				while ($o = Sql::fetch_array($result))
				{
					$item = new MWCategory($o);
					$item->load_template("mw_image_category_select_item.html");
					$item->set_selected($o["id"] == $image->mw_category_id);
					$box->items[] = $item;
				}
			}
		}
		
		return $box;
	}
	
	public static function modul_mw_categories()
	{
		$box = NULL;
		
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_category` ORDER BY `title`";
		$result = Sql::query($query);
		if (Sql::num_rows($result))
		{
			$box = new Core();
			$box->items = array();
			$box->load_template("mw_categories.html");
			while ($o = Sql::fetch_array($result))
			{
				$item = new MWCategory($o);
				$item->load_template("mw_categories_item.html");
				$item->url = Core::$HTTP_HOST . "/kategoria/" . $item->permalink . ".html";
				$box->items[] = $item;
			}
		}
		
		return $box;
	}
}
?>