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

class CmsEditor extends Core
{
	static $EDITOR_FUNCTIONS = array(
		"editor_text" => "CmsEditor::generate_editor_text",
		"editor_password" => "CmsEditor::generate_editor_password",
		"editor_date" => "CmsEditor::generate_editor_date",
		"editor_datetime" => "CmsEditor::generate_editor_datetime",
		"editor_price" => "CmsEditor::generate_editor_price",
		"editor_image" => "CmsEditor::generate_editor_image",
		"editor_int" => "CmsEditor::generate_editor_int",
		"editor_memo" => "CmsEditor::generate_editor_memo",
		"editor_ckeditor" => "CmsEditor::generate_editor_ckeditor",
		"editor_permalink" => "CmsEditor::generate_editor_permalink",
		"label" => "CmsEditor::generate_label",
		"label_date" => "CmsEditor::generate_label_date",
		"label_datetime" => "CmsEditor::generate_label_datetime",
		"label_hashed" => "CmsEditor::generate_label_hashed",
		"label_user" => "CmsEditor::generate_label_user",
		"label_link" => "CmsEditor::generate_label_link",
		"label_cms" => "CmsEditor::generate_label_cms",
		"radio" => "CmsEditor::generate_radio",
		"select_parent" => "CmsEditor::generate_select_parent",
		"select_by_id" => "CmsEditor::generate_select_by_id",
		"dnd" => "CmsEditor::generate_dnd",
		);
	protected $record;

	public function __construct()
	{
		$tb = microtime();

		// Az oldal sablonjnak betöltése
		$this->load_template("cmseditor.html");

		$menu = $_SESSION["m"];
		$config = Core::$CMS['EDITORS'][$menu];
		$id = empty($_SESSION["id"]) ? NULL : $_SESSION["id"];

		// Az elvégzendő feladat elvégzése és visszajelzés
		$this->message = $this->todo();

		// A szerkesztő egyéb beállításai
		$this->title = $config['title'];
		$this->menu = $menu;
		$this->backurl = $_SESSION["backurl"];
		$this->clone_disabled = $config['clone_enabled'] ? "" : "disabled=\"disabled\"";

		// Az előző és következő rekord meghatározása
		$this->set_prev_next();

		// A szerkesztősorok feldolgozása
		$this->init();

		$this->log("ctor()",$tb);
	}

