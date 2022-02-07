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
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\DefinitionParameter;

class BeeSwarm extends Backend
{

    /**
     * beeswarm constructor.
     * @param IL10N $l
     */
    public function __construct(IL10N $l)
    {
        //$appWebPath = \OC_App::getAppWebPath('files_external_beeswarm');

        \OC::$server->getLogger()->warning("\\apps\\nextcloud-swarm-plugin\\lib\\Backend\\BeeSwarm.php-__construct(): appWebPath=");

        $this
            ->setIdentifier('files_external_beeswarm')
            ->addIdentifierAlias('\OC\Files\External_Storage\BeeSwarm') // legacy compat
            ->setStorageClass('\OCA\Files_External_BeeSwarm\Storage\BeeSwarm')
            ->setText($l->t('BeeSwarm \\nextcloud-swarm-plugin'))
            ->addParameters([
				new DefinitionParameter('ip', $l->t('IP Address 2')),
				new DefinitionParameter('port', $l->t('Port 2')),
				new DefinitionParameter('postage_batchid', $l->t('Postage Batch Id')),
			])
            ->addAuthScheme(AuthMechanism::SCHEME_NULL);
            //->addCustomJs("../../../$appWebPath/js/beeswarm");
    }
}
