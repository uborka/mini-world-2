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

class CmsList extends Core
{
	public function __construct()
	{
		$tb = microtime();

		$this->items = array();
		
		$this->message = "";
		$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
		if ($todo == "delete")
			$this->delete();

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
		$this->load_template("cmslist.html");
		
		// Az állandó értékek beállítása
		$this->title = $config['title'];
		// A kiválasztott menü
		$this->menu = $menu;
		// Az aktuálisan megjelenítendő oldal
		$this->page = array_key_exists("page",$_SESSION) ? $_SESSION["page"] : 1;
		// Az oldalankénti sorok száma
		$this->rpp = array_key_exists("rpp",$_SESSION) ? $_SESSION["rpp"] : 20;
		// A rendezési sorrend kiválasztott mezője
		$this->order_field = array_key_exists("order_field",$_SESSION) ? $_SESSION["order_field"] : $config['default_field'];
		// A rendezés iránya
		$this->order_dir = array_key_exists("order_dir",$_SESSION) ? $_SESSION["order_dir"] : $config['default_direction'];
		// A lista szűrési feltétele
		$this->filter = empty($_SESSION["filter"]) ? NULL : $_SESSION["filter"];
		$this->fmin = empty($_SESSION["fmin"]) ? NULL : $_SESSION["fmin"];
		$this->fmax = empty($_SESSION["fmax"]) ? NULL : $_SESSION["fmax"];
		// A megjelenítendő oszlopok száma
		$this->col_nums = array_key_exists("col_nums",$config) ? $config['col_nums'] : count($config['headers']);
		
		// A fejléc beállítása
		$this->set_header();
		
		// A keresősáv beállítása
		$this->set_searchbar();
		
		// Extra funkciók beállítása
		$this->set_extra_commands();

		// Az elemek betöltése
		$this->load_items();

		$this->log("init()",$tb);
	}
	
	function set_header()
	{
		$tb = microtime();
		
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		$header = new Core();
		$header->load_template("cmslist_header.html");
		$header->items = array();
		
		for ($i = 0; $i < count($config['headers']); $i++)
		{
			if (array_key_exists($config['types'][$i],CmsListItem::$COLUMN_FUNCTIONS))
			{
				$item = new Core();
				$item->load_template("cmslist_header_item.html");
				
				$item->title = $config['headers'][$i];
				$item->is_selected = $this->order_field == $config['fields'][$i];
				$item->url = "?a=cmslist&m=".$menu.
					"&page=".$this->page.
					"&rpp=".$this->rpp.
					"&filter=".(is_null($this->filter) ? "" : $this->filter).
					"&order_field=".$config['fields'][$i].
					"&order_dir=".($item->is_selected ? ($this->order_dir == "asc" ? "desc" : "asc") : $this->order_dir);
				$item->set_selected($item->is_selected);
				$item->tip = ($item->is_selected
					? ($this->order_dir == "asc" ? "Rendezés csökkenő sorrendben" : "Rendezés növekvő sorrendben")
					: "Rendezés ezen oszlop szerint");
				$header->items[] = $item;
			}
		}
		
		$this->header = $header;
		
		$this->log("set_header()",$tb);
	}
	
