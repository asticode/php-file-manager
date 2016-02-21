<?php
namespace Asticode\FileManager\Tests\Handler;

use Asticode\Toolbox\ExtendedUnitTesting;
use DateTime;
use PHPUnit_Framework_TestCase;

class AbstractHandlerTest extends PHPUnit_Framework_TestCase
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
        $oFile1 = ExtendedUnitTesting::callMethod(
            '\Asticode\FileManager\Handler\AbstractHandler',
            'parseRawList',
            [$sRawList1, $sBasePath]
        );
        $oFile2 = ExtendedUnitTesting::callMethod(
            '\Asticode\FileManager\Handler\AbstractHandler',
            'parseRawList',
            [$sRawList2, $sBasePath]
        );
        $oFile3 = ExtendedUnitTesting::callMethod(
            '\Asticode\FileManager\Handler\AbstractHandler',
            'parseRawList',
            [$sRawList3, $sBasePath]
        );

        // Assert
        $this->assertEquals('/tmp/name', $oFile1->getPath());
        $this->assertEquals($oDateHour->getTimestamp(), $oFile1->getModificationDate()->getTimestamp());
        $this->assertEquals('/tmp/name', $oFile2->getPath());
        $this->assertEquals($oDateYear->getTimestamp(), $oFile2->getModificationDate()->getTimestamp());
        $this->assertEquals('/tmp/name', $oFile3->getPath());
        $this->assertEquals($oDateHour->getTimestamp(), $oFile3->getModificationDate()->getTimestamp());
    }
}
