// noinspection JSUnusedGlobalSymbols

/**
 * Adds an observer to the document that checks for
 * the visibility of the details tags and remembers their state
 */
export default class DetailsHandler {
	/**
	 * The details statuses, whether they are open or closed
	 * @type {{}}
	 */
	detailsStatuses = {};
	/**
	 * The observer for the crop frame.
	 * @type {Observer}
	 */
	observer = window.observer;

	constructor() {
		this.detailsStatuses = this.getDetailsStatuses();

		const detailsTags = document.querySelectorAll('details')

		// Observe changes of the "open" attribute of the details tags
		detailsTags.forEach(detailsTag => {
			this.initDetailElement(detailsTag);

			this.reopenDetailsTag(detailsTag, this.detailsStatuses);
		})

		this.observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation){
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType === Node.ELEMENT_NODE) {
				if (node.tagName === 'DETAILS') {
					this.initDetailElement(node);

					this.reopenDetailsTag(node, this.detailsStatuses);
				}

				const elements = node.querySelectorAll('details');
				elements.forEach((element) => {
					this.initDetailElement(element);

					this.reopenDetailsTag(element, this.detailsStatuses);
				});
			}
		});
	}

	/**
	 * Observe detail element changes
	 * @param {HTMLDetailsElement} detailsTag - The details element to observe
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeDetailElement(detailsTag, mutation) {
		if (mutation.type === 'attributes' && mutation.attributeName === 'open') {
			const path = this.path(detailsTag);
			this.detailsStatuses[ path ] = detailsTag.open;

			localStorage.setItem('detailsStatuses', JSON.stringify(this.detailsStatuses));
		}
	}

	/**
	 * Observes a single details element for changes to its "open" attribute
	 * @param {HTMLDetailsElement} detailsTag - The details element to observe
	 */
	initDetailElement(detailsTag) {
		this.observer.addObserver(this.observeDetailElement.bind(this, detailsTag), detailsTag, {attributes: true});
	}

	/**
	 * Get the details statuses from local storage
	 * @returns {any|{}}
	 */
	getDetailsStatuses() {
		return JSON.parse(localStorage.getItem('detailsStatuses')) || {};
	}

	/**
	 * Reopen a details tag if it was previously open
	 * @param detailsTag
	 * @param detailsStatuses
	 */
	reopenDetailsTag(detailsTag, detailsStatuses) {
		const path = this.path(detailsTag);
		if (detailsStatuses[path]) {
			detailsTag.open = true;
		}
	}

	/**
	 * Get a unique path for an element based on its id and class names
	 * @param element
	 * @returns {string|string|string}
	 */
	path(element) {
		if (!element) {
			return '';
		}

		if (element.id) {
			return `#${element.id}`;
		}

		const parts = [];

		// If the element is part of a .FormInput, use the form input
		const formInput = element.closest('.FormInput');
		if (formInput) {
			parts.push(`.${formInput.classList[0]}`);
		}

		// If the element is part of a fieldset with a class, use the class name
		const fieldset = formInput ? formInput.closest('fieldset') : element.closest('fieldset');
		if (fieldset && fieldset.classList.length) {
			parts.push(`.${fieldset.classList[0]}`);
		}

		// If there's an element with an id in the parent chain, use that.
		// Otherwise, use the class names
		let parentClasses = [];
		let currentElement = fieldset ? fieldset : (formInput ? formInput : element);
		while (currentElement.parentElement) {
			if (currentElement.parentElement.id) {
				parts.unshift(`#${currentElement.parentElement.id}`);

				// Empty the class list
				parentClasses.length = [];

				break;
			}

			if (currentElement.parentElement.classList.length) {
				parentClasses.unshift(`.${Array.from(currentElement.parentElement.classList).join('.')}`);
			}

			currentElement = currentElement.parentElement;
		}

		// Always make sure the html-tag itself is included
		if (currentElement.tagName !== 'HTML') {
			const htmlTag = document.querySelector('html');
			if (htmlTag.id) {
				parts.unshift(`#${htmlTag.id}`);
			}
			else if (htmlTag.classList.length) {
				parts.unshift(`.${Array.from(htmlTag.classList).join('.')}`);
			}
		}

		if (parentClasses.length) {
			// Prepend the parent classes to the parts array
			parts.unshift(...parentClasses);
		}

		return parts.length ? parts.join(' ') : '';
	}
}
