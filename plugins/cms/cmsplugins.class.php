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

class CmsPlugins extends CmsList
{
	public function __construct()
	{
		$tb = microtime();

		$this->items = array();
		
		$this->message = "";
		$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
		switch ($todo)
		{
			case "delete":
				$this->message = $this->delete_plugin($_SESSION["id"]);
				break;
			case "activate":
				$this->message = $this->activate_plugin($_SESSION["id"]);
				break;
			case "deactivate":
				$this->message = $this->deactivate_plugin($_SESSION["id"]);
				CmsUserGroupEditor::after_userdata_update();
				break;
		}
			

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
		$this->load_template("cmsplugins.html");
		
		// Az állandó értékek beállítása
		$this->title = $config['title'];
		// A kiválasztott menü
		$this->menu = $menu;
		
		// Az elemek betöltése
		$this->load_plugins();

		$this->log("init()",$tb);
	}
	
	public function load_plugins()
	{
		$tb = microtime();
		
		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		// Először is a /plugin mappa tartalmát kérdezzük le és ellenőrizzük
		if (is_dir(Core::$DOCROOT.Core::$RELPATH."/plugins"))
		{
			$filelist = scandir(Core::$DOCROOT.Core::$RELPATH."/plugins");
			foreach ($filelist as $filename)
			{
				if (($filename != ".") && ($filename != ".."))
				{
					if (is_dir(Core::$DOCROOT.Core::$RELPATH."/plugins/".$filename))
					{
						$query = "SELECT `id` FROM `".Core::$TABLE_PREFIX.$config['table']."`
							WHERE `path` LIKE '/".$filename."%'";
						$result = Sql::query($query);
						if (Sql::num_rows($result) == 0)
						{
							// az adatbázisban nem szereplő új bővítményeket beillesztjük a táblába
							
							// A readme.txt feldolgozása
							if (file_exists(Core::$DOCROOT.Core::$RELPATH."/plugins/".$filename."/readme.txt"))
							{
								$uid = $user->id;
								$path = "/".$filename;
								$title = "";
								$version = "";
								$depends = "";
								$depend_nums = 0;
								$copyright = "";
								$description = "";
								
								$readme = file_get_contents(Core::$DOCROOT.Core::$RELPATH."/plugins/".$filename."/readme.txt");
								$lines = explode("\n",$readme);
								foreach ($lines as $line)
								{
									switch (strtolower(substr(trim($line),0,5)))
									{
										case "plug-": $title = addslashes(trim(substr($line,9))); break;
										case "versi": $version = addslashes(trim(substr($line,9))); break;
										case "copyr": $copyright = addslashes(trim(substr($line,11))); break;
										case "descr": $description = addslashes(trim(substr($line,13))); break;
										case "depen":
											$depends = addslashes(trim(substr($line,9)));
											$depend_nums = count(explode(",",$depends));
											break;
									}
								}
								$query = "INSERT INTO `".Core::$TABLE_PREFIX.$config['table']."` " .
								"(`id`,`path`,`title`,`version`,`description`,`depends`,`depend_nums`,`option_active`,`date_create`,`user_create_id`,`date_modify`,`user_modify_id`) " .
								"VALUES " .
								"(NULL,'".$path."','$title','$version','$description<br/>$copyright','$depends',$depend_nums,0,UNIX_TIMESTAMP(),$uid,UNIX_TIMESTAMP(),$uid)";
								Sql::query($query);
							}
						}
					}
				}
			}
		}
		else
			$this->message = $this->create_message("Hiányzik a bővítmények mappája!","error");
		
		// A query összeállítása
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX.$config['table']."` ";
		// Rendezési sorrend beállítása
		$query .= " ORDER BY `option_active` DESC,`title` ASC";
		
		$result = Sql::query($query);
		
		while ($o = Sql::fetch_array($result))
		{
			// Ha az adott elem mappája már nem létezik, akkor töröljük a rekordját
			$item = new Core($o);
			
			$item->load_template("cmsplugins_item.html");
			$item->state = ($item->option_active == 1) ? "active" : "inactive";
			$item->action_link = ($item->option_active == 1)
				? "<a href=\"?a=cmslist&m=$menu&todo=deactivate&id=".$o["id"]."\" title=\"A bővítmény kikapcsolása\">Kikapcsolás</a>"
				: "<a href=\"?a=cmslist&m=$menu&todo=activate&id=".$o["id"]."\" title=\"A bővítmény bekapcsolása\">Aktiválás</a>";
			$item->delete_link = "<a href=\"?a=cmslist&m=$menu&todo=delete&id=".$o["id"]."\" title=\"A bővítmény törlése\">Törlés</a>";
			 
			$this->items[] = $item;
		}
		
		$this->log("load_plugins()",$tb);
	}
	
	private function remove_dir($path)
	{
		$filelist = scandir($path);
		foreach ($filelist as $filename)
		{
			if (!in_array($filename,array(".","..")))
			{
				if (is_dir($filename))
					remove_dir($path);
				else
					unlink($filename);
			}
		}
	}
	
	public function delete_plugin($id = 0)
	{
		$tb = microtime();

		$message = "";
		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		if ($user->user_rights->check($menu,"delete") && ($id > 0))
		{
			// A bővítmény állományainak törlése
			$query = "SELECT `path` FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (`id`=$id) LIMIT 1";
			$result = Sql::query($query);
			if (Sql::sql_error() == "")
			{
				$o = Sql::fetch_array($result);
				$path = Core::$DOCROOT.$o["path"];
				if (is_dir($path))
				{
					$this->remove_dir($path);
				}
			}
			// A bővítmény rekordjának törlése
			$query = "DELETE FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (`id`=$id) LIMIT 1";
			$result = Sql::query($query);
			if (Sql::sql_error() == "")
				$message = $this->create_message("A bővítményt sikeresen törölte.","ok");
			else
				$message = $this->create_message("A bővítményt nem sikerült törölni! (SQL hiba)","error");
		}
		else
			$message = $this->create_message("Önnek nincs joga törölni bővítményt!","error");

		$this->log("delete_plugin()",$tb);
		
		return $message;
	}
	
	public function activate_plugin($id = 0)
	{
		$tb = microtime();

		$message = "";
		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		if ($user->user_rights->check($menu,"edit") && ($id > 0))
		{
			// A függőségek ellenőrzése
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (id = $id)";
			$result = Sql::query($query);
			if ($o = Sql::fetch_array($result))
			{
				$errors = array();
				$depends = explode(",",$o["depends"]);
				foreach ($depends as $depend)
				{
					if (!Core::check_plugin(trim($depend)))
						$errors[] = $depend;
				}
				if (count($errors) == 0)
				{
					// A bővítmény rekordjának módosítása
					$query = "UPDATE `".Core::$TABLE_PREFIX.$config['table']."` SET `option_active` = 1 WHERE (`id`=$id) LIMIT 1";
					$result = Sql::query($query);
					if (Sql::sql_error() == "")
						$message = $this->create_message("A bővítményt sikeresen aktiválta.","ok");
					else
						$message = $this->create_message("A bővítményt aktiválása sikertelen! (SQL hiba)","error");
				}
				else
				{
					$depend_list = implode(", ",$errors);
					$message = $this->create_message("A bővítményt aktiválása sikertelen! (Függőségi hiba: $depend_list)","error");
				}
			}
			else
				$message = $this->create_message("A bővítményt aktiválása sikertelen! (SQL hiba)","error");
		}
		else
			$message = $this->create_message("Önnek nincs joga bővítményeket aktiválni!","error");

		$this->log("activate_plugin()",$tb);
		
		return $message;
	}
	
	public function deactivate_plugin($id = 0)
	{
		$tb = microtime();

		$message = "";
		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		if ($user->user_rights->check($menu,"edit") && ($id > 0))
		{
			// A bővítmény rekordjának módosítása
			$query = "UPDATE `".Core::$TABLE_PREFIX.$config['table']."` SET `option_active` = 0 WHERE (`id`=$id) LIMIT 1";
			$result = Sql::query($query);
			if (Sql::sql_error() == "")
			{
				$query = "SELECT * FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (`id`=$id) LIMIT 1";
				$result = Sql::query($query);
				$o = Sql::fetch_array($result);
				$plugin = substr($o["path"],1);
				
				$query = "SELECT `id` FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE FIND_IN_SET('$plugin',`depends`)";
				$result = Sql::query($query);
				while ($o = Sql::fetch_array($result))
				{
					$query = "UPDATE `".Core::$TABLE_PREFIX.$config['table']."` SET `option_active` = 0 WHERE (`id`=".$o["id"].") LIMIT 1";
					Sql::query($query);
				}
				
			
				$message = $this->create_message("A bővítményt sikeresen kikapcsolta.","ok");
			}
			else
				$message = $this->create_message("A bővítményt kikapcsolása sikertelen! (SQL hiba)","error");
		}
		else
			$message = $this->create_message("Önnek nincs joga bővítményeket kikapcsolni!","error");

		$this->log("deactivate_plugin()",$tb);
		
		return $message;
	}
}
?>