<?php
namespace Asticode\FileManager\Tests;

use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\FileManager;
use Asticode\FileManager\Handler\FTPHandler;
use Asticode\FileManager\Handler\UNIXHandler;
use PHPUnit_Framework_TestCase;

class FileManagerTest extends PHPUnit_Framework_TestCase
{

    public function testChooseBestFileMethods()
    {
        // Init
        $oFileManager = new FileManager([]);
        $oUnixHandler = new UNIXHandler([]);
        $oFTPHandler = new FTPHandler(['host' => '']);
        $aCopyMethods = array_merge($oUnixHandler->getCopyMethods(), $oFTPHandler->getCopyMethods());
        $aMoveMethods = array_merge($oUnixHandler->getMoveMethods(), $oFTPHandler->getMoveMethods());

        // Choose best copy methods
        $aMethods = $oFileManager->chooseBestFileMethods($oFTPHandler, $oUnixHandler, $aCopyMethods);
        $this->assertEquals(1, count($aMethods));
        $this->assertEquals("download", $aMethods[0]->getCallable()[1]);
        $aMethods = $oFileManager->chooseBestFileMethods($oUnixHandler, $oUnixHandler, $aCopyMethods);
        $this->assertEquals(1, count($aMethods));
        $this->assertEquals("copy", $aMethods[0]->getCallable()[1]);
        $aMethods = $oFileManager->chooseBestFileMethods($oFTPHandler, $oFTPHandler, $aCopyMethods);
        $this->assertEquals(2, count($aMethods));
        $this->assertEquals("download", $aMethods[0]->getCallable()[1]);
        $this->assertEquals("upload", $aMethods[1]->getCallable()[1]);

        // Choose best move methods
        $aMethods = $oFileManager->chooseBestFileMethods($oFTPHandler, $oUnixHandler, $aMoveMethods);
        $this->assertEquals(0, count($aMethods));
        $aMethods = $oFileManager->chooseBestFileMethods($oUnixHandler, $oUnixHandler, $aMoveMethods);
        $this->assertEquals(1, count($aMethods));
        $this->assertEquals("rename", $aMethods[0]->getCallable()[1]);
        $aMethods = $oFileManager->chooseBestFileMethods($oFTPHandler, $oFTPHandler, $aMoveMethods);
        $this->assertEquals(1, count($aMethods));
        $this->assertEquals("rename", $aMethods[0]->getCallable()[1]);
    }
}
