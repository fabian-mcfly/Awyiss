// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import Coloris from 'Coloris/Coloris';

export default class ColorPicker {
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The selector for the color picker input.
	 * @type {string}
	 */
	selector = 'input[type="color"]';

	/**
	 * Initialize the color pickers present on the page and observe for new ones.
	 */
	constructor() {
		const colorPickers = document.querySelectorAll(this.selector);
		colorPickers.forEach((colorPicker) => {
			this.initColorPicker(colorPicker);
		});

		this.observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Initialize the color picker.
	 *
	 * @param {HTMLInputElement} element
	 */
	initColorPicker(element) {
		element.type = 'text';

		const options = {
			defaultColor: '#00000000',
			element: element,
			theme: 'large',
			themeMode: document.documentElement.classList.contains('🌚') ? 'dark' : 'light',
		};

		// Check if the element is inside a dialog
		const dialog = element.closest('dialog');
		if (dialog) {
			options.parent = dialog;
		}

		// noinspection JSUndefinedPropertyAssignment
		element.colorPicker = new Coloris(options);
	}

	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		const addedNodes = mutation.addedNodes;
		addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initColorPicker(node);
			}

			const colorPickers = node.querySelectorAll(this.selector);
			colorPickers.forEach((colorPicker) => {
				this.initColorPicker(colorPicker);
			});
		});
	}
}