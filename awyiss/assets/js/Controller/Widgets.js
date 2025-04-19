// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import ResizableContents from 'ResizableContents';

export default class WidgetsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The context menu instance.
	 * @type {HTMLElement}
	 */
	contextMenu;
	/**
	 * The resizable handler instance.
	 * @type {ResizableContents}
	 */
	resizeableContent;

	constructor() {
		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}

		if (document.querySelector('.Form.EditFrontendEditor')) {
			// Find the close button and send an event to the parent window
			const closeButton = document.querySelector('.Button-Close');
			closeButton.addEventListener('click', () => {
				window.parent.postMessage('closeFrontendEditor', '*');
			});
		}
	}

	initOverview() {
		const nestedListHandler = window.nestedListHandler;

		nestedListHandler.setGroupIdentifierAttribute((list, items) => {
			const identifier =list.closest('fieldset').dataset.identifier;

			items.forEach((item, index) => {
				items[index] = {
					id: item,
					identifier: identifier,
					systemOrder: index + 1,
				}
			});

			return identifier;
		});

		const nestedList = document.querySelector('.NestedList');
		if (typeof columnWidths !== 'undefined' && nestedList.classList.contains('NestedList-Compact')) {
			this.resizeableContent = new ResizableContents('widgets', columnWidths);

			nestedListHandler.onEndDefault = nestedListHandler.onEnd;
			nestedListHandler.onEnd = event => {
				// noinspection JSUnresolvedReference
				nestedListHandler.onEndDefault(event);

				// Trigger the recalculation of element widths
				// noinspection JSUnresolvedReference
				this.resizeableContent.setNarrowClass(event.item);
			}
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

		this.eventHandler.add('fieldsetCollapse', event => {
			const fieldset = event.target;
			const nestedList = fieldset.querySelector('.NestedList');
			if (nestedList && this.resizeableContent && !event.detail.isCollapsed) {
				this.resizeableContent.resetListItemWidths();
			}
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {WidgetsController}
 */
window.WidgetsController = WidgetsController;
