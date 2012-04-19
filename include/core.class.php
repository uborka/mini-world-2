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

/**
 * A Core osztály minden egyéb az oldal megjelenését befolyásoló blokk alapja.
 *
 * Ez az osztály tartalmazza a sablonkezeléshez szükséges alapokat, illetve
 * egyéb hasznos funkciót.
 * @author Csuka Ádám
 */
class Core
{
	/**
	 * A sablonba beillesztendő modulok objektumai.
	 *
	 * @var asszociatív tömb
	 */
	public $inner_modules = array();
	/**
	 * A sablon kódját tartalmazó változó.
	 *
	 * @var szöveg
	 */
	public $template;
	/**
	 * Az objektum elemeinek tömbje.
	 *
	 * @var tömb
	 */
	public $items = NULL;
	/**
	 * Az objektum tulajdonságai.
	 *
	 * @var asszociatív tömb
	 */
	protected $props = array();
	// Statikus értékek, globális változók
	/**
	 * A rendszerben ismétlődő objektumok gyórsítótárja.
	 *
	 * A használatukra optimalizált osztályok a tényleges adatbázis művelet
	 * előtt ellenőrzik a gyórsítótár tartalmát, illetve ők másolják bele
	 * az objektumokat.
	 *
	 * @var asszociatív tömb
	 */
	public static $CACHE = array();
	/*
	 * Az oldalhoz tartozó CSS stíluslapok relatív útvonalukkal együtt.
	 *
	 * @var szöveg tömb
	 */
	public static $CSS = array();
	/**
	 * Az adminisztrációs modul konfigurációs tömbje.
	 *
	 * @var asszociatív tömb
	 */
	public static $CMS = array();
	/**
	 * A nyomkövetés során kiírandó értékek, szövegek.
	 *
	 * @var szöveg
	 */
	public static $DEBUG = "";
	/**
	 * A nyomkövetés szintjét befolyásoló változó.
	 *
	 * @var szám
	 */
	public static $DEBUG_LEVEL = 4;
	/**
	 * Az oldal fizikai elérési útvonala.
	 *
	 * @var szöveg
	 */
	public static $DOCROOT = "";
	public static $RELPATH = "";
	/**
	 * A futás közben fellépet hibák.
	 *
	 * @var szöveg
	 */
	public static $ERRORS = "";
	/**
	 * Az oldal HTTP címe.
	 *
	 * @var szöveg
	 */
	public static $HTTP_HOST = "";
	/**
	 * Az oldal HTTPS címe.
	 *
	 * @var szöveg
	 */
	public static $HTTPS_HOST = "";
	/**
	 * Az oldalon megjelenő szöveges konstansok tömbje.
	 *
	 * A szövegeket a sablonokba a {lang:AZONOSÍTIÓ} kóddal lehet elérni,
	 * valódi tárolásuk pedig a 'lang' táblában törtnik.
	 *
	 * @var asszociatív tömb
	 */
	public static $LANG = array();
	/**
	 * Az oldal globális főobjektumai: az aktuális oldal, kategória, hirdetés.
	 *
	 * @var asszociatív tömb
	 */
	public static $MAIN = array('THEME'=>NULL,'PAGE'=>NULL);
	/**
	 * A futó program üzemmódja.
	 *
	 * DEBUG – nyomkövető mód, RELEASE – éles változat.
	 *
	 * @var szöveg
	 */
	public static $MODE = "DEBUG";
	/**
	 * Az oldal működését befolyásoló beállítások.
	 *
	 * @var asszociatív tömb
	 */
  	public static $PARAM = array();
  	/**
  	 * Az oldalhoz csatolt bővítmények.
  	 *
  	 * @var asszociatív tömb
  	 */
  	public static $PLUGINS = array();
  	/*
  	 * Az oldalhoz betöltendő JavaScript állományok elérési útjai.
  	 */
  	public static $SCRIPTS = array();
  	/**
  	 * Az adatbázis táblák bevezető előtagja.
  	 *
  	 * @var szöveg
  	 */
	public static $TABLE_PREFIX = "";
	/**
	 * Az oldal futásakor rögzített futási idők naplója.
	 *
	 * @var szöveg
	 */
	public static $TIMES = "";

