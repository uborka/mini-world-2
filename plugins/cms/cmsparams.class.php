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

class CmsParams extends Core
{
	public function __construct()
	{
		$tb = microtime();

		$this->items = array();
		
		$this->message = "";
		$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
		if ($todo == "update")
			$this->message = $this->update_params();

		// A lista megjelenítése a kért nézetben
		$this->init();

		$this->log("ctor()",$tb);
	}

	public function init()
	{
		$tb = microtime();

		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		// Az oldal sablonjnak betöltése
		$this->load_template("cmsparams.html");
		
		// Az állandó értékek beállítása
		$this->title = $config['title'];
		// A kiválasztott menü
		$this->menu = $menu;
		
		// A lapok betöltése
		$this->tabs = "";
		$this->description = "";
		$keys = array_keys($config['tabs']);
		foreach ($keys as $key)
		{
			$li = "<li><a href=\"#".$key."\">".$config['tabs'][$key]['title']."</a></li>";
			$div = new Core();
			$div->load_template($config['tabs'][$key]['template']);
			
			$this->tabs .= $li;
			$this->description .= $div->template;
		}
		
		// A beállítások bemásolása az osztály tulajdonságai közé
		$query = "SELECT `name`,`value` FROM `".Core::$TABLE_PREFIX.$config['table']."` ORDER BY `id`";
		$result = Sql::query($query);
		while ($o = Sql::fetch_array($result))
		{
			$this->props[strtolower($o["name"])] = $o["value"];
		}
		
		$this->log("init()",$tb);
	}
	
	public function update_params()
	{
		$tb = microtime();

		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		$message = "";
		
		if ($user->user_rights->check($menu,"edit"))
		{
			$errors = "";
			$keys = array_keys(Core::$PARAM);
			$session_keys = array_keys($_SESSION);
			foreach ($keys as $key)
			{
				if (in_array(strtolower($key),$session_keys))
				{
					$query = "UPDATE `".Core::$TABLE_PREFIX.$config['table']."`
						SET `value` = '".addslashes($_SESSION[strtolower($key)])."',
							`date_modify`=UNIX_TIMESTAMP(),
							`user_modify_id`=".$user->id."
						WHERE `name` = '$key' LIMIT 1;";
					Sql::query($query);
					$errors .= Sql::sql_error();
				}
			}
			if ($errors == "")
				$message = $this->create_message("A beállítások mentése megtörtént","ok");
			else
				$message = $this->create_message("A beállítások mentése sikertelen! (SQL hiba)","error");
		}
		else
			$message = $this->create_message("Önnek nincs joga módosítani a beállításokat!","error");

		$this->log("update_params()",$tb);
		
		return $message;
	}
}
?>