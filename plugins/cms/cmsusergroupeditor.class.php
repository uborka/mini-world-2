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

class CmsUserGroupEditor extends CmsEditor
{
	public function prepare_sql()
	{
		// A csoport általános jellemzőit kezelő SQL-ek összeállítása
		parent::prepare_sql();
		
		// A jogok kezelését kezelő SQL-ek összeállítása
		$menu = $_SESSION["m"];
		$id = $_SESSION["id"];
		$user = unserialize($_SESSION["user"]);
			
		$selects = array();
		$inserts = array();
		$updates = array();
		
		$rights = array();
		$keys = array_keys($_SESSION);
		foreach ($keys as $key)
		{
			if (strpos($key,"right") === 0)
			{
				$right = explode("-",$key);
				$rights[$right[1]] = $_SESSION[$key];
			}
		}
		
		$block_keys = array_keys(Core::$CMS['MENUS']);
		foreach ($block_keys as $block_key)
		{
			$item_keys = array_keys(Core::$CMS['MENUS'][$block_key]['items']);
			foreach ($item_keys as $item_key)
			{
				if (in_array($item_key,array_keys($rights)))
					$r = join($rights[$item_key],",");
				else
					$r = "";
				
				$selects[] = "SELECT `id` FROM `".Core::$TABLE_PREFIX."core_user_right` " .
						"WHERE `user_group_id`=$id AND `menu`='$item_key' LIMIT 1;";
				$updates[] = "UPDATE `".Core::$TABLE_PREFIX."core_user_right` " .
						"SET `rights`='$r',`date_modify`=UNIX_TIMESTAMP(),`user_modify_id`=$user->id " .
						"WHERE `user_group_id`=$id AND `menu`='$item_key' LIMIT 1;";
				$inserts[] = "INSERT INTO `".Core::$TABLE_PREFIX."core_user_right` " .
						"(`user_group_id`,`menu`,`rights`,`date_create`,`date_modify`,`user_create_id`,`user_modify_id`) " .
						"VALUES " .
						"($id,'$item_key','$r',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$user->id,$user->id);";
			}
		}
		for ($i = 0; $i < count($selects); $i++)
		{
			$query = $selects[$i];
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				$this->update_sql .= "\n".$updates[$i];
				$this->insert_sql .= "\n".$updates[$i];
			}
			else
			{
				$this->update_sql .= "\n".$inserts[$i];
				$this->insert_sql .= "\n".$inserts[$i];
			}
		}
	}

	public function init()
	{
		$menu = $_SESSION["m"];
		$config = Core::$CMS['EDITORS'][$menu];
		
		$this->items = array();
		foreach ($config['rows'] as $row)
		{
			$this->items[] = call_user_func(CmsEditor::$EDITOR_FUNCTIONS[$row['type']],$row,$this->record);
		}
	}

	public static function generate_rightlist($config,$record)
	{
		$row = new Core();
		$row->load_template("cmsusergroupeditor_rightlist.html");
		$row->title = $config['title'];
		$row->name = $config['name'];
		
//		if ($this->record["is_admin"] == 0)
		if ($record["is_admin"] == 0)
			$row->items = "<span class=\"red\">Nincs CMS hozzáférés.</span>";
		else
		{
			$block_keys = array_keys(Core::$CMS['MENUS']);
			foreach ($block_keys as $block_key)
			{
				$block = new Core();
				$block->template = "<h3>".Core::$CMS['MENUS'][$block_key]['title']."</h3>";
				$row->items[] = $block;
				
				$item_keys = array_keys(Core::$CMS['MENUS'][$block_key]['items']);
				foreach ($item_keys as $item_key)
				{
					$id = 0;
					$rights = array();
					$query = "SELECT `id`,`rights` " .
						"FROM `".Core::$TABLE_PREFIX."core_user_right` " .
						"WHERE (`user_group_id` = ".$_SESSION["id"].") " .
							"AND (`menu` = '$item_key')";
					$result = Sql::query($query);
					if ($o = Sql::fetch_array($result))
					{
						$id = $o["id"];
						$rights = explode(",",$o["rights"]);
					}
					
					$item = new Core();
					$item->template = "<div><h4>".(is_array(Core::$CMS['MENUS'][$block_key]['items'][$item_key]) ? Core::$CMS['MENUS'][$block_key]['items'][$item_key]["title"] : Core::$CMS['MENUS'][$block_key]['items'][$item_key])."</h4>{this>rights}</div>";
					
					$item->rights = "";
					$right_keys = array_keys(Core::$CMS['RIGHTS'][$item_key]);
					foreach ($right_keys as $right_key)
					{
						$is_checked = "";
						if ($id > 0)
							if (in_array($right_key,$rights))
								$is_checked = "checked=\"checked\"";
						
						$item->rights .= "<label>" .
							"<input type=\"checkbox\" name=\"right-".$item_key."[]\" value=\"$right_key\" " .
							$is_checked .
							"/> " .
							Core::$CMS['RIGHTS'][$item_key][$right_key] .
							"</label> ";
					}
						
					$row->items[] = $item;
				}
			}
		}
		
//		$this->items[] = $row;
		return $row;
	}

	public static function after_userdata_update($id)
	{
		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		
		// Ha az aktuálisan belépett felhasználót érinti a
		if ($user->login_ok == 1)
		{
			switch ($menu)
			{
				case "USER":
					if ($user->id == $id)
					{
						$user->init();
						$_SESSION["user"] = serialize($user);
					}
					break;
				case "USER_GROUP":
					if ($user->user_group->id == $id)
					{
						$user->init();
						$_SESSION["user"] = serialize($user);
					}
					break;
			}
		}
	}
}
?>