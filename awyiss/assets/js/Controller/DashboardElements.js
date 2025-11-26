// noinspection JSUnusedGlobalSymbols

import AirDatepicker from 'AirDatepicker/AirDatepicker';

export default class DashboardElementsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('DashboardElementsController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.DashboardElements.Form'));
		}
	}


	/**
	 * Initialize the logic for the form.
	 *
	 * @param {HTMLElement} form The form element
	 * @returns {void}
	 */
	initForm(form) {
		this.eventHandler.add('input', this.handleInputEvent.bind(this), form);
		this.eventHandler.add('click', this.handleClickEvent.bind(this), form);

		const rows = form.querySelectorAll('.Column');
		// For each row, add a remove button
		rows.forEach((row) => {
			this.initRow(row);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}


	/**
	 * Handle click events
	 * @param {MouseEvent} event The click event
	 * @returns {void}
	 */
	handleClickEvent(event) {
		const target = event.target;
		const removeButton = target.closest('.RemoveColumn');

		if (removeButton) {
			const row = target.closest('.Column');

			row.classList.remove('Active');

			// Remove the operator
			const operator = row.querySelector('select[name^="settings[filter]["][name$="][operator]"]');
			operator.value = '';
			operator.dispatchEvent(new Event('change', {bubbles: true}));

			const valueInput = row.querySelector('input[name^="settings[filter]["][name$="][value]"]');
			if (valueInput) {
				if (valueInput.matches('[type="radio"]')) {
					// Set the value to the default value
					valueInput.checked = true;
				}
				else {
					valueInput.value = '';
				}
			}

			const hiddenActiveInput = row.querySelector('input[name^="settings[filter]["][name$="][active]"]');
			hiddenActiveInput.value = '0';

			removeButton.setAttribute('tabindex', '-1');
		}
	}

	/**
	 * Handle input events
	 * @param {InputEvent} event The input event
	 * @returns {void}
	 */
	handleInputEvent(event) {
		const target = event.target;

		if (target.matches('select[name^="settings[filter]["][name$="][operator]"]')) {
			const value = target.value;
			const valueInput = target.closest('.Column').querySelector('input[name^="settings[filter]["][name$="[value]"]');

			if (valueInput) {
				const multipleValueOperators = ['in', 'not_in', 'between', 'not_between'];
				valueInput.setAttribute('data-allow-multiple-values', multipleValueOperators.includes(value) ? 'true' : 'false');

				if (valueInput.closest('.InputType-Datetime')) {
					const datePicker = valueInput.datepicker;
					datePicker.update({
						multipleDates: multipleValueOperators.includes(value) && (value === 'in' || value === 'not_in'),
						range: multipleValueOperators.includes(value) && value !== 'in' && value !== 'not_in',
					}, true);
				}
			}

			const row = target.closest('.Column');
			// Toggle the Active class of the row
			row.classList.toggle('Active', value !== '');

			const removeButton = row.querySelector('.RemoveColumn');
			if (removeButton) {
				removeButton.setAttribute('tabindex', value !== '' ? '0' : '-1');
			}

			const hiddenActiveInput = row.querySelector('input[name^="settings[filter]"][name$="][active]"]');
			hiddenActiveInput.value = value !== '' ? '1' : '0';
		}

		if (target.matches('input[name^="settings[filter]"][name$="][value]"]')) {
			const row = target.closest('.Column');

			const operatorSelect = row.querySelector('select[name^="settings[filter]"][name$="][operator]"]');

			if (operatorSelect.value === '' && !target.matches('[type="radio"]')) {
				// Set the operator to the default value
				operatorSelect.value = '=';
			}

			const hiddenActiveInput = row.querySelector('input[name^="settings[filter]"][name$="][active]"]');
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


	/**
	 * Initialize a row
	 * @param {HTMLElement} row The row element
	 * @returns {void}
	 */
	initRow(row) {
		const operatorInput = row.querySelector('select[name^="settings[filter]["][name$="][operator]"]');
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
			const valueInput = row.querySelector('input[name^="settings[filter]["][name$="][value]"]');

			const datePickerInput = valueInput.cloneNode(true);
			datePickerInput.name = '';
			datePickerInput.type = 'text';
			datePickerInput.value = '';
			valueInput.insertAdjacentElement('beforebegin', datePickerInput);

			valueInput.setAttribute('autocomplete', 'off');

			valueInput.datepicker = new AirDatepicker(datePickerInput, {
				altField: valueInput,
				altFieldDateFormat: altFieldDateFormat,
				container: this.element,
				keyboardNav: true,
				locale: airDatepickerLocale,
				multipleDates: valueInput.dataset.allowMultipleValues === 'true' && (operator === 'in' || operator === 'not_in'),
				multipleDatesSeparator: ', ',
				onlyTimepicker: timePicker,
				position: function ({$datepicker}) {
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

			// Set the altField to inert so it is not focusable
			valueInput.datepicker.$altField.inert = true;

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
					valueInput.datepicker.selectDate(valueInput.value);
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

	/**
	 * Observe mutations
	 *
	 * @param {MutationRecord} mutation The mutation record
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE || !node.closest('.DashboardElements.Form')) {
				return;
			}

			if (node.matches('.Column')) {
				this.initRow(node);
			}

			const filter = node.querySelectorAll('.Column');
			filter.forEach((element) => {
				this.initRow(element);
			});
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {DashboardElementsController}
 */
window.DashboardElementsController = DashboardElementsController;
