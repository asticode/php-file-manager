<?php
namespace Asticode\FileManager\Entity;

use SplDoublyLinkedList;

class CopyMethod
{
    // Attributes
    private $iSourceDatasource;
    private $iTargetDatasource;
    private $aCallable;
    private $lDoublyLinkedList;

    // Construct
    public function __construct($iSourceDatasource, $iTargetDatasource, array $aCallable)
    {
        // Initialize
        $this->iSourceDatasource = $iSourceDatasource;
        $this->iTargetDatasource = $iTargetDatasource;
        $this->aCallable = $aCallable;
        $this->lDoublyLinkedList = new SplDoublyLinkedList();
        $this->lDoublyLinkedList->push(new Vertex($iSourceDatasource));
        $this->lDoublyLinkedList->push(new Vertex($iTargetDatasource));
        $this->lDoublyLinkedList->rewind();
    }

    public function __clone()
    {
        $lDoublyLinkedList = new SplDoublyLinkedList();
        $this->lDoublyLinkedList->rewind();
        while ($this->lDoublyLinkedList->valid()) {
            $lDoublyLinkedList->push(clone $this->lDoublyLinkedList->current());
            $this->lDoublyLinkedList->next();
        }
        $lDoublyLinkedList->rewind();
        $this->lDoublyLinkedList = $lDoublyLinkedList;
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

    public function getDoublyLinkedList()
    {
        return $this->lDoublyLinkedList;
    }
}