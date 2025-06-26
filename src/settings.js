document.addEventListener("DOMContentLoaded", () => {
	if (window.location.href.includes("settings/admin/externalstorages")) {
		const targetElement = document.querySelector(".settings-hint");
		if (targetElement) {
			const link =
				'<a href="https://app.hejbit.com" target="_blank" style="text-decoration: underline">app.hejbit.com</a>';
			const el = document.createElement("p");
			el.style.marginBottom = "10px";
			el.innerHTML = `To get <strong>Swarm</strong> external storage access key, please visit ${link}`;
			targetElement.insertAdjacentElement("afterend", el);
		} else {
			console.log("settings-hint not found");
		}
	}
});
