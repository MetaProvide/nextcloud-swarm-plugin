<?php
/**
 * @copyright Copyright (c) 2022, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me>
 *
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
 *
 */

namespace OCA\Files_External_Ethswarm\Storage;

use ArrayIterator;
use Exception;
use OC;
use OC\Files\Cache\Cache;
use OC\Files\Storage\Common;
use OC_Helper;
use OCA\Files_External_Ethswarm\Db\SwarmFileMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Constants;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeLoader;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ITempManager;
use Sabre\DAV\Exception\BadRequest;
use Traversable;

class BeeSwarm extends Common
{
	use BeeSwarmTrait;

	public const APP_NAME = 'files_external_ethswarm';

	/** @var int */
	protected int $storageId;

	/** @var bool */
	private bool $isEncrypted; // TODO: remove

	/** @var string */
	protected string $stampBatchId; // TODO: remove

	/** @var SwarmFileMapper */
	private SwarmFileMapper $fileMapper;

	/** @var IConfig */
	private IConfig $config;

	/** @var IDBConnection */
	protected IDBConnection $dbConnection;

	/** @var ITempManager */
	protected ITempManager $tempManager;

	/** @var IMimeTypeLoader */
	private IMimeTypeLoader $mimeTypeHandler;

	/** @var Cache */
	private Cache $cacheHandler;

	/** @var string */
	protected string $id;


