// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import ResizableContents from 'ResizableContents';

/**
 * Class to handle the contents overview and form.
 */
export default class ContentsController {
	/**
	 * The event handler instance.
	 *
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The context menu instance.
	 *
	 * @type {HTMLElement}
	 */
	contextMenu;
	/**
	 * The resizable handler instance.
	 *
	 * @type {ResizableContents}
	 */
	resizeableContent;

	constructor() {
		if (!document.body.classList.contains('ContentsController')) {
			return;
		}

		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.Contents.Form'));
		}

		const frontendEditor = document.querySelector('.Form.EditFrontendEditor');
		if (frontendEditor) {
			// Find the close buttons and send an event to the parent window
			const closeButtons = frontendEditor.querySelectorAll('.Button-Close');
			closeButtons.forEach(closeButton => {
				closeButton.addEventListener('click', () => {
					window.parent.postMessage('closeFrontendEditor', '*');
				});
			});

			document.getElementById('Content').dataset.title = frontendEditor.dataset.title;
			frontendEditor.focus();
		}
	}

	/**
	 * Initialize the logic for the overview.
	 *
	 * Enable resizing of the contents,
	 * disallow moving contents into areas they are not allowed to be in
	 * and add a context menu to the list items when they are narrow.
	 *
	 * @returns {void}
	 */
	initOverview() {
		const nestedListHandler = window.nestedListHandler;

		nestedListHandler.setGroupIdentifierAttribute((list, items) => {
			const contentAreaId = parseInt(list.closest('fieldset').dataset.contentAreaId);

			items.forEach((item, index) => {
				items[index] = {
					id: item,
					contentAreaId: contentAreaId,
					systemOrder: index + 1,
				}
			});

			return contentAreaId;
		});

		nestedListHandler.onStartDefault = nestedListHandler.onStart;
		nestedListHandler.onStart = event => {
			nestedListHandler.onStartDefault(event);

			// Find the list items, including the dragged item
			// noinspection JSUnresolvedReference
			const items = [event.item, ...event.item.querySelectorAll('.ListItem')];

			const contentAreas = document.querySelectorAll('.Overview-Fieldset');
			contentAreas.forEach(contentArea => {
				const contentAreaId = parseInt(contentArea.dataset.contentAreaId);

				for (const item of items) {
					if (!JSON.parse(item.dataset.contentAreaIds).includes(contentAreaId)) {
						contentArea.classList.add('UnassignedContentArea');
						return;
					}
				}
			});
		}

		nestedListHandler.onMoveDefault = nestedListHandler.onMove;
		nestedListHandler.onMove = event => {
			// noinspection JSUnresolvedReference
			const defaultReturn = nestedListHandler.onMoveDefault(event);

			// If the default return is false, then we don't want to do anything
			if (defaultReturn === false) {
				return false;
			}

			// Find the content area ID
			// noinspection JSUnresolvedReference
			const contentAreaId = parseInt(event.to.closest('fieldset').dataset.contentAreaId);

			// Find the list items, including the dragged item
			// noinspection JSUnresolvedReference
			const items = [event.dragged, ...event.dragged.querySelectorAll('.ListItem')];

			// Traverse the list items and check if the content area ID is part of the data-content-area-ids attribute
			// If it isn't, moving the item is not allowed
			for (const item of items) {
				if (!JSON.parse(item.dataset.contentAreaIds).includes(contentAreaId)) {
					return false;
				}
			}
		}

		const nestedList = document.querySelector('.NestedList');
		if (typeof columnWidths !== 'undefined' && nestedList.classList.contains('NestedList-Compact')) {
			this.resizeableContent = new ResizableContents('contents', columnWidths);
		}

		this.eventHandler.add('click', event => {
			const listItem = event.target.closest('.ListItem');
			if (!listItem || listItem.getBoundingClientRect().width > 310) {
				if (this.contextMenu) {
					this.contextMenu.remove();
					this.contextMenu = null;
				}

				return;
			}

			// Check if the click event happened in the right area of the list item
			if (
				event.clientX > listItem.getBoundingClientRect().left + listItem.offsetWidth - 6 ||
				event.clientX < listItem.getBoundingClientRect().left + listItem.offsetWidth - 36
			) {
				return;
			}

			if (listItem.querySelector(':scope > .ListItem-ContextMenu')) {
				this.contextMenu.remove();
				this.contextMenu = null;
				return;
			}

			if (this.contextMenu) {
				this.contextMenu.remove();
				this.contextMenu = null;
			}

			// Prevent the default context menu from showing up
			event.preventDefault();

			// Create a custom context menu
			const contextMenu = this.contextMenu = document.createElement('ul');
			contextMenu.classList.add('ListItem-ContextMenu');

			const auditInfo = listItem.querySelector('.AuditInfo').cloneNode(true);
			contextMenu.appendChild(auditInfo);

			window.audit.auditInfo.bindEvents(auditInfo);

			const actions = listItem.querySelector('.Actions').cloneNode(true);
			contextMenu.appendChild(actions);

			// Append the context menu to the body
			listItem.appendChild(contextMenu);
		});

		nestedListHandler.onEndDefault = nestedListHandler.onEnd;
		nestedListHandler.onEnd = event => {
			nestedListHandler.onEndDefault(event);

			const contentAreas = document.querySelectorAll('.Overview-Fieldset.UnassignedContentArea');
			contentAreas.forEach(contentArea => {
				contentArea.classList.remove('UnassignedContentArea');
			});
		}
	}

	/**
	 * Initialize the logic for the form.
	 *
	 * @returns {void}
	 */
	initForm(form) {
		new DuplicateOfConfiguration(form);
	}
}

