<?php
namespace Asticode\FileManager\Entity;

use SplDoublyLinkedList;

class Vertex
{
    // Attributes
    private $iDatasource;
    private $bVisited;
    private $iDistance;
    private $aPath;

    // Construct
    public function __construct($iDatasource)
    {
        // Initialize
        $this->iDatasource = $iDatasource;
        $this->bVisited = false;
        $this->iDistance = -1;
        $this->aPath = [];
    }

    public function getDatasource()
    {
        return $this->iDatasource;
    }

    public function isVisited()
    {
        return $this->bVisited;
    }

    public function getDistance()
    {
        return $this->iDistance;
    }

    public function getPath()
    {
        return $this->aPath;
    }

    public function setVisited($bVisited)
    {
        $this->bVisited = $bVisited;
    }

    public function setDistance($iDistance)
    {
        $this->iDistance = $iDistance;
    }

    public function setPath($aPath)
    {
        $this->aPath = $aPath;
    }
}