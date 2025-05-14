// noinspection JSUnusedGlobalSymbols

export default class DesignsController {
	/**
	 * Event handler
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
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
					if (option) {
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
		// Check if the value is in the range of the range input
		const min = parseFloat(rangeInput.min);
		const max = parseFloat(rangeInput.max);

		value = Math.max(min, Math.min(max, value));

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
	 * @param unitSelect
	 * @param rangeInput
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
