<template>
	<AppContent>
		<div class="section">
			<h2 class="inlineblock">External Storage: Swarm</h2>

			<div>
				Access key:
				<input v-model="accessKey" type="text" maxlength="200" />
				<button @click="verifyAccessKey">Verify</button>

				<!-- message box -->
				<div v-if="accessKeySubmitted" class="message">
					<div class="message-text">
						<span v-if="hasAccess" class="icon-checkmark"></span>
						<span v-else class="icon-alert-outline-white"></span>
						<span>{{ message }}</span>
						<button @click="accessKeySubmitted = false">
							Close
						</button>
					</div>
				</div>
			</div>

			<a
				target="_blank"
				rel="noreferrer"
				class="icon-info"
				title="Open documentation"
				href="https://github.com/MetaProvide/nextcloud-swarm-plugin/"
			></a>
			<p class="settings-hint">
				View the current status of the Swarm node(s) configured in
				'External Storage' section of NextCloud.
			</p>
			<div v-if="parsedMounts.length === 0">
				Please configure a Swarm storage in the
				<a href="externalstorages">"External Storage"</a> Administration
				section.
			</div>
			<div
				v-for="(mount, mountidx) in parsedMounts"
				:key="mount.mount_id"
			>
				<div @click="setSaveMessage(mountidx, '')">
					<h3>
						Swarm Node: <b>{{ mount.mount_name }}</b>
						<Actions>
							<ActionButton
								icon="icon-caret-dark"
								@click="showNode(mountidx)"
							></ActionButton>
						</Actions>
					</h3>

					<div v-if="toggleNode[mountidx]">
						<div>
							<CheckboxRadioSwitch
								:checked.sync="mount.isEncrypted"
								type="switch"
								@update:checked="toggleEncryption(mountidx)"
							>
								Enable encryption
							</CheckboxRadioSwitch>
						</div>

						<div>
							Available chequebook balance (bzz):
							<input
								type="text"
								:value="mount.chequebalance"
								maxlength="200"
								readonly
							/>
						</div>

						<div><u>Stamp batches:</u></div>

						<div>
							<table id="externalStorage" class="grid">
								<thead>
									<tr>
										<th>Batch Id</th>
										<th>Bzz purchased</th>
										<th>Balance</th>
										<th>Usage</th>
										<th>Usable</th>
										<th>Active</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr
										v-for="(
											batch, batchidx
										) in mount.batches"
										:key="batchidx"
									>
										<td>
											<input
												type="text"
												name="batchid"
												:value="batch.batchID"
												maxlength="200"
												readonly
											/>
										</td>
										<td>
											<input
												type="text"
												name="bzz"
												:value="batch.amount"
												maxlength="200"
												readonly
											/>
										</td>
										<td>
											<input
												type="text"
												name="balance"
												:value="batch.batchTTL"
												maxlength="200"
												readonly
											/>
										</td>
										<td>
											<span>
												{{ stampsRemainingCapacity[batch.batchID]}} remaining out of {{ stampsCapacity[batch.batchID] }} {{ stampsPercentageUsage[batch.batchID] }} 
											</span>
										</td>
										<td>
											<CheckboxRadioSwitch
												:checked.sync="batch.isUsable"
												:disabled="true"
												type="switch"
												name="toggleUsableBatchName"
											>
											</CheckboxRadioSwitch>
										</td>
										<td>
											<CheckboxRadioSwitch
												:checked.sync="batch.isActive"
												type="switch"
												name="toggleActiveBatchName"
												@update:checked="
													toggleActiveBatch(
														mountidx,
														batchidx,
														batch.batchID
													)
												"
											>
											</CheckboxRadioSwitch>
										</td>
										<td>
											<Actions>
												<ActionButton
													:disabled="true"
													icon="icon-add"
													>Top up (Bzz)
												</ActionButton>
												<ActionInput
													type="number"
													:editable="true"
													:value="batch.topUpValue"
													@update:value="
														(x) =>
															handleTopUpChange(
																x,
																mountidx
															)
													"
													@submit="
														topupBatch(
															mountidx,
															batchidx,
															batch.batchID
														)
													"
												>
												</ActionInput>
												<ActionSeparator title="" />
											</Actions>
										</td>
									</tr>
									<tr>
										<td colspan="6">
											<input
												type="submit"
												:value="
													saveSettingsValue[mountidx]
												"
												:disabled="
													saveSettingsBtn[mountidx]
												"
												@click="
													saveSettings(
														mountidx,
														$event
													)
												"
											/>&nbsp;&nbsp;&nbsp;{{
												saveSettingsLabel[mountidx]
											}}
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div name="sectionline"></div>

						<div><u>Purchase new Stamp:</u></div>

						<div>
							<form @submit.prevent>
								<table>
									<thead>
										<tr>
											<th>Amount:</th>
											<th>Depth (17-255):</th>
											<th>&nbsp;</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>
												<input
													v-model="
														newBatchAmounts[
															mountidx
														]
													"
													type="number"
													value=""
													maxlength="10"
												/>
											</td>
											<td>
												<input
													v-model="
														newBatchDepths[mountidx]
													"
													type="number"
													value=""
													maxlength="17"
												/>
											</td>
											<td>
												<input
													type="submit"
													:disabled="
														newBatchBtnDisabled[
															mountidx
														]
													"
													value="Buy"
													@click="
														buyPostage(
															mountidx,
															$event
														)
													"
												/>&nbsp;&nbsp;&nbsp;{{
													newBatchLabel[mountidx]
												}}
											</td>
										</tr>
									</tbody>
								</table>
							</form>
						</div>
						<div name="mainline"></div>
					</div>
				</div>
			</div>
		</div>
	</AppContent>
