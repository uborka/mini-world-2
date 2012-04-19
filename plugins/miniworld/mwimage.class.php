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

class MWImage extends Core
{
	public static function get_image($data)
	{
		$id = 0;
		if (is_array($data))
			$id = $data["id"];
		elseif (is_numeric($data))
			$id = $data;
		else
			die;
		if (Core::$MEMCACHE)
		{
			if (! ($image = Core::$MEMCACHE->get(Core::$HTTP_HOST.":mw_image_$id",MEMCACHE_COMPRESSED)))
			{
				$image = new MWImage($data);
				Core::$MEMCACHE->set(Core::$HTTP_HOST.":mw_image_$id",$image,MEMCACHE_COMPRESSED,300);
			}
		}
		else
		{
			$image = new MWImage($data);
		}
		return $image;
	}
	
	public function __construct($data)
	{
		// Ha a DATA egy rekord
		if (is_array($data))
		{
			parent::__construct($data);
		}
		// Ha a DATA egy kulcs
		elseif (is_numeric($data))
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."mw_image` WHERE (`id` = $data)";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				parent::__construct(Sql::fetch_array($result));
			}
		}
		
		// miniatűrök beállítása
		if (!$this->tn_coordinates)
		{
			$this->tn_coordinates = $this->get_coordinates();
			Sql::query("UPDATE ".Core::$TABLE_PREFIX."mw_image SET tn_coordinates = '$this->tn_coordinates' WHERE id = '$this->id'");
		}
		$th = basename($this->image_url);
		if (!file_exists(Core::$DOCROOT.$this->image_url))
		{
			// HIBA: nincs meg a kép
		}
		elseif (!file_exists(Core::$DOCROOT."/items/_thumbs/u".$this->user_id."/tn240_".$th))
		{
//			$this->create_thumbnails();
			$c = json_decode($this->tn_coordinates);
			$this->create_thumbnails($c[0],$c[1],($c[2]-$c[0]));
		}
		$size = getimagesize(Core::$DOCROOT.$this->image_url);
		$this->width = $size[0];
		$this->height = $size[1];
		$this->image_32_url = "/items/_thumbs/u".$this->user_id."/tn32_".$th;
		$this->image_240_url = "/items/_thumbs/u".$this->user_id."/tn240_".$th;
		$this->image_980_url = "/items/_thumbs/u".$this->user_id."/tn980_".$th;
		
		$this->desc = $this->make_link($this->desc);
		$this->unaccepted_class = $this->option_accepted ? "" : "unaccepted";
		
		// Kapcsolódó adatok betöltés
		$this->create_date = Core::unix_to_date($this->date_create);

		$this->category = new MWCategory((int)$this->mw_category_id);
		$this->category_title = $this->category->title;
		$this->category_url = Core::$HTTP_HOST . "/kategoria/" . $this->category->permalink . ".html";
		
