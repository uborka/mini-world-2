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

class MWComment extends Core
{
	public function __construct($data)
	{
		// Ha a DATA egy rekord
		if (is_array($data))
		{
			parent::__construct($data);
			$this->init();
		}
		// Ha a DATA egy kulcs
		elseif (is_numeric($data))
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_comment` WHERE (`id` = $data)";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				parent::__construct(Sql::fetch_array($result));
				$this->init();
			}
		}
	}
	
	public function init()
	{
		$this->user = new User();
		$this->user->read($this->user_create_id);
		$this->user_name = $this->user->username;
		
		$this->avatar_url = $this->user->avatar_url;
		
		$this->comment = MWImage::make_link(stripslashes($this->comment));
		
		$this->create_date = Core::unix_to_datetime($this->date_create);
		$diff = time() - $this->date_create;
		if ($diff > 31536000)
			$this->time_diff = sprintf("Több mint %d éve",round($diff/31536000));
		elseif ($diff > 86400) // nap
			$this->time_diff = sprintf("%d napja",round($diff/86400));
		elseif ($diff > 3600) // óra
			$this->time_diff = sprintf("%d órája",round($diff/3600));
		elseif ($diff > 60) // perc
			$this->time_diff = sprintf("%d perce",round($diff/60));
		else
			$this->time_diff = "most";
			
		$query = "SELECT * FROM ".Core::$TABLE_PREFIX."mw_rate WHERE mw_image_id = $this->mw_image_id AND user_create_id = $this->user_create_id";
		$result = Sql::query($query);
		if (Sql::num_rows($result))
		{
			$rate = Sql::fetch_array($result);
			$this->ratio = round($rate['rate']*2);
			$this->rate = sprintf("%d",$rate['rate']);;
		}
	}
	
	public static function before_core_set_content($content)
	{
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
		switch ($action)
		{
			// A tartalmat felcserélő modulok
			case "mw_image_comment":
				$content .= "{modul:$action}";
				break;
		}
		
		return $content;
	}
	
	public static function after_user_init($user)
	{
		$sql = "SELECT COUNT(c.id) AS nums
			FROM ".Core::$TABLE_PREFIX."mw_image AS img
			INNER JOIN ".Core::$TABLE_PREFIX."mw_comment AS c ON c.mw_image_id = img.id AND option_viewed = 0
			WHERE img.user_id = ".$user->id;
		$result = Sql::query($sql);
		if (Sql::num_rows($result))
		{
			$o = Sql::fetch_array($result);
			if ($o["nums"] > 0)
				$user->unviwed_comment_nums = $o["nums"];
		}
	}
	
	public static function modul_mw_unviewed_comments()
	{
		$box = new Core();
		
		$user = unserialize($_SESSION["user"]);
		
		$sql = "SELECT c.*,img.title,img.id AS img_id
			FROM ".Core::$TABLE_PREFIX."mw_image AS img
			INNER JOIN ".Core::$TABLE_PREFIX."mw_comment AS c ON c.mw_image_id = img.id AND option_viewed = 0
			WHERE img.user_id = ".$user->id;
		$result = Sql::query($sql);
		if (Sql::num_rows($result))
		{
			$box->items = array();
			$box->load_template("mw_unviewed_comments.html");
			
			while ($o = Sql::fetch_array($result))
			{
				$img = new MWImage($o["img_id"]);
				
				$item = new MWComment($o);
				$item->load_template("mw_unviewed_comments_item.html");
				$item->image_url = $img->url . "#c" . $item->id;
				
				$box->items[] = $item;
			}
			
			$box->unviwed_comment_nums = count($box->items);
		}
		else
			$box->load_template("mw_unviewed_comments_empty.html");
		
		
		return $box;
	}
	
	public static function modul_mw_image_comments()
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$unviewed = array();
			
			$query = "SELECT c.*
				FROM ".Core::$TABLE_PREFIX."mw_comment AS c
				LEFT JOIN  ".Core::$TABLE_PREFIX."core_user AS u ON c.user_create_id = u.id
				WHERE c.mw_image_id = ".$_SESSION['imgid']."
				ORDER BY c.date_create DESC";
			$result = Sql::query($query);
			if (Sql::num_rows($result))
			{
				$img = new MWImage($_SESSION["imgid"]);
				$user = unserialize($_SESSION["user"]);
				
				$box = new Core();
				$box->load_template("mw_image_comments.html");
				$box->items = array();
				while ($o = Sql::fetch_array($result))
				{
					$item = new MWComment($o);
					$item->load_template("mw_image_comments_item.html");
					$box->items[] = $item;
					
					if (!$item->option_viewed && $img->user_id == $user->id)
						$unviewed[] = $item->id;
				}
				if (count($unviewed))
				{
					Sql::query("UPDATE ".Core::$TABLE_PREFIX."mw_comment
						SET option_viewed = 1
						WHERE id IN (".implode(",",$unviewed).")");
				}
			}
			else
			{
				$box = new Core();
				$box->load_template("mw_image_comments_empty.html");
			}
		}
		
		return $box;
	}

	public static function modul_mw_image_comment_nums()
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$query = "SELECT COUNT(id) AS nums FROM ".Core::$TABLE_PREFIX."mw_comment WHERE mw_image_id = ".$_SESSION['imgid'];
			$result = Sql::query($query);
			if (Sql::num_rows($result))
			{
				$o = Sql::fetch_array($result);
				$box = new Core($o);
				if ($o['nums'])
					$box->load_template("mw_image_comment_nums.html");
				else
					$box->load_template("mw_image_comment_nums_empty.html");
			}
		}
		
		return $box;
	}
	
	public static function modul_mw_image_comment_form()
	{
		$box = new Core();
		
		$user = unserialize($_SESSION["user"]);
		if ($user->login_ok)
		{
			$box->load_template("mw_image_comment_form.html");
		}
		else
		{
			$box->load_template("mw_image_comment_noform.html");
		}
		
		return $box;
	}
	
	public static function modul_mw_image_comment()
	{
		$box = new Core();
		
		$user = unserialize($_SESSION['user']);
		$imgid = $_SESSION['imgid'];
		$comment = trim($_SESSION['comment']);
		if ($user->login_ok && is_numeric($imgid) && strlen($comment) > 2)
		{
			$image = new MWImage($imgid);
			$comment = addslashes(strip_tags($comment));
			// Ha minden okés, akkor rögzítjük a kommentet
			Sql::query($sql = "INSERT INTO ".Core::$TABLE_PREFIX."mw_comment 
				(id,mw_image_id,comment,option_viewed,date_create,date_modify,user_create_id,user_modify_id)
				VALUES
				(NULL,$imgid,'$comment',0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$user->id,$user->id)");
			$commentid = Sql::insert_id();
			if ($commentid > 0)
			{
				// Majd értesítjük az érintett felhasználókat
				if ($user->option_email_from_comment)
				{
					$img_user = new User();
					$img_user->read($image->user_id);
					$image->load_template("mw_email_comment.html");
					
					$image->comment_user_name = $user->username;
					$image->comment = $comment;
					
					$image->email_to($image,null,"",
						$img_user->email,$img_user->username,
						"Értesítés: új kommentárt fűztek a képedhez");
				}
				$box = new MWComment($commentid);
				$box->load_template("mw_image_comments_item.html");
			}
			else
			{
				$box->template = "{this>message}";
				$box->message = $box->create_message("Ismeretlen SQL hiba! ($sql)","error");
			}
		}
		elseif ($user->login_ok == FALSE)
		{
			$box->template = "{this>message}";
			$box->message = $box->create_message("Ön nincs bejelentkezve!","error");
		}
		elseif (!$imgid)
		{
			$box->template = "{this>message}";
			$box->message = $box->create_message("A kép nem azonosítható!","error");
		}
		elseif (strlen($comment)<3)
		{
			$box->template = "{this>message}";
			$box->message = $box->create_message("A kommentár minimum 3 leütés hosszú kell legyen!","error");
			Core::$MAIN['HEADER'] .= "HTTP/1.0 400 Bad Request\n";
		}
		else
		{
			$box->template = "{this>message}";
			$box->message = $box->create_message("Ismeretlen hiba!","error");
		}
		
		return $box;
	}
}
?>