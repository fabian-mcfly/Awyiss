// noinspection JSUnusedGlobalSymbols

/*!
* Copyright (c) 2021 Momo Bassit.
* Copyright (c) 2024 Awyiss.
*
* Licensed under the MIT License (MIT)
* https://github.com/mdbassit/Coloris
*/

export default class Coloris {
	/**
	 * @type {HTMLDivElement|null}
	 */
	static picker = null;

	/**
	 * @type {CanvasRenderingContext2D}
	 */
	static ctx = document.createElement('canvas').getContext('2d');

	/**
	 * @type {HTMLDivElement|null}
	 */
	static colorArea = null;
	/**
	 * @type {HTMLDivElement|null}
	 * @type {{width: number, height: number, x: number, y: number}}
	 */
	static colorAreaDims = {};

	/**
	 * @type {HTMLDivElement|null}
	 */
	static colorMarker = null;

	/**
	 * @type {HTMLDivElement|null}
	 */
	static colorPreview = null;

	/**
	 * @type {HTMLInputElement|null}
	 */
	static colorValue = null;

	/**
	 * @type {HTMLButtonElement|null}
	 */
	static clearButton = null;

	/**
	 * @type {HTMLButtonElement|null}
	 */
	static closeButton = null;

	/**
	 * @type {HTMLInputElement|null}
	 */
	static hueSlider = null;

	/**
	 * @type {HTMLDivElement|null}
	 */
	static hueMarker = null;

	/**
	 * @type {HTMLInputElement|null}
	 */
	static alphaSlider = null;

	/**
	 * @type {HTMLDivElement|null}
	 */
	static alphaMarker = null;

	/**
	 * @type {HTMLInputElement|null}
	 */
	static currentEl = null;


	/**
	 * @type {{r: number, g: number, b: number, h: number, s: number, v: number, a: number}}
	 */
	currentColor = {r: 0, g: 0, b: 0, h: 0, s: 0, v: 0, a: 1};

	/**
	 * @type {HTMLElement|null}
	 */
	container = null;

	/**
	 * @type {string|null}
	 */
	currentFormat = null;

	/**
	 * @type {boolean|null}
	 */
	keyboardNav = null;

	/**
	 * @type {{width: number, height: number, x: number, y: number}}
	 */
	colorAreaDims = {};

	/**
	 * @type {Object.<string, Object>}
	 */
	instances = {};

	/**
	 * @type {string}
	 */
	currentInstanceId = '';

	/**
	 * @type {Object}
	 */
	defaultInstance = {};

	/**
	 * @type {boolean}
	 */
	hasInstance = false;

	/**
	 * @type {Function}
	 */
	boundListeners = this.moveMarker.bind(this);

	settings = {
		element: '[data-coloris]',
		parent: 'body',
		theme: 'default',
		themeMode: 'light',
		rtl: false,
		wrap: true,
		margin: 2,
		format: 'hex',
		formatToggle: false,
		swatches: [],
		swatchesOnly: false,
		alpha: true,
		forceAlpha: false,
		focusInput: true,
		selectInput: false,
		inline: false,
		defaultColor: '#000000',
		clearButton: false,
		clearLabel: 'Clear',
		closeButton: false,
		closeLabel: 'Close',
		onChange: function onChange() {
			return undefined;
		},
		a11y: {
			open: 'Open color picker',
			close: 'Close color picker',
			clear: 'Clear the selected color',
			marker: 'Saturation: {s}. Brightness: {v}.',
			hueSlider: 'Hue slider',
			alphaSlider: 'Opacity slider',
			input: 'Color value field',
			format: 'Color format',
			swatch: 'Color swatch',
			instruction: 'Saturation and brightness selector. Use up, down, left and right arrow keys to select.'
		}
	};

	/**
	 * Create a new instance of Coloris.
	 * @param {object} options Configuration options.
	 */
	constructor(options = {}) {
		this.configure(options);

		this.init();
	}

	/**
	 * Init the color picker.
	 */
	init() {
		if (!Coloris.picker) {
			// Bind the picker to the default selector
			this.bindFields(this.settings.element);

			if (this.settings.wrap) {
				this.wrapFields(this.settings.element);
			}

			if (this.settings.inline) {
				this.updatePickerPosition();
			}
		}

		this.#buildPicker();
	}

