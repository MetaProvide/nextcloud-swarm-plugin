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
use OCP\IDBConnection;
use OCP\Files\GenericFileException;
use OCP\ILogger;
use OCP\Files\StorageNotAvailableException;
use Sabre\DAV\Exception\BadRequest;

class BeeSwarm extends \OC\Files\Storage\Common
{
	use BeeSwarmTrait;

	const APP_NAME = 'files_external_beeswarm';

	/**
	 * @var isEncrypted
	 */
	private $isEncrypted;

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

	/** @var int */
	protected $storageId;

	public function __construct($params)
	{
		$this->parseParams($params);
		$this->id = 'beeswarm2::' . $this->ip . ':' . $this->port;

		$dbConnection = \OC::$server->get(IDBConnection::class);
		$this->filemapper = new SwarmFileMapper($dbConnection);

		$this->mimeTypeHandler = \OC::$server->get(IMimeTypeLoader::class);

		$this->storageId = $this->getStorageCache()->getNumericId();

		$this->isEncrypted = true;

		//$this->config->getAppValue(SELF::APP_NAME,"key",0);	//default
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
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-test:" . self::class);
		return true;
	}

	public function file_exists($path) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-file_exists: path=" . $path);

		if ($path === '' || $path === '/' || $path === '.') {
			// Return true always the creation of the root folder
		 	return true;
		}
		return false;
	}

	public function filemtime($path) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-filemtime: path=" . $path . ";class ns=" . self::class);

		//$this->cacheFilemtime[$path] = $mtime;
		$mtime = 0;
		return $mtime;
	}

	public function stat($path) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-stat: path=" . $path . ";class ns=" . self::class);
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
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-Start getDirectoryContent(): directory=" . $directory);
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
			$swarmFile = $this->filemapper->find($path, $this->getStorageCache()->getNumericId());
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
	 	\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-opendir(): path=". $path);
		//path = @"/var/www/html/data/";
		//parent::opendir($path);
		return opendir($path);
	}

	public function is_dir($path) {
		return true;
	}

	public function filetype($path) {
		return true;
	}

	public function getPermissions($path) {
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-getPermissions(): path=". $path . ";Constants::PERMISSION_ALL + Constants::PERMISSION_CREATE=" . Constants::PERMISSION_CREATE);
		return Constants::PERMISSION_ALL + Constants::PERMISSION_CREATE;
	}

	public function free_space($path) {
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-free_space(): path=". $path . ";Constants::SPACE_UNLIMITED=" . \OCP\Files\FileInfo::SPACE_UNLIMITED);
		return \OCP\Files\FileInfo::SPACE_UNLIMITED;
	}

	public function hasUpdated($path, $time) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-hasUpdated(): path=". $path);
		return true;	// $this->filemtime($path) > $time;
	}

	public function isLocal() {
		// the common implementation returns a temporary file by
		// default, which is not local
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-isLocal()");
		return false;
	}

	public function setMountOptions(array $options) {
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-setMountOptions()");
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
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-isCreatable(): path=" . $path);
		return true;
	}

	public function isUpdatable($path)
	{
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-isUpdatable(): path=" . $path);
		return true;
	}

	public function unlink($path) {
		return true;
	}

	public function fopen($path, $mode) {

		$swarmFile = $this->filemapper->find($path, $this->getStorageCache()->getNumericId());
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
		// $oldMask = umask(022);
		// $result = file_put_contents($this->getSourcePath($path), $data);
		// umask($oldMask);
		// return $result;
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
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): reference2=" . (is_array($result) ? $result["reference"] : "novalue") . ";isset=" . isset($result['response']) );

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
		"storage" => $this->getStorageCache()->getNumericId(),
		];
		$this->filemapper->createFile($uploadfiles);

		// //TODO: Read back from swarm to return filesize
		return $tmpFilesize;
	}
}