	public function prepare_sql($rows = null)
	{
		$menu = $_SESSION["m"];
		$lang = empty($_SESSION["lang"]) ? Core::$PARAM["LANG_DEFAULT"] : $_SESSION["lang"];
		$config = Core::$CMS['EDITORS'][$menu];
		$table = is_array($config['table']) ? $config['table'][$lang] : $config['table'];
		$id = empty($_SESSION["id"]) ? NULL : $_SESSION["id"];
		$user = unserialize($_SESSION["user"]);

		if (is_null($rows))
			$rows = $config["rows"];

		$fieldnames = "";
		$values = "";
		$updates = "";

		if (!empty($rows))
			for ($i = 0; $i < count($rows); $i++)
			{
				if (!empty($rows[$i]['field']))
				{
					switch ($rows[$i]['type'])
					{
					case "editor_text":
					case "editor_memo":
					case "editor_ckeditor":
						if ($fieldnames != "")
						{
							$fieldnames .= ",";
							$values .= ",";
							$updates .= ",";
						}
						$fieldnames .= "`".$rows[$i]["field"]."`";
						if (array_key_exists($rows[$i]["name"],$_SESSION))
						{
							$values .= "CONVERT('".$_SESSION[$rows[$i]["name"]]."' USING utf8)";
							$updates .= "`".$rows[$i]["field"]."`=CONVERT('".$_SESSION[$rows[$i]["name"]]."' USING utf8)";
						}
						break;
					case "label_date":
					case "label_datetime":
					case "editor_date":
					case "editor_datetime":
					if ($fieldnames != "")
						{
							$fieldnames .= ",";
							$values .= ",";
							$updates .= ",";
						}
						$fieldnames .= "`".$rows[$i]["field"]."`";
						if (array_key_exists($rows[$i]["name"],$_SESSION))
						{
							if ((int)$_SESSION[$rows[$i]["name"]] == 0)
							{
								$values .= "0";
								$updates .= "`".$rows[$i]["field"]."`=0";
							}
							else
							{
								$values .= "UNIX_TIMESTAMP('".$_SESSION[$rows[$i]["name"]]."')";
								$updates .= "`".$rows[$i]["field"]."`=UNIX_TIMESTAMP('".$_SESSION[$rows[$i]["name"]]."')";
							}
						}
						break;
					case "editor_password":
						// Az érkező jelszavak ellenőrzése
						$fielname = $rows[$i]["name"];
						$psw1 = empty($_SESSION[$fielname]) ? "" : $_SESSION[$fielname];
						$psw2 = empty($_SESSION[$fielname."_retype"]) ? "" : $_SESSION[$fielname."_retype"];
						if ((strlen(trim($psw1)) > 0) && (strlen(trim($psw2)) > 0))
						{
							if (($psw1 == $psw2))
							{
								if ($fieldnames != "")
								{
									$fieldnames .= ",";
									$values .= ",";
									$updates .= ",";
								}
								$fieldnames .= "`".$rows[$i]["field"]."`";
								if (array_key_exists($rows[$i]["name"],$_SESSION))
								{
									$values .= "'".md5($_SESSION[$rows[$i]["name"]])."'";
									$updates .= "`".$rows[$i]["field"]."`='".md5($_SESSION[$rows[$i]["name"]])."'";
								}
							}
							else
							{
								$this->message = $this->create_message("A megadott jelszavak nem egyeznek!","error","3");
							}
						}
						elseif ((strlen(trim($psw1)) > 0) || (strlen(trim($psw2)) > 0))
						{
							$this->message = $this->create_message("A megadott jelszavak hossza nem megyegyező!","error","3");
						}
						break;
					case "keywords":
						// Kulcsszavak összeállítása
						$k = "";
						for ($j = 0; $j < count($rows); $j++)
						{
							if (in_array($rows[$j]['field'],$rows[$i]['keyfields']))
							{
								switch ($rows[$j]["type"])
								{
									case "label_user":
									case "select_by_id":
									case "dnd":
										$tbl = substr($rows[$j]['field'],0,strpos($rows[$j]['field'],"_id")) . "s";
										$query = "SELECT GROUP_CONCAT(`title` SEPARATOR ',') AS `title` FROM `".$tbl."` WHERE `id` IN (".$_SESSION[$rows[$j]['name']].")";
										$result = Sql::query($query);
										if ($result)
											while ($o = Sql::fetch_array($result))
											{
												if (strlen($k)>0) $k .= " ";
												$k .= $o["title"];
											}
										break;
									case "select_parent":
										$query = "SELECT GROUP_CONCAT(`title` SEPARATOR ',') AS `title` FROM `".$table."` WHERE `id` IN (".$_SESSION[$rows[$j]['name']].")";
										$result = Sql::query($query);
										if ($result)
											while ($o = Sql::fetch_array($result))
											{
												if (strlen($k)>0) $k .= " ";
												$k .= $o["title"];
											}
										break;
									case "radio":
										if (strlen($k)>0) $k .= " ";
											$k .= Core::$CMS['ENUMERATIONS'][strtoupper($menu.".".$rows[$j]['field'])][$_SESSION[$rows[$j]['name']]];
										break;
									case "label_cms":
									case "label_id":
									case "editor_password":
									case "editor_ckeditor":
									case "keywords":
										// Az ilyen típusú adatok sose kerülnek bele
										break;
									default:
										if (strlen($k)>0) $k .= " ";
										$k .= $_SESSION[$rows[$j]["name"]];
										break;
								}
							}
						}

						$fields .= "`".$rows[$i]["field"]."`";
						$values .= "'$k'";
						$update_sql .= "`".$rows[$i]["field"]."`='$k'";
						break;
					default:
						if ($fieldnames != "")
						{
							$fieldnames .= ",";
							$values .= ",";
							$updates .= ",";
						}
						$fieldnames .= "`".$rows[$i]["field"]."`";
						if (array_key_exists($rows[$i]["name"],$_SESSION))
						{
							$values .= "'".$_SESSION[$rows[$i]["name"]]."'";
							$updates .= "`".$rows[$i]["field"]."`='".$_SESSION[$rows[$i]["name"]]."'";
						}
						break;
					}
				}
			}

		$this->select_sql = "SELECT ".$fieldnames.",`date_create`,`date_modify`,`user_create_id`,`user_modify_id`
			FROM `".Core::$TABLE_PREFIX.$table."`";
		$this->update_sql = "UPDATE `".Core::$TABLE_PREFIX.$table."`
			SET ".$updates.",`date_modify`=UNIX_TIMESTAMP(),`user_modify_id`=$user->id
			WHERE `id`=".$id.";";
		if (is_array($config['table']))
		{
			$this->insert_sql = "";
			foreach ($config['table'] as $tbl)
			{
				$this->insert_sql .= "INSERT INTO `".Core::$TABLE_PREFIX.$tbl."`
					(".$fieldnames.",`date_create`,`date_modify`,`user_create_id`,`user_modify_id`)
					VALUES
					(".$values.",UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$user->id,$user->id);\n";
			}
		}
		else
			$this->insert_sql = "INSERT INTO `".Core::$TABLE_PREFIX.$table."`
				(".$fieldnames.",`date_create`,`date_modify`,`user_create_id`,`user_modify_id`)
				VALUES
				(".$values.",UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$user->id,$user->id);";
	}

