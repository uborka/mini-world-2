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

class CmsListItem extends Core
{
	static $COLUMN_FUNCTIONS = array(
		"id" => "CmsListItem::generate_id",
		"text" => "CmsListItem::generate_text",
		"color" => "CmsListItem::generate_color",
		"int" => "CmsListItem::generate_int",
		"percent" => "CmsListItem::generate_percent",
		"currency" => "CmsListItem::generate_currency",
		"enum" => "CmsListItem::generate_enum",
		"email" => "CmsListItem::generate_email",
		"date" => "CmsListItem::generate_date",
		"datetime" => "CmsListItem::generate_datetime",
		"included" => "CmsListItem::generate_included",
		"parent" => "CmsListItem::generate_parent",
		"user" => "CmsListItem::generate_user",
		"is_system" => "CmsListItem::generate_is_system",
		"drop" => "CmsListItem::generate_drop"
		);
	
	public function __construct($data)
	{
		$tb = microtime();
		
		// Az adatok betöltése a tulajdonságokba
		parent::__construct($data);

		// A sor összeállítása
		$this->init();

		$this->log("ctor()",$tb);
	}
	
	public function init()
	{
		$menu = $_SESSION["m"];
		$lang = empty($_SESSION["lang"]) ? Core::$PARAM["LANG_DEFAULT"] : $_SESSION["lang"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		$table = is_array($config['table']) ? $config['table'][$lang] : $config['table'];
		$user = unserialize($_SESSION["user"]);
		
		// Az oldal sablonjnak betöltése
		$this->load_template("cmslist_item.html");
		
		// A listaváltozók betöltése tulajdonságokba
		$this->menu = $menu;
		$this->page = array_key_exists("page",$_SESSION) ? $_SESSION["page"] : 1;
		$this->rpp = array_key_exists("rpp",$_SESSION) ? $_SESSION["rpp"] : 20;
		$this->order_field = array_key_exists("order_field",$_SESSION) ? $_SESSION["order_field"] : $config['default_field'];
		$this->order_dir = array_key_exists("order_dir",$_SESSION) ? $_SESSION["order_dir"] : $config['default_direction'];
		$this->filter = array_key_exists("filter",$_SESSION) ? $_SESSION["filter"] : NULL;
		
		// Back URL összeállítása, amivel vissza lehet térni a listára
		$this->backurl = urlencode("?a=cmslist&m=".$menu."&order_field=".$this->order_field.
				"&page=".$this->page.
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter));
		
		// A szerkesztés URL-jének összeállítása
		$this->edit_url = "";
		if ($user->user_rights->check($menu,"edit"))
			$this->edit_url = "?a=cmseditor&m=".$menu."&id=".$this->id."&backurl=".$this->backurl;
		// A törlés URL-jének összeállítása
		$this->delete_url = "";
		if ($user->user_rights->check($menu,"delete"))
			$this->delete_url = "?a=cmslist&m=".$menu."&todo=delete&id=".$this->id.
				"&order_field=".$this->order_field.
				"&page=".$this->page.
				"&rpp=".$this->rpp.
				"&filter=".(empty($this->filter) ? "" : $this->filter);
		
		// Az oszlopok összeállítása
		for ($i = 0; $i < count($config['fields']); $i++)
		{
			$fieldname = $config['fields'][$i];
			
			if (array_key_exists($config['types'][$i],CmsListItem::$COLUMN_FUNCTIONS))
			{
				$item = call_user_func(CmsListItem::$COLUMN_FUNCTIONS[$config['types'][$i]],$this,$fieldname);
				
				if (!$item->is_hidden)
					$this->items[] = $item;
			}
		}

		if (strlen($this->edit_url) > 0)
			$this->edit_link = "<a href=\"$this->edit_url\" title=\"Szerkesztés...\">" .
					"<img src=\"".Core::$RELPATH."/plugins/cms/templates/images/icon_edit.gif\"></a>";
		else
			$this->edit_link = "<img src=\"".Core::$RELPATH."/plugins/cms/templates/images/icon_edit_disabled.gif\" title=\"Nem szerkeszthető.\">";
		// A szerkesztési és törlési jogok ellenőrzése után az egyes funkciók beállítása
		if (strlen($this->delete_url) > 0)
			$this->delete_link = "<a href=\"$this->delete_url\" title=\"Törlés\">" .
					"<img src=\"".Core::$RELPATH."/plugins/cms/templates/images/icon_delete.gif\"></a>";
		else
			$this->delete_link = "<img src=\"".Core::$RELPATH."/plugins/cms/templates/images/icon_delete_disabled.gif\" title=\"Nem törölhető.\">";
	}
	
	public static function generate_id($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		if (strlen($record->edit_url) > 0)
			$item->title = "<a href=\"$record->edit_url\" title=\"Szerkesztés...\" >["
				.$record->$fieldname."]</a>";
		else
			$item->title = "[".$record->$fieldname."]";
			
		return $item;
	}
	
	public static function generate_text($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "left";
		
		$item->title = $record->$fieldname;
		
		return $item;
	}
	
	public static function generate_color($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		$c = explode("/",$record->$fieldname);
		for ($i = 0; $i < 2; $i++)
		{
			if ($c[$i] == "TRANS")
				$c[$i] = "FFF";
		}
		
		$item->title = "<div style=\"color:#$c[1];font-weight:bold;background-color:#$c[0];border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;\">".$record->$fieldname."</div>";
		
		return $item;
	}
	
	public static function generate_int($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		$item->title = $record->$fieldname;
		
		return $item;
	}
	
	public static function generate_percent($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		$item->title = $record->$fieldname . "%";
		
		return $item;
	}
	