	public static $MEMCACHE = FALSE;
	/**
	 * A tulajdonságok elérét biztosító funkció.
	 *
	 * Lehetővé teszi a props tömbben tárolt értékek egyszerű elérését,
	 * és szükség esetén számolt mezők funkciókkal való leképezését.
	 */
	public function __get($property)
	{
		if (array_key_exists($property,$this->props))
			return $this->props[$property];
		else
      	{
      		// Ha nincs ilyen tulajdonság definiálva
      		// ellenőrizzük van-e hozzá tartozó eljárás
      		// Az eljárások névmetodikája: get_eljaras_neve
         	$method = 'get_' . strtolower($property);
         	if (method_exists($this, $method))
         		return $this->$method();
         	else
         	{
         		$debug = debug_backtrace();
		        $error = "Can access undefinied property ($property). ";
		        $error .= 'File: '.$debug[0]['file']." Line: ".$debug[0]['line'];
		        trigger_error($error, E_USER_NOTICE);
            }
         }
	}
	/**
	 * A tulajdosnágok értékadását írja felül.
	 *
	 * A nem definiált tulajdonságok automatikusan a props tömbbe kerülnek be.
	 */
	public function __set($name, $value)
	{
		$this->props[$name] = $value;
	}
	/**
	 * Ellenőrzi hogy a megadott tulajdonság be van-e állítva.
	 *
	 * @return logikai IGAZ ha van ilyen tuladjonság és az nem üres.
	 */
	public function have_property($name)
	{
		$return = array_key_exists($name,$this->props);
		if ($return)
			$return = !empty($this->props[$name]);
		return $return;
	}
	public function get_lang()
	{
		return array_key_exists("lang",$_SESSION) ? $_SESSION["lang"] : Core::$PARAM["LANG_DEFAULT"];
	}
	public function get_currency()
	{
		return array_key_exists("currency",$_SESSION) ? $_SESSION["currency"] : Core::$PARAM["CURRENCY_DEFAULT"];
	}
//	public function get_user()
//	{
//		return (!empty($_SESSION["user"]) ? unserialize($_SESSION["user"]) : new User());
//	}
	/**
	 * Az osztály alapértelmezett konstruktora.
	 *
	 * @param tömb $variables Ha az értéke nem NULL, akkor bemásolja azt a props tömbbe.
	 */
	public function __construct($variables = null)
	{
		$this->template = "";
		if (is_array($variables))
		{
			$this->props = $variables;
		}
	}
	/**
	 * Ez a függvény kerül meghívásra, ha a kód nem létező függvényt hívott meg.
	 */
	public function __call($funcname, $args)
	{
		Core::$ERRORS .= "Undefinied method $funcname called with vars: ";
		Core::$ERRORS .= var_export($args,TRUE) . "\n";
	}
	/**
	 * Dekódolja a kapott HTML szöveg speciális jeleit UTF-8 karakterekre.
	 *
	 * A dekódolás során a speciális HTML kódokkal helyettesített
	 * szövegekből UTF-8 kódolású szövegláncot készít.
	 * @param szöveg $text A dekódolandó szöveg.
	 * @return szöveg A dekódolt szöveg.
	 */
	public static function decode_html($text)
	{
//		$tb = microtime();

//		foreach (Core::get_prefunctions("core_decode_html") as $fnname)
//			$text = call_user_func($fnname, $text);

		$text = str_replace("&aacute;","á",$text);
		$text = str_replace("&Aacute;","Á",$text);
		$text = str_replace("&eacute;","é",$text);
		$text = str_replace("&Eacute;","É",$text);
		$text = str_replace("&iacute;","í",$text);
		$text = str_replace("&Iacute;","Í",$text);
		$text = str_replace("&oacute;","ó",$text);
		$text = str_replace("&Oacute;","Ó",$text);
		$text = str_replace("&ouml;","ö",$text);
		$text = str_replace("&Ouml;","Ö",$text);
		$text = str_replace("&#337;","ő",$text);
		$text = str_replace("&#336;","Ő",$text);
		$text = str_replace("&uacute;","ú",$text);
		$text = str_replace("&Uacute;","Ú",$text);
		$text = str_replace("&uuml;","ü",$text);
		$text = str_replace("&Uuml;","Ü",$text);
		$text = str_replace("&#369;","ű",$text);
		$text = str_replace("&#368;","Ű",$text);
		$text = str_replace("&rsquo;","'",$text);
		$text = str_replace("&nbsp;"," ",$text);
		$text = str_replace("<br>","\n",$text);
		$text = str_replace("<br/>","\n",$text);
		$text = str_replace("<br />","\n",$text);
		$text = str_replace("<BR>","\n",$text);
		$text = str_replace("<BR/>","\n",$text);
		$text = str_replace("<BR />","\n",$text);

//		foreach (Core::get_postfunctions("core_decode_html") as $fnname)
//			$text = call_user_func($fnname, $text);

//		$this->log("decode()",$tb);

		return $text;
	}
	/**
	 * A kapott szövegből eltávolítja az összes ékezetet és más speciális karaktert.
	 *
	 * Az eljárás permalinkek és mappanevek létrehozásához használható.
	 * @param szöveg $text Az eredeti szöveg.
	 * @return szöveg Az átalakított szöveg.
	 */
	public static function unaccuate($text)
	{
//		$tb = microtime();

//		foreach (Core::get_prefunctions("core_unaccuate") as $fnname)
//			$text = call_user_func($fnname, $text);

		$text = strtolower($text);

		$text = str_replace(array("&aacute;","á","&Aacute;","Á"),array("a","a","a","a"),$text);
		$text = str_replace(array("&eacute;","é","&Eacute;","É"),array("e","e","e","e"),$text);
		$text = str_replace(array("&iacute;","í","&Iacute;","Í"),array("i","i","i","i"),$text);
		$text = str_replace(array("&oacute;","ó","&Oacute;","Ó","&ouml;","ö","&Ouml;","Ö","&#337;","ő","&#336;","Ő"),array("o","o","o","o","o","o","o","o","o","o","o","o"),$text);
		$text = str_replace(array("&uacute;","ú","&Uacute;","Ú","&uuml;","ü","&Uuml;","Ü","&#369;","ű","&#368;","Ű"),array("u","u","u","u","u","u","u","u","u","u","u","u"),$text);
		$text = str_replace(array("\"","&rsquo;","'","<br>","\n","<br/>",",",".",":",";","?","!"),array("","","","","","","","","","","",""),$text);
		$text = str_replace(array("-"," ","_","+","/"),array("-","-","_","",""),$text);

//		foreach (Core::get_postfunctions("core_unaccuate") as $fnname)
//			$text = call_user_func($fnname, $text);

//		$this->log("uncaccuate()",$tb);

		return $text;
	}
	public static function unix_to_date($date,$format = null)
	{
		if (is_null($format))
			$format = Core::$LANG['DATE_FORMAT'];
		return strftime($format,$date);
	}
	public static function unix_to_datetime($date,$format = null)
	{
		if (is_null($format))
			$format = Core::$LANG['DATETIME_FORMAT'];
		return strftime($format,$date);
	}
	public static function date_to_unix($date)
	{
		return strtotime($date);
	}
	/**
	 * Az általános hibakezelést kiváltó függvény.
	 *
	 * A függvény a DEBUG_LEVEL szintjének megfelelő hibákat kiírja
	 * az ERRORS változóba. Ezzel párhuzamosan minden hibát - annak
	 * szintjétől függetlenül - kiír a 'log_errors' táblába.
	 * @link http://hu2.php.net/manual/en/function.set-error-handler.php Lásd a PHP dokumentációt.
	 */
	public static function error_handler($severity, $msg, $filename, $linenum)
	{
		$user = (!empty($_SESSION["user"]) ? unserialize($_SESSION["user"]) : new User());
		$user_id = ($user->have_property("id") ? $user->id : 0);
		$query = "INSERT INTO `".Core::$TABLE_PREFIX."log_error`
			(`date`,`severity`,`message`,`filename`,`linenum`,`date_create`,`user_create_id`)
			VALUES (NOW(),";
		switch ($severity)
		{
		case E_USER_ERROR:
			$query .= "'ERROR','".addslashes($msg)."','$filename',$linenum,UNIX_TIMESTAMP(),$user_id)";
			if (Core::$DEBUG_LEVEL > 0)
				Core::$ERRORS .= "ERROR: $msg in $filename on line $linenum: $msg.\n";
			break;
		case E_USER_WARNING:
			$query .= "'WARNING','$msg','$filename',$linenum,UNIX_TIMESTAMP(),$user_id)";
			if (Core::$DEBUG_LEVEL > 1)
				Core::$ERRORS .= "WARNING: $msg in $filename on line $linenum: $msg.\n";
			break;
		case E_USER_NOTICE:
			$query .= "'NOTICE','$msg','$filename',$linenum,UNIX_TIMESTAMP(),$user_id)";
			if (Core::$DEBUG_LEVEL > 2)
				Core::$ERRORS .= "NOTICE: $msg in $filename on line $linenum: $msg.\n";
			break;
		default:
			$query .= "'UNKNOWN','$msg','$filename',$linenum,UNIX_TIMESTAMP(),$user_id)";
			if (Core::$DEBUG_LEVEL > 3)
				Core::$ERRORS .= "UNKNOWN problem in $filename on line $linenum: $msg.\n";
			break;
		}
		if (Core::$MODE == "RELEASE")
			Sql::query($query);
	}
	/**
	 * Nyomkövetés céljából a TIMES változóba naplózza az aktuális bejegyzést.
	 *
	 * @param szöveg $method A naplózandó funkció neve.
	 * @param vegyes $time_before A naplózandó funkció indítási időpontja a microtime() alapján.
	 */
	public function log($method,$time_before = NULL)
	{
		if ($time_before != NULL)
		{
			if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
			{
				$tb = array_sum(explode(' ', $time_before));
				$time_after = array_sum(explode(' ', microtime()));
				$total_execution_time = $time_after - $tb;
				Core::$TIMES .= sprintf("%-42s",get_class($this).".".$method.":")
					."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
			}
		}
	}
	public static function log_modul($method,$time_before = NULL)
	{
		if ($time_before != NULL)
		{
			if ((Core::$DEBUG_LEVEL > 3)&&(Core::$MODE == "DEBUG"))
			{
				$tb = array_sum(explode(' ', $time_before));
				$time_after = array_sum(explode(' ', microtime()));
				$total_execution_time = $time_after - $tb;
				Core::$TIMES .= sprintf("%-42s","modul:".$method.":")
					."\t".sprintf("%01.4f mp",$total_execution_time)."\n";
			}
		}
	}
	/**
	 * A megadott változót átmásolja a GET/POST bementről a munkamenetbe.
	 *
	 * @param szöveg $varname A kimásolandó változó neve.
	 * @param szöveg $session_name Opcionálisan a munkamenetben ez lesz a neve. Ha értéke NULL, akkor azonos az eredetivel.
	 */
	public function get_variant($varname, $session_name = NULL)
	{
		if (is_null($session_name))
			$variable_name = $varname;
		else
			$variable_name = $session_name;
		if (array_key_exists($varname,$_GET))
		{
			if (is_array($_GET[$varname]))
			{
				foreach ($_GET[$varname] as $key => $value)
				{
					$_SESSION[$variable_name][$key] = $_GET[$varname][$key];
				}
			}
			else
				$_SESSION[$variable_name] = $_GET[$varname];
		}
		elseif (array_key_exists($varname,$_POST))
			$_SESSION[$variable_name] = $_POST[$varname];
		else
			unset($_SESSION[$variable_name]);
		if (array_key_exists($variable_name,$_SESSION))
		{
			$this->props[$variable_name] = $_SESSION[$variable_name];
		}
	}
	/**
	 * A kapott feltétel alapján beállítja a letiltást jelző tulajdonságokat.
	 *
	 * Ha a feltétel igaz, akkor a class_disabled és option_disabled
	 * tulajdonságok olyan értéket kapnak, amik alapján a sablonban egy-egy
	 * elem másképp viselkedhet, jelenhet meg. Ellenben ezek üres szövegek.
	 * @param logikai $condition A feltétel.
	 */
	public function set_disabled($condition = false)
	{
		$this->call_prefunctions("core_set_disabled", $this, array("condition"=>$condition));

		// Ha a feltétel igaz, akkor "letiltja" a modult
		if ($condition)
		{
			$this->is_disabled = TRUE;
			$this->class_disabled = "disabled";
			$this->option_disabled = "disabled=\"disabled\"";
		}
		else
		{
			$this->is_disabled = FALSE;
			$this->class_disabled = "";
			$this->option_disabled = "";
		}

		$this->call_postfunctions("core_set_disabled", $this, array("condition"=>$condition));
	}
	/**
	 * A kapott feltétel alapján beállítja a kiválasztottságot jelző tulajdonságokat.
	 *
	 * Ha a feltétel igaz, akkor a class_selected, option_selected és
	 * checked_selected tulajdonságok olyan értéket kapnak, amik alapján a
	 * sablonban egy-egy elem másképp viselkedhet, jelenhet meg. Ellenben ezek
	 * üres szövegek.
	 * @param logikai $condition A feltétel.
	 */
	public function set_selected($condition = false)
	{
		$this->call_prefunctions("core_set_selected",$this,array("condition"=>$condition));

		// A kapott logikai érték alapján beállítja a selected/checked tagokat
		if ($condition)
		{
			$this->is_selected = TRUE;
			$this->class_selected = "selected";
			$this->option_selected = "selected=\"selected\"";
			$this->checked_selected = "checked=\"checked\"";
		}
		else
		{
			$this->is_selected = FALSE;
			$this->class_selected = "";
			$this->option_selected = "";
			$this->checked_selected = "";
		}

		$this->call_postfunctions("core_set_selected",$this,array("condition"=>$condition));
	}
	/**
	 * A megadott üzenetet kiküldi e-mailben a PHPMailer osztállyal.
	 *
	 * Minden kiküldött e-mail üzenetet egyben naplóz is a 'log_emails'
	 * táblában.
	 *
	 * @param szöveg $message Az e-mail üzenet, HTML formátumban.
	 * @param szöveg $from A feladó e-mail címe.
	 * @param szöveg $from_name A feladó neve.
	 * @param szöveg $to A címzett e-mail címe.
	 * @param szöveg $to_name A címzett neve.
	 * @param szöveg $subject Az e-mail üzenet tárgya.
	 * @param szöveg $to_admin Opcionálisan annak a mezőnek a neve, aminek a beállítását ellenőrzi a felhasználói táblában.
	 */
	public function email_to($message,
							 $from = null,
							 $from_name = "",
							 $to = null,
							 $to_name = "",
							 $subject = "",
							 $to_admin = null)
	{
		$tb = microtime();

		$mail_id = 0;

		// Az e-mail-ek postázása
		$mail = new PHPMailer();
		$mail->SetLanguage("hu", $_SERVER['DOCUMENT_ROOT']."/mods/phpmailer/language/");
		$mail->CharSet = "utf-8";
		$mail->IsHTML(true);

		// A feladó megállapítása
		$mail->From = is_null($from) ? Core::$PARAM["DEFAULT_EMAIL_ADDRESS"] : $from;
		$mail->FromName = is_null($from) ? Core::$PARAM["DEFAULT_EMAIL_NAME"] : $from_name;

		// A címzett megállapítása
		$to_addresses = array();
		if (is_null($to_admin))
		{
			$to_addresses[0]["address"] = is_null($to) ? "" : $to;
			$to_addresses[0]["name"] = is_null($to) ? "" : $to_name;
		}
		else
		{
			$query = "SELECT * FROM `".Core::$TABLE_PREFIX."core_user` WHERE `$to_admin`=1";
			$result = Sql::query($query);
			$index = 0;
			while ($o = Sql::fetch_array($result))
			{
				$to_addresses[$index]["address"] = $o["email"];
				$to_addresses[$index]["name"] = $o["fullname"];
				$index++;
			}
		}

		for ($i = 0; $i < count($to_addresses); $i++)
		{
			$body = stripslashes($message->generate());
			$html_body = eregi_replace("[\]",'',$body);
			$text_body = strip_tags(Core::decode_html($body));

			$mail->ClearAddresses();
			$mail->AddAddress($to_addresses[$i]["address"],$to_addresses[$i]["name"]);
			$mail->Subject = $subject;
			$mail->AltBody = "Az eredeti üzenet megtekintéséhez kérem használjon\nHTML kompatibilis levelező programot!\n\n".$text_body;

			$mail->MsgHTML($body);

			if (!$mail->Send())
			{
				$query = "INSERT INTO `".Core::$TABLE_PREFIX."core_log_email` " .
					"(`date`,`to`,`subject`,`message`,`status`,`comments`) ".
					"VALUES (UNIX_TIMESTAMP(),'".$to_addresses[$i]["address"]."','".$mail->Subject."','".$html_body."','ERROR','".$mail->ErrorInfo."')";
				Sql::query($query);
				$mail_id = Sql::insert_id();
			}
			else
			{
				$query = "INSERT INTO `".Core::$TABLE_PREFIX."core_log_email` " .
					"(`date`,`to`,`subject`,`message`,`status`) ".
					"VALUES (UNIX_TIMESTAMP(),'".$to_addresses[$i]["address"]."','".$mail->Subject."','".$html_body."','SENT')";
				Sql::query($query);
				$mail_id = Sql::insert_id();
			}
		}

		$this->log("email_to()",$tb);
		return $mail_id;
	}
	/**
	 * A megadott nevű állományt betölti az objektum template tulajdonságába.
	 *
	 * A betötlendő sablonokat az aktuálisan beállított séma (THEME) mappájában
	 * keresi, vagy ha az nincs megadva, akkor a munkamenet template_path által
	 * megadott mappában. Ha a program éles módban fut, akkor megkisérli az
	 * állománykat az APC gyórsítóban tárolni és visszatölteni.
	 * @param szöveg $filename A betöltendő sablonfájl neve.
	 */
	public function load_template($filename)
	{
		$tb = microtime();

		$return = "";

		// TODO Memcache használata
//		if (function_exists("apc_store") && (Core::$MODE == "RELEASE"))
//		{
//			$apc_key = Core::$HTTP_HOST."::".__FUNCTION__."::".md5(var_export(func_get_args(),true));
//			if (!($return = apc_fetch($apc_key)))
//			{
//				$path = Core::$DOCROOT;
//				if (Core::$MAIN['THEME'] !== null)
//					$path .= Core::$RELPATH.Core::$MAIN['THEME']->template_path;
//				else
//					$path .= $_SESSION["template_path"];
//				$return = strlen(trim($path)) > 0
//					? file_get_contents($path."/".$filename)
//					: file_get_contents(Core::$RELPATH."/templates/default/".$filename);
//				if (!empty($return))
//					apc_store($apc_key,$return,86400);
//			}
//		}
//		else
		{
			$path = Core::$DOCROOT;
			if (!file_exists($path."/".$filename))
			{
				if (is_object(Core::$MAIN['THEME']))
					$path .= Core::$RELPATH.Core::$MAIN['THEME']->template_path;
				else
					$path .= empty($_SESSION["template_path"]) ? "" : $_SESSION["template_path"];
			}
			if (file_exists($path."/".$filename))
				$return = strlen(trim($path)) > 0
					? file_get_contents($path."/".$filename)
					: file_get_contents(Core::$RELPATH."/templates/default/".$filename);
			elseif (Core::$DEBUG_LEVEL > 2)
				Core::$ERRORS .= "Template not found: ".$path."/".$filename."\n";
		}

		$this->template = $return;

		$this->log("load_template()",$tb);
	}
	/**
	 * Feldolgozza a sablont, és az abba ágyazott modulok objektumait létrehozza.
	 */
	public function include_moduls()
	{
		$tb = microtime();

		$num = preg_match_all("/[{]{1}((modul:)([_a-zA-Z0-9]*)((:)([_a-zA-Z0-9]*)){0,1})[}]{1}/", $this->template, $vars);

		for ($i = 0; $i < count($vars[3]); $i++)
		{
			$modul_name = $vars[3][$i];
			$variable_name = $modul_name . (!empty($vars[6][$i]) ? ":".$vars[6][$i] : "");

			if (array_key_exists($modul_name,Core::$PLUGINS['moduls']))
			{
				if (Core::$MODE == "DEBUG")
					Core::$DEBUG .= sprintf("%-42s","INCLUDE MODUL: ".$variable_name)
					." CALL: ".Core::$PLUGINS['moduls'][$modul_name]."\n";
				$this->inner_modules[$variable_name] = call_user_func(Core::$PLUGINS['moduls'][$modul_name],$variable_name);
				$this->log("inner_moduls($variable_name)",$tb);
			}
			else
			{
				if (Core::$MODE == "DEBUG")
					Core::$DEBUG .= sprintf("%-42s","INCLUDE MODUL: ".$variable_name)
					." MISSED CALL ($modul_name).\n";
			}
		}

		$this->log("include_moduls()",$tb);
	}
	/**
	 * A sablon {content} kódját cseréli le a vezérlőparaméter alapján egy modul kódjára.
	 */
	private function set_content()
	{
		$tb = microtime();

		$content = "";
		foreach (Core::get_prefunctions("core_set_content") as $fnname)
			$content = call_user_func($fnname, $content);

		// A parancsváltozó alapján a {content} kódot lecseréli a sablonban
		// a megfelelő modul kódjára
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;

		if (empty($content))
		{
			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "user_profile":
				case "user_regist":
				case "user_lost_password":
					$content = "{modul:$action}";
					break;
				// Üzenetetküldő modulok
				case "logon":
					$content = "{modul:$action}" . $content;
				// Alapértelmezett modulok
				default:
					// Ha az a értéke null, vagy nem kezelt
					if (Core::$MAIN['PAGE'] != NULL)
						$content .= "{modul:page}";
					break;
			}
		}

		foreach (Core::get_postfunctions("core_set_content") as $fnname)
			$content = call_user_func($fnname, $content);

//		$this->debug($content);

		$this->template = preg_replace("({content})", $content, $this->template);

		$this->log("set_content()",$tb);
	}
	/**
	 * Feldolgozza a sablont és megjeleníte a létrehozott HTML kódot.
	 */
	public function show()
	{
		$tb = microtime();

		$this->set_content();

		$result = $this->generate();

		// A nyomkövetési infók megjelentse
	    $time_after = array_sum(explode(' ', microtime()));
	    $time_before = array_sum(explode(' ', $this->time_before));
	    $total_execution_time = $time_after - $time_before;

	    $this->log("show()",$tb);

	    $this->debug_info = "";
	    if ( Core::$MODE == "DEBUG" )
	    {
			if (Core::$DEBUG != "")
	        	$this->debug_info .= sprintf("<fieldset><legend>NYOMKÖVETÉS</legend><pre>%s</pre></fieldset>", htmlspecialchars(Core::$DEBUG));
			if (Core::$ERRORS != "")
	        	$this->debug_info .= sprintf("<fieldset><legend>HIBÁK</legend><pre>%s</pre></fieldset>", Core::$ERRORS);
			$this->debug_info .= sprintf("<fieldset><legend>MUNKAMENET VÁLTOZÓ</legend><pre>%s</pre></fieldset>",
	      		htmlspecialchars(var_export($_SESSION,TRUE)
//				. "\n\nUSER: " . var_export(unserialize($_SESSION["user"]),TRUE)
				));
			if (Sql::$QUERIES != "")
				$this->debug_info .= sprintf("<fieldset><legend>KIKÜLDÖTT SQL KÉRÉSEK</legend><pre>%s</pre></fieldset>", Sql::$QUERIES);
			$this->debug_info .= sprintf("<fieldset><legend>FUTÁSI IDŐK</legend><pre>SQL: %s\nOldal: %s\n%s</pre></fieldset>",
	        	sprintf("%01.4f mp",Sql::$TOTAL_QUERY_TIME),
	        	sprintf("%01.4f mp",$total_execution_time),
	        	Core::$TIMES);
	    }
		$result = preg_replace("({debug_info})", $this->debug_info, $result);
		// A kimeneti HTML kódból a kommentelt részek törlése, és a kód tömörítése
		if (Core::$MODE == "RELEASE")
		{
			$result = preg_replace('/<!--\s(.|\s)*?-->/', '', $result);
			$result = preg_replace('/\s\s+/', ' ', $result);
		}

		foreach (Core::get_prefunctions("core_show") as $fnname)
			$result = call_user_func($fnname, $result);

		echo $result;

		foreach (Core::get_postfunctions("core_show") as $fnname)
			call_user_func($fnname);
	}
	/**
	 * Feldolgozza a sablont és létrehozza a HTML kódját.
	 *
	 * @return szöveg A létrehozott HTML kód.
	 */
	public function generate()
	{
		$tb = microtime();

		// Listaelemek esetén az elemek tartalmának generálása
		if (is_array($this->items))
		{
			if (count($this->items) > 0)
			{
				$items = "";
				foreach ($this->items as $item)
				{
					if (is_object($item))
						$items .= $item->generate();
				}
				$this->props["items"] = $items;
			}
			else
				$this->props["items"] = "";
		}
		elseif (!is_null($this->items))
			$this->props["items"] = $this->items;

		// Ha a modulnak van description mezője, akkor annak tartalmát
		// előre kicseréljük, így az abba ágyazott modulok is beágyazódnak
		if (array_key_exists("description",$this->props))
		{
			$this->template = preg_replace("/[{]this>description[}]/", $this->props["description"], $this->template);
//			$this->debug($this->template);
		}

		// A sablon kódjának beállítása, betöltése a kimeneti változóba
		$result = $this->template;

		// A kicserélendő értékek kigyűjtése a VARS tömbbe
		$num = preg_match_all("/[{]{1}(([_a-zA-Z0-9]*)(:|>)([_a-zA-Z0-9]*)((:)([_a-zA-Z0-9]*)){0,1})[}]{1}/", $this->template, $vars);

		// A beillesztett modulok előkészítése
		$this->include_moduls();

		// A beágyazott modulok átalakítása HTML kóddá
		$keys = array_keys($this->inner_modules);
		if (count($keys) > 0)
		{
			foreach ($keys as $key)
			{
				if (is_object($this->inner_modules[$key]))
				{
					$this->props[$key] = $this->inner_modules[$key]->generate();
				}
			}
		}

		foreach ($vars[1] as $var)
		{
			// A legenerált modulok beágyazása
			if ( strpos($var,"modul:") !== FALSE )
			{
				$v = explode(":",$var);
				$varname = $v[1] . (array_key_exists(2,$v) ? ":".$v[2] : "");
				if (array_key_exists($varname,$this->props))
					$result = preg_replace("/[{]".$var."[}]/", $this->props[$varname], $result);
				else
					$result = preg_replace("/[{]".$var."[}]/", "", $result);
			}
			// A sablonban található címkék beillesztése
			if ( strpos($var,"lang:") !== FALSE )
			{
				$v = explode(":",$var);
				if (array_key_exists($v[1],Core::$LANG))
					$result = preg_replace("/[{]".$var."[}]/", Core::$LANG[$v[1]], $result);
				else
					$result = preg_replace("/[{]".$var."[}]/", $var, $result);
			}
			// A sablonban található paraméterek beillesztése
			if ( strpos($var,"param:") !== FALSE )
			{
				$v = explode(":",$var);
				if (array_key_exists($v[1],Core::$PARAM))
					$result = preg_replace("/[{]".$var."[}]/", Core::$PARAM[$v[1]], $result);
				else
					$result = preg_replace("/[{]".$var."[}]/", "", $result);
			}
			// A program globális változóiban beillesztése a sablon megfelelő helyeire
			if ( strpos($var,"session:") !== FALSE )
			{
				$v = explode(":",$var);
				if (array_key_exists($v[1],$_SESSION))
					$result = preg_replace("/[{]".$var."[}]/", $_SESSION[$v[1]], $result);
				else
					$result = preg_replace("/[{]".$var."[}]/", "", $result);
			}
			// A modul saját mezőinek beillesztése a megadott helyekre
			if ( strpos($var,"this>") !== FALSE )
			{
				$v = explode(">",$var);
				if (array_key_exists($v[1],$this->props))
					$result = preg_replace("/[{]".$var."[}]/", is_object($this->props[$v[1]]) ? $this->props[$v[1]]->generate() : $this->props[$v[1]], $result);
				elseif ((Core::$DEBUG_LEVEL > 2)&&(Core::$MODE == "DEBUG"))
						$result = preg_replace("/[{]".$var."[}]/", $var, $result);
					else
						$result = preg_replace("/[{]".$var."[}]/", "", $result);
			}
			// Minden egyéb kód törlése
			$result = preg_replace("/[{]".$var."[}]/", "", $result);
		}

		$this->log("generate()",$tb);

		return $result;
	}
	/**
	 * Az osztály inicializáló eljárása. Alapesetben üres funkció.
	 */
//	public function init() {}

