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

class User extends Core
{
	public $login_ok = false;

	public static $COMPULSORY_FIELDS = array();
	public static $NON_COMPULSORY_FIELDS = array();
	private static $FIELD_TYPE = array(
		"id"=>"disabled",
		"username"=>"text",
		"password"=>"password",
		"retype"=>"password",
		"fullname"=>"letters",
		"email"=>"email",
		"lang"=>"none",
		"phone"=>"phone",
		"fax"=>"phone",
		"company"=>"none",
		"taxnumber"=>"sequence",
		);
	private static $FIELD_LENGTH = array(
		"id"=>0,
		"username"=>6,
		"password"=>6,
		"retype"=>6,
		"fullname"=>5,
		"email"=>6,
		"lang"=>0,
		"phone"=>6,
		"fax"=>6,
		"company"=>5,
		"taxnumber"=>0,
		);

	public function __set($name, $value)
	{
		switch ($name)
		{
		case "password":
			break;
		default:
			$this->props[$name] = $value;
			break;
		}
	}

	public function __construct($name = NULL,$password = NULL)
	{
		$tb = microtime();

		$this->login_ok = FALSE;
		$this->message = "";

		if (!is_null($name) && !is_null($password))
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE (`username`)='$name' AND `password`='$password' AND (`option_banned`=0) LIMIT 1";
			$result = Sql::query($query);
			if ($result)
			{
				$this->login($result);
			}
		}

