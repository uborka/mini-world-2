<?php
/**
 * Iterateur de Segments
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Last commit by $Author: neveldo $
 * Date - $Date: 2009-06-04 15:41:28 +0200 (jeu., 04 juin 2009) $
 * SVN Revision - $Rev: 34 $
 * Id : $Id: SegmentIterator.php 34 2009-06-04 13:41:28Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
class SegmentIterator implements RecursiveIterator
{
    private $ref;
    private $key;
    public function __construct(array $ref)
    {
        $this->ref = $ref;
        $this->key = 0;
        $this->keys = array_keys($this->ref);
    }
    public function hasChildren()
    {
        return $this->valid() && $this->current() instanceof Segment;
    }
    public function current()
    {
        return $this->ref[$this->keys[$this->key]];
    }
    function getChildren()
    {
        return new self($this->current()->children);
    }
    public function key()
    {
        return $this->key;
    }
    public function valid()
    {
        return array_key_exists($this->key, $this->keys);
    }
    public function rewind()
    {
        $this->key = 0;
    }
    public function next()
    {
        $this->key ++;
    }
}

?>