	public function create_message($description, $class = "", $id = NULL)
	{
		$tb = microtime();

		$msg = new Core();

		$msg->load_template("message.html");
		$msg->id = is_null($id) ? time() : $id;
		$msg->class = $class;
		$msg->description = $description;

		$this->log("create_message()",$tb);

		return $msg;
	}

	public function change_url($params,$original_url = NULL)
	{
		$names = array_keys($params);
		$founds = array();

		$original_url = html_entity_decode(urldecode($original_url));
		$request_url = html_entity_decode(urldecode($_SERVER["REQUEST_URI"]));
	    $url = empty($original_url) ? explode("?",$request_url) : explode("?",$original_url);
		//kiveszem az "&" karaktereket,és beteszem az $and_less_elements tömbbe
		if (count($url) > 1)
			$and_less_elements = explode("&",$url[1]);
		else
		{
			$and_less_elements = array();
			$url[0] = $original_url == "" ? $request_url : $original_url;
		}
		foreach ($and_less_elements as $elem)
		{
			$equal_less_elements = explode("=",$elem);
			// Ha benn van a megváltoztatandó elemek között
			if (in_array($equal_less_elements[0],$names))
			{
				// akkor megváltoztatjuk az értékét
				$equal_less_elements[1] = $params[$equal_less_elements[0]];
				$founds[] = $equal_less_elements[0];
			}
			// Ha a változó értéke nem NULL, akkor visszarakjuk az új URL-be
			if (!empty($equal_less_elements[1]))
				$new_and_less_elements[] = $equal_less_elements[0]."=".$equal_less_elements[1];
		}
		// A meg nem talált kulcsokat végül hozzáadjuk
		foreach ($names as $name)
		{
			if (!in_array($name,$founds) && !is_null($params[$name]))
				$new_and_less_elements[] = $name."=".$params[$name];
		}

		$target_url = implode("&amp;",$new_and_less_elements);
		
		return Core::$HTTP_HOST . $url[0]."?".$target_url;
	}

