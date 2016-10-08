<?php
namespace Asticode\FileManager\Tests;

use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\FileManager;
use PHPUnit_Framework_TestCase;

class FileManagerTest extends PHPUnit_Framework_TestCase
{

    public function testChooseBestCopyMethods()
    {
        // Init
        $oFileManager = new FileManager([]);
        $oFileManager->addHandler("ftp", "FTP", ["host" => "dummy"]);
        $oFileManager->addHandler("unix", "UNIX", []);

        // Choose best copy methods
        $oCopyMethods = $oFileManager->chooseBestCopyMethods($oFileManager->getHandler("ftp"), $oFileManager->getHandler("ftp"));
        $this->assertEquals(Datasource::FTP, $oCopyMethods[0]->getSourceDatasource());
        $this->assertEquals(Datasource::FTP, $oCopyMethods[1]->getTargetDatasource());
    }
}
