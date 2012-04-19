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

class MiniWorld extends Core
{
	public static function before_core_set_content($content)
	{
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
		switch ($action)
		{
			// A tartalmat felcserélő modulok
			case "mw_album_user":
			case "mw_album_category":
			case "mw_album_user_by_category":
			case "mw_image":
			case "mw_image_editor":
			case "mw_image_upload":
			case "mw_image_delete":
			case "mw_image_download":
			case "mw_user":
				$content .= "{modul:$action}";
				break;
		}
		
		return $content;
	}
	
	public static function after_user_init($user)
	{
		$user->avatar_url = "/templates/default/img/no_avatar.png";
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
			case "kategoria":
				$category_id = MWCategory::search_by_permalink($permalink);
				if ($category_id)
				{
					$_SESSION["a"] = "mw_album_category";
					$_SESSION["p"] = 1;
					$_SESSION["cid"] = ($category_id > 0) ? $category_id : 0;
				}
				break;
			case "album":
				$user_id = MiniWorld::user_search_by_permalink($permalink);
				if ($user_id)
				{
					$_SESSION["a"] = "mw_album_user";
					if (count($url) == 3)
					{
						$category_id = MWCategory::search_by_permalink($url[1]);
						if ($category_id)
						{
							$_SESSION["a"] = "mw_album_user_by_category";
							$_SESSION["cid"] = ($category_id > 0) ? $category_id : 0;
						}
					}
					$_SESSION["p"] = 1;
					$_SESSION["uid"] = ($user_id > 0) ? $user_id : 0;
				}
				break;
			case "kep":
				$image_id = MWImage::search_by_permalink($permalink);
				if ($image_id)
				{
					$_SESSION["a"] = "mw_image";
					$_SESSION["p"] = 1;
					$_SESSION["imgid"] = ($image_id > 0) ? $image_id : 0;
				}
				break;
		}
	}
	
	public static function user_search_by_permalink($permalink)
	{
		$return = 0;

		// Az oldal megkeresése a permalink-je alapján
		$query = "SELECT `id` FROM `".Core::$TABLE_PREFIX."core_user` WHERE `username` = '$permalink'";
		$result = Sql::query($query);
		if (Sql::num_rows($result) == 1)
		{
			$o = Sql::fetch_array($result);
			$return = $o["id"];
		}

		return $return;
	}
	
	public static function modul_mw_user()
	{
		$box = NULL;
		
		$uid = array_key_exists("uid",$_SESSION) ? $_SESSION["uid"] : 0;

		if ($uid > 0)
		{
			$box = new User();
			$box->read($uid);
			$box->load_template("mw_user.html");
			
			if (trim($box->fullname) == "")
				$box->fullname = "N/A";
			if (trim($box->address) == "")
				$box->address = "N/A";
				
			// Képek száma
			$sql = "SELECT COUNT(img.id) AS nums,COUNT(img.id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_image)*100 AS percent
				FROM ".Core::$TABLE_PREFIX."mw_image AS img
				WHERE img.user_id = ".$box->id."
				GROUP BY img.user_id";
			$result = Sql::query($sql);
			if (Sql::num_rows($result))
			{
				$o = Sql::fetch_array($result);
				$box->image_nums = $o["nums"];
				if ($o["percent"] > 1)
					$box->image_nums_percent = "(".sprintf("%0.1f",$o["percent"])."%)";
			}
			else
				$box->image_nums = "0";
			
			foreach (Core::get_postfunctions("modul_mw_user") as $fnname)
				call_user_func($fnname, $box);
		}
		
		return $box;
	}
}
?>