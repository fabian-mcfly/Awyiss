// noinspection JSUnusedGlobalSymbols

export default class DesignsController {
	/**
	 * Event handler
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('DesignsController')) {
			return;
		}

		const form = document.querySelector('#DesignForm');
		this.eventHandler.add('input', (event) => {
			clearTimeout(form.inputTimeout);

			form.inputTimeout = setTimeout(() => {
				this.handleFormInput(form, event);
			}, 500);
		}, form);

		const rangeInputs = document.querySelectorAll('input[type="range"]');
		rangeInputs.forEach((rangeInput) => {
			this.initRangeInput(rangeInput);
		});

		const fontInputs = document.querySelectorAll('.FormInputType-Font');
		fontInputs.forEach((fontInput) => {
			this.initFontInput(fontInput);
		});

		const saveDialog = document.querySelector('#SaveDialog');
		if (saveDialog) {
			this.initSaveDialog(saveDialog);
		}
	}


	/**
	 * Handle form input
	 *
	 * @param {HTMLFormElement} form
	 * @param {Event} event
	 */
	handleFormInput(form, event) {
		// Get the complete form data
		const formData = new FormData(form);
		const previewFrame = document.querySelector('#Preview');
		const allowGoogleFonts = previewFrame.dataset.allowGoogleFonts === 'true';
		const previewDoc = previewFrame.contentDocument;
		const fontFamilies = [];

		// Convert FormData to a plain object
		formData.forEach((value, key) => {
			if (
				key.includes('[') ||
				['save_as_copy', 'reload_form'].includes(key) ||
				key.endsWith('_unit')
			) {
				return;
			}

			// If a key exists with the current name plus '_unit', add the unit to the value
			const unitKey = key + '_unit';
			if (formData.has(unitKey)) {
				const unit = formData.get(unitKey);
				if (unit) {
					value += unit;
				}
			}

			// Transform the key to camelBack case
			const property = key.replace(/_([a-z])/g, (match, letter) => letter.toUpperCase());

			if (key.startsWith('font_name_')) {
				if (!allowGoogleFonts) {
					return;
				}

				if (formData.has(`custom[${key}]`) && formData.get(`custom[${key}]`)) {
					value = '"' + formData.get(`custom[${key}]`) + '"';
				}
				else {
					// Check if the input for key is a select, if so, get the selected option and its title
					const fontSelect = form.querySelector(`select[name="${key}"]`);
					if (fontSelect) {
						const selectedOption = fontSelect.selectedOptions[0];
						if (selectedOption) {
							value = '"' + selectedOption.title + '"';
							fontFamilies.push(selectedOption.title);
						}
					}
				}
			}

			previewDoc.documentElement.style.setProperty(`--${property}`, value);

			if (key.startsWith('font_name_')) {
				let fontCategory = key.replace('font_name_', '');
				fontCategory = fontCategory.charAt(0).toUpperCase() + fontCategory.slice(1);
				fontCategory = fontCategory.replace(/_([a-z])/g, (match, letter) => letter.toUpperCase());

				const property = `fontStack${fontCategory}`;

				let fallbackKey = key.replace('font_name_', 'font_stack_fallback_');
				if (formData.has(fallbackKey) && formData.get(fallbackKey)) {
					value += `, ${formData.get(fallbackKey)}`;
				}

				previewDoc.documentElement.style.setProperty(`--${property}`, value);
			}
		});

		if (!allowGoogleFonts) {
			return;
		}

		// Get the fonts preview style tag
		let googleFontsPreviewStyle = previewDoc.getElementById('GoogleFontsPreviewStyle');
		if (!googleFontsPreviewStyle) {
			googleFontsPreviewStyle = previewDoc.createElement('link');
			googleFontsPreviewStyle.id = 'GoogleFontsPreviewStyle';
			googleFontsPreviewStyle.rel = 'stylesheet';
			previewDoc.head.appendChild(googleFontsPreviewStyle);
		}

		// Get the fonts preview style href
		let googleFontsPreviewStyleHref = '';
		if (fontFamilies.length) {
			googleFontsPreviewStyleHref = 'https://fonts.googleapis.com/css2?family=' + fontFamilies.join('&family=') + '&display=swap';
		}

		// Update the href of the fonts preview style tag
		if (googleFontsPreviewStyle.href !== googleFontsPreviewStyleHref) {
			googleFontsPreviewStyle.href = googleFontsPreviewStyleHref;
		}
	}


