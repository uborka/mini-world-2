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

class Site extends Core
{
	public $time_before = 0;
	public static $STATIC_PARAMETERS = array("SID","user","lang","currency","template_path");
	private $sid;

	public function __construct()
	{
  		$tb = microtime();

	    // A SESSION felvétele vagy elindítása
	    if (isset($_SESSION))
	    {
			$this->sid = $_SESSION["SID"];
			session_start($this->sid);
	    }
	    else
	    {
			session_start();
			$this->sid = session_id();
			$_SESSION["SID"] = $this->sid;
	    }
	    
	    // Betölti az aktív bővítményeket
	    $this->load_plugins();

	    // A felhasználó hitelesítése
	    $this->load_user();

	    // Az általános paraméterek betöltése
	    $this->load_params();
	    
	    // Az alapértelmezett nyelv és valuta beállítása ha nincs még
    	$this->load_languages();
	    
    	// A változók begyüjtése a GET/POST változókból
	    $this->load_variants();
	    
		// A séma beállítása
		$this->load_theme();

    	// A főobjektumok betöltése
    	$this->load_mains();

	    // Az osztály tulajdonságainak beállítása
	    $this->init();

	    $this->log("ctor()",$tb);
  	}
  	
  	public function load_plugins ()
  	{
  		$tb = microtime();
  		
		// Először is beállítjuk az alapértelmezett modulokat
		Core::$PLUGINS['moduls']['page'] = "Page::modul_page";
		Core::$PLUGINS['moduls']['mainmenu'] = "PageList::modul_mainmenu";
		Core::$PLUGINS['moduls']['submenu'] = "PageList::modul_submenu";
		Core::$PLUGINS['moduls']['basemenu'] = "PageList::modul_basemenu";
		Core::$PLUGINS['moduls']['page_breadcrumb'] = "PageList::modul_page_breadcrumb";
		Core::$PLUGINS['moduls']['page_refered'] = "PageList::modul_page_refered";
		Core::$PLUGINS['moduls']['languagebox'] = "Site::modul_languagebox";
		Core::$PLUGINS['moduls']['currencybox'] = "Site::modul_currencybox";
		Core::$PLUGINS['moduls']['userbox'] = "User::modul_userbox";
		Core::$PLUGINS['moduls']['user_regist'] = "User::modul_user_regist";
		Core::$PLUGINS['moduls']['user_profile'] = "User::modul_user_profile";
		Core::$PLUGINS['moduls']['user_lost_password'] = "User::modul_user_lost_password";
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

	/**
	 * Beolvassa a GET s POST változókat a munkamenetbe.
	 **/
	public function load_variants()
	{
    	$tb = microtime();

		// A meg nem őrizendő változók törlése a munkamenetből
		$keys = array_keys($_SESSION);
	    foreach($keys as $key)
	    {
	    	if (!in_array($key,Site::$STATIC_PARAMETERS))
	    		unset($_SESSION[$key]);
	    }
	    // A POST változók feldolgozása
	    $post_variables = array_keys($_POST);
	    foreach ($post_variables as $post_variable)
	    {
	    	$this->get_variant($post_variable);
	    }
	    // A GET változó feldolgozása
	    $get_variables = array_keys($_GET);
	    foreach ($get_variables as $get_variable)
	    {
    		$this->get_variant($get_variable);
	    }
	    // A logon/logoff feldolgozása korábban történik,
	    // ezért azokat utólag töröljük
	    if (!empty($_SESSION["a"]))
		    if (in_array($_SESSION["a"],array("logon","logoff")))
		    	unset($_SESSION["a"]);
	    // Ha a permalink opció be van kapcsolva
	    // akkor az URL-t is értelmezzük
	    if (Core::$PARAM["PERMALINKS"] == 1)
	    {
	    	$u_start = strpos($_SERVER["REQUEST_URI"],"/") + 1;
	    	$u_end = strpos($_SERVER["REQUEST_URI"],"?");
	    	// $u_length = $u_end === false ? 0 : $u_end - $u_start + 1;
	    	
	    	$url = $u_end === false ?
	    		explode("/",substr($_SERVER["REQUEST_URI"], $u_start)) :
	    		explode("/",substr($_SERVER["REQUEST_URI"], $u_start, $u_end - $u_start + 1));
	    	
	    	if (count($url) > 0)
	    	{
	    		// Ha vannak a címben tagok, akkor az első értelmezése
	    		// az 'a' változóként - amennyiben az nincs megadva GET-ben
	    		// az utolsó pedig az 'a' alapján az adott rekord id-jét
	    		// helyettesíti.
	    		// Az utolsó tag mindig tartalmazza a '.html' kiterjesztést is.
	    		
    			if (strpos($url[0],".html") > 0)
    			{
    				// A megadott tag értelmezése oldalcímként,
    				// ha az nincs külön megadva
    				
    				if (empty($_SESSION["p"]))
    				{
    					$permalink = substr($url[0],0,strpos($url[0],".html"));
    					$page_id = Page::search_by_permalink($permalink);
						$_SESSION["p"] = ($page_id > 1) ? $page_id : 1;
						$this->props["p"] = $_SESSION["p"];
    				}
	    		}
	    		elseif (empty($_SESSION["a"]))
	    		{
	    			// Az első tag áthelyezése az a paraméterbe
					$_SESSION["a"] = $url[0];

					// A kiegészítők által beállított metódusok feldolgozása
					$this->call_postfunctions("site_permalink");
	    		}
	    	}
	    }
	
	    $this->log("load_variants()",$tb);
	}

	public function load_params()
	{
		$tb = microtime();
		
		$this->call_prefunctions("site_load_params");
		
		$query = "SELECT `name`,`value` FROM `".Core::$TABLE_PREFIX."core_param`";
		$result = Sql::query($query);
		while ($o = Sql::fetch_object($result))
		{
			Core::$PARAM["$o->name"] = $o->value;
		}

		$this->call_postfunctions("site_load_params");
		
		$this->log("load_params()",$tb);
	}
	
	public function load_languages()
	{
		$tb = microtime();
		
		$this->call_prefunctions("site_load_languages");
		
		if (empty($_SESSION["lang"]))
    	{
			if (($_SERVER["HTTP_ACCEPT_LANGUAGE"]!="hu") || ($_SERVER["HTTP_ACCEPT_LANGUAGE"]!="en"))
				$_SESSION["lang"] = "hu";
			else
				$_SESSION["lang"] = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		}
		if (array_key_exists("lang",$_GET) || array_key_exists("lang",$_POST))
			$this->get_variant("lang");
		
		if (empty($_SESSION["currency"]))
			$_SESSION["currency"] = Core::$PARAM["CURRENCY_DEFAULT"];
		if (array_key_exists("currency",$_GET) || array_key_exists("currency",$_POST))
			$this->get_variant("currency");
	    // A nyelvnek megfelelő szövegek beillesztése
		$lang_codes = explode(",",Core::$PARAM["LANG_ENABLED"]);
		$lang_postfix = explode(",",Core::$PARAM["LANG_POSTFIX"]);
		
		Core::$PARAM["LANG_CODES"] = $lang_codes;
		// A LANG_POSTFIX átalakítása asszociatív tömbbé
		Core::$PARAM["LANG_POSTFIX"] = array();
		for ($i = 0; $i < count($lang_codes);$i++)
		{
			Core::$PARAM["LANG_POSTFIX"][$lang_codes[$i]] = $lang_postfix[$i];
		}
		
		// A nyelvnek megfelelő szövegek beillesztése
		$query = "SELECT `name`,`value` FROM `".Core::$TABLE_PREFIX."core_lang".Core::$PARAM["LANG_POSTFIX"][$this->lang]."`";
		$result = Sql::query($query);
		while ($o = Sql::fetch_object($result))
		{
			Core::$LANG["$o->name"] = $o->value;
		}
		// A LANG_TITLES átalakítása asszociatív tömbbé
		$lang_titles = explode(",",Core::$LANG["LANG_TITLES"]);
		Core::$PARAM["LANG_TITLES"] = array();
		for ($i = 0; $i < count($lang_codes);$i++)
		{
			Core::$PARAM["LANG_TITLES"][$lang_codes[$i]] = $lang_titles[$i];
		}
		// A lokalizáció beállítása
		if (!empty(Core::$LANG['DATE_FORMAT_LOCALE']))
			setlocale(LC_ALL,Core::$LANG['DATE_FORMAT_LOCALE']);
		
		$this->call_postfunctions("site_load_languages");
		
		$this->log("load_languages()",$tb);
	}
	
	public function load_theme()
	{
		$tb = microtime();
		
		$this->call_prefunctions("site_load_theme");
		
		// Az oldal betöltése
//		if (!$this->have_property("p"))
		if (empty($_SESSION["p"]))
			$this->p = Core::$PARAM["OPEN_PAGE_ID"];
		else
			$this->p = $_SESSION["p"];
//		$query = "SELECT `theme_id` FROM `".Core::$TABLE_PREFIX."core_page` WHERE `id`=".$this->p;
//		$result = Sql::query($query);
//		if (Sql::num_rows($result) > 0)
//		{
//			$o = Sql::fetch_array($result);
//	    	Core::$MAIN['THEME'] = new Theme($o["theme_id"]);
//		    // A sablon alapján a sablonok útvonalának és a CSS meghatározása
//		    $this->template_path = Core::$MAIN['THEME']->template_path;
//		    $_SESSION["template_path"] = Core::$MAIN['THEME']->template_path;
//		    if (file_exists(Core::$DOCROOT.$this->template_path."/site.css"))
//		    	Core::$CSS[] = $this->template_path."/site.css";
//		    if (file_exists(Core::$DOCROOT.$this->template_path."/jquery-ui.css"))
//		    	Core::$CSS[] = $this->template_path."/jquery-ui.css";
//		    // A jQuery UI keretrendszer betöltése
//		    if (file_exists(Core::$DOCROOT.$this->template_path."/js/jquery-ui.min.js"))
//		    	Core::$SCRIPTS[] = $this->template_path."/js/jquery-ui.min.js";
//		}
//		else
//		{
//		    $this->template_path = "/templates/default/";
//		    $_SESSION["template_path"] = "/templates/default";
//		    Core::$CSS[] = "/templates/default/site.css";
//		}
		$query = "SELECT `theme_id` FROM `".Core::$TABLE_PREFIX."core_page` WHERE `id`=".$this->p;
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			$o = Sql::fetch_array($result);
	    	Core::$MAIN['THEME'] = new Theme($o["theme_id"]);
		    // A sablon alapján a sablonok útvonalának és a CSS meghatározása
		    $this->template_path = Core::$RELPATH.Core::$MAIN['THEME']->template_path;
		}
		else
		{
		    $this->template_path = Core::$RELPATH."/templates/default/";
		}
		$_SESSION["template_path"] = Core::$RELPATH.Core::$MAIN['THEME']->template_path;
	    if (file_exists(Core::$DOCROOT.$this->template_path."/site.css"))
	    	Core::$CSS[] = $this->template_path."/site.css";
	    if (file_exists(Core::$DOCROOT.$this->template_path."/jquery-ui.css"))
	    	Core::$CSS[] = $this->template_path."/jquery-ui.css";
	    // A jQuery UI keretrendszer betöltése
	    if (file_exists(Core::$DOCROOT.$this->template_path."/js/jquery-ui.min.js"))
	    	Core::$SCRIPTS[] = $this->template_path."/js/jquery-ui.min.js";
		
		$this->call_postfunctions("site_load_theme");
		
		$this->log("load_theme()",$tb);
	}
	
