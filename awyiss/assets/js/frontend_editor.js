class FrontendEditor {
	/**
	 * The active element ID
	 * @type {string}
	 */
	activeElementId
	/**
	 * The type of the active element
	 * @type {string}
	 */
	activeType = null;
	/**
	 * The link element to the backend mask resp the overlay form
	 * @type {HTMLElement}
	 */
	editLink;
	/**
	 * The hightlight element for the active element
	 * @type {HTMLElement}
	 */
	highlightElement;
	/**
	 * The iframe element for the overlay
	 * @type {HTMLIFrameElement}
	 */
	iframe
	/**
	 * The overlay element
	 * @type {HTMLElement}
	 */
	overlay;
	/**
	 * Selector config vor editor-aware elements
	 * @type {{contents: {selector: string, uri: string, enabled: boolean}, formElements: {selector: string, uri: string, enabled: boolean}, menuEntries: {selector: string, uri: string, enabled: boolean}, widgets: {selector: string, uri: string, enabled: boolean}}}
	 */
	selectorConfig = {
		contents: {
			enabled: false,
			overlayForm: true,
			selector: '.ContentElement[id^="Content"]',
			uri: 'contents/edit/id:'
		},
		formElements: {
			enabled: false,
			overlayForm: true,
			selector: '.FormElement[id^="Form"]',
			uri: 'form-elements/edit/id:'
		},
		menuEntries: {
			enabled: false,
			overlayForm: false,
			selector: 'ul.Level1[class*="Menu-"]',
			uri: 'menu-entries/overview/menu-identifier:'
		},
		widgets: {
			enabled: false,
			overlayForm: true,
			selector: '.WidgetElement[id^="Widget"]',
			uri: 'widgets/edit/id:'
		},
	};

	/**
	 * @param {Object} selectorConfig
	 */
	constructor(selectorConfig) {
		// Iterate over the keys of the user-provided configuration
		Object.keys(selectorConfig).forEach(key => {
			// Check if the property is an object and exists in both configurations
			if (typeof selectorConfig[key] === 'object' && this.selectorConfig[key]) {
				// Perform a shallow merge for this property
				this.selectorConfig[key] = {
					...this.selectorConfig[key],
					...selectorConfig[key]
				};
			}
			else {
				// Directly assign/update the property
				this.selectorConfig[key] = selectorConfig[key];
			}
		});

		this.createEditLink();
		this.createHighlightElement();
		this.createOverlay();
		this.addEventListeners();
	}

	/**
	 * Create the edit link element
	 *
	 * @returns {void}
	 */
	createEditLink() {
		this.editLink = document.createElement('a');
		this.editLink.id = 'AwyissEditLink';
		this.editLink.innerHTML = 'Edit';
		this.editLink.addEventListener('click', (event) => {
			if (!this.selectorConfig[this.activeType].overlayForm) {
				return;
			}

			event.preventDefault();

			this.showOverlay(this.editLink.href);
		});

		this.editLink.addEventListener('mouseenter', () => {
			// Prevent the highlight element from disappearing
			this.highlightElement.classList.add('Visible');
			this.editLink.classList.add('Visible');
		})

		document.body.appendChild(this.editLink);
	}

	/**
	 * Highlight element for the active element
	 *
	 * @returns {void}
	 */
	createHighlightElement() {
		// Create a new element for highlighting
		this.highlightElement = document.createElement('div');
		this.highlightElement.id = 'AwyissFrontendEditorHighlight';
		document.body.appendChild(this.highlightElement);
	}

	/**
	 * Create the overlay element
	 *
	 * @returns {void}
	 */
	createOverlay() {
		this.overlay = document.createElement('div');
		this.overlay.id = 'AwyissFrontendEditor';

		this.overlay.inner = document.createElement('div');
		this.overlay.inner.classList.add('Inner');
		this.overlay.appendChild(this.overlay.inner);

		this.iframe = document.createElement('iframe');
		this.overlay.inner.appendChild(this.iframe);

		document.body.appendChild(this.overlay);

		// Listen for a close request from the iframe
		window.addEventListener('message', (event) => {
			if (event.data === 'closeFrontendEditor') {
				this.hideOverlay();
			}

			if (event.data === 'closeFrontendEditorAndFetch') {
				this.hideOverlay();
				this.handleSuccessMessage();
			}
		});

	}

	/**
	 * Add the event listeners to all elements matching the selectors in the config
	 *
	 * @returns {void}
	 */
	addEventListeners() {
		Object.entries(this.selectorConfig).forEach(([key, config]) => {
			if (!config.enabled) {
				return;
			}

			document.querySelectorAll(config.selector).forEach(element => {
				// Check if the element already has the event listeners set
				if (!element.eventsAdded) {
					element.addEventListener('mouseenter', (event) => this.onMouseEnter(event, key));
					element.addEventListener('mouseleave', (event) => this.onMouseLeave(event));
					element.addEventListener('dblclick', () => this.editLink.dispatchEvent(new MouseEvent('click', {
						bubbles: true,
						cancelable: true,
					})));

					// Mark the element as having the events set
					element.eventsAdded = 'true';
				}
			});
		});
	}

	/**
	 * Handle the mouse enter event for the element
	 *
	 * @param {MouseEvent} event
	 * @param {string} type
	 * @returns {void}
	 */
	onMouseEnter(event, type) {
		const element = event.currentTarget;

		this.activeType = type;
		this.activeElementId = element.id;

		const rect = element.getBoundingClientRect();
		let size = {
			width: rect.width + 20,
			height: rect.height + 20,
			top: rect.top + window.scrollY - 10,
			right: rect.right + window.scrollX + 5,
			left: rect.left + window.scrollX - 10
		};

		// Check if the element is overflowing the viewport, if so, adjust the size and position
		if (size.width >= document.documentElement.clientWidth ) {
			size.width -= 40;
			size.right -= 20;
			size.left += 20;
		}

		if (size.top < 0) {
			size.top = 10;
		}

		// Set the size and position of the highlight element
		this.highlightElement.style.width = `${size.width}px`;
		this.highlightElement.style.height = `${size.height}px`;
		this.highlightElement.style.top = `${size.top}px`;
		this.highlightElement.style.left = `${size.left}px`;
		this.highlightElement.classList.add('Visible');

		// Set the position of the edit link
		this.editLink.style.top = `${size.top + 5}px`;
		this.editLink.style.right = `${document.documentElement.clientWidth - size.right}px`;
		this.editLink.classList.add('Visible');

		// Replace non-numeric characters in the ID
		let id = element.id.replace(/\D/g, '');

		if (type === 'menuEntries') {
			// Get the class that contains the menu identifier
			const menuClass = element.className.match(/Menu-[a-zA-Z0-9]+/);
			id = menuClass ? menuClass[0].replace('Menu-', '') : null;

			// Convert the camelCase id to kebab-case
			id = id.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
		}

		this.editLink.href = `${baseUrl}backend/${languageShortcode}/${this.selectorConfig[type].uri}${id}/`;
	}

	/**
	 * Handle the mouse leave event for the element
	 *
	 * @param {MouseEvent} event
	 * @returns {void}
	 */
	onMouseLeave(event) {
		const relatedTarget = event.relatedTarget;
		const parentSelector = this.selectorConfig[this.activeType]?.selector;

		// Check if the related target is a parent or relevant element
		if (relatedTarget && (relatedTarget.matches(parentSelector) || relatedTarget.closest(parentSelector))) {
			const parentElement = relatedTarget.matches(parentSelector) ? relatedTarget : relatedTarget.closest(parentSelector);
			// Manually trigger onMouseEnter for the parent element
			this.onMouseEnter({currentTarget: parentElement}, this.activeType);
		}
		else {
			// Proceed with usual onMouseLeave logic
			this.highlightElement.classList.remove('Visible');
			this.editLink.classList.remove('Visible');
		}
	}

	/**
	 * Show the frontend editor overlay for a given backend url
	 *
	 * @param {string} url
	 * @returns {void}
	 */
	showOverlay(url) {
		// Hide the edit link and highlight element
		this.editLink.classList.remove('Visible');
		this.highlightElement.classList.remove('Visible');

		this.iframe.contentWindow.location.replace(url + 'mode:frontend-editor/');
		this.overlay.classList.add('Visible');
	}

	/**
	 * Hide the overlay
	 *
	 * @returns {void}
	 */
	hideOverlay() {
		this.overlay.classList.remove('Visible');

		// Remove the iframe and create a new one
		this.iframe.remove();
		this.iframe = document.createElement('iframe');
		this.overlay.inner.appendChild(this.iframe);
	}

	/**
	 * Handle the success message from the iframe
	 * Fetch the current page and replace the active element with the updated version
	 *
	 * @returns {void}
	 */
	handleSuccessMessage() {
		fetch(window.location.href)
		.then(response => response.text())
		.then(html => {
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');
			const newElement = doc.querySelector(`#${this.activeElementId}`);

			if (newElement) {
				const oldElement = document.querySelector(`#${this.activeElementId}`);
				if (oldElement) {
					oldElement.replaceWith(newElement);
				}

				// Re-add the event listeners
				this.addEventListeners();

				// If lazyload exists in the window object, force a reload
				if (window.lazyLoad) {
					setTimeout(() => window.lazyLoad.update(), 300);
				}
			}
		});
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		window.frontendEditor = new FrontendEditor(frontendEditorConfig)
	});
}
else {
	window.frontendEditor = new FrontendEditor(frontendEditorConfig);
}


/**
 * Expose the class globally
 * @global
 * @type {FrontendEditor}
 */
window.FrontendEditor = FrontendEditor;