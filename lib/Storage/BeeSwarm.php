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
use OCP\Files\FileInfo;
use OCP\Files\StorageNotAvailableException;
use OC\Files\Filesystem;
use OCP\Files\ForbiddenException;
use OCP\Files\GenericFileException;
use OCP\ILogger;
use Symfony\Component\Console\Output\OutputInterface;

class BeeSwarm extends \OC\Files\Storage\Common
{
	use BeeSwarmTrait;

	const APP_NAME = 'files_external_beeswarm';

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * @var int
	 */
	protected $cacheFilemtime = [];

	public function __construct($params)
	{
		$this->parseParams($params);
		$this->id = 'beeswarm2::' . $this->ip . ':' . $this->port;
		//\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\Storage\\BeeSwarm.php-ip=" . $this->ip . ";id=" . $this->id);
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
		return false;
		// if ($path === '' || $path === '/' || $path === '.') {
		// 	return true;
		// }

		// return parent::file_exists($path);
	}

	protected function getLargest($arr, $default = 0) {
		if (\count($arr) === 0) {
			return $default;
		}
		\arsort($arr);
		return \array_values($arr)[0];
	}

	public function filemtime($path) {
		if ($this->is_dir($path)) {
			if ($path === '.' || $path === '') {
				$path = "/";
			}

			if ($this->cacheFilemtime && isset($this->cacheFilemtime[$path])) {
				return $this->cacheFilemtime[$path];
			}

			$arr = [];
			$contents = $this->flysystem->listContents($path, true);
			foreach ($contents as $c) {
				$arr[] = $c['type'] === 'file' ? $c['timestamp'] : 0;
			}
			$mtime = $this->getLargest($arr);
		} else {
			if ($this->cacheFilemtime && isset($this->cacheFilemtime[$path])) {
				return $this->cacheFilemtime[$path];
			}
			$mtime = parent::filemtime($path);
		}
		$this->cacheFilemtime[$path] = $mtime;
		return $mtime;
	}

	public function stat($path) {
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-stat: path=" . $path . ";class ns=" . self::class);
		// if ($path === '' || $path === '/' || $path === '.') {
		// 	return ['mtime' => 0];
		// }
		return parent::stat($path);
	}

	public function getDirectoryContent($directory) :\Traversable
	{
		// $idx = 0;
		// while ($idx < 5) {
			// $metadata = $this->getMetaData($directory);
			// yield $metadata;
		// 	$idx++;
		// }
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

	public function getMetaData($path) {

		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-Start getMetaData(): path=". $path);

		$data = [];
	 	$data['name'] = '';
	 	$data['permissions'] = Constants::PERMISSION_ALL;
	 	$data['mimetype'] = 'httpd/unix-directory';		//$this->getMimeType($path);
	 	$data['mtime'] = time();
	 	$data['storage_mtime'] = time();
	 	$data['size'] = -1; //unknown
	 	$data['etag'] = "61dx037xdc07z";
		 \OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-End getMetaData(): data['mimetype']=". $data['mimetype']);
		// $data = [];
	 	// $data['name'] = "sample.txt";
	 	// $data['permissions'] = Constants::PERMISSION_ALL;
	 	// $data['mimetype'] = "text/plain";		//$this->getMimeType($path);
	 	// $data['mtime'] = time();
	 	// $data['storage_mtime'] = time();
	 	// $data['size'] = -1; //unknown
	 	// $data['etag'] = "61dx037xdc07x";
		 //\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-End getMetaData(): data['name']=". $data['name']);
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
				"size" => -1,
				"etag" => uniqid()
			],
			[
				"name" => "sample_icon.jpg",
				"permissions" => Constants::PERMISSION_ALL,
				"mimetype" => "image/jpeg",
				"mtime" => time(),
				"storage_mtime" => time(),
				"size" => -1,
				"etag" => uniqid()			]
		];
		return $metadata_arr;
	}

	public function mkdir($path) {
		return true;
	}

	public function rmdir($path) {
		// $path = $this->normalizePath($path);

		// if ($this->isRoot($path)) {
		// 	return $this->clearBucket();
		// }

		// if (!$this->file_exists($path)) {
		// 	return false;
		// }

		// $this->invalidateCache($path);
		// return $this->batchDelete($path);
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
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-fopen(): path=" . $path);
		return true;
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

	/**
	 * Get the source path (on disk) of a given path
	 *
	 * @param string $path
	 * @return string
	 * @throws ForbiddenException
	 */
	public function getSourcePath($path) {
		if (Filesystem::isFileBlacklisted($path)) {
			throw new ForbiddenException('Invalid path: ' . $path, false);
		}

		$fullPath = $this->datadir . $path;
		$currentPath = $path;
		$allowSymlinks = \OC::$server->getConfig()->getSystemValue('localstorage.allowsymlinks', false);
		if ($allowSymlinks || $currentPath === '') {
			return $fullPath;
		}
		$pathToResolve = $fullPath;
		$realPath = realpath($pathToResolve);
		while ($realPath === false) { // for non existing files check the parent directory
			$currentPath = dirname($currentPath);
			if ($currentPath === '' || $currentPath === '.') {
				return $fullPath;
			}
			$realPath = realpath($this->datadir . $currentPath);
		}
		if ($realPath) {
			$realPath = $realPath . '/';
		}
		if (substr($realPath, 0, $this->dataDirLength) === $this->realDataDir) {
			return $fullPath;
		}

		\OCP\Util::writeLog('core', "Following symlinks is not allowed ('$fullPath' -> '$realPath' not inside '{$this->realDataDir}')", ILogger::ERROR);
		throw new ForbiddenException('Following symlinks is not allowed', false);
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
		\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): path=" . $path . ";size=" . ($size === null ? "unknown" : $size) . ";tempfile=" . $tmpFile . ";file_exists=" . file_exists($tmpFile) . ";filesize=" . $tmpFilesize);

		try {
		 	//$result = $this->upload_file($path, $tmpFile, $tmpFilesize);
			$result = $this->upload_stream($path, $stream, $tmpFile, $tmpFilesize);
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): result2=" . var_export($result, true));
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): reference2=" . (is_array($result) ? $result["reference"] : "novalue") . ";isset=" . isset($result) );

			// if (!is_array($result))
			// {
			// 	\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): GenericFileException=" . (is_array($result) ? $result["reference"] : "novalue"));
			//   	throw new GenericFileException("Failed to upload file to swarm " . $this->id);
			// }
		}
		catch (\Exception $e) {
			\OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Storage\\BeeSwarm.php-writeStream(): exception=" . $e->getMessage());
			//$output->writeln('<error>Mount not found</error>');
		}
		finally {
		  	fclose($stream);
		}


		// //TODO: Read back from swarm to return filesize
		return $tmpFilesize;
	}
}
