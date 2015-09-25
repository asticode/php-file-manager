<?php
namespace Asticode\FileManager\Entity;

class CopyMethod
{
    // Attributes
    private $iSourceDatasource;
    private $iTargetDatasource;
    private $aCallable;

    // Construct
    public function __construct($iSourceDatasource, $iTargetDatasource, array $aCallable)
    {
        // Initialize
        $this->iSourceDatasource = $iSourceDatasource;
        $this->iTargetDatasource = $iTargetDatasource;
        $this->aCallable = $aCallable;
    }

    public function getSourceDatasource()
    {
        return $this->iSourceDatasource;
    }

    public function getTargetDatasource()
    {
        return $this->iTargetDatasource;
    }

    public function getCallable()
    {
        return $this->aCallable;
    }
}