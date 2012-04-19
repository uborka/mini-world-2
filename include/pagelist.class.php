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

class PageList extends Core
{
	public function __construct($data = NULL,
								$modul_template,
								$item_template,
								$expand = "none",
								$level = 0,
								$check_collapse = FALSE)
	{
		$tb = microtime();
		
		$this->items = array();
		
		if (!is_null($data))
		{
			if (is_array($data))
			{
				for ($i = 0; $i < count($data); $i++)
				{
					$item = NULL;
					if (is_numeric($data[$i]) && ($data[$i] > 0))
					{
						if (!empty(Core::$CACHE))
						{
							if (array_key_exists($data[$i],Core::$CACHE['PAGES']))
							{
								$item = clone Core::$CACHE['PAGES'][$data[$i]];
							}
							else
							{
								$item = new Page((int)$data[$i],$item_template,$level,$check_collapse);
								Core::$CACHE['PAGES'][(int)$data[$i]] = clone $item;
							}
						}
						else
						{
							$item = new Page((int)$data[$i],$item_template,$level,$check_collapse);
							Core::$CACHE['PAGES'][(int)$data[$i]] = clone $item;
						}
					}
					elseif (is_object($data[$i]))
					{
						$item = $data[$i];
					}
					elseif (is_int($data[$i]))
					{
						$item = new Page($data[$i],$item_template,$level,$check_collapse);
					}

					if (!is_null($item))
					{
						$this->init_item($item,$modul_template,$item_template,$level,$check_collapse,$expand);
						$this->items[] = $item;
					}
				}
				if (count($this->items) > 0)
				{
					$this->load_template($modul_template);
				}
			}
			elseif (is_object($data))
			{
				// Az eredmények beolvassa az $items tömbbe
				// Az oldal sablonjnak betöltése
				if (Sql::num_rows($data) > 0)
					$this->load_template($modul_template);
				// A találatok feldolgozása
				while ($o = Sql::fetch_array($data))
				{
					if (!array_key_exists('PAGES',Core::$CACHE))
					{
						$item = new Page($o,$item_template,$level,$check_collapse);
						Core::$CACHE['PAGES'] = array();
						Core::$CACHE['PAGES'][$o["id"]] = $item;
					}
					elseif (array_key_exists($o["id"],Core::$CACHE['PAGES']))
					{
						$item = clone Core::$CACHE['PAGES'][$o["id"]];
						$item->load_template($item_template);
					}
					else
					{
						$item = new Page($o,$item_template,$level,$check_collapse);
						Core::$CACHE['PAGES'][$o["id"]] = $item;
					}

					$this->items[] = $item;
					$this->init_item($item,$modul_template,$item_template,$level,$check_collapse,$expand);
				}
			}
		}
		
		$this->log("ctor()",$tb);
	}
	
	public function init_item($item,$modul_template,$item_template,$level,$check_collapse,$expand)
	{
		$lang = $this->get_lang();
		
		switch ($expand)
		{
			case "submenu":
				// Ez nem kifejti a listát, hanem csak átalakítja kinézetre
				// az alkategóriákéra
				$this->load_template("submenu.html");
				foreach ($this->items as $item)
				{
					$item->load_template("submenu_item.html");
				}
				break;
			case "sitemap":
				$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."`
						WHERE (`option_submenu` = 1) AND (`parent_id` = ".$item->id.")
						ORDER BY `bearing` DESC,`title` ASC";
				$result = Sql::query($query);
				if (Sql::num_rows($result) > 0)
				{
					$items = new PageList($result,
						$modul_template,"mainmenu_item.html",
						"none",($level + 1),
						$check_collapse);
					$item->items = $items->items;
				}
				else
					$item->items = "";
				break;
			case "one":
				$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."`
						WHERE (`option_submenu` = 1) AND (`parent_id` = ".$item->id.")
						ORDER BY `bearing` DESC,`title` ASC";
				$result = Sql::query($query);
				$items = new PageList($result,
					$modul_template,$item_template,
					"none", $level + 1,
					$check_collapse);
				$item->items = $items->items;
				break;
			case "selected":
				if ($item->is_selected_root)
				{
					// Az aktuális elem alá rendelt elemek lekérdezése
					$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."`
							WHERE (`option_submenu` = 1) AND (`parent_id` = ".$item->id.")
							ORDER BY `bearing` DESC,`title` ASC";
					$result = Sql::query($query);
					$items = new PageList($result,
						"submenu.html","submenu_item.html",
						"selected",($level + 1),
						$check_collapse);
					$item->items = $items->items;
				}
				else
					$item->items = "";
				break;
			case "none":
			default:
				$item->items = "";
				$item->load_template($item_template);
				break;
		}
	}
	
	public static function modul_mainmenu($modul_variable)
	{
		$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."` " .
				 "WHERE (`option_mainmenu`=1) ORDER BY `bearing` DESC,`title` ASC";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			$mv = explode(":",$modul_variable);
			if (count($mv)>1)
				$collapse = $mv[1] == "submenu";
			else
				$collapse = false;
			return new PageList($result,
				"mainmenu.html","mainmenu_item.html",
				strpos($modul_variable,":")===FALSE ? "none" : $mv[1],0,$collapse);
		}
		else return NULL;
	}
	
	public static function modul_submenu($modul_variable)
	{
		$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
		$mm = Core::$MAIN['PAGE']->roots[count(Core::$MAIN['PAGE']->roots)-2];
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."` " .
				 "WHERE (`option_submenu`=1) AND (`parent_id` = $mm) " .
				 "ORDER BY `bearing` DESC,`title` ASC";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			$mv = explode(":",$modul_variable);
			return new PageList($result,
								"submenu.html","submenu_item.html",
								strpos($modul_variable,":")===FALSE ? "selected" : $mv[1],0,true);
		}
		else return NULL;
	}

	public static function modul_basemenu($modul_variable)
	{
		$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."` " .
				 "WHERE (`option_basemenu`=1) ORDER BY `bearing` DESC,`title` ASC";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			return new PageList($result,"basemenu.html","basemenu_item.html");
		}
		else return NULL;
	}

	public static function modul_page_breadcrumb()
	{
		if (array_key_exists("PAGE",Core::$MAIN))
		{
			return new PageList(array_reverse(Core::$MAIN['PAGE']->roots),
								"page_breadcrumb.html","page_breadcrumb_item.html");
		}
		else return NULL;
	}
	
	public static function modul_page_refered($modul_variable)
	{
		if (array_key_exists("PAGE",Core::$MAIN))
		{
			if (strlen(trim(Core::$MAIN['PAGE']->refered_page_ids)) > 0)
			{
				return new PageList(explode(",",Core::$MAIN['PAGE']->refered_page_ids),
									"page_refered.html","page_refered_item.html");
			}
			else return NULL;
		}
		else return NULL;
	}
}
?>