/**
 * Class to handle the configuration of the duplicate of field.
 */
export class DuplicateOfConfiguration {
	/**
	 * The overlay element.
	 *
	 * @type {HTMLDialogElement} overlay
	 */
	dialog;
	/**
	 * The input field for the duplicate of
	 *
	 * @type {HTMLInputElement} duplicateOfInput
	 */
	duplicateOfInput;
	/**
	 * Whether the form has been changed when opening the overlay.
	 *
	 * @type {boolean} isFormChanged
	 */
	isFormChanged = false;

	constructor(form = document) {
		this.duplicateOfInput = form.querySelector('#Content-DuplicateOf');

		if (this.duplicateOfInput) {
			this.duplicateOfInput.instantUpdate = true;
			this.duplicateOfInput.addEventListener('click', event => this.openOverlay(event));
		}

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Create the dialog element.
	 *
	 * @returns {void}
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'DuplicateConfigurationOverlay';

		this.dialog.addEventListener('close', () => {
			// Remove all children from the dialog
			while (this.dialog.firstChild) {
				this.dialog.removeChild(this.dialog.firstChild);
			}

			// Reset the form changed status
			window.formLeaveConfirmation.isFormChanged = this.isFormChanged;
		});

		this.dialog.addEventListener('click', event => this.handleDialogClick(event));

		this.dialog.addEventListener('dblclick', event => {

			// If the target is a checkbox, use
			if (event.target.matches('label')) {
				const checkbox = event.target.querySelector('input[type="checkbox"]');

				this.duplicateOfInput.value = checkbox.value || '';
				this.isFormChanged = true;

				// Trigger an input event
				const inputEvent = new Event('input', {
					bubbles: true,
				});
				this.duplicateOfInput.dispatchEvent(inputEvent);

				this.dialog.close();
			}
		});

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
	 * Fetch the duplicate configuration form.
	 *
	 * @returns {Promise<Element>}
	 */
	async fetchDuplicateConfiguration() {
		const response = await fetch(`${baseUrl}backend/${languageShortcode}/contents/duplicate-configuration/page-id:${this.duplicateOfInput.dataset.pageId}/`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				duplicate_of_page_id: this.duplicateOfInput.dataset.duplicateOfPageId,
				duplicate_of: this.duplicateOfInput.value,
				content_template_id: this.duplicateOfInput.dataset.contentTemplateId,
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
		this.isFormChanged = window.formLeaveConfirmation.isFormChanged;

		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();

		const form = await this.fetchDuplicateConfiguration();

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

			// Get the ckecked checkbox and use its value
			const checked = this.dialog.querySelector('input[type="checkbox"]:checked');
			this.duplicateOfInput.value = checked?.value || '';
			this.isFormChanged = true;

			// Trigger an input event
			const inputEvent = new Event('input', {
				bubbles: true,
			});
			this.duplicateOfInput.dispatchEvent(inputEvent);

			this.dialog.close();

			return;
		}

		// If the click event was on an input, uncheck all other checkboxes
		if (event.target.matches('input[name="duplicate_of"]')) {
			const checkbox = event.target;
			const checked = this.dialog.querySelectorAll('input[type="checkbox"]:checked');

			for (const item of checked) {
				if (item !== checkbox) {
					item.checked = false;
				}
			}
		}

		if (event.target.matches('.Button-Close')) {
			event.preventDefault();
			this.dialog.close();
		}
	}


	/**
	 * Observe mutations in the DOM and set up the duplicate of configuration.
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			const selector = '#Content-DuplicateOf';

			if (node.nodeType === Node.ELEMENT_NODE) {
				if (node.matches(selector)) {
					this.duplicateOfInput = node;
					this.duplicateOfInput.instantUpdate = true;
					this.duplicateOfInput.addEventListener('click', event => this.openOverlay(event));
				}

				const elements = node.querySelectorAll(selector);
				elements.forEach((element) => {
					this.duplicateOfInput = element;
					this.duplicateOfInput.instantUpdate = true;
					this.duplicateOfInput.addEventListener('click', event => this.openOverlay(event));
				});
			}
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {ContentsController}
 */
window.ContentsController = ContentsController;