	public function todo()
	{
		$this->prepare_sql();

		$id = empty($_SESSION["id"]) ? NULL : $_SESSION["id"];
		$menu = $_SESSION["m"];
		$config = Core::$CMS['EDITORS'][$menu];

		$message = NULL;
		$actual_message = $this->have_property('message') ? $this->message : NULL;
		if (!is_object($actual_message))
		{
			// todo értékei: edit, update, new, insert
			$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "edit";

			switch ($todo)
			{
				case "new":
					// A szerkesztő kialakítása a kapott paraméterek alapájn
					$this->action = "insert";
					$this->id = "'NULL'";
					break;
				case "insert":
					// Beszúrás
					Sql::query($this->insert_sql);
					if (strlen(Sql::sql_error()) > 0)
					{
						$message = $this->create_message("A rekord mentése sikertelen! (SQL hiba)","error","1");
						$this->action = "insert";
						$this->id = "'NULL'";
					}
					else
					{
						$id = Sql::insert_id();
						$_SESSION["id"] = $id;

						$this->debug($id);

						$message = $this->create_message("Sikeresen mentve.","ok","1");
						// Utófeldolgozási funkciók hívása
						if (!empty($config["after_insert"]))
							call_user_func($config["after_insert"],$id);
						// Sikeres futás estén a szerkesztő kialakítása
						$this->select();
						$this->action = "update";
						$this->id = $id;
					}
					break;
				case "edit":
					$this->select();
					$this->action = "update";
					$this->id = $id;
					break;
				case "update":
					Sql::query($this->update_sql);
					if (strlen(Sql::sql_error()) > 0)
						$message = $this->create_message("A rekord mentése sikertelen! (SQL hiba)","error","1");
					else
					{
						$message = $this->create_message("Sikeresen mentve.","ok","1");
						// Utófeldolgozási funkciók hívása
						if (!empty($config["after_update"]))
							call_user_func($config["after_update"],$id);
					}
					$this->select();
					$this->action = "update";
					$this->id = $id;
					break;
			}
		}
		else
		{
			if ($id == "NULL")
			{
				// new
				$this->action = "insert";
				$this->id = "'NULL'";
			}
			else
			{
				// edit
				$this->select();
				$this->action = "update";
				$this->id = $id;
			}
			$message = $this->message;
		}

		return $message;
	}