	/**
	 * Configure the color picker.
	 * @param {object} options Configuration options.
	 */
	configure(options) {
		if (typeof options !== 'object') {
			return;
		}

		for (const key in options) {
			// noinspection FallThroughInSwitchStatementJS
			switch (key) {
				case 'element':
					this.settings.element = options.element;
					this.settings.wrap = options.wrap || this.settings.wrap;

					if (Coloris.picker) {
						this.bindFields(options.element);

						if (options.wrap !== false) {
							this.wrapFields(options.element);
						}
					}

					break;
				case 'parent':
					this.container = options.parent instanceof HTMLElement ? options.parent : document.querySelector(options.parent);
					if (this.container) {
						if (Coloris.picker) {
							this.container.appendChild(Coloris.picker);
						}

						this.settings.parent = options.parent;

						// document.body is special
						if (this.container === document.body) {
							this.container = undefined;
						}
					}
					break;
				case 'themeMode':
					this.settings.themeMode = options.themeMode;
					if (options.themeMode === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
						this.settings.themeMode = 'dark';
					}
					// The lack of a break statement is intentional
				case 'theme':
					if (options.theme) {
						this.settings.theme = options.theme;
					}

					// Set the theme and color scheme
					if (Coloris.picker) {
						Coloris.picker.className = "clr-picker clr-" + this.settings.theme + " clr-" + this.settings.themeMode;
					}

					// Update the color picker's position if inline mode is in use
					if (this.settings.inline) {
						this.updatePickerPosition();
					}
					break;
				case 'rtl':
					this.settings.rtl = !!options.rtl;
					Array.from(document.getElementsByClassName('clr-field')).forEach(field => {
						return field.classList.toggle('clr-rtl', this.settings.rtl);
					});
					break;
				case 'margin':
					options.margin *= 1;
					this.settings.margin = !isNaN(options.margin) ? options.margin : this.settings.margin;
					break;
				case 'wrap':
					if (options.element && options.wrap) {
						this.wrapFields(options.element);
					}
					break;
				case 'formatToggle':
					this.settings.formatToggle = !!options.formatToggle;
					this.getEl('clr-format').style.display = this.settings.formatToggle ? 'block' : 'none';
					if (this.settings.formatToggle) {
						this.settings.format = 'auto';
					}
					break;
				case 'swatches':
					this.settings.swatches = options.swatches.slice();
					if (Coloris.picker) {
						this.createSwatches(options);
					}
					break;
				case 'swatchesOnly':
					this.settings.swatchesOnly = !!options.swatchesOnly;
					if (Coloris.picker) {
						Coloris.picker.setAttribute('data-minimal', this.settings.swatchesOnly);
					}
					break;
				case 'alpha':
					this.settings.alpha = !!options.alpha;
					if (Coloris.picker) {
						Coloris.picker.setAttribute('data-alpha', this.settings.alpha);
					}
					break;
				case 'inline':
					this.settings.inline = !!options.inline;
					if (Coloris.picker) {
						Coloris.picker.setAttribute('data-inline', this.settings.inline);

						if (this.settings.inline) {
							const defaultColor = options.defaultColor || this.settings.defaultColor;

							this.currentFormat = this.getColorFormatFromStr(defaultColor);
							this.updatePickerPosition();
							this.setColorFromStr(defaultColor);
						}
					}
					break;
				case 'clearButton':
					// Backward compatibility
					if (typeof options.clearButton === 'object') {
						if (options.clearButton.label) {
							this.settings.clearLabel = options.clearButton.label;
							if (Coloris.clearButton) {
								Coloris.clearButton.innerHTML = this.settings.clearLabel;
							}
						}

						options.clearButton = options.clearButton.show;
					}

					this.settings.clearButton = !!options.clearButton;
					if (Coloris.clearButton) {
						Coloris.clearButton.style.display = this.settings.clearButton ? 'block' : 'none';
					}
					break;
				case 'clearLabel':
					this.settings.clearLabel = options.clearLabel;
					if (Coloris.clearButton) {
						Coloris.clearButton.innerHTML = this.settings.clearLabel;
					}
					break;
				case 'closeButton':
					this.settings.closeButton = !!options.closeButton;

					if (this.settings.closeButton) {
						if (Coloris.picker) {
							Coloris.picker.insertBefore(Coloris.closeButton, Coloris.colorPreview);
						}
					}
					else {
						if (Coloris.colorPreview) {
							Coloris.colorPreview.appendChild(Coloris.closeButton);
						}
					}

					break;
				case 'closeLabel':
					this.settings.closeLabel = options.closeLabel;
					if (Coloris.closeButton) {
						Coloris.closeButton.innerHTML = this.settings.closeLabel;
					}
					break;
				case 'a11y':
					const labels = options.a11y;
					let update = false;

					if (typeof labels === 'object') {
						for (const label in labels) {
							if (labels[label] && this.settings.a11y[label]) {
								this.settings.a11y[label] = labels[label];
								update = true;
							}
						}
					}

					if (Coloris.picker && update) {
						const openLabel = this.getEl('clr-open-label');
						const swatchLabel = this.getEl('clr-swatch-label');

						openLabel.innerHTML = this.settings.a11y.open;
						swatchLabel.innerHTML = this.settings.a11y.swatch;
						Coloris.closeButton.setAttribute('aria-label', this.settings.a11y.close);
						Coloris.clearButton.setAttribute('aria-label', this.settings.a11y.clear);
						Coloris.hueSlider.setAttribute('aria-label', this.settings.a11y.hueSlider);
						Coloris.alphaSlider.setAttribute('aria-label', this.settings.a11y.alphaSlider);
						Coloris.colorValue.setAttribute('aria-label', this.settings.a11y.input);
						Coloris.colorArea.setAttribute('aria-label', this.settings.a11y.instruction);
					}
					break;
				default:
					this.settings[key] = options[key];
			}
		}
	}

	createSwatches(options) {
		if (!Array.isArray(options.swatches)) {
			return;
		}

		const swatchesContainer = this.getEl('clr-swatches');
		const swatches = document.createElement('div');

		// Clear current swatches
		swatchesContainer.textContent = '';

		options.swatches.forEach(
			/**
			 * Build new swatches
			 * @param {string} swatch
			 * @param {number} i
			 */
			(swatch, i) => {
				// phpcs:disable
				const button = document.createElement('button');

				button.setAttribute('type', "button");
				button.setAttribute('id', "clr-swatch-" + i);
				button.setAttribute('aria-labelledby', "clr-swatch-label clr-swatch-" + i);
				button.style.color = swatch;
				button.textContent = swatch;

				swatches.appendChild(button);
				// phpcs:enable
			}
		);

		// Append new swatches if any
		if (options.swatches.length) {
			swatchesContainer.appendChild(swatches);
		}

		this.settings.swatches = options.swatches.slice();
	}

