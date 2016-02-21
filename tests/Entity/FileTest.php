<?php
namespace Asticode\FileManager\Tests\Entity;

use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\OrderField;
use DateTime;
use PHPUnit_Framework_TestCase;

class FileTest extends PHPUnit_Framework_TestCase
{

    public function testParseRawList()
    {
        // Initialize
        $sPath = '/tmp/path';
        $iSize = 200;
        $oDate = new DateTime();
        $oFile = new File(
            $sPath,
            $iSize,
            $oDate
        );

        // Assert
        $this->assertEquals($oDate->getTimestamp(), $oFile->getOrderField(OrderField::MODIFICATION_DATE));
        $this->assertEquals($iSize, $oFile->getOrderField(OrderField::SIZE));
        $this->assertEquals('path', $oFile->getOrderField(OrderField::BASENAME));
        $this->assertEquals('path', $oFile->getOrderField('test'));
    }
}