	public static function generate_currency($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "right";
		
		if (intval($record->$fieldname) > 0)
			$item->title = $item->format_price($record->$fieldname);
		else
			$item->title = "<span class=\"red\">".$item->format_price($record->$fieldname)."</span>";
			
		return $item;
	}
	
	public static function generate_enum($record,$fieldname)
	{
		$menu = $_SESSION["m"];
		
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		$title = Core::$CMS['ENUMERATIONS'][strtoupper($menu).".".strtoupper($fieldname)][$record->$fieldname];
		if ($title == strtoupper($title))
			$item->title = "<span class=\"red\">$title</span>";
		else
			$item->title = $title;
			
		return $item;
	}
	
	public static function generate_email($record,$fieldname)
	{
		$menu = $_SESSION["m"];
		$user = unserialize($_SESSION["user"]);
		
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "left";
		
		if ($user->user_rights->check($menu,"email"))
			$item->title = "<nobr><a href=\"mailto:".$record->$fieldname."\" title=\"E-mail küldése\">".$record->$fieldname."</a></nobr>";
		else
			$item->title = $record->$fieldname;
			
		return $item;
	}
	
	public static function generate_date($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		if ($record->$fieldname > 0)
			$item->title = Core::unix_to_date($record->$fieldname);
		else
			$item->title = "<span class=\"red\">N/A</span>";
		
		return $item;
	}
	
	public static function generate_datetime($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "center";
		
		if ($record->$fieldname > 0)
			$item->title = Core::unix_to_datetime($record->$fieldname);
		else
			$item->title = "<span class=\"red\">N/A</span>";
		
		return $item;
	}
	
	public static function generate_included($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "left";
		
		if ($record->$fieldname > 0)
		{
			$tablename = substr($fieldname,0,strpos($fieldname,"_id"));
			if (in_array($tablename,array("user","user_group","domain","page","theme")))
				$tablename = "core_".$tablename;
			$query = "SELECT `id`,`title` FROM `".Core::$TABLE_PREFIX.$tablename."` WHERE id IN (".$record->$fieldname.")";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				$item->title = "";
				while ($o = Sql::fetch_object($result))
				{
					if ($item->title != "") $item->title .= ", ";
					$item->title .= "<span title=\"".$o->title . " [" . $o->id . "]\">".$o->title;
					if (Core::$MODE == "DEBUG")
						$item->title .= " [".$o->id."]";
					$item->title .= "</span>";
				}
			}
			else
				$item->title = "<span class=\"red\">N/A [".$record->$fieldname."]</span>";
		}
		else
		{
			$item->title = "<span class=\"red\">N/A [".$record->$fieldname."]</span>";
		}
		
		return $item;
	}
	
	public static function generate_parent($record,$fieldname)
	{
		$menu = $_SESSION["m"];
		$lang = empty($_SESSION["lang"]) ? Core::$PARAM["LANG_DEFAULT"] : $_SESSION["lang"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		$table = is_array($config['table']) ? $config['table'][$lang] : $config['table'];
		
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "left";
		
		if ($record->$fieldname > 0)
		{
			$query = "SELECT `id`,`title` FROM `".Core::$TABLE_PREFIX.$table."` WHERE id = ".$record->$fieldname;
			$result = Sql::query($query);
			if ($o = sql::fetch_object($result))
			{
				$item->title = "<span title=\"".$o->title . " [" . $o->id . "]\">".$o->title;
				if (Core::$MODE == "DEBUG")
					$item->title .= " [".$o->id."]";
				$item->title .= "</span>";
			}
			else
				$item->title = "<span class=\"red\">N/A [".$record->$fieldname."]</span>";
		}
		else
		{
			$item->title = "<span class=\"gray\">Ős/szülő [0]</span>";
		}
		
		return $item;
	}
	
	public static function generate_user($record,$fieldname)
	{
		Core::insert_script("/plugins/cms/js/jquery.tools-tooltip.min.js");
		
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = FALSE;
		$item->align = "left";
		
		if ($record->$fieldname > 0)
		{
			$id = sprintf("%0.0f",microtime(TRUE)*10000);
			$usr = new User("","");
			$usr->read($record->$fieldname);
			$item->title = "<a id=\"user$id\" href=\"mailto:".$usr->email."\"><nobr>".
				$usr->fullname." [".$usr->username."]</nobr></a>";
			$item->title .= "<div class=\"tooltip\" id=\"tooltip$id\">" .
				"<h3>".$usr->fullname."</h3>" .
				"<label>Azonosító:</label><div>".$usr->username."</div>" .
				"<label>E-mail cím:</label><div>".$usr->email."</div>" .
				"<label>Telefon:</label><div>".(strlen($usr->phone)>0 ? $usr->phone : "-")."</div>" .
				"<label>Cím:</label><div>".(strlen($usr->address)>0 ? $usr->address : "-")."</div>" .
				"<label>Cégnév:</label><div>".(strlen($usr->company)>0 ? $usr->company : "-")."</div>" .
				"</div>";
			$item->title .= "<script>\$('a#user$id').tooltip({position:'top center',offset:[-5,0]});</script>";
		}
		else
			$item->title = "<span class=\"red\">N/A [0]</span>";
			
		return $item;
	}
	
	public static function generate_is_system($record,$fieldname)
	{
		$item = new Core();
		$item->template = "<td align=\"{this>align}\">{this>title}</td>";
		$item->is_hidden = TRUE;
		
		if ($record->$fieldname == 1)
			$record->delete_url = "";
		
		return $item;
	}
	
	public static function generate_drop($record,$fieldname)
	{
		$item = new Core();
		$item->is_hidden = TRUE;
		
		return $item;
	}
}
?>