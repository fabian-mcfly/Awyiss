export default class HistoryTables {
	/**
	 * Whether sliding the tables should change the URL.
	 *
	 * @type {boolean}
	 */
	urlChange = false;

	/**
	 * Initialize the history page.
	 */
	constructor(wrapper) {
		if (!wrapper) {
			wrapper = document.querySelector('.AuditHistory-Tables')
		}

		const tables = wrapper.querySelectorAll('.AuditHistory-Table');

		if (!tables.length) {
			return;
		}

		// Wrap all tables in a container
		const slider = document.createElement('div');
		slider.classList.add('AuditHistory-Slider');

		tables.forEach(table => {
			slider.appendChild(table);
			table.inert = true;
		});

		// Append the slider to the table's wrapper
		wrapper.appendChild(slider);

		// Create the slider controls
		this.createSliderControls(wrapper, slider);

		// Add event listeners
		this.addSliderEventListeners(wrapper, slider);

		// Trigger the initial update
		this.updateSlider(slider, slider.children.length - 1);
	}

	/**
	 * Enable the URL change feature.
	 */
	enableUrlChange() {
		this.urlChange = true;

		// If the url contains a hash, scroll to the corresponding table
		const hash = window.location.hash;

		if (hash) {
			const timestamp = hash.substring(1);
			const table = document.querySelector(`[data-timestamp="${timestamp}"]`);

			if (table) {
				const slider = table.closest('.AuditHistory-Slider');
				const range = slider.parentElement.querySelector('.AuditHistory-Slider-Range');
				const index = Array.from(slider.children).indexOf(table);

				this.updateSlider(slider, range.max - index);
				range.value = range.max - index;
			}
		}
	}

	/**
	 * Add event listeners to the slider.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {HTMLElement} slider
	 */
	addSliderEventListeners(wrapper, slider) {
		const prev = wrapper.querySelector('.AuditHistory-Slider-Prev');
		const next = wrapper.querySelector('.AuditHistory-Slider-Next');
		const range = wrapper.querySelector('.AuditHistory-Slider-Range');

		prev.addEventListener('click', () => {
			range.stepDown();
			this.updateSlider(slider, range.value * 1);
		});

		next.addEventListener('click', () => {
			range.stepUp();
			this.updateSlider(slider, range.value * 1);
		});

		range.addEventListener('input', () => {
			this.updateSlider(slider, range.value * 1);
		});
	}

	/**
	 * Create the slider controls.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {HTMLElement} slider
	 */
	createSliderControls(wrapper, slider) {
		// Create slide controls
		const controls = document.createElement('div');
		controls.classList.add('AuditHistory-Slider-Controls');

		const prev = document.createElement('button');
		prev.classList.add('AuditHistory-Slider-Prev');
		controls.appendChild(prev);

		const range = document.createElement('input');
		range.type = 'range';
		range.min = 0;
		range.max = slider.children.length - 1;
		range.value = range.max;
		range.classList.add('AuditHistory-Slider-Range');
		range.style.setProperty('--stepCount', slider.children.length);
		controls.appendChild(range);

		const next = document.createElement('button');
		next.classList.add('AuditHistory-Slider-Next');
		controls.appendChild(next);

		this.appendColumnSelectors(wrapper, controls);

		wrapper.insertBefore(controls, slider);
	}


	/**
	 * Append column selectors to the slider controls.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {HTMLElement} controls
	 */
	appendColumnSelectors(wrapper, controls) {
		// Column Selection for old value, new value, current value
		const columnSelectionWrapper = document.createElement('details');
		columnSelectionWrapper.classList.add('AuditHistory-ColumnSelection');
		controls.appendChild(columnSelectionWrapper);

		const label = document.createElement('summary');
		label.textContent = wrapper.dataset.showColumnsLabel ?? 'Show Columns';
		columnSelectionWrapper.appendChild(label);

		// Column Selection for old value, new value, current value
		const columnSelection = document.createElement('div');
		columnSelection.classList.add('FormInput', 'FormInputType-Multicheckbox');
		columnSelectionWrapper.appendChild(columnSelection);

		let checked;
		let checkedCounter = 0;
		const localStorageKey = 'audit_history_column_selection';
		const storedSelection = localStorage.getItem(localStorageKey);
		if (storedSelection) {
			const selection = JSON.parse(storedSelection);

			if (selection.old !== undefined) {
				wrapper.dataset.showOldValue = selection.old ? '1' : '0';
			}

			if (selection.new !== undefined) {
				wrapper.dataset.showNewValue = selection.new ? '1' : '0';
			}

			if (selection.current !== undefined) {
				wrapper.dataset.showCurrentValue = selection.current ? '1' : '0';
			}
		}

		checked = wrapper.dataset.showOldValue === '1' || wrapper.dataset.showOldValue === 'true';
		if (checked) {
			checkedCounter++;
		}
		columnSelection.appendChild(this.createColumnSelector(
			wrapper,
			checked,
			'old',
			wrapper.dataset.oldValueLabel ?? 'Old Value'
		));

		checked = wrapper.dataset.showNewValue === '1' || wrapper.dataset.showNewValue === 'true';
		if (checked) {
			checkedCounter++;
		}
		columnSelection.appendChild(this.createColumnSelector(
			wrapper,
			checked,
			'new',
			wrapper.dataset.newValueLabel ?? 'New Value'
		));

		checked = wrapper.dataset.showCurrentValue === '1' || wrapper.dataset.showCurrentValue === 'true';
		if (checked) {
			checkedCounter++;
		}
		columnSelection.appendChild(this.createColumnSelector(
			wrapper,
			checked,
			'current',
			wrapper.dataset.currentValueLabel ?? 'Current Value'
		));

		columnSelectionWrapper.appendChild(this.createReferenceColumnSelector(wrapper));

		wrapper.querySelectorAll('.AuditHistory-Table').forEach(table => {
			// Update all tables and set the --columns CSS variable
			table.style.setProperty('--columns', checkedCounter + 1); // +1 for the field name column

			// Update all tds with a colspan attribute
			table.querySelectorAll('td[colspan]').forEach(td => {
				td.colSpan = checkedCounter + 1; // +1 for the field name column
			});
		});
	}


	/**
	 * Create a column selector checkbox.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {boolean} checked
	 * @param {string} type
	 * @param {string} labelText
	 * @returns {HTMLDivElement}
	 */
	createColumnSelector(wrapper, checked, type, labelText) {
		const formElement = document.createElement('div');
		formElement.classList.add('FormInput', 'FormInputType-Checkbox');

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.checked = checked;
		checkbox.id = 'AuditHistory-ColumnSelector-' + type.charAt(0).toUpperCase() + type.slice(1);
		formElement.appendChild(checkbox);

		const label = document.createElement('label');
		label.classList.add('Label');
		label.textContent = labelText;
		label.htmlFor = checkbox.id;
		formElement.appendChild(label);

		checkbox.addEventListener('change', () => {
			const localStorageKey = 'audit_history_column_selection';
			const storedSelection = localStorage.getItem(localStorageKey);
			let selection = {};

			if (storedSelection) {
				selection = JSON.parse(storedSelection);
			}

			selection[ type ] = checkbox.checked;
			localStorage.setItem(localStorageKey, JSON.stringify(selection));

			switch (type) {
				case 'old':
					wrapper.dataset.showOldValue = checkbox.checked ? '1' : '0';
					break;
				case 'new':
					wrapper.dataset.showNewValue = checkbox.checked ? '1' : '0';
					break;
				case 'current':
					wrapper.dataset.showCurrentValue = checkbox.checked ? '1' : '0';
					break;
			}

			let checkedCounter = 0;
			formElement.parentElement.querySelectorAll('input[type="checkbox"]').forEach(cb => {
				if (cb.checked) {
					checkedCounter++;
				}
			});

			wrapper.querySelectorAll('.AuditHistory-Table').forEach(table => {
				// Update all tables and set the --columns CSS variable
				table.style.setProperty('--columns', checkedCounter + 1); // +1 for the field name column

				// Update all tds with a colspan attribute
				table.querySelectorAll('td[colspan]').forEach(td => {
					td.colSpan = checkedCounter + 1; // +1 for the field name column
				});
			});

			// Update the slider to adjust the height
			const slider = wrapper.querySelector('.AuditHistory-Slider');
			const range = wrapper.querySelector('.AuditHistory-Slider-Range');

			const currentIndex = range.value * 1;
			const currentElement = slider.children[ slider.children.length - 1 - currentIndex ];
			delete currentElement.htmlInitialized;

			this.updateSlider(slider, currentIndex);
		});

		return formElement;
	}

	/**
	 * Create a reference column selector dropdown.
	 *
	 * @param {HTMLElement} wrapper
	 * @returns {HTMLDivElement}
	 */
	createReferenceColumnSelector(wrapper) {
		const formElement = document.createElement('div');
		formElement.classList.add('FormInput', 'FormInputType-Select');

		const label = document.createElement('label');
		label.classList.add('Label');
		label.textContent = wrapper.dataset.referenceColumnLabel ?? 'Reference Column';
		label.htmlFor = 'AuditHistory-ReferenceColumnSelector';
		formElement.appendChild(label);

		const select = document.createElement('select');
		select.id = 'AuditHistory-ReferenceColumnSelector';
		select.classList.add('Select');
		formElement.appendChild(select);

		// Create options
		const options = [
			{ value: 'old', label: wrapper.dataset.oldValueLabel ?? 'Old Value' },
			{ value: 'new', label: wrapper.dataset.newValueLabel ?? 'New Value' },
			{ value: 'current', label: wrapper.dataset.currentValueLabel ?? 'Current Value' }
		];

		options.forEach(option => {
			const optionElement = document.createElement('option');
			optionElement.value = option.value;
			optionElement.textContent = option.label;
			select.appendChild(optionElement);
		});

		// Load stored selection or use default
		const localStorageKey = 'audit_history_column_selection';
		const storedSelection = localStorage.getItem(localStorageKey);
		let storedReference = 'current'; // Default value

		if (storedSelection) {
			const selection = JSON.parse(storedSelection);
			if (selection.reference && ['old', 'new', 'current'].includes(selection.reference)) {
				storedReference = selection.reference;
			}
		}

		select.value = storedReference;
		wrapper.classList.add('ReferenceColumn-' + storedReference.charAt(0).toUpperCase() + storedReference.slice(1));

		// Add event listener
		select.addEventListener('change', () => {
			const newValue = select.value;

			// Remove old reference column class
			wrapper.classList.remove('ReferenceColumn-Old', 'ReferenceColumn-New', 'ReferenceColumn-Current');

			// Add new reference column class
			wrapper.classList.add('ReferenceColumn-' + newValue.charAt(0).toUpperCase() + newValue.slice(1));

			// Save to local storage
			const storedSelection = localStorage.getItem(localStorageKey);
			let selection = {};

			if (storedSelection) {
				selection = JSON.parse(storedSelection);
			}

			selection.reference = newValue;
			localStorage.setItem(localStorageKey, JSON.stringify(selection));
		});

		return formElement;
	}


	/**
	 * Update the slider.
	 *
	 * @param {HTMLElement} slider
	 * @param {number} index
	 */
	updateSlider(slider, index) {
		// Set the CSS property
		slider.style.setProperty('--slideOffset', index - slider.children.length + 1);

		// Get the current table
		const current = slider.children[ slider.children.length - 1 - index ];
		const dateTime = current.dataset.dateTime;

		const controls = slider.parentElement.querySelector('.AuditHistory-Slider-Controls');
		controls.title = dateTime;

		// Toggle the buttons
		const prev = slider.parentElement.querySelector('.AuditHistory-Slider-Prev');
		prev.disabled = index === 0;
		if (prev.disabled && prev.querySelector('.Hover')) {
			// Reset width and height of the button since disabled buttons have no pointer events,
			// so the mouse leave event won't be triggered
			const hoverElement = prev.querySelector('.Hover');
			if (hoverElement) {
				hoverElement.style.width = '';
				hoverElement.style.height = '';
			}
		}

		const next = slider.parentElement.querySelector('.AuditHistory-Slider-Next');
		next.disabled = index === slider.children.length - 1;
		if (next.disabled && next.querySelector('.Hover')) {
			// Reset width and height of the button since disabled buttons have no pointer events,
			// so the mouse leave event won't be triggered
			const hoverElement = next.querySelector('.Hover');
			if (hoverElement) {
				hoverElement.style.width = '';
				hoverElement.style.height = '';
			}
		}

		// Set the current table to be active
		// and set all other tables to be inactive
		for (let i = 0, childrenLength = slider.children.length; i < childrenLength; i++) {
			slider.children[i].inert = i !== slider.children.length - 1 - index;
		}

		if (!current.htmlInitialized) {
			// noinspection JSUnresolvedReference
			clearTimeout(slider.htmlInitialized);

			slider.htmlInitialized = setTimeout(() => {
				/**
				 * For each `tr.ValueIsHtml`, create an iframe and set the content inside
				 * the `TableCell-OldValue`- and `TableCell-CurrentValue`-cells
				 */
				current.querySelectorAll('.ValueIsHtml').forEach(row => {
					const codeBlocks = row.querySelectorAll('code');
					codeBlocks.forEach(codeBlock => {
						let cell = codeBlock.closest('td');

						// If the cell is not visible or already has an iframe, skip it
						if (cell.offsetParent === null) {
							return;
						}

						let iframe = cell.querySelector('iframe');
						if (iframe) {
							cell.removeChild(iframe);
						}

						iframe = document.createElement('iframe');
						cell.appendChild(iframe);

						iframe.addEventListener('load', () => {
							iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';

							setTimeout(function () {
								iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
								slider.style.setProperty('--slideHeight', current.clientHeight + 'px');
							}, 200);
						});

						iframe.contentWindow.document.open();

						// Get the success color from the root element
						const successColor = getComputedStyle(document.documentElement).getPropertyValue('--colorSuccess');
						if (successColor && successColor !== '#63D1A5') {
						iframe.contentWindow.document.write(`<html lang="${languageShortcode}" style="--customColor:${successColor};">`);
						}
						else {
						iframe.contentWindow.document.write(`<html lang="${languageShortcode}">`);
						}

						// noinspection HtmlRequiredTitleElement
						iframe.contentWindow.document.write('<head>');
						iframe.contentWindow.document.write(`<link rel="stylesheet" href="${baseUrl}assets/css/audit_history.css">`);
						iframe.contentWindow.document.write('</head>');
						iframe.contentWindow.document.write('<body class="AuditHistory-Table-Content">');
						iframe.contentWindow.document.write(codeBlock.innerText);
						iframe.contentWindow.document.write('</body>');
						iframe.contentWindow.document.write('</html>');
						iframe.contentWindow.document.close();
					});

					current.htmlInitialized = true;

					// Add a toggle button to show/hide the full content of the cell
					const toggleButton = document.createElement('button');
					toggleButton.classList.add('AuditHistory-Table-ToggleButton', 'Button-Small', 'Button-View');
					toggleButton.textContent = 'Toggle';
					toggleButton.addEventListener('click', () => {
						row.classList.toggle('ViewCode');
					});
					row.querySelector('.TableCell-Field').appendChild(toggleButton);
				});
			}, 500);
		}
		else {
			slider.style.setProperty('--slideHeight', current.clientHeight + 'px');
		}

		if (this.urlChange) {
			// noinspection JSUnresolvedReference
			clearTimeout(slider.urlChange);

			slider.urlChange = setTimeout(() => {
				const url = new URL(window.location.href);

				url.hash = '#' + current.dataset.timestamp;

				// If it's the last table, remove the hash
				if (index === slider.children.length - 1) {
					url.hash = '';
				}

				history.replaceState(null, '', url.toString());
			}, 500);
		}
	}
}
