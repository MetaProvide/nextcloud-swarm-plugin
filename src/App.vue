<template>
	<AppContent>
	<div class="section">
		<h2 class="inlineblock">External Storage: Swarm</h2>

		<div>
			parseMounts={{ parsedMounts }}
		</div>

 		<div v-for="(mount,idx) in parsedMounts" :key="mount.mount_id">
			<h3 >Swarm Node: <b>{{mount.mount_name}}</b>
			<Actions><ActionButton icon="icon-caret-dark" ></ActionButton></Actions>
			</h3>

			<div v-show="show" >
				<!--<Actions>
					<ActionButton icon="icon-delete" @click="alert('Delete')">Delete</ActionButton>
				</Actions>

				<Actions>
					<ActionText icon="icon-edit" title="Please edit the text" value="This is a textarea with title" />
					<ActionTextEditable icon="icon-edit" value="This is a text editable area" />
				</Actions>

				<Actions>
					<ActionTextEditable icon="icon-edit" :disabled="true" value="This is a disabled editable textarea" />
					<ActionTextEditable icon="icon-edit" title="Please edit the text" value="This is a textarea editable with title" />
				</Actions>-->
				<div>
					<CheckboxRadioSwitch :checked.sync="mount.isEncrypted" type="switch" @update:checked="toggleEncryption(idx)">Enable encryption</CheckboxRadioSwitch>
					<!--  -->
					selected: {{mount.encrypt}} mountid: "ethswarm_encrypt_{{mount.mount_id}}"
				</div>

				<div>
					Available chequebook balance (bzz): <input type="text" :value="mount.chequebalance" maxlength="200" readonly @click="alertme" @change="toggleNode"/>
				</div>

				<Actions>

				</Actions>

				<div><u>Stamp batches:</u></div>

				<div > <!--style="overflow-x: auto;"-->
					<table id="externalStorage" class="grid" ><!--class="grid" uses 100% width -->
						<thead>
							<tr>
								<th>Batch Id</th>
								<th>Bzz purchased</th>
								<th>Balance</th>
								<th>Active</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input id="ethswarm_batchid_" type="text" name="batchid" :value="mount.batchid" maxlength="200" @click="alertme" @change="toggleNode"/></td>
								<td><input id="ethswarm_bzz_" type="text" name="bzz" value="bzz" maxlength="200" readonly /></td>
								<td><input id="ethswarm_balance_" type="text" name="balance" :value="mount.batchbalance" maxlength="200" readonly /></td>
								<td ><CheckboxRadioSwitch id="ethswarm_active_" :disabled="true" type="switch"></CheckboxRadioSwitch></td>
								<td>
									<Actions>
										<!--ActionText icon="icon-edit" value="Top up" /-->
										<ActionInput id="ethswarm_topup_" type="number" :editable="true" icon="icon-add" >Top up (Bzz)</ActionInput>
										<ActionSeparator title="" />
										<ActionButton id="ethswarm_setactive_" :disabled="false" icon="icon-toggle" @click="alertme('Edit')">Set as active</ActionButton>
									</Actions>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div style="border-bottom: 1px solid #ccc !important; padding: 20px 20px 20px 20px;"></div>

				<div><u>Purchase new Stamp:</u></div>

				<div >
					<table id="" style="border-style: solid;">
						<thead>
							<tr>
								<th>Amount:</th>
								<th>Depth</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input type="number" name="amount" value="BatchId" maxlength="200" @click="alertme" @change="toggleNode"/></td>
								<td><input type="number" name="depth" value="bzz" maxlength="200" /></td>
								<td><input type="submit" value="Buy"  /></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div style="border-bottom: 2px solid #ccc !important; padding: 20px 20px 20px 20px;"></div>
			</div>
		</div>
	</div>
	</AppContent>
</template>

<script>
/* eslint-disable no-console */
import AppContent from "@nextcloud/vue/dist/Components/AppContent";
import Actions from '@nextcloud/vue/dist/Components/Actions';
/* import ActionText from '@nextcloud/vue/dist/Components/ActionText';
import ActionTextEditable from '@nextcloud/vue/dist/Components/ActionTextEditable'; */
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton';
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch';
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput';
import ActionSeparator from '@nextcloud/vue/dist/Components/ActionSeparator';
// More components and docs here: https://nextcloud-vue-components.netlify.app/
import { Bee } from "@ethersphere/bee-js";

export default {
	name: "App",
	components: {
		AppContent,Actions,ActionButton,CheckboxRadioSwitch,ActionInput,ActionSeparator
	},
	props: {
		settings: {
			type: Object,
			default() {
				return {};
			},
		},
	},
	data() {
		// Here the component data is stored
		return {
			beeClient: null, // with inital values
			show: true,
			parsedMounts: null,
		};
	},
	computed: {
	},
	async mounted() {
		this.parsedMounts = JSON.parse(this.settings.mounts);
		this.parsedMounts = this.parsedMounts.map(mount => ({ ...mount, isEncrypted: mount.encrypt === 1}));

		// Code in here runs before component is mounted to DOM
		console.log('XD1',this.settings,'XD2');
		this.beeClient = new Bee("http://localhost:1633");
		// Be aware, this creates on-chain transactions that spend Eth and BZZ!
		// const batchId = await this.beeClient.createPostageBatch('100', 17)
		// const fileHash = await this.beeClient.uploadData(batchId, "bee is awesome!")
		// const data = await this.beeClient.downloadData(fileHash)
	},
	methods: {
		toggleNode (evt) {
			console.log(evt);
		},
		alertme (evt) {
		 	alert(evt);
		},
		toggleEncryption(mountIdx) {
      		this.parsedMounts[mountIdx].encrypt = this.parsedMounts[mountIdx].isEncrypted ? 1 : 0;
  		},
	},
};
/* eslint-enable no-console */
</script>

<style scoped>
input[type=text][name='batchid'] {
	width:450px;
}

input[type=text] {
	width:250px;
}

.hide {
	visibility: hidden !important;
}
</style>
