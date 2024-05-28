// noinspection JSUnusedGlobalSymbols

// Core SortableJS (without default plugins)
import Sortable from '../SortableJS/sortable.core.esm.js';

export default class ContentTemplatesController {
	constructor() {
		if (document.documentElement.classList.contains('AddAction') || document.documentElement.classList.contains('EditAction')) {
			this.initForm();
		}

	}

	initForm() {
		new this.sortableContentElements();
	}

	sortableContentElements = class {
		/**
		 * The lists containing draggable items
		 * @type {NodeListOf<Element>}
		 */
		lists;
		/**
		 * The sortable list selector
		 * @type {string}
		 */
		sortableListSelector = '.ContentElements-List';

		constructor() {
			this.lists = document.querySelectorAll(this.sortableListSelector);

			this.lists.forEach((element) => {
				element.sortable = Sortable.create(element, {
					chosenClass: 'SortableChosen',
					dataIdAttr: 'id',
					direction: 'vertical',
					fallbackOnBody: true,
					ghostClass: 'SortableGhost',
					group: this.sortableListSelector.slice(1),
					invertSwap: true,
					preventOnFilter: false,
					swapThreshold: .4,
					onEnd: (event) => {
						return this.onEnd(event);
					},
					onMove: (event) => {
						return this.onMove(event);
					},
					onStart: (event) => {
						return this.onStart(event);
					},
				});
			});
		}

		/**
		 * The onEnd event handler
		 * @param {object} event
		 */
		onEnd(event) {
			const item = event.item;
			const targetFieldset = event.to.dataset.fieldset;

			// Get the checkbox from inside the .Title element
			const checkbox = item.querySelector('.Title input[type="checkbox"]');
			// Check the checkbox if the fieldset isn't "unused", uncheck otherwise
			setTimeout(function () {
				checkbox.checked = targetFieldset !== 'unused';
			}, 50);

			// Find the select element
			const select = item.querySelector('select');
			// Set the selected option to the target fieldset
			select.value = targetFieldset;

			this.lists.forEach(item => {
				item.classList.remove('SortableDragging');
			});
		}

		/**
		 * The onMove event handler
		 * @param {object} event
		 */
		onMove(event) {
			// Check if the item being moved has the `data-assignable="false"` attribute
			// and if the list it's being moved to is the "unused" fieldset
			if (event.dragged.dataset.assignable === 'false' && event.to.dataset.fieldset === 'unused') {
				// If both conditions are met, cancel the move
				return false;
			}
		}

		/**
		 * The onStart event handler
		 */
		onStart() {
			this.lists.forEach(item => {
				item.classList.add('SortableDragging');
			});
		}
	}
}

/**
 * Expose the class globally
 * @global
 * @type {ContentTemplatesController}
 */
window.ContentTemplatesController = ContentTemplatesController;
