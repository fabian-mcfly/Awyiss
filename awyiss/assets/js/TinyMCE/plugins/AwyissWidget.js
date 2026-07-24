/* Return the metadata for the help plugin */
class AwyissWidget {
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
			name: 'Awyiss Widget helper plugin',
			url: '#'
		};
	}

	/**
	 * Create the dialog element.
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'WidgetConfigurationOverlay';

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
				widgetIdentifier: identifier,
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
	 * @param {tinymce.Editor} editor
	 * @param {HTMLElement} node
	 * @returns {Promise<void>}
	 */
	async openOverlay(event, editor, node) {
		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();

		// Select the widget
		// noinspection JSUnresolvedReference
		this.editor.selection.select(node);

		let settings = {};
		if (node.nodeName.toLowerCase() === 'widget') {
			try {
				settings = JSON.parse(node.textContent ?? '{}');
			}
			catch (error) {
				// Handle or log the error as needed
				console.error('Failed to parse JSON:', error);
				settings = {};
			}
		}

		// If the editor is inside the TranslationDialog, the language of the editor's textarea
		// must be used to fetch the widget configuration
		let language = null;
		if (this.editor.getContainer().closest('#TranslationDialog')) {
			const formInput = this.editor.getContainer().closest('.FormInput ');
			// The language is part of the textarea's name
			language = formInput.querySelector('textarea').name.split('[')[1].split(']')[0];
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

			// If the widget identifier is empty, just close the dialog
			if (!this.dialog.querySelector('select[name="widgetIdentifier"]').value) {
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
	 * Copy or cut the selected widget(s) to the clipboard.
	 *
	 * @param {Event} event
	 * @param {tinymce.Editor} editor
	 * @returns {boolean}
	 */
	widgetCopyCut(event, editor) {
		let foundWidget = false;
		// noinspection JSUnresolvedReference
		let i = 0, blocks = editor.selection.getSelectedBlocks();
		let length = blocks.length;
		let clipboardData = '';

		for (; i < length; i++) {
			const block = blocks[i];

			if (block.nodeName.toLowerCase() === 'widget') {
				if (clipboardData !== '') {
					clipboardData += "\n\n";
				}

				clipboardData += '<widget class="' + block.attributes.class.nodeValue + '" data-label="' + block.dataset.label + '" data-identifier="' +
					block.dataset.identifier + '">' + block.textContent + '</widget>';

				foundWidget = true;
			}
			else if (block.textContent !== '') {
				if (clipboardData !== '') {
					clipboardData += "\n\n";
				}

				clipboardData += block.textContent;
			}
		}

		if (foundWidget) {
			// noinspection JSUnresolvedReference
			event.clipboardData.setData('text/plain', clipboardData);
			event.preventDefault();

			return true;
		}

		return false;
	}

	/**
	 * Use the settings from the dialog to update the widget node.
	 *
	 * @returns {boolean}
	 */
	useWidgetSettings() {
		const identifierSelect = this.dialog.querySelector('select[name="widgetIdentifier"]');
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

				return;
			}

			// If the element is a select-multiple, get all selected options
			if (element.type === 'select-multiple') {
				settings[key] = Array.from(element.selectedOptions).map(option => option.value);
				return;
			}

			settings[key] = element.value;
		});

		// noinspection JSUnresolvedReference
		const node = this.editor.selection.getNode();

		// If the node is a widget, update the settings
		if (node.nodeName.toLowerCase() === 'widget') {
			node.dataset.identifier = identifier;
			node.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			node.textContent = JSON.stringify(settings);
		}
		// If the node is not a widget, create a new widget node
		else {
			// noinspection JSUnresolvedReference
			const widget = this.editor.getDoc().createElement('widget');
			widget.classList.add('mceNonEditable');
			widget.dataset.identifier = identifier;
			widget.dataset.label = identifierSelect.querySelector(`option[value="${identifier}"]`).textContent;
			widget.textContent = JSON.stringify(settings);

			// If the node is a widget, replace it with the new widget
			// noinspection JSUnresolvedReference
			this.editor.insertContent(widget.outerHTML + '\n');
		}

		return true;
	}
}

(function () {
	tinymce.PluginManager.add('awyissWidget', function (editor) {
		const awyissWidget = new AwyissWidget(editor);

		editor.ui.registry.addIcon('widget', '<svg width="21" height="24" viewBox="0 0 21 24"><path d="M10.5,0l10.5,6.083v11.835l-10.5,6.083L0,17.917V6.083L10.5,0ZM2.25,8.685l7.125,4.128v7.933l-7.125-4.128v-7.933h0ZM11.625,20.746l7.125-4.128v-7.933l-7.125,4.128v7.933h0ZM10.5,10.862l7.129-4.13-7.129-4.13L3.371,6.732l7.129,4.13Z"  fill-rule="evenodd" stroke-width="0"/></svg>');

		/* Add a button that opens a window */
		editor.ui.registry.addToggleButton('awyissWidget', {
			icon: 'widget',
			text: 'Widget',
			onAction: function (event) {
				// noinspection JSIgnoredPromiseFromCall
				awyissWidget.openOverlay(event, editor, editor.selection.getNode());
			},
			onSetup: function (buttonApi) {
				return editor.on('NodeChange', function () {
					buttonApi.setActive(editor.selection.getNode().nodeName.toLowerCase() === 'widget');
				});
			}
		});

		editor.on('copy', function (event) {
			if (awyissWidget.widgetCopyCut(event, this)) {
				return false;
			}
		}.bind(editor));

		editor.on('cut', function (event) {
			if (awyissWidget.widgetCopyCut(event, this)) {
				this.execCommand('Delete');
				return false;
			}
		}.bind(editor));

		editor.on('PastePreProcess', function (event) {
			event.content = event.content.replace(/&lt;widget(.*?)&lt;\/widget&gt;/g, function (match) {
				let txt = document.createElement('textarea');
				txt.innerHTML = match;

				return txt.value;
			});
		}.bind(editor));

		editor.on('dblclick', function (event) {
			if (event.target.nodeName.toLowerCase() === 'widget') {
				// noinspection JSIgnoredPromiseFromCall
				awyissWidget.openOverlay(event, this, event.target);
			}
		}.bind(editor));

		return awyissWidget;
	});
})();