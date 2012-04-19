<?php
/*
 * FÜGE² - Tartalomkezelés - Webáruház - Ügyviteli rendszer
 * Copyright (C) 2008-2012; PTI Kft.
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

class MWAlbum extends Core
{
	public function __construct($data)
	{
		$this->items = array();
		
		// Ha a DATA az eredménysor
		if (is_object($data))
		{
			if (Sql::num_rows($data) > 0)
			{
				while ($o = Sql::fetch_array($data))
				{
					$item = new MWImage($o);
					$item = MWImage::get_image($o);
					$this->items[] = $item;
				}
			}
		}
		// Ha a DATA már az eredmény
		elseif (is_array($data))
		{
			foreach ($data as $o)
			{
				$item = new MWImage($o);
				$item = MWImage::get_image($o);
				$this->items[] = $item;
			}
		}
		// Ha a DATA az SQL
		else if (strlen($data))
		{
			$result = Sql::query($data);
			if (Sql::num_rows($result) > 0)
			{
				while ($o = Sql::fetch_array($result))
				{
					$item = new MWImage($o);
					$item = MWImage::get_image($o);
					$this->items[] = $item;
				}
			}
		}
	}
	
	public function load_templates($template_main,$template_item,$template_empty = "")
	{
		if (count($this->items))
		{
			$this->load_template($template_main);
			foreach ($this->items as $item)
			{
				$item->load_template($template_item);
			}
		}
		else
		{
			$this->load_template($template_empty);
		}
	}
	
	public function load_pagigation($num_rows,$rows_per_page)
	{
		$page = array_key_exists("page",$_SESSION) ? $_SESSION["page"] : 1;
		$page_nums = ceil($num_rows / $rows_per_page);
		
		// A lapozó összeállítása
		$this->pagination = new Core();
		$this->pagination->load_template("mw_album_pagination.html");
		$this->pagination->items = array();
		
		// A megjelenítendő tartomány kiszámolása
		$first_page = 1;
		$last_page = $page_nums;
		if (($page - 5) < 1)
			$last_page = ($page_nums > 10) ? 11 : $page_nums;
		elseif (($page + 5) > $page_nums)
			$first_page = (($page_nums - 10) > 0) ? $page_nums - 10 : 1;
		else
		{
			$first_page = $page - 5;
			$last_page = $page + 5;
		}
		// Az elemek összeállítása
		// Előző
		$item = new Core();
		$item->load_template("mw_album_pagination_item.html");
		$item->title = "&laquo;";
		$item->tip = "Ugrás az első oldalra";
		$item->set_selected(FALSE);
		$item->set_disabled($page == 1);
		$item->url = $this->change_url(array("page"=>1));
		$this->pagination->items[] = $item;
		for ($i = $first_page; $i <= $last_page; $i++)
		{
			$item = new Core();
			$item->load_template("mw_album_pagination_item.html");
			$item->title = $i;
			$item->tip = "Ugrás a $i. oldalra";
			$item->set_selected($i == $page);
			$item->set_disabled(FALSE);
			$item->url = $this->change_url(array("page"=>$i));
			$this->pagination->items[] = $item;
		}
		// Következő
		$item = new Core();
		$item->load_template("mw_album_pagination_item.html");
		$item->title = "&raquo;";
		$item->tip = "Ugrás az utolsó oldalra";
		$item->set_selected(FALSE);
		$item->set_disabled($page == $page_nums);
		$item->url = $this->change_url(array("page"=>$page_nums));
		$this->pagination->items[] = $item;
	}
	
	public static function modul_mw_album_category()
	{
		$box = null;
		
		$cid = array_key_exists("cid",$_SESSION) ? $_SESSION["cid"] : 0;
		if ($cid)
		{
			$category = new MWCategory($cid);
			$page = array_key_exists("page",$_SESSION) ? ($_SESSION["page"] ? $_SESSION["page"]-1 : 0) : 0;
			
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".Core::$TABLE_PREFIX."mw_image
				WHERE mw_category_id = $cid AND option_accepted = 1
				ORDER BY date_create DESC LIMIT ".($page*16).",16";
			$result = Sql::query($sql);
			$num_rows = Sql::calc_found_rows();
			if (Sql::num_rows($result))
			{
				$box = new MWAlbum($result);
				$box->title = $category->title . " kategória";
				$box->load_templates("mw_album.html","mw_album_item.html");
				$box->load_pagigation($num_rows,16);
			}
		}
		
		return $box;
	}
	
	public static function modul_mw_album_user()
	{
		$box = null;
		
		$uid = array_key_exists("uid",$_SESSION) ? $_SESSION["uid"] : 0;
		if ($uid)
		{
			$user = unserialize($_SESSION['user']);
			$usr = new User();
			$usr->read($uid);
			$page = array_key_exists("page",$_SESSION) ? ($_SESSION["page"] ? $_SESSION["page"]-1 : 0) : 0;
			
			$where = "AND option_accepted = 1";
			if ($user->login_ok)
				if ($user->id == $uid)
					$where = ""; 
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".Core::$TABLE_PREFIX."mw_image
				WHERE user_id = $uid $where
				ORDER BY date_create DESC LIMIT ".($page*16).",16";
			$result = Sql::query($sql);
			$num_rows = Sql::calc_found_rows();
			if (Sql::num_rows($result))
			{
				$box = new MWAlbum($result);
				$box->title = $usr->fullname . " albuma";
				$box->load_templates("mw_album.html","mw_album_item.html");
				$box->load_pagigation($num_rows,16);
			}
		}
		
		return $box;
	}
	public static function modul_mw_album_user_by_category()
	{
		$box = null;
		
		$cid = array_key_exists("cid",$_SESSION) ? $_SESSION["cid"] : 0;
		$uid = array_key_exists("uid",$_SESSION) ? $_SESSION["uid"] : 0;
		if ($uid)
		{
			$user = new User();
			$user->read($uid);
			$page = array_key_exists("page",$_SESSION) ? ($_SESSION["page"] ? $_SESSION["page"]-1 : 0) : 0;
			
			$category = new MWCategory($cid);
			
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".Core::$TABLE_PREFIX."mw_image
				WHERE user_id = $uid AND mw_category_id = $cid AND option_accepted = 1
				ORDER BY date_create DESC LIMIT ".($page*16).",16";
			$result = Sql::query($sql);
			$num_rows = Sql::calc_found_rows();
			if (Sql::num_rows($result))
			{
				$box = new MWAlbum($result);
				$box->title = $user->fullname . " képei a ".strtolower($category->title)." kategóriában";
				$box->load_templates("mw_album.html","mw_album_item.html");
				$box->load_pagigation($num_rows,16);
			}
		}
		
		return $box;
	}
	public static function modul_mw_album_new_minis($modul_variable)
	{
		$box = null;
		$cid = 0;
		
		if (strpos($modul_variable,":") !== FALSE)
		{
			$mv = explode(":",$modul_variable);
			if (is_numeric($mv[1]))
			{
				$cid = (int)$mv[1];
				$category = new MWCategory($cid);
				$sql = "SELECT * FROM ".Core::$TABLE_PREFIX."mw_image
					WHERE mw_category_id = $cid AND option_accepted = 1
					ORDER BY date_create DESC LIMIT 0,16";
			}
		}
		else
		{
			$sql = "SELECT * FROM ".Core::$TABLE_PREFIX."mw_image
				WHERE option_accepted = 1
				ORDER BY date_create
				DESC LIMIT 0,16";
		}
		
		$result = Sql::query($sql);
		if (Sql::num_rows($result))
		{
			$box1 = new MWAlbum($result);
			$box1->load_templates("mw_album_new_minis.html","mw_album_new_minis_item.html");
			// Átalakítás a jQuery Tools Scrollable miatt
			$box = new Core();
			$box->id = $cid;
			$box->title = "Legfrissebb képek" . ($category ? " ".$category->title." kategóriában" : "");
			$box->load_template("mw_album_new_minis.html");
			
			$block = new Core();
			$block->items = array();
			$block->template = "<div>{this>items}</div>";
			
			for ($i = 0; $i < count($box1->items); $i++)
			{
				if ($i % 4 == 0 && count($block->items))
				{
					$box->items[] = $block;
					$block = new Core();
					$block->items = array();
					$block->template = "<div>{this>items}</div>";
				}
				$block->items[] = $box1->items[$i];
			}
			$box->items[] = $block;
		}
		
		return $box;
	}
}
?>