<?php

declare(strict_types=1);

/**
 * @copyright
 *
 * @author
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_External_BeeSwarm\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setFileid(int $fileid)
 * @method int getFileid()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setSwarmReference(string $reference)
*  @method string|null getSwarmReference()
 * @method void setSwarmTag(string $tag)
*  @method string|null getSwarmTag()
 * @method void setMimeType(int $mimetype)
 * @method string|null getMimeType()
 * @method void setSize(int $size)
 * @method string getSize()
 * @method void setMtime(int $mtime)
 * @method int getMTime()
 * @method void setEncryptionKey(string $encryption)
 * @method string getEncryptionKey()
 */
class SwarmFile extends Entity {

	/** @var int */
	protected $fileid;

	/** @var string */
	protected $name;

	/** @var string|null */
	protected $swarmReference;

	/** @var string|null */
	protected $swarmTag;

	/** @var int */
	protected $mimetype;

	/** @var int */
	protected $size;

	/** @var int */
	protected $storageMtime;

	/** @var string */
	protected $encryptionKey;

	public function __construct() {
		$this->addType('fileid', 'int');
		$this->addType('name', 'string');
		$this->addType('swarmReference', 'string');
		$this->addType('swarmTag', 'string');
		$this->addType('mimetype', 'int');
		$this->addType('size', 'int');
		$this->addType('storageMtime', 'int');
		$this->addType('encryptionKey', 'string');
	}
}
