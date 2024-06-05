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

		this.eventHandler.add('input', () => {
			input.value = rangeInput.value;
		}, rangeInput);

		this.eventHandler.add('input', () => {
			rangeInput.value = input.value;
		}, input);

		const unitSelect = formInput.querySelector(`select[name="${name}_unit"]`);
		if (unitSelect) {
			this.eventHandler.add('input', () => {
				this.updateRangeInput(unitSelect, rangeInput);
			}, unitSelect);

			// Trigger update
			this.updateRangeInput(unitSelect, rangeInput);
		}
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
