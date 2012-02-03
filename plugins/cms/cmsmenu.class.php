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

class CmsMenu extends Core
{
	public function __construct()
	{
		Core::insert_script("/plugins/cms/js/jquery.tools-tooltip.min.js");
		
		$menu = empty($_SESSION["m"]) ? "" : $_SESSION["m"];
		$user = unserialize($_SESSION["user"]);
		
		// Az oldal sablonjának betöltése
		$this->load_template("cmsmenu.html");
		
		if ($user->user_group->is_admin == 1)
		{
			$block_keys = array_keys(Core::$CMS["MENUS"]);
			foreach ($block_keys as $block_key)
			{
				$block = new Core();
				$block->load_template("cmsmenu_item.html");
				$block->title = Core::$CMS["MENUS"][$block_key]["title"];
				$block->key = $block_key;
				
				$item_keys = array_keys(Core::$CMS["MENUS"][$block_key]["items"]);
				foreach ($item_keys as $item_key)
				{
					if ($user->user_rights->check($item_key,"access"))
					{
						$item = new Core();
						if (is_array(Core::$CMS["MENUS"][$block_key]["items"][$item_key]))
						{
							$item->title = Core::$CMS["MENUS"][$block_key]["items"][$item_key]["title"];
							$item->tip = array_key_exists("tip",Core::$CMS["MENUS"][$block_key]["items"][$item_key]) ? Core::$CMS["MENUS"][$block_key]["items"][$item_key]["tip"] : Core::$CMS["MENUS"][$block_key]["items"][$item_key]["title"];
							$item->load_template(array_key_exists("template",Core::$CMS["MENUS"][$block_key]["items"][$item_key]) ? Core::$CMS["MENUS"][$block_key]["items"][$item_key]["template"] : "cmsmenu_item_subitem.html");
							$item->url = array_key_exists("url",Core::$CMS["MENUS"][$block_key]["items"][$item_key]) ? Core::$CMS["MENUS"][$block_key]["items"][$item_key]["url"] : Core::$HTTP_HOST."/admin/?a=cmslist&m=".$item_key;
						}
						else
						{
							$item->title = Core::$CMS["MENUS"][$block_key]["items"][$item_key];
							$item->tip = Core::$CMS["MENUS"][$block_key]["items"][$item_key];
							$item->load_template("cmsmenu_item_subitem.html");
							$item->url = Core::$HTTP_HOST."/admin/?a=cmslist&m=".$item_key;
						}
						$item->set_selected($item_key == $menu);
						
						$block->items[] = $item;
					}
					else
					{
						$item = new Core();
						$item->load_template("cmsmenu_item_subitem(disabled).html");
						if (is_array(Core::$CMS["MENUS"][$block_key]["items"][$item_key]))
						{
							$item->title = Core::$CMS["MENUS"][$block_key]["items"][$item_key]["title"];
							$item->tip = array_key_exists("tip",Core::$CMS["MENUS"][$block_key]["items"][$item_key]) ? Core::$CMS["MENUS"][$block_key]["items"][$item_key]["tip"] : Core::$CMS["MENUS"][$block_key]["items"][$item_key]["title"];
						}
						else
						{
							$item->title = Core::$CMS["MENUS"][$block_key]["items"][$item_key];
							$item->tip = Core::$CMS["MENUS"][$block_key]["items"][$item_key];
						}
						
						$block->items[] = $item;
					}
				}
				if (count($block->items) > 0)
					$this->items[] = $block;
			}
		}
	}
}
?>