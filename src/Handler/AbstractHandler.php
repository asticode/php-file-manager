<?php
namespace Asticode\FileManager\Handler;

use RuntimeException;

abstract class AbstractHandler implements HandlerInterface
{

    public function getCopyMethods()
    {
        return [];
    }

    public function exists($sPath)
    {
        // Metadata
        try {
            $this->metadata($sPath);
            return true;
        } catch (RuntimeException $oException) {
            return false;
        }
    }

    public function searchPattern($sPattern, $sPath)
    {
        // Initialize
        $aFiles = [];

        // Get list of files in directory
        $aList = $this->explore($sPath);

        // Loop through files
        /** @var $oFile \Asticode\FileManager\Entity\File */
        foreach ($aList as $oFile) {
            if (preg_match($sPattern, $oFile->getBasename()) > 0) {
                $aFiles[] = $oFile;
            }
        }

        // Return
        return $aFiles;
    }

}