</template>

<script>
/* eslint-disable no-console */
import AppContent from "@nextcloud/vue/dist/Components/AppContent";
import Actions from "@nextcloud/vue/dist/Components/Actions";
import ActionButton from "@nextcloud/vue/dist/Components/ActionButton";
import CheckboxRadioSwitch from "@nextcloud/vue/dist/Components/CheckboxRadioSwitch";
import ActionInput from "@nextcloud/vue/dist/Components/ActionInput";
import ActionSeparator from "@nextcloud/vue/dist/Components/ActionSeparator";
import axios from "axios";
import { generateUrl } from "@nextcloud/router";

export default {
	name: "App",
	components: {
		AppContent,
		Actions,
		ActionButton,
		CheckboxRadioSwitch,
		ActionInput,
		ActionSeparator,
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
		return {
			beeClient: null,
			accessKey: null,
			hasAccess: false,
			message: null,
			accessKeySubmitted: false,
			show: true,
			parsedMounts: [],
			stampsCapacity: [],
			stampsRemainingCapacity: [],
			stampsPercentageUsage: [],
			stampsUsage: [],
			newBatchAmounts: [],
			newBatchDepths: [],
			newBatchLabel: [],
			newBatchBtnDisabled: [],
			toggleNode: [],
			saveSettingsValue: [],
			saveSettingsBtn: [],
			saveSettingsLabel: [],
			debugConsole: true, // set true to write to console.log, false to disable console.log
		};
	},
	computed: {},
	async mounted() {
		this.parsedMounts = JSON.parse(this.settings.mounts);
		this.parsedMounts = this.parsedMounts.map((mount) => ({
			...mount,
			isEncrypted: mount.encrypt === 1,
			batches: mount.batches.map((b) => ({ ...b, topUpValue: 0 })),
		}));
		this.settings.stampsUsage.forEach((stamp) => {
			const batchID = stamp.batchID;
			this.stampsCapacity[batchID] = stamp.capacity;
			this.stampsRemainingCapacity[batchID] = stamp.remainingCapacity;
			this.stampsPercentageUsage[batchID] = stamp.usage;
		});

		this.newBatchAmounts = Array(this.parsedMounts.length).fill("");
		this.newBatchDepths = Array(this.parsedMounts.length).fill("");
		this.newBatchLabel = Array(this.parsedMounts.length).fill("");
		this.newBatchBtnDisabled = Array(this.parsedMounts.length).fill(false);
		this.topUpValue = Array(this.parsedMounts.length).fill("");
		this.toggleNode = Array(this.parsedMounts.length).fill(false);
		this.saveSettingsValue = Array(this.parsedMounts.length).fill(
			"Save Settings"
		);
		this.saveSettingsBtn = Array(this.parsedMounts.length).fill(false);
		this.saveSettingsLabel = Array(this.parsedMounts.length).fill("");
		if (!this.debugConsole) {
			const methods = ["log", "debug", "warn", "info"];
			for (let i = 0; i < methods.length; i++) {
				console[methods[i]] = function () {};
			}
		}
	},
	methods: {
		async verifyAccessKey() {
			this.accessKeySubmitted = true;
			if (!this.accessKey) {
				this.message = "Please enter an access key";
				return;
			}

			try {
				const response = await axios.post(
					generateUrl(
						"/apps/files_external_ethswarm/bee/verifyBeeNodeAccess"
					),
					{ access_key: this.accessKey }
				);
				this.hasAccess = response.status === 200;

				if (this.hasAccess) {
					this.message = "Access key verified successfully";
				} else {
					this.message = response.data.msg;
				}
			} catch (error) {
				console.error("There was a problem with the request:", error);
				this.message = error.response.data.msg;
			}
		},
		getRequestOptions(authUser, authPassword) {
			let requestOptions = null;
			if (authUser && authPassword) {
				requestOptions = {
					headers:
						"Authorization: Basic " +
						btoa(authUser + ":" + authPassword),
				};
			}
			return requestOptions;
		},
		showNode(mountIdx) {
			const newToggleNode = [...this.toggleNode];
			newToggleNode[mountIdx] = !newToggleNode[mountIdx];
			this.toggleNode = newToggleNode;
		},
		toggleEncryption(mountIdx) {
			this.parsedMounts[mountIdx].encrypt = this.parsedMounts[mountIdx]
				.isEncrypted
				? 1
				: 0;
		},
		toggleActiveBatch(mountIdx, batchIdx, activeBatchId) {
			this.parsedMounts[mountIdx].batchid = "";
			if (this.parsedMounts[mountIdx].batches[batchIdx].isActive) {
				this.parsedMounts[mountIdx].batchid = activeBatchId;
			}
			const tmpParsedMounts = [...this.parsedMounts];
			let bIdx = 0;
			for (const batch of tmpParsedMounts[mountIdx].batches) {
				if (batchIdx !== bIdx && batch.isActive) {
					batch.isActive = !batch.isActive;
					console.log(
						"Set batch.isActive 1 = " +
							batch.isActive +
							" (" +
							bIdx +
							")"
					);
				}
				bIdx++;
			}
			this.parsedMounts = tmpParsedMounts;
		},
		handleTopUpChange(x, mountIdx) {
			const newTopUp = [...this.topUpValue];
			newTopUp[mountIdx] = x;
			this.topUpValue = newTopUp;
		},
		/**
		 * User input validation
		 *
		 * @param {bigint} amount amount to check
		 * @param {number} depth depth to check (optional)
		 * @return {boolean} true if input is valid.
		 * Throws a string exception with an error message
		 */
		isInputValid(amount, depth) {
			if (amount <= 0) {
				throw t("files_external_ethswarm", "Please enter an amount");
			} else if (depth !== undefined && (depth < 17 || depth > 255)) {
				throw t(
					"files_external_ethswarm",
					"Please enter a valid depth between 17 and 255"
				);
			}
			return true;
		},
		async topupBatch(mountIdx, batchIdx, activeBatchId) {
			const url = generateUrl(
				"/apps/files_external_ethswarm/bee/topUpBatch"
			);
			const postageBatch = this.parsedMounts[mountIdx];
			postageBatch.activeBatchId = activeBatchId;
			postageBatch.topUpValue = Number(this.topUpValue[mountIdx]);
			console.log(
				"json=" +
					JSON.stringify(this.parsedMounts) +
					";postageBatch=" +
					JSON.stringify(postageBatch) +
					"batch,amount=" +
					activeBatchId +
					"," +
					postageBatch.topUpValue
			);

			try {
				this.isInputValid(postageBatch.topUpValue);
			} catch (error) {
				console.log(error);
				return false;
			}
			await axios
				.post(url, {
					postageBatch: JSON.stringify(postageBatch),
				})
				.then((response) => {
					console.log("Success", response.data.batchID);
				})
				.catch((error) => {
					console.log(
						"response err=" +
							error.response +
							";mesg=" +
							error.response.data.msg +
							"error.msg=" +
							error.message
					);
					console.log(error);
				});
		},
		async buyPostage(mountidx, evt) {
			if (evt) {
				evt.preventDefault();
			}
			const postageBatch = this.parsedMounts[mountidx];
			postageBatch.amount = Number(this.newBatchAmounts[mountidx]);
			postageBatch.depth = Number(this.newBatchDepths[mountidx]);

			console.log(
				"amount,depth=" + postageBatch.amount + "," + postageBatch.depth
			);

			let newBatchlabel = [...this.newBatchLabel];
			try {
				this.isInputValid(postageBatch.amount, postageBatch.depth);
			} catch (errorMessage) {
				newBatchlabel[mountidx] = errorMessage;
				this.newBatchLabel = newBatchlabel;
				return false;
			}
			newBatchlabel[mountidx] = "Status...";
			this.newBatchLabel = newBatchlabel;

			this.newBatchBtnDisabled[mountidx] = true;

			newBatchlabel = [...this.newBatchLabel];
			const url = generateUrl(
				"/apps/files_external_ethswarm/bee/createPostageBatch"
			);

			console.log(
				"json=" +
					JSON.stringify(this.parsedMounts) +
					";len=" +
					this.parsedMounts.length +
					";url=" +
					url +
					";newparse=" +
					JSON.stringify(postageBatch)
			);

			await axios
				.post(url, {
					postageBatch: JSON.stringify(postageBatch),
				})
				.then((response) => {
					const newBatchId = response.data.batchID;
					newBatchlabel[mountidx] =
						"Success: Created new batch " + newBatchId;

					this.parsedMounts[mountidx].batches.push({
						batchID: newBatchId,
						amount: this.newBatchAmounts[mountidx],
						batchTTL: "",
						isActive: false,
						isDisabled: false,
						isUsable: false,
					});
					this.newBatchLabel = newBatchlabel;
					this.newBatchBtnDisabled[mountidx] = false;
				})
				.catch((error) => {
					console.log(
						"response err=" +
							error.response +
							";mesg=" +
							error.response.data.msg +
							"error.msg=" +
							error.message
					);
					console.log(error);
					newBatchlabel[mountidx] = error.response.data.msg;
					this.newBatchLabel = newBatchlabel;
					this.newBatchBtnDisabled[mountidx] = false;
				});
		},
		async saveSettings(mountidx, evt) {
			if (evt) {
				evt.preventDefault();
			}
			this.setSaveBtnValue(mountidx, "Saving...");
			this.setSaveMessage(mountidx, "");
			this.saveSettingsBtn[mountidx] = true;

			const url = generateUrl("/apps/files_external_ethswarm/save");
			const parsedMountsToSave = this.parsedMounts.map((mount) => ({
				mount_id: mount.mount_id,
				encrypt: mount.encrypt,
				batchid: mount.batchid,
			}));
			console.log(
				"json=" +
					JSON.stringify(this.parsedMounts) +
					";len=" +
					this.parsedMounts.length +
					";url=" +
					url +
					";newparse=" +
					JSON.stringify(parsedMountsToSave)
			);
			await axios
				.post(url, {
					storageconfig: JSON.stringify(parsedMountsToSave),
					swarm_access_key: this.swarm_access_key,
				})
				.then((response) => {
					this.setSaveMessage(mountidx, "Saved!");
				})
				.catch((error) => {
					console.log(
						"response err=" +
							error.response +
							";mesg=" +
							error.response.data.message +
							"error.msg=" +
							error.message
					);
					this.setSaveMessage(
						mountidx,
						"Failed to save: " +
							(error.response
								? error.response.data.message
								: error)
					);
				});

			this.setSaveBtnValue(mountidx, "Save Settings");
			this.saveSettingsBtn[mountidx] = false;
		},
		setSaveMessage(mountidx, message) {
			// Set label
			const newSaveSettingsLabel = [...this.saveSettingsLabel];
			newSaveSettingsLabel[mountidx] = message;
			this.saveSettingsLabel = newSaveSettingsLabel;
		},
		setSaveBtnValue(mountidx, message) {
			// Set new button value
			const newsaveSettingsValue = [...this.saveSettingsValue];
			newsaveSettingsValue[mountidx] = message;
			this.saveSettingsValue = newsaveSettingsValue;
		},
	},
};
/* eslint-enable no-console */
</script>

<style scoped>
input[type="text"][name="batchid"] {
	width: 450px;
}

input[type="text"] {
	width: 250px;
}

div[name="mainline"] {
	border-bottom: 2px solid #ccc !important;
	padding: 20px 20px 20px 20px;
}

div[name="sectionline"] {
	border-bottom: 1px solid #ccc !important;
	padding: 20px 20px 20px 20px;
}

.hide {
	visibility: hidden !important;
}

a {
	border: 0;
	text-decoration: underline;
	cursor: pointer;
}
</style>
