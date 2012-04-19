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

class Sql
{
	private static $con = NULL;
	public static $HOST;
	public static $DATABASE;
	public static $USERNAME;
	public static $PASSWORD;
	public static $MESSAGE_NOT_CONNECT = "Nem sikerült kapcsolódni az adatbázis szerverhez.";
	public static $MESSAGE_NO_DATABASE = "Az adatbázis sérült vagy nem létezik.";
	public static $QUERIES = "";
	public static $TOTAL_QUERY_TIME = 0;

	public function __construct()
	{
		Sql::$con = NULL;
	}

	private static function error($message)
	{
		echo $message;
		exit;
	}

	public static function connected()
	{
		return (!is_null(ql::$con));
	}

	public static function connect()
	{
		try
		{
			$dsn = "mysql:dbname=".Sql::$DATABASE.";host=".Sql::$HOST;
			Sql::$con = new PDO($dsn,Sql::$USERNAME,Sql::$PASSWORD);
			Sql::$QUERIES .= "PDO connected.\n";
			
//			Sql::$con = mysql_connect(Sql::$HOST,Sql::$USERNAME,Sql::$PASSWORD) or Sql::error(Sql::$MESSAGE_NOT_CONNECT);
//			Sql::$QUERIES .= "Connected.\n";
			
			Sql::query("SET SESSION `character_set_client` = 'utf8'");
			Sql::query("SET SESSION `character_set_connection` = 'utf8'");
			Sql::query("SET SESSION `character_set_database` = 'utf8'");
			Sql::query("SET SESSION `character_set_filesystem` = 'utf8'");
			Sql::query("SET SESSION `character_set_results` = 'utf8'");
			Sql::query("SET SESSION `character_set_server` = 'utf8'");
			Sql::query("SET SESSION `collation_connection` = 'utf8_hungarian_ci'");
			Sql::query("SET SESSION `collation_database` = 'utf8_hungarian_ci'");
			Sql::query("SET SESSION `collation_server` = 'utf8_hungarian_ci'");
			Sql::query("SET SESSION `group_concat_max_len` = 512");
			
//			mysql_select_db(Sql::$DATABASE,Sql::$con) or Sql::error(Sql::$MESSAGE_NO_DATABASE);
			
			return true;
		}
		catch (PDOException $exc)
		{
			echo "<pre>".var_export($exc,TRUE)."<pre>";
		}
		catch (Exception $exc)
		{
			echo $exc;
		}
	}

	public static function query($query)
	{
		$return = NULL;
		
		if (!Sql::$con)
			Sql::connect();
		
		if (Sql::$con)
		{
			if (Core::$MODE == "DEBUG")
				if (strpos($query,"log_error")>0)
					Sql::$QUERIES .= "<span style='color:red;'>".$query."</span>";
				else
					Sql::$QUERIES .= $query;
	
			$time_before = array_sum(explode(' ', microtime()));
	
	//		$result = mysql_query($query,Sql::$con);
			$result = Sql::$con->prepare($query);
			$success = $result->execute();
	
			$time_after = array_sum(explode(' ', microtime()));
	
			if (Core::$MODE == "DEBUG")
			{
				$querytime = $time_after - $time_before;
				Sql::$TOTAL_QUERY_TIME += $querytime;
				if (in_array(substr($query,0,4),array("SELE","SHOW","DESC","EXPL")))
					$row_nums = Sql::num_rows($result);
				else
					$row_nums = Sql::affected_rows($result);
				if (!$row_nums)
					$row_nums = "0";
				$time = sprintf("%01.4f mp",$querytime);
				$total = sprintf("%01.4f mp",Sql::$TOTAL_QUERY_TIME);
				Sql::$QUERIES .= " ($row_nums sor: $time/$total)\n";
			}
	//		if (mysql_error(Sql::$con))
			if (!$success)
				Sql::echo_sql_error($query);
		}
		
		return $result;
	}

	public static function affected_rows($result = NULL)
	{
//		if (isset($result) && !is_null($result))
//			return mysql_affected_rows();
//		else
//			return 0;
		if (!is_null($result))
			return $result->rowCount();
		else
			return 0;
	}

	public static function num_rows($result = NULL)
	{
//		if (isset($result) && !is_null($result))
//			return mysql_num_rows($result);
//		else
//			return 0;
		if (!is_null($result))
			return $result->rowCount();
		else
			return 0;
	}

	public static function calc_found_rows()
	{
		$query = "SELECT FOUND_ROWS() AS row_nums";
		$result = Sql::query($query);
		if ($result)
			return Sql::fetch_object($result)->row_nums;
		else
			return 0;
	}

	public static function fetch_object($result)
	{
//		return mysql_fetch_object($result);
		if (!is_null($result))
			return $result->fetch(PDO::FETCH_OBJ);
		else
			return NULL;
	}

	public static function fetch_array($result)
	{
//		if (isset($result) && !is_null($result))
//			return mysql_fetch_array($result);
//		else
//			return array();
		if (!is_null($result))
			return $result->fetch(PDO::FETCH_ASSOC);
		else
			return array();
	}
	
	public static function fetch_arrayset($result)
	{
		if (!is_null($result))
			return $result->fetchAll(PDO::FETCH_ASSOC);
		else
			return array();
	}

	public static function free_result($result)
	{
//		mysql_free_result($result);
		$result->closeCursor();
		unset($result);
	}

	public static function sql_error()
	{
		if (!Sql::$con)
			Sql::connect();
//		return mysql_error(Sql::$con);
		$error = Sql::$con->errorInfo();
		if ($error[0] > 0)
			return "[".$error[0]."] ".$error[2];
		else
			return "";
	}

	public static function insert_id()
	{
//		return mysql_insert_id();
		if (Sql::$con)
			return Sql::$con->lastInsertId();
	}

	public static function disconnect()
	{
//		mysql_close(Sql::$con);
		if (Sql::$con)
			Sql::$con = NULL;
	}

	public static function echo_sql_error($query = false)
	{
		$error = Sql::$con->errorInfo();
//		if (mysql_error())
//			Core::$ERRORS .= "[SQL Error]: ".mysql_error()."\n";
		if ($error[0] > 0)
			Core::$ERRORS .= "[SQL Error]: ".$error[2]."\n";
		if (($query)&&($error[0] > 0))
			Core::$ERRORS .= "[SQL Query]: ".$query."\n";
	}

	public static function get_database_size()
	{
		$space_sql = 0;
		$query = "SHOW TABLE STATUS FROM ".Sql::$DATABASE;
		$result = Sql::query($query);
		if (Sql::num_rows($result))
		{
			while ($o = Sql::fetch_object($result))
			{
				$space_sql += $o->Data_length;
				$space_sql += $o->Data_free;
				$space_sql += $o->Index_length;
			}
		}

		return $space_sql;
	}
}
?>