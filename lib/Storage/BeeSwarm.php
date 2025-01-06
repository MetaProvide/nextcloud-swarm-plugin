<?php

/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 * @author Ron Trevor <ecoron@proton.me>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Files_External_Ethswarm\Storage;

use ArrayIterator;
use Exception;
use OC;
use OC\Files\Cache\Cache;
use OC\Files\Storage\Common;
use OC_Helper;
use OCA\Files_External_Ethswarm\AppInfo\AppConstants;
use OCA\Files_External_Ethswarm\Db\SwarmFile;
use OCA\Files_External_Ethswarm\Db\SwarmFileMapper;
use OCA\Files_External_Ethswarm\Service\NotificationService;
use OCP\Constants;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\StorageBadConfigException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Traversable;

class BeeSwarm extends Common {
	use BeeSwarmTrait;

	protected int $storageId;

	protected IDBConnection $dbConnection;

	protected IL10N $l10n;

	protected string $id;

	private bool $isEncrypted; // TODO: remove

	private SwarmFileMapper $fileMapper;

	private IConfig $config;

	private IMimeTypeLoader $mimeTypeHandler;

	private ITempManager $tempManager;

	private IMimeTypeDetector $mimeTypeDetector;

	private Cache $cacheHandler;

	private NotificationService $notificationService;

	private string $token;

	private LoggerInterface $logger;

