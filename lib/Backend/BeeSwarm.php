<?php

/**
 * @author Metaprovide
 * @copyright Copyright (c) 2022, metaprovide
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

namespace OCA\Files_External_BeeSwarm\Backend;

use OCP\IL10N;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External_BeeSwarm\Auth\HttpBasicAuth;
use OCA\Files_External\Lib\DefinitionParameter;

class BeeSwarm extends Backend
{

    /**
     * beeswarm constructor.
     * @param IL10N $l
     */
    public function __construct(IL10N $l)
    {
        $this
            ->setIdentifier('files_external_beeswarm')
            ->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
            ->setStorageClass('\OCA\Files_External_BeeSwarm\Storage\BeeSwarm')
            ->setText($l->t('BeeSwarm \\nextcloud-swarm-plugin'))
            ->addParameters([
				new DefinitionParameter('ip', $l->t('IP Address')),
				new DefinitionParameter('port', $l->t('API Port')),
				new DefinitionParameter('debug_api_port', $l->t('Debug API Port')),
			])
			->addAuthScheme(AuthMechanism::SCHEME_NULL)
			->addAuthScheme(HttpBasicAuth::SCHEME_HTTP_BASIC);
            //->addCustomJs("../../../$appWebPath/js/beeswarm");
    }
}