	public function load_mains()
	{
		$tb = microtime();
		
		$this->call_prefunctions("site_load_mains");
		
		// Az oldal betöltése
	    if (!$this->have_property("p"))
			$this->p = Core::$PARAM["OPEN_PAGE_ID"];
	    $_SESSION["p"] = $this->p;
	    Core::$MAIN['PAGE'] = new Page($this->p);
	    
	    Core::$MAIN['TITLE'] = Core::$MAIN['PAGE']->title;
	    Core::$MAIN['META_KEYWORDS'] = Core::$MAIN['PAGE']->meta_keywords;
	    Core::$MAIN['META_DESCRIPTION'] = Core::$MAIN['PAGE']->meta_description;
	    
	    $this->call_postfunctions("site_load_mains");
	    
	    $this->log("load_mains()",$tb);
	}
	
	public function load_user()
	{
		$tb = microtime();
		
		$this->call_prefunctions("site_load_user");
		
		if (empty($_SESSION["user"]))
		{
			if (array_key_exists("user",$_COOKIE))
		    {
		    	$user = new User($_COOKIE["user"],$_COOKIE["usercode"]);
		    	$user->load_template("userbox_logged.html");
				$_SESSION["user"] = serialize($user);
		    }
		    else
		    {
		    	$user = new User();
		    	$user->load_template("userbox_login.html");
		    	$_SESSION["user"] = serialize($user);
		    }
		}
		else
		{
			$user = NULL;
			$this->get_variant("a");
			$action = $this->have_property("a") ? $this->a : "";
			switch ($action)
			{
				case "logon":
					// A felhasználó hitelesítésének megkísérlése
			       	$user = new User($_POST["usr"],md5($_POST["psw"]));
			       	$_SESSION["user"] = serialize($user);
			       	if ($user->login_ok && $this->have_property("auto"))
			       	{
				    	if ($_SESSION["auto"]==1)
		    				$user->save_cookies($_POST["usr"],md5($_POST["psw"]));
			       	}
			       	break;
				case "logoff":
					$user = new User();
					$user->delete_cookies();
					session_unset();
					session_destroy();
					session_start();
				    $this->sid = session_id();
					$_SESSION["SID"] = $this->sid;
				    $_SESSION["user"] = serialize($user);
				    break;
				default:
					$user = array_key_exists("user",$_SESSION) ? unserialize($_SESSION["user"]) : NULL;
					break;
			}
		}
		if (!empty($user))
			if ($user->have_property("timezone"))
				date_default_timezone_set($user->timezone);
			else
				date_default_timezone_set("Europe/Budapest");
		
		$this->call_postfunctions("site_load_user");
		
		$this->log("load_user()",$tb);
	}
	
