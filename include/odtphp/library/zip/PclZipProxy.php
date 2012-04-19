<?php
require_once 'pclzip/pclzip.lib.php';
require_once 'ZipInterface.php';
class PclZipProxyException extends Exception
{ }
/**
 * Proxy class for the PclZip library
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Last commit by $Author: neveldo $
 * Date - $Date: 2009-05-29 10:05:11 +0200 (ven., 29 mai 2009) $
 * SVN Revision - $Rev: 28 $
 * Id : $Id: odf.php 28 2009-05-29 08:05:11Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
class PclZipProxy implements ZipInterface
{
	const TMP_DIR = './tmp';
	protected $openned = false;
	protected $filename;
	protected $pclzip;
    /**
     * Constructeur de classe
     *
     * @throws PclZipProxyException
     */
	public function __construct()
	{
		if (! class_exists('PclZip')) {
			throw new PclZipProxyException('PclZip class not loaded - PclZip library
			 is required for using PclZipProxy'); ;
		}
	}	
	/**
	 * Ouvrir une archive au format Zip
	 * 
	 * @param string $filename le nom de l'archive � ouvrir
	 * @return true si l'ouverture � r�ussi
	 */
	public function open($filename)
	{
		if (true === $this->openned) {
			$this->close();
		}		
		if (!file_exists(self::TMP_DIR)) {
			mkdir(self::TMP_DIR);
		}
		$this->filename = $filename;
		$this->pclzip = new PclZip($this->filename);
		$this->openned = true;
		return true;
	}
	/**
	 * R�cup�rer le contenu d'un fichier de l'archive � partir de son nom
	 * 
	 * @param string $name le nom du fichier � extraire
	 * @return le contenu du fichier dans une chaine de caract�res
	 */
	public function getFromName($name)
	{
		if (false === $this->openned) {
			return false;
		}
		$name = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $name);
		$extraction = $this->pclzip->extract(PCLZIP_OPT_BY_NAME, $name, 
			PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!empty($extraction)) {
			return $extraction[0]['content'];
		} 
		return false;
	}
	/**
	 * Ajouter un fichier � l'archive � partir d'une chaine de caract�res
	 * 
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @param string $contents le contenu du fichier
	 * @return true si le fichier a �t� ajout� avec succ�s
	 */
	public function addFromString($localname, $contents)
	{
		if (false === $this->openned) {
			return false;
		}
		if (file_exists($this->filename) && !is_writable($this->filename)) {
			return false;
		}
		$localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
		$localpath = dirname($localname);
		$tmpfilename = self::TMP_DIR . '/' . basename($localname);
		if (false !== file_put_contents($tmpfilename, $contents)) {
			$this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
			$add = $this->pclzip->add($tmpfilename,
				PCLZIP_OPT_REMOVE_PATH, self::TMP_DIR,
				PCLZIP_OPT_ADD_PATH, $localpath);
			unlink($tmpfilename);
			if (!empty($add)) {
				return true;
			} 
		}
		return false;
	}
	/**
	 * Ajouter un fichier � l'archive � partir d'un fichier
	 * 
	 * @param string $filename le chemin vers le fichier � ajouter
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @return true si le fichier a �t� ajout� avec succ�s
	 */
	public function addFile($filename, $localname = null)
	{
		if (false === $this->openned) {
			return false;
		}
		if (file_exists($this->filename) && !is_writable($this->filename)) {
			return false;
		}		
		if (isSet($localname)) {
			$localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
			$localpath = dirname($localname);
			$tmpfilename = self::TMP_DIR . '/' . basename($localname);
		} else {
			$localname = basename($filename);
			$tmpfilename = self::TMP_DIR . '/' . $localname;
			$localpath = '';
		}
		if (file_exists($filename)) {
			copy($filename, $tmpfilename);
			$this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
			$this->pclzip->add($tmpfilename,
				PCLZIP_OPT_REMOVE_PATH, self::TMP_DIR,
				PCLZIP_OPT_ADD_PATH, $localpath);
			unlink($tmpfilename);
			return true;
		}
		return false;
	}
	/**
	 * ferme l'archive Zip
	 * @return true
	 */
	public function close()
	{
		if (false === $this->openned) {
			return false;
		}		
		$this->pclzip = $this->filename = null;
		$this->openned = false;
		if (file_exists(self::TMP_DIR)) {
			$this->_rrmdir(self::TMP_DIR);
			rmdir(self::TMP_DIR);
		}
		return true;
	}
	/**
	 * Vide le r�pertoire temporaire de travail r�cursivement
	 * @param $dir le r�pertoire temporaire de travail
	 * @return void
	 */
	private function _rrmdir($dir)
	{
		if ($handle = opendir($dir)) { 
			while (false !== ($file = readdir($handle))) { 
				if ($file != '.' && $file != '..') {
					if (is_dir($dir . '/' . $file)) {
						$this->_rrmdir($dir . '/' . $file);
						rmdir($dir . '/' . $file);
					} else {
						unlink($dir . '/' . $file);
					}
				} 
			} 
			closedir($handle); 
		} 
	}		
}

?>