// noinspection JSUnusedGlobalSymbols

/**
 * Search class
 */
export default class Search {
	/**
	 * An instance of EventHandler
	 *
	 * @property {EventHandler} eventHandler
	 */
	eventHandler = window.eventHandler;
	/**
	 * The selector for filter buttons
	 *
	 * @type {string}
	 */
	filterButtonSelector = '.Button-ShowFilter';
	/**
	 * The selector for reset filter buttons
	 *
	 * @type {string}
	 */
	resetButtonSelector = '.Button-ResetFilter';

	/**
	 * The selector for filter forms
	 *
	 * @type {string}
	 */
	filterSelector = '.SearchFilter-Form';

	constructor() {
		const filter = document.querySelectorAll(this.filterSelector);
		if (filter.length) {
			filter.forEach((element) => {
				new SearchFilter(element);
			});
		}

		const filterButton = document.querySelectorAll(this.filterButtonSelector);
		if (filterButton.length) {
			filterButton.forEach((element) => {
				this.bindFilterButtonClick(element);
			});
		}

		const resetButton = document.querySelectorAll(this.resetButtonSelector);
		if (resetButton.length) {
			resetButton.forEach((element) => {
				this.bindResetButtonClick(element);
			});
		}

		const observer = window.observer;
		observer.addObserver(this.observe.bind(this));
	}

	bindFilterButtonClick(element) {
		const target = document.querySelector(element.dataset.target || '.SearchFilter-Form');
		if (!target) {
			return;
		}

		this.eventHandler.add('click', function (event) {
			event.preventDefault();
			event.stopPropagation();

			target.showModal();
		}, element);
	}

	bindResetButtonClick(element) {
		const target = document.querySelector(element.dataset.target || '.SearchFilter-Form');
		if (!target) {
			return;
		}

		this.eventHandler.add('click', function (event) {
			event.preventDefault();
			event.stopPropagation();

			const hiddenInput = document.createElement('input');
			hiddenInput.type = 'hidden';
			hiddenInput.name = 'submit_type';
			hiddenInput.value = 'reset';

			const form = target.querySelector('form');
			form.appendChild(hiddenInput);

			// Submit the form to reset the filters
			form.requestSubmit();
		}, element);
	}

	observe(mutation) {
		if (!mutation.addedNodes.length) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.filterSelector)) {
				new SearchFilter(node);
			}

			if (node.matches(this.filterButtonSelector)) {
				this.bindFilterButtonClick(node);
			}

			if (node.matches(this.resetButtonSelector)) {
				this.bindResetButtonClick(node);
			}

			const filter = node.querySelectorAll(this.filterSelector);
			if (filter.length) {
				filter.forEach((element) => {
					new SearchFilter(element);
				});
			}

			const filterButton = node.querySelectorAll(this.filterButtonSelector);
			if (filterButton.length) {
				filterButton.forEach((element) => {
					this.bindFilterButtonClick(element);
				});
			}

			const resetButton = node.querySelectorAll(this.resetButtonSelector);
			if (resetButton.length) {
				resetButton.forEach((element) => {
					this.bindResetButtonClick(element);
				});
			}
		});
	}
}

export class SearchFilter {
	/**
	 * The element to attach the event listeners to
	 * @type {Element}
	 */
	element;
	/**
	 * An instance of EventHandler
	 *
	 * @property {EventHandler} eventHandler
	 */
	eventHandler = window.eventHandler;
	/**
	 * The dirty state of the form
	 * @type {boolean}
	 */
	formState = false

	constructor(element) {
		this.element = element;
		this.eventHandler.add('input', this.handleInputEvent.bind(this), this.element);
		this.eventHandler.add('click', this.handleClickEvent.bind(this), this.element);

		const rows = this.element.querySelectorAll('.Column');
		// For each row, add a remove button
		rows.forEach((row) => {
			this.initRow(row);
		});

		//this.resetColumnOrder();

		// Handle the open and close events of the dialog
		this.element.addEventListener('beforetoggle', this.handleShowEvent.bind(this));
		this.element.addEventListener('close', this.handleCloseEvent.bind(this));
	}