	/**
	 * Add or update a virtual instance.
	 * @param {String} selector The CSS selector of the elements to which the instance is attached.
	 * @param {Object} options Per-instance options to apply.
	 */
	setVirtualInstance(selector, options) {
		if (typeof selector === 'string' && typeof options === 'object') {
			this.instances[selector] = options;
			this.hasInstance = true;
		}
	}

	/**
	 * Attach a virtual instance to an element if it matches a selector.
	 * @param {Object} element Target element that will receive a virtual instance if applicable.
	 */
	attachVirtualInstance(element) {
		if (!this.hasInstance) {
			return;
		}

		// These options can only be set globally, not per instance
		const unsupportedOptions = ['el', 'wrap', 'rtl', 'inline', 'defaultColor', 'a11y'];
		const _loop = selector => {
			const options = this.instances[selector];

			// If the element matches an instance's CSS selector
			if (element.matches(selector)) {
				this.currentInstanceId = selector;
				this.defaultInstance = {};

				// Delete unsupported options
				unsupportedOptions.forEach(function (option) {
					return delete options[option];
				});

				// Back up the default options, so we can restore them later
				for (const option in options) {
					this.defaultInstance[option] = Array.isArray(this.settings[option]) ? this.settings[option].slice() : this.settings[option];
				}

				// Set the instance's options
				this.configure(options);

				return 'break';
			}
		};

		for (const selector in this.instances) {
			const _ret = _loop(selector);
			if (_ret === 'break') {
				break;
			}
		}
	}

	/**
	 * Revert any per-instance options that were previously applied.
	 */
	resetVirtualInstance() {
		if (Object.keys(this.defaultInstance).length > 0) {
			this.configure(this.defaultInstance);
			this.currentInstanceId = '';
			this.defaultInstance = {};
		}
	}

	/**
	 * Bind the color picker to input fields that match the selector.
	 * @param {(string|HTMLElement|HTMLElement[])} selector A CSS selector string, a DOM element or a list of DOM elements.
	 */
	bindFields(selector) {
		if (selector instanceof HTMLElement) {
			selector = [selector];
		}

		if (Array.isArray(selector)) {
			selector.forEach(field => {
				this.addListener(field, 'click', this.openPicker.bind(this));
				this.addListener(field, 'input', this.updateColorPreview.bind(this));
			});
		}
		else {
			this.addListener(document, 'click', selector, this.openPicker.bind(this));
			this.addListener(document, 'input', selector, this.updateColorPreview.bind(this));
		}
	}

	/**
	 * Open the color picker.
	 * @param {object} event The event that opens the color picker.
	 */
	openPicker(event) {
		// Skip if inline mode is in use
		if (this.settings.inline) {
			return;
		}

		// Apply any per-instance options first
		this.attachVirtualInstance(event.target);

		Coloris.currentEl = event.target;
		Coloris.currentEl.oldColor = Coloris.currentEl.value;
		this.currentFormat = this.getColorFormatFromStr(Coloris.currentEl.oldColor);
		Coloris.picker.classList.add('clr-open');

		this.updatePickerPosition();
		this.setColorFromStr(Coloris.currentEl.oldColor);

		if (this.settings.focusInput || this.settings.selectInput) {
			Coloris.colorValue.focus({preventScroll: true});
			Coloris.colorValue.setSelectionRange(Coloris.currentEl.selectionStart, Coloris.currentEl.selectionEnd);
		}

		if (this.settings.selectInput) {
			Coloris.colorValue.select();
		}

		// Always focus the first element when using keyboard navigation
		if (this.keyboardNav || this.settings.swatchesOnly) {
			this.getFocusableElements().shift().focus();
		}

		// Trigger an "open" event
		Coloris.currentEl.dispatchEvent(new Event('coloris:open', {bubbles: true}));
	}

	/**
	 * Update the color picker's position and the color gradient's offset
	 */
	updatePickerPosition() {
		const parent = this.container;
		const scrollY = window.scrollY;
		const pickerWidth = Coloris.picker.offsetWidth;
		const pickerHeight = Coloris.picker.offsetHeight;
		const reposition = {left: false, top: false};
		let parentStyle, parentMarginTop, parentBorderTop;
		let offset = {x: 0, y: 0};

		if (parent) {
			parentStyle = window.getComputedStyle(parent);
			parentMarginTop = parseFloat(parentStyle.marginTop);
			parentBorderTop = parseFloat(parentStyle.borderTopWidth);

			offset = parent.getBoundingClientRect();
			offset.y += parentBorderTop + scrollY;
		}

		if (!this.settings.inline) {
			const coords = Coloris.currentEl.getBoundingClientRect();
			let left = coords.x;
			let top = scrollY + coords.y + coords.height + this.settings.margin;

			// If the color picker is inside a custom container
			// set the position relative to it
			if (parent) {
				left -= offset.x;
				top -= offset.y;

				if (left + pickerWidth > parent.clientWidth) {
					left += coords.width - pickerWidth;
					reposition.left = true;
				}

				if (top + pickerHeight > parent.clientHeight - parentMarginTop) {
					if (pickerHeight + this.settings.margin <= coords.top - (offset.y - scrollY)) {
						top -= coords.height + pickerHeight + this.settings.margin * 2;
						reposition.top = true;
					}
				}

				top += parent.scrollTop;
			}
			// Otherwise set the position relative to the whole document
			else {
				if (left + pickerWidth > document.documentElement.clientWidth) {
					left += coords.width - pickerWidth;
					reposition.left = true;
				}

				if (top + pickerHeight - scrollY > document.documentElement.clientHeight) {
					if (pickerHeight + this.settings.margin <= coords.top) {
						top = scrollY + coords.y - pickerHeight - this.settings.margin;
						reposition.top = true;
					}
				}
			}

			Coloris.picker.classList.toggle('clr-left', reposition.left);
			Coloris.picker.classList.toggle('clr-top', reposition.top);
			Coloris.picker.style.left = left + "px";
			Coloris.picker.style.top = top + "px";
			offset.x += Coloris.picker.offsetLeft;
			offset.y += Coloris.picker.offsetTop;
		}

		Coloris.colorAreaDims = {
			width: Coloris.colorArea.offsetWidth,
			height: Coloris.colorArea.offsetHeight,
			x: Coloris.colorArea.offsetLeft + offset.x,
			y: Coloris.colorArea.offsetTop + offset.y
		};

	}

