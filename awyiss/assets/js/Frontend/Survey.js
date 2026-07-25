// noinspection JSUnusedGlobalSymbols

export default class Survey {
	selector = 'div.Survey';

	constructor() {
		const surveys = document.querySelectorAll(this.selector);
		surveys.forEach(survey => {
			this.initSurvey(survey);
		});

		// Create a new MutationObserver instance and set its callback
		this.observer = new MutationObserver((mutationsList, observer) => {
			for (let mutation of mutationsList) {
				 this.observeMutations(mutation);
			}
		});

		this.observer.observe(document.body, {childList: true, subtree: true});
	}


	/**
	 * Init the survey by adding the necessary event listeners
	 */
	initSurvey(survey) {
		if (survey.dataset.ajax !== 'false') {
			survey.addEventListener('submit', this.handleSubmit.bind(this, survey));
		}

		survey.addEventListener('click', this.handleClick.bind(this));

		// Extract and store survey data as dataset attributes
		this.extractSurveyData(survey);
	}


	/**
	 * Extract surveyIdentifier and surveyHash from the form and store as data attributes
	 * @param {HTMLElement} survey
	 */
	extractSurveyData(survey) {
		const form = survey.querySelector('form');
		if (!form) {
			return;
		}

		// Get surveyIdentifier from hidden input
		const identifierInput = form.querySelector('input[name="_surveyIdentifier"]');
		if (identifierInput && identifierInput.value) {
			survey.dataset.surveyIdentifier = identifierInput.value;
		}

		// Get surveyHash from hidden input
		const hashInput = form.querySelector('input[name="_surveyHash"]');
		if (hashInput && hashInput.value) {
			survey.dataset.surveyHash = hashInput.value;
		}
	}


	/**
	 * Build the POST endpoint URL
	 * @param {HTMLElement} survey
	 * @returns {string|null} The complete endpoint URL or null if data is missing
	 */
	buildEndpointUrl(survey) {
		const identifier = survey.dataset.surveyIdentifier;
		const hash = survey.dataset.surveyHash;

		if (!identifier || !hash) {
			return null;
		}

		// Use global languageShortcode and baseUrl
		const langCode = languageShortcode || 'de';
		const base = baseUrl || '/';

		return `${base}${langCode}/_survey/${identifier}/${hash}/`;
	}


	/**
	 * Handle the survey submission
	 * @param {HTMLElement} survey
	 * @param {SubmitEvent} event
	 */
	handleSubmit(survey, event) {
		event.preventDefault();

		survey.classList.add('FetchInProgress');

		const form = event.target;
		const formData = new FormData(form);

		// Add the submitter
		const submitter = event.submitter;
		if (submitter) {
			formData.append(submitter.name, submitter.value);
		}

		const surveyId = survey.getAttribute('id');

		// Try to build the endpoint URL from stored data
		const endpointUrl = this.buildEndpointUrl(survey);
		const fetchUrl = endpointUrl || form.action;

		fetch(fetchUrl, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
			redirect: 'follow',
		})
		.then(response => {
			// If the response was redirected, redirect the user
			if (response.redirected) {
				let url = response.url

				// If the url does not contain a hash, append the survey ID
				if (!url.includes('#')) {
					url += `#${surveyId}`;
				}

				// Redirect the user to the new URL
				window.location.href = url;

				// Prevent the next .then from running
				return Promise.reject('Redirected');
			}

			return response.text();
		})
		.then(html => {
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			// Find the new survey element by its ID
			const newSurvey = doc.querySelector(`#${surveyId}`);

			// Empty the current survey element
			survey.innerHTML = '';

			// Append all children from the new survey
			while (newSurvey.firstChild) {
				survey.appendChild(newSurvey.firstChild);
			}

			survey.classList.remove('FetchInProgress');

			// Update lazy loading if it exists
			if (window.lazyLoad && typeof window.lazyLoad.update === 'function') {
				window.lazyLoad.update();
			}
		})
		.catch(error => console.error('Error:', error));
	}


	/**
	 * Handle click events on the survey
	 * @param {MouseEvent} event
	 */
	handleClick(event) {
		if (event.target.closest('.SurveyAnswer-Custom')) {
			const answer = event.target.closest('.SurveyAnswer');
			if (answer.querySelector('input').checked) {
				answer.querySelector('.SurveyAnswer-CustomInput').focus();
			}
		}
	}


	/**
	 * Observe mutations and call the appropriate methods
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		if (mutation.addedNodes.length === 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initSurvey(node);
			}

			const surveys = node.querySelectorAll(this.selector);
			surveys.forEach((survey) => {
				this.initSurvey(survey);
			});
		});
	}
}
