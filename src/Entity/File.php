<?php
namespace Asticode\FileManager\Entity;

use Asticode\FileManager\Toolbox;
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
        return Toolbox::getParentPath($this->sPath);
    }

    public function getBasename()
    {
        return Toolbox::getBasename($this->sPath);
    }

    public function getExtension()
    {
        return Toolbox::getExtension($this->sPath);
    }

    public function getPathWithoutExtension()
    {
        return Toolbox::getPathWithoutExtension($this->sPath);
    }

    public function getBasenameWithoutExtension()
    {
        return Toolbox::getBasenameWithoutExtension($this->sPath);
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