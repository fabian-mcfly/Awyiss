// noinspection JSUnusedGlobalSymbols

export default class SeoAnalyzeDialog {
	/**
	 * @type {HTMLButtonElement}
	 */
	button;
	/**
	 * @type {HTMLDialogElement}
	 */
	dialog;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * Initialize the SEO analyze dialog.
	 */
	constructor(dialog = null, openerButton = null) {
		this.button = openerButton ?? document.querySelector('#SeoAnalyzeDialogButton');
		if (!this.button) {
			return;
		}

		this.dialog = dialog ?? document.querySelector('#SeoAnalyzeDialog');
		if (!this.dialog) {
			return;
		}

		this.button.inert = false;

		this.eventHandler.add('click', this.handleButtonClick.bind(this), this.button);
		this.eventHandler.add('click', this.handleDialogClick.bind(this), this.dialog);

		this.fetchSeoAnalyzeContent();
	}

	/**
	 * Reset the SEO analyze dialog to loading state and refetch the content.
	 */
	reset() {
		if (!this.dialog || !this.button) {
			return;
		}

		this.button.classList.remove('SeoInfoStatus-Ok', 'SeoInfoStatus-Warning', 'SeoInfoStatus-Error');
		this.button.classList.add('Button-Loading');
		this.button.querySelector('i')?.remove();

		this.resetElement('TextLength');
		this.resetElement('HeadlineStructure');
		this.resetElement('MetaTitle');
		this.resetElement('MetaDescription');

		this.fetchSeoAnalyzeContent();
	}

	/**
	 * Reset a seo info element to loading state.
	 *
	 * @param {string} type
	 */
	resetElement(type) {
		const infoElement = this.dialog.querySelector(`.SeoInfo-${type}`);
		const title = infoElement.querySelector('.Title');
		const icon = infoElement.querySelector('.Icon');

		infoElement.classList.remove('SeoInfoStatus-Ok', 'SeoInfoStatus-Warning', 'SeoInfoStatus-Error');

		// Reset the title to contain only the strong text
		title.innerHTML = title.querySelector('strong').outerHTML;

		icon.querySelector('i')?.remove();
		icon.classList.add('Loading');
	}

	/**
	 * Handle the click event on the SEO analyze button.
	 *
	 * @param {MouseEvent} event
	 */
	handleButtonClick(event) {
		this.dialog.showModal();
	}

	/**
	 * Handle the click event on the SEO analyze dialog.
	 *
	 * @param {MouseEvent} event
	 */
	handleDialogClick(event) {
		if (event.target.closest('.Button-Close')) {
			event.preventDefault();
			this.dialog.close();
		}
	}

	/**
	 * Fetch the SEO analyze content and insert it into the dialog.
	 */
	fetchSeoAnalyzeContent() {
		const pageId = this.dialog.dataset.pageId;

		fetch(`${baseUrl}backend/${languageShortcode}/seo/analyze-rendered-page/id:${pageId}/`, {
			method: 'GET',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
		})
		.then(response => response.json())
		.then(data => {
			const status = {
				errors: 0,
				warnings: 0,
				ok: 0,
			};

			this.setStatus('TextLength', data.status.contents, status);
			this.setStatus('HeadlineStructure', data.status.headlines, status);
			this.setStatus('MetaTitle', data.status.title, status);
			this.setStatus('MetaDescription', data.status.description, status);

			/**
			 * const mostlyUsedWordsTitle = element.querySelector('.SeoInfo-MostlyUsedWords')
			 * 		if (data.mostlyUsedWords) {
			 * 			const mostlyUsedWordsList = document.createElement('ul');
			 * 			mostlyUsedWordsList.classList.add('MostlyUsedWords-List');
			 *
			 * 			// Traverse mostly used words object
			 * 			for (const [word, count] of Object.entries(data.mostlyUsedWords)) {
			 * 				const listItem = document.createElement('li');
			 * 				listItem.innerHTML = `${word} (${count}x)`;
			 * 				mostlyUsedWordsList.appendChild(listItem);
			 * 			}
			 *
			 * 			mostlyUsedWordsTitle.appendChild(mostlyUsedWordsList);
			 * 		}
			 */

			const statusClass = [];
			let iconClass = '';

			if (status.ok > 0) {
				statusClass.push('SeoInfoStatus-Ok');
				iconClass = 'la-check';
			}

			if (status.warnings > 0) {
				statusClass.push('SeoInfoStatus-Warning');
				iconClass = 'la-exclamation';
			}

			if (status.errors > 0) {
				statusClass.push('SeoInfoStatus-Error');
				iconClass = 'la-times';
			}

			const icon = document.createElement('i');
			icon.classList.add('las', iconClass);

			this.button.appendChild(icon)
			this.button.classList.remove('Button-Loading');
			this.button.classList.add(...statusClass);
		});
	}


	/**
	 * Set the status for a specific SEO check.
	 *
	 * @param {string} type
	 * @param {Object} data
	 * @param {Object} status
	 */
	setStatus(type, data, status) {
		const infoElement = this.dialog.querySelector(`.SeoInfo-${type}`);
		const icon = infoElement.querySelector('.Icon');
		const title = infoElement.querySelector('.Title');

		icon.classList.remove('Loading');

		if (!data) {
			return;
		}

		title.innerHTML += ' ' + data.status;

		if (type === 'TextLength' || type === 'HeadlineStructure') {
			this.setArrayData(data, infoElement, icon, title, status);
		}
		else {
			this.setSingleData(data, infoElement, icon, status);
		}
	}

	/**
	 * Set the status for checks that return arrays of errors and warnings.
	 *
	 * @param {Object} data
	 * @param {HTMLElement} infoElement
	 * @param {HTMLElement} icon
	 * @param {HTMLElement} title
	 * @param {Object} status
	 */
	setArrayData(data, infoElement, icon, title, status) {
		if (data.errors.length) {
			infoElement.classList.add('SeoInfoStatus-Error');
			icon.innerHTML = '<i class="las la-times"></i>';

			// Get the first error and add it to the title
			title.innerHTML += `: ${data.errors[0]}`;

			status.errors += 1;
		}
		else {
			// noinspection JSUnresolvedReference
			if (data.warnings.length) {
				infoElement.classList.add('SeoInfoStatus-Warning');
				icon.innerHTML = '<i class="las la-exclamation"></i>';

				// Get the first warning and add it to the title
				// noinspection JSUnresolvedReference
				title.innerHTML += `: ${data.warnings[0]}`;

				status.warnings += 1;
			}
			else {
				infoElement.classList.add('SeoInfoStatus-Ok');
				icon.innerHTML = '<i class="las la-check"></i>';

				status.ok += 1;
			}
		}
	}

	/**
	 * Set the status for checks that return a single error or warning.
	 *
	 * @param {Object} data
	 * @param {HTMLElement} infoElement
	 * @param {HTMLElement} icon
	 * @param {Object} status
	 */
	setSingleData(data, infoElement, icon, status) {
		if (data.error) {
			infoElement.classList.add('SeoInfoStatus-Error');
			icon.innerHTML = '<i class="las la-times"></i>';

			status.errors += 1;
		}
		else {
			if (data.warning) {
				infoElement.classList.add('SeoInfoStatus-Warning');
				icon.innerHTML = '<i class="las la-exclamation"></i>';
				status.warnings += 1;
			}
			else {
				infoElement.classList.add('SeoInfoStatus-Ok');
				icon.innerHTML = '<i class="las la-check"></i>';

				status.ok += 1;
			}
		}
	}
}