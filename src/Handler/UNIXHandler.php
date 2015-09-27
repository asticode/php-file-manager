<?php
namespace Asticode\FileManager\Handler;

use Asticode\Toolbox\ExtendedArray;
use Asticode\Toolbox\ExtendedString;
use Asticode\FileManager\Entity\CopyMethod;
use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\Toolbox\ExtendedShell;
use RuntimeException;

class UNIXHandler extends AbstractHandler
{
    // Attributes
    private $aConfig;

    // Construct
    public function __construct(array $aConfig)
    {
        // Initialize
        $this->aConfig = $aConfig;

        // Default values
        $this->aConfig = ExtendedArray::extendWithDefaultValues(
            $this->aConfig,
            [
                'timeout' => 0,
            ]
        );

        // Check config required attributes
        ExtendedArray::checkRequiredKeys(
            $this->aConfig,
            [
                'timeout',
            ]
        );
    }

    public function getDatasource()
    {
        return Datasource::LOCAL;
    }

    public function getCopyMethods()
    {
        return [
            new CopyMethod(
                Datasource::LOCAL,
                Datasource::LOCAL,
                [$this, 'copy']
            ),
        ];
    }

    private function escapeSingleQuotes($sInput)
    {
        return ExtendedString::escape(
            $sInput,
            "'",
            "'\\%s'"
        );
    }

    private function exec($sCommand)
    {
        // Execute
        list (
            $aOutputArray,
            $aErrorArray
        ) = ExtendedShell::exec($sCommand, $this->aConfig['timeout']);

        // Error
        if (!empty($aErrorArray)) {
            throw new RuntimeException(sprintf(
                'Error while executing %s => %s',
                $sCommand,
                implode("\n", $aErrorArray)
            ));
        }

        // Return
        return $aOutputArray;
    }

    public function metadata($sPath)
    {
        // Execute
        $aOutputArray = $this->exec(sprintf(
            'ls -dl \'%s\'',
            $this->escapeSingleQuotes($sPath)
        ));

        // Parse
        try {
            return $this->parseRawList(isset($aOutputArray[0]) ? $aOutputArray[0] : '', dirname($sPath));
        } catch (RuntimeException $oException) {
            throw new RuntimeException(sprintf(
                'Path %s doesn\'t exist with message %s',
                $sPath,
                $oException->getMessage()
            ));
        }
    }

    public function createDir($sPath)
    {
        // Execute
        $this->exec(sprintf(
            'mkdir -p \'%s\'',
            $this->escapeSingleQuotes($sPath)
        ));
    }

    public function createFile($sPath)
    {
        // Execute
        $this->exec(sprintf(
            'touch \'%s\'',
            $this->escapeSingleQuotes($sPath)
        ));
    }

    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = []
    ) {
        // Initialize
        $aFiles = [];

        // Get files
        $aList = $this->exec(sprintf(
            'ls -l \'%s\'',
            $this->escapeSingleQuotes($sPath)
        ));

        // Remove total
        if (isset($aList[0]) && preg_match('/^total[\s]+[0-9]+/', $aList[0]) > 0) {
            array_shift($aList);
        }

        // Add file
        foreach ($aList as $sFile) {
            // Initialize
            $oFile = self::parseRawList($sFile, $sPath);

            // Check extension
            if (!in_array($oFile->getBasename(), ['.', '..'])) {
                if ($aAllowedExtensions === [] || in_array($oFile->getExtension(), $aAllowedExtensions)) {
                    $aFiles[] = $oFile;
                }
            }
        }

        // Order
        $this->sortFiles($aFiles, $iOrderField, $iOrderDirection);

        // Return
        return $aFiles;
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Get operator
        if ($iWriteMethod === WriteMethod::APPEND) {
            $sOperator = '>>';
        } else {
            $sOperator = '>';
        }

        // Execute
        $this->exec(sprintf(
            'echo \'%s\' %s \'%s\'',
            $this->escapeSingleQuotes($sContent),
            $sOperator,
            $this->escapeSingleQuotes($sPath)
        ));
    }

    public function read($sPath)
    {
        // Execute
        return implode("\n", $this->exec(sprintf(
            'cat \'%s\'',
            $this->escapeSingleQuotes($sPath)
        )));
    }

    public function rename($sSourcePath, $sTargetPath)
    {
        // Execute
        $this->exec(sprintf(
            'mv \'%s\' \'%s\'',
            $this->escapeSingleQuotes($sSourcePath),
            $this->escapeSingleQuotes($sSourcePath)
        ));
    }

    public function delete($sPath)
    {
        // Execute
        $this->exec(sprintf(
            'rm -rf \'%s\'',
            $this->escapeSingleQuotes($sPath)
        ));
    }

    public function copy($sSourcePath, $sTargetPath)
    {
        // Execute
        $this->exec(sprintf(
            'cp \'%s\' \'%s\'',
            $this->escapeSingleQuotes($sSourcePath),
            $this->escapeSingleQuotes($sTargetPath)
        ));
    }
}
