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

class MWRate extends Core
{
	public function __construct($image_id)
	{
		$this->image_id = $image_id;
		
		$query = "SELECT COUNT(id) AS nums,AVG(rate) AS rate
			FROM `".Core::$TABLE_PREFIX."mw_rate`
			WHERE (`mw_image_id` = $image_id)
			GROUP BY mw_image_id";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			parent::__construct(Sql::fetch_array($result));
			
			$this->ratio = round($this->rate*2);
			$this->rate = sprintf("%0.1f",$this->rate);
		}
		else
		{
			$this->ratio = "0";
			$this->rate = "0";
			$this->nums = "0";
		}
		
		// ELlenőrizzük, hogy a felhasználó pontozta-e már
		$this->voted = TRUE;
		$user = unserialize($_SESSION['user']);
		if ($user->login_ok)
		{
			$query = "SELECT user_id FROM ".Core::$TABLE_PREFIX."mw_image WHERE id = $image_id";
			$result = Sql::query($query);
			$img = Sql::fetch_array($result);
			
			$result = Sql::query("SELECT id FROM ".Core::$TABLE_PREFIX."mw_rate WHERE mw_image_id = $image_id AND user_create_id = $user->id");
			if (Sql::num_rows($result))
			{
				$o = Sql::fetch_array($result);
				$this->voted = ($img['user_id'] != $user->id) && ($o['id'] > 0);
			}
			else
				$this->voted = $img['user_id'] == $user->id;
		}
	}
	
	public static function before_core_set_content($content)
	{
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
		switch ($action)
		{
			// A tartalmat felcserélő modulok
			case "mw_image_rate":
			case "mw_album_top_minis":
				$content .= "{modul:$action}";
				break;
		}
		
		return $content;
	}
	
	public static function after_site_permalink()
	{
		// Az URI felbontása
		$u_start = strpos($_SERVER["REQUEST_URI"],"/") + 1;
    	$u_end = strpos($_SERVER["REQUEST_URI"],"?");
    	
    	$url = $u_end === false ?
    		explode("/",substr($_SERVER["REQUEST_URI"], $u_start)) :
    		explode("/",substr($_SERVER["REQUEST_URI"], $u_start, $u_end - $u_start + 1));
    	
    	// Az utolsó tagból a keresett permalink kivágása
	    $permalink = substr($url[count($url)-1],0,strpos($url[count($url)-1],".html"));
    	
		switch ($_SESSION["a"])
		{
			case "top":
				$category_id = MWCategory::search_by_permalink($permalink);
				if ($category_id)
				{
					$_SESSION["a"] = "mw_album_top_minis";
					$_SESSION["p"] = 1;
					$_SESSION["cid"] = ($category_id > 0) ? $category_id : 0;
				}
				break;
		}
	}
	
	public static function after_mw_image_init($image)
	{
		$image->rate = new MWRate($image->id);
		$image->rate->load_template("mw_image_rate.html");
		
/*		$user = new User();
		$user->read($image->user_id);
		if ($user->rate)
		{
			$image->user_rank = new Core();
			$image->user_rank->rank = $user->rank;
			$image->user_rank->rate = $user->rate;
			$image->user_rank->ratio = $user->ratio;
			$image->user_rank->load_template("mw_image_rank.html");
		}*/
	}
	
	public static function after_user_init($user)
	{
		$sql = "SELECT AVG(r.rate) AS rate
			FROM ".Core::$TABLE_PREFIX."mw_rate AS r
			INNER JOIN ".Core::$TABLE_PREFIX."mw_image AS img ON img.id = r.mw_image_id
			WHERE img.user_id = ".$user->id;
		$result = Sql::query($sql);
		if (Sql::num_rows($result))
		{
			$o = Sql::fetch_array($result);
			$sql = "SELECT title FROM ".Core::$TABLE_PREFIX."mw_rank
				WHERE rate <= ".$o["rate"]."
				ORDER BY rate DESC
				LIMIT 0,1";
			$result = Sql::query($sql);
			if (Sql::num_rows($result))
			{
				$o2 = Sql::fetch_array($result);
				$user->rank = $o2["title"];
				$user->ratio = round($o["rate"]*2);
				$user->rate = sprintf("%0.1f",$o["rate"]);
			}
		}
	}
	
	public static function after_modul_mw_user($user)
	{
		// kommentárok összesítése
		$sql = "SELECT COUNT(c.id) AS nums,COUNT(c.id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_comment)*100 AS percent
			FROM ".Core::$TABLE_PREFIX."mw_image AS img
			LEFT JOIN ".Core::$TABLE_PREFIX."mw_comment AS c ON c.mw_image_id = img.id
			WHERE img.user_id = $user->id
			GROUP BY img.user_id";
		$result = Sql::query($sql);
		if ($o = Sql::fetch_array($result))
		{
			$user->image_comment_nums = $o["nums"];
			if ($o["percent"] > 1)
				$user->image_comment_nums_percent = "(".sprintf("%0.1f",$o["percent"])."%)";
		}
		else
			$user->image_comment_nums = "0";
			
		// értékelések összesítése
		$sql = "SELECT COUNT(r.id) AS nums,COUNT(r.id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_rate)*100 AS percent
			FROM ".Core::$TABLE_PREFIX."mw_image AS img
			LEFT JOIN ".Core::$TABLE_PREFIX."mw_rate AS r ON r.mw_image_id = img.id
			WHERE img.user_id = $user->id
			GROUP BY img.user_id";
		$result = Sql::query($sql);
		if ($o = Sql::fetch_array($result))
		{
			$user->image_rate_nums = $o["nums"];
			if ($o["percent"] > 1)
				$user->image_rate_nums_percent = "(".sprintf("%0.1f",$o["percent"])."%)";
		}
		else
			$user->image_rate_nums = "0";
			
		// kommentárjainak száma
		$sql = "SELECT COUNT(id) AS nums,COUNT(id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_comment)*100 AS percent
			FROM ".Core::$TABLE_PREFIX."mw_comment
			WHERE user_create_id = $user->id
			GROUP BY user_create_id";
		$result = Sql::query($sql);
		if ($o = Sql::fetch_array($result))
		{
			$user->comment_nums = $o["nums"];
			if ($o["percent"] > 1)
				$user->comment_nums_percent = "(".sprintf("%0.1f",$o["percent"])."%)";
		}
		else
			$user->comment_nums = "0";
		
		// értékeléseinek száma
		$sql = "SELECT COUNT(id) AS nums,COUNT(id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_rate)*100 AS percent
			FROM ".Core::$TABLE_PREFIX."mw_rate
			WHERE user_create_id = $user->id
			GROUP BY user_create_id";
		$result = Sql::query($sql);
		if ($o = Sql::fetch_array($result))
		{
			$user->rate_nums = $o["nums"];
			if ($o["percent"] > 1)
				$user->rate_nums_percent = "(".sprintf("%0.1f",$o["percent"])."%)";
		}
		else
			$user->rate_nums = "0";
	}
	
	public static function modul_mw_image_rates()
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$user = unserialize($_SESSION['user']);
			
			$box = new MWRate($_SESSION['imgid']);
			if ($user->login_ok && !$box->voted)
				$box->load_template("mw_image_rates_form.html");
			else
				$box->load_template("mw_image_rates.html");
		}
		
		return $box;
	}
	
	public static function modul_mw_image_rate()
	{
		// AJAX-al menti a szavazatot
		$user = unserialize($_SESSION['user']);
		$image_id = $_SESSION['imgid'];
		$rate = $_SESSION['rate'];
		
		if ($user->login_ok)
		{
			$query = "SELECT user_id FROM ".Core::$TABLE_PREFIX."mw_image WHERE id = $image_id";
			$result = Sql::query($query);
			if (Sql::num_rows($result))
			{
				$o = Sql::fetch_array($result);
				if ($o['user_id'] != $user->id)
				{
					Sql::query($query = "INSERT INTO `".Core::$TABLE_PREFIX."mw_rate`
						(`id`,`mw_image_id`,`rate`,`date_create`,`date_modify`,`user_create_id`,`user_modify_id`)
						VALUES
						(NULL,'$image_id','$rate',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'$user->id','$user->id')");
				}
			}
		}
	}
	
	public static function modul_mw_top_categories()
	{
		$box = NULL;
		
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_category` ORDER BY `title`";
		$result = Sql::query($query);
		if (Sql::num_rows($result))
		{
			$box = new Core();
			$box->items = array();
			$box->load_template("mw_top_categories.html");
			while ($o = Sql::fetch_array($result))
			{
				$item = new MWCategory($o);
				$item->load_template("mw_top_categories_item.html");
				$item->url = Core::$HTTP_HOST . "/top/" . $item->permalink . ".html";
				$box->items[] = $item;
			}
		}
		
		return $box;
	}
	
	public static function modul_mw_album_top_minis()
	{
		$box = null;
		
		$cid = array_key_exists("cid",$_SESSION) ? $_SESSION["cid"] : 0;
		if ($cid)
		{
			$category = new MWCategory($cid);
			$page = array_key_exists("page",$_SESSION) ? ($_SESSION["page"] ? $_SESSION["page"]-1 : 0) : 0;
			
			$sql = "SELECT SQL_CALC_FOUND_ROWS img.*
				FROM ".Core::$TABLE_PREFIX."mw_image AS img
				INNER JOIN ".Core::$TABLE_PREFIX."mw_rate AS r ON r.mw_image_id = img.id
				WHERE img.mw_category_id = $cid AND img.option_accepted = 1
				GROUP BY img.id
				ORDER BY AVG(r.rate) DESC,img.date_create DESC LIMIT ".($page*16).",16";
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
}
?>