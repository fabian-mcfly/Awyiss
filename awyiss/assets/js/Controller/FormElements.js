// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import ResizableContents from 'ResizableContents';

export default class FormElementsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The form element.
	 * @type {HTMLElement}
	 */
	form = null;

	constructor() {
		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
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
		}

		// If the document contains a flash message, send an event to the parent window
		if (document.querySelector('.FlashMessage.Success') && window.parent !== window) {
			window.parent.postMessage('closeFrontendEditorAndFetch', '*');

			// Empty the dom so the user can't interact with the page
			document.body.innerHTML = '';
		}
	}

	/**
	 * Initialize the logic for the overview.
	 *
	 * Enable resizing of the elements,
	 * and add a context menu to the list items when they are narrow.
	 *
	 * @returns {void}
	 */
	initOverview() {
		const nestedList = document.querySelector('.NestedList');
		if (typeof columnWidths !== 'undefined' && nestedList.classList.contains('NestedList-Compact')) {
			this.resizeableElements = new ResizableContents('form-elements', columnWidths);
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
	}
}

/**
 * Expose the class globally
 * @global
 * @type {FormElementsController}
 */
window.FormElementsController = FormElementsController;