	/**
	 * @param mixed $params
	 *
	 * @throws StorageBadConfigException
	 */
	public function __construct($params) {
		parent::__construct($params);

		// Load storage configuration
		$this->parseParams($params);
		$this->id = 'ethswarm::'.$this->access_key;
		$this->storageId = $this->getStorageCache()->getNumericId();
		$this->token = $this->getStorageCache()->getStorageId($this->storageId);

		// Load handlers and services
		$this->tempManager = OC::$server->get(ITempManager::class);
		$this->dbConnection = OC::$server->get(IDBConnection::class);
		$this->mimeTypeHandler = OC::$server->get(IMimeTypeLoader::class);
		$this->dbConnection = OC::$server->get(IDBConnection::class);
		$this->mimeTypeHandler = OC::$server->get(IMimeTypeLoader::class);
		$this->mimeTypeDetector = OC::$server->get(IMimeTypeDetector::class);
		$this->logger = OC::$server->get(LoggerInterface::class);
		$mountHandler = OC::$server->get(IUserMountCache::class);
		$storageMounts = $mountHandler->getMountsForStorageId($this->storageId);

		$this->fileMapper = new SwarmFileMapper($this->dbConnection);

		/** @var IL10NFactory $l10nFactory */
		$l10nFactory = OC::$server->get(IL10NFactory::class);
		$this->l10n = $l10nFactory->get(AppConstants::APP_NAME);

		$this->notificationService = new NotificationService(
			OC::$server->get(IManager::class),
			OC::$server->get(IUserManager::class),
			OC::$server->get(IUserSession::class)
		);

		if (is_array($storageMounts) && isset($storageMounts[0])) {
			// Parse array for config of requested storage
			$storageMount = $storageMounts[0];
			$mountId = $storageMount->getMountId();

			$this->config = OC::$server->get(IConfig::class);
			$configSettings = $this->config->getAppValue(AppConstants::APP_NAME, 'storageconfig');
			$mounts = json_decode($configSettings, true);
			if (is_array($mounts)) {
				$mountIds = array_column($mounts, 'mount_id');
				$key = array_search($mountId, $mountIds);
				if (!empty($key) || 0 === $key) {
					$this->isEncrypted = '1' === $mounts[$key]['encrypt'];
				}
			}
		}
		$this->cacheHandler = new Cache($this);
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * @throws Exception
	 */
	public function test(): bool {
		if (!$this->checkConnection()) {
			return false;
		}

		$this->fileMapper->updateStorageIds($this->token, $this->storageId);
		$this->add_root_folder_cache();
		$this->add_token_files_cache();

		return true;
	}

	/**
	 * @param mixed $source
	 * @param mixed $target
	 *
	 * @throws Exception
	 */
	public function copy($source, $target): bool {
		try {
			// Get the source file from the mapper
			$sourceFile = $this->fileMapper->find($source, $this->storageId);
			if (!$sourceFile->getFileId()) {
				$this->logger->error(
					'copy failed: source file not found in mapper '.$source,
					['app' => AppConstants::APP_NAME]
				);

				return false;
			}

			// Prepare the data for the new file
			$copyData = [];
			$copyData['name'] = $target;
			$copyData['reference'] = $sourceFile->getSwarmReference();
			$copyData['etag'] = null;
			$copyData['mimetype'] = $sourceFile->getMimetype();
			$copyData['size'] = $sourceFile->getSize();
			$copyData['storage_mtime'] = time();
			$copyData['storage'] = $this->storageId;
			$copyData['token'] = $this->token;

			// Create the new file entry in the mapper
			$newFile = $this->fileMapper->createFile($copyData);

			if (!$newFile->getFileId()) {
				$this->logger->error(
					'copy failed: failed to create new file in mapper '.$target,
					['app' => AppConstants::APP_NAME]
				);

				return false;
			}

			return true;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function add_root_folder_cache(): void {
		$fileData = [
			'storage' => $this->storageId,
			'path' => '',
			'path_hash' => md5(''),
			'name' => '',
			'mimetype' => 'httpd/unix-directory',
			'size' => 1,
			'etag' => uniqid(),
			'storage_mtime' => time(),
			// 2024-11-14 - We still don't support edit, so file is never updated.
			'mtime' => time(),
			'permissions' => (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE),
			'parent' => -1,
		];
		$this->cacheHandler->put($fileData['path'], $fileData);
	}

	public function add_file_cache(SwarmFile $file): bool {
		$fileData = [
			'storage' => $file->getStorage(),
			'path' => $file->getName(),
			'path_hash' => md5($file->getName()),
			'name' => basename($file->getName()),
			'mimetype' => $this->mimeTypeHandler->getMimetypeById($file->getMimetype()),
			'size' => $file->getSize(),
			'etag' => uniqid(),
			'storage_mtime' => $file->getStorageMtime(),
			// 2024-11-14 - We still don't support edit, so file is never updated.
			'mtime' => $file->getStorageMtime(),
		];

		if ($file->getMimetype() == $this->mimeTypeHandler->getId('httpd/unix-directory')) {
			$fileData['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
		} else {
			$fileData['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE);
		}

		$this->cacheHandler->put($fileData['path'], $fileData);

		return true;
	}

	/**
	 * @throws Exception
	 */
	public function add_token_files_cache(): void {
		foreach ($this->fileMapper->findAllWithToken($this->token) as $file) {
			$this->add_file_cache($file);
		}
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function file_exists($path): bool {
		if ('' === $path || '/' === $path || '.' === $path) {
			// Return true always the creation of the root folder
			return true;
		}

		return $this->fileMapper->findExists($path, $this->storageId) > 0;
	}

	public function filemtime($path): int {
		return 0;
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function stat($path): array|bool {
		$data = $this->getMetaData($path);
		if ('httpd/unix-directory' === $data['mimetype']) {
			return false;
		}

		return [
			'mtime' => $data['mtime'],
			'size' => $data['size'],
		];
	}

	public function getETag($path): ?string {
		return null;
	}

	public function needsPartFile(): bool {
		return false;
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function mkdir($path): bool {
		$this->fileMapper->createDirectory($path, $this->storageId, $this->token);

		return true;
	}

	public function rmdir($path): void {
		// TODO: Implement rmdir() method.
	}

	/**
	 * @param mixed $source
	 * @param mixed $target
	 *
	 * @throws Exception
	 */
	public function rename($source, $target): bool {
		$rows = $this->fileMapper->getPathTree($source, $this->storageId);
		foreach ($rows as $row) {
			$oldPath = $row->getName();
			$newPath = substr_replace($oldPath, $target, 0, strlen($source));
			$this->fileMapper->updatePath($oldPath, $newPath, $this->storageId);
		}

		return true;
	}

	public function opendir($path): bool {
		return true;
	}

	/**
	 * * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function is_dir($path): bool {
		return 'httpd/unix-directory' === $this->getMetaData($path)['mimetype'];
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function is_file($path): bool {
		return 'httpd/unix-directory' !== $this->getMetaData($path)['mimetype'];
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function filetype($path): string {
		return $this->is_file($path) ? 'file' : 'dir';
	}

	public function getPermissions($path = null): int {
		return Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE;
	}

	public function free_space($path): false|float|int {
		return FileInfo::SPACE_UNLIMITED;
	}

	public function hasUpdated($path, $time): bool {
		return true;
	}

	public function isLocal(): bool {
		// the common implementation returns a temporary file by
		// default, which is not local
		return false;
	}

	public function setMountOptions(array $options): void {
		// TODO: Implement setMountOptions() method.
	}

	public function getMountOption($name, $default = null): ?string {
		return $this->mountOptions[$name] ?? $default;
	}

	public function verifyPath($path, $fileName) {}

	public function isCreatable($path): bool {
		return true;
	}

	public function isUpdatable($path): bool {
		return true;
	}

	public function unlink($path): bool {
		return true;
	}

	/**
	 * @param mixed $path
	 * @param mixed $mode
	 *
	 * @throws Exception
	 */
	public function fopen($path, $mode) {
		if ('' === $path || '/' === $path || '.' === $path) {
			return false;
		}
		$swarmFile = $this->fileMapper->find($path, $this->storageId);
		$reference = $swarmFile->getSwarmReference();

		switch ($mode) {
			case 'r':
			case 'rb':
				// Get file from swarm
				return $this->downloadSwarm($reference);

			case 'w':    // Open for writing only; place the file pointer at the beginning of the file
			case 'w+':    // Open for reading and writing
			case 'wb':
			case 'wb+':
			case 'a':
			case 'ab':
			case 'r+':    // Open for reading and writing. place the file pointer at the beginning of the file
			case 'a+':    // Open for reading and writing. place the file pointer at the end of the file.
			case 'x':    // Create and open for writing only. place the file pointer at the beginning of the file
			case 'x+':    // Create and open for reading and writing.
			case 'c':    // Open the file for writing only
			case 'c+':    // Open the file for reading and writing;
		}

		return false;
	}

	public function touch($path, $mtime = null): bool {
		return true;
	}

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function file_get_contents($path): false|string {
		$swarmFile = $this->fileMapper->find($path, $this->storageId);
		$reference = $swarmFile->getSwarmReference();

		return stream_get_contents($this->downloadSwarm($reference));
	}

	public function file_put_contents($path, $data): false|float|int {
		// TODO: Implement file_put_contents() method.
		return parent::file_put_contents($path, $data);
	}

	public function getDirectDownload($path): array|bool {
		// TODO: Implement getDirectDownload() method.
		return parent::getDirectDownload($path);
	}

	/* Enabling this function causes a fatal exception "Call to a member function getId() on null /var/www/html/lib/private/Files/Mount/MountPoint.php - line 276: OC\Files\Cache\Wrapper\CacheWrapper->getId("")
	public function getCache($path = '', $storage = null)
	{

	}
	*/

	/**
	 * @param mixed $path
	 *
	 * @throws Exception
	 */
	public function getMetaData($path): ?array {
		$data = [];
		// If not in swarm table, assume it's a folder
		$exists = 0 !== $this->fileMapper->findExists($path, $this->storageId);
		if (!$exists) {
			// Create a folder item
			$data['name'] = $path;
			// Folder permissions should allow renaming so PERMISSION_UPDATE is included.
			$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);

			// Check if this is likely a file based on extension
			$isFile = '' !== pathinfo($path, PATHINFO_EXTENSION);

			if ($isFile) {
				$data['mimetype'] = $this->mimeTypeDetector->detectPath($path);
				$data['size'] = 0;
			} else {
				// Directory logic
				$data['mimetype'] = 'httpd/unix-directory';
				$data['size'] = 1;
			}
		} else {
			// Get record from table
			$swarmFile = $this->fileMapper->find($path, $this->storageId);
			// Set mimetype as a string, get by using its ID (int)
			$mimeTypeId = $swarmFile->getMimetype();
			if ($mimeTypeId == $this->mimeTypeHandler->getId('httpd/unix-directory')) {
				$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
			} else {
				$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE);
			}
			$data['name'] = basename($path); // TODO: Test
			$data['mimetype'] = $this->mimeTypeHandler->getMimetypeById($mimeTypeId);
			$data['mtime'] = time();
			$data['storage_mtime'] = $swarmFile->getStorageMtime();
			$data['size'] = $swarmFile->getSize();
			$data['etag'] = uniqid();
			$data['swarm_ref'] = $swarmFile->getSwarmReference();
		}

		return $data;
	}

	/**
	 * @param mixed $directory
	 *
	 * @throws Exception
	 */
	public function getDirectoryContent($directory): Traversable {
		$rows = $this->fileMapper->getPathTree($directory, $this->storageId, false, false);
		$content = array_map(fn ($val) => $this->getMetaData($val->getName()), $rows);

		return new ArrayIterator($content);
	}

	/**
	 * @param resource $stream
	 *
	 * @throws Exception
	 */
	public function writeStream(string $path, $stream, ?int $size = null): int {
		// save stream to temp file
		$tmpFile = $this->createTempFile($stream);
		$tmpFileSize = (file_exists($tmpFile) ? filesize($tmpFile) : -1);
		$mimetype = str_ends_with(strtolower($path), '.md') ? 'text/markdown' : mime_content_type($tmpFile);

		try {
			$reference = $this->uploadSwarm($path, $tmpFile, $mimetype);
		} catch (Exception $e) {
			throw new BadRequest($e->getMessage());
		} finally {
			fclose($stream);
		}

		// save to swarm table
		$uploadFiles = [
			'name' => $path,
			'permissions' => (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE),
			'mimetype' => $this->mimeTypeHandler->getId($mimetype),
			'mtime' => time(),
			'storage_mtime' => time(),
			'size' => $tmpFileSize,
			'etag' => null,
			'reference' => $reference,
			'storage' => $this->storageId,
			'token' => $this->token,
		];
		$this->fileMapper->createFile($uploadFiles);

		$this->notificationService->sendTemporaryNotification('swarm-fileupload', $path);

		// TODO: Read back from swarm to return filesize?
		return $tmpFileSize;
	}

	/**
	 * @param resource $stream
	 */
	protected function createTempFile($stream): string {
		$extension = '';
		$tmpFile = $this->tempManager->getTemporaryFile($extension);
		$target = fopen($tmpFile, 'w');
		OC_Helper::streamCopy($stream, $target);
		fclose($target);

		return $tmpFile;
	}
}
