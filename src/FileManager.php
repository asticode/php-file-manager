<?php
namespace Asticode\FileManager;

use Asticode\Toolbox\ExtendedArray;
use Asticode\Toolbox\ExtendedString;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\FileManager\Handler\HandlerInterface;
use RuntimeException;

class FileManager
{
    // Attributes
    private $aConfig;
    private $sNamespace;
    private $aHandlers;
    private $sDefaultHandlerName;
    private $aCopyMethods;

    // Construct
    public function __construct(array $aConfig, $sNamespace = 'Asticode\\FileManager\\Handler')
    {
        // Initialize
        $this->aConfig = $aConfig;
        $this->sNamespace = $sNamespace;
        $this->aHandlers = [];
        $this->sDefaultHandlerName = '';
        $this->aCopyMethods = [];
    }

    public function addHandler($sHandlerName, $sClassName, array $aConfig, $bDefaultHandler = false, $sNamespace = '')
    {
        // Get class name
        $sClassName = ExtendedString::toCamelCase(sprintf(
            '%s\\%sHandler',
            $sNamespace === '' ? $this->sNamespace : $sNamespace,
            $sClassName
        ), '_', true);

        // Class name is valid
        if (!class_exists($sClassName)) {
            throw new RuntimeException(sprintf(
                'Invalid class name %s',
                $sClassName
            ));
        }

        // Add handler
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        $oHandler = new $sClassName($aConfig);
        $this->aHandlers[$sHandlerName] = $oHandler;

        // Default handler
        if ($bDefaultHandler) {
            $this->sDefaultHandlerName = $sHandlerName;
        }

        // Add copy methods
        $this->aCopyMethods = array_merge($this->aCopyMethods, $oHandler->getCopyMethods());
    }

    public function setDefaultHandlerName($sHandlerName)
    {
        if (!isset($this->aHandlers[$sHandlerName])) {
            throw new RuntimeException(sprintf(
                'Invalid handler name %s',
                $sHandlerName
            ));
        }

        $this->sDefaultHandlerName = $sHandlerName;
    }

    /**
     * @param $sHandlerName
     * @return \Asticode\FileManager\Handler\HandlerInterface
     */
    public function getHandler($sHandlerName)
    {
        if (!isset($this->aHandlers[$sHandlerName])) {
            throw new RuntimeException(sprintf(
                'Invalid handler name %s',
                $sHandlerName
            ));
        }

        return $this->aHandlers[$sHandlerName];
    }

    /**
     * @param $sPath
     * @return array
     */
    public function parsePath($sPath)
    {
        // Parse path
        if (preg_match('/^([a-z_]+)\:\/(.+)/i', $sPath, $aMatches) > 0) {
            return [
                $aMatches[1],
                $aMatches[2],
            ];
        } else {
            return [
                ($this->sDefaultHandlerName !== '') ? $this->sDefaultHandlerName :
                    ExtendedArray::getFirstKey($this->aHandlers),
                $sPath
            ];
        }
    }

    public function exists($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        return $oHandler->exists($sPath);
    }

    public function metadata($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        return $oHandler->metadata($sPath);
    }

    public function createDir($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        $oHandler->createDir($sPath);
    }

    public function createFile($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        $oHandler->createFile($sPath);
    }

    public function explore(
        $sPath,
        $iOrderField = OrderField::NONE,
        $iOrderDirection = OrderDirection::ASC,
        array $aAllowedExtensions = [],
        array $aAllowedPatterns = []
    ) {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        return $oHandler->explore(
            $sPath,
            $iOrderField,
            $iOrderDirection,
            $aAllowedExtensions,
            $aAllowedPatterns
        );
    }

    public function searchPattern($sPattern, $sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        return $oHandler->searchPattern($sPattern, $sPath);
    }

    public function write($sContent, $sPath, $iWriteMethod = WriteMethod::APPEND)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        $oHandler->write($sContent, $sPath, $iWriteMethod);
    }

    public function read($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        return $oHandler->read($sPath);
    }

    public function rename($sSourcePath, $sTargetPath)
    {
        // Parse paths
        /** @var $oSourceHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sSourceHandlerName, $sSourcePath) = $this->parsePath($sSourcePath);
        $oSourceHandler = $this->getHandler($sSourceHandlerName);
        list ($sTargetHandlerName, $sTargetPath) = $this->parsePath($sTargetPath);

        // Execute
        $oSourceHandler->rename($sSourcePath, $sTargetPath);
    }

    public function delete($sPath)
    {
        // Parse path
        /** @var $oHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sHandlerName, $sPath) = $this->parsePath($sPath);
        $oHandler = $this->getHandler($sHandlerName);

        // Execute
        $oHandler->delete($sPath);
    }

    public function copy($sSourcePath, $sTargetPath)
    {
        // Parse paths
        /** @var $oSourceHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sSourceHandlerName, $sSourcePath) = $this->parsePath($sSourcePath);
        $oSourceHandler = $this->getHandler($sSourceHandlerName);
        /** @var $oTargetHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sTargetHandlerName, $sTargetPath) = $this->parsePath($sTargetPath);
        $oTargetHandler = $this->getHandler($sTargetHandlerName);

        // Choose best copy methods
        $aCopyMethods = $this->chooseBestCopyMethods($oSourceHandler, $oTargetHandler);

        // No copy method has been found
        if ($aCopyMethods === []) {
            throw new RuntimeException(sprintf(
                'No copy methods found to copy from %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }

        // Loop through copy methods
        /** @var $oCopyMethod \Asticode\FileManager\Entity\CopyMethod */
        foreach ($aCopyMethods as $oCopyMethod) {
            call_user_func_array($oCopyMethod->getCallable(), [$sSourcePath, $sTargetPath]);
        }
    }

    public function move($sSourcePath, $sTargetPath)
    {
        // Copy
        $this->copy($sSourcePath, $sTargetPath);

        // Delete
        $this->delete($sSourcePath);
    }

    private function chooseBestCopyMethods(HandlerInterface $oSourceHandler, HandlerInterface $oTargetHandler)
    {
        // Initialize
        $aCopyMethods = [];

        // Loop through file handler copy methods
        /** @var $oCopyMethod \Asticode\FileManager\Entity\CopyMethod */
        foreach ($this->aCopyMethods as $oCopyMethod) {
            if ($oCopyMethod->getSourceDatasource() === $oSourceHandler->getDatasource()
                and $oCopyMethod->getTargetDatasource() === $oTargetHandler->getDatasource()) {
                $aCopyMethods = [$oCopyMethod];
            }
        }

        // Return
        return $aCopyMethods;
    }
}
