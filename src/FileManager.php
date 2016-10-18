<?php
namespace Asticode\FileManager;

use Asticode\FileManager\Entity\FileMethod;
use Asticode\FileManager\Entity\Vertex;
use Asticode\FileManager\Enum\Datasource;
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
    private $aMoveMethods;

    // Construct
    public function __construct(array $aConfig, $sNamespace = 'Asticode\\FileManager\\Handler')
    {
        // Initialize
        $this->aConfig = $aConfig;
        $this->sNamespace = $sNamespace;
        $this->aHandlers = [];
        $this->sDefaultHandlerName = '';
        $this->aCopyMethods = [];
        $this->aMoveMethods = [];
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

        // Add file methods
        $this->aCopyMethods = array_merge($this->aCopyMethods, $oHandler->getCopyMethods());
        $this->aMoveMethods = array_merge($this->aMoveMethods, $oHandler->getMoveMethods());
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
        array $aAllowedBasenamePatterns = []
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
            $aAllowedBasenamePatterns
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

    private function executeFileMethods($sSourcePath, $sTargetPath, array $aFileMethods)
    {
        // Loop through copy methods
        /** @var FileMethod $oFileMethod1 */
        /** @var FileMethod $oFileMethod2 */
        list($oFileMethod1, $oFileMethod2) = $aFileMethods;
        if (count($aFileMethods) === 1) {
            // Only one method, everything should be fine
            call_user_func_array($oFileMethod1->getCallable(), [$sSourcePath, $sTargetPath]);
            return;
        } elseif (count($aFileMethods) === 2) {
            // Two methods here
            // If middle method is not local, problems will arise :(
            if ($oFileMethod1->getTargetDatasource() === Datasource::LOCAL) {
                $sTempPath = tempnam(sys_get_temp_dir(), "file_manager_");
                call_user_func_array($oFileMethod1->getCallable(), [$sSourcePath, $sTempPath]);
                call_user_func_array($oFileMethod2->getCallable(), [$sTempPath, $sTargetPath]);
                unlink($sTempPath);
            }
        }

        // Throw an error by default
        throw new RuntimeException(sprintf(
            "Couldn't execute file methods from %s to %s using %d file methods",
            $sSourcePath,
            $sTargetPath,
            count($aFileMethods)
        ));
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
        $aCopyMethods = $this->chooseBestFileMethods($oSourceHandler, $oTargetHandler, $this->aCopyMethods);

        // No copy method has been found
        if ($aCopyMethods === []) {
            throw new RuntimeException(sprintf(
                'No copy methods found to copy from %s to %s',
                $sSourcePath,
                $sTargetPath
            ));
        }

        // Execute copy methods
        $this->executeFileMethods($sSourcePath, $sTargetPath, $aCopyMethods);
    }

    public function move($sSourcePath, $sTargetPath)
    {
        // Parse paths
        /** @var $oSourceHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sSourceHandlerName, $sSourcePath) = $this->parsePath($sSourcePath);
        $oSourceHandler = $this->getHandler($sSourceHandlerName);
        /** @var $oTargetHandler \Asticode\FileManager\Handler\HandlerInterface */
        list ($sTargetHandlerName, $sTargetPath) = $this->parsePath($sTargetPath);
        $oTargetHandler = $this->getHandler($sTargetHandlerName);

        // Choose best move methods
        $aMoveMethods = $this->chooseBestFileMethods($oSourceHandler, $oTargetHandler, $this->aMoveMethods);

        // Move methods have been found
        if ($aMoveMethods !== []) {
            // Execute copy methods
            $this->executeFileMethods($sSourcePath, $sTargetPath, $aMoveMethods);
        } else {
            // Copy
            $this->copy($sSourcePath, $sTargetPath);

            // Delete
            $this->delete($sSourcePath);
        }
    }

    public function chooseBestFileMethods(
        HandlerInterface $oSourceHandler,
        HandlerInterface $oTargetHandler,
        array $aMethods
    ) {
        // Get start vertex
        $oVertexStart = new Vertex($oSourceHandler->getDatasource());
        $oVertexStart->setVisited(true);
        $oVertexStart->setDistance(0);

        // Clone copy methods
        /** @var FileMethod $oCopyMethod */
        $aCopyMethods = [];
        foreach ($aMethods as $oCopyMethod) {
            // Clone
            $aCopyMethods[] = clone $oCopyMethod;

            // Check whether this copy method is sufficient
            if ($oCopyMethod->getSourceDatasource() === $oSourceHandler->getDatasource()
                && $oCopyMethod->getTargetDatasource() === $oTargetHandler->getDatasource()) {
                return [$oCopyMethod];
            }
        }

        // Compute paths
        $aQueue = [$oVertexStart];
        $aVertexes = [];
        while ($aQueue) {
            // Get last vertex from the queue
            /** @var Vertex $oLastVertexFromQueue */
            $oLastVertexFromQueue = array_pop($aQueue);

            // Find appropriate copy methods
            foreach ($aCopyMethods as $oCopyMethod) {
                // Copy method is appropriate
                if ($oCopyMethod->getSourceDatasource() === $oLastVertexFromQueue->getDatasource()) {
                    // Loop through copy method vertexes
                    while ($oCopyMethod->getDoublyLinkedList()->valid()) {
                        /** @var Vertex $oCurrentVertex */
                        $oCurrentVertex = $oCopyMethod->getDoublyLinkedList()->current();
                        if (!$oCurrentVertex->isVisited()) {
                            $oCurrentVertex->setVisited(true);
                            $oVertex = clone $oCurrentVertex;
                            $oVertex->setDistance($oLastVertexFromQueue->getDistance() + 1);
                            $aPath = $oLastVertexFromQueue->getPath();
                            array_push($aPath, $oCopyMethod);
                            $oVertex->setPath($aPath);
                            array_push($aQueue, $oVertex);
                            array_push($aVertexes, $oVertex);
                        }
                        $oCopyMethod->getDoublyLinkedList()->next();
                    }
                }
            }
        }

        // Look for shortest path
        $oShortestPath = [];
        $iMinPathDistance = -1;
        /** @var Vertex $oVertex */
        foreach ($aVertexes as $oVertex) {
            /** @var FileMethod $oFirstCopyMethod */
            $oFirstCopyMethod = $oVertex->getPath()[0];
            /** @var FileMethod $oLastCopyMethod */
            $oLastCopyMethod = $oVertex->getPath()[count($oVertex->getPath()) - 1];

            // Valid path
            if ($oFirstCopyMethod->getSourceDatasource() === $oSourceHandler->getDatasource()
                && $oLastCopyMethod->getTargetDatasource() === $oTargetHandler->getDatasource()
                && ($iMinPathDistance === -1 || $iMinPathDistance > $oVertex->getDistance())) {
                $oShortestPath = $oVertex->getPath();
            }
        }

        // Return
        return $oShortestPath;
    }
}
