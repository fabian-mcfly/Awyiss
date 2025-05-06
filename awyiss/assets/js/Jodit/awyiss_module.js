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
	 * @returns {Promise<Element>}
	 */
	async fetchModuleConfiguration(identifier, settings) {
		const response = await fetch(`${baseUrl}backend/${languageShortcode}/contents/module-configuration/`, {
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

		let settings = {};

		if (node.nodeName.toLowerCase() === 'module') {
			try {
				settings = JSON.parse(node.textContent ?? '{}');
			} catch (error) {
				console.error('Failed to parse JSON:', error);
				return;
			}
		}

		const form = await this.fetchModuleConfiguration(node.dataset.identifier ?? null, settings);

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

			this.useModuleSettings();
			this.dialog.close();
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
	 * @returns {void}
	 */
	useModuleSettings() {
		const identifierSelect = this.dialog.querySelector('select[name="module_identifier"]');
		const identifier = identifierSelect.value;

		const settings = {};

		const formElements = this.dialog.querySelectorAll('input[name^="settings["], select[name^="settings["], textarea[name^="settings["]');
		formElements.forEach(element => {
			// noinspection RegExpRedundantEscape
			const key = element.name.match(/\[(.*?)\]/)[1];

			if (element.type === 'checkbox') {
				settings[key] = element.checked;
				return;
			}

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

			settings[key] = element.value;
		});

		const node = this.editor.selection.current();

		if (node.nodeName.toLowerCase() === 'module') {
			node.dataset.identifier = identifier;
			node.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			node.textContent = JSON.stringify(settings);
		}
		else {
			const module = document.createElement('module');
			module.classList.add('mceNonEditable');
			module.dataset.identifier = identifier;
			module.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			module.textContent = JSON.stringify(settings);

			this.editor.selection.insertNode(module);
		}
	}

	/**
	 * Clean up module tags.
	 * Unwrap modules from block elements.
	 *
	 * @returns {void}
	 */
	cleanModuleTags(event, clean = false) {
		const target = event.target;

		if (clean) {
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

			return;
		}

		if (target.cleanTimeout) {
			clearTimeout(target.cleanTimeout);
		}

		target.cleanTimeout = setTimeout(function () {
			target.cleanTimeout = null;
			this.cleanModuleTags(event, true);
		}.bind(this), 500);
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
		tooltip: 'Awyiss module helper plugin'
	};

	editor.events.on('afterInit', (event) => {
		const element = editor.element;
		element.addEventListener('change', awyissModule.cleanModuleTags.bind(awyissModule));
		element.addEventListener('input', awyissModule.cleanModuleTags.bind(awyissModule));

		// Trigger a change event to clean the module tags
		setTimeout(() => {
			element.dispatchEvent(new Event('change'));
		}, 250);
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