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

class MWLog extends Core
{
	public static function mw_image_modul($image)
	{
		if (is_object($image))
		{
			$store = TRUE;
			$user = unserialize($_SESSION["user"]);

			$sid = $_SESSION["SID"];
			$id = $image->id;
			$tid = $image->mw_category_id;
			$uid = $user->login_ok ? $user->id : 0;
			$ip = $_SERVER['REMOTE_ADDR'];
			if ($uid)
			{
				// Ha van belépve felhasználó, akkor csekkoljuk nézte-e már meg
				$result = Sql::query("SELECT id FROM ".Core::$TABLE_PREFIX."mw_image_log
					WHERE mw_image_id = $id AND user_create_id = $uid");
				if (Sql::num_rows($result)>0)
					$store = FALSE;
			}
			else
			{
				// Ha nincs belépve, de ugyenezen IP-ről, ugyanezzel a SID-del van látogatás
				$result = Sql::query("SELECT id FROM ".Core::$TABLE_PREFIX."mw_image_log
					WHERE mw_image_id = $id AND session_id = '$sid'");
				if (Sql::num_rows($result)>0)
					$store = FALSE;
				else
				{
					// Illetve, ha 24 órán belül van ugyenerről a címről látogatás
					$result = Sql::query("SELECT id FROM ".Core::$TABLE_PREFIX."mw_image_log
						WHERE mw_image_id = $id AND ip_address = '$ip' AND date_create < ".strtotime("now - 1 day"));
					if (Sql::num_rows($result)>0)
						$store = FALSE;
				}
			}
			if ($store)
			{
				$query = "INSERT INTO ".Core::$TABLE_PREFIX."mw_image_log
							(id,mw_image_id,mw_category_id,user_agent,ip_address,session_id,date_create,date_modify,user_create_id,user_modify_id)
							VALUES
							(NULL,$id,$tid,'".$_SERVER['HTTP_USER_AGENT']."','".$ip."','$sid',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$uid,$uid)";
				Sql::query($query);
			}
		}
	}
	
	public static function after_modul_mw_user($user)
	{
		// A képeinek megtekintési aránya
		$sql = "SELECT COUNT(c.id) AS nums,COUNT(c.id)/(SELECT COUNT(id) FROM ".Core::$TABLE_PREFIX."mw_image_log)*100 AS percent
			FROM ".Core::$TABLE_PREFIX."mw_image AS img
			LEFT JOIN ".Core::$TABLE_PREFIX."mw_image_log AS c ON c.mw_image_id = img.id
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
	}

/*	static function modul_mag_last_images()
	{
		$box = NULL;
		$sid = $_SESSION["SID"];
		
		$query = "SELECT * FROM ".Core::$TABLE_PREFIX."mag_article
			WHERE id IN (SELECT mag_article_id FROM ".Core::$TABLE_PREFIX."mag_article_log
				WHERE (session_id = '$sid') AND (date_create > ".strtotime("now -2 days").")
				GROUP BY mag_article_id
				ORDER BY date_create DESC)";
		$result = Sql::query($query);
		if (Sql::num_rows($result) > 0)
		{
			$box = new MagazineArticleList($result,
				"mag_last_articles.html","mag_last_articles_item.html");
		}
		
		return $box;
	}*/
	
	public static function modul_mw_image_views()
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$box = new Core();
			$box->load_template("mw_image_views.html");
			// Kép megtekintéseinek száma
			$result = Sql::query("SELECT COUNT(id) AS nums FROM ".Core::$TABLE_PREFIX."mw_image_log WHERE mw_image_id = ".$_SESSION['imgid']);
			if (Sql::num_rows($result) > 0)
			{
				$o = Sql::fetch_array($result);
				$box->nums = $o['nums'];
			}
			else
				$box->nums = 0;
		}
		
		return $box;
	}

	public static function modul_mw_top_images()
	{
		$box = NULL;
		
		$query = "SELECT SQL_CALC_FOUND_ROWS a.*,COUNT(l.id) AS view_count FROM ".Core::$TABLE_PREFIX."mw_image AS a
			INNER JOIN ".Core::$TABLE_PREFIX."mw_image_log AS l ON (l.mw_image_id = a.id)
			GROUP BY a.id
			ORDER BY COUNT(l.id) DESC
			LIMIT 0,16";
		$result = Sql::query($query);
		$num_rows = Sql::calc_found_rows();
		if ($num_rows > 0)
		{
			$box = new MWAlbum($result);
			$box->title = "Legnézettebb képek";
			$box->load_templates("mw_album.html","mw_album_item.html");
			$box->load_pagigation($num_rows,16);
		}
		
		return $box;
	}
}
?>