	handleShowEvent(event) {
		if (event.newState === 'open') {
			this.formState = window.formLeaveConfirmation.isFormChanged;
		}
	}

	handleCloseEvent() {
		window.formLeaveConfirmation.isFormChanged = this.formState;
	}

	handleClickEvent(event) {
		const target = event.target;
		const removeButton = target.closest('.RemoveColumn');

		if (removeButton) {
			const row = target.closest('.Column');
			const select = this.element.querySelector('select[name="filter_add_column"]');
			const column = row.dataset.column;

			row.classList.remove('Active');

			// Enable the option for the column
			select.querySelector('option[value="' + column + '"]').disabled = false;

			// Remove the operator
			const operator = row.querySelector('select[name^="filter["][name$="][operator]"]');
			operator.value = '';

			const valueInput = row.querySelector('input[name^="filter["][name$="][value]"]');
			if (valueInput.matches('[type="radio"]')) {
				// Set the value to the default value
				valueInput.checked = true;
			}
			else {
				valueInput.value = '';
			}

			const hiddenActiveInput = row.querySelector('input[name^="filter["][name$="][active]"]');
			hiddenActiveInput.value = '0';

			removeButton.setAttribute('tabindex', '-1');
		}

		if (target.closest('.Button-Close') && !target.closest('.Button-Reset')) {
			event.preventDefault();
			event.stopPropagation();

			const form = target.closest('dialog');
			form.close();
		}
	}

	handleInputEvent(event) {
		const target = event.target;

		if (target.matches('select[name^="filter["][name$="][operator]"]')) {
			const value = target.value;
			const valueInput = target.closest('.Column').querySelector('input[name^="filter["][name$="[value]"]');

			const multipleValueOperators = ['in', 'not_in', 'between', 'not_between'];
			valueInput.setAttribute('data-allow-multiple-values', multipleValueOperators.includes(value) ? 'true' : 'false');

			if (valueInput.closest('.InputType-Datetime')) {
				const datePicker = valueInput.datepicker;
				datePicker.update({
					multipleDates: multipleValueOperators.includes(value) && (value === 'in' || value === 'not_in'),
					range: multipleValueOperators.includes(value) && value !== 'in' && value !== 'not_in',
				}, true);
			}

			const row = valueInput.closest('.Column');

			// Toggle the Active class of the row
			row.classList.toggle('Active', value !== '');

			const removeButton = row.querySelector('.RemoveColumn');
			if (removeButton) {
				removeButton.setAttribute('tabindex', value !== '' ? '0' : '-1');
			}

			const hiddenActiveInput = row.querySelector('input[name^="filter["][name$="][active]"]');
			hiddenActiveInput.value = value !== '' ? '1' : '0';
		}

		if (target.matches('input[name^="filter["][name$="][value]"]')) {
			const row = target.closest('.Column');

			const operatorSelect = row.querySelector('select[name^="filter["][name$="][operator]"]');

			if (operatorSelect.value === '' && !target.matches('[type="radio"]')) {
				// Set the operator to the default value
				operatorSelect.value = '=';
			}

			const hiddenActiveInput = row.querySelector('input[name^="filter["][name$="][active]"]');
			hiddenActiveInput.value = '1';

			const removeButton = row.querySelector('.RemoveColumn');

			if (target.matches('[type="radio"]') && target.value === '') {
				hiddenActiveInput.value = 0;

				// Toggle the Active class of the row
				row.classList.remove('Active');
				removeButton.setAttribute('tabindex', '-1');
			}
			else {
				// Toggle the Active class of the row
				row.classList.add('Active');
				removeButton.setAttribute('tabindex', '0');
			}
		}
	}

	/*resetColumnOrder() {
		const rows = this.element.querySelectorAll('.Column');
		let order = 1;

		rows.forEach((row, index) => {
			const hiddenOrderInput = row.querySelector('input[name^="filter["][name$="][order]"]');
			const hiddenActiveInput = row.querySelector('input[name^="filter["][name$="][active]"]');

			hiddenActiveInput.value = row.matches('.Active') ? '1' : '0';

			if (row.matches('.Active')) {
				hiddenOrderInput.value = order++;
			}
			else {
				hiddenOrderInput.value = '';
			}
		});
	}*/

