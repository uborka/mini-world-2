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
class RssComb extends Core
{
	static $CHANNELTAGS	= array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	static $ITEMTAGS = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
	static $IMAGETAGS = array('title', 'url', 'link', 'width', 'height');
	static $TEXTINPUTTAGS = array('title', 'description', 'name', 'link');

	public static function get_rss_comb()
	{
		if (Core::$MEMCACHE)
		{
			if (! ($rss = Core::$MEMCACHE->get(Core::$HTTP_HOST.":rss_comb",MEMCACHE_COMPRESSED)))
			{
				$rss = new RssComb();
				Core::$MEMCACHE->set(Core::$HTTP_HOST.":rss_comb",$rss,MEMCACHE_COMPRESSED,3600);
			}
		}
		else
		{
			$rss = new RssComb();
		}
		return $rss;
	}
	
	public function __construct()
	{
		/*
		 * Beolvasssa az adatbázisban engedélyezett hírfolyamok címeit,és azokkal meghívja a prepare funkciót.
		 * Majd a végén a sort() funkcióval rendezi
		 */

		$tb = microtime();
		$this->init();
		$this->log("ctor",$tb);
	}

	public function init()
	{
		// Feed > híreket tartalmaz
		// 1. lekérdezem az adatokat
		$query = "SELECT * FROM `".Core::$TABLE_PREFIX."rss_comb` WHERE `option_enabled` = 1 ";
		$result = Sql::query($query);

		/*
		 * megvizsgáljuk az rss_feed-ek darabszámát
		 * ha van benne legalább egy,csak akkor foglalkozunk vele
		 */
		if(Sql::num_rows($result)>0 )
		{
			$this->items = array(); // items tömb létrejön
			while($o = Sql::fetch_array($result)) //végigmegyünk az eredményeket tartalmazó tömbön
			{
				// 2. átadom a loadnak paraméterként a source_url-t
				// majd a load() visszatért értékét átadom a $final_result -nak
				$final_result = $this->load($o["source"]);

				/*
				 * végigmegyek a $final_result['items']-en
				 * és példányosítom a Core osztályt($it) paraméterrel
				 * az $item objektum publish_date paramétere felveszi
				 * az rss 'pubDate' értékét unixdatetimeból datetime-ra
				 * majd ezután beteszi az items[] tömb legvégére
				 */
				foreach($final_result['items'] as $it)
				{
					$item = new Core($it);
					$item->publish_date =  Core::unix_to_datetime($it['pubDate']);
					$this->items[]=$item; //beraktuk a tömb legvégére
				}
			}
			// 3. meghívja a sort-ot és rendezi az objektumokat
			$this->sort();

			// 4. a megadott paraméterig betöltjük az elemek sablonját
			$i = 0;
			foreach($this->items as $it)
			{
				//megvizsgálja a paraméter értékét
				// és ha 0 /nincs benne rss/ kiugrik
				if($i>=Core::$PARAM['RSS_COMB_NUMS'])
				break;

				$i++;
				//A lista elejére betöltjük a sablonokat/megjelenítés/
				$it->load_template("rss_comb_item.html");
			}
			//ha került a tömbbe rss,akkor betölti a sablont a megjelenítéshez
			if($this->items>0){
				$this->load_template("rss_comb.html");
			}
		}
	}