	/**
	 * Initialize a font input
	 * @param {HTMLElement} fontInput
	 */
	initFontInput(fontInput) {
		const fontSelect = fontInput.querySelector('select');
		const name = fontSelect.getAttribute('name');
		const customFont = fontInput.querySelector('.FormInputType-CustomFontName');

		this.eventHandler.add('input', () => {
			const selectedFont = fontSelect.value;
			customFont.classList.toggle('Visible', selectedFont === 'custom');
			customFont.querySelector('input').required = selectedFont === 'custom';

			// Find the optgroup of the selected font
			const selectedOption = fontSelect.selectedOptions[0];
			const optgroup = selectedOption.parentNode;

			const webfont = webfonts?.[optgroup?.label]?.[selectedOption?.value] || null;

			const fontVariantInputs = fontInput.querySelectorAll(`input[name="font_variants[${name}][]"]`);
			fontVariantInputs.forEach((fontVariantInput) => {
				let value = fontVariantInput.value + '';

				if (value === '400') {
					value = 'regular';
				}
				else if (value === '400i') {
					value = 'italic';
				}

				if (value.endsWith('i')) {
					value += 'talic';
				}

				fontVariantInput.disabled = selectedFont !== 'custom' && !(webfont?.variants?.includes(value) || false);
			});
		}, fontSelect);

		// Trigger update
		fontSelect.dispatchEvent(new Event('input'));
	}


	/**
	 * Initialize a range input
	 * @param {HTMLElement} rangeInput
	 */
	initRangeInput(rangeInput) {
		const name = rangeInput.getAttribute('name');

		if (!name || ['clr-alpha-slider', 'clr-hue-slider'].includes(name)) {
			return;
		}

		const formInput = rangeInput.closest('.FormInputType-Range');
		const input = formInput?.querySelector(`input[name="${name}"][type="text"]`);
		if (!input) {
			return;
		}

		const unitSelect = formInput.querySelector(`select[name="${name}_unit"]`);

		this.eventHandler.add('input', () => {
			input.value = rangeInput.value;
		}, rangeInput);

		this.eventHandler.add('input', () => {
			clearTimeout(input.inputTimeout);

			input.inputTimeout = setTimeout(() => {
				let value = input.value;

				// Remove all non-numeric characters except for the decimal separator
				value = value.replace(/,/g, '.');
				value = value.replace(/[^0-9.]/g, '');

				// If the original value has a unit, select it in the unit dropdown (if it exists)
				if (unitSelect) {
					const unit = input.value.replace(/[^a-zA-Z]/g, '').toLowerCase();
					const option = unitSelect.querySelector(`option[value="${unit}"]`);
					if (unit && option) {
						unitSelect.value = option.value;

						// Update the range input's attributes
						this.updateRangeInput(unitSelect, rangeInput);
					}
				}

				value = this.normalizeRangeValue(rangeInput, value);

				input.value = value;
				rangeInput.value = value;
			}, 500);
		}, input);

		if (unitSelect) {
			this.eventHandler.add('input', () => {
				this.updateRangeInput(unitSelect, rangeInput);

				let value = rangeInput.value;

				value = this.normalizeRangeValue(rangeInput, value);

				input.value = value;
				rangeInput.value = value;
			}, unitSelect);

			// Trigger update
			this.updateRangeInput(unitSelect, rangeInput);
		}
	}


	/**
	 * Normalize a range input value
	 * @param {HTMLElement} rangeInput
	 * @param {number | string} value
	 * @returns {number}
	 */
	normalizeRangeValue = (rangeInput, value) => {
		const initialValue = value;
		// Check if the value is in the range of the range input
		const min = parseFloat(rangeInput.min);
		const max = parseFloat(rangeInput.max);

		value = Math.max(min, Math.min(max, value));

		if (value === 0 && initialValue === '') {
			return '';
		}

		// Round the value, according to the range input's step
		const step = parseFloat(rangeInput.step);
		if (step > 0) {
			const decimalPlaces = step.toString().split('.')[1]?.length || 0;
			value = parseFloat(value).toFixed(decimalPlaces);

			// If the value is a whole number, remove the decimal part
			if (value.includes('.0')) {
				value = value.split('.')[0];
			}
		}

		return value;
	}


	/**
	 * Init the save dialog
	 */
	initSaveDialog(saveDialog) {
		const saveSettingsButton = document.querySelector('.Button-SaveSettings');
		this.eventHandler.add('click', () => {
			saveDialog.showModal();
		}, saveSettingsButton);

		const closeButton = saveDialog.querySelector('#SaveDialog-Button-No');
		this.eventHandler.add('click', event => {
			event.preventDefault();
			saveDialog.close();
		}, closeButton);
	}


	/**
	 * Update a range input
	 * @param {HTMLSelectElement} unitSelect
	 * @param {HTMLInputElement} rangeInput
	 */
	updateRangeInput(unitSelect, rangeInput) {
		const selectedUnit = unitSelect.value;
		const units = JSON.parse(unitSelect.dataset.units);

		// Update the range input's attributes
		rangeInput.min = units[selectedUnit].range.min;
		rangeInput.max = units[selectedUnit].range.max;
		rangeInput.step = units[selectedUnit].step || 1;
	}
}


/**
 * Expose the class globally
 * @global
 * @type {DesignsController}
 */
window.DesignsController = DesignsController;
