<?php
declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Sabre;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use OCA\Files_External_Ethswarm\Service\EthswarmService;

class PostPlugin extends ServerPlugin {
    /** @var Server */
    private $server;

    /** @var EthswarmService */
    private $EthswarmService;

    public function __construct(EthswarmService $service) {
        $this->EthswarmService = $service;
    }

    public function initialize(Server $server) {
        $this->server = $server;
        $this->server->on('method:POST', [$this, 'httpPost']);
    }

    public function httpPost(RequestInterface $request, ResponseInterface $response)
    {
        $action = $request->getRawServerValue('HTTP_HEJBIT_ACTION') ?? null;

        if ($action === 'unview') {
            $path = $request->getPath();
            $node = $this->server->tree->getNodeForPath($path);
            if (($node instanceof \OCA\DAV\Connector\Sabre\File)) {
                $storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
           	 	$filename = $node->getFileInfo()->getinternalPath();
            }
			if (($node instanceof \OCA\DAV\Connector\Sabre\Directory)) {
                $storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
           	 	$filename = $node->getFileInfo()->getinternalPath();
            }

            $class = $this->EthswarmService;

            $class->setVisiblity($filename, $storageid, 0);

            $response->setStatus(200);
            $response->setHeader('Content-Length', '0');

            return false;
        } else {
            $exMessage = 'There was no plugin in the system that was willing to handle a POST method with this action > '.$action.'.';
            throw new \Sabre\DAV\Exception\NotImplemented($exMessage);
        }
    }
}