	public function select()
	{
		$tb = microtime();

		// A lekérdezést végző SQL összeállítása és futtatása
		$id = empty($_SESSION["id"]) ? NULL : $_SESSION["id"];

		if (is_numeric($id))
			$result = Sql::query($this->select_sql." WHERE `id`=$id");
		else
			$result = false;
		if ($result)
		{
			$this->record = Sql::fetch_array($result);
		}
		else
		{
			$this->record = array();
			$this->message = $this->create_message("A rekord beolvasása meghíjúsult! (SQL hiba)","error","2");
		}

		$this->log("select()",$tb);
	}

	public function init()
	{
		Core::insert_script("/plugins/cms/js/jquery.tools-tooltip.min.js");

		$menu = $_SESSION["m"];
		$config = Core::$CMS['EDITORS'][$menu];

		$this->items = array();
		foreach ($config['rows'] as $row)
		{
			$this->items[] = call_user_func(CmsEditor::$EDITOR_FUNCTIONS[$row['type']],$row,$this->record);
		}
	}

	public function set_prev_next()
	{
		$tb = microtime();

		$menu = $_SESSION["m"];
		$id = empty($_SESSION["id"]) ? NULL : $_SESSION["id"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];

		// A kereső query összeállítása
		$listparams = explode("&",$this->backurl);
		$this->order_field = $config['default_field'];
		$this->order_dir = $config['default_direction'];
		$this->filter = NULL;
		$this->fmin = NULL;
		$this->fmax = NULL;

		$query = "SELECT `id` FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (1=1) ";

		foreach ($listparams as $listparam)
		{
			$param = explode("=",$listparam);
			switch ($param[0])
			{
				case "order_field":
					$this->order_field = $param[1];
					break;
				case "order_dir":
					$this->order_dir = $param[1];
					break;
				case "filter":
					$this->filter = $param[1];
					break;
				case "fmin":
					$this->fmin = $param[1];
					break;
				case "fmax":
					$this->fmax = $param[1];
					break;
			}
		}
		// A szűrő beállítása
		$filter = "";
		if (!is_null($this->filter))
		{
			$index = array_search($this->order_field,$config['fields']);
			switch ($config['types'][$index])
			{
				case "text":
				case "email":
					$filter = "AND (`".$this->order_field."` LIKE '".$this->filter."%') ";
					break;
				case "id":
				case "enum":
					$filter = "AND (`".$this->order_field."` = ".$this->filter.") ";
					break;
				case "included":
				case "parent":
				case "user":
					// Először is az azonosító kulcsok lekérdezése
					$table = $table = substr($this->order_field,0,strpos($this->order_field,"_id")) . "s";
					$subquery = "SELECT GROUP_CONCAT(`id` SEPARATOR ',') AS `ids`
						FROM `".Core::$TABLE_PREFIX.$table."`
						WHERE `title` LIKE '".$this->filter."%'";
					$subresult = Sql::query($subquery);
					if (Sql::num_rows($subresult) > 0)
					{
						$o = Sql::fetch_array($subresult);
						$filter = "AND `".$this->order_field."` IN(".$o["ids"].") ";
					}
					break;
			}
		}
		elseif (!(is_null($this->fmin) && is_null($this->fmax)))
		{
			$index = array_search($this->order_field,$config['fields']);
			switch ($config['types'][$index])
			{
				case "currency":
				case "int":
					$filter = "AND (`".$this->order_field."` BETWEEN ".$this->fmin." AND ".$this->fmax.") ";
					break;
				case "date":
					$filter = "AND (`".$this->order_field."` BETWEEN '".$this->fmin." 00:00:00' AND '".$this->fmax." 23:59:59') ";
					break;
			}
		}
		$query .= $filter;
		// Az extra szűrési feltétel beállítása
		if (!empty($config['where_clause']))
			$query .= " AND ".$config['where_clause']." ";
		// Rendezési sorrend beállítása
		$query .= " ORDER BY `".$this->order_field."` ".$this->order_dir;

		$result = Sql::query($query);

		$prev_id = 0;
		$next_id = 0;
		$found = false;

		while ($o = Sql::fetch_array($result))
		{
			// Ha az aktuális rekordhoz értünk
			if ($o["id"] == $id)
			{
				$found = true;
			}
			// Ha az előző volt az aktuális rekord
			elseif ($found)
			{
				$next_id = $o["id"];
				break;
			}
			// Ellenben lépünk a következőre, ez az előző
			else
			{
				$prev_id = $o["id"];
			}
		}

		if ($prev_id > 0)
			$this->prevurl = "?a=cmseditor&m=$menu&id=$prev_id&backurl=".urlencode($this->backurl);
		else
			$this->prevurl = "";

		if ($next_id > 0)
			$this->nexturl = "?a=cmseditor&m=$menu&id=$next_id&backurl=".urlencode($this->backurl);
		else
			$this->nexturl = "";

		$this->prevurl_disabled = strlen($this->prevurl) > 0 ? "" : "disabled=\"disabled\"";
		$this->nexturl_disabled = strlen($this->nexturl) > 0 ? "" : "disabled=\"disabled\"";

		$this->log("set_prev_next()",$tb);
	}

