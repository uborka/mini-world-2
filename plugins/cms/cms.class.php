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

class CMS extends Site
{
	public function load_plugins ()
  	{
  		$tb = microtime();
  		
		// Először is beállítjuk az alapértelmezett modulokat
		// A CMS kiegészítőben ez a rész helyettesíti az init.inc.php fájlt
		Core::$PLUGINS['moduls']['cmsmenu'] = "CMS::modul_cmsmenu";
		Core::$PLUGINS['moduls']['cmslist'] = "CMS::modul_cmslist";
		Core::$PLUGINS['moduls']['cmseditor'] = "CMS::modul_cmseditor";
		Core::$PLUGINS['moduls']['cmsbrowser'] = "CMS::modul_cmsbrowser";
		Core::$PLUGINS['moduls']['languagebox'] = "Site::modul_languagebox";
		// Az utófeldolgozás funkciók bővítése
		Core::insert_postfunction("core_set_content","CMS","after_core_set_content");
		// A bővítmény tábla beállítása alapján a többi modul inicializálása
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_plugin` WHERE `option_active`=1 ORDER BY `depend_nums` ASC";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			while ($o = Sql::fetch_array($result))
			{
				if (file_exists(Core::$DOCROOT.Core::$RELPATH."/plugins".$o["path"]."/init.inc.php"))
				{
					// A bővítmény útvonalának beállítása az autolóad-hoz
					Core::$PLUGINS['autoload'][] = Core::$DOCROOT.Core::$RELPATH."/plugins".$o["path"];
				}
			}
		}
		// A betöltött útvonalak alapján a bővítmények inicializálása
		foreach (Core::$PLUGINS['autoload'] as $path)
		{
			if (file_exists($path."/init.inc.php"))
				include($path."/init.inc.php");
		}
		
		$this->log("load_plugins()",$tb);
	}
	
	public function load_theme()
	{
		$tb = microtime();
		
	    $_SESSION["template_path"] = Core::$RELPATH."/plugins/cms/templates";

		$this->log("load_theme()",$tb);
	}
	
	public function load_mains()
	{
		$tb = microtime();
		
		$this->call_prefunctions("adminsite_load_mains");
		
	    $this->call_postfunctions("adminsite_load_mains");
	    
	    $this->log("load_mains()",$tb);
	}
	
	public function init()
	{
		$tb = microtime();
		
		$this->call_prefunctions("adminsite_init");
		
		// Az oldal sablonjának betöltése
		if ($this->user->login_ok)
		   	$this->load_template("site.html");
		else
			$this->load_template("logon.html");
		
		// A nyomkövetéskor eltérő modulok betöltése
	    $this->finisher_code = "";
	    if (Core::$MODE == "DEBUG")
	    {
	    	$this->template = preg_replace("({this>finisher_code})", "<div id=\"debug_info\">{debug_info}</div>", $this->template);
	    }
	    else
	    {
	    	$finisher_code = new Core();
	    	$finisher_code->load_template("finisher_code.html");
	    	$this->enclose_code = $finisher_code;
	    }
	    // Az SVN revizíó szám beállítása
	    $this->svn_revision = Core::$MAIN['SVN'];
	    // A Host adatok beállítása
	    $this->http_host = Core::$HTTP_HOST;
	    // Az alapértelmezett üzenet
	    if ($this->user->login_ok)
	    	$this->message = "";
	    else
	    {
	    	if (is_object($this->user->message))
	    	{
		    	$this->message = $this->user->message;
		    	$this->message->load_template("logon_message.html");
	    	}
	    	else
	    	{
	    		$this->message = $this->create_message("Ön sikeresen kijelentkezett.","ok");
	    		$this->message->load_template("logon_message.html");
	    	}
	    }
	    
	    $this->call_postfunctions("adminsite_init");
	    
	    $this->log("load_init()",$tb);
	}
	
	public static function after_core_set_content($content)
	{
		if (empty($content))
		{
			$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "cmslist":
				case "cmseditor":
				case "cmsparams":
				case "cmsplugins":
				case "cmsbrowser":
					$content = "{modul:$action}";
					break;
			}
		}
		
		return $content;
	}
	
	public static function modul_cmsmenu($modul_variable)
	{
		return new CmsMenu();
	}
	public static function modul_cmslist($modul_variable)
	{
		$menu = empty($_SESSION["m"]) ? NULL : $_SESSION["m"];
		
		$return = NULL;
		if (!is_null($menu))
		{
			$class = Core::$CMS['LISTS'][$menu]['class'];
			$return = new $class;
		}
		return $return;
	}
	public static function modul_cmseditor($modul_variable)
	{
		$menu = empty($_SESSION["m"]) ? NULL : $_SESSION["m"];
		$submitnclose = empty($_SESSION["submitnclose"]) ? NULL : $_SESSION["submitnclose"];
		
		$return = NULL;
		// Ha a submitnclose nincs definiálva, akkor egyszerűen megjelenítjük
		// a szerkesztőt
		if (is_null($submitnclose))
		{
			if (!is_null($menu))
			{
				$class = Core::$CMS['EDITORS'][$menu]['class'];
				$return = new $class;
			}
		}
		// Ellenkező esetben a szerkesztő todo() funkciójával feldolgoztatjuk
		// a bemenetet (annak visszatérési értékét üzenetként kezelve), majd
		// megjelenítjük a listát
		else
		{
			if (!is_null($menu))
			{
				// Mentés
				$class = Core::$CMS['EDITORS'][$menu]['class'];
				$return = new $class;
				if (method_exists($return,"todo"))
					$message = $return->todo();
				// A backurl feldolgozása
				if (!empty($_SESSION["backurl"]))
				{
					$parameters = explode("&",$_SESSION["backurl"]);
					foreach ($parameters as $parameter)
					{
						$p = explode("=",$parameter);
						$_SESSION[$p[0]] = $p[1];
					}
				}
				// Majd a lista visszatöltése
				$class = Core::$CMS['LISTS'][$menu]['class'];
				$return = new $class;
				if (is_object($message))
					$return->message = $message;
			}
		}
		return $return;
	}
	public static function modul_cmsbrowser($modul_variable)
	{
		Core::insert_script("/include/ckfinder/ckfinder.js");
		
		$return = new Core();
		$return->load_template("cmsbrowser.html");
		
		return $return;
	}
	
	public static function insert_cms_menu($main,$menu,$title)
	{
		if (is_null($main))
			Core::$CMS['MENUS'][$menu] = array("title"=>$title,"items"=>array());
		else
			Core::$CMS['MENUS'][$main]["items"][$menu] = $title;
	}
	public static function insert_cms_right($menu,$rights)
	{
		Core::$CMS['RIGHTS'][strtoupper($menu)] = $rights;
	}
	public static function insert_cms_enumeration($menu,$enumeration,$items)
	{
		Core::$CMS['ENUMERATIONS'][strtoupper($menu.".".$enumeration)] = $items;
	}
	public static function insert_cms_list($menu,$title,$class,$table,$fields,
										   $types,$headers,$default_orderfield,
										   $default_orderdir,
										   $extra_commands = NULL,
										   $where_clause = "")
	{
		Core::$CMS['LISTS'][$menu] = array(
			"title" => $title,
	  		"class" => $class,
	  		"table" => $table,
			"fields" => $fields,
			"types" => $types,
			"headers" => $headers,
			"default_field" => $default_orderfield,
			"default_direction" => $default_orderdir
			);
		if (is_array($extra_commands))
			Core::$CMS['LISTS'][$menu]["extra_commands"] = $extra_commands;
		if (strlen($where_clause) > 0)
			Core::$CMS['LISTS'][$menu]["where_clause"] = $where_clause;
	}
	public static function insert_cms_editor($menu,$title,$class,$table,
											 $clone_enabled,$rows)
	{
		Core::$CMS['EDITORS'][$menu] = array(
			"title" => $title,
			"class" => $class,
			"clone_enabled" => $clone_enabled,
			"table" => $table,
			"rows" => $rows
			);
	}
	public static function insert_cms_editor_row($menu,$row)
	{
		// Az aktuális szerkesztősor kiválasztása
		$editor = Core::$CMS['EDITORS'][$menu];
		// Az szerkesztőmező sorszámának meghatározása és beállítása
		$row_nums = count($editor["rows"]);
		$name = $editor["rows"][$row_nums-2]["name"];
		$number = substr($name,6);
		$row["name"] = "editor".($number+1);
		// A sor beillesztése a CMS helyére és annak ismételt beillesztése
		$editor["rows"][$row_nums-1] = $row;
		$editor["rows"][] = array("title"=>"CMS napló","name"=>"editor99","type"=>"label_cms");
		// Végül az régi szerkesztő átírása az újra
		Core::$CMS['EDITORS'][$menu] = $editor;
	}
}
?>