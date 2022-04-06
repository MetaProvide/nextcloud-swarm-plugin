<template>
	<AppContent>
		<h2 class="aClass">Single-column main content</h2>
	</AppContent>
</template>

<script>
import AppContent from "@nextcloud/vue/dist/Components/AppContent";
// More components and docs here: https://nextcloud-vue-components.netlify.app/
import { Bee } from "@ethersphere/bee-js";

export default {
	name: "App",
	components: {
		AppContent,
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
		};
	},
	async mounted() {
		// Code in here runs before component is mounted to DOM
		this.beeClient = new Bee("http://localhost:1633");
		// Be aware, this creates on-chain transactions that spend Eth and BZZ!
		// const batchId = await this.beeClient.createPostageBatch('100', 17)
		// const fileHash = await this.beeClient.uploadData(batchId, "bee is awesome!")
		// const data = await this.beeClient.downloadData(fileHash)
	},
	methods: {},
};
</script>

<style scoped>
.aClass {
	background-color: green;
}
</style>
