<?php
namespace Asticode\FileManager\Entity;

use Asticode\Toolbox\ExtendedArray;
use Asticode\FileManager\Enum\OrderField;

class File
{
    // Attributes
    private $sPath;
    private $iSize;
    private $oModificationDate;

    // Construct
    public function __construct($sPath, $iSize = 0, \DateTime $oModificationDate = null)
    {
        // Initialize
        $this->sPath = $sPath;
        $this->iSize = intval($iSize);
        $this->oModificationDate = $oModificationDate;
    }

    public function getPath()
    {
        return $this->sPath;
    }

    public function getParentPath()
    {
        return dirname($this->sPath);
    }

    public function getBasename()
    {
        return basename($this->sPath);
    }

    public function getExtension()
    {
        $aExplodedFilename = explode('.', $this->getBasename());
        return ExtendedArray::getLastValue($aExplodedFilename);
    }

    public function getPathWithoutExtension()
    {
        $aExplodedFilename = explode('.', $this->getBasename());
        array_pop($aExplodedFilename);
        return sprintf(
            '%s/%s',
            $this->getParentPath(),
            implode('.', $aExplodedFilename)
        );
    }

    public function getBasenameWithoutExtension()
    {
        $aExplodedFilename = explode('.', $this->getBasename());
        array_pop($aExplodedFilename);
        return implode('.', $aExplodedFilename);
    }

    public function getSize()
    {
        return $this->iSize;
    }

    public function getModificationDate()
    {
        return $this->oModificationDate;
    }

    public function getOrderField($iOrderFieldId)
    {
        switch ($iOrderFieldId) {
            case OrderField::MODIFICATION_DATE:
                return $this->getModificationDate()->getTimestamp();
                break;
            case OrderField::BASENAME:
                return $this->getBasename();
                break;
            case OrderField::SIZE:
                return $this->getSize();
                break;
        }
    }
}