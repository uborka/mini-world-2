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

class Overview extends Core
{
	public static $BOXES = array();
	
	public function __construct()
	{
		$user = unserialize($_SESSION["user"]);
	
		$this->load_template("/plugins/overview/templates/overview.html");
		
		$this->leftboxes = new Core();
		$this->leftboxes->template = "{this>items}";
		$this->leftboxes->items = array();
		
		$this->rightboxes = new Core();
		$this->rightboxes->template = "{this>items}";
		$this->rightboxes->items = array();
		
		// A dobozok összeállítása
		foreach (Overview::$BOXES['left'] as $box)
		{
			$class = "Core";
			if (array_key_exists("class",$box))
				if (!empty($box['class']))
					$class = $box['class'];
			$modul = new $class;
			$modul->load_template($box['template']);
			if (array_key_exists("title",$box))
				$modul->title = $box['title'];
			if (!is_array($modul->items))
			{
				$modul->items = array();
				if (is_array($box['items']))
					foreach ($box['items'] as $bi)
					{
						$item = new Core();
						$item->title = $bi['title'];
						$item->url = $bi['url'];
					}
				foreach($modul->items as $item)
				{
					if (!empty($box['template_item']))
						$item->load_template($box['template_item']);
					else
						$item->template = "<li><a href=\"{this>url}\">{this>title}</a></li>";
				}
			}
			$this->leftboxes->items[] = $modul;
		}
		foreach (Overview::$BOXES['right'] as $box)
		{
			$class = "Core";
			if (array_key_exists("class",$box))
				if (!empty($box['class']))
					$class = $box['class'];
			$modul = new $class;
			$modul->load_template($box['template']);
			if (array_key_exists("title",$box))
				$modul->title = $box['title'];
			if (array_key_exists("items",$box))
				if (!is_array($modul->items))
				{
					$modul->items = array();
					if (is_array($box['items']))
						foreach ($box['items'] as $bi)
						{
							$item = new Core();
							$item->title = $bi['title'];
							$item->url = array_key_exists('url',$bi) ? $bi['url'] : "#";
//							$item->set_disabled($bi['enabled'] == false);
							if (is_bool($bi['right']))
								$item->set_disabled(!$bi['right']);
							elseif (is_string($bi['right'])&&is_string($bi['menu']))
								if ($user->login_ok)
									$item->set_disabled(!$user->user_rights->check($bi['menu'],$bi['right']));
								else
									$item->set_disabled(true);
							
							$modul->items[] = $item;
						}
				}
			if (is_array($modul->items))
				foreach($modul->items as $item)
				{
					if (!empty($box['template_item']))
						$item->load_template($box['template_item']);
					elseif ($item->is_disabled)
						$item->template = "<li class=\"disabled\">{this>title}</li>";
					else
						$item->template = "<li><a href=\"{this>url}\" title=\"{this>title}\">{this>title}</a></li>";
				}
			$this->rightboxes->items[] = $modul;
		}
	}
	
	public static function before_core_set_content($content)
	{
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
		switch ($action)
		{
			// A tartalmat felcserélő modulok
			default:
				if (Core::check_plugin("cms"))
				if (empty($content))
					if (is_null($action) || $action == "admin")
						$content = "{modul:overview}";
				break;
		}
		
		return $content;
	}
	
	public static function modul_overview($modul_variable)
	{
		$box = new Overview();
		
		return $box;
	}
}
?>