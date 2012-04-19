<?php
require_once 'ZipInterface.php';
class PhpZipProxyException extends Exception
{ }
/**
 * Proxy class for the PHP Zip Extension
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

class PhpZipProxy implements ZipInterface
{
	protected $zipArchive;
	protected $filename;
    /**
     * Constructeur de classe
     *
     * @throws PhpZipProxyException
     */	
	public function __construct()
	{
		if (! class_exists('ZipArchive')) {
			throw new PhpZipProxyException('Zip extension not loaded - check your php settings, PHP5.2 minimum with zip extension
			 is required for using PhpZipProxy'); ;
		}
		$this->zipArchive = new ZipArchive();
	}
	/**
	 * Ouvrir une archive au format Zip
	 * 
	 * @param string $filename le nom de l'archive à ouvrir
	 * @return true si l'ouverture à réussi
	 */		
	public function open($filename)
	{
		$this->filename = $filename;
		return $this->zipArchive->open($filename, ZIPARCHIVE::CREATE);
	}
	/**
	 * Récupérer le contenu d'un fichier de l'archive à partir de son nom
	 * 
	 * @param string $name le nom du fichier à extraire
	 * @return le contenu du fichier dans une chaine de caractères
	 */		
	public function getFromName($name)
	{
		return $this->zipArchive->getFromName($name);
	}
	/**
	 * Ajouter un fichier à l'archive à partir d'une chaine de caractères
	 * 
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @param string $contents le contenu du fichier
	 * @return true si le fichier a été ajouté avec succès
	 */		
	public function addFromString($localname, $contents)
	{
		if (file_exists($this->filename) && !is_writable($this->filename)) {
			return false;
		}
		return $this->zipArchive->addFromString($localname, $contents);
	}
	/**
	 * Ajouter un fichier à l'archive à partir d'un fichier
	 * 
	 * @param string $filename le chemin vers le fichier à ajouter
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @return true si le fichier a été ajouté avec succès
	 */		
	public function addFile($filename, $localname = null)
	{
		if (file_exists($this->filename) && !is_writable($this->filename)) {
			return false;
		}
		return $this->zipArchive->addFile($filename, $localname);
	}
	/**
	 * ferme l'archive Zip
	 * @return true
	 */		
	public function close()
	{
		return $this->zipArchive->close();
	}
}
?>