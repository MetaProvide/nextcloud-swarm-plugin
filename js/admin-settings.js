document.addEventListener('DOMContentLoaded', () => {
	if (window.location.href.includes('settings/admin/externalstorages')) {
		const targetElement = document.querySelector('.settings-hint');
		if (targetElement) {
			const link = '<a href="https://metaprovide.org/hejbit/start" target="_blank" style="text-decoration: underline">metaprovide.org/hejbit/start</a>';
			const el = document.createElement('p');
			el.style.marginBottom = '10px';
			el.style.fontWeight = 'bold';
			el.innerHTML = `To get Swarm external storage access key, please visit ${link}`;
			targetElement.insertAdjacentElement('afterend', el);
		} else {
			console.log('settings-hint not found');
		}
	}
});
