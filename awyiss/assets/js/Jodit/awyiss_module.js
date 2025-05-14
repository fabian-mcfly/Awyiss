// noinspection DuplicatedCode

class AwyissModule {
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
		this.dialog.id = 'ModuleConfigurationOverlay';

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
	 * Fetch the module configuration form.
	 * @param {string} identifier
	 * @param {Object} settings
	 * @param {string} language
	 * @returns {Promise<Element>}
	 */
	async fetchModuleConfiguration(identifier, settings, language) {
		language = language || languageShortcode;

		const response = await fetch(`${baseUrl}backend/${language}/contents/module-configuration/`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				module_identifier: identifier,
				settings: settings,
			}),
		});

		const html = await response.text();

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, "text/html");

		return doc.querySelector('#Content')?.querySelector('.Form');
	}

	/**
	 * Open the overlay to configure the module.
	 *
	 * @param {Event} event
	 * @param {tinymce.Editor} editor
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

		if (node.nodeName.toLowerCase() === 'module') {
			try {
				settings = JSON.parse(node.textContent ?? '{}');
			} catch (error) {
				console.error('Failed to parse JSON:', error);
				settings = {};
			}
		}

		// If the editor is inside the TranslationDialog, the language of the editor's textarea
		// must be used to fetch the module configuration
		let language = null;
		if (this.editor.element.closest('#TranslationDialog')) {
			// The language is part of the textarea's name
			language = this.editor.element.name.split('[')[1].split(']')[0];
		}

		const form = await this.fetchModuleConfiguration(node.dataset.identifier ?? null, settings, language);

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

			if (!this.dialog.querySelector('select[name="module_identifier"]').value) {
				this.dialog.close();
				return;
			}

			if (this.useModuleSettings()) {
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
	 * Use the settings from the dialog to update the module node.
	 *
	 * @returns {boolean}
	 */
	useModuleSettings() {
		const identifierSelect = this.dialog.querySelector('select[name="module_identifier"]');
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

		if (node.nodeName.toLowerCase() === 'module') {
			node.dataset.identifier = identifier;
			node.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			node.textContent = JSON.stringify(settings);

			this.editor.selection.select(node);
			// Replace the module with the new one
			this.editor.selection.current().parentNode.replaceWith(node);
		}
		else {
			const module = document.createElement('module');
			module.classList.add('mceNonEditable');
			module.dataset.identifier = identifier;
			module.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			module.textContent = JSON.stringify(settings);

			let target = this.editor.selection.current();
			if (!target) {
				this.editor.selection.insertNode(module);
			}
			else {
				if (target.nodeName.toLowerCase() === '#text') {
					target = target.parentNode;
				}

				console.log(target, target.nodeName);

				if (target.matches('body')) {
					target = target.querySelector('p');
				}

				target.replaceWith(module);
			}
		}

		return true;
	}

	/**
	 * Clean up module tags.
	 * Unwrap modules from block elements.
	 *
	 * @returns {void}
	 */
	cleanModuleTags(event) {
		const target = event.target;

		// Parse the value as HTML
		const parser = new DOMParser();
		const doc = parser.parseFromString(target.value, 'text/html');

		// Get all module elements
		// noinspection CssInvalidHtmlTagReference
		const modules = doc.querySelectorAll('module');

		modules.forEach((module) => {
			// If the module is inside a p, div, or other block element, replace the block element with the module
			if (module.parentNode && module.parentNode.nodeName.toLowerCase() !== 'body') {
				module.parentNode.replaceWith(module);
			}
		});

		// Set the cleaned value
		target.value = doc.body.innerHTML;
	}
}

// Register the plugin
// noinspection JSUnresolvedReference
Jodit.plugins.add('awyissModuleConfig', (editor) => {
	const awyissModule = new AwyissModule(editor);

	// noinspection JSUnresolvedReference
	Jodit.modules.Icon.set('module', `
		<svg width="21" height="24" viewBox="0 0 21 24">
			<path d="M10.5,0l10.5,6.083v11.835l-10.5,6.083L0,17.917V6.083L10.5,0ZM2.25,8.685l7.125,4.128v7.933l-7.125-4.128v-7.933h0ZM11.625,20.746l7.125-4.128v-7.933l-7.125,4.128v7.933h0ZM10.5,10.862l7.129-4.13-7.129-4.13L3.371,6.732l7.129,4.13Z"  fill-rule="evenodd" stroke-width="0"/>
		</svg>
	`);

	// noinspection JSUnresolvedReference
	Jodit.defaultOptions.controls.awyissModuleConfig = {
		icon: 'module',
		exec: function (editor, current, control, event) {
			const parent = editor.selection.current().parentNode;

			if (current && current.nodeName.toLowerCase() === 'module') {
				// noinspection JSIgnoredPromiseFromCall
				awyissModule.openOverlay(event, current);
			}
			else if (parent && parent.nodeName.toLowerCase() === 'module') {
				// noinspection JSIgnoredPromiseFromCall
				awyissModule.openOverlay(event, parent);
			}
			else {
				let current = editor.selection.current();

				// Jodit.IS_BLOCK holds a regex that matches block elements
				// noinspection JSUnresolvedReference
				while (current && !Jodit.IS_BLOCK.test(current.nodeName)) {
					current = current.parentNode;
				}

				// noinspection JSIgnoredPromiseFromCall
				awyissModule.openOverlay(event, current);
			}
		},
		tooltip: 'Awyiss Module'
	};

	editor.events.on('afterInit', (event) => {
		const element = editor.element;
		element.addEventListener('change', awyissModule.cleanModuleTags.bind(awyissModule));
		element.addEventListener('input', awyissModule.cleanModuleTags.bind(awyissModule));
	});

	editor.events.on('keydown', (event) => {
		if (editor.selection.current().parentNode.nodeName.toLowerCase() === 'module') {
			if (event.key === 'Enter') {
				// Move the selection to after the module
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

		if (parent && parent.nodeName.toLowerCase() === 'module') {
			parent.classList.add('Selected');
			editor.selection.select(parent);
		}
	}, true);


	editor.events.on('dblclick', (event) => {
		if (event.target.nodeName.toLowerCase() === 'module') {
			// noinspection JSIgnoredPromiseFromCall
			awyissModule.openOverlay(event, event.target);
		}
	});
});