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
		});

		// Append the slider to the tables wrapper
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
			console.log(range.value);
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

		wrapper.insertBefore(controls, slider);
	}

	/**
	 * Update the slider.
	 *
	 * @param {HTMLElement} slider
	 * @param {number} index
	 */
	updateSlider(slider, index) {
		// Set the css property
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
			// Reset width and height of the button since disabled buttons have no pointer events
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
			// Reset width and height of the button since disabled buttons have no pointer events
			// so the mouse leave event won't be triggered
			const hoverElement = next.querySelector('.Hover');
			if (hoverElement) {
				hoverElement.style.width = '';
				hoverElement.style.height = '';
			}
		}

		if (!current.htmlInitialized) {
			// noinspection JSUnresolvedReference
			clearTimeout(slider.htmlInitialized);

			slider.htmlInitialized = setTimeout(() => {
				/**
				 * For each tr.ValueIsHtml, create an iframe and set the content inside
				 * the `TableCell-OldValue`- and `TableCell-CurrentValue`-cells
				 */
				current.querySelectorAll('.ValueIsHtml').forEach(row => {
					const codeBlocks = row.querySelectorAll('code');
					codeBlocks.forEach(codeBlock => {
						let cell = codeBlock.closest('td');
						let iframe = document.createElement('iframe');
						cell.appendChild(iframe);

						iframe.addEventListener('load', () => {
							iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';

							setTimeout(function () {
								iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
								slider.style.setProperty('--slideHeight', current.clientHeight + 'px');
							}, 200);
						});

						iframe.contentWindow.document.open();
						iframe.contentWindow.document.write(`<html lang="${languageShortcode}">`);
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
