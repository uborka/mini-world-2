<?php
require 'SegmentIterator.php';
class SegmentException extends Exception
{}
/**
 * Classe de gestion des segments de templating pour fichier odt
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Last commit by $Author: neveldo $
 * Date - $Date: 2009-06-04 17:38:25 +0200 (jeu., 04 juin 2009) $
 * SVN Revision - $Rev: 37 $
 * Id : $Id: Segment.php 37 2009-06-04 15:38:25Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
class Segment implements IteratorAggregate, Countable
{
    protected $xml;
    protected $xmlParsed = '';
    protected $name;
    protected $children = array();
    protected $vars = array();
	protected $images = array();
	protected $odf;
	protected $file;
    /**
     * Constructeur
     *
     * @param string $name nom du segment à construire
     * @param string $xml structure xml du segment
     */
    public function __construct($name, $xml, $odf)
    {
        $this->name = (string) $name;
        $this->xml = (string) $xml;
		$this->odf = $odf;
        $zipHandler = $this->odf->getConfig('ZIP_PROXY');
        $this->file = new $zipHandler();	
        $this->_analyseChildren($this->xml);
    }
    /**
     * Retourne le nom du segment
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Le segment a-t-il des enfants ?
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->getIterator()->hasChildren();
    }
    /**
     * Countable interface
     *
     * @return int
     */
    public function count()
    {
        return count($this->children);
    }
    /**
     * IteratorAggregate interface
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return new RecursiveIteratorIterator(new SegmentIterator($this->children), 1);
    }
    /**
     * Remplace les variables de template dans le XML,
     * tous les enfants sont aussi appelés
     *
     * @return string
     */
    public function merge()
    {
        $this->xmlParsed .= str_replace(array_keys($this->vars), array_values($this->vars), $this->xml);
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $this->xmlParsed = str_replace($child->xml, ($child->xmlParsed=="")?$child->merge():$child->xmlParsed, $this->xmlParsed);
                $child->xmlParsed = '';
            }
        }
        $reg = "/\[!--\sBEGIN\s$this->name\s--\](.*)\[!--\sEND\s$this->name\s--\]/sm";
        $this->xmlParsed = preg_replace($reg, '$1', $this->xmlParsed);
        $this->file->open($this->odf->getTmpfile());
        foreach ($this->images as $imageKey => $imageValue) {
			if ($this->file->getFromName('Pictures/' . $imageValue) === false) {
				$this->file->addFile($imageKey, 'Pictures/' . $imageValue);
			}
        }
        $this->file->close();		
        return $this->xmlParsed;
    }
    /**
     * Analyse le xml pour trouver des enfants
     *
     * @param string $xml
     * @return Segment
     */
    protected function _analyseChildren($xml)
    {
        // $reg2 = "#\[!--\sBEGIN\s([\S]*)\s--\](?:<\/text:p>)?(.*)(?:<text:p\s.*>)?\[!--\sEND\s(\\1)\s--\]#sm";
        $reg2 = "#\[!--\sBEGIN\s([\S]*)\s--\](.*)\[!--\sEND\s(\\1)\s--\]#sm";
        preg_match_all($reg2, $xml, $matches);
        for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
            if ($matches[1][$i] != $this->name) {
                $this->children[$matches[1][$i]] = new self($matches[1][$i], $matches[0][$i], $this->odf);
            } else {
                $this->_analyseChildren($matches[2][$i]);
            }
        }
        return $this;
    }
    /**
     * Affecte une variable de template à remplacer
     *
     * @param string $key
     * @param string $value
     * @throws SegmentException
     * @return Segment
     */
    public function setVar($key, $value, $encode = true, $charset = 'ISO-8859')
    {
        if (strpos($this->xml, $this->odf->getConfig('DELIMITER_LEFT') . $key . $this->odf->getConfig('DELIMITER_RIGHT')) === false) {
            throw new SegmentException("var $key not found in {$this->getName()}");
        }
		$value = $encode ? htmlspecialchars($value) : $value;
		$value = ($charset == 'ISO-8859') ? utf8_encode($value) : $value;
        $this->vars[$this->odf->getConfig('DELIMITER_LEFT') . $key . $this->odf->getConfig('DELIMITER_RIGHT')] = str_replace("\n", "<text:line-break/>", $value);
        return $this;
    }
    /**
     * Affecte une variable de template en tant qu'image
     *
     * @param string $key nom de la variable dans le template
     * @param string $value chemin vers une image
     * @throws OdfException
     * @return Segment
     */
    public function setImage($key, $value)
    {
        $filename = strtok(strrchr($value, '/'), '/.');
        $file = substr(strrchr($value, '/'), 1);
        $size = @getimagesize($value);
        if ($size === false) {
            throw new OdfException("Invalid image");
        }
        list ($width, $height) = $size;
        $width *= Odf::PIXEL_TO_CM;
        $height *= Odf::PIXEL_TO_CM;
        $xml = <<<IMG
<draw:frame draw:style-name="fr1" draw:name="$filename" text:anchor-type="char" svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;
        $this->images[$value] = $file;
        $this->setVar($key, $xml, false);
        return $this;
    }	
    /**
     * Raccourci pour récupérer un enfant
     *
     * @param string $prop
     * @return Segment
     * @throws SegmentException
     */
    public function __get($prop)
    {
        if (array_key_exists($prop, $this->children)) {
            return $this->children[$prop];
        } else {
            throw new SegmentException('child ' . $prop . ' does not exist');
        }
    }
    /**
     * Proxy vers setVar
     *
     * @param string $meth
     * @param array $args
     * @return Segment
     */
    public function __call($meth, $args)
    {
        try {
            return $this->setVar($meth, $args[0]);
        } catch (SegmentException $e) {
            throw new SegmentException("method $meth nor var $meth exist");
        }
    }
    /**
     * Retourne le XML parsé
     *
     * @return string
     */
    public function getXmlParsed()
    {
        return $this->xmlParsed;
    }
}

?>