		Core::log("ctor()",$tb);
	}
	
	public function login($result,$name)
	{
		if (Sql::num_rows($result))
		{
			// Az adatsorok beállítása mezőnek
			$o = Sql::fetch_array($result);
			$keys = array_keys($o);
			foreach ($keys as $key)
			{
				// A számmal indexelt mezők és a jelszó mező automatikus eldobása
				if (($key !== "password")&&(!is_int($key)))
				{
					$this->props[$key] = $o[$key];
				}
			}
			$this->login_ok = TRUE;
			$this->init();
			// A belépés tárolása és feljegyzése
			$query = "UPDATE `".Core::$TABLE_PREFIX."core_user` SET `date_last_login`=UNIX_TIMESTAMP() WHERE `id`=".$this->id;
			Sql::query($query);
		}
		else
		{
			$this->login_ok = FALSE;
			// A sikertelenség okának ellenőrzése
			// Az azonosító ellenőrzése
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE `username`='$name' LIMIT 1";
			$result = Sql::query($query);
			if ($result)
			{
				if (Sql::num_rows($result)>0)
				{
					$o = Sql::fetch_array($result);
					// A kizárás ellenőrzése
					if ($o["option_banned"]==1)
						$this->message = $this->create_message("Az Ön felhasználója nincs engedélyezve.","error");
					else
						$this->message = $this->create_message("Az Ön által megadott jelszó nem helyes.","error");
				}
				else
				{
					// Ha nincs ilyen azonosító...
					$this->message = $this->create_message("Az Ön által megadott azonosító nincs regisztrálva.","error");
				}
			}
		}
	}
	
	public static function get_user($data)
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
			if (! ($user = Core::$MEMCACHE->get(Core::$HTTP_HOST.":mw_user_$id",MEMCACHE_COMPRESSED)))
			{
				$user = new User();
				$user->read($data);
				Core::$MEMCACHE->set(Core::$HTTP_HOST.":user_$id",$user,MEMCACHE_COMPRESSED,600);
			}
		}
		else
		{
			$user = new User();
			$user->read($data);
		}
		return $user;
	}

	public function read($user_id)
	{
		$tb = microtime();
		
		if ( (int)$user_id > 0)
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE `id`=$user_id LIMIT 1";
			$result = Sql::query($query);
			if ($result)
			{
				if (Sql::num_rows($result))
				{
					// Az adatsorok beállítása mezőnek
					$o = Sql::fetch_array($result);
					$keys = array_keys($o);
					foreach ($keys as $key)
					{
						// A számszerűsített mezők és a jelszó mező automatikus eldobása
						if (($key !== "password")&&(!is_int($key)))
						{
							$this->props[$key] = $o[$key];
						}
					}
					// A belépés tárolása és feljegyzése
					$this->login_ok = FALSE;
					$this->init();
				}
				else
					$this->login_ok = FALSE;
			}
		}
		else
			$this->login_ok = FALSE;

		Core::log("read()",$tb);
	}

	public function init()
	{
		foreach (Core::get_prefunctions("user_init") as $fnname)
			call_user_func($fnname, $this);
		
		// A felhasználó csoport és jogok betöltése
		if ($this->login_ok)
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user_group` WHERE `id`=".$this->user_group_id;
			$result = Sql::query($query);
			$this->user_group = new UserGroup(Sql::fetch_array($result));
			if ($this->user_group->is_admin == 1)
			{
				// Ha a felhasználó admin, akkor a jogok betöltése
				$this->user_rights = new UserRightList($this->user_group_id);
			}
		}
		// A dátumok formázása
		$this->birth_date = Core::unix_to_date($this->date_birth);
		$this->registered_date = Core::unix_to_datetime($this->date_registered);
		$this->last_login_date = $this->date_last_login == 0 ? "soha" : Core::unix_to_datetime($this->date_last_login);
		// Az opciók formázása
		$this->banned_option = $this->option_banned ? "KIZÁRVA" : "Engedélyezve";
		$this->newsletter_option = $this->option_newsletter ? "Feliratkozva" : "Nincs feliratkozva";
		$this->email_from_registration_option = $this->option_email_from_registration ? "KÉZBESÍT" : "Nincs";
		
		foreach (Core::get_postfunctions("user_init") as $fnname)
			call_user_func($fnname, $this);
	}

	public function save_cookies($username,$passcode)
	{
		setcookie("user",$username,time()+Core::$PARAM["AUTOLOGIN_EXPIRE_SECONDS"]);
		setcookie("usercode",$passcode,time()+Core::$PARAM["AUTOLOGIN_EXPIRE_SECONDS"]);
	}
	
	public function delete_cookies()
	{
		setcookie("user","",time()-1);
		setcookie("usercode","",time()-1);
	}

	public function set_password($password)
	{
		$tb = microtime();

		$query = "UPDATE `".Core::$TABLE_PREFIX."core_user` SET `password`=MD5('$password') WHERE (`id`=$this->id)";
		Sql::query($query);

		$this->log("set_password()",$tb);
	}
	
	public static function generate_random_password()
	{
		$tb = microtime();

		$pwchars = "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ";
		$passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
		$passwd = '';

		for ( $i = 0; $i < $passwordlength; $i++ )
		{
			$passwd .= $pwchars[ floor( mt_rand(0,strlen($pwchars))) ];
		}
		
//		$this->log("generate_random_password()",$tb);
		
		return $passwd;
	}

	public function regist()
	{
		$tb = microtime();

		if ($_SESSION["a"]=="user_regist" && $_SESSION["todo"]=="store")
		{
			$lang = array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
			
			$CODES = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
			//			    0   1   2   3   4   5   6   7   8   9   10  11  12  13  14  15  16  17  18  19  20  21  22  23  24  25  26  27  28  29  30  31  32  33  34  35
			mb_regex_encoding("UTF-8");
	
			$error = array();
			$banned = 'N';
			// Adatok hosszának és karaktereinek ellenőrzése
			foreach (User::$COMPULSORY_FIELDS as $fieldname)
			{
				$data = $_POST[$fieldname];
				switch (User::$FIELD_TYPE[$fieldname])
				{
				case "text":
					if (!mb_ereg_match("[A-ZÁÄÉËÍÓÖŐÚÜŰŁß]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "number":
					if (!mb_ereg_match("[\d]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "sequence":
					if (!mb_ereg_match("[\d\-]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "email":
					if (!mb_ereg_match("\w[-.\w]*\@[-A-Z0-9]+(\.[-A-Z0-9]+)*\.[A-Z]{2,4}",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "phone":
					if (!mb_ereg_match("[\d\-\b/\(\)\+]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "logic":
					if (mb_strtoupper($data,"UTF-8") !== 'Y')
						$e->newsletter = 'N';
					break;
				case "zipcode":
					if (!mb_ereg_match("[A-Z0-9]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "password":
				case "none":
					break;
				default:
					break;
				}
				if (User::$FIELD_LENGTH[$fieldname] > 0)
				{
					if (strlen($data) < User::$FIELD_LENGTH[$fieldname])
						$error[] = Core::$LANG["REGIST_ERROR_LENGTH_".strtoupper($fieldname)];
				}
				$this->props[$fieldname] = $data;
			}
			foreach (User::$NON_COMPULSORY_FIELDS as $fieldname)
			{
				$this->props[$fieldname] = mb_strtoupper($_POST[$fieldname],"UTF-8");
			}
			// Jelszó ellenőrzése
			$password = $_POST["password"];
			$retype = $_POST["retype"];
			if (!($password === $retype))
				$error[] = Core::$LANG["REGIST_ERROR_PASSWORD"];
			if (mb_strlen($password,"UTF-8")<6)
				$error[] = Core::$LANG["REGIST_ERROR_LENGHT_PASSWORD"];
			// Felhasználónév ellenőrzése
			$query = "SELECT `id` FROM ".Core::$TABLE_PREFIX."core_user`
				WHERE (`username`='".mb_strtolower($_POST["username"])."') LIMIT 1";
			$result = Sql::query($query);
			if (Sql::num_rows($result))
			{
				$error[] = Core::$LANG["REGIST_ERROR_ALREADY_EXISTS"];
			}
			if (!$error)
			{
				$query = "INSERT INTO `".Core::$TABLE_PREFIX."core_user`
							(`username`,
							`fullname`,
							`company`,
							`taxnumber`,
							`email`,
							`phone`,
							`password`,
							`date_registered`,
							`date_create`,
							`date_modify`,
							`user_create_id`,
							`user_modify_id`
							)
							VALUES
							('$this->username',
							'$this->fullname',
							'$this->company',
							'$this->taxnumber',
							'$this->email',
							'$this->phone',
							MD5('$password'),
							UNIX_TIMESTAMP(),
							UNIX_TIMESTAMP(),
							UNIX_TIMESTAMP(),
							0,
							0
							)";
				Sql::query($query);
				$uid = Sql::insert_id();
				// A felhasználói objektum inicializálása
				$usr = new User();
				$usr->read($uid);
				
				// E-mail összeállítása és küldése először a most regisztrált felhasználónak
				$tmpl = "email_regist_to_user".Core::$PARAM["LANG_POSTFIX"][$lang].".html";
				$usr->load_template($tmpl);
				$this->email_to($usr,null,"",$to = $usr->email,$usr->fullname,"Regisztráció");
				
				// E-mail összeállítása és küldése a webáruház karbantartójának
				$usr->load_template("email_regist_to_admin.html");
				$this->email_to($usr,null,"",null,"","Regisztráció ($usr->fullname)","option_email_from_registration");
				
				$tmpl = "user_regist_success".Core::$PARAM["LANG_POSTFIX"][$lang].".html";
				$this->load_template($tmpl);
				Core::$MAIN['TITLE'] = Core::$LANG['TITLE_REGIST_SUCCESS'];
	
				$this->log("regist()",$tb);
	
				return FALSE;
			}
			else
			{
				$this->load_template("user_regist.html");
				Core::$MAIN['TITLE'] = Core::$LANG['TITLE_REGIST'];
				
				$this->log("regist()",$tb);
	
				return $error;
			}
		}
		else
		{
			$this->load_template("user_regist.html");
			Core::$MAIN['TITLE'] = Core::$LANG['TITLE_REGIST'];
			
			$this->log("regist()",$tb);
			
			return FALSE;
		}
	}

	public function lost_password()
	{
		$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
		$email = array_key_exists("email",$_SESSION) ? $_SESSION["email"] : "";
		
		$this->load_template("user_lost_password.html");
		Core::$MAIN['TITLE'] = Core::$LANG['TITLE_LOST_PASSWORD'];
		
		switch ($todo)
		{
			case "generate":
				// Az adatok alapján a felhasználói jelszó átíállítása
				// és e-mail küldése
				if (strlen($email) > 0)
				{
					if (mb_ereg_match("\w[-.\w]*\@[-A-Z0-9]+(\.[-A-Z0-9]+)*\.[A-Z]{2,4}",mb_strtoupper($email,"UTF-8")))
						$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE `email`='$email'";
					else
						$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE `username`='$email'";

					$result = Sql::query($query);
					if ($result)
					{
						if (Sql::num_rows($result) == 1)
						{
							$o = Sql::fetch_array($result);
							if ($o["option_banned"] == 1)
								$this->message = $this->create_message("A megadott felhasználó le van tiltva.","error");
							else
							{
								// HA minden adat helyes, akkor a jelszó átállítása és az e-mail összeállítása
								$usr = new User();
								$usr->read($o["id"]);
								$usr->newpassword = $this->generate_random_password();
								$usr->set_password($usr->newpassword);
								
								$usr->load_template("email_lost_password.html");
								
								$this->email_to($usr,null,null,$usr->email,$usr->fullname,"Elfelejetett jelszó");
								
								$this->load_template("user_lost_password_success.html");
								$this->message = $this->create_message("Az új jelszavad elküldtük a megadott e-mail címre.","ok");
							}
						}
						elseif (Sql::num_rows($result) > 1)
							$this->message = $this->create_message("A megadott azonosítókhoz több felhasználó van regisztrálva.<br/>Kérem keresse meg ügyfélszolgálatunkat!","error");
						else
							$this->message = $this->create_message("A megadott azonosító nincs regisztrálva.","error");
					}
					else
						$this->message = $this->create_message("A megadott adatok értelmezhetetlenek.","error");
				}
				else
					$this->message = $this->create_message("Nem adtott meg semmilyen adatot.","error");
				break;
		}
		
	}
	
	public function update()
	{
		if ($_SESSION["a"]=="user_profile" && $_SESSION["todo"]=="update")
		{
			$CODES = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
			//			    0   1   2   3   4   5   6   7   8   9   10  11  12  13  14  15  16  17  18  19  20  21  22  23  24  25  26  27  28  29  30  31  32  33  34  35
			mb_regex_encoding("UTF-8");
	
			$error = array();
			// Adatok hosszának és karaktereinek ellenőrzése
			foreach (User::$COMPULSORY_FIELDS as $fieldname)
			{
				$data = $_POST[$fieldname];
				switch (User::$FIELD_TYPE[$fieldname])
				{
				case "text":
					if (!mb_ereg_match("[A-ZÁÄÉËÍÓÖŐÚÜŰŁß]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "number":
					if (!mb_ereg_match("[\d]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "sequence":
					if (!mb_ereg_match("[\d\-]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "email":
					if (!mb_ereg_match("\w[-.\w]*\@[-A-Z0-9]+(\.[-A-Z0-9]+)*\.[A-Z]{2,4}",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "phone":
					if (!mb_ereg_match("[\d\-\b/\(\)\+]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "yesno":
					if (mb_strtoupper($data,"UTF-8") !== 'Y')
						$e->newsletter = 'N';
					break;
				case "zipcode":
					if (!mb_ereg_match("[A-Z0-9]+",mb_strtoupper($data,"UTF-8")))
						$error[] = Core::$LANG["REGIST_ERROR_".strtoupper($fieldname)];
					break;
				case "password":
					break;
				default:
					break;
				}
				if ((User::$FIELD_LENGTH[$fieldname] > 0) && (User::$FIELD_TYPE[$fieldname] != "password"))
				{
					if (strlen($data) < User::$FIELD_LENGTH[$fieldname])
						$error[] = Core::$LANG["REGIST_ERROR_LENGTH_".strtoupper($fieldname)];
				}
				$this->props[$fieldname] = $data;
			}
			foreach (User::$NON_COMPULSORY_FIELDS as $fieldname)
			{
				$this->props[$fieldname] = mb_strtoupper($_POST[$fieldname],"UTF-8");
			}
			// Jelszó ellenőrzése
			$password = $_POST["password"];
			$retype = $_POST["retype"];
			if (!($password === $retype))
				$error[] = Core::$LANG["REGIST_ERROR_PASSWORD"];
			elseif ((mb_strlen($password,"UTF-8")<6)&&(strlen($password)>0))
				$error[] = Core::$LANG["REGIST_ERROR_LENGHT_PASSWORD"];
			elseif (strlen($password)>0)
				$this->set_password($password);
			if (count($error)==0)
			{
				$query = "UPDATE `".Core::$TABLE_PREFIX."core_user` SET
							`fullname`='".stripslashes($this->fullname)."',
							`company`='".stripslashes($this->company)."',
							`taxnumber`='".stripslashes($this->taxnumber)."',
							`email`='".stripslashes($this->email)."',
							`phone`='".stripslashes($this->phone)."',
							`date_modify`=UNIX_TIMESTAMP(),
							`user_modify_id`=$this->id
						WHERE (`id`=$this->id)";
				Sql::query($query);
				// A felhasználói objektum inicializálása
				$this->read($this->id);
				$this->login_ok = TRUE;
				$_SESSION["user"] = serialize($this);
				
				$this->errors = "";
				$this->message = $this->create_message("Személyes beállításait frissítette.","ok");
			}
			else
			{
				$errors = "";
				foreach ($error as $m)
					$errors .= $m;
				$this->errors = $errors;
				
				$this->message = $this->create_message("A beállítások mentése sikertelen.","error");
			}
		}
		else
		{
			$this->errors = "";
			$this->message = $this->create_message("A beállítások mentése sikertelen.","error");
		}
	}

	public static function modul_userbox($modul_variable)
	{
		$user = unserialize($_SESSION["user"]);
		
		if ($user->login_ok)
			$user->load_template("userbox_logged.html");
		else
			$user->load_template("userbox_login.html");
		return $user;
	}

	public static function modul_user_regist($modul_variable)
	{
		// regisztráció
		$user = new User();
		$user->message = "";
		$user->errors = "";
		$messages = $user->regist();
		if ($messages !== FALSE)
		{
			$errors = "";
			foreach ($messages as $m)
				$errors .= $m;
			$user->errors = $errors;
		}
		// ha sikertelen, akkor...
		return $user;
	}

	public static function modul_user_profile($modul_variable)
	{
		$user = unserialize($_SESSION["user"]);
		$todo = array_key_exists("todo",$_SESSION) ? $_SESSION["todo"] : "";
		if ($todo == "update")
		{
			$user->update();
			$user->load_template("user_profile.html");
			Core::$MAIN['TITLE'] = Core::$LANG['TITLE_PROFILE'];
			
			return $user;
		}
		else
		{
			$user->load_template("user_profile.html");
			Core::$MAIN['TITLE'] = Core::$LANG['TITLE_PROFILE'];
			$user->errors = "";
			
			return $user;
		}
	}

	public static function modul_user_lost_password($modul_variable)
	{
		// Elfeljetett jelszó
		$user = new User();
		$user->lost_password();
		
		return $user;
	}

}
?>