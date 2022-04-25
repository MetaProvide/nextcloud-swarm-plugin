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
namespace OCA\Files_External_Ethswarm\Auth;

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
