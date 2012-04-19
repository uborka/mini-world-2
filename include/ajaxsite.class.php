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

class AjaxSite extends Site
{
	public function load_plugins ()
  	{
  		$tb = microtime();
  		
		// A header kiküldéséhez extra funkciót fűzünk a core_show-hoz
		Core::insert_prefunction("core_show","AjaxSite","before_core_show");
		// A CMS AJAX-hoz szükséges extra fájlok bellítása
		Core::$PLUGINS['autoload'][] = Core::$DOCROOT.Core::$RELPATH."/plugins/cms";
		// Majd beállítjuk az alapértelmezett modulokat
		parent::load_plugins();
		
		$this->log("load_plugins()",$tb);
  	}
	
	public function load_theme()
	{
		$tb = microtime();
		
		$this->call_postfunctions("ajaxsite_load_theme");
		
		$this->log("load_theme()",$tb);
	}
  	
	public function load_mains()
	{
		$tb = microtime();
		
		$this->call_prefunctions("ajaxsite_load_mains");
		
	    $this->call_postfunctions("ajaxsite_load_mains");
	    
	    $this->log("load_mains()",$tb);
	}
	
	public function init()
	{
		$tb = microtime();
		
		$this->call_prefunctions("ajaxsite_init");
		
		// Az oldal sablonjának beállítása
		$this->template = "{content}";
//		$this->template = "{content}<div id=\"debug_info\">{debug_info}</div>";
		
	    // Az SVN revizíó szám beállítása
	    $this->svn_revision = Core::$MAIN['SVN'];
	    // Az alapértelmezett üzenet
	    $this->message = "";
	    
	    $this->call_postfunctions("ajaxsite_init");
	    
	    $this->log("load_init()",$tb);
	}
	
	public static function before_core_show($result)
	{
		if (!empty(Core::$MAIN['HEADER']))
		{
			foreach (explode("\n",Core::$MAIN['HEADER']) as $header)
			{
				header($header);
			}
		}
		else
			header("Content-type: text/html; charset=utf-8");
		
		return $result;
	}
}
?>