/* Return the metadata for the help plugin */
class AwyissModule {
	/**
	 * @type {HTMLDialogElement} overlay - The overlay element.
	 */
	dialog;
	/**
	 * @type {tinymce.Editor} editor - The editor instance.
	 */
	editor;

	constructor(editor) {
		this.editor = editor;
	}

	/**
	 * Return the metadata for the help plugin.
	 *
	 * @returns {{name: string, url: string}}
	 */
	getMetadata() {
		return {
			name: 'Awyiss module helper plugin',
			url: '#'
		};
	}

	/**
	 * Create the dialog element.
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'ModuleConfigurationOverlay';

		this.dialog.addEventListener('close', () =>{
			// Remove all children from the dialog
			while (this.dialog.firstChild) {
				this.dialog.removeChild(this.dialog.firstChild);
			}
		});

		this.dialog.addEventListener('click', event => this.handleDialogClick(event));

		this.dialog.addEventListener('keypress', event => {
			// Prevent the dialog from closing when pressing the enter key
			if (event.key === 'Enter') {
				event.preventDefault();
			}
		});

		// Append dialog to body
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
	async openOverlay(event, editor, node) {
		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();

		// Select the module
		// noinspection JSUnresolvedReference
		this.editor.selection.select(node);

		let settings = {};

		if (node.nodeName.toLowerCase() === 'module') {
			try {
				settings = JSON.parse(node.textContent ?? '{}');
			}
			catch (error) {
				// Handle or log the error as needed
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
		if (event.target.matches('.Button-Save')) {
			event.preventDefault();

			// If the module identifier is empty, just close the dialog
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
	 * Copy or cut the selected module(s) to the clipboard.
	 *
	 * @param {Event} event
	 * @param {tinymce.Editor} editor
	 * @returns {boolean}
	 */
	moduleCopyCut(event, editor) {
		let foundModule = false;
		// noinspection JSUnresolvedReference
		let i = 0, blocks = editor.selection.getSelectedBlocks();
		let length = blocks.length;
		let clipboardData = '';

		for (; i < length; i++) {
			const block = blocks[i];

			if (block.nodeName.toLowerCase() === 'module') {
				if (clipboardData !== '') {
					clipboardData += "\n\n";
				}

				clipboardData += '<module class="' + block.attributes.class.nodeValue + '" data-label="' + block.dataset.label + '">' +
					block.textContent + '</module>';

				foundModule = true;
			}
			else if (block.textContent !== '') {
				if (clipboardData !== '') {
					clipboardData += "\n\n";
				}

				clipboardData += block.textContent;
			}
		}

		if (foundModule) {
			// noinspection JSUnresolvedReference
			event.clipboardData.setData('text/plain', clipboardData);
			event.preventDefault();

			return true;
		}

		return false;
	}

	/**
	 * Use the settings from the dialog to update the module node.
	 *
	 * @returns {void}
	 */
	useModuleSettings() {
		const identifierSelect = this.dialog.querySelector('select[name="module_identifier"]');
		const identifier = identifierSelect.value;

		// Get the form data for all elements starting with `settings[` and create a JSON object
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

			// If the element is a number, convert it to a number
			if (element.type === 'number') {
				// If the value is empty, set it to null
				settings[key] = element.value === '' ? null : element.value;

				// If step is not set or does not contain a dot, convert the value to a float
				if (!element.step || !element.step.includes('.')) {
					settings[key] = parseFloat(settings[key]);
				}
				else {
					settings[key] = parseInt(settings[key]);
				}

				return;
			}

			settings[key] = element.value;
		});

		// noinspection JSUnresolvedReference
		const node = this.editor.selection.getNode();

		// If the node is a module, update the settings
		if (node.nodeName.toLowerCase() === 'module') {
			node.dataset.identifier = identifier;
			node.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			node.textContent = JSON.stringify(settings);
		}
		// If the node is not a module, create a new module node
		else {
			// noinspection JSUnresolvedReference
			const module = this.editor.getDoc().createElement('module');
			module.classList.add('mceNonEditable');
			module.dataset.identifier = identifier;
			module.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			module.textContent = JSON.stringify(settings);

			// If the node is a module, replace it with the new module
			// noinspection JSUnresolvedReference
			this.editor.insertContent(module.outerHTML + '\n');
		}
	}
}

(function () {
	tinymce.PluginManager.add('awyissModule', function (editor) {
		const awyissModule = new AwyissModule(editor);

		editor.ui.registry.addIcon('module', '<svg width="21" height="24" viewBox="0 0 21 24"><path d="M10.5,0l10.5,6.083v11.835l-10.5,6.083L0,17.917V6.083L10.5,0ZM2.25,8.685l7.125,4.128v7.933l-7.125-4.128v-7.933h0ZM11.625,20.746l7.125-4.128v-7.933l-7.125,4.128v7.933h0ZM10.5,10.862l7.129-4.13-7.129-4.13L3.371,6.732l7.129,4.13Z"  fill-rule="evenodd" stroke-width="0"/></svg>');

		/* Add a button that opens a window */
		editor.ui.registry.addToggleButton('awyissModule', {
			icon: 'module',
			text: 'Module',
			onAction: function (event) {
				// noinspection JSIgnoredPromiseFromCall
				awyissModule.openOverlay(event, editor, editor.selection.getNode());
			},
			onSetup: function (buttonApi) {
				return editor.on('NodeChange', function () {
					buttonApi.setActive(editor.selection.getNode().nodeName.toLowerCase() === 'module');
				});
			}
		});

		editor.on('copy', function (event) {
			if (awyissModule.moduleCopyCut(event, this)) {
				return false;
			}
		}.bind(editor));

		editor.on('cut', function (event) {
			if (awyissModule.moduleCopyCut(event, this)) {
				this.execCommand('Delete');
				return false;
			}
		}.bind(editor));

		editor.on('PastePreProcess', function (event) {
			event.content = event.content.replace(/&lt;module(.*?)&lt;\/module&gt;/g, function (match) {
				let txt = document.createElement('textarea');
				txt.innerHTML = match;

				return txt.value;
			});
		}.bind(editor));

		editor.on('dblclick', function (event) {
			if (event.target.nodeName.toLowerCase() === 'module') {
				// noinspection JSIgnoredPromiseFromCall
				awyissModule.openOverlay(event, this, event.target);
			}
		}.bind(editor));

		return awyissModule;
	});
})();