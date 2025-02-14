<template>
	<AppContent>
		<div class="section">
			<div class="header-wrapper">
				<h2>External Storage: Swarm By Hejbit</h2>
				<a
					target="_blank"
					rel="noreferrer noopener"
					class="icon-info"
					title="Open documentation"
					href="https://github.com/MetaProvide/nextcloud-swarm-plugin/">
				</a>
			</div>

			<h3>Telemetry</h3>
			<h4>Why Telemetry is Important for Us and for You</h4>

			<p>Our telemetry is designed solely to capture exceptions (errors) that occur while using our Nextcloud plugin. This means it focuses exclusively on identifying and understanding issues that might disrupt your experience. Here's why enabling telemetry can benefit both you and us:</p>
			<ol>
				<li>Improving Stability and Reliability: By collecting information about exceptions, we can quickly identify bugs or errors that users encounter. This allows us to fix problems faster and ensure the plugin runs smoothly for everyone.</li>
				<li>Proactive Problem Solving: Exception data helps us detect patterns in errors, even before users report them. This proactive approach ensures that we can address potential issues early, minimizing disruptions to your workflow.</li>
				<li>Focusing on What Matters: Since our telemetry is limited to exceptions, it avoids unnecessary data collection and focuses only on what's critical for improving the plugin's performance and reliability.</li>
				<li>Respecting Your Privacy: We understand the importance of privacy. That's why our telemetry is optional and designed to collect only anonymized data about errors. No personal or sensitive information is ever gathered, ensuring your data remains secure.</li>
			</ol>
			<p>By enabling telemetry, you're helping us create a more stable and reliable plugin for you and the entire Nextcloud community. It's a small step that makes a big difference in improving the quality of the tools you rely on every day. Thank you for considering this option!</p>

			<div class="settings-group">
				<CheckboxRadioSwitch
					:checked="telemetryEnabled"
					type="switch"
					:disabled="isSaving"
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

			<h3>Check the Status of the HejBit Application and Bee Node Services</h3>

			<p>To ensure seamless access to your decentralized data, it is essential that at least one Bee node service is operational alongside the HejBit Application. To help you stay informed, we've created a status monitoring page where you can check the current operational status of these services.</p>
			<p>You can view the status here: <a href="https://monitoring.metaprovide.org/status/hejbit" target="_blank" rel="noreferrer">HejBit Application Status</a></p>
			<p>This page provides real-time updates on the availability and performance of the HejBit Application and the Bee node services. By checking this link, you can quickly verify if everything is running smoothly or if there are any disruptions that might affect your access to HejBit decentralized data.</p>
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
