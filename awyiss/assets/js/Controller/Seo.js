//noinspection JSUnusedGlobalSymbols

export default class SeoController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (document.documentElement.classList.contains('AnalyzeAction')) {
			this.initAnalyze();
		}
	}

	/**
	 * Initialize the SEO analyze page
	 */
	initAnalyze() {
		// Fetch the headline status for all pages
		fetch(`/backend/${languageShortcode}/seo/analyze-headline-structures/`, {
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

				const headlineStructureInfo = element.querySelector('.SeoInfo-HeadlineStructure');
				const icon = headlineStructureInfo.querySelector('.Icon');
				const title = headlineStructureInfo.querySelector('.Title');

				icon.classList.remove('Loading');
				title.innerHTML += status.status;

				if (status.errors.length) {
					headlineStructureInfo.classList.add('SeoInfoStatus-Error');
					icon.innerHTML = '<i class="las la-times"></i>';

					// Get the first error and add it to the title
					title.innerHTML += `: ${status.errors[0]}`;
				}
				else { // noinspection JSUnresolvedReference
					if (status.warnings.length) {
						headlineStructureInfo.classList.add('SeoInfoStatus-Warning');
						icon.innerHTML = '<i class="las la-exclamation"></i>';

						// Get the first warning and add it to the title
						// noinspection JSUnresolvedReference
						title.innerHTML += `: ${status.warnings[0]}`;
					}
					else {
						headlineStructureInfo.classList.add('SeoInfoStatus-Ok');
						icon.innerHTML = '<i class="las la-check"></i>';
					}
				}

				let titleAttribute = headlineStructureInfo.getAttribute('title');
				if (status.errors.length) {
					// Append all errors to the title attribute
					titleAttribute += `\n${status.errors.join('\n')}`;
				}
				// noinspection JSUnresolvedReference
				if (status.warnings.length) {
					// Append all warnings to the title attribute
					// noinspection JSUnresolvedReference
					titleAttribute += `\n${status.warnings.join('\n')}`;
				}
				headlineStructureInfo.setAttribute('title', titleAttribute);
			}

			const summaryList = document.querySelector('.Summary-List');
			// noinspection JSDeprecatedSymbols
			if (data.summary.errors) {
				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.errors;
				li.classList.add('HeadlineStructure-Error', 'Error');

				if (summaryList.querySelector('li')) {
					summaryList.insertBefore(li, summaryList.querySelector('li'));
				}
				else {
					summaryList.appendChild(li);
				}
			}

			// noinspection JSDeprecatedSymbols,JSUnresolvedReference
			if (data.summary.warnings) {
				const firstWarning = summaryList.querySelector('li.Warning');

				const li = document.createElement('li');
				// noinspection JSDeprecatedSymbols
				li.innerHTML = data.summary.warnings;
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
}


/**
 * Expose the class globally
 * @global
 * @type {SeoController}
 */
window.SeoController = SeoController;