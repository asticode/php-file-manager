<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use RuntimeException;

abstract class AbstractHandler implements HandlerInterface
{

    public function getCopyMethods()
    {
        return [];
    }

    protected static function parseRawList($sFile, $sPath)
    {
        // drwxr-xr-x 2 zmifwezd zmifwezd 4096 Sep 22 14:45 .
        // Split file
        $aExplodedRawListOutput = preg_split('/\s+/', $sFile);

        // Exploded is valid
        if (!isset($aExplodedRawListOutput[8])) {
            throw new RuntimeException(sprintf(
                'Unparseable raw list output %s',
                $sFile
            ));
        }

        // Parse raw list output
        list($sRights, $iNumber, $sUser, $sGroup, $iSize, $sMonth, $sDay, $sTime, $sName) = $aExplodedRawListOutput;

        // Get modification date
        $sModificationDate = sprintf(
            '%s %s %s',
            $sMonth,
            $sDay,
            $sTime
        );
        $oModificationDate = \DateTime::createFromFormat('F d H:i', $sModificationDate);

        // Modification date is valid
        if (!$oModificationDate) {
            throw new RuntimeException(sprintf(
                'Unparseable modification date %s',
                $sModificationDate
            ));
        }

        // Return
        return new File(
            sprintf(
                '%s/%s',
                $sPath,
                $sName
            ),
            $iSize,
            $oModificationDate
        );
    }

    protected function sortFiles(array &$aFiles, $iOrderField, $iOrderDirection)
    {
        if ($iOrderField !== OrderField::NONE) {
            // Initialize
            $aFilesToSort = [];

            // Loop through files
            /** @var $oFile \Asticode\FileManager\Entity\File */
            foreach ($aFiles as $oFile) {
                // Get key
                $sKey = $oFile->getOrderField($iOrderField);

                if (!isset($aFilesToSort[$sKey])) {
                    $aFilesToSort[$sKey] = [];
                }

                $aFilesToSort[$sKey][$oFile->getPath()] = $oFile;
            }

            // Sort
            if ($iOrderDirection === OrderDirection::ASC) {
                ksort($aFilesToSort);
            } else {
                krsort($aFilesToSort);
            }

            // Recreate files
            $aFiles = [];
            foreach ($aFilesToSort as $aFilesToSortWithSameKey) {
                $aFiles = array_merge($aFiles, array_values($aFilesToSortWithSameKey));
            }
        }
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