//		$this->user = new User();
//		$this->user->read($this->user_id);
		$this->user = User::get_user($this->user_id);
		$this->user_name = $this->user->username;
		$this->user_album_url = Core::$HTTP_HOST . "/album/" . strtolower($this->user_name) . ".html";
		$this->user_category_album_url = Core::$HTTP_HOST . "/album/" . $this->category->permalink . "/" . strtolower($this->user_name) . ".html";
		
		$this->url = Core::$HTTP_HOST . "/kep/" . $this->permalink . ".html";
		
		// Felhasználói funkciók betöltése
		$user = unserialize($_SESSION["user"]);
		if ($user->login_ok)
		{
			if ($user->id == $this->user_id)
			{
				$this->user_func = new Core();
				$this->user_func->load_template("mw_image_user_func.html");
				$this->user_func->delete_url = Core::$HTTP_HOST . "?a=mw_image_delete&imgid=" . $this->id;
				$this->user_func->edit_url = Core::$HTTP_HOST . "?a=mw_image_editor&imgid=" . $this->id;
			}
			elseif (in_array($user->user_group_id,array(2,3)))
			{
				$this->moderator_func = new Core();
				$this->moderator_func->load_template("mw_image_moderator_func.html");
				$this->moderator_func->disable_url = Core::$HTTP_HOST . "?a=mw_image_moderate&todo=disable&imgid=" . $this->id;
				$this->moderator_func->enable_url = Core::$HTTP_HOST . "?a=mw_image_moderate&todo=enable&imgid=" . $this->id;
				if ($this->option_accepted == 1)
					$this->moderator_func->enable_class = "disabled";
				else
					$this->moderator_func->disable_class = "disabled";
			}
			if (($user->id == $this->user_id) || (in_array($user->user_group_id,array(2,3))))
			{
				$this->moderator_info = new Core();
				$this->moderator_info->load_template("mw_image_moderator_info.html");
				
				if ($this->option_accepted == 1)
					$this->moderator_info->title = "Engedélyezés időpontja";
				else
					$this->moderator_info->title = "Moderálás időpontja";
				if ($this->user_modify_id == $this->user_id)
				{
					$this->moderator_info->moderator_date = "várakozik";
				}
				else
				{
//					$mod = new User();
//					$mod->read($this->user_modify_id);
					$this->moderator_info->moderator_date = Core::unix_to_date($this->date_modify);
				}
			}
		}
		
		// Utófunkciók futtatása
		foreach (Core::get_postfunctions("mw_image_init") as $fnname)
			call_user_func($fnname, $this);
	}
	
	private function get_coordinates()
	{
		$coordinates = "";
		
		$size = getimagesize(Core::$DOCROOT.$this->image_url);
		$width = $size[0];
		$height = $size[1];
		$x = 0;
		$y = 0;
		$w = $width - $x;
		if ($w > $height - $y)
			$w = $height - $y;
		if ($width > 980)
		{
			if ($width > $height)
				$coordinates = "[0,0,$height,$height]";
			else
				$coordinates = "[0,0,$width,$width]";
		}
		else
		{
			$coordinates = "[0,0,$w,$w]";
		}
		
		return $coordinates;
	}
	public function delete_thumbnails()
	{
		$folder = Core::$DOCROOT."/items/_thumbs/u".$this->user_id;
		unlink($folder."/tn32_".basename($this->image_url));
		unlink($folder."/tn240_".basename($this->image_url));
		unlink($folder."/tn980_".basename($this->image_url));
//		Core::debug(file_exists($folder."/tn32_".basename($this->image_url)));
	}
	
	public function create_thumbnails($x=0,$y=0,$w=0)
	{
//		Core::debug($x);
//		Core::debug($y);
//		Core::debug($w);
		$folder = Core::$DOCROOT."/items/_thumbs/u".$this->user_id;
//		Core::debug($folder."/".basename($this->image_url));
//		Core::debug(Core::$DOCROOT.$this->image_url);
		if (!file_exists($folder))
		{
			mkdir($folder);
			chmod($folder,0755);
		}
		
		// Az eredeti kép betöltése
		$osize = getimagesize(Core::$DOCROOT.$this->image_url);
		$owidth = $osize[0];
		$oheight = $osize[1];
		$type = $osize[2]; // 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF
		if ($type<3)
		{
			switch ($type)
			{
				case 1:
					$original = imagecreatefromgif(Core::$DOCROOT.$this->image_url);
					break;
				case 2:
					$original = imagecreatefromjpeg(Core::$DOCROOT.$this->image_url);
					break;
				case 3:
					$original = imagecreatefrompng(Core::$DOCROOT.$this->image_url);
					break;
			}
		}
		else
			exit;
		// Az eredeti kép kicsinyítése 980-asra, ha szélesebb
		if ($owidth > 980)
		{
			// 980 / 1024 * 768 =
			$h = round(980 / $owidth * $oheight);
			$dst980 = imagecreatetruecolor(980, $h);
			imagecopyresized($dst980, $original, 0, 0, 0, 0, 980, $h, $owidth, $oheight);
			imagejpeg($dst980,$folder."/tn980_".basename($this->image_url),100);
			chmod($folder."/tn980_".basename($this->image_url),0755);
		}
		else
		{
			$dst980 = $original;
			imagejpeg($dst980,$folder."/tn980_".basename($this->image_url),100);
			chmod($folder."/tn980_".basename($this->image_url),0755);
		}
		// A kivágandó régió beállítása
		if ($w == 0)
			$w = $owidth - $x;
		if ($w > $oheight - $y)
			$w = $oheight - $y;
//		Core::debug($w);
		// A megjelölt terület kivágása
		$cropped = imagecreatetruecolor($w,$w);
		imagecopy($cropped,$dst980,0,0,$x,$y,$w,$w);
//		imagejpeg($cropped,$folder."/tnc_".basename($this->image_url),75);
		// A kivágott régióból a miniatűrök legyártása
		// 240-es
		$dst240 = imagecreatetruecolor(240,240);
		imagecopyresized($dst240,$cropped,0,0,0,0,240,240,$w,$w);
		imagejpeg($dst240,$folder."/tn240_".basename($this->image_url),75);
		chmod($folder."/tn240_".basename($this->image_url),0755);
		imagedestroy($dst240);
		// 32-es
		$dst32 = imagecreatetruecolor(32,32);
		imagecopyresized($dst32,$cropped,0,0,0,0,32,32,$w,$w);
		imagejpeg($dst32,$folder."/tn32_".basename($this->image_url),75);
		chmod($folder."/tn32_".basename($this->image_url),0755);
		imagedestroy($dst32);
		
		imagedestroy($original);
		imagedestroy($dst980);
		imagedestroy($cropped);
	}
	
	public static function make_link($text)
	{
		return preg_replace(
		     array(
		       '/(?(?=<a[^>]*>.+<\/a>)
		             (?:<a[^>]*>.+<\/a>)
		             |
		             ([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+)
		         )/iex',
		       '/<a([^>]*)target="?[^"\']+"?/i',
		       '/<a([^>]+)>/i',
		       '/(^|\s)(www.[^<> \n\r]+)/iex',
		       '/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+)
		       (\\.[A-Za-z0-9-]+)*)/iex'
		       ),
		     array(
		       "stripslashes((strlen('\\2')>0?'\\1<a href=\"\\2\">\\2</a>\\3':'\\0'))",
		       '<a\\1',
		       '<a\\1 target="_blank">',
		       "stripslashes((strlen('\\2')>0?'\\1<a href=\"http://\\2\">\\2</a>\\3':'\\0'))",
		       "stripslashes((strlen('\\2')>0?'<a href=\"mailto:\\0\">\\0</a>':'\\0'))"
		       ),
		       $text
		   );
	}
	
	public static function search_by_permalink($permalink)
	{
		$return = 0;

		// Az oldal megkeresése a permalink-je alapján
		$query = "SELECT `id`,`title` FROM `".Core::$TABLE_PREFIX."mw_image` WHERE `permalink` = '$permalink'";
		$result = Sql::query($query);
		if (Sql::num_rows($result) == 1)
		{
			$o = Sql::fetch_array($result);
			$return = $o["id"];
		}

		return $return;
	}
	
	public static function modul_mw_image($modul_variable)
	{
		$image = NULL;

		if (strpos($modul_variable,":") !== FALSE)
		{
			$mv = explode(":",$modul_variable);
			if (is_numeric($mv[1]))
			$image = new MWImage((int)$mv[1]);
		}
		if (is_null($image))
			if (!empty($_SESSION['imgid']))
				$image = new MWImage((int)$_SESSION['imgid']);
		
		if (!is_null($image))
		{
			Core::$MAIN['TITLE'] = $image->title;
		}
		
		if (is_object($image))
			$image->load_template("mw_image.html");
			
		// Utófunkciók futtatása
		foreach ($image->get_postfunctions("mw_image_modul") as $fnname)
			call_user_func($fnname, $image);

		return $image;
	}
	
	public static function modul_mw_image_editor()
	{
		$image = NULL;

		if (!empty($_SESSION['imgid']))
		{
			$user = unserialize($_SESSION['user']);
			$image = new MWImage((int)$_SESSION['imgid']);
			
			if (!empty($_SESSION['todo']))
				if ($_SESSION['todo'] == "store")
					if ($user->id == $image->user_id)
					{
						$title = stripslashes(trim(strip_tags($_SESSION['title'])));
						$desc = stripslashes(trim(strip_tags($_SESSION['desc'])));
						$coordinates = $_SESSION['tn_coordinates'];
						$cid = $_SESSION['mw_category_id'];
						
						Sql::query("UPDATE `".Core::$TABLE_PREFIX."mw_image` SET
							`title` = '$title',
							`mw_category_id` = $cid,
							`desc` = '$desc',
							`tn_coordinates` = '$coordinates',
							`user_modify_id` = $user->id,
							`date_modify` = UNIX_TIMESTAMP()
							WHERE `id` = $image->id");
						
						if ($image->tn_coordinates != $coordinates)
						{
							$c = json_decode($coordinates);
//							Core::debug($c);
							$image->delete_thumbnails();
							$image->create_thumbnails($c[0],$c[1],($c[2]-$c[0]));
						}
						
						// Utófunkciók futtatása
						foreach ($image->get_postfunctions("mw_image_editor") as $fnname)
							call_user_func($fnname, $image);
						
						$image = new MWImage($image->id);
					}
					else
					{}
		
			if (!is_null($image))
			{
				Core::$MAIN['TITLE'] = "Szerkesztő: " . $image->title;
			}
		
			if (is_object($image))
				$image->load_template("mw_image_editor.html");
		}

		return $image;
	}
	
	public static function modul_mw_image_details($modul_variable)
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$box = new MWImage((int)$_SESSION['imgid']);
			$box->load_template("mw_image_details.html");
			
			// kép dimenziója
			$size = getimagesize(Core::$DOCROOT.$box->image_url);
			$box->width = $size[0];
			$box->height = $size[1];
			// kép fizikai mérete
			$box->size_in_byte = filesize(Core::$DOCROOT.$box->image_url);
			if ($box->size_in_byte > 1024)
			{
				if ($box->size_in_byte > (1048576))
					$box->size_in_mbyte = sprintf("%1.1f",$box->size_in_byte / 1048576) . " MB";
				else
					$box->size_in_mbyte = sprintf("%1.1f",$box->size_in_byte / 1024) . " kB";
			}
			else
				$box->size_in_mbyte = $box->size_in_mbyte . " B";
			// EXIF adatok
			$exif = exif_read_data(Core::$DOCROOT.$box->image_url);
//			Core::debug($exif);
			// kép eredeti dátuma
			if (array_key_exists('DateTimeOriginal',$exif))
				$box->original_date = Core::unix_to_date(strtotime($exif['DateTimeOriginal']));
			else
				$box->original_date = Core::unix_to_date($exif['FileDateTime']);
			if (array_key_exists('Model',$exif))
				$box->camera = $exif['Make'].' '.$exif['Model'];
			else
				$box->camera = 'nincs megadva';
		}
		
		return $box;
	}
	
	public static function modul_mw_image_upload()
	{
		$user = unserialize($_SESSION['user']);
		$msg = array();
		// A feltöltött képeket másolja fel a felhasználóhoz, ha azok JPEG-ek
		if ($_FILES)
		{
			$good = 0;
			$bad = array();
			for($i = 0;$i < count($_FILES['files']['name']); $i++)
			{
				// Ha a kép JPEG, akkor átmásoljuk a felhasználó mappájába
				if (!$_FILES['files']['size'][$i] || $_FILES['files']['size'][$i]>8388608 || $_FILES['files']['error'][$i] || $_FILES['files']['type'][$i]!='image/jpeg')
					$bad[] = $_FILES['files']['name'][$i];
				else
				{
					$size = getimagesize($_FILES['files']['tmp_name'][$i]);
					if ($size[2] != 2)
						$bad[] = $_FILES['files']['name'][$i]."(".$size[2].")";
					else
					{
						$folder = "/items/miniworld/u".$user->id;
						if (!is_dir(Core::$DOCROOT.$folder)) 
						{
						    mkdir(Core::$DOCROOT.$folder);
						    chmod(Core::$DOCROOT.$folder,755);
						}
						// Ha a kép JPEG, akkor áttesszük a felhasználóhoz
						$md5 = strtolower(md5(time()));
						// Ha van már ilyen file, akkor generálunk új nevet
						while (file_exists(Core::$DOCROOT.$folder."/".$md5.".jpg"))
						{
							$md5 = strtolower(md5(time()));
						}
						// Ellenben mentjük a felhasználóhoz
						move_uploaded_file($_FILES['files']['tmp_name'][$i],Core::$DOCROOT.$folder."/".$md5.".jpg");
						// Majd rögzítjük az adatokat az adatbázisban
						Sql::query($query = "INSERT INTO ".Core::$TABLE_PREFIX."mw_image
							(id,user_id,mw_category_id,title,image_url,option_accepted,permalink,user_create_id,user_modify_id,date_create,date_modify)
							VALUES
							(NULL,$user->id,6,'névtelen kép','$folder/$md5.jpg',0,'$md5',$user->id,$user->id,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
						$imgid = Sql::insert_id();
						// Végül értesítjük a moderátorokat a feltöltésről
						$image = new MWImage($imgid);
						$img_user = new User();
						$img_user->read($image->user_id);
						$image->load_template("mw_email_upload.html");

						$image->email_to($image,null,"",
							$img_user->email,$img_user->username,
							"Értesítés: $img_user->username új képet töltöt fel",
							"option_email_from_upload");
						$good++;
					}
				}
			}
			if ($good)
			{
				$msg['message'] = "Sikeresen feltöltött $good képet.";
				if ($bad)
					$msg['message'] .= " ".count($bad)." kép feltöltése nem sikerült.";
				$msg['message'] .= " 5 másodperc múlva automatikusan átíránytunk a saját albumodba, ahol megadhatod az új képek címét, leírását, kategóriáját.";
				$_msg = $user->create_message($msg['message'],"ok");
				$msg['status'] = 'ok';
				$msg['message'] = $_msg->generate();
			}
			else
			{
				$filenames = implode(", ",$bad);
				$_msg = $user->create_message("A feltöltött képek nem feleltek meg a követelményeknek ($filenames)!","error");
				$msg['status'] = 'error';
				$msg['message'] = $_msg->generate();
			}
		}
		else
		{
			$msg['status'] = 'error';
			$_msg = $user->create_message("Nem sikerült az állományok feltöltése!","error");
			$msg['message'] = $_msg->generate();
		}
		echo json_encode($msg);
	}
	
	public static function modul_mw_image_delete()
	{
		$box = null;
		
		if (!empty($_SESSION['imgid']))
		{
			$user = unserialize($_SESSION['user']);
			$image = new MWImage((int)$_SESSION['imgid']);
			
			if ($user->id == $image->user_id)
			{
				Sql::query("DELETE FROM ".Core::$TABLE_PREFIX."mw_image WHERE id = $image->id");
				$_SESSION['uid'] = $user->id;
				$box = MWAlbum::modul_mw_album_user();
				$box->message = $box->create_message("A képet törlölted.","ok");
			}
		}
		
		return $box;
	}
	
	public static function modul_mw_image_download()
	{
		if (!empty($_SESSION['url']))
		{
			if (file_exists(Core::$DOCROOT . $_SESSION['url']) && !empty($_SESSION['imgid']))
			{
				$image = new MWImage((int)$_SESSION['imgid']);

				$user = unserialize($_SESSION['user']);
				$sid = $_SESSION["SID"];
				$id = $image->id;
				$uid = $user->login_ok ? $user->id : 0;
				$ip = $_SERVER['REMOTE_ADDR'];
				$query = "INSERT INTO ".Core::$TABLE_PREFIX."mw_image_download
							(id,mw_image_id,user_agent,ip_address,session_id,date_create,date_modify,user_create_id,user_modify_id)
							VALUES
							(NULL,$id,'".$_SERVER['HTTP_USER_AGENT']."','".$ip."','$sid',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),$uid,$uid)";
				Sql::query($query);
				
				
				$filename = Core::unaccuate($image->user_name . "-" . $image->title) . ".jpg";
				
				header("Content-type: application/octet-stream");
				header("Content-Disposition: inline; filename=\"".$filename."\"");
				header("Content-length: ".(string)(filesize(Core::$DOCROOT.$image->image_url)));
				header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
				header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
				header("Cache-Control: no-cache, must-revalidate");
				header("Pragma: no-cache");

				readfile(Core::$DOCROOT . $_SESSION['url']);
			}
		}
		
		return NULL;
	}
}
?>