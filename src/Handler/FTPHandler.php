<?php
namespace Asticode\FileManager\Handler;

use Asticode\Toolbox\ExtendedArray;
use Asticode\FileManager\Entity\CopyMethod;
use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use RuntimeException;

class FTPHandler extends AbstractHandler
{
    // Attributes
    private $rHandle;
    private $iLastConnectionTimestamp;
    private $aConfig;

    // Construct
    public function __construct(array $aConfig)
    {
        // Initialize
        $this->rHandle = false;
        $this->aConfig = $aConfig;

        // Default values
        $this->aConfig = ExtendedArray::extendWithDefaultValues(
            $this->aConfig,
            [
                'port' => 21,
                'proxy' => '',
                'timeout' => 90,
            ]
        );

        // Check config required attributes
        ExtendedArray::checkRequiredKeys(
            $this->aConfig,
            [
                'host',
            ]
        );
    }

    public function getDatasource()
    {
        return Datasource::FTP;
    }

    public function getCopyMethods()
    {
        return [
            new CopyMethod(
                Datasource::FTP,
                Datasource::LOCAL,
                [$this, 'download']
            ),
            new CopyMethod(
                Datasource::LOCAL,
                Datasource::FTP,
                [$this, 'upload']
            ),
        ];
    }

    public function connect()
    {
        // Connect
        if (!$this->rHandle || (time() - $this->iLastConnectionTimestamp) > $this->aConfig['timeout']) {
            // Connect
            $this->rHandle = ftp_connect(
                $this->aConfig['host'],
                $this->aConfig['port'],
                $this->aConfig['timeout']
            );

            // Handle is not valid
            if (!$this->rHandle) {
                throw new RuntimeException(sprintf(
                    'Error while executing ftp_connect to %s:%s with timeout %s',
                    $this->aConfig['host'],
                    $this->aConfig['port'],
                    $this->aConfig['timeout']
                ));
            }

            // Login
            if (isset($this->aConfig['username']) and isset($this->aConfig['password'])) {
                // Login
                $bSuccess = ftp_login(
                    $this->rHandle,
                    $this->aConfig['username'],
                    $this->aConfig['password']
                );

                // Failure
                if (!$bSuccess) {
                    throw new RuntimeException(sprintf(
                        'Error while executing ftp_login with credentials %s:%s',
                        $this->aConfig['username'],
                        $this->aConfig['password']
                    ));
                }
            }

            // Passive mode
            ftp_pasv($this->rHandle, true);

            // Update last connection timestamp
            $this->iLastConnectionTimestamp = time();
        }
    }

    public function disconnect()
    {
        // Handle is set
        if ($this->rHandle) {
            // Close
            $bSuccess = ftp_close($this->rHandle);

            // Failure
            if (!$bSuccess) {
                throw new RuntimeException('Error while executing ftp_close');
            }
        }

        // Update handle
        $this->rHandle = false;
    }

    public function exists($sPath)
    {
        // Initialize
        $this->connect();

        // Parent exists
        return parent::exists($sPath);
    }

    public function metadata($sPath)
    {
        // Initialize
        $this->connect();

        // Get files
        $aFiles = $this->searchPattern(sprintf(
            '/^%s$/',
            basename($sPath)
        ), dirname($sPath));

        // Path exists
        if ($aFiles !== []) {
            return $aFiles[0];
        } else {
            throw new RuntimeException(sprintf(
                'Path %s doesn\'t exist',
                $sPath
            ));
        }
    }

    public function createDir($sPath)
    {
        // Initialize
        $this->connect();

        // Mkdir
        $bSuccess = ftp_mkdir($this->rHandle, $sPath);

        // Failure
        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while executing ftp_mkdir at %s',
                $sPath
            ));
        }
    }

    public function createFile($sPath)
    {
        // Initialize
        $this->connect();

        // Write
        $this->write('', $sPath);
    }

    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = []
    ) {
        // Initialize
        $this->connect();
        $aFiles = [];

        // Get files
        $aList = ftp_rawlist($this->rHandle, $sPath);

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

    public function searchPattern($sPattern, $sPath)
    {
        // Initialize
        $this->connect();

        // Parent search pattern
        return parent::searchPattern($sPattern, $sPath);
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Initialize
        $this->connect();

        // Create source path
        $sSourcePath = tempnam(sys_get_temp_dir(), 'asticode_filehandler_');
        file_put_contents($sSourcePath, $sContent);

        // Get start position
        if ($iWriteMethod === WriteMethod::APPEND) {
            // Get remote file content length
            try {
                $iStartPosition = strlen($this->read($sPath));
            } catch (RuntimeException $oException) {
                $iStartPosition = 0;
            }
        } else {
            $iStartPosition = 0;
        }

        try {
            // Upload
            $this->upload($sPath, $sSourcePath, $iStartPosition);

            // Remove temp file
            unlink($sSourcePath);
        } catch (RuntimeException $oException) {
            // Remove temp file
            unlink($sSourcePath);

            // Throw exception
            throw $oException;
        }
    }

    public function read($sPath)
    {
        // Initialize
        $this->connect();
        $sTargetPath = tempnam(sys_get_temp_dir(), 'asticode_filehandler_');

        // Download
        $this->download($sPath, $sTargetPath);

        // Get content
        $sContent = file_get_contents($sTargetPath);

        // Remove temp file
        unlink($sTargetPath);

        // Return
        return $sContent;
    }

    public function rename($sSourcePath, $sTargetPath)
    {
        // Initialize
        $this->connect();

        // Delete
        $bSuccess = ftp_rename($this->rHandle, $sSourcePath, $sTargetPath);

        // Failure
        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while executing ftp_rename from %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }
    }

    public function delete($sPath)
    {
        // Initialize
        $this->connect();

        // Delete
        $bSuccess = ftp_delete($this->rHandle, $sPath);

        // Failure
        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while executing ftp_delete on %s',
                $sPath
            ));
        }
    }

    public function download($sSourcePath, $sTargetPath)
    {
        // Initialize
        $this->connect();

        // Delete
        $bSuccess = ftp_get($this->rHandle, $sTargetPath, $sSourcePath, FTP_BINARY);

        // Failure
        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while executing ftp_get from %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }
    }

    public function upload($sSourcePath, $sTargetPath, $iStartPosition = 0)
    {
        // Initialize
        $this->connect();

        // Delete
        $bSuccess = ftp_put($this->rHandle, $sTargetPath, $sSourcePath, FTP_BINARY, $iStartPosition);

        // Failure
        if (!$bSuccess) {
            throw new RuntimeException(sprintf(
                'Error while executing ftp_put from %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }
    }

}
