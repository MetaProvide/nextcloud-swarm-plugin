<template>
	<NcContent app-name="files_external_ethswarm" about="HejBit Settings">
		<NcAppContent>
			<NcSettingsSection name="Telemetry">
				<strong
					>Why Telemetry is Important for Us and for You</strong
				>
				<p>
					Our telemetry is designed solely to capture exceptions
					(errors) that occur while using our Nextcloud plugin.
					This means it focuses exclusively on identifying and
					understanding issues that might disrupt your experience.
					Here's why enabling telemetry can benefit both you and
					us:
				</p>

				<ul>
					<li>Improving Stability and Reliability</li>
					<li>Focusing on What Matters</li>
					<li>Proactive Problem Solving</li>
					<li>Respecting Your Privacy</li>
				</ul>

				<div class="switch-wrapper">
					<NcCheckboxRadioSwitch
						:checked="telemetryEnabled"
						type="switch"
						:disabled="isSaving"
						@update:checked="toggleTelemetry"
					>
						Enable Telemetry
					</NcCheckboxRadioSwitch>
				</div>

				<NcButton
					type="primary"
					:disabled="!hasChanges"
					:loading="isSaving"
					@click="saveSettings"
				>
					{{ isSaving ? "Saving..." : "Save" }}
				</NcButton>

				<NcEmptyContent
					v-if="saveMessage"
					:title="saveMessage"
					:type="saveSuccess ? 'success' : 'error'"
				/>
			</NcSettingsSection>

			<NcSettingsSection name="Status">
				<strong
					>Check the Status of the HejBit Application and Bee Node
					Services</strong
				>
				<p>
					To ensure seamless access to your decentralized data, it
					is essential that at least one Bee node service is
					operational alongside the HejBit Application. Check out
					the status page for more information.
				</p>
				<p>
					<a
						href="https://monitoring.metaprovide.org/status/hejbit"
						class="service-link"
						target="_blank"
						rel="noreferrer"
						>HejBit Status</a
					>
				</p>
			</NcSettingsSection>
		</NcAppContent>
	</NcContent>
</template>

<script>
import {
	NcAppContent,
	NcButton,
	NcCheckboxRadioSwitch,
	NcContent,
	NcEmptyContent,
	NcSettingsSection,
} from "@nextcloud/vue";

import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
	name: "Setting",
	components: {
		NcContent,
		NcAppContent,
		NcSettingsSection,
		NcCheckboxRadioSwitch,
		NcEmptyContent,
		NcButton,
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
			saveMessage: "",
			saveSuccess: false,
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
			this.saveMessage = "";

			try {
				await axios.post(
					generateUrl("/apps/files_external_ethswarm/settings"),
					{
						telemetry: this.telemetryEnabled,
					}
				);

				this.saveSuccess = true;
				OC.Notification.showTemporary(
					t("files_external_ethswarm", "Settings saved")
				);
				this.originalTelemetryState = this.telemetryEnabled;
			} catch (error) {
				console.error("Error saving settings:", error);
				this.saveSuccess = false;
				OC.Notification.showTemporary(
					t("files_external_ethswarm", "Error saving settings")
				);
			} finally {
				this.isSaving = false;
			}
		},
	},
};
</script>

<style scoped>
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
