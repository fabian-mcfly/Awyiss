class Preview {
	previewConfig = {
		enabled: false,
		i18n: {
			disable: 'Disable',
			label: 'Preview Mode',
			markInactiveElements: 'Mark inactive elements',
		},
		markInactiveElements: false,
		settingsUrl: null,
	}

	/**
	 * @param {Object} previewConfig
	 */
	constructor(previewConfig) {
		// Iterate over the keys of the user-provided configuration
		Object.keys(previewConfig).forEach(key => {
			// Directly assign/update the property
			this.previewConfig[key] = previewConfig[key];
		});

		if (!this.previewConfig.enabled) {
			return;
		}

		if (this.previewConfig.markInactiveElements) {
			document.documentElement.classList.add('AwyissFrontendPreview-MarkInactiveElements');
		}

		this.createPreviewInfo();
	}

	/**
	 * Create the preview info element
	 */
	createPreviewInfo() {
		const previewInfo = document.createElement('div');
		previewInfo.id = 'AwyissFrontendPreview-Info';
		previewInfo.innerHTML = `
			<span class="AwyissFrontendPreview-Info-Title">${this.previewConfig.i18n.label}</span>
			<label class="AwyissFrontendPreview-Info-Option">
				<input type="checkbox" ${this.previewConfig.markInactiveElements ? 'checked' : ''} />
				${this.previewConfig.i18n.markInactiveElements}
			</label>
			<button class="AwyissFrontendPreview-Info-CloseButton">${this.previewConfig.i18n.disable}</button>
		`;

		previewInfo.querySelector('input').addEventListener('change', (event) => {
			const isChecked = event.target.checked;
			document.documentElement.classList.toggle('AwyissFrontendPreview-MarkInactiveElements', isChecked);

			this.saveSetting('markInactiveElements', isChecked ? '1' : '0');
		});

		previewInfo.querySelector('.AwyissFrontendPreview-Info-CloseButton').addEventListener('click', () => {
			this.saveSetting('enabled', '0').then(() => {
				location.replace(window.location.href);
			});
		});

		document.body.appendChild(previewInfo);
	}

	/**
	 * Save the preview mode setting
	 *
	 * @param {string} identifier
	 * @param {string} value
	 */
	saveSetting(identifier, value) {
		if (!this.previewConfig.settingsUrl) {
			return;
		}

		return fetch(this.previewConfig.settingsUrl, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				identifier,
				value
			})
		});
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		window.frontendPreview = new Preview(frontendPreviewConfig)
	});
}
else {
	window.frontendPreview = new Preview(frontendPreviewConfig);
}
