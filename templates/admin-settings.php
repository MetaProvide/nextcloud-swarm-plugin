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
style('files_external_ethswarm', 'settings-admin');
script('files_external_ethswarm', 'settings');
?>
<div id="beeswarm" class="section">
	<h2 class="inlineblock"><?php p($l->t('External Storage: Swarm')); ?></h2>
	<a target="_blank" rel="noreferrer" class="icon-info" title="<?php p($l->t('Open documentation'));?>" href="<?php p('https://github.com/MetaProvide/nextcloud-swarm-plugin/'); ?>"></a>
    <p class="settings-hint"><?php p($l->t('View the current status of the Swarm node(s) configured in \'External Storage\' section of NextCloud.')); ?></p>
	<?php
	// Get configured storage mounts from parameters
	$mounts = json_decode($_['mounts'], true);
	$mountIds = array_column($mounts, 'mount_id');
	// Make comma-seperated list of storageIds to use in hidden control in this form.
	$controlmountIds = implode(",", $mountIds);

	foreach ($mounts as $mount) :
		$mountId = $mount['mount_id'];
		$mountName = $mount['mount_name'];
		$encrypted = $mount['encrypt'];
		$batchId =  isset($mount['batchid']) ? $mount['batchid'] : "";
		$batchBalance = isset($mount['batchbalance']) ? $mount['batchbalance'] : "";
		$chequeBalance = isset($mount['chequebalance']) ? $mount['chequebalance'] : "";
	?>
	<div>
		<label>
			<h3><b><span id="<?php p($mountId);?>"><?php p($l->t('Swarm node')) ?></b>: <?php p($mountName); ?></span></h3>
		</label>
	</div>
	<div>
		<label>
			<span class="label"><?php p($l->t('Allow encryption')) ?></span>
			<?php
			if ($encrypted) {
				$checked = "checked";
			}?>
			<span><input id="beeswarm_encrypt_<?php p($mountId); ?>" type="checkbox" <?php p($checked) ?>/></span>
		</label>
	</div>
	<div>
		<label>
			<span class="label"><?php p($l->t('Current Batch Id')) ?></span>
			<span><input id="beeswarm_batchid_<?php p($mountId); ?>" type="text" maxlength="200" class="inputBatch" value="<?php p($batchId)?>" /></span>
		</label>
	</div>
	<div>
		<label>
			<span class="label"><?php p($l->t('Batch TTL')) ?></span>
			<span><input id="beeswarm-bzz_<?php p($mountId); ?>" type="text" placeholder="<?php p($l->t('Batch TTL')); ?>" value="<?php p($batchBalance) ?>" maxlength="20" readonly/><span>
			<span data-setting="bzz" data-toggle="tooltip" data-original-title="<?php p($l->t('Reset to default')); ?>" class="theme-undo icon icon-history"></span>
		</label>
	</div>
	<div>
		<label>
			<span class="label"><?php p($l->t('Chequebook Balance')) ?></span>
			<input id="beeswarm-chequebalance_<?php p($mountId);?>" type="text" maxlength="20" value="<?php p($chequeBalance) ?>" readonly/>
			<div data-setting="chequebalance_" data-toggle="tooltip" data-original-title="<?php p($l->t('Reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</label>
	</div>
	<?php endforeach;	?>
	<input id="mountsIds" type="hidden" value='<?php p($controlmountIds);?>'>

	<button id="beeswarm-save-settings"><?php p($l->t("Save")); ?></button>
</div>
