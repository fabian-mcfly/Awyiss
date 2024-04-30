// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import ResizableContents from 'ResizableContents';

export default class ContentsController {
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
			if (this.contextMenu) {
				this.contextMenu.remove();
				this.contextMenu = null;
			}

			const listItem = event.target.closest('.ListItem');
			if (!listItem || !listItem.classList.contains('Narrow')) {
				return;
			}

			// Check if the click event happend in the right area of the list item
			if (
				event.clientX > listItem.getBoundingClientRect().left + listItem.offsetWidth - 6 ||
				event.clientX < listItem.getBoundingClientRect().left + listItem.offsetWidth - 36
			) {
				return;
			}

			// Prevent the default context menu from showing up
			event.preventDefault();

			// Create a custom context menu
			const contextMenu = this.contextMenu = document.createElement('ul');
			contextMenu.classList.add('ListItem-ContextMenu');

			const auditInfo = listItem.querySelector('.AuditInfo').cloneNode(true);
			contextMenu.appendChild(auditInfo);

			window.auditInfo.bindEvents(auditInfo);

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
 * @type {ContentsController}
 */
window.ContentsController = ContentsController;
