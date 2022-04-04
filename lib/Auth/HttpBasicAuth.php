<?php
/**
 * @copyright Copyright (c)
 *
 * @author
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External_BeeSwarm\Auth;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * Basic Http Authentication
 */
class HttpBasicAuth extends AuthMechanism {
	public const SCHEME_HTTP_BASIC = 'http_basic_auth';

	public function __construct(IL10N $l) {
		$this
			->setIdentifier('http::basicauth')
			->setScheme(self::SCHEME_HTTP_BASIC)
			->setText($l->t('HTTP Basic Auth'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Username')),
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
			]);
	}
}