	/**
	 * Wrap the linked input fields in a div that adds a color preview.
	 * @param {(string|HTMLElement|HTMLElement[])} selector A CSS selector string, a DOM element or a list of DOM elements.
	 */
	wrapFields(selector) {
		if (selector instanceof HTMLElement) {
			this.wrapColorField(selector);
		}
		else if (Array.isArray(selector)) {
			selector.forEach(this.wrapColorField);
		}
		else {
			document.querySelectorAll(selector).forEach(this.wrapColorField);
		}
	}

	/**
	 * Wrap an input field in a div that adds a color preview.
	 * @param {HTMLInputElement} field The input field.
	 */
	wrapColorField(field) {
		const parentNode = field.parentNode;

		if (!parentNode.classList.contains('clr-field')) {
			const wrapper = document.createElement('div');
			let classes = 'clr-field';

			if (this.settings.rtl || field.classList.contains('clr-rtl')) {
				classes += ' clr-rtl';
			}

			wrapper.innerHTML = '<button type="button" aria-labelledby="clr-open-label"></button>';
			parentNode.insertBefore(wrapper, field);
			wrapper.className = classes;
			wrapper.style.color = field.value;
			wrapper.appendChild(field);
		}
	}

	/**
	 * Update the color preview of an input field
	 * @param {object} event The "input" event that triggers the color change.
	 */
	updateColorPreview(event) {
		const parent = event.target.parentNode;

		// Only update the preview if the field has been previously wrapped
		if (parent.classList.contains('clr-field')) {
			parent.style.color = event.target.value;
		}
	}

	/**
	 * Close the color picker.
	 * @param {boolean} [revert] If true, revert the color to the original value.
	 */
	closePicker(revert) {
		if (!Coloris.currentEl || this.settings.inline) {
			return;
		}

		const prevEl = Coloris.currentEl;

		// Revert the color to the original value if needed
		if (revert) {
			// This will prevent the "change" event on the colorValue input to execute its handler
			Coloris.currentEl = undefined;

			if (prevEl.oldColor !== prevEl.value) {
				prevEl.value = prevEl.oldColor;

				// Trigger an "input" event to force update the thumbnail next to the input field
				prevEl.dispatchEvent(new Event('input', {bubbles: true}));
			}
		}

		// Trigger a "change" event if needed
		setTimeout(() => {
			// Add this to the end of the event loop
			if (prevEl.oldColor !== prevEl.value) {
				prevEl.dispatchEvent(new Event('change', {bubbles: true}));
			}
		});

		// Hide the picker dialog
		Coloris.picker.classList.remove('clr-open');

		// Reset any previously set per-instance options
		if (this.hasInstance) {
			this.resetVirtualInstance();
		}

		// Trigger a "close" event
		prevEl.dispatchEvent(new Event('coloris:close', {bubbles: true}));

		if (this.settings.focusInput) {
			prevEl.focus({preventScroll: true});
		}

		// This essentially marks the picker as closed
		Coloris.currentEl = undefined;
	}

	/**
	 * Set the active color from a string.
	 * @param {string} str String representing a color.
	 */
	setColorFromStr(str) {
		const rgba = this.strToRGBA(str);
		const hsva = this.RGBAtoHSVA(rgba);

		this.updateMarkerA11yLabel(hsva.s, hsva.v);
		this.updateColor(rgba, hsva);

		// Update the UI
		Coloris.hueSlider.value = '' + hsva.h;
		Coloris.picker.style.color = "hsl(" + hsva.h + ", 100%, 50%)";
		Coloris.hueMarker.style.left = hsva.h / 360 * 100 + "%";

		Coloris.colorMarker.style.left = Coloris.colorAreaDims.width * hsva.s / 100 + "px";
		Coloris.colorMarker.style.top = Coloris.colorAreaDims.height - Coloris.colorAreaDims.height * hsva.v / 100 + "px";

		Coloris.alphaSlider.value = '' + (hsva.a * 100);
		Coloris.alphaMarker.style.left = hsva.a * 100 + "%";
	}

	/**
	 * Guess the color format from a string.
	 * @param {string} str String representing a color.
	 * @return {string} The color format.
	 */
	getColorFormatFromStr(str) {
		const format = str.substring(0, 3).toLowerCase();

		if (format === 'rgb' || format === 'hsl') {
			return format;
		}

		return 'hex';
	}

	/**
	 * Copy the active color to the linked input field.
	 * @param {string} [color] Color value to override the active color.
	 */
	pickColor(color) {
		color = color !== undefined ? color : Coloris.colorValue.value;

		if (Coloris.currentEl) {
			Coloris.currentEl.value = color;
			Coloris.currentEl.dispatchEvent(new Event('input', {bubbles: true}));
		}

		if (this.settings.onChange) {
			this.settings.onChange.call(window, color, Coloris.currentEl);
		}

		document.dispatchEvent(new CustomEvent('coloris:pick', {detail: {color: color, currentEl: Coloris.currentEl}}));
	}

