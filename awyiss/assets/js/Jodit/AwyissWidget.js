// noinspection DuplicatedCode

class AwyissWidget {
	/**
	 * @type {HTMLDialogElement} overlay - The overlay element.
	 */
	dialog;
	/**
	 * @type {Object} editor - The editor instance.
	 */
	editor;

	/**
	 * @type {HTMLElement} node - The node to be configured.
	 */
	node;

	constructor(editor) {
		this.editor = editor;
		this.dialog = null;
	}

	/**
	 * Create the dialog element.
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'WidgetConfigurationOverlay';

		this.dialog.addEventListener('close', () => {
			while (this.dialog.firstChild) {
				this.dialog.removeChild(this.dialog.firstChild);
			}
		});

		this.dialog.addEventListener('click', event => this.handleDialogClick(event));

		this.dialog.addEventListener('keypress', event => {
			if (event.key === 'Enter') {
				event.preventDefault();
			}
		});

		document.body.appendChild(this.dialog);
	}

	/**
	 * Fetch the widget configuration form.
	 * @param {string} identifier
	 * @param {Object} settings
	 * @param {string} language
	 * @returns {Promise<Element>}
	 */
	async fetchWidgetConfiguration(identifier, settings, language) {
		language = language || languageShortcode;

		const response = await fetch(`${baseUrl}backend/${language}/contents/widget-configuration/`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				widget_identifier: identifier,
				settings: settings,
			}),
		});

		const html = await response.text();

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, "text/html");

		return doc.querySelector('#Content')?.querySelector('.Form');
	}

	/**
	 * Open the overlay to configure the widget.
	 *
	 * @param {Event} event
	 * @param {HTMLElement} node
	 * @returns {Promise<void>}
	 */
	async openOverlay(event, node) {
		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();
		this.editor.selection.select(node);
		this.node = node;

		let settings = {};

		if (node.nodeName.toLowerCase() === 'widget') {
			try {
				settings = JSON.parse(node.textContent ?? '{}');
			} catch (error) {
				console.error('Failed to parse JSON:', error);
				settings = {};
			}
		}

		// If the editor is inside the TranslationDialog, the language of the editor's textarea
		// must be used to fetch the widget configuration
		let language = null;
		if (this.editor.element.closest('#TranslationDialog')) {
			// The language is part of the textarea's name
			language = this.editor.element.name.split('[')[1].split(']')[0];
		}

		const form = await this.fetchWidgetConfiguration(node.dataset.identifier ?? null, settings, language);

		if (!form) {
			return;
		}

		form.classList.remove('Contents');
		this.dialog.appendChild(form);
	}

	/**
	 * Handle the click event on the dialog.
	 *
	 * @param {MouseEvent} event
	 */
	handleDialogClick(event) {
		if (event.target.matches('.Button-Success.Button-Save')) {
			event.preventDefault();

			if (!this.dialog.querySelector('select[name="widget_identifier"]').value) {
				this.dialog.close();
				return;
			}

			if (this.useWidgetSettings()) {
				this.dialog.close();
			}

			return;
		}

		if (event.target.matches('.Button-Close')) {
			event.preventDefault();
			this.dialog.close();
		}
	}

	/**
	 * Use the settings from the dialog to update the widget node.
	 *
	 * @returns {boolean}
	 */
	useWidgetSettings() {
		const identifierSelect = this.dialog.querySelector('select[name="widget_identifier"]');
		const identifier = identifierSelect.value;

		const form = this.dialog.querySelector('form');
		if (!form.checkValidity()) {
			form.reportValidity();
			return false;
		}

		/**
		 * Get the form data for all elements starting with `settings[` and create a JSON object.
		 *
		 * Manually build the object instead of using FormData because:
		 *
		 * - FormData returns the values `as-is`, so checkboxes are not converted to booleans.
		 * A manual lookup for the input type would be needed.
		 *
		 * - FormData does not support nested objects/the array notation. `setting[foo]` would be `settings[foo]` in the object
		 */
		const settings = {};

		const formElements = this.dialog.querySelectorAll('input[name^="settings["], select[name^="settings["], textarea[name^="settings["]');
		formElements.forEach(element => {
			// noinspection RegExpRedundantEscape
			const key = element.name.match(/\[(.*?)\]/)[1];

			// If the element is a checkbox, use the checked property and convert it to a boolean
			if (element.type === 'checkbox') {
				settings[key] = element.checked;
				return;
			}

			// If the element is a radio button, use the checked property and set it to the value
			if (element.type === 'radio') {
				if (element.checked) {
					settings[key] = element.value;
				}

				return;
			}

			// If the element is a number, convert it to a number
			if (element.type === 'number') {
				// If the value is empty, set it to null
				settings[key] = element.valueAsNumber || null;

				// If the step attribute is not set or does not contain a dot, convert the value to an integer
				if (!element.step || !element.step.includes('.')) {
					settings[key] = parseInt(settings[key]);
				}
				else {
					settings[key] = parseFloat(settings[key]);
				}
			}

			// If the element is a select-multiple, get all selected options
			if (element.type === 'select-multiple') {
				settings[key] = Array.from(element.selectedOptions).map(option => option.value);
				return;
			}

			settings[key] = element.value;
		});

		let node = this.node;

		if (node.nodeName.toLowerCase() === 'widget') {
			node.dataset.identifier = identifier;
			node.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			node.textContent = JSON.stringify(settings);

			this.editor.selection.select(node);
			// Replace the widget with the new one
			this.editor.selection.current().parentNode.replaceWith(node);
		}
		else {
			const widget = document.createElement('widget');
			widget.classList.add('mceNonEditable');
			widget.dataset.identifier = identifier;
			widget.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			widget.textContent = JSON.stringify(settings);

			let target = this.editor.selection.current();
			if (!target) {
				this.editor.selection.insertNode(widget);
			}
			else {
				if (target.nodeName.toLowerCase() === '#text') {
					target = target.parentNode;
				}

				console.log(target, target.nodeName);

				if (target.matches('body')) {
					target = target.querySelector('p');
				}

				target.replaceWith(widget);
			}
		}

		return true;
	}

	/**
	 * Clean up widget tags.
	 * Unwrap widgets from block elements.
	 *
	 * @returns {void}
	 */
	cleanWidgetTags(event) {
		const target = event.target;

		// Parse the value as HTML
		const parser = new DOMParser();
		const doc = parser.parseFromString(target.value, 'text/html');

		// Get all widget elements
		// noinspection CssInvalidHtmlTagReference
		const widgets = doc.querySelectorAll('widget');

		widgets.forEach((widget) => {
			// If the widget is inside a p, div, or other block element, replace the block element with the widget
			if (widget.parentNode && widget.parentNode.nodeName.toLowerCase() !== 'body') {
				widget.parentNode.replaceWith(widget);
			}
		});

		// Set the cleaned value
		target.value = doc.body.innerHTML;
	}
}