	/**
	 * @throws Exception
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		$this->parseParams($params);
		$this->id = 'ethswarm::' . $this->api_url;
		$this->storageId = $this->getStorageCache()->getNumericId();

		// Load handlers
		$this->tempManager = OC::$server->get(ITempManager::class);
		$this->dbConnection = OC::$server->get(IDBConnection::class);
		$this->fileMapper = new SwarmFileMapper($this->dbConnection);
		$this->mimeTypeHandler = OC::$server->get(IMimeTypeLoader::class);

		$mountHandler = OC::$server->get(IUserMountCache::class);
		$storageMounts = $mountHandler->getMountsForStorageId($this->storageId);

		if (is_array($storageMounts) && isset($storageMounts[0])) {
			// Parse array for config of requested storage
			$storageMount = $storageMounts[0];
			$mountId = $storageMount->getMountId();

			$this->config = OC::$server->get(IConfig::class);
			$configSettings = $this->config->getAppValue(self::APP_NAME, "storageconfig", "");    //default
			$mounts = json_decode($configSettings, true);
			if (is_array($mounts)) {
				$mountIds = array_column($mounts, 'mount_id');
				$key = array_search($mountId, $mountIds);
				if (!empty($key) || $key === 0) {
					$isConfigured = true;
					$this->isEncrypted = $mounts[$key]['encrypt'] == "1" ? true : false;
					$this->stampBatchId = $mounts[$key]['batchid'];
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function test(): bool
	{
		return $this->checkConnection();
	}

	/**
	 * @inheritDoc
	 */
	public function file_exists($path): bool
	{
		if ($path === '' || $path === '/' || $path === '.') {
			// Return true always the creation of the root folder
			return true;
		}
		$exists = $this->fileMapper->findExists($path, $this->storageId);
		if ($exists == 0) {
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function filemtime($path): int
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function stat($path): bool|array
	{
		$data = $this->getMetaData($path);
		if ($data['mimetype'] === 'httpd/unix-directory') {
			return false;
		}
		return [
			'mtime' => $data['mtime'],
			'size' => $data['size'],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getETag($path): ?string
	{
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function needsPartFile(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function mkdir($path): bool
	{
		$this->fileMapper->createDirectory($path, $this->storageId);
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function rmdir($path): void
	{
		// TODO: Implement rmdir() method.
	}

	/**
	 * @inheritDoc
	 */
	public function rename($source, $target): bool
	{
		$rows = $this->fileMapper->getPathTree($source, $this->storageId);
		foreach ($rows as $row) {
			$oldPath = $row->getName();
			$newPath = substr_replace($oldPath, $target, 0, strlen($source));
			$this->fileMapper->updatePath($oldPath, $newPath, $this->storageId);
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function opendir($path): bool
	{
		return true;
	}

	/**
	 * * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function is_dir($path): bool
	{
		return $this->getMetaData($path)['mimetype'] === 'httpd/unix-directory';
	}

	/**
	 * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function is_file($path): bool
	{
		return $this->getMetaData($path)['mimetype'] !== 'httpd/unix-directory';
	}

	/**
	 * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function filetype($path): string
	{
		return $this->is_file($path) ? 'file' : 'dir';
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissions($path = null): int
	{
		return (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE);
	}

	/**
	 * @inheritDoc
	 */
	public function free_space($path): float|false|int
	{
		return FileInfo::SPACE_UNLIMITED;
	}

	/**
	 * @inheritDoc
	 */
	public function hasUpdated($path, $time): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isLocal(): bool
	{
		// the common implementation returns a temporary file by
		// default, which is not local
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function setMountOptions(array $options): void
	{
		// TODO: Implement setMountOptions() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getMountOption($name, $default = null): ?string
	{
		return $this->mountOptions[$name] ?? $default;
	}

	/**
	 * @inheritDoc
	 */
	public function verifyPath($path, $fileName)
	{

	}

	/**
	 * @inheritDoc
	 */
	public function isCreatable($path): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isUpdatable($path): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function unlink($path): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function fopen($path, $mode)
	{
		if ($path === '' || $path === '/' || $path === '.') {
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

	/**
	 * @inheritDoc
	 */
	public function touch($path, $mtime = null): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function file_get_contents($path): string|false
	{
		$swarmFile = $this->fileMapper->find($path, $this->storageId);
		$reference = $swarmFile->getSwarmReference();
		return stream_get_contents($this->downloadSwarm($reference));
	}

	/**
	 * @inheritDoc
	 */
	public function file_put_contents($path, $data): int|float|false
	{
		// TODO: Implement file_put_contents() method.
		return parent::file_put_contents($path, $data);
	}

	/**
	 * @inheritDoc
	 */
	public function getDirectDownload($path): array|bool
	{
		// TODO: Implement getDirectDownload() method.
		return parent::getDirectDownload($path);
	}

	/* Enabling this function causes a fatal exception "Call to a member function getId() on null /var/www/html/lib/private/Files/Mount/MountPoint.php - line 276: OC\Files\Cache\Wrapper\CacheWrapper->getId("")
	public function getCache($path = '', $storage = null)
	{

	}
	*/

	/**
	 * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function getMetaData($path): ?array
	{
		$data = [];
		if ($path === '' || $path === '/' || $path === '.') {
			// This creates a root folder for the storage mount.
			$data['name'] = '';
			$data['permissions'] = Constants::PERMISSION_ALL;
			$data['mimetype'] = 'httpd/unix-directory';
			$data['mtime'] = time();
			$data['storage_mtime'] = time();
			$data['size'] = 0; //unknown
			$data['etag'] = null;
		} // TODO: check overwrite issue
		// If not in swarm table, assume it's a folder
		$exists = $this->fileMapper->findExists($path, $this->storageId) !== 0;
		if (!$exists) {
			// Create a folder item
			$data['name'] = $path;
			// Folder permissions should allow renaming so PERMISSION_UPDATE is included.
			$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
			$data['mimetype'] = 'httpd/unix-directory';
			$data['mtime'] = time();
			$data['storage_mtime'] = time();
			$data['size'] = 1; //unknown
			$data['etag'] = uniqid();
		} else {
			// Get record from table
			$swarmFile = $this->fileMapper->find($path, $this->storageId);
			// Set mimetype as a string, get by using its ID (int)
			$mimeTypeId = $swarmFile->getMimetype();
			if ($mimeTypeId == $this->mimeTypeHandler->getId('httpd/unix-directory'))
				$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
			else
				$data['permissions'] = (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE);

			$data['name'] = basename($path); //TODO: Test
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
	 * @inheritDoc
	 * @throws DoesNotExistException
	 */
	public function getDirectoryContent($directory): Traversable
	{
		$rows = $this->fileMapper->getPathTree($directory, $this->storageId, false);
		$content = array_map(fn($val) => $this->getMetaData($val->getName()), $rows);

		return new ArrayIterator($content);
	}

	/**
	 * @param resource $stream
	 * @return string
	 */
	protected function createTempFile($stream): string
	{
		$extension = '';
		$tmpFile = $this->tempManager->getTemporaryFile($extension);
		$target = fopen($tmpFile, 'w');
		OC_Helper::streamCopy($stream, $target);
		fclose($target);
		return $tmpFile;
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function writeStream(string $path, $stream, int $size = null): int
	{
		// save stream to temp file
		$tmpFile = $this->createTempFile($stream);
		$tmpFileSize = (file_exists($tmpFile) ? filesize($tmpFile) : -1);
		$mimeType = mime_content_type($tmpFile);

		// upload to swarm
		try {
			$reference = $this->uploadSwarm($path, $tmpFile, $mimeType);
		} catch (Exception $e) {
			throw new BadRequest($e->getMessage());
		} finally {
			fclose($stream);
		}

		// save to swarm table
		$uploadFiles = [
			"name" => $path,
			"permissions" => (Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE - Constants::PERMISSION_UPDATE),
			"mimetype" => $this->mimeTypeHandler->getId($mimeType),
			"mtime" => time(),
			"storage_mtime" => time(),
			"size" => $tmpFileSize,
			"etag" => null,
			"reference" => $reference,
			"storage" => $this->storageId,
		];
		$this->fileMapper->createFile($uploadFiles);

		// TODO: Read back from swarm to return filesize?
		return $tmpFileSize;
	}
}