	/**
	 * Set the active color based on a specific point in the color gradient.
	 * @param {number} x Left position.
	 * @param {number} y Top position.
	 */
	setColorAtPosition(x, y) {
		const hsva = {
			h: Coloris.hueSlider.value * 1,
			s: x / Coloris.colorAreaDims.width * 100,
			v: 100 - y / Coloris.colorAreaDims.height * 100,
			a: Coloris.alphaSlider.value / 100
		};

		const rgba = this.HSVAtoRGBA(hsva);

		this.updateMarkerA11yLabel(hsva.s, hsva.v);
		this.updateColor(rgba, hsva);
		this.pickColor();
	}

	/**
	 * Update the color marker's accessibility label.
	 * @param {number} saturation
	 * @param {number} value
	 */
	updateMarkerA11yLabel(saturation, value) {
		let label = this.settings.a11y.marker;

		saturation = saturation.toFixed(1) * 1;
		value = value.toFixed(1) * 1;
		label = label.replace('{s}', '' + saturation);
		label = label.replace('{v}', '' + value);
		Coloris.colorMarker.setAttribute('aria-label', label);
	}

	//
	/**
	 * Get the pageX and pageY positions of the pointer.
	 * @param {object} event The MouseEvent or TouchEvent object.
	 * @return {object} The pageX and pageY positions.
	 */
	getPointerPosition(event) {
		return {
			pageX: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
			pageY: event.changedTouches ? event.changedTouches[0].pageY : event.pageY
		};

	}

	/**
	 * Move the color marker when dragged.
	 * @param {object} event The MouseEvent object.
	 */
	moveMarker(event) {
		const pointer = this.getPointerPosition(event);
		const x = pointer.pageX - Coloris.colorAreaDims.x;
		let y = pointer.pageY - Coloris.colorAreaDims.y;

		if (this.container) {
			y += this.container.scrollTop;
		}

		this.setMarkerPosition(x, y);

		// Prevent scrolling while dragging the marker
		event.preventDefault();
		event.stopPropagation();
	}

	/**
	 * Move the color marker when the arrow keys are pressed.
	 * @param {number} offsetX The horizontal amount to move.
	 * @param {number} offsetY The vertical amount to move.
	 */
	moveMarkerOnKeydown(offsetX, offsetY) {
		const x = Coloris.colorMarker.style.left.replace('px', '') * 1 + offsetX;
		const y = Coloris.colorMarker.style.top.replace('px', '') * 1 + offsetY;

		this.setMarkerPosition(x, y);
	}

	/**
	 * Set the color marker's position.
	 * @param {number} x Left position.
	 * @param {number} y Top position.
	 */
	setMarkerPosition(x, y) {
		// Make sure the marker doesn't go out of bounds
		x = x < 0 ? 0 : x > Coloris.colorAreaDims.width ? Coloris.colorAreaDims.width : x;
		y = y < 0 ? 0 : y > Coloris.colorAreaDims.height ? Coloris.colorAreaDims.height : y;

		// Set the position
		Coloris.colorMarker.style.left = x + "px";
		Coloris.colorMarker.style.top = y + "px";

		// Update the color
		this.setColorAtPosition(x, y);

		// Make sure the marker is focused
		Coloris.colorMarker.focus();
	}

	/**
	 * Update the color picker's input field and preview thumb.
	 * @param {Object} rgba Red, green, blue and alpha values.
	 * @param {Object} hsva Hue, saturation, value and alpha values.
	 */
	updateColor(rgba= {}, hsva= {}) {
		let format = this.settings.format;

		for (const key in rgba) {
			this.currentColor[key] = rgba[key];
		}

		for (const _key in hsva) {
			this.currentColor[_key] = hsva[_key];
		}

		const hex = this.RGBAToHex(this.currentColor);
		const opaqueHex = hex.substring(0, 7);

		Coloris.colorMarker.style.color = opaqueHex;
		// noinspection JSUnresolvedReference
		Coloris.alphaMarker.parentNode.style.color = opaqueHex;
		Coloris.alphaMarker.style.color = hex;
		Coloris.colorPreview.style.color = hex;

		// Force repaint the color and alpha gradients as a workaround for a Google Chrome bug
		Coloris.colorArea.style.display = 'none';
		Coloris.colorArea.offsetHeight;
		Coloris.colorArea.style.display = '';
		Coloris.alphaMarker.nextElementSibling.style.display = 'none';
		Coloris.alphaMarker.nextElementSibling.offsetHeight;
		Coloris.alphaMarker.nextElementSibling.style.display = '';

		if (format === 'mixed') {
			format = this.currentColor.a === 1 ? 'hex' : 'rgb';
		}
		else if (format === 'auto') {
			format = this.currentFormat;
		}

		switch (format) {
			case 'hex':
				Coloris.colorValue.value = hex;
				break;
			case 'rgb':
				Coloris.colorValue.value = this.RGBAToStr(this.currentColor);
				break;
			case 'hsl':
				Coloris.colorValue.value = this.HSLAToStr(this.HSVAtoHSLA(this.currentColor));
				break;
		}


		// Select the current format in the format switcher
		document.querySelector(".clr-format [value=\"" + format + "\"]").checked = true;
	}

	/**
	 * Set the hue when its slider is moved.
	 */
	setHue() {
		const hue = Coloris.hueSlider.value * 1;
		const x = Coloris.colorMarker.style.left.replace('px', '') * 1;
		const y = Coloris.colorMarker.style.top.replace('px', '') * 1;

		Coloris.picker.style.color = "hsl(" + hue + ", 100%, 50%)";
		Coloris.hueMarker.style.left = hue / 360 * 100 + "%";

		this.setColorAtPosition(x, y);
	}

