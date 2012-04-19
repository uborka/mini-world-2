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

class Page extends Core
{
	public $roots = array();

	public function __construct($data = NULL,
								$template = "page.html",
								$level = 0,
								$check_collapse = FALSE)
	{
		$tb = microtime();

		if (is_array($data))
		{
			// A mező értékek átmásolása az objektum mezőibe
			$this->props = $data;
			
			$this->template_name = $template;
			$this->level = $level;
			$this->check_collapse = $check_collapse;
		
			$this->init();
		}
		elseif (is_numeric($data))
		{
			// A kiírandó változók beállítása
			$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page".Core::$PARAM["LANG_POSTFIX"][$lang]."` " .
					 "WHERE (`id`=$data) LIMIT 1";
			$result = Sql::query($query);
			if (Sql::num_rows($result) == 1)
			{
				// A mező értékek átmásolása az objektum mezőibe
				$this->props = Sql::fetch_array($result);
				
				$this->template_name = $template;
				$this->level = $level;
				$this->check_collapse = $check_collapse;
				
				$this->init();
			}
		}

		Core::log("ctor()",$tb);
	}
	
	public function init()
	{
		$tb = microtime();
		
		// Az oldal sablonjának betöltése
		$this->load_template($this->template_name);
		
		$this->level_class = "level".$this->level;
		if (array_key_exists("p",$_SESSION))
		{
			if ($this->id == $_SESSION["p"])
			{
				// Az oldal ősoldalainak felderítése
				$this->roots = array($this->id);
				$this->get_roots($this->parent_id);
			}
		}
		// Az oldal kijelölése, ha az az aktuálisan kiválasztott
		$this->is_selected = $this->id == $_SESSION["p"];
		$this->set_selected($this->is_selected);
		// Az oldal kijelölése, ha az a kiválasztott, vagy annak egyik őse
		if (array_key_exists("PAGE",Core::$MAIN))
			if (is_object(Core::$MAIN['PAGE']))
				if (is_array(Core::$MAIN['PAGE']->roots))
				{
					$this->is_selected_root = in_array($this->id,Core::$MAIN['PAGE']->roots);
					$this->class_selected_root = $this->is_selected_root ? "selected" : "";
					$this->option_selected_root = $this->is_selected_root ? "selected=\"selected\"" : "";
					$this->checked_selected_root = $this->is_selected_root ? "checked=\"checked\"" : "";
				}
		// Annak ellenőrzése, az oldalnak vannak-e alárendelt oldalai
		$this->option_collapse = "";
		if ($this->check_collapse)
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_page` WHERE (`parent_id`=".$this->id.") " .
					 "AND ((option_submenu=1) OR (option_mainmenu=1))";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
				$this->option_collapse = Core::$LANG["OPTION_COLLAPSE"];
		}
		// Az oldalhoz kapcsolódó dátumok formázása
		$this->publish_date = $this->date_publish == 0 ? "" : Core::unix_to_date($this->date_publish);
		$this->hide_date = $this->date_hide == 2147468400 ? "" : Core::unix_to_date($this->date_hide);
		$this->create_date = Core::unix_to_date($this->date_create);
		$this->modify_date = Core::unix_to_date($this->date_modify);
		// Az oldalra mutató URL és parancsok összeállítása
		if ($this->id == Core::$PARAM["OPEN_PAGE_ID"])
			$this->command = Core::$HTTP_HOST;
		elseif (Core::$PARAM["PERMALINKS"] == 1)
		{
			// Ha az oldalnak még nincs megadva a permalink-je, akkor
			// generálunk neki egyet
			if (strlen($this->permalink) == 0)
			{
				$this->permalink = Core::unaccuate($this->title);
				// Ellenőrizzük, van-e már azonos permalink
				if (Page::search_by_permalink($this->permalink) > 0)
					$this->permalink .= "(".$this->id.")";
				
//				$langs = explode(",",Core::$PARAM["LANG_POSTFIX"]);
//				foreach ($langs as $lang)
				foreach (Core::$PARAM["LANG_POSTFIX"] as $lang)
				{
					$query = "UPDATE `".Core::$TABLE_PREFIX."page$lang`
						SET `permalink` = '".$this->permalink."'
						WHERE `id` = ".$this->id;
					Sql::query($query);
				}
			}
			$this->command = Core::$HTTP_HOST . "/" . $this->permalink.".html";
		}
		else
			$this->command = Core::$HTTP_HOST."/?p=".$this->id;
			
		if (trim($this->link) == "")
			$this->url = $this->command;
		elseif (strpos($this->link,"http") === false)
			$this->url = Core::$HTTP_HOST.$this->link;
		else
			$this->url = $this->link;
			
		Core::log("init()",$tb);
	}

	private function get_roots($id = 0)
	{
		$this->roots[] = $id;
		if ($id > 0)
		{
			$query = "SELECT `parent_id` FROM `".Core::$TABLE_PREFIX."core_page` WHERE (`id`=$id)";
			$result = Sql::query($query);
			if ($result)
			{
				if ($o = Sql::fetch_object($result))
				{
					$this->get_roots($o->parent_id);
				}
			}
		}
	}
	
	public static function search_by_permalink($permalink)
	{
		$return = 0;
		
		// Az oldal megkeresése a permalink-je alapján
		$query = "SELECT id FROM ".Core::$TABLE_PREFIX."core_page WHERE permalink = '$permalink'";
		$result = Sql::query($query);
		if (Sql::num_rows($result) == 1)
		{
			$o = Sql::fetch_array($result);
			$return = $o["id"];
		}
		
		return $return;
	}
	
	public static function modul_page($modul_variable)
	{
		$page = NULL;
		
		if (strpos($modul_variable,":") !== FALSE)
		{
			$mv = explode(":",$modul_variable);
			if (is_numeric($mv[1]))
				$page = new Page((int)$mv[1]);
		}
		if (is_null($page))
			$page = Core::$MAIN['PAGE'];
		if (!is_null($page))
		{
			Core::$MAIN['TITLE'] = $page->title;
			Core::$MAIN['META_KEYWORDS'] = $page->meta_keywords;
			Core::$MAIN['META_DECRIPTION'] = $page->meta_description;
		}
		
		return $page;
	}
}
?>