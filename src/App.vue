<template>
	<Content app-name="files_external_ethswarm">
		<AppContent>
			<SettingsSection>
				<AppContentHeader>
					<HeaderDetails class="header-details">
						<h1>External Storage: Swarm By Hejbit</h1>
						<a
							target="_blank"
							rel="noreferrer noopener"
							class="icon-info"
							title="Open documentation"
							href="https://github.com/MetaProvide/nextcloud-swarm-plugin/">
						</a>
					</HeaderDetails>
				</AppContentHeader>

				<AppSettingsSection title="Telemetry">
						<strong>Why Telemetry is Important for Us and for You</strong>
						<p>Our telemetry is designed solely to capture exceptions (errors) that occur while using our Nextcloud plugin. This means it focuses exclusively on identifying and understanding issues that might disrupt your experience. Here's why enabling telemetry can benefit both you and us:</p>

						<ul>
							<li>Improving Stability and Reliability</li>
							<li>Proactive Problem Solving</li>
							<li>Focusing on What Matters</li>
							<li>Respecting Your Privacy</li>
						</ul>

						<div class="switch-wrapper">
							<CheckboxRadioSwitch
								:checked="telemetryEnabled"
								type="switch"
								:disabled="isSaving"
								@update:checked="toggleTelemetry">
								Enable Telemetry
							</CheckboxRadioSwitch>
						</div>

						<Button
							type="primary"
							:disabled="!hasChanges"
							:loading="isSaving"
							@click="saveSettings">
							{{ isSaving ? 'Saving...' : 'Save' }}
						</Button>

						<EmptyContent v-if="saveMessage" :title="saveMessage" :type="saveSuccess ? 'success' : 'error'" />
					</AppSettingsSection>
						<AppSettingsSection title="Service Status">
						<RichText>
							<strong>Check the Status of the HejBit Application and Bee Node Services</strong>
							<p>To ensure seamless access to your decentralized data, it is essential that at least one Bee node service is operational alongside the HejBit Application. Check out the status page for more information.</p>
							<p><a href="https://monitoring.metaprovide.org/status/hejbit" class="service-link" target="_blank" rel="noreferrer">HejBit Status</a></p>
						</RichText>

				</AppSettingsSection>
			</SettingsSection>
		</AppContent>
	</Content>
</template>

<script>
import { Content, AppContent, AppContentHeader, HeaderDetails, AppSettingsSection, SettingsSection, CheckboxRadioSwitch, EmptyContent, RichText } from '@nextcloud/vue';

import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

export default {
	name: 'App',
	components: {
		Content,
		AppContent,
		AppContentHeader,
		HeaderDetails,
		AppSettingsSection,
		SettingsSection,
		CheckboxRadioSwitch,
		EmptyContent,
		RichText,
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
.header-details {
	display: flex;
	align-items: center;
}

.header-details h1 {
	font-size: 2rem;
}

ul {
    list-style-type: disc;
    margin-left: 20px;
    margin-bottom: 20px;
}

li {
    margin: 8px 0;
}

.switch-wrapper {
    margin: 24px 0;
}

button {
    margin: 24px 0;
}

p {
    margin: 12px 0;
    line-height: 1.5;
}

strong {
    display: block;
    margin-bottom: 16px;
    font-size: 1.1em;
}

.service-link {
    display: inline-block;
    margin: 12px 0;
    padding: 8px 16px;
    background-color: var(--color-primary);
    color: var(--color-primary-text);
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.2s;
}

.service-link:hover {
    background-color: var(--color-primary-element-light);
}
</style>
