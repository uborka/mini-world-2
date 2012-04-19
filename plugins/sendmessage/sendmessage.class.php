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

class SendMessage extends Core
{
	public static function after_core_set_content($content)
	{
		$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;
		
		switch ($action)
		{
			// A tartalmat felcserélő modulok
			case "sm_sendmessage":
				$content .= "{modul:sm_sendmessage}";
				break;
		}
		
		return $content;
	}
	
	public static function after_site_load_mains()
	{
		if (empty($_SESSION["subject"]))
			if (Core::$MAIN['TITLE'] != "")
				$_SESSION["subject"] = Core::$MAIN['TITLE'];
	}
	
	public static function modul_sm_form()
	{
		$box = new Core();
		$box->load_template("sm_form.html");
		
		// Az egyes mezők alapértelmezett értékének beállítása
		$box->name = empty($_SESSION["name"]) ? "" : trim($_SESSION["name"]);
		$box->email = empty($_SESSION["email"]) ? "" : trim($_SESSION["email"]);
		$box->phone = empty($_SESSION["phone"]) ? "" : trim($_SESSION["phone"]);
		$box->subject = empty($_SESSION["subject"]) ? "" : trim($_SESSION["subject"]);
		$box->notes = empty($_SESSION["notes"]) ? "" : trim($_SESSION["notes"]);
		
		return $box;
	}
	
	public static function modul_sm_sendmessage()
	{
		$box = new Core();
		$box->load_template("email_from_sendmessage.html");
		
		$msg = $box->create_message("");
			
		$box->name = empty($_SESSION["name"]) ? "" : trim($_SESSION["name"]);
		$box->email = empty($_SESSION["email"]) ? "" : trim($_SESSION["email"]);
		$box->phone = empty($_SESSION["phone"]) ? "" : trim($_SESSION["phone"]);
		$box->subject = empty($_SESSION["subject"]) ? "" : trim($_SESSION["subject"]);
		$box->notes = empty($_SESSION["notes"]) ? "" : trim($_SESSION["notes"]);
		
		// Ellenőrzések
		if ((strlen($box->name) > 4) && (strpos($box->name," ")>0))
		{
			if (eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $box->email))
			{
				if (eregi("[0-9 ()+-/]{".strlen($box->phone)."}",$box->phone))
				{
					// E-mail kiküldése
					$box->email_to($box,
						$box->email,$box->name,
						NULL,NULL,
						Core::$LANG["TITLE_SITE"].": ".$box->subject,"option_email_from_sendmessage");
					// Adatok törlése
					$_SESSION["name"] = "";
					$_SESSION["email"] = "";
					$_SESSION["phone"] = "";
					$_SESSION["notes"] = "";
					// Visszajelzés
					$msg = $box->create_message("Adatait sikeresen továbbítottuk. Munkatársaink hamarosan felveszik önnel a kapcsolatot.","ok");
					
				}
				else
					$msg = $box->create_message("A telefonszám nem értelmezhető!","error");
			}
			else
				$msg = $box->create_message("A megadott e-mail cím nem értelmezhető!","error");
		}
		else
			$msg = $box->create_message("Az Ön neve hiányos, vagy hibásan adta meg. Kérem a teljes nevét adja meg!","error");
			
		return $msg;
	}
}
?>