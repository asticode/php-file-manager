<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Entity\CopyMethod;
use Asticode\FileManager\Entity\File;
use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use RuntimeException;

class Handler extends AbstractHandler
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
            new CopyMethod(
                Datasource::LOCAL,
                Datasource::LOCAL,
                [$this, 'copy']
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
        mkdir($sPath);
    }

    public function createFile($sPath)
    {
        $this->write('', $sPath);
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
        $aList = scandir($sPath);

        // Add file
        foreach ($aList as $sBasename) {
            // Initialize
            $oFile = $this->metadata(sprintf(
                '%s/%s',
                $sPath,
                $sBasename
            ));

            // Check extension
            if ($aAllowedExtensions === [] || in_array($oFile->getExtension(), $aAllowedExtensions)) {
                $aFiles[] = $oFile;
            }
        }

        // Order
        $this->sortFiles($aFiles, $iOrderField, $iOrderDirection);

        // Return
        return $aFiles;
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Execute
        if ($iWriteMethod === WriteMethod::APPEND) {
            file_put_contents($sPath, $sContent, FILE_APPEND);
        } else {
            file_put_contents($sPath, $sContent);
        }
    }

    public function read($sPath)
    {
        // Return
        return file_get_contents($sPath);
    }

    public function rename($sSourcePath, $sTargetPath)
    {
        // Execute
        rename($sSourcePath, $sTargetPath);
    }

    public function delete($sPath)
    {
        // Execute
        unlink($sPath);
    }

    public function copy($sSourcePath, $sTargetPath)
    {
        // Execute
        copy($sSourcePath, $sTargetPath);
    }
}
