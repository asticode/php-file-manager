<?php
namespace Asticode\FileManager;

use Asticode\Toolbox\ExtendedArray;

class Toolbox
{

    public static function getParentPath($sPath)
    {
        return dirname($sPath);
    }

    public static function getBasename($sPath)
    {
        return basename($sPath);
    }

    public static function getExtension($sPath)
    {
        $aExplodedBasename = explode('.', self::getBasename($sPath));
        return count($aExplodedBasename) > 1 ? ExtendedArray::getLastValue($aExplodedBasename) : '';
    }

    public static function removeExtension($sPath)
    {
        $aExplodedPath = explode('.', $sPath);
        if (count($aExplodedPath) > 1) {
            array_pop($aExplodedPath);
        }
        return implode('.', $aExplodedPath);
    }

    public static function getPathWithoutExtension($sPath)
    {
        return self::removeExtension($sPath);
    }

    public static function getBasenameWithoutExtension($sPath)
    {
        return self::removeExtension(self::getBasename($sPath));
    }

}
