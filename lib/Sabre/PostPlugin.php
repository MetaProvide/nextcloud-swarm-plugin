<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Sabre;

use Exception;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\Files_External_Ethswarm\Service\EthswarmService;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PostPlugin extends ServerPlugin
{
	/** @var Server */
	private $server;

	/** @var EthswarmService */
	private $EthswarmService;

	public function __construct(EthswarmService $service)
	{
		$this->EthswarmService = $service;
	}

	public function initialize(Server $server)
	{
		$this->server = $server;
		$this->server->on('method:POST', [$this, 'httpPost']);
		$this->server->on('method:MOVE', [$this, 'httpMove']);
	}

	public function httpPost(RequestInterface $request, ResponseInterface $response): bool
	{
		$action = $request->getRawServerValue('HTTP_HEJBIT_ACTION');

		if (!$action) {
			return true;
		}

		try {
			switch ($action) {
				case 'hide':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}
					if ($node instanceof Directory) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}

					$this->EthswarmService->setVisibility($filename, $storageid, 0);

					break;

				case 'unhide':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}
					if ($node instanceof Directory) {
						$storageid = $node->getFileInfo()->getStorage()->getCache()->getNumericStorageId();
						$filename = $node->getFileInfo()->getinternalPath();
					}

					$this->EthswarmService->setVisibility($filename, $storageid, 1);

					break;

				case 'archive':
					$path = $request->getPath();
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File || $node instanceof Directory) {
						$fileInfo = $node->getFileInfo();
						$filename = $fileInfo->getInternalPath();
						$storage = $fileInfo->getMountPoint()->getStorage();
						$this->EthswarmService->archiveNode($filename, $storage);
					}

					break;

				case 'unarchive' || 'move':
					$path = $request->getPath();
					$destination = $request->getHeader('Destination');
					$node = $this->server->tree->getNodeForPath($path);
					if ($node instanceof File || $node instanceof Directory) {
						$fileInfo = $node->getFileInfo();
						$filename = $fileInfo->getInternalPath();
						$storage = $fileInfo->getMountPoint()->getStorage();
						$this->EthswarmService->moveNode($filename, $storage, $destination);
					}

					break;

				default:
					throw new NotImplemented('Action not implemented');
			}

			$response->setBody(json_encode([
				'status' => true,
				'message' => 'success',
			]));
		} catch (Exception $ex) {
			$response->setBody(json_encode([
				'status' => false,
				'message' => $ex->getMessage(),
			]));
		}

		$response->setHeader('Content-Type', 'application/json');
		$response->setStatus(200);

		return false;
	}

	public function httpMove(RequestInterface $request, ResponseInterface $response): bool
	{
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		$nodeInfo = $node->getFileInfo();
		$fileName = $nodeInfo->getInternalPath();
		$destination = $request->getHeader('Destination');
		$newName = urldecode(basename($destination));
		$storage = $nodeInfo->getMountPoint()->getStorage();

		try {
			$this->EthswarmService->rename($fileName, $newName, $storage);
			$response->setStatus(200);
		} catch (Exception $ex) {
			$response->setStatus(500);
		}
		$response->setHeader('Content-Length', '0');

		return false;
	}
}
