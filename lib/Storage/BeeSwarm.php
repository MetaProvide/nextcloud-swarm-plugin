<?php

/**
 * @author Metaprovide
 *
 * @copyright Copyright (c) 2022, Metaprovide
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace OCA\Files_External_BeeSwarm\Storage;

use OCP\Constants;
use OC\Files\Cache\CacheEntry;
use OCA\Files_External_BeeSwarm\Storage\BeeSwarmTrait;
use OCP\Files\IMimeTypeLoader;
use OCA\Files_External_BeeSwarm\Db\SwarmFileMapper;
use OCA\Files_External\Service;
use OCA\Files_External\Service\DBConfigService;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Files\GenericFileException;
use OCP\ILogger;
use OCP\Files\StorageNotAvailableException;
use OCP\Security\ICrypto;
use Sabre\DAV\Exception\BadRequest;

class BeeSwarm extends \OC\Files\Storage\Common
{
	use BeeSwarmTrait;

	const APP_NAME = 'files_external_beeswarm';

	/** @var int */
	protected $storageId;

	/**
	 * @var bool
	 */
	private $isEncrypted;

	/** @var string */
	protected $stampBatchId;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/** @var SwarmFileMapper */
	private $filemapper;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var \OCP\Files\IMimeTypeLoader */
	private $mimeTypeHandler;

	/** @var \OC\Files\Cache\Cache */
	private $cacheHandler;

	public function __construct($params)
	{
		$this->parseParams($params);
		$this->id = 'beeswarm2::' . $this->ip . ':' . $this->port;
		$this->storageId = $this->getStorageCache()->getNumericId();

		// Load handlers
		$dbConnection = \OC::$server->get(IDBConnection::class);
		$this->filemapper = new SwarmFileMapper($dbConnection);
		$this->mimeTypeHandler = \OC::$server->get(IMimeTypeLoader::class);

		$mountHandler = \OC::$server->get(IUserMountCache::class);
		$storageMounts = $mountHandler->getMountsForStorageId($this->storageId);
		$isConfigured = false;
		if (is_array($storageMounts) && $storageMounts[0]) {
			// Parse array for config of requested storage
			$storageMount = $storageMounts[0];
				$mountId = $storageMount->getMountId();

				$this->config = \OC::$server->get(IConfig::class);
				$configSettings = $this->config->getAppValue(SELF::APP_NAME,"storageconfig","");	//default
				$mounts = json_decode($configSettings, true);
				$mountIds = array_column($mounts, 'mount_id');
				$key = array_search($mountId, $mountIds);
				if (!empty($key) || $key === 0) {
					$isConfigured = true;
					$this->isEncrypted = $mounts[$key]['encrypt'] == "1" ? true : false;
					$this->stampBatchId = $mounts[$key]['batchid'];
				}
		}
		if (!$isConfigured)
		{
			// not yet configured, exception
			//throw new \Exception("Unable to read swarm configuration for {$this->id}");
		}
	}

	public static function checkDependencies() {
		return true;
	}

	public function getId()
	{
		return $this->id;
	}

	public function test()
	{
		return true;
	}

	public function file_exists($path) {
		if ($path === '' || $path === '/' || $path === '.') {
			// Return true always the creation of the root folder
		 	return true;
		}
		return false;
	}

	public function filemtime($path) {
		$mtime = 0;
		return $mtime;
	}

	public function stat($path) {
		// if ($path === '' || $path === '/' || $path === '.') {
		// 	return ['mtime' => 0];
		// }
		return parent::stat($path);
	}

	/**
	 * get the ETag for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getETag($path) {
	 	return null;
	}

	public function getDirectoryContent($directory) :\Traversable
	{
		$metadata = $this->populateMetadata();
		foreach ($metadata as $meta)
		{
			$cacheEntry = $this->getCache()->get($meta['name']);
			if (1==2 && $cacheEntry instanceof CacheEntry) {
				\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getDirectoryContent(): cacheEntry->getData()=" . var_export($cacheEntry->getData(), true));
				yield $cacheEntry->getData();
			}
			else {
			 	\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getDirectoryContent(): populateMetadata()=" . var_export($meta, true));
			 	yield $meta;
			}
		}
	}

	public function needsPartFile()
	{
		return false;
	}

	public function getMetaData($path) {
		$data = [];
		if ($path === '' || $path === '/' || $path === '.') {
			// This creates a root folder for the storage mount.
			$data['name'] = '';
			$data['permissions'] = Constants::PERMISSION_ALL;
			$data['mimetype'] = 'httpd/unix-directory';		//$this->getMimeType($path);
			$data['mtime'] = time();
			$data['storage_mtime'] = time();
			$data['size'] = 0; //unknown
			$data['etag'] = null;
		}
		else
        {
			// Get record from table
			$swarmFile = $this->filemapper->find($path, $this->storageId);
            $data['name'] = $path;
            $data['permissions'] = Constants::PERMISSION_ALL;
			// Set mimetype as a string, get by using its ID (int)
			$mimetypeId = $swarmFile->getMimetype();
            $data['mimetype'] = $this->mimeTypeHandler->getMimetypeById($mimetypeId);
            $data['mtime'] = time();
            $data['storage_mtime'] = $swarmFile->getStorageMtime();
            $data['size'] = $swarmFile->getSize();
            $data['etag'] = null;
        }
	 	return $data;
	}
	private function populateMetadata() {
		$metadata_arr =[
			[
				"name" => "sample.txt",
				"permissions" => Constants::PERMISSION_ALL,
				"mimetype" => "text/plain",
				"mtime" => time(),
				"storage_mtime" => time(),
				"size" => 60,
				"etag" => uniqid()
			],
			[
				"name" => "sample_icon.jpg",
				"permissions" => Constants::PERMISSION_ALL,
				"mimetype" => "image/jpeg",
				"mtime" => time(),
				"storage_mtime" => time(),
				"size" => 70,
				"etag" => uniqid()			]
		];
		return $metadata_arr;
	}

	public function mkdir($path) {
		return true;
	}

	public function rmdir($path) {
	}

	public function opendir($path) {
		return opendir($path);
	}

	public function is_dir($path) {
		return true;
	}

	public function filetype($path) {
		return true;
	}

	public function getPermissions($path) {
		return Constants::PERMISSION_ALL + Constants::PERMISSION_CREATE;
	}

	public function free_space($path) {
		return \OCP\Files\FileInfo::SPACE_UNLIMITED;
	}

	public function hasUpdated($path, $time) {
		return true;
	}

	public function isLocal() {
		// the common implementation returns a temporary file by
		// default, which is not local
		return false;
	}

	public function setMountOptions(array $options) {
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getMountOption($name, $default = null) {
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getMountOption(): name=" . $name);
		return isset($this->mountOptions[$name]) ? $this->mountOptions[$name] : $default;
	}

	public function verifyPath($path, $fileName) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-verifyPath(): path=" . $path . ";filename=" . $fileName) ;
	}

	public function isCreatable($path) {
		return true;
	}

	public function isUpdatable($path)
	{
		return true;
	}

	public function unlink($path) {
		return true;
	}

	public function fopen($path, $mode) {

		$swarmFile = $this->filemapper->find($path, $this->storageId);
		$reference = $swarmFile->getSwarmReference();

		$useExisting = true;
		switch ($mode) {
			case 'r':
			case 'rb':
				// Get file from swarm
				return $this->get_stream($path, $reference);
			case 'w':	// Open for writing only; place the file pointer at the beginning of the file
			case 'w+':	// Open for reading and writing
			case 'wb':
			case 'wb+':
				// no break
			case 'a':
			case 'ab':
			case 'r+':	// Open for reading and writing. place the file pointer at the beginning of the file
			case 'a+':	// Open for reading and writing. place the file pointer at the end of the file.
			case 'x':	// Create and open for writing only. place the file pointer at the beginning of the file
			case 'x+':	// Create and open for reading and writing.
			case 'c':	// Open the file for writing only
			case 'c+': 	// Open the file for reading and writing;
		}

		return false;
	}

	public function touch($path, $mtime = null) {
		return true;
	}

	public function file_get_contents($path)
	{
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-file_get_contents(): path=" . $path);
	}

	public function file_put_contents($path, $data)
	{
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-file_put_contents(): path=" . $path);
	}

	public function getDirectDownload($path)
	{
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getDirectDownload(): path=" . $path);
	}

	/* Enabling this function causes a fatal exception "Call to a member function getId() on null /var/www/html/lib/private/Files/Mount/MountPoint.php - line 276: OC\Files\Cache\Wrapper\CacheWrapper->getId("")
	public function getCache($path = '', $storage = null)
	{
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getCache(): path=" . $path);
	}
	*/

	protected function toTmpFile($source) { //no longer in the storage api, still useful here
		$extension = '';
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($extension);
		$target = fopen($tmpFile, 'w');
		\OC_Helper::streamCopy($source, $target);
		fclose($target);
		return $tmpFile;
	}

	public function writeStream(string $path, $stream, int $size = null): int {
		// Write to temp file
		$tmpFile = $this->toTmpFile($stream);
		$tmpFilesize = (file_exists($tmpFile) ? filesize($tmpFile) : -1);
		$mimetype = mime_content_type($tmpFile);

		try {
		 	//$result = $this->upload_file($path, $tmpFile, $tmpFilesize);
			$result = $this->upload_stream($path, $stream, $tmpFile, $mimetype, $tmpFilesize);
			$reference = (isset($result["reference"]) ? $result['reference'] : null);

			if (!isset($reference))
			{
				throw new BadRequest("Failed to upload file to swarm " . $this->id);
			}
		}
		catch (\Exception $e) {
			throw new StorageNotAvailableException($e->getMessage());
		}
		finally {
		  	fclose($stream);
		}

		// Write metadata to table
		$uploadfiles = [
		"name" => $path,
		"permissions" => Constants::PERMISSION_ALL,
		"mimetype" => $this->mimeTypeHandler->getId($mimetype),
		"mtime" => time(),
		"storage_mtime" => time(),
		"size" => $tmpFilesize,
		"etag" => null,
		"reference" => $reference,
		"storage" => $this->storageId,
		];
		$this->filemapper->createFile($uploadfiles);

		// //TODO: Read back from swarm to return filesize
		return $tmpFilesize;
	}
}