	/**
	 * Set the alpha when its slider is moved.
	 */
	setAlpha() {
		const alpha = Coloris.alphaSlider.value / 100;

		Coloris.alphaMarker.style.left = alpha * 100 + "%";
		this.updateColor({a: alpha});
		this.pickColor();
	}

	/**
	 * Convert HSVA to RGBA.
	 * @param {object} hsva Hue, saturation, value and alpha values.
	 * @return {object} Red, green, blue and alpha values.
	 */
	HSVAtoRGBA(hsva) {
		const saturation = hsva.s / 100;
		const value = hsva.v / 100;
		let chroma = saturation * value;
		const hueBy60 = hsva.h / 60;
		let x = chroma * (1 - Math.abs(hueBy60 % 2 - 1));
		const m = value - chroma;

		chroma = chroma + m;
		x = x + m;

		const index = Math.floor(hueBy60) % 6;
		const red = [chroma, x, m, m, x, chroma][index];
		const green = [x, chroma, chroma, x, m, m][index];
		const blue = [m, m, x, chroma, chroma, x][index];

		return {
			r: Math.round(red * 255),
			g: Math.round(green * 255),
			b: Math.round(blue * 255),
			a: hsva.a
		};

	}

	/**
	 * Convert HSVA to HSLA.
	 * @param {object} hsva Hue, saturation, value and alpha values.
	 * @return {object} Hue, saturation, lightness and alpha values.
	 */
	HSVAtoHSLA(hsva) {
		const value = hsva.v / 100;
		const lightness = value * (1 - hsva.s / 100 / 2);
		let saturation;

		if (lightness > 0 && lightness < 1) {
			saturation = Math.round((value - lightness) / Math.min(lightness, 1 - lightness) * 100);
		}

		return {
			h: hsva.h,
			s: saturation || 0,
			l: Math.round(lightness * 100),
			a: hsva.a
		};

	}

	/**
	 * Convert RGBA to HSVA.
	 * @param {object} rgba Red, green, blue and alpha values.
	 * @return {object} Hue, saturation, value and alpha values.
	 */
	RGBAtoHSVA(rgba) {
		const red = rgba.r / 255;
		const green = rgba.g / 255;
		const blue = rgba.b / 255;
		const xmax = Math.max(red, green, blue);
		const xmin = Math.min(red, green, blue);
		const chroma = xmax - xmin;
		const value = xmax;
		let hue = 0;
		let saturation = 0;

		if (chroma) {
			if (xmax === red) {
				hue = (green - blue) / chroma;
			}
			if (xmax === green) {
				hue = 2 + (blue - red) / chroma;
			}
			if (xmax === blue) {
				hue = 4 + (red - green) / chroma;
			}
			if (xmax) {
				saturation = chroma / xmax;
			}
		}

		hue = Math.floor(hue * 60);

		return {
			h: hue < 0 ? hue + 360 : hue,
			s: Math.round(saturation * 100),
			v: Math.round(value * 100),
			a: rgba.a
		};

	}

	/**
	 * Parse a string to RGBA.
	 * @param {string} str String representing a color.
	 * @return {object} Red, green, blue and alpha values.
	 */
	strToRGBA(str) {
		// noinspection RegExpSimplifiable
		const regex = /^((rgba)|rgb)[\D]+([\d.]+)[\D]+([\d.]+)[\D]+([\d.]+)[\D]*?([\d.]+|$)/i;
		let match, rgba;

		// Default to black for invalid color strings
		Coloris.ctx.fillStyle = '#000000';

		// Use canvas to convert the string to a valid color string
		Coloris.ctx.fillStyle = str;
		match = regex.exec(Coloris.ctx.fillStyle);

		if (match) {
			rgba = {
				r: match[3] * 1,
				g: match[4] * 1,
				b: match[5] * 1,
				a: match[6] * 1
			};
		}
		else {
			match = Coloris.ctx.fillStyle.replace('#', '').match(/.{2}/g).map(function (h) {
				return parseInt(h, 16);
			});
			rgba = {
				r: match[0],
				g: match[1],
				b: match[2],
				a: 1
			};
		}

		return rgba;
	}

	/**
	 * Convert RGBA to Hex.
	 * @param {object} rgba Red, green, blue and alpha values.
	 * @return {string} Hex color string.
	 */
	RGBAToHex(rgba) {
		let R = rgba.r.toString(16);
		let G = rgba.g.toString(16);
		let B = rgba.b.toString(16);
		let A = '';

		if (rgba.r < 16) {
			R = '0' + R;
		}

		if (rgba.g < 16) {
			G = '0' + G;
		}

		if (rgba.b < 16) {
			B = '0' + B;
		}

		if (this.settings.alpha && (rgba.a < 1 || this.settings.forceAlpha)) {
			const alpha = rgba.a * 255 | 0;
			A = alpha.toString(16);

			if (alpha < 16) {
				A = '0' + A;
			}
		}

		return '#' + R + G + B + A;
	}

	/**
	 * Convert RGBA values to a CSS rgb/rgba string.
	 * @param {object} rgba Red, green, blue and alpha values.
	 * @return {string} CSS color string.
	 */
	RGBAToStr(rgba) {
		if (!this.settings.alpha || rgba.a === 1 && !this.settings.forceAlpha) {
			return "rgb(" + rgba.r + ", " + rgba.g + ", " + rgba.b + ")";
		}
		else {
			return "rgba(" + rgba.r + ", " + rgba.g + ", " + rgba.b + ", " + rgba.a + ")";
		}
	}

