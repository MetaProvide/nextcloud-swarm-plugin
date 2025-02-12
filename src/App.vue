<template>
	<AppContent>
		<div class="section">
			<h2 class="inlineblock">External Storage: Swarm By Hejbit</h2>
			<a target="_blank" rel="noreferrer" class="icon-info" title="Open documentation" href="https://github.com/MetaProvide/nextcloud-swarm-plugin/"></a>

			<div class="settings-group">
				<CheckboxRadioSwitch
					:checked="telemetryEnabled"
					type="switch"
					@update:checked="toggleTelemetry">
					Enable Telemetry
				</CheckboxRadioSwitch>

				<button
					class="primary"
					:disabled="!hasChanges"
					@click="saveSettings">
					{{ isSaving ? 'Saving...' : 'Save' }}
				</button>

				<span v-if="saveMessage" :class="{'success': saveSuccess, 'error': !saveSuccess}">
					{{ saveMessage }}
				</span>
			</div>
		</div>
	</AppContent>
</template>

<script>
import AppContent from "@nextcloud/vue/dist/Components/AppContent";
import CheckboxRadioSwitch from "@nextcloud/vue/dist/Components/CheckboxRadioSwitch";
import axios from "axios";
import { generateUrl } from "@nextcloud/router";

export default {
	name: "App",
	components: {
		AppContent,
		CheckboxRadioSwitch,
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
			telemetryEnabled: false,
			originalTelemetryState: false,
			isSaving: false,
			saveMessage: '',
			saveSuccess: false
		};
	},
	computed: {
		hasChanges() {
			return this.telemetryEnabled !== this.originalTelemetryState;
		},
	},
	mounted() {
		this.telemetryEnabled = this.settings.telemetry_enabled || false;
		this.originalTelemetryState = this.telemetryEnabled;
	},
	methods: {
		toggleTelemetry(value) {
			this.telemetryEnabled = value;
		},
		async saveSettings() {
			this.isSaving = true;
			this.saveMessage = '';

			try {
				await axios.post(generateUrl('/apps/files_external_ethswarm/settings'), {
					telemetry: this.telemetryEnabled ? '1' : '0'
				});

				this.saveSuccess = true;
				OC.Notification.showTemporary(t('files_external_ethswarm', 'Settings saved'));
				this.originalTelemetryState = this.telemetryEnabled;
			} catch (error) {
				console.error('Error saving settings:', error);
				this.saveSuccess = false;
				OC.Notification.showTemporary(t('files_external_ethswarm', 'Error saving settings'));
			} finally {
				this.isSaving = false;
			}
		},
	},
};
</script>

<style scoped>
.settings-group {
	margin-top: 20px;
}

button {
	margin-top: 10px;
}
</style>
