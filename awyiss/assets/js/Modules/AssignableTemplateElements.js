// noinspection JSUnusedGlobalSymbols

import Sortable from 'SortableJS/sortable';

export default class AssignableTemplateElements {
	/**
	 * The lists containing draggable items
	 * @type HTMLElement[]
	 */
	lists;
	/**
	 * The sortable list selector
	 * @type {string}
	 */
	sortableListSelector;

	constructor(selector, parentElement = document) {
		this.sortableListSelector = selector;

		this.lists = Array.from(parentElement.querySelectorAll(this.sortableListSelector));

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

		// Add an add/remove button to each list item
		const items = parentElement.querySelectorAll(`${this.sortableListSelector} > .Item`);
		items.forEach((item) => {
			// Items that are not assignable don't need the button
			if (item.dataset.assignable === 'false') {
				return;
			}

			const addRemoveButton = document.createElement('button');
			addRemoveButton.type = 'button';

			const add = item.closest(this.sortableListSelector).dataset.fieldset === 'unused';

			addRemoveButton.classList.add('Button', 'Button-AddRemove', add ? 'Button-Add' : 'Button-Delete');
			item.appendChild(addRemoveButton);
		});

		// Add event listeners to the add/remove buttons
		window.eventHandler.add('click', this.handleAddRemoveButtonClick.bind(this), document.querySelector('#Content form'));
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

		window.formLeaveConfirmation.formChanged();
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

	/**
	 * Handle the add/remove button click
	 * @param {MouseEvent} event
	 */
	handleAddRemoveButtonClick(event) {
		const button = event.target.closest('.Button-AddRemove');

		if (!button) {
			return;
		}

		const item = button.closest('.Item');
		let added = false;

		if (button.classList.contains('Button-Add')) {
			let targetList = this.lists.filter((list) => {
				return list.dataset.fieldset === 'general';
			})

			if (!targetList.length) {
				// If no target list is found, use the first list that is not the current list
				targetList = this.lists.filter((list) => {
					return list.dataset.fieldset !== 'unused';
				});
			}

			// Move the item to the target list
			targetList[0].appendChild(item);

			added = true;
		}
		else {
			const unusedList = this.lists.filter((list) => {
				return list.dataset.fieldset === 'unused';
			});

			// Move the item to the unused list
			unusedList[0].appendChild(item);
		}

		button.classList.toggle('Button-Add');
		button.classList.toggle('Button-Delete');

		// Reset width and height of the button since the moved button
		// will not have its mouseleave event triggered
		const hoverElement = button.querySelector('.Hover');
		if (hoverElement) {
			hoverElement.style.width = '';
			hoverElement.style.height = '';
		}

		if (added) {
			setTimeout(() => item.scrollIntoView({behavior: 'smooth', block: 'center'}), 300);
		}

		window.formLeaveConfirmation.formChanged();
	}
}