	/**
	 * Convert HSLA values to a CSS hsl/hsla string.
	 * @param {object} hsla Hue, saturation, lightness and alpha values.
	 * @return {string} CSS color string.
	 */
	HSLAToStr(hsla) {
		if (!this.settings.alpha || hsla.a === 1 && !this.settings.forceAlpha) {
			return "hsl(" + hsla.h + ", " + hsla.s + "%, " + hsla.l + "%)";
		}
		else {
			return "hsla(" + hsla.h + ", " + hsla.s + "%, " + hsla.l + "%, " + hsla.a + ")";
		}
	}

	/**
	 * Return a list of focusable elements within the color picker.
	 * @return {array} The list of focusable DOM elemnts.
	 */
	getFocusableElements() {
		const controls = Array.from(Coloris.picker.querySelectorAll('input, button'));

		return controls.filter(function (node) {
			return !!node.offsetWidth;
		});
	}

	/**
	 * Shortcut for getElementById to optimize the minified JS.
	 * @param {string} id The element id.
	 * @return {object} The DOM element with the provided id.
	 */
	getEl(id) {
		return document.getElementById(id);
	}

	/**
	 * Shortcut for addEventListener to optimize the minified JS.
	 * @param {object} context The context to which the listener is attached.
	 * @param {string} type Event type.
	 * @param {(string|function)} selector Event target if delegation is used, event handler if not.
	 * @param {function} [fn] Event handler if delegation is used.
	 */
	addListener(context, type, selector, fn) {
		// Delegate event to the target of the selector
		if (typeof selector === 'string') {
			context.addEventListener(type, function (event) {
				if (event.target.matches(selector)) {
					fn.call(event.target, event);
				}
			});
		}
		// If the selector is not a string then it's a function
		// in which case we need a regular event listener
		else {
			fn = selector;
			context.addEventListener(type, fn);
		}
	}

	#buildPicker() {
		const picker = this.getEl('clr-picker');

		if (picker) {
			Coloris.picker = picker;
			return;
		}

		Coloris.picker = document.createElement('div');
		Coloris.picker.setAttribute('id', 'clr-picker');

		Coloris.picker.className = `clr-picker clr-${this.settings.theme} clr-${this.settings.themeMode}`;

		Coloris.picker.setAttribute('data-minimal', this.settings.swatchesOnly);
		Coloris.picker.setAttribute('data-alpha', this.settings.alpha);
		Coloris.picker.setAttribute('data-inline', this.settings.inline);

		// phpcs:disable
		Coloris.picker.innerHTML = `
			<input id="clr-color-value" name="clr-color-value" class="clr-color" type="text" value="" spellcheck="false" aria-label="${this.settings.a11y.input}">
			<div id="clr-color-area" class="clr-gradient" role="application" aria-label="${this.settings.a11y.instruction}">
				<div id="clr-color-marker" class="clr-marker" tabindex="0"></div>
			</div>
			<div class="clr-hue">
				<input id="clr-hue-slider" name="clr-hue-slider" type="range" min="0" max="360" step="1" aria-label="${this.settings.a11y.hueSlider}">
				<div id="clr-hue-marker"></div>
			</div>
			<div class="clr-alpha">
				<input id="clr-alpha-slider" name="clr-alpha-slider" type="range" min="0" max="100" step="1" aria-label="${this.settings.a11y.alphaSlider}">
				<div id="clr-alpha-marker"></div>
				<span></span>
			</div>
			<div id="clr-format" class="clr-format">
				<fieldset class="clr-segmented">
				<legend>${this.settings.a11y.format}</legend>
				<input id="clr-f1" type="radio" name="clr-format" value="hex">
				<label for="clr-f1">Hex</label>
				<input id="clr-f2" type="radio" name="clr-format" value="rgb">
				<label for="clr-f2">RGB</label>
				<input id="clr-f3" type="radio" name="clr-format" value="hsl">
				<label for="clr-f3">HSL</label>
				<span></span>
				</fieldset>
			</div>
			<div id="clr-swatches" class="clr-swatches"></div>
			<button type="button" id="clr-clear" class="clr-clear" aria-label="${this.settings.a11y.clear}">${this.settings.clearLabel}</button>
			<div id="clr-color-preview" class="clr-preview">
				<button type="button" id="clr-close" class="clr-close" aria-label="${this.settings.a11y.close}">${this.settings.closeLabel}</button>
			</div>
			<span id="clr-open-label" hidden>${this.settings.a11y.open}</span>
			<span id="clr-swatch-label" hidden>${this.settings.a11y.swatch}</span>
		`;
		// phpcs:enable

		if (this.container) {
			this.container.appendChild(Coloris.picker);
		}
		else {
			// Append the color picker to the DOM
			document.body.appendChild(Coloris.picker);
		}

		if (this.settings.swatches) {
			this.createSwatches(this.settings);
		}

		this.addListener(Coloris.picker, 'mousedown', event => {
			Coloris.picker.classList.remove('clr-keyboard-nav');
			event.stopPropagation();
		});

		this.addListener(Coloris.picker, 'click', '.clr-swatches button', event => {
			this.setColorFromStr(event.target.textContent);
			this.pickColor();

			if (this.settings.swatchesOnly) {
				this.closePicker();
			}
		});

		// Reference the UI elements
		Coloris.alphaMarker = this.getEl('clr-alpha-marker');
		Coloris.colorPreview = this.getEl('clr-color-preview');
		Coloris.hueMarker = this.getEl('clr-hue-marker');

