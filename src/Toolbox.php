<?php
namespace Asticode\FileManager;

use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\Toolbox\ExtendedArray;
use RuntimeException;

class Toolbox
{

    const DATE_FORMATS = [
        'F d H:i',
        'F d Y',
        'd F H:i',
    ];

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

    /**
     * Parse a raw list like a UNIX or FTP list
     * @param string $sRawList Raw list
     * @param string $sPath Path of the list
     * @return File
     */
    public static function parseRawList($sRawList, $sPath)
    {
        // Explode raw list
        list($sRights, $iNumber, $sUser, $sGroup, $iSize, $sDateItem1, $sDateItem2, $sDateItem3, $sName) = array_pad(
            preg_split('/\s+/', $sRawList),
            9,
            ''
        );

        // Get modification date as a string
        $sModificationDate = sprintf(
            '%s %s %s',
            $sDateItem1,
            $sDateItem2,
            $sDateItem3
        );

        // Create modification date as an object
        $oModificationDate = false;
        foreach (self::DATE_FORMATS as $sDateFormat) {
            $oModificationDate = \DateTime::createFromFormat($sDateFormat, $sModificationDate);
            if ($oModificationDate) {
                break;
            }
        }

        // Modification date is valid
        if (!$oModificationDate) {
            throw new RuntimeException(sprintf(
                'Raw modification date <%s> is not any of the following formats <%s> for raw list <%s>',
                $sModificationDate,
                implode(',', self::DATE_FORMATS),
                $sRawList
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

    /**
     * Sort an array of files
     * @param array $aFiles Array of files
     * @param int $iOrderField Order field
     * @param int $iOrderDirection Order direction. ASC by default.
     */
    public static function sortFiles(array &$aFiles, $iOrderField, $iOrderDirection = OrderDirection::ASC)
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

    /**
     * Add a file to an array of files depending on extension or pattern conditions
     * @param array $aFiles Array of files
     * @param File $oFile File to be added
     * @param array $aAllowedExtensions Allowed extensions
     * @param array $aAllowedBasenamePatterns Allowed basename patterns
     */
    public static function addFile(array &$aFiles, File $oFile, array $aAllowedExtensions = [], array $aAllowedBasenamePatterns = [])
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
        if ($aAllowedBasenamePatterns !== []) {
            // Initialize
            $bIsValid = false;

            // Loop through allowed patterns
            foreach ($aAllowedBasenamePatterns as $sAllowedPattern) {
                if (preg_match(sprintf('/%s/', $sAllowedPattern), $oFile->getBasename()) > 0) {
                    $bIsValid = true;
                    break;
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

}