// Register the plugin
// noinspection JSUnresolvedReference
Jodit.plugins.add('awyissWidgetConfig', (editor) => {
	const awyissWidget = new AwyissWidget(editor);

	// noinspection JSUnresolvedReference
	Jodit.widgets.Icon.set('widget', `
		<svg width="21" height="24" viewBox="0 0 21 24">
			<path d="M10.5,0l10.5,6.083v11.835l-10.5,6.083L0,17.917V6.083L10.5,0ZM2.25,8.685l7.125,4.128v7.933l-7.125-4.128v-7.933h0ZM11.625,20.746l7.125-4.128v-7.933l-7.125,4.128v7.933h0ZM10.5,10.862l7.129-4.13-7.129-4.13L3.371,6.732l7.129,4.13Z" fill-rule="evenodd" stroke-width="0"/>
		</svg>
	`);

	// noinspection JSUnresolvedReference
	Jodit.defaultOptions.controls.awyissWidgetConfig = {
		icon: 'widget',
		exec: function (editor, current, control, event) {
			const parent = editor.selection.current().parentNode;

			if (current && current.nodeName.toLowerCase() === 'widget') {
				// noinspection JSIgnoredPromiseFromCall
				awyissWidget.openOverlay(event, current);
			}
			else if (parent && parent.nodeName.toLowerCase() === 'widget') {
				// noinspection JSIgnoredPromiseFromCall
				awyissWidget.openOverlay(event, parent);
			}
			else {
				let current = editor.selection.current();

				// Jodit.IS_BLOCK holds a regex that matches block elements
				// noinspection JSUnresolvedReference
				while (current && !Jodit.IS_BLOCK.test(current.nodeName)) {
					current = current.parentNode;
				}

				// noinspection JSIgnoredPromiseFromCall
				awyissWidget.openOverlay(event, current);
			}
		},
		tooltip: 'Awyiss Widget'
	};

	editor.events.on('afterInit', (event) => {
		const element = editor.element;
		element.addEventListener('change', awyissWidget.cleanWidgetTags.bind(awyissWidget));
		element.addEventListener('input', awyissWidget.cleanWidgetTags.bind(awyissWidget));
	});

	editor.events.on('keydown', (event) => {
		if (editor.selection.current().parentNode.nodeName.toLowerCase() === 'widget') {
			if (event.key === 'Enter') {
				// Move the selection to after the widget
				const range = editor.selection.createRange();
				range.setStartAfter(editor.selection.current().parentNode);
				range.collapse(true);
				editor.selection.selectRange(range);
			}

			event.preventDefault();
			return false;
		}
	}, true);

	editor.events.on('click', () => {
		const selection = editor.selection;

		const parent = selection.current().parentNode;

		const selected = editor.editor.querySelectorAll('.Selected');
		selected.forEach((element) => {
			element.classList.remove('Selected');
		});

		if (parent && parent.nodeName.toLowerCase() === 'widget') {
			parent.classList.add('Selected');
			editor.selection.select(parent);
		}
	}, true);


	editor.events.on('dblclick', (event) => {
		if (event.target.nodeName.toLowerCase() === 'widget') {
			// noinspection JSIgnoredPromiseFromCall
			awyissWidget.openOverlay(event, event.target);
		}
	});
});