		this.#initEvents();
	}

	/**
	 * Init the global events and the element-specific events. by calling the appropriate methods.
	 */
	#initEvents() {
		this.addListener(document, 'mouseup', () => {
			document.removeEventListener('mousemove', this.boundListeners);
		});

		this.addListener(document, 'touchend', () => {
			document.removeEventListener('touchmove', this.boundListeners);
		});

		this.addListener(document, 'mousedown', () => {
			this.keyboardNav = false;
			Coloris.picker.classList.remove('clr-keyboard-nav');
			this.closePicker();
		});

		this.addListener(document, 'keydown', event => {
			const key = event.key;
			const target = event.target;
			const shiftKey = event.shiftKey;
			const navKeys = ['Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];

			if (key === 'Escape') {
				this.closePicker(true);
			}
			// Display focus rings when using the keyboard
			else if (navKeys.includes(key)) {
				this.keyboardNav = true;
				Coloris.picker.classList.add('clr-keyboard-nav');
			}

			// Trap the focus within the color picker while it's open
			if (key === 'Tab' && target.matches('.clr-picker *')) {
				const focusables = this.getFocusableElements();
				const firstFocusable = focusables.shift();
				const lastFocusable = focusables.pop();

				if (shiftKey && target === firstFocusable) {
					lastFocusable.focus();
					event.preventDefault();
				}
				else if (!shiftKey && target === lastFocusable) {
					firstFocusable.focus();
					event.preventDefault();
				}
			}
		});

		this.addListener(document, 'click', '.clr-field button', event => {
			// Reset any previously set per-instance options
			if (this.hasInstance) {
				this.resetVirtualInstance();
			}

			// Open the color picker
			event.target.nextElementSibling.dispatchEvent(new Event('click', {bubbles: true}));
		});

		this.#initAlphaSliderEvents();
		this.#initClearButtonEvents();
		this.#initCloseButtonEvents();
		this.#initColorAreaEvents();
		this.#initColorMarkerEvents();
		this.#initColorValueEvents();
		this.#initHueSliderEvents();

		this.addListener(this.getEl('clr-format'), 'click', '.clr-format input', event => {
			this.currentFormat = event.target.value;
			this.updateColor();
			this.pickColor();
		});
	}

	/**
	 * Init the events for the alpha slider
	 */
	#initAlphaSliderEvents() {
		Coloris.alphaSlider = this.getEl('clr-alpha-slider');

		this.addListener(Coloris.alphaSlider, 'input', this.setAlpha.bind(this));
	}

	/**
	 * Init the events for the clear button
	 */
	#initClearButtonEvents() {
		Coloris.clearButton = this.getEl('clr-clear');

		Coloris.clearButton.style.display = this.settings.clearButton ? 'block' : 'none';
		Coloris.clearButton.innerHTML = this.settings.clearLabel;

		this.addListener(Coloris.clearButton, 'click', () => {
			this.pickColor('');
			this.closePicker();
		});
	}

	/**
	 * Init the events for the close button
	 */
	#initCloseButtonEvents() {
		Coloris.closeButton = this.getEl('clr-close');

		Coloris.closeButton.innerHTML = this.settings.closeLabel;

		if (this.settings.closeButton) {
			Coloris.picker.insertBefore(Coloris.closeButton, Coloris.colorPreview);
		}
		else {
			Coloris.colorPreview?.appendChild(Coloris.closeButton);
		}

		this.addListener(Coloris.closeButton, 'click', () => {
			this.pickColor();
			this.closePicker();
		});
	}

	/**
	 * Init events for the color area
	 */
	#initColorAreaEvents() {
		Coloris.colorArea = this.getEl('clr-color-area');

		this.addListener(Coloris.colorArea, 'mousedown', () => {
			this.addListener(document, 'mousemove', this.boundListeners);
		});

		this.addListener(Coloris.colorArea, 'contextmenu', event => {
			event.preventDefault();
		});

		this.addListener(Coloris.colorArea, 'touchstart', () => {
			document.addEventListener('touchmove', this.boundListeners, {passive: false});
		});

		this.addListener(Coloris.colorArea, 'click', this.moveMarker.bind(this));
	}

	/**
	 * Init the events for the marker
	 */
	#initColorMarkerEvents() {
		Coloris.colorMarker = this.getEl('clr-color-marker');

		this.addListener(Coloris.colorMarker, 'mousedown', () => {
			this.addListener(document, 'mousemove', this.boundListeners);
		});

		this.addListener(Coloris.colorMarker, 'touchstart', () => {
			document.addEventListener('touchmove', this.boundListeners, {passive: false});
		});

		this.addListener(Coloris.colorMarker, 'keydown', event => {
			const movements = {
				ArrowUp: [0, -1],
				ArrowDown: [0, 1],
				ArrowLeft: [-1, 0],
				ArrowRight: [1, 0]
			};

			if (Object.keys(movements).includes(event.key)) {
				this.moveMarkerOnKeydown.apply(void 0, movements[event.key]);
				event.preventDefault();
			}
		});
	}

	/**
	 * Init the events for the color value input
	 */
	#initColorValueEvents() {
		Coloris.colorValue = this.getEl('clr-color-value');

		this.addListener(Coloris.colorValue, 'change', () => {
			const value = Coloris.colorValue.value;

			if (Coloris.currentEl || this.settings.inline) {
				const color = value === '' ? value : this.setColorFromStr(value);
				this.pickColor(color);
			}
		});
	}

	/**
	 * Init the events for the hue slider
	 */
	#initHueSliderEvents() {
		Coloris.hueSlider = this.getEl('clr-hue-slider');

		this.addListener(Coloris.hueSlider, 'input', this.setHue.bind(this));
	}
}
