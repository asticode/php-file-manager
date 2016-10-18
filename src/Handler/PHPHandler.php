<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Entity\FileMethod;
use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\FileManager\Toolbox;
use RuntimeException;

class PHPHandler extends AbstractHandler
{
    // Attributes
    private $aConfig;

    // Construct
    public function __construct(array $aConfig)
    {
        // Initialize
        $this->aConfig = $aConfig;
    }

    public function getDatasource()
    {
        return Datasource::LOCAL;
    }

    public function getCopyMethods()
    {
        return [
            new FileMethod(
                Datasource::LOCAL,
                Datasource::LOCAL,
                [$this, 'copy']
            ),
        ];
    }

    public function getMoveMethods()
    {
        return [
            new FileMethod(
                Datasource::LOCAL,
                Datasource::LOCAL,
                [$this, 'rename']
            ),
        ];
    }

    public function exists($sPath)
    {
        return file_exists($sPath);
    }

    public function metadata($sPath)
    {
        if ($this->exists($sPath)) {
            return new File(
                $sPath,
                filesize($sPath),
                \DateTime::createFromFormat('U', filectime($sPath))
            );
        } else {
            throw new RuntimeException(sprintf(
                'Path %s doesn\'t exist',
                $sPath
            ));
        }
    }

    public function createDir($sPath)
    {
        // Check if dir exists
        if (is_dir($sPath)) {
            return;
        }

        // Create dir
        $bSuccess = mkdir($sPath, 0750, true);

        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while creating directory %s',
                $sPath
            ));
        }
    }

    public function createFile($sPath)
    {
        $this->write('', $sPath);
    }

    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = [],
        array $aAllowedBasenamePatterns = []
    ) {
        // Initialize
        $aFiles = [];

        // Get files
        $aList = scandir($sPath);

        if (!$aList) {
            throw new RuntimeException(sprintf(
                'Error while exploring %s',
                $sPath
            ));
        }

        // Add file
        foreach ($aList as $sBasename) {
            // Create file
            $oFile = $this->metadata(sprintf(
                '%s/%s',
                $sPath,
                $sBasename
            ));

            // Add file
            Toolbox::addFile($aFiles, $oFile, $aAllowedExtensions, $aAllowedBasenamePatterns);
        }

        // Order
        Toolbox::sortFiles($aFiles, $iOrderField, $iOrderDirection);

        // Return
        return $aFiles;
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Execute
        if ($iWriteMethod === WriteMethod::APPEND) {
            $bSuccess = file_put_contents($sPath, $sContent, FILE_APPEND);
        } else {
            $bSuccess = file_put_contents($sPath, $sContent);
        }

        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while writing to %s',
                $sPath
            ));
        }
    }

    public function read($sPath)
    {
        // Return
        $sResult = file_get_contents($sPath);

        if (!$sResult) {
            throw new RuntimeException(sprintf(
                'Error while reading %s',
                $sPath
            ));
        }

        // Return
        return $sResult;
    }

    public function rename($sSourcePath, $sTargetPath)
    {
        // Execute
        $bSuccess = rename($sSourcePath, $sTargetPath);

        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while renaming %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }
    }

    public function delete($sPath)
    {
        // Execute
        $bSuccess = unlink($sPath);

        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while deleting %s',
                $sPath
            ));
        }
    }

    public function copy($sSourcePath, $sTargetPath)
    {
        // Execute
        $bSuccess = copy($sSourcePath, $sTargetPath);

        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while copying %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }
    }
}
