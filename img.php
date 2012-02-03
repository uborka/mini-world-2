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

define ("NOPIC",$_SERVER['DOCUMENT_ROOT']."/images/noimage.png");
define ("ZOOMICON",$_SERVER['DOCUMENT_ROOT']."/images/zoom.png");

$image = $_SERVER['DOCUMENT_ROOT']."/".$_GET["img"];
$do = $_GET["do"];

if (file_exists($image))
{
	if (($do == "rotate") || ($do == "rotate-ccw"))
	{
		$angle = 90.0;
		$original = imagecreatefromjpeg($image);
		$rotated = imagerotate($original, $angle, 0);
		header('Content-type: image/jpeg');
		imagejpeg($rotated);
	}
	
	if ($do == "resize")
	{
		$size = getimagesize($image);
	    $width = $size[0];
	    $height = $size[1];
	    $type = $size[2]; // 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF
		
		if ($_GET["width"]>0)
			$max_width = $_GET["width"];
		else
			$max_width = 320;
		if (isset($_GET["height"]))
		{
			if ($_GET["height"]>0)
				$max_height = $_GET["height"];
			else
				$max_height = 240;
		}
		else
			$max_height = -1;
		if (isset($_GET["bg"]))
			$bg = $_GET["bg"];
		else
			$bg = "FFFFFF";
	    
	    if ($type<3)
		{
		    switch ($type)
			{
			case 1:
				$src = imagecreatefromgif($image);
				break;
			case 2:
				$src = imagecreatefromjpeg($image);
				break;
			case 3:
				$src = imagecreatefrompng($image);
		    	break;
		    }
			if ($src == "")
			{
				$src = imagecreatefrompng(NOPIC);
			}
		}
		else
		{
			$src = imagecreatefrompng(NOPIC);
		}
			
		// A kép átméretezése a képarányok megtartásával
		// ha az nagyobb, mint a kért méret
		
		if ($max_height == -1)
			$m_height = $height;
		else
			$m_height = $max_height;
		$m_width = $max_width;
		
		if (($width > $m_width) || ($height > $m_height))
		{
			$x_ratio = $m_width / $width;
			$y_ratio = $m_height / $height;
			
			/* 1. Ha a kép magassága nagyobb, mint a kívánt
			 *    akkor arányosan kicsinyít
			 * 2. Ha a kép szélessége nagyobb, mint a kívánt
			 *    akkor arányosan kicsinyít
			 */
		
			if (($x_ratio * $height) < $m_height)
			{
				$tn_height = ceil($x_ratio * $height);
				$tn_width = $m_width;
			}
			else if (($y_ratio * $width) < $m_width)
			{
				$tn_width = ceil($y_ratio * $width);
				$tn_height = $m_height;
			}
			else
			{
				$tn_height = ceil($x_ratio * $height);
				$tn_width = ceil($y_ratio * $width);
			}
			
			$dst = imagecreatetruecolor($tn_width, $tn_height);
			imagecopyresized($dst, $src, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
		}
		else
		{
			$dst = imagecreatetruecolor($width, $height);
			imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
			$tn_width = $width;
			$tn_height = $height;
		}
		
		// A kép alá háttérszín beállítása, ha az kisebb, mint a kért méret
		if ($max_height == -1)
		{
			$x_ratio = $max_width / $width;
			$height < (ceil($x_ratio * $height)) ? $max_height = $height : $max_height = ceil($x_ratio * $height);
			//$max_height = $height;//;
		}
		
		$img = imagecreatetruecolor($max_width, $max_height);
		if (isset($_GET["setbg"]))
		{
			if (($tn_width < $max_width) || ($tn_height < $max_height))
			{
				$rgb = sscanf($bg, '%2x%2x%2x');
				$color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
				imagefill($img, 0, 0, $color);
				$x_pos = round(($max_width - $tn_width) / 2);
				$y_pos = round(($max_height - $tn_height) / 2);
				imagecopy($img, $dst, $x_pos, $y_pos, 0, 0, $tn_width, $tn_height);
				
				$tn_width = $max_width;
				$tn_height = $max_height;
			}
			else
			{
				imagecopy($img, $dst, 0, 0, 0, 0, $max_width, $max_height);
			}
		}
		else
		{
			$img = imagecreatetruecolor($tn_width, $tn_height);
			imagecopy($img, $dst, 0, 0, 0, 0, $tn_width, $tn_height);
		}
		
		// A kép sarkába a nagyítást jelző ikon felmásolása, ha
		// a kép kicsinyítve lett és ezt kérték
		if (isset($_GET["setzoom"]))
		{
			if (($size[0] < $max_width) || ($size[1] < $max_height))
			{
				$icon = imagecreatefrompng(ZOOMICON);
				$isize = getimagesize(ZOOMICON);
	    		imagecopymerge($img,
	    					   $icon,
	    					   $tn_width - $isize[0],
	    					   $tn_height - $isize[1],
	    					   0,0,
	    					   $isize[0],$isize[1],
	    					   100);
			}
		}

		// A kép generálása a kimenetre
		
		switch ($type)
		{
		case 1:
			header("Content-type: image/gif");
			imagegif($img);
			break;
		case 2:
			header("Content-type: image/jpeg");
			imagejpeg($img);
			break;
		case 3:
			header("Content-type: image/png");
			imagepng($img);
			break;
		}
		
		imagedestroy($src);
		imagedestroy($dst);
		imagedestroy($img);
	}
}
else
{
	$src = imagecreatefrompng(NOPIC);
	header("Content-type: image/png");
	imagepng($img);
	imagedestroy($src);
}
?>