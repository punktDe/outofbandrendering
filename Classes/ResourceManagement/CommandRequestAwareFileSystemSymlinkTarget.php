<?php
namespace PunktDe\OutOfBandRendering\ResourceManagement;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Cli\CommandRequestHandler;
use Neos\Flow\ResourceManagement\Target\FileSystemSymlinkTarget;

class CommandRequestAwareFileSystemSymlinkTarget extends FileSystemSymlinkTarget
{
    protected function detectResourcesBaseUri()
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();

        if ($requestHandler instanceof CommandRequestHandler) {
            return '/' . $this->baseUri;
        }

        return parent::detectResourcesBaseUri();
    }
}