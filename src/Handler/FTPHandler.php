<?php
namespace Asticode\FileManager\Handler;

use Asticode\FileManager\Enum\ObjectType;
use Asticode\Toolbox\ExtendedArray;
use Asticode\FileManager\Entity\CopyMethod;
use Asticode\FileManager\Enum\Datasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Exception;
use RuntimeException;

class FTPHandler extends AbstractHandler
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

    private function curlInit()
    {
        // Initialize
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_PORT, $this->aConfig['port']);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->aConfig['timeout']);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->aConfig['timeout']);

        // Add user and password
        if (isset($this->aConfig['username']) and isset($this->aConfig['password'])) {
            curl_setopt($oCurl, CURLOPT_USERPWD, sprintf(
                '%s:%s',
                $this->aConfig['username'],
                $this->aConfig['password']
            ));
        }

        // Return
        return $oCurl;
    }

    private function curlExec(
        $oCurl,
        $sPath,
        $iObjectTypeId = ObjectType::FILE,
        $sCustomRequest = '',
        $aPostCommands = []
    ) {
        // Set URL
        $sUrl = $this->getFullPath($sPath, $iObjectTypeId);
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);

        // Set custom request
        if ($sCustomRequest !== '') {
            curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $sCustomRequest);
        }

        // Set post quote
        if ($aPostCommands !== []) {
            curl_setopt($oCurl, CURLOPT_POSTQUOTE, $aPostCommands);
        }

        // Exec
        $sResponse = curl_exec($oCurl);

        // Failure
        if (curl_errno($oCurl) > 0) {
            throw new RuntimeException(sprintf(
                'Error while executing "%s" on %s with curl error #%s and curl error message "%s"',
                $sCustomRequest === '' ? implode('","', $aPostCommands) : $sCustomRequest,
                $sUrl,
                curl_errno($oCurl),
                curl_error($oCurl)
            ));
        }

        // Return
        return $sResponse;
    }

    private function getFullPath($sPath, $iObjectType)
    {
        // Build path
        $sPath = sprintf(
            'ftp://%s%s',
            $this->aConfig['host'],
            $sPath
        );

        // Add trailing slash
        if ($iObjectType === ObjectType::DIRECTORY) {
            $sPath .= '/';
        }

        // Return
        return $sPath;
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

    public function metadata($sPath)
    {
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

    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = [],
        array $aAllowedPatterns = []
    ) {
        // Initialize
        $aFiles = [];

        // Get CURL
        $oCurl = $this->curlInit();

        // Execute CURL
        $sResponse = $this->curlExec($oCurl, '', ObjectType::DIRECTORY, 'LIST -a');

        // Get files
        $aList = explode("\n", $sResponse);

        // Add file
        foreach ($aList as $sFile) {
            if ($sFile !== '') {
                // Initialize
                $oFile = self::parseRawList($sFile, $sPath);

                // Filter file
                $this->filterFile($aFiles, $oFile, $aAllowedExtensions, $aAllowedPatterns);
            }
        }

        // Order
        $this->sortFiles($aFiles, $iOrderField, $iOrderDirection);

        // Return
        return $aFiles;
    }

    public function createDir($sPath)
    {
        // Get CURL
        $oCurl = $this->curlInit();

        // Execute CURL
        curl_setopt($oCurl, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
        $this->curlExec($oCurl, $sPath, ObjectType::DIRECTORY);
    }

    public function createFile($sPath)
    {
        // Write
        $this->write('', $sPath);
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Create source path
        $sSourcePath = tempnam(sys_get_temp_dir(), 'asticode_filehandler_');
        file_put_contents($sSourcePath, $sContent);

        // Upload
        $this->upload($sSourcePath, $sPath);
    }

    public function read($sPath)
    {
        // Initialize
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
        // Get CURL
        $oCurl = $this->curlInit();

        // Execute CURL
        $this->curlExec(
            $oCurl,
            '',
            ObjectType::FILE,
            '',
            [
                sprintf('RNFR %s', $sSourcePath),
                sprintf('RNTO %s', $sTargetPath),
            ]
        );
    }

    public function download($sSourcePath, $sTargetPath)
    {
        // Initialize
        $oFile = fopen($sTargetPath, 'w');

        // Get CURL
        $oCurl = $this->curlInit();

        // Download
        curl_setopt($oCurl, CURLOPT_FILE, $oFile);
        try {
            $this->curlExec($oCurl, $sSourcePath);
        } catch (Exception $oException) {
            // Close file
            fclose($oFile);

            // Throw
            throw $oException;
        }

        // Close file
        fclose($oFile);
    }

    public function upload($sSourcePath, $sTargetPath)
    {
        // Initialize
        $oFile = fopen($sSourcePath, 'r');

        // Get CURL
        $oCurl = $this->curlInit();

        // Download
        curl_setopt($oCurl, CURLOPT_UPLOAD, true);
        curl_setopt($oCurl, CURLOPT_INFILE, $oFile);
        curl_setopt($oCurl, CURLOPT_INFILESIZE, filesize($sSourcePath));
        try {
            $this->curlExec($oCurl, $sTargetPath);
        } catch (Exception $oException) {
            // Close file
            fclose($oFile);

            // Throw
            throw $oException;
        }

        // Close file
        fclose($oFile);
    }

    public function delete($sPath)
    {
        // Get CURL
        $oCurl = $this->curlInit();

        // Execute CURL
        $this->curlExec(
            $oCurl,
            '',
            ObjectType::FILE,
            '',
            [
                sprintf('DELE %s', $sPath),
            ]
        );
    }

}
