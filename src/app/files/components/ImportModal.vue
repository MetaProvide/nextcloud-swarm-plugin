<template>
	<NcModal
		:show="true"
		:name="labels.title"
		size="normal"
		@close="close"
	>
		<form class="hejbit-import-modal" @submit.prevent="submit">
			<h2>{{ labels.title }}</h2>
			<p class="hejbit-import-modal__description">
				{{ labels.description }}
			</p>

			<fieldset class="hejbit-import-modal__types" :disabled="isImporting">
				<legend>{{ labels.typeLegend }}</legend>
				<div class="hejbit-import-modal__radio-grid">
					<label class="hejbit-import-modal__radio">
						<input v-model="importType" type="radio" value="swarm" />
						<span>{{ labels.swarm }}</span>
					</label>
					<label class="hejbit-import-modal__radio">
						<input v-model="importType" type="radio" value="ipfs" />
						<span>{{ labels.ipfs }}</span>
					</label>
				</div>
			</fieldset>

			<label for="import-reference">
				<span class="hejbit-import-modal__label-text">{{ labels.referenceLabel }}</span>
				<NcInputField
					id="import-reference"
					v-model="reference"
					:label="inputLabel"
					:placeholder="placeholder"
					:disabled="isImporting"
					:error="hasReferenceError"
					:helper-text="referenceHelperText"
					type="text"
				/>
			</label>

			<div class="hejbit-import-modal__actions">
				<NcButton :disabled="isImporting" variant="secondary" @click="close">
					{{ labels.cancel }}
				</NcButton>
				<NcButton
					:disabled="!canSubmit"
					variant="primary"
					type="submit"
				>
					{{ isImporting ? labels.importing : labels.import }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script lang="ts">
import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { emit as emitEvent } from "@nextcloud/event-bus";
import { NcButton, NcInputField, NcModal } from "@nextcloud/vue";
import { defineComponent } from "vue";

type ImportType = "swarm" | "ipfs";

export default defineComponent({
	name: "ImportModal",
	components: {
		NcButton,
		NcInputField,
		NcModal,
	},
	props: {
		context: {
			type: Object,
			required: true,
		},
	},
	emits: ["close"],
	data() {
		return {
			importType: "swarm" as ImportType,
			reference: "",
			isImporting: false,
			submitted: false,
			labels: {
				title: t("files_external_ethswarm", "Import"),
				description: t(
					"files_external_ethswarm",
					"Import an existing Swarm BZZ Hash or IPFS CID into this HejBit storage.",
				),
				typeLegend: t("files_external_ethswarm", "Storage Type"),
				referenceLabel: t(
					"files_external_ethswarm",
					"Storage Reference",
				),
				swarm: t("files_external_ethswarm", "Swarm"),
				swarmReference: t("files_external_ethswarm", "Swarm BZZ Hash"),
				ipfs: t("files_external_ethswarm", "IPFS"),
				ipfsReference: t("files_external_ethswarm", "IPFS CID"),
				cancel: t("files_external_ethswarm", "Cancel"),
				import: t("files_external_ethswarm", "Import"),
				importing: t("files_external_ethswarm", "Importing..."),
				missingReference: t(
					"files_external_ethswarm",
					"Enter a reference to import.",
				),
				missingFolder: t(
					"files_external_ethswarm",
					"Could not find the current folder.",
				),
				success: t(
					"files_external_ethswarm",
					"Imported file successfully",
				),
				error: t("files_external_ethswarm", "Failed to import file"),
			},
		};
	},
	computed: {
		trimmedReference(): string {
			return this.reference.trim();
		},
		inputLabel(): string {
			return this.importType === "swarm"
				? this.labels.swarmReference
				: this.labels.ipfsReference;
		},
		placeholder(): string {
			return this.importType === "swarm"
				? this.labels.swarmReference
				: this.labels.ipfsReference;
		},
		hasReferenceError(): boolean {
			return this.submitted && this.trimmedReference.length === 0;
		},
		referenceHelperText(): string {
			return this.hasReferenceError ? this.labels.missingReference : "";
		},
		canSubmit(): boolean {
			return !this.isImporting && this.trimmedReference.length > 0;
		},
	},
	methods: {
		close() {
			if (!this.isImporting) {
				this.$emit("close");
			}
		},
		async submit() {
			this.submitted = true;
			if (!this.canSubmit) {
				return;
			}

			const encodedSource = (this.context as { encodedSource?: string })
				.encodedSource;
			if (!encodedSource) {
				showError(this.labels.missingFolder);
				return;
			}

			this.isImporting = true;
			try {
				const response = await axios({
					method: "post",
					url: encodedSource,
					headers: {
						"Hejbit-Action": "import",
						"Hejbit-Import-Type": this.importType,
						"Hejbit-Import-Reference": this.trimmedReference,
					},
				});

				if (response.data.status === true) {
					showSuccess(this.labels.success);
					emitEvent("files:config:updated", undefined);
					this.$emit("close");
				} else {
					showError(response.data.message || this.labels.error);
				}
			} catch (error) {
				console.error("Error while importing file", error);
				showError(this.labels.error);
			} finally {
				this.isImporting = false;
			}
		},
	},
});
</script>

<style scoped>
.hejbit-import-modal {
	display: flex;
	flex-direction: column;
	gap: 14px;
	padding: 22px 24px 20px;
}

.hejbit-import-modal h2 {
	margin: 0;
	font-size: 1.35rem;
	font-weight: 700;
}

.hejbit-import-modal__description {
	margin: 0;
	color: var(--color-text-maxcontrast);
	line-height: 1.4;
}

.hejbit-import-modal__types {
	margin: 0;
	padding: 0;
	border: 0;
}

.hejbit-import-modal__types legend {
	margin-bottom: 6px;
	font-weight: 600;
}

.hejbit-import-modal__radio-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 8px;
}

.hejbit-import-modal__radio {
	display: flex;
	align-items: center;
	gap: 10px;
	box-sizing: border-box;
	min-height: 44px;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	cursor: pointer;
	line-height: 1.2;
}

.hejbit-import-modal__radio input {
	flex: 0 0 auto;
	margin: 0;
	accent-color: var(--color-primary-element);
}

.hejbit-import-modal__radio:has(input:checked) {
	border-color: var(--color-primary-element);
	background: var(--color-primary-light);
}

.hejbit-import-modal__actions {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	margin-top: 2px;
}

@media (max-width: 640px) {
	.hejbit-import-modal__radio-grid {
		grid-template-columns: 1fr;
	}

	.hejbit-import-modal__actions {
		flex-direction: column-reverse;
	}
}
</style>
