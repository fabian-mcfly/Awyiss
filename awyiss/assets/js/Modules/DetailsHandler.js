/**
 * Adds an observer to the document that checks for
 * the visibility of the details tags and remembers their state
 */
export default class DetailsHandler {
	detailsStatuses = {};

	constructor() {
		this.detailsStatuses = this.getDetailsStatuses();

		const detailsTags = document.querySelectorAll('details')

		// Observe changes of the "open" attribute of the details tags
		detailsTags.forEach(detailsTag => {
			this.observe(detailsTag);

			this.reopenDetailsTag(detailsTag, this.detailsStatuses);
		})

		window.observer.addObserver((mutation) => {
			if (!mutation.addedNodes.length > 0) {
				return;
			}

			mutation.addedNodes.forEach((node) => {
				if (node.nodeType === Node.ELEMENT_NODE) {
					if (node.tagName === 'DETAILS') {
						this.observe(node);

						this.reopenDetailsTag(node, this.detailsStatuses);
					}

					const elements = node.querySelectorAll('details');
					elements.forEach((element) => {
						this.observe(element);

						this.reopenDetailsTag(element, this.detailsStatuses);
					});
				}
			});
		});
	}

	observe(detailsTag) {
		const observer = new MutationObserver((mutationsList) => {
			mutationsList.forEach(mutation => {
				if (mutation.type === 'attributes' && mutation.attributeName === 'open') {
					const path = this.path(detailsTag);
					this.detailsStatuses[path] = detailsTag.open;

					localStorage.setItem('detailsStatuses', JSON.stringify(this.detailsStatuses));
				}
			});
		});

		observer.observe(detailsTag, {attributes: true});
	}

	getDetailsStatuses() {
		return JSON.parse(localStorage.getItem('detailsStatuses')) || {};
	}

	reopenDetailsTag(detailsTag, detailsStatuses) {
		const path = this.path(detailsTag);
		if (detailsStatuses[path]) {
			detailsTag.open = true;
		}
	}

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