	initRow(row) {
		const operatorInput = row.querySelector('select[name^="filter["][name$="][operator]"]');
		const operator = operatorInput.value;

		let datePicker = !!row.querySelector('.InputType-Date');
		let timePicker = !!row.querySelector('.InputType-Time');
		let dateTimePicker = !!row.querySelector('.InputType-Datetime');

		let altFieldDateFormat = 'yyyy-MM-dd';
		if (dateTimePicker) {
			altFieldDateFormat += ' HH:mm';
		}
		if (timePicker) {
			altFieldDateFormat = 'HH:mm';
		}

		if (datePicker || timePicker || dateTimePicker) {
			const valueInput = row.querySelector('input[name^="filter["][name$="][value]"]');

			const datePickerInput = valueInput.cloneNode(true);
			datePickerInput.name = '';
			datePickerInput.type = 'text';
			datePickerInput.value = '';
			valueInput.insertAdjacentElement('beforebegin', datePickerInput);

			valueInput.datepicker = new AirDatepicker(datePickerInput, {
				altField: valueInput,
				altFieldDateFormat: altFieldDateFormat,
				container: this.element,
				locale: airDatepickerLocale,
				multipleDates: valueInput.dataset.allowMultipleValues === 'true' && (operator === 'in' || operator === 'not_in'),
				multipleDatesSeparator: ', ',
				onlyTimepicker: timePicker,
				position: function ({$datepicker, $target, $pointer}) {
					// We are extremely lazy here and just use the internal function to set the position
					// There is no need to reinvent the wheel and practically copy the code
					$datepicker.classList.remove('-custom-position-', '-left-bottom-', '-top-center-');

					if (document.body.clientWidth <= 768) {
						$datepicker.classList.remove('-from-left-');
						this.datepicker._setPositionClasses('top center');
						this.datepicker.setPosition('top center');

						return;
					}

					$datepicker.classList.remove('-from-top-');
					this.datepicker._setPositionClasses('left bottom');
					this.datepicker.setPosition('left bottom');
				}.bind(valueInput),
				range: valueInput.dataset.allowMultipleValues === 'true' && operator !== 'in' && operator !== 'not_in',
				timepicker: dateTimePicker || timePicker,
				onSelect: function (data) {
					const element = data.datepicker.$el;
					const inputEvent = new Event('input', {
						bubbles: true,
						cancelable: true,
					});
					element.dispatchEvent(inputEvent)
				}
			});

			window.eventHandler.add('input', function (event) {
				const value = event.target.value;

				// If the value contains '-', the user has most likely pasted a date
				if (value.includes('-')) {
					const dates = value.split(',').map((date) => {
						return new Date(date);
					});

					valueInput.datepicker.selectDate(dates);
				}

				const inputEvent = new Event('input', {
					bubbles: true,
					cancelable: true,
				});
				valueInput.dispatchEvent(inputEvent);
			}, datePickerInput);

			if (valueInput.value) {
				// If the value contains a comma, split the value and select the dates
				if (valueInput.value.includes(',')) {
					const dates = valueInput.value.split(',').map((date) => {
						return new Date(date);
					});
					valueInput.datepicker.selectDate(dates);
				}
				else {
					// Select the date
					valueInput.datepicker.selectDate(datePickerInput.value);
				}
			}
		}

		const text = row.dataset.listItemRemove || 'Remove';
		const className = ['Button-Remove', 'RemoveColumn'];

		// Create a new button element
		const button = document.createElement('button');
		// Set the button type to button
		button.type = 'button';
		// Make the button not focusable by default
		button.tabIndex = row.classList.contains('Active') ? '0' : '-1';
		// Set the button text
		button.textContent = text;
		// Add the Button and the provided class to the button
		button.classList.add('Button', ...className);
		// Append the button to the element
		row.appendChild(button);
	}
}