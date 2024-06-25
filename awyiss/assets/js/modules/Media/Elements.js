// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import Sortable from 'SortableJS/sortable';

export default class Elements {
	constructor() {
		new this.sortableMediaElementAssignments();
	}

	sortableMediaElementAssignments = class {
		/**
		 * The lists containing draggable items
		 * @type {NodeListOf<Element>}
		 */
		lists;
		/**
		 * The sortable list selector
		 * @type {string}
		 */
		sortableListSelector = '.MediaElements-List';

		constructor() {
			if (document.documentElement.classList.contains('MediaElementsController')) {
				return;
			}

			this.lists = document.querySelectorAll(this.sortableListSelector);

			this.lists.forEach((element) => {
				this.initSortable(element);
			});

			const observer = window.observer;
			observer.addObserver(this.observeMutations.bind(this));
		}

		/**
		 * Initialize the sortable for the given element
		 *
		 * @param {HTMLElement} element
		 */
		initSortable(element) {
			element.sortable = Sortable.create(element, {
				chosenClass: 'SortableChosen',
				dataIdAttr: 'id',
				direction: 'vertical',
				fallbackOnBody: true,
				ghostClass: 'SortableGhost',
				group: this.sortableListSelector.slice(1),
				invertSwap: true,
				preventOnFilter: false,
				swapThreshold: .8,
				onEnd: (event) => {
					return this.onEnd(event);
				},
				onStart: (event) => {
					return this.onStart(event);
				},
			});
		}

		/**
		 * The onEnd event handler
		 * @param {object} event
		 */
		onEnd(event) {
			const item = event.item;
			const targetFieldset = event.to.closest('fieldset');

			// Get the checkbox from inside the .Title element
			const checkbox = item.querySelector('.Title input[type="checkbox"]');
			// Check the checkbox if the fieldset isn't "unused", uncheck otherwise
			setTimeout(function () {
				checkbox.checked = !targetFieldset.classList.contains('Fieldset-MediaElements-Available');
			}, 50);

			this.lists.forEach(item => {
				item.classList.remove('SortableDragging');
			});
		}

		/**
		 * The onStart event handler
		 */
		onStart() {
			this.lists.forEach(item => {
				item.classList.add('SortableDragging');
			});
		}

		/**
		 * @param {MutationRecord} mutation - The mutation to observe.
		 */
		observeMutations(mutation) {
			this.lists = document.querySelectorAll(this.sortableListSelector);

			// If nodes were added
			if (!mutation.addedNodes.length) {
				return;
			}

			mutation.addedNodes.forEach(node => {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}

				if (node.matches(this.sortableListSelector)) {
					this.initSortable(node);
				}

				const element = node.querySelectorAll(this.sortableListSelector);
				element.forEach((element) => {
					this.initSortable(element);
				});
			});
		}
	}
}
