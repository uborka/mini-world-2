<?php
/**
 * Interface for Zip libraries used in odtPHP
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
interface ZipInterface
{
	/**
	 * Ouvrir une archive au format Zip
	 * 
	 * @param string $filename le nom de l'archive à ouvrir
	 * @return true si l'ouverture à réussi
	 */	
	public function open($filename);
	/**
	 * Récupérer le contenu d'un fichier de l'archive à partir de son nom
	 * 
	 * @param string $name le nom du fichier à extraire
	 * @return le contenu du fichier dans une chaine de caractères
	 */	
	public function getFromName($name);
	/**
	 * Ajouter un fichier à l'archive à partir d'une chaine de caractères
	 * 
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @param string $contents le contenu du fichier
	 * @return true si le fichier a été ajouté avec succès
	 */	
	public function addFromString($localname, $contents);
	/**
	 * Ajouter un fichier à l'archive à partir d'un fichier
	 * 
	 * @param string $filename le chemin vers le fichier à ajouter
	 * @param string $localname le chemin local du fichier dans l'archive
	 * @return true si le fichier a été ajouté avec succès
	 */	
	public function addFile($filename, $localname = null);
	/**
	 * ferme l'archive Zip
	 * @return true
	 */	
	public function close();
}
?>