<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;

interface HandlerInterface
{

    /**
     * @return int
     */
    public function getDatasource();

    /**
     * @return array
     */
    public function getCopyMethods();

    /**
     * @param string $sPath
     * @return boolean
     */
    public function exists($sPath);

    /**
     * @param string $sPath
     * @return \Asticode\FileManager\Entity\File
     */
    public function metadata($sPath);

    /**
     * @param string $sPath
     */
    public function createDir($sPath);

    /**
     * @param string $sPath
     */
    public function createFile($sPath);

    /**
     * @param $sPath
     * @param int $iOrderField
     * @param int $iOrderDirection
     * @param array $aAllowedExtensions
     * @param array $aAllowedPatterns
     * @return mixed
     */
    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = [],
        array $aAllowedPatterns = []
    );

    /**
     * @param string $sPattern
     * @param string $sPath
     * @return array
     */
    public function searchPattern($sPattern, $sPath);

    /**
     * @param string $sContent
     * @param string $sPath
     * @param int $iWriteMethod
     */
    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND);

    /**
     * @param string $sPath
     * @return string
     */
    public function read($sPath);

    /**
     * @param string $sSourcePath
     * @param string $sTargetPath
     */
    public function rename($sSourcePath, $sTargetPath);

    /**
     * @param string $sPath
     */
    public function delete($sPath);
}