	public function init()
	{
		$tb = microtime();
		
		ob_start();
		
		$this->call_prefunctions("site_init");
		
		// Az oldal sablonjának betöltése
    	$this->load_template("site.html");
		
		// A nyomkövetéskor eltérő modulok betöltése
		// és a META robot beállítása
	    $this->meta_robot = "";
	    $this->finisher_code = "";
	    if (Core::$MODE == "DEBUG")
	    {
	    	$this->meta_robot = "noindex,nofollow";
	    	$this->template = preg_replace("({this>finisher_code})", "<div id=\"debug_info\">{debug_info}</div>", $this->template);
	    }
	    else
	    {
	    	$this->meta_robot = "index,follow";
	    	$finisher_code = new Core();
	    	$finisher_code->load_template("/templates/default/google_analytics.html");
	    	$this->finisher_code = $finisher_code;
	    }
	    // Az SVN revizíó szám beállítása
	    $this->svn_revision = Core::$MAIN['SVN'];
	    // A Host adatok beállítása
	    $this->http_host = Core::$HTTP_HOST;
	    // Az alapértelmezett üzenet
	    $this->message = ($this->user->login_ok) ? "" : $this->user->message;
	    
	    foreach (Core::get_postfunctions("site_init") as $fnname)
			call_user_func($fnname, $this);
	    
	    $this->log("load_init()",$tb);
	}
	