	public function load($source_url)
	{
		/* a kapott forrást feldolgozza,
		   minden hírt önálló Core objektumként
		   elhelyez az items tömbben
		*/

		// If CACHE ENABLED
		if (Core::$PARAM['RSS_COMB_CACHE_FOLDER'] != '')
		{
			$cache_file = Core::$PARAM['RSS_COMB_CACHE_FOLDER']  . '/rsscache_' . md5($source_url);
			$timedif = @(time() - filemtime($cache_file));
			if ($timedif < Core::$PARAM['RSS_COMB_CACHE_TIME'])
			{
				// cached file is fresh enough, return cached array
				$result = unserialize(file_get_contents(Core::$DOCROOT.$cache_file));
				// set 'cached' to 1 only if cached file is correct
				if ($result)
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
			$result = $this->parse($source_url);
			if ($result)
				$result['cached'] = 0;
		}
		// return result
		return $result;
	}

	function my_preg_match ($pattern, $subject)
	{
		// start regullar expression
		preg_match($pattern, $subject, $out);

		// if there is some result... process it and return it
		if(isset($out[1])) {
			// Process CDATA (if present)
			if (Core::$PARAM['RSS_COMB_CDATA'] == 'content') { // Get CDATA content (without CDATA tag)
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			} elseif (Core::$PARAM['RSS_COMB_CDATA'] == 'strip') { // Strip CDATA
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}

			// If code page is set convert character encoding to required
			if (Core::$PARAM['RSS_COMB_CODE_PAGE'] != '')
				//$out[1] = $this->MyConvertEncoding($this->rsscp, $this->cp, $out[1]);
				$out[1] = iconv($this->rsscp, Core::$PARAM['RSS_COMB_CODE_PAGE'].'//TRANSLIT', $out[1]);
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
		if ($f = @fopen($rss_url, 'r'))
		{
			$rss_content = '';
			while (!feof($f)) {
				$rss_content .= fgets($f, 4096);
			}
			fclose($f);

			// Parse document encoding
			$this->rsscp = Core::$PARAM['RSS_COMB_DEFAULT_CODE_PAGE'];
			$result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
			// if document codepage is specified, use it
			if ($result['encoding'] != '')
				{ $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
			// otherwise use the default codepage

			// Parse CHANNEL info
			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
			foreach(RssComb::$CHANNELTAGS as $channeltag)
			{
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
			if (isset($out_textinfo[2])) {
				foreach(RssComb::$TEXTINPUTTAGS as $textinputtag) {
					$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
				}
			}
			// Parse IMAGE info
			preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
			if (isset($out_imageinfo[1]))
			{
				foreach(RssComb::$IMAGETAGS as $imagetag)
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
				if ($i < Core::$PARAM['RSS_COMB_NUMS'] || Core::$PARAM['RSS_COMB_NUMS'] == 0)
			//	if ($i < $this->item_nums || $this->item_nums == 0)
				{
					foreach(RssComb::$ITEMTAGS as $itemtag) {
						$temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
						if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
					}
					// Strip HTML tags and other bullshit from DESCRIPTION
					if ((Core::$PARAM['RSS_COMB_STRIPHTML']== "1") && $result['items'][$i]['description'])
						$result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
					// Strip HTML tags and other bullshit from TITLE
					if ((Core::$PARAM['RSS_COMB_STRIPHTML']== "1") && $result['items'][$i]['title'])
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
			return False;
		}
	}

	static function compare($m , $n)
	{
    	if ($m->pubDate > $n->pubDate)
    	{
        	return -1;
    	}
    	if ($m->pubDate < $n->pubDate)
    	{
    		return 1;
    	}
    	else
    	{
    		return 0;
    	}

 	}

	public function sort()
	{
		/* az items tömbben lévő objektumokat rendezi azok
		   date_publish (csökkenő), title (növekvő) tulajdonságai alapján.
		   majd az items_num alapján csonkolja a tömböt.
		*/



		$sorted = usort($this->items, array('RssComb' , 'compare'));

	}


	public static function after_core_set_content($content)
	{
		if (empty($content))
		{
			$action = array_key_exists("a",$_SESSION) ? $_SESSION["a"] : NULL;

			switch ($action)
			{
				// A tartalmat felcserélő modulok
				case "rss_comb":
					$content = "{modul:$action}";
					break;
			}
		}
		return $content;
	}


	public static function modul_rss_comb($modul_variable)
	{
		//$box = new RssComb();
		$box = RssComb::get_rss_comb();
		return $box;
	}

}
?>