	function set_searchbar()
	{
		$tb = microtime();
		
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		
		$index = array_search($this->order_field,$config['fields']);
		switch ($config['types'][$index])
		{
			case "id":
			case "text":
			case "email":
				$this->searchbar = "<input type=\"text\" name=\"filter\" value=\"".$this->filter."\"/>";
				$this->searchbar_disabled = "";
				break;
			case "currency":
			case "int":
				// Az adott mező minimum és maximum értékének lekérdezése
				$query = "SELECT MIN(`".$this->order_field."`) AS `min`,MAX(`".$this->order_field."`) as `max` FROM `".$config['table']."`";
				$result = Sql::query($query);
				$o = Sql::fetch_array($result);
				$min = $o["min"];
				$max = $o["max"];
				// A filter alapján az aktuális értékek beállítása
				if (is_null($this->fmin) && is_null($this->fmax))
				{
					$fmin = $min;
					$fmax = $max;
				}
				else
				{
					$fmin = $this->fmin;
					$fmax = $this->fmax;
				}
				
				$this->searchbar .= "<div>Szűrési tartomány: <input type=\"text\" id=\"amount\"/></div>";
				$this->searchbar .= "<div id=\"filter\"></div>";
				$this->searchbar .= "<script type=\"text/javascript\">
					\$(function() {
						\$('#filter').slider({
							range: true,
							min: $min,
							max: $max,
							values: [$fmin, $fmax],
							slide: function(event, ui) {
								\$('#amount').val(ui.values[0] + ' - ' + ui.values[1]);
							}
						});
						\$('#amount').val(\$('#filter').slider('values', 0) + ' - ' + \$('#filter').slider('values', 1));
					});
					</script>";
				$this->searchbar_disabled = "";
				break;
			case "date":
				// A filter alapján az aktuális értékek beállítása
				if (is_null($this->fmin) && is_null($this->fmax))
				{
					// Az adott mező minimum és maximum értékének lekérdezése
					$query = "SELECT MIN(`".$this->order_field."`) AS `min`,MAX(`".$this->order_field."`) as `max` FROM `".$config['table']."`";
					$result = Sql::query($query);
					$o = Sql::fetch_array($result);
					$min = substr($o["min"],0,strpos($o["min"]," "));
					$max = substr($o["max"],0,strpos($o["max"]," "));
				}
				else
				{
					$min = $this->fmin;
					$max = $this->fmax;
				}
				
				$this->searchbar = "<input type=\"text\" name=\"fmin\" id=\"fmin\" value=\"$min\" />" .
					"<input type=\"text\" name=\"fmax\" id=\"fmax\" value=\"$max\" />";
				$this->searchbar .= "<script type=\"text/javascript\">
					\$(function() {
						\$.datepicker.setDefaults(\$.extend({changeMonth: true, changeYear: true }, \$.datepicker.regional['hu']));
						\$('#fmin').datepicker();
						\$('#fmax').datepicker();
						});
					</script>";
				$this->searchbar_disabled = "";
				break;
			case "enum":
				$this->searchbar = "<select name=\"filter\">";
				$keys = array_keys(Core::$CMS['ENUMERATIONS'][strtoupper($menu.".".$this->order_field)]);
				foreach ($keys as $key)
				{
					$this->searchbar .= "<option value=\"".$key."\">".
						Core::$CMS['ENUMERATIONS'][strtoupper($menu.".".$this->order_field)][$key].
						"</option>";
				}
				$this->searchbar .= "</select>";
				$this->searchbar_disabled = "";
				break;
			case "parent":
				$this->searchbar = "<input type=\"text\" name=\"filter\" id=\"filter\" value=\"".$this->filter."\"/>";
				$this->searchbar .= "<script type=\"text/javascript\">\$().ready(function() {
					\$('#filter').autocomplete({
						source: '/ajax.php?a=autocomplete&table=".$config['table']."',
						minLength: 2
					});
				});</script>";
				$this->searchbar_disabled = "";
				break;
			case "included":
			case "user":
				$table = substr($this->order_field,0,strpos($this->order_field,"_id"));
				$this->searchbar = "<input type=\"text\" name=\"filter\" id=\"filter\" value=\"".$this->filter."\"/>";
				$this->searchbar .= "<script type=\"text/javascript\">\$().ready(function() {
					\$('#filter').autocomplete({
						source: '/ajax.php?a=cmsautocomplete&table=".$table."',
						minLength: 2
					});
				});</script>";
				$this->searchbar_disabled = "";
				break;
			case "is_system":
			default:
				$this->searchbar = "<span class=\"disabled\">Nincsenek.</span>";
				$this->searchbar_disabled = "disabled=\"disabled\"";
				break;
		}
		
		$this->log("set_searchbar()",$tb);
	}
	
	function set_extra_commands()
	{
		$tb = microtime();
		
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		$extra_commands = empty($config['extra_commands']) ? NULL : $config['extra_commands'];
		$user = unserialize($_SESSION["user"]);
		
		$this->new_command = "";
		$this->extra_commands = "";
		
		if (!is_null($extra_commands))
		{
			$backurl = urlencode("?a=cmslist&m=".$menu."&order_field=".$this->order_field.
				"&page=".$this->page.
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter));;
			$nc = "";
			$ec = "";
			foreach ($extra_commands as $command)
			{
				if ($user->user_rights->check($menu,$command["right"]))
				{
					if ($command["icon"] != "")
						$img = "<img src=\"".$command["icon"]."\" />";
					switch ($command["right"])
					{
						case "new":
							$nc .= "<li><a href=\"".$command["url"].
								"&backurl=".$backurl."\" " .
								"title=\"".$command["title"]."\" " .
								">".$img.$command["title"]."</a></li>";
							break;
						default:
							$ec .= "<li><a onclick=\"\$('#dlg_".$command["right"]."').ptiShowDialog('".$command["url"]."','".$command["title"]."');\" " .
								"title=\"".$command["title"]."\" " .
								">".$img.$command["title"]."</a>" .
								"<div id=\"dlg_".$command["right"]."\"></div>" .
								"</li>";
							break;
					}
				}
				else
				{
					if ($command["icon"] != "")
						$img = "<img src=\"".str_replace(".gif","_disabled.gif",$command["icon"])."\" border=\"0\" align=\"absmiddle\" />";
					if ($command["right"] == "new")
						$nc = "<li><span>".$img.$command["title"]."</span></li>";
					else
						$ec .= "<li><span>".$img.$command["title"]."</span></li>";
				}
			}
			if (strlen($nc) > 0)
				$this->new_command = "<span class=\"new\"><ul>".$nc."</ul></span>";
			if (strlen($ec) > 0)
				$this->extra_commands = "<span class=\"extra\"><ul>".$ec."</ul></span>";
		}
			
		$this->log("set_extra_commands()",$tb);
	}

	function load_items()
	{
		$tb = microtime();
		
		$menu = $_SESSION["m"];
		$lang = empty($_SESSION["lang"]) ? Core::$PARAM["LANG_DEFAULT"] : $_SESSION["lang"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		$table = is_array($config['table']) ? $config['table'][$lang] : $config['table'];
		
		// A query összeállítása
		$fieldlist = "";
		for ($i = 0; $i < count($config['fields']); $i++)
		{
			if (strlen($fieldlist) > 0) $fieldlist .= ",";
			$fieldlist .= "`".$config['fields'][$i]."`";
		}
		
		$query = "SELECT SQL_CALC_FOUND_ROWS ".$fieldlist." FROM `".Core::$TABLE_PREFIX.$table."` WHERE (1=1) ";
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
					$filter = "AND (`".$this->order_field."` = '".$this->filter."') ";
					break;
				case "included":
				case "parent":
				case "user":
					// Először is az azonosító kulcsok lekérdezése
					$table = $table = substr($this->order_field,0,strpos($this->order_field,"_id"));
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
		// A lekérdezett tartomány beállítása
		$query .= " LIMIT ".max(0,(($this->page-1)*$this->rpp)).",".$this->rpp;
		
		$result = Sql::query($query);
		
		$page_nums = ceil(Sql::calc_found_rows() / $this->rpp);
// ez vajon mire volt jó?
//		if ($this->page > $page_nums)
//		{
//			$this->page = $page_nums;
//			$limit = " LIMIT ".max(0,(($page-1)*$row_nums_per_page)).",".$row_nums_per_page;
//			$result = Sql::query($query.$limit);
//			$page_nums = ceil(Sql::calc_found_rows() / $row_nums_per_page);
//		}
//		$this->variables["page"] = $page;
		while ($o = Sql::fetch_array($result))
		{
			$this->items[] = new CmsListItem($o);
		}
		
		// A lapozó elkészítése
		
		$pager = new Core();
		$pager->load_template("cmslist_pager.html");
		$pager->items = array();
		// A megjelenítendő tartomány kiszámolása
		$first_page = 1;
		$last_page = $page_nums;
		if (($this->page - 5) < 1)
			$last_page = ($page_nums > 10) ? 11 : $page_nums;
		elseif (($this->page + 5) > $page_nums)
			$first_page = (($page_nums - 10) > 0) ? $page_nums - 10 : 1;
		else
		{
			$first_page = $this->page - 5;
			$last_page = $this->page + 5;
		}
		// Az elemek összeállítása
		// Előző
		$item = new Core();
		$item->load_template("cmslist_pager_item.html");
		$item->title = "&laquo;";
		$item->tip = "Ugrás az előző oldalra";
		$item->class = "prevnext";
		$item->set_selected(FALSE);
		$item->set_disabled($this->page == 1);
		$item->url = ($this->page > 1 ? "?a=cmslist&m=".$menu.
				"&page=".($this->page - 1).
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter).
				"&order_field=".$this->order_field.
				"&order_dir=".$this->order_dir : "#");
		$pager->items[] = $item;
		for ($i = $first_page; $i <= $last_page; $i++)
		{
			$item = new Core();
			$item->load_template("cmslist_pager_item.html");
			$item->title = $i;
			$item->tip = "Ugrás a $i. oldalra";
			$item->class = "";
			$item->set_selected($i == $this->page);
			$item->set_disabled(FALSE);
			$item->url = "?a=cmslist&m=".$menu.
				"&page=".$i.
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter).
				"&order_field=".$this->order_field.
				"&order_dir=".$this->order_dir;
			$pager->items[] = $item;
		}
		// Következő
		$item = new Core();
		$item->load_template("cmslist_pager_item.html");
		$item->title = "&raquo;";
		$item->tip = "Ugrás a következő oldalra";
		$item->class = "prevnext";
		$item->set_selected(FALSE);
		$item->set_disabled($this->page == $page_nums);
		$item->url = ($this->page < $page_nums ? "?a=cmslist&m=".$menu.
				"&page=".($this->page + 1).
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter).
				"&order_field=".$this->order_field.
				"&order_dir=".$this->order_dir : "#");
		$pager->items[] = $item;
		
		$this->inner_modules["pager"] = $pager;
		
		// Az ugró beállítása
		$jumper = new Core();
		$jumper->load_template("cmslist_jumper.html");
		$jumper->items = array();
		$jumper->url = "?a=cmslist&m=".$menu.
				"&rpp=".$this->rpp.
				"&filter=".(is_null($this->filter) ? "" : $this->filter).
				"&order_field=".$this->order_field.
				"&order_dir=".$this->order_dir;
		
		for ($i = 1; $i <= $page_nums; $i++)
		{
			$item = new Core();
			$item->load_template("cmslist_jumper_item.html");
			$item->set_selected($i == $this->page);
			$item->title = $i;
			
			$jumper->items[] = $item;
		}
		
		if ($page_nums > 1)
			$this->inner_modules["jumper"] = $jumper;
		else
			$this->jumper = "";
		
		$this->log("load_items()",$tb);
	}
	
	public function delete()
	{
		$tb = microtime();

		$user = unserialize($_SESSION["user"]);
		$menu = $_SESSION["m"];
		$config = Core::$CMS['LISTS'][$_SESSION["m"]];
		$id = $_SESSION["id"];
		
		if ($user->user_rights->check($menu,"delete"))
		{
			$deletable = TRUE;
			if (in_array("is_system",$config['fields']))
			{
				$query = "SELECT `is_system` FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE `id`=".$id;
				$result = Sql::query($query);
				if ($result)
					if ($o = Sql::fetch_array($result))
						$deletable = ($o["is_system"] == 0);
			}
			if ($deletable)
			{
				$query = "DELETE FROM `".Core::$TABLE_PREFIX.$config['table']."` WHERE (`id`=$id) LIMIT 1";
				$result = Sql::query($query);
				if (Sql::sql_error() == "")
					$this->message = $this->create_message("A rekordot sikeresen törölte.","ok");
				else
					$this->message = $this->create_message("A rekordot nem sikerült törölni! (SQL hiba)","error");
			}
			else
				$this->message = $this->create_message("A rekordot nem lehet törölni, mert az védett!","error");
		}
		else
			$this->message = $this->create_message("Önnek nincs joga törölni a rekordot!","error");

		$this->log("delete()",$tb);
	}
}
?>