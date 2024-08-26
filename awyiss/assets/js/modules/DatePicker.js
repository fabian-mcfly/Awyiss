// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

export default class DatePicker {
	/**
	 * The selector for the date picker input
	 * @type {string}
	 */
	selector = 'input[type="date"], input[type="datetime-local"], input[type="time"]';

	/**
	 * Initialize the date pickers present on the page and observe for new ones.
	 */
	constructor() {
		const datePickers = document.querySelectorAll(this.selector);
		datePickers.forEach((datePicker) => {
			this.initDatePicker(datePicker);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}


	/**
	 * Initialize a date picker.
	 *
	 * @param {HTMLElement} input
	 */
	initDatePicker(input) {
		const datePickerInput = input.cloneNode(true);
		datePickerInput.type = 'text';
		input.insertAdjacentElement('beforebegin', datePickerInput);

		const type = input.type;

		input.style.display = 'none';

		let altFieldDateFormat = 'yyyy-MM-dd';
		if (type === 'datetime-local') {
			altFieldDateFormat += ' HH:mm';
		}
		if (type === 'time') {
			altFieldDateFormat = 'HH:mm';
		}

		const settings = {
			altField: input,
			altFieldDateFormat: altFieldDateFormat,
			locale: airDatepickerLocale,
			onlyTimepicker: type === 'time',
			timepicker: type === 'time' || type === 'datetime-local',
		};

		const datePicker = new AirDatepicker(datePickerInput, settings);
		if (datePickerInput.value) {
			datePicker.selectDate(datePickerInput.value);
		}
	}

	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		const addedNodes = mutation.addedNodes;
		addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initDatePicker(node);
			}

			const datePickers = node.querySelectorAll(this.selector);
			datePickers.forEach((datePicker) => {
				this.initDatePicker(datePicker);
			});
		});
	}
}
