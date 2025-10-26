//noinspection JSUnusedGlobalSymbols

export default class SeoController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('SeoController')) {
			return;
		}

		if (document.body.classList.contains('AnalyzeAction')) {
			this.initAnalyze();
		}
	}

	/**
	 * Initialize the SEO analyze page
	 */
	initAnalyze() {
		// Fetch the headline status for all pages
		fetch(`${baseUrl}backend/${languageShortcode}/seo/analyze-rendered-pages/`, {
			method: 'GET',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
		}).then(response => response.json()).then(data => {
			// Traverse the object and set the status for each page
			// noinspection JSUnresolvedReference
			for (const [page, status] of Object.entries(data.pages)) {
				const element = document.querySelector(`#Pages-ListItem${page}`);

				if (!element) {
					return;
				}

				this.setTextLengthStatus(element, status);
				this.setHeadlineStructureStatus(element, status);
			}

			const summaryList = document.querySelector('.Summary-List');

			let hasContentLengthErrors = false;
			let hasContentLengthWarnings = false;

			// noinspection JSDeprecatedSymbols
			if (data.summary.contents.errors) {
				hasContentLengthErrors = true;
				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.contents.errors;
				li.classList.add('TextLength-Error', 'Error');

				if (summaryList.querySelector('li')) {
					summaryList.insertBefore(li, summaryList.querySelector('li'));
				}
				else {
					summaryList.appendChild(li);
				}
			}

			// noinspection JSDeprecatedSymbols,JSUnresolvedReference
			if (data.summary.contents.warnings) {
				hasContentLengthWarnings = true;
				const firstWarning = summaryList.querySelector('li.Warning');

				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.contents.warnings;
				li.classList.add('TextLength-Warning', 'Warning');

				if (firstWarning) {
					summaryList.insertBefore(li, firstWarning);
				}
				else {
					summaryList.appendChild(li);
				}
			}

			// noinspection JSDeprecatedSymbols
			if (data.summary.headlines.errors) {
				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.headlines.errors;
				li.classList.add('HeadlineStructure-Error', 'Error');

				let selector = 'li';
				if (hasContentLengthErrors) {
					selector = 'li.TextLength-Error + li';
				}

				if (summaryList.querySelector(selector)) {
					summaryList.insertBefore(li, summaryList.querySelector(selector));
				}
				else {
					summaryList.appendChild(li);
				}
			}

			// noinspection JSDeprecatedSymbols,JSUnresolvedReference
			if (data.summary.headlines.warnings) {
				let firstWarning = summaryList.querySelector('li.Warning');
				if (hasContentLengthWarnings) {
					firstWarning = summaryList.querySelector('li.TextLength-Warning + li');
				}

				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.headlines.warnings;
				li.classList.add('HeadlineStructure-Warning', 'Warning');

				if (firstWarning) {
					summaryList.insertBefore(li, firstWarning);
				}
				else {
					summaryList.appendChild(li);
				}
			}

			if (!summaryList.querySelector('li')) {
				summaryList.remove();
			}
		});
	}

	setHeadlineStructureStatus(element, status) {
		const headlineStructureInfo = element.querySelector('.SeoInfo-HeadlineStructure');
		const headlineStructureIcon = headlineStructureInfo.querySelector('.Icon');
		const headlineStructureTitle = headlineStructureInfo.querySelector('.Title');

		headlineStructureIcon.classList.remove('Loading');
		headlineStructureTitle.innerHTML += status.headlines.status;

		if (status.headlines.errors.length) {
			headlineStructureInfo.classList.add('SeoInfoStatus-Error');
			headlineStructureIcon.innerHTML = '<i class="las la-times"></i>';

			// Get the first error and add it to the title
			headlineStructureTitle.innerHTML += `: ${status.headlines.errors[0]}`;
		}
		else { // noinspection JSUnresolvedReference
			if (status.headlines.warnings.length) {
				headlineStructureInfo.classList.add('SeoInfoStatus-Warning');
				headlineStructureIcon.innerHTML = '<i class="las la-exclamation"></i>';

				// Get the first warning and add it to the title
				// noinspection JSUnresolvedReference
				headlineStructureTitle.innerHTML += `: ${status.headlines.warnings[0]}`;
			}
			else {
				headlineStructureInfo.classList.add('SeoInfoStatus-Ok');
				headlineStructureIcon.innerHTML = '<i class="las la-check"></i>';
			}
		}

		let titleAttribute = headlineStructureInfo.getAttribute('title');
		if (status.headlines.errors.length) {
			// Append all errors to the title attribute
			titleAttribute += `\n${status.headlines.errors.join('\n')}`;
		}
		// noinspection JSUnresolvedReference
		if (status.headlines.warnings.length) {
			// Append all warnings to the title attribute
			// noinspection JSUnresolvedReference
			titleAttribute += `\n${status.headlines.warnings.join('\n')}`;
		}
		headlineStructureInfo.setAttribute('title', titleAttribute);
	}

	setTextLengthStatus(element, status) {
		const textLengthInfo = element.querySelector('.SeoInfo-TextLength');
		const textLengthIcon = textLengthInfo.querySelector('.Icon');
		const textLengthTitle = textLengthInfo.querySelector('.Title');

		textLengthIcon.classList.remove('Loading');

		if (!status.contents) {
			return;
		}

		textLengthTitle.innerHTML += status.contents.status;

		if (status.contents.errors.length) {
			textLengthInfo.classList.add('SeoInfoStatus-Error');
			textLengthIcon.innerHTML = '<i class="las la-times"></i>';

			// Get the first error and add it to the title
			textLengthTitle.innerHTML += `: ${status.contents.errors[0]}`;
		}
		else { // noinspection JSUnresolvedReference
			if (status.contents.warnings.length) {
				textLengthInfo.classList.add('SeoInfoStatus-Warning');
				textLengthIcon.innerHTML = '<i class="las la-exclamation"></i>';

				// Get the first warning and add it to the title
				// noinspection JSUnresolvedReference
				textLengthTitle.innerHTML += `: ${status.contents.warnings[0]}`;
			}
			else {
				textLengthInfo.classList.add('SeoInfoStatus-Ok');
				textLengthIcon.innerHTML = '<i class="las la-check"></i>';
			}
		}

		let titleAttribute = textLengthInfo.getAttribute('title');
		if (status.contents.errors.length) {
			// Append all errors to the title attribute
			titleAttribute += `\n${status.contents.errors.join('\n')}`;
		}
		// noinspection JSUnresolvedReference
		if (status.contents.warnings.length) {
			// Append all warnings to the title attribute
			// noinspection JSUnresolvedReference
			titleAttribute += `\n${status.contents.warnings.join('\n')}`;
		}
		textLengthInfo.setAttribute('title', titleAttribute);

		const mostlyUsedWordsTitle = element.querySelector('.SeoInfo-MostlyUsedWords')
		if (status.contents.mostlyUsedWords) {
			const mostlyUsedWordsList = document.createElement('ul');
			mostlyUsedWordsList.classList.add('MostlyUsedWords-List');

			// Traverse mostly used words object
			for (const [word, count] of Object.entries(status.contents.mostlyUsedWords)) {
				const listItem = document.createElement('li');
				listItem.innerHTML = `${word} (${count}x)`;
				mostlyUsedWordsList.appendChild(listItem);
			}

			mostlyUsedWordsTitle.appendChild(mostlyUsedWordsList);
		}
	}
}


/**
 * Expose the class globally
 * @global
 * @type {SeoController}
 */
window.SeoController = SeoController;