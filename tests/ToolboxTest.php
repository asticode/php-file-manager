<?php
namespace Asticode\FileManager\Tests;

use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Toolbox;
use DateTime;
use PHPUnit_Framework_TestCase;

class ToolboxTest extends PHPUnit_Framework_TestCase
{

    public function testParseRawList()
    {
        // Initialize
        $sBasePath = '/tmp';
        $oDateHour = DateTime::createFromFormat('F d H:i', 'Sep 22 14:45');
        $oDateYear = DateTime::createFromFormat('F d Y', 'Sep 22 2015');
        $sRawList1 = 'drwxr-xr-x 2 zmifwezd zmifwezd 4096 Sep 22 14:45 name';
        $sRawList2 = 'drwxr-xr-x 2 zmifwezd zmifwezd 4096 Sep 22 2015 name';
        $sRawList3 = 'drwxr-xr-x 2 zmifwezd zmifwezd 4096 22 sep 14:45 name';

        // Parse
        $oFile1 = Toolbox::parseRawList($sRawList1, $sBasePath);
        $oFile2 = Toolbox::parseRawList($sRawList2, $sBasePath);
        $oFile3 = Toolbox::parseRawList($sRawList3, $sBasePath);

        // Assert
        $this->assertEquals('/tmp/name', $oFile1->getPath());
        $this->assertEquals($oDateHour->getTimestamp(), $oFile1->getModificationDate()->getTimestamp());
        $this->assertEquals('/tmp/name', $oFile2->getPath());
        $this->assertEquals($oDateYear->getTimestamp(), $oFile2->getModificationDate()->getTimestamp());
        $this->assertEquals('/tmp/name', $oFile3->getPath());
        $this->assertEquals($oDateHour->getTimestamp(), $oFile3->getModificationDate()->getTimestamp());
    }

    public function testSortFiles()
    {
        // Initialize
        $aFiles = [
            new File(
                '/tmp/path1',
                202,
                DateTime::createFromFormat('Y-m-d', '2016-02-19')
            ),
            new File(
                '/tmp/path2',
                200,
                DateTime::createFromFormat('Y-m-d', '2016-02-18')
            ),
            new File(
                '/tmp/path3',
                205,
                DateTime::createFromFormat('Y-m-d', '2016-02-19')
            )
        ];

        // No sorting
        Toolbox::sortFiles($aFiles, OrderField::NONE);

        // Assert
        $this->assertEquals('path1', $aFiles[0]->getBasename());
        $this->assertEquals('path2', $aFiles[1]->getBasename());
        $this->assertEquals('path3', $aFiles[2]->getBasename());

        // Sorting by modification date
        Toolbox::sortFiles($aFiles, OrderField::MODIFICATION_DATE);

        // Assert
        $this->assertEquals('path1', $aFiles[1]->getBasename());
        $this->assertEquals('path2', $aFiles[0]->getBasename());
        $this->assertEquals('path3', $aFiles[2]->getBasename());

        // Sorting DESC
        Toolbox::sortFiles($aFiles, OrderField::SIZE, OrderDirection::DESC);

        // Assert
        $this->assertEquals('path1', $aFiles[1]->getBasename());
        $this->assertEquals('path2', $aFiles[2]->getBasename());
        $this->assertEquals('path3', $aFiles[0]->getBasename());
    }

    public function testAddFile()
    {
        // Initialize
        $aAllowedExtensions = ['csv', 'doc'];
        $aAllowedBasenamePatterns = ['^path4', 'test'];
        $iSize = 200;
        $oDate = new DateTime();
        $aFiles = [
            new File('/tmp/path1', $iSize, $oDate),
            new File('/tmp/path2', $iSize, $oDate),
        ];

        // Ignore . and ..
        Toolbox::addFile($aFiles, new File('/tmp/.', $iSize, $oDate));
        Toolbox::addFile($aFiles, new File('/tmp/..', $iSize, $oDate));
        $this->assertCount(2, $aFiles);

        // Test allowed extensions
        Toolbox::addFile($aFiles, new File('/tmp/path3.xls', $iSize, $oDate), $aAllowedExtensions);
        Toolbox::addFile($aFiles, new File('/tmp/path3.csv', $iSize, $oDate), $aAllowedExtensions);
        $this->assertCount(3, $aFiles);
        $this->assertEquals('path3.csv', $aFiles[2]->getBasename());

        // Test allowed basename patterns
        Toolbox::addFile($aFiles, new File('/tmp/path34.csv', $iSize, $oDate), [], $aAllowedBasenamePatterns);
        Toolbox::addFile($aFiles, new File('/tmp/path4.csv', $iSize, $oDate), [], $aAllowedBasenamePatterns);
        $this->assertCount(4, $aFiles);
        $this->assertEquals('path4.csv', $aFiles[3]->getBasename());
    }
}
