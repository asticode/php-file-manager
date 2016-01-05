<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use RuntimeException;

abstract class AbstractHandler implements HandlerInterface
{

    private static $aDateFormats = [
        'F d H:i',
        'F d Y'
    ];

    public function getCopyMethods()
    {
        return [];
    }

    protected static function parseRawList($sFile, $sPath)
    {
        // UNIX => drwxr-xr-x 2 zmifwezd zmifwezd 4096 Sep 22 14:45 .
        // UNIX (2) => drwxr-xr-x 2 zmifwezd zmifwezd 4096 Sep 22 2015 .
        // OSX  => drwxr-xr-x 2 zmifwezd zmifwezd 4096 22 sep 14:45 .
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

        // Switch month and day for OSX
        if (intval($sDay) === 0) {
            $sTemp = $sMonth;
            $sMonth = $sDay;
            $sDay = $sTemp;
        }

        // Get modification date as a string
        $sModificationDate = sprintf(
            '%s %s %s',
            $sMonth,
            $sDay,
            $sTime
        );

        // Create modification date as an object
        $oModificationDate = false;
        foreach (self::$aDateFormats as $sDateFormat) {
            $oModificationDate = \DateTime::createFromFormat($sDateFormat, $sModificationDate);
            if ($oModificationDate) {
                break;
            }
        }

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

    protected function filterFile(array &$aFiles, File $oFile, array $aAllowedExtensions, array $aAllowedPatterns)
    {
        // Do not process . and ..
        if (in_array($oFile->getBasename(), ['.', '..'])) {
            return;
        }

        // Filter allowed extensions
        if ($aAllowedExtensions !== [] and !in_array($oFile->getExtension(), $aAllowedExtensions)) {
            return;
        }

        // Filter allowed patterns
        if ($aAllowedPatterns !== []) {
            // Initialize
            $bIsValid = false;

            // Loop through allowed patterns
            foreach ($aAllowedPatterns as $sAllowedPattern) {
                if (preg_match(sprintf('/%s/', $sAllowedPattern), $oFile->getBasename()) > 0) {
                    $bIsValid = true;
                }
            }

            // Invalid
            if (!$bIsValid) {
                return;
            }
        }

        // Add file
        $aFiles[] = $oFile;
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
