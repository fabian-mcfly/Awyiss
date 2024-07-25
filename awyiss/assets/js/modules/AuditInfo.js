//noinspection JSUnusedGlobalSymbols

/**
 * Class to handle the loading of audit information for a given element.
 */
export default class AuditInfo {
	/**
	 * The selector for the elements that will have audit information loaded.
	 * @type {string}
	 */
	elementSelector = '.AuditInfo';
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The timeout in milliseconds before the audit information is loaded.
	 * @type {number}
	 */
	mouseOverTimeout = 1000;
	/**
	 * The set of elements that have been processed.
	 * @type {Set}
	 */
	processedElements = new Set();

	/**
	 * Creates a new instance of the AuditInfo class.
	 * @param {string} selector
	 */
	constructor(selector) {
		if (selector) {
			this.elementSelector = selector;
		}

		const elements = document.querySelectorAll(this.elementSelector);
		this.bindEvents(elements);
	}

	/**
	 * Binds the 'mouseenter' and 'mouseleave' events to the elements with the given selector.
	 * @param {NodeList|HTMLElement} elements
	 */
	bindEvents(elements) {
		if (elements instanceof HTMLElement) {
			// noinspection JSValidateTypes
			elements = [elements];
		}

		elements.forEach(element => {
			this.eventHandler.add('mouseenter', this.handleMouseOver.bind(this), element, true);
			this.eventHandler.add('mouseleave', this.handleMouseOut.bind(this), element, true);
		});
	}

	/**
	 * Handles the 'mouseover' event on elements with the class 'AuditInfo'.
	 *
	 * When the mouse pointer enters the area of an 'AuditInfo' element, this method sets a timeout to load the audit information for that element after 1 second.
	 * If the mouse pointer leaves the area of the 'AuditInfo' element before the timeout expires, the timeout is cleared and the audit information is not loaded.
	 *
	 * @param {Event} event - The 'mouseover' event.
	 */
	handleMouseOver(event) {
		const element = event.target.closest(this.elementSelector);

		if (!element) {
			return;
		}

		element.timeoutId = setTimeout(() => this.loadAuditInfo(element), this.mouseOverTimeout);
	}

	/**
	 * Handles the 'mouseout' event on elements with the class 'AuditInfo'.
	 *
	 * When the mouse pointer leaves the area of an 'AuditInfo' element, this method clears the timeout set by the 'handleMouseOver' method.
	 * This prevents the audit information from being loaded if the mouse pointer leaves the area of the 'AuditInfo' element before the timeout expires.
	 *
	 * @param {Event} event - The 'mouseover' event.
	 */
	handleMouseOut(event) {
		const element = event.target.closest(this.elementSelector);

		if (!element || !element.timeoutId) {
			return;
		}

		clearTimeout(element.timeoutId);
	}

	/**
	 * Loads the audit information for the given element.
	 *
	 * @param {HTMLElement} element
	 */
	loadAuditInfo(element) {
		if (this.processedElements.has(element)) {
			return;
		}

		this.processedElements.add(element);

		//noinspection CssInvalidHtmlTagReference
		const auditInfoList = element.querySelector(`${this.elementSelector}List`);
		const loader = document.createElement('li');
		loader.classList.add('Loader');
		auditInfoList.appendChild(loader);

		let scope = element.dataset.scope;
		if (!scope) {
			const baseHtmlTag = document.querySelector('html');
			const controllerClass = Array.from(baseHtmlTag.classList).find(cls => cls.endsWith('Controller'));

			scope = controllerClass.replace('Controller', '');
			// Convert the scope to kebap-case
			scope = scope.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
		}
		const id = element.dataset.id;

		fetch(`/backend/${languageShortcode}/audit/info/scope:${scope}/id:${id}/`, {
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
		.then(response => response.json())
		.then(data => {
			loader.remove();

			const keys = ['created', 'changed'];
			keys.forEach(key => {
				const value = data[key];
				const user = data[`${key}By`];
				const time = data[`${key}On`];

				if (time) {
					const listItem = document.createElement('li');
					listItem.innerHTML = `<strong>${value}</strong><br>${user} (${time})`;
					auditInfoList.appendChild(listItem);
				}
			});

			element.classList.add('NoDelay');

			// Give the parent the size of the child
			element.style.setProperty('--infoWidth', `${auditInfoList.offsetWidth}px`);
			element.style.setProperty('--infoHeight', `${auditInfoList.offsetHeight}px`);

			setTimeout(() => {
				element.classList.remove('NoDelay');
			}, 500);
		});
	}
}