	public function include_moduls()
	{
		parent::include_moduls();
		
		// Az oldal illetve kategória alapján az oldal címének meghatározása
		// valamint az oldal META tagjainak beállítása
		$this->title = empty(Core::$MAIN['TITLE']) ?  "" : Core::$MAIN['TITLE'];
		$this->meta_keywords = empty(Core::$MAIN['META_KEYWORDS']) ? Core::$LANG["META_KEYWORDS_DEFAULT"] : Core::$MAIN['META_KEYWORDS'];
		$this->meta_description = empty(Core::$MAIN['META_DESCRIPTION']) ? Core::$LANG["META_DESCRIPTION_DEFAULT"] : Core::$MAIN['META_DESCRIPTION'];
		$this->site_url = Core::$HTTP_HOST;
		$this->url = $this->site_url;
		
		$this->scripts = "";
		if (count(Core::$SCRIPTS) > 0)
		{
			foreach (Core::$SCRIPTS as $script)
				$this->scripts .= "<script type=\"text/javascript\" src=\"$script\"></script>\n";
		}
		$this->css = "";
		if (count(Core::$CSS) > 0)
		{
			foreach (Core::$CSS as $css)
				$this->css .= "<link href=\"$css?v=".Core::$MAIN['SVN'].
					"\" rel=\"stylesheet\" type=\"text/css\" media=\"screen\" />\n";
		}
	}

	static public function modul_languagebox()
	{
		$box = NULL;
		$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
		
		$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
			
		if (count(Core::$PARAM["LANG_CODES"]) > 1)
		{
			$box = new Core();
			$box->load_template("languagebox.html");
			
			$box->items = array();
			foreach (Core::$PARAM["LANG_CODES"] as $language)
			{
				$item = new Core();
				$item->load_template("languagebox_item.html");
				$item->set_selected($lang == $language);
				
				$item->lang = $language;
				$item->title = Core::$PARAM["LANG_TITLES"][$language];
				
				$url = $_SERVER["QUERY_STRING"];
				if (strpos($url,"lang=")>0)
				{
					$what = substr($url,strpos($url,"lang="),7);
					$url = str_replace($what,"lang=" . $language, $url);
				}
				else
				{
					$url .= "&amp;lang=" . $language;
				}
				$item->url = "?".$url;
				
				$box->items[] = $item;
			}
		}
		
		return $box;
	}
	
	static public function modul_currencybox()
	{
		$box = NULL;
		
		$currency = array_key_exists("currency",$_SESSION) ? $_SESSION["currency"] : Core::$PARAM["CURRENCY_DEFAULT"];
			
		$currency_codes = explode(",",Core::$PARAM["CURRENCY_ENABLED"]);
		$currency_titles = explode(",",Core::$LANG["CURRENCY_TITLES"]);
					
		if (count($currency_codes) > 1)
		{
			$box = new Core();
			$box->load_template("currencybox.html");
			
			$box->items = array();
			for ($j = 0; $j < count($currency_codes); $j++)
			{
				$item = new Core();
				$item->load_template("currencybox_item.html");
				$item->code = $currency_codes[$j];
				$item->title = $currency_titles[$j];
				$item->set_selected($currency == $currency_codes[$j]);

				$url = $_SERVER["QUERY_STRING"];
				if (strpos($url,"currency=")>0)
				{
					$what = substr($url,strpos($url,"currency="),12);
					$url = str_replace($what,"currency=" . $currency_codes[$j], $url);
				}
				else
				{
					$url .= "&amp;currency=" . $currency_codes[$j];
				}
				$item->url = "?".$url;
				
				$box->items[] = $item;
			}
		}
		
		return $box;
	}
}
?>