	public function format_price($price, $currency = NULL)
	{
		if (is_null($currency))
			$currency = array_key_exists("currency",$_SESSION) ? $_SESSION["currency"] : Core::$PARAM["CURRENCY_DEFAULT"];
		return sprintf(Core::$LANG["PRICE_FORMAT_".strtoupper($currency)],
			number_format($price,Core::$LANG["PRICE_DECIMAL_PLACES_".strtoupper($currency)],
				Core::$LANG["PRICE_DECIMAL_SEPARATOR_".strtoupper($currency)],
				Core::$LANG["PRICE_THOUSAND_SEPARATOR_".strtoupper($currency)]));
	}

	public static function check_plugin($plugin_name)
	{
		$return = FALSE;

		$path = Core::$DOCROOT.Core::$RELPATH."/plugins/".$plugin_name;
		$return = in_array($path,Core::$PLUGINS['autoload']);

		return $return;
	}
	public static function insert_modul($modul_name, $class_name, $function_name)
	{
		$name = "";
		if (strlen(trim($class_name)) > 0)
			$name = trim($class_name)."::";
		$name .= trim($function_name);
		Core::$PLUGINS["moduls"][$modul_name] = $name;
	}
	public static function insert_prefunction($method_name, $class_name, $function_name)
	{
		$name = "";
		if (strlen(trim($class_name)) > 0)
			$name = trim($class_name)."::";
		$name .= trim($function_name);
		if (!array_key_exists($method_name."_pre",Core::$PLUGINS))
			Core::$PLUGINS[$method_name."_pre"] = array();
		Core::$PLUGINS[$method_name."_pre"][] = $name;
	}
	public static function insert_postfunction($method_name, $class_name, $function_name)
	{
		$name = "";
		if (strlen(trim($class_name)) > 0)
			$name = trim($class_name)."::";
		$name .= trim($function_name);
		if (!array_key_exists($method_name."_post",Core::$PLUGINS))
			Core::$PLUGINS[$method_name."_post"] = array();
		Core::$PLUGINS[$method_name."_post"][] = $name;
	}
	public static function insert_css($css)
	{
		if (!in_array($css,Core::$CSS))
			Core::$CSS[] = $css;
	}
	public static function insert_script($script)
	{
		$not_found = TRUE;
		$basename = basename($script);
		foreach (Core::$SCRIPTS as $scr)
		{
			$not_found &= (basename($scr) != $basename);
		}
		if ($not_found)
			Core::$SCRIPTS[] = $script;
	}
	public function call_prefunctions($method_name)
	{
		if (array_key_exists($method_name."_pre",Core::$PLUGINS))
		{
			foreach (Core::$PLUGINS[$method_name."_pre"] as $mn)
			{
				call_user_func($mn);
			}
		}
	}
	public function call_postfunctions($method_name)
	{
		if (array_key_exists($method_name."_post",Core::$PLUGINS))
		{
			foreach (Core::$PLUGINS[$method_name."_post"] as $mn)
			{
				call_user_func($mn);
			}
		}
	}
	public function get_prefunctions($method_name)
	{
		$return = array();
		if (array_key_exists($method_name."_pre",Core::$PLUGINS))
		{
			$return = Core::$PLUGINS[$method_name."_pre"];
		}
		return $return;
	}
	public function get_postfunctions($method_name)
	{
		$return = array();
		if (array_key_exists($method_name."_post",Core::$PLUGINS))
		{
			$return = Core::$PLUGINS[$method_name."_post"];
		}
		return $return;
	}
	public static function debug($var, $text = "")
	{
		$text = trim($text);
		if (!empty($text))
			$text .= " ";
		switch ($type = gettype($var))
		{
			case "boolean":
				Core::$DEBUG .= "$text(boolean) " . ($var ? "TRUE\n" : "FALSE\n");
				break;
			case "integer":
				Core::$DEBUG .= "$text(integer) " . $var . "\n";
				break;
			case "double":
				Core::$DEBUG .= "$text(double) " . $var . "\n";
				break;
			case "string":
				Core::$DEBUG .= "$text(string) " . $var . "\n";
				break;
			case "array":
			case "object":
			case "resource":
			case "unknown type":
				Core::$DEBUG .= "$text($type) " . var_export($var,TRUE) . "\n";
				break;
			case "NULL":
				Core::$DEBUG .= "$text(NULL)\n";
				break;
		}
	}
}
?>