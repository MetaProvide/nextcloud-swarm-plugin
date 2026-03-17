import { subscribe } from "@nextcloud/event-bus";
import Feedback from "@betahuhn/feedback-js";
import axios from "@nextcloud/axios";
import { getCurrentUser } from "@nextcloud/auth";
import { generateOcsUrl, generateUrl } from "@nextcloud/router";
import HejBitLogo from "../../img/hejbit-logo.svg";
import FeaturesHelper from "@/util/FeaturesHelper";

if (typeof Feedback?.prototype?._addStyle === "function") {
	Feedback.prototype._addStyle = () => {};
}

const feedback = {
	async enabled() {
		return FeaturesHelper.bool("show-feedback-form", true);
	},
	async resolver(data) {
		if (!(await this.enabled())) return;

		if (typeof data.folder === "undefined") return;

		const isPathHejBitStorage = data.folder?.attributes["ethswarm-node"];

		const renderedFeedback = document.getElementById("feedback-root");

		if (isPathHejBitStorage && !renderedFeedback) {
			this.render();
			this.fetchUserEmail();
		} else if (!isPathHejBitStorage && renderedFeedback) {
			renderedFeedback.remove();
		}
	},
	render() {
		try {
			const feedbackButton = `<img src="${HejBitLogo}" alt="HejBit" style="height: 20px; vertical-align: middle;margin-right:10px">HejBit Feedback`;
			const feedbackInstance = new Feedback({
				id: "feedback",
				endpoint: generateUrl(
					"/apps/files_external_ethswarm/feedback/submit",
				),
				emailField: true,
				events: false,
				forceShowButton: false,
				types: {
					general: {
						text: "General",
						icon: "📝",
					},
					idea: {
						text: "Idea",
						icon: "💡",
					},
					bug: {
						text: "Issue",
						icon: "⚠️",
					},
				},
				btnTitle: feedbackButton,
				title: feedbackButton,
				inputPlaceholder: "We welcome your feedback here.",
				submitText: "Submit",
				backText: "Back",
				typeMessage: "How can we improve HejBit?",
				success: "We Appreciate Your Feedback!",
				failedTitle: "Oops, an error occurred!",
				failedMessage:
					"Please try again. If this keeps happening, try to send an email to support@hejbit.com instead.",
				position: "right",
			});
			feedbackInstance.renderButton();
			this.loaded = true;
		} catch (error) {
			console.error("Error:", error);
		}
	},
	fetchUserEmail() {
		const currentUser = getCurrentUser();
		if (!currentUser?.uid) {
			return;
		}

		axios
			.get(generateOcsUrl("/cloud/users/{uid}", { uid: currentUser.uid }))
			.then((response) => {
				const email = response.data.ocs.data.email;
				const observer = new MutationObserver((mutations) => {
					for (const mutation of mutations) {
						if (mutation.addedNodes.length) {
							const emailField = document.querySelector(
								"input#feedback-email",
							);
							if (emailField) {
								emailField.value = email;
								emailField.dispatchEvent(
									new Event("input", { bubbles: true }),
								);
								observer.disconnect();
								break;
							}
						}
					}
				});
				observer.observe(document.querySelector("div#feedback-root"), {
					childList: true,
					subtree: true,
				});
			})
			.catch((error) => {
				console.error("Error fetching user data:", error);
			});
	},
};

subscribe("files:list:updated", (data) => feedback.resolver(data));
