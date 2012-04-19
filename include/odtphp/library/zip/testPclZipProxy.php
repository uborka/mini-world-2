<?php
/**
 * Checking the PclZipProxy
 * PclZip library is required for this proxy
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Last commit by $Author: neveldo $
 * Date - $Date: 2009-06-03 16:44:26 +0200 (mer., 03 juin 2009) $
 * SVN Revision - $Rev: 31 $
 * Id : $Id: odf.php 31 2009-06-03 14:44:26Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
	require_once('PclZipProxy.php');
	$sep = PHP_EOL . '/****************************************/' . PHP_EOL;
	chdir('../../tests/');
	copy('tutoriel1.odt', 'tmpTest.odt');
	
	echo 'Test 0 : constructeur de la classe ';
	try {
		$zip = new PclZipProxy();
		echo '[ok] (objet créé)';
	} catch (Exception $e) {
		echo '[erreur] ' . $e->getMessage();
		exit;
	}
	echo  $sep;
	
	echo 'Test 1: $zip::getFromName(\'content.xml\') avec une archive non ouverte' . PHP_EOL;
	$result1 = $zip->getFromName('content.xml');
	echo ($result1 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 2: $zip::addFromString(\'content.xml\', \'test string\') avec une archive non ouverte' . PHP_EOL;
	$result2 = $zip->addFromString('content.xml', 'test string');
	echo ($result2 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 3: $zip::addFile(\'images/anaska.gif\', \'Pictures/anaska.gif\') avec une archive non ouverte' . PHP_EOL;
	$result3 = $zip->addFile('images/anaska.gif', 'Pictures/anaska.gif');
	echo ($result3 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 4: $zip::close() avec une archive non ouverte' . PHP_EOL;
	$result4 = $zip->close();
	echo ($result4 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 5: $zip::open(\'nothing.odt\') avec une archive inexistante (création de l\'archive)' . PHP_EOL;
	$result5 = $zip->open('nothing.odt');
	echo ($result5 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 6: $zip::open() avec une archive existante' . PHP_EOL;
	$result6 = $zip->open('tmpTest.odt');
	echo ($result6 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 7: $zip::getFromName(\'nothing.xml\') avec un fichier inexistant' . PHP_EOL;
	$result7 = $zip->getFromName('nothing.xml');
	echo ($result7 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 8: $zip::getFromName(\'content.xml\') avec un fichier existant' . PHP_EOL;
	$result8 = $zip->getFromName('content.xml');
	echo ($result8 !== false) ? '[ok] ('.$result8.')' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 9: $zip::addFromString(\'nothing.xml\', \'test string\') avec un fichier destination inexistant (création)' . PHP_EOL;
	$result9 = $zip->addFromString('nothing.xml', 'test string');
	echo ($result9 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 10: $zip::addFromString(\'content.xml\', \'test string\') avec un fichier destination existant (remplacement)' . PHP_EOL;
	$result10 = $zip->addFromString('content.xml', 'test string');
	echo ($result10 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 11: $zip::addFile(\'images/nothing.gif\', \'Pictures/anaska.gif\') avec un fichier source inexistant' . PHP_EOL;
	$result11 = $zip->addFile('images/nothing.gif', 'Pictures/anaska.gif');
	echo ($result11 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 12: $zip::addFile(\'images/anaska.gif\', \'Pictures/anaska.gif\') avec un fichier destination inexistant (création)' . PHP_EOL;
	$result12 = $zip->addFile('images/anaska.gif', 'Pictures/anaska.gif');
	echo ($result12 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 13: $zip::addFile(\'images/anaska.gif\', \'Pictures/anaska.gif\') avec un fichier destination existant (remplacement)' . PHP_EOL;
	$result13 = $zip->addFile('images/anaska.gif', 'Pictures/anaska.gif');
	echo ($result13 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 14: $zip::getFromName(\'nothingUHjiijI.xml\') avec un fichier inexistant' . PHP_EOL;
	$result14 = $zip->getFromName('nothingUHjiijI.xml');
	echo ($result14 === false) ? '[ok] (false)' : '[erreur] (true)';
	echo  $sep;
	
	echo 'Test 15: $zip::getFromName(\'settings.xml\') avec un fichier existant' . PHP_EOL;
	$result15 = $zip->getFromName('settings.xml');
	echo ($result15 !== false) ? '[ok] ('.$result15.')' : '[erreur] (false)';
	echo  $sep;
	
	echo 'Test 16: $zip::close() avec une archive ouverte' . PHP_EOL;
	$result16 = $zip->close();
	echo ($result16 !== false) ? '[ok] (true)' : '[erreur] (false)';
	echo  $sep;
	unlink('tmpTest.odt');
?> 