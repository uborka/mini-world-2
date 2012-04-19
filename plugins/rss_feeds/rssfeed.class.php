<?php
/*
 * PTI WPM - Web Page Motor 3.0
 * Copyright (c) 2010; PTI Kft.
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

class RssFeed extends Core
{
	static $CHANNELTAGS	= array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	static $ITEMTAGS = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
	static $IMAGETAGS = array('title', 'url', 'link', 'width', 'height');
	static $TEXTINPUTTAGS = array('title', 'description', 'name', 'link');

	public static function get_rss_comb($data)
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
			if (! ($rss = Core::$MEMCACHE->get(Core::$HTTP_HOST.":rss_feed_$id",MEMCACHE_COMPRESSED)))
			{
				$rss = new RssFeed($data);
				Core::$MEMCACHE->set(Core::$HTTP_HOST.":rss_feed_$id",$rss,MEMCACHE_COMPRESSED,300);
			}
		}
		else
		{
			$rss = new RssFeed($data);
		}
		return $rss;
	}
	
	public function __construct($data)
	{
		$tb = microtime();
				
		// Megvizsgáljuk a paraméterként kapott $data-t
		if (is_array($data)) //ha tömb
		{
			parent::__construct($data);
			/*
			 * Átadtam a Core konstruktorának
			 * Az majd végigmegy a tömb elemein,és objektum tulajdonságként eléri az adatbázis tábla mezőit.
			 */
			$this->init();
		}
		elseif (is_int($data))
		{
			// Egyébként ha a paraméter egy int tipus,akkor az adott id-t lekérdezem:
			$query = "SELECT * FROM `" . Core :: $TABLE_PREFIX . "rss_feed` WHERE `id` = $data";
			$result = Sql::query($query);
			if (Sql::num_rows($result) > 0)
			{
				// $o-ban eltárolom a lekérdezés eredmény rekordját
				$o = Sql::fetch_array($result);
     			// majd átadom az ős konstruktorának
				parent::__construct($o);
				$this->init();
			}
		}
		//naplózza az eltelt időt
		//paraméterként megadom a constructort>ctor,és az indítási időértéket
		$this->log("ctor", $tb);
	}

	public function init()
	{
		// 1. RSS beolvasása
		$result = $this->load($this->source);

		// 2. Az XML feldokgozása, RSS alapadatok beolvasása az objektumba
		$this->original_title = $result['title'];
		$this->link = $result['link'];
		$this->description = array_key_exists('description',$result)?$result['description']:"" ;
		// 3. Az adott számú RSS hír beolvasása az items tömbbe
		$this->items = array();
		foreach($result['items'] as $it)
		{
			$item = new Core($it);
			if (array_key_exists("pubDate",$it))
				$item->publish_date = Core::unix_to_date($it['pubDate']);
			else
				$item->publish_date = Core::unix_to_date(time());
			$item->load_template("rss_feed_item.html");
			$this->items[]=$item; //beraktuk a tömb legvégére
		}
		if(count($this->items)>0)
		{
			$this->load_template("rss_feed.html");
		}
	}

	function load($rss_url)
	{
		// If CACHE ENABLED
		if (Core::$PARAM['RSS_CACHE_FOLDER'] != '')
		{
			$cache_file = Core::$PARAM['RSS_CACHE_FOLDER']  . '/rsscache_' . md5($rss_url);
			$timedif = @(time() - filemtime($cache_file));
			if ($timedif < Core::$PARAM['RSS_CACHE_TIME'])
			{
				// cached file is fresh enough, return cached array
				$result = unserialize(file_get_contents(Core::$DOCROOT.$cache_file));
				// set 'cached' to 1 only if cached file is correct
				$result['cached'] = 1;
			}
			else
			{
				// cached file is too old, create new
				$result = $this->parse($source_url);
				$serialized = serialize($result);
				file_put_contents(Core::$DOCROOT.$cache_file, $serialized);
				$result['cached'] = 1;
			}
		}
		// If CACHE DISABLED >> load and parse the file directly
		else
		{
			$result = $this->parse($rss_url);
			if ($result) $result['cached'] = 0;
		}
		// return result
		return $result;
	}

	function my_preg_match ($pattern, $subject)
	{
		// start regullar expression
		preg_match($pattern, $subject, $out);
		// if there is some result... process it and return it
		if(isset($out[1]))
		{
			// Process CDATA (if present)
			if (Core::$PARAM['RSS_CDATA'] == 'content') { // Get CDATA content (without CDATA tag)
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			} elseif (Core::$PARAM['RSS_CDATA'] == 'strip') { // Strip CDATA
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}

			// If code page is set convert character encoding to required
			if (Core::$PARAM['RSS_CODE_PAGE'] != '')
				//$out[1] = $this->MyConvertEncoding($this->rsscp, $this->cp, $out[1]);
				$out[1] = iconv($this->rsscp, Core::$PARAM['RSS_CODE_PAGE'].'//TRANSLIT', $out[1]);
			// Return result
			return trim($out[1]);
		} else {
		// if there is NO result, return empty string
			return '';
		}
	}

	// -------------------------------------------------------------------
	// Replace HTML entities &something; by real characters
	// -------------------------------------------------------------------
	function unhtmlentities ($string) {
		// Get HTML entities table
		$trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
		// Flip keys<==>values
		$trans_tbl = array_flip ($trans_tbl);
		// Add support for &apos; entity (missing in HTML_ENTITIES)
		$trans_tbl += array('&apos;' => "'");
		// Replace entities by values
		return strtr ($string, $trans_tbl);
	}

	// -------------------------------------------------------------------
	// Parse() is private method used by Get() to load and parse RSS file.
	// Don't use Parse() in your scripts - use Get($rss_file) instead.
	// -------------------------------------------------------------------
	function parse ($rss_url)
	{
		// Open and load RSS file
		if ($f = @fopen($rss_url, 'r')) {
			$rss_content = '';
			while (!feof($f))
			{
				$rss_content .= fgets($f, 4096);
			}
			fclose($f);

			// Parse document encoding
			$this->rsscp = Core::$PARAM['RSS_DEFAULT_CODE_PAGE'];
			$result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
			// if document codepage is specified, use it
			if ($result['encoding'] != '')
				{ $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
			// otherwise use the default codepage

			// Parse CHANNEL info
			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
			foreach(RssFeed::$CHANNELTAGS as $channeltag)
			{
				if (count($out_channel)>1)
					$temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
				if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
			}
			// If date_format is specified and lastBuildDate is valid
			if(array_key_exists('lastBuildDate',$result))
				if ($timestamp = strtotime($result['lastBuildDate']))
				{
					// convert lastBuildDate to specified date format
					$result['lastBuildDate'] =  $timestamp;
				}
			// Parse TEXTINPUT info
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
				// This a little strange regexp means:
				// Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
			if (isset($out_textinfo[2]))
			{
				foreach(RssFeed::$TEXTINPUTTAGS as $textinputtag)
				{
					$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
				}
			}
			// Parse IMAGE info
			preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
			if (isset($out_imageinfo[1]))
			{
				foreach(RssFeed::$IMAGETAGS as $imagetag)
				{
					$temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
					if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
				}
			}
			// Parse ITEMS
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			$result['items'] = array(); // create array even if there are no items
			foreach($rss_items as $rss_item)
			{
				// If number of items is lower then limit: Parse one item
				if ($i < $this->item_nums || $this->item_nums == 0)
				{
					foreach(RssFeed::$ITEMTAGS as $itemtag)
					{
						$temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
						if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
					}
					// Strip HTML tags and other bullshit from DESCRIPTION
					if ((Core::$PARAM['RSS_STRIPHTML']== "1") && $result['items'][$i]['description'])
						$result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
					// Strip HTML tags and other bullshit from TITLE
					if ((Core::$PARAM['RSS_STRIPHTML']== "1") && $result['items'][$i]['title'])
						$result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
					// If date_format is specified and pubDate is valid
					if ($timestamp = strtotime($result['items'][$i]['pubDate']))
					{
						// convert pubDate to specified date format
						$result['items'][$i]['pubDate'] = $timestamp;
					}
					// Item counter
					$i++;
				}
			}
			$result['items_count'] = $i;
			return $result;
		}
		else // Error in opening return False
		{
			return FALSE;
		}
	}
}
?>