	static public function generate_label($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = is_null($record[$config['field']])
			? (empty($config['default']) ? "N/A" : $config['default']) : $record[$config['field']];

		return $row;
	}

	static public function generate_label_date($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = empty($record[$config['field']])
			? (empty($config['default']) ? "N/A" : $config['default']) : Core::unix_to_date($record[$config['field']]);

		return $row;
	}

	static public function generate_label_datetime($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = empty($record[$config['field']])
			? (empty($config['default']) ? "N/A" : $config['default']) : Core::unix_to_datetime($record[$config['field']]);

		return $row;
	}

	static public function generate_label_hashed($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label_hashed.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = empty($record[$config['field']])
			? $config['default'] : $record[$config['field']];

		return $row;
	}

	static public function generate_label_user($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label_user.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		if (is_null($record[$config['field']]))
		{
			$row->value = $config['default'];
			$row->userid = "N/A";
			$row->username = "N/A";
			$row->fullname = "N/A";
			$row->email = "";
			$row->phone = "-";
		}
		else
		{
			$usr = new User(NULL,NULL);
			$usr->read($record[$config['field']]);

			$row->value = $record[$config['field']];
			$row->userid = $usr->id;
			$row->username = $usr->username;
			$row->fullname = $usr->fullname;
			$row->email = $usr->email;
			$row->phone = (strlen($usr->phone)>0 ? $usr->phone : "-");
		}

		return $row;
	}

	static public function generate_label_link($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label_link.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];

		return $row;
	}

	static public function generate_label_cms($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_label_cms.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];

		// CREATE
		$usr = new User(NULL,NULL);
		$usr->read($record["user_create_id"]);
		$row->user_create = "N/A";
		if (!is_null($usr))
			$row->user_create = $usr->fullname;
		else
			$row->user_create = ">[".$$record["user_create"]."]";
		$row->date_create = Core::unix_to_datetime($record["date_create"]);

		// MODIFY
		$usr->read($record["user_modify_id"]);
		$row->user_modify = "N/A";
		if (!is_null($usr))
			$row->user_modify = $usr->fullname;
		else
			$row->user_modify = ">[".$$record["user_create"]."]";
		$row->date_modify = Core::unix_to_datetime($record["date_modify"]);

		return $row;
	}

	static public function generate_editor_text($config,$record)
	{
		Core::insert_script("/js/jquery.plugins/jquery.limit.min.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_text.html");
		$row->title = $config['title'];
		$row->name = $config['name'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->class = (($config['field'] == "title")||($config['field'] == "name"))
			? "input_title" : "";
		$row->maxlength = ($config['maxlength'] > 0) ? $config['maxlength'] : "";
		$row->default = $config['default'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		return $row;
	}

	static public function generate_editor_password($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_password.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->default = "";
		$row->clonable = "false";

		return $row;
	}

	static public function generate_editor_date($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_date.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->default = $config['default'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$date = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		if ($date == 0)
			$row->value = 0;
		else
			$row->value = strftime("%Y-%m-%d",$date);

		return $row;
	}

	static public function generate_editor_datetime($config,$record)
	{
		Core::insert_script("/plugins/cms/js/jquery.timepicker.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_datetime.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->default = $config['default'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : strftime("%Y-%m-%d %H:%M",$record[$config['field']]);

		return $row;
	}

	static public function generate_editor_price($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_price.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->currency = strtoupper(empty($config["currency"]) ? "HUF" : $config["currency"]);
		$row->default = $config['default'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default']
			: number_format($record[$config['field']],Core::$LANG["PRICE_DECIMAL_PLACES_".strtoupper($row->currency)],
				Core::$LANG["PRICE_DECIMAL_SEPARATOR_".strtoupper($row->currency)],"");

		return $row;
	}

	static public function generate_editor_int($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_int.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->default = $config['default'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->unit = empty($config['unit']) ? "" : $config['unit'];

		return $row;
	}

	static public function generate_editor_image($config,$record)
	{
		Core::insert_script("/js/jquery.plugins/jquery.limit.min.js");
		Core::insert_script("/include/ckfinder/ckfinder.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_image.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->maxlength = ($config['maxlength'] > 0) ? $config['maxlength'] : "";

		if (file_exists(Core::$DOCROOT.$row->value) && (strlen($row->value) > 0))
		{
			$info = getimagesize(Core::$DOCROOT.$row->value);
			$row->preview = "<img src=\"/plugins/cms/templates/images/icon_link.gif\" " .
					"border=\"0\" onclick=\"\$('#image".$config['name']."').dialog({modal:true,width:'auto'});\"/>" .
					$info[0]."×".$info[1]."px" .
					"<div style=\"display:none;margin:10px;background-color:#fff;\" " .
					"id=\"image".$config['name']."\" title=\"Előnézet\">" .
					"<img src=\"".Core::$HTTP_HOST.$row->value."\"></div>";
		}
		else
			$row->preview = "";

		return $row;
	}

	static public function generate_editor_memo($config,$record)
	{
		Core::insert_script("/js/jquery.plugins/jquery.limit.min.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_memo.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->maxlength = empty($config['maxlength']) ? "" : $config['maxlength'];
		$row->rownums = empty($config['rownums']) ? 3 : $config['rownums'];

		return $row;
	}

	static public function generate_editor_ckeditor($config,$record)
	{
		Core::insert_script("/include/ckeditor/ckeditor.js");
		Core::insert_script("/include/ckfinder/ckfinder.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_ckeditor.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->toolbar = $config['toolbar'];

		return $row;
	}

	static public function generate_editor_permalink($config,$record)
	{
		Core::insert_script("/js/jquery.plugins/jquery.limit.min.js");

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_editor_premalink.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$row->value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		$row->maxlength = ($config['maxlength'] > 0) ? $config['maxlength'] : "";

		return $row;
	}

	static public function generate_radio($config,$record)
	{
		$menu = $_SESSION["m"];

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_radio.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$enumeration = empty($config['enumeration']) ?
			Core::$CMS['ENUMERATIONS'][strtoupper($menu).".".strtoupper($config['field'])] :
			$config['enumeration'];
		$selected_value = is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']];
		if (is_array($enumeration))
		{
			$keys = array_keys($enumeration);
			$row->values = "";
			foreach ($keys as $key)
			{
				$row->values .= "<input type=\"radio\" name=\"".$config['name']."\" " .
					"id=\"".$config['name']."_".$key."\" value=\"".$key."\"" .
					($key == $selected_value ? "checked=\"checked\"" : "") .
					" />" .
					"<label for=\"".$config['name']."_".$key."\">".$enumeration[$key]."</label> ";
			}
		}
		else
			$row->values = "N/A";

		return $row;
	}

	static private function generate_select_parent_branch($table,$level,$parent_id,$selected_id)
	{
		$return = "";

		$id = empty($_SESSION["id"]) ? "NEW" : $_SESSION["id"];

		$indent = "";
		for ($i = 0; $i<$level; $i++) { $indent .= "&nbsp;&nbsp;"; }

		$query = "SELECT `id`,`title` FROM `".Core::$TABLE_PREFIX.$table."` " .
			"WHERE `parent_id`=".$parent_id." ORDER BY `bearing` DESC,`title` ASC";
		$result = Sql::query($query);
		if ($result)
		{
			while ($o = Sql::fetch_array($result))
			{
				$return .= "<option value=\"".$o["id"]."\"".
					($selected_id == $o["id"] ? "selected=\"selected\"" : "").
					($id == $o["id"] ? "disabled=\"disabled\"" : "").
					"\">".
					$indent.$o["title"]." [".$o["id"]."]</option>";
				$return .= CmsEditor::generate_select_parent_branch($table,$level+1,$o["id"],$selected_id);
			}
		}
		return $return;
	}

	static public function generate_select_parent($config,$record)
	{
		$todo = empty($_SESSION["todo"]) ? "" : $_SESSION["todo"];
		$id = empty($_SESSION["id"]) ? "NEW" : $_SESSION["id"];

		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_select_parent.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$selected_id = $todo == "new" ? 0 : $record[$config['field']];
		$row->options = "<option value=\"0\" ".($selected_id == 0 ? "selected=\"selected\"" : "").">Nincs szülő (ős)</option>";

		$parent_id = 0;
		$row->options .= CmsEditor::generate_select_parent_branch($config['table'],0,$parent_id,$selected_id);

		return $row;
	}

	static public function generate_select_by_id($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_select_by_id.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->clonable = isset($config['nonclonable']) ? "false" : "true";

		$default_value = empty($record[$config['field']]) ? $config['default'] : $record[$config['field']];

		$row->options = "";
		$query = "SELECT `id`,`title` FROM `".Core::$TABLE_PREFIX.$config['table']."` ORDER BY `title`";
		$result = Sql::query($query);
		while ($o = sql::fetch_array($result))
		{
			$row->options .= "<option value=\"".$o["id"]."\" " .
				(($default_value == $o["id"]) ? "selected=\"selected\"" : "") .
				">".$o["title"]." [".$o["id"]."]</option>";
		}

		return $row;
	}

	static public function generate_dnd($config,$record)
	{
		$row = new Core();
		$row->load_template("/plugins/cms/templates/cmseditor_dnd.html");
		$row->title = $config['title'];
		$row->tip = empty($config['tip']) ? "" : "<img class=\"tip\" title=\"<h3>Súgó</h3>".$config['tip']."\" src=\"/plugins/cms/templates/images/info.png\"/>";
		$row->name = $config['name'];
		$row->value = trim(is_null($record[$config['field']])
			? $config['default'] : $record[$config['field']]);
		$row->type = Core::$MODE == "RELEASE" ? "hidden" : "text";

		// A kiválasztott elemek összeállítása
		$row->selected_items = "";
		if (strlen($row->value) > 0)
		{
			$query = "SELECT `id`,`title`
				FROM `".Core::$TABLE_PREFIX.$config['table']."`
				WHERE `id` IN (".$row->value.")
				ORDER BY FIELD(`id`,$row->value)";
			$result = Sql::query($query);
			while ($o = Sql::fetch_array($result))
			{
				$row->selected_items .= "<li class=\"item selected_item\" " .
					"id=\"".$config['name']."_".$o["id"]."\" rel=\"".$o["id"]."\">".$o["title"]." [".$o["id"]."]</li>";
			}
		}
		// A kiválasztható elemek összeállítása
		$row->available_items = "";
		$query = "SELECT `id`,`title`
			FROM `".Core::$TABLE_PREFIX.$config['table']."`
			".(strlen($row->value) > 0 ? "WHERE `id` NOT IN (".$row->value.")" : "")."
			ORDER BY `title`";
		$result = Sql::query($query);
		while ($o = Sql::fetch_array($result))
		{
			$row->available_items .= "<li class=\"item available_item\" " .
				"id=\"".$config['name']."_".$o["id"]."\" rel=\"".$o["id"]."\">".$o["title"]." [".$o["id"]."]</li>";
		}

		return $row;
	}
}
?>