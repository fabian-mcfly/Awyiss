// noinspection DuplicatedCode

/**
 * Awyiss Lightbox
 *
 * A simple lightbox script that can display images, videos, iframes, inline elements and AJAX requests.
 *
 * Usage:
 *
 * Import the script
 *
 * ```js
 * 	import Lightbox from 'Lightbox';
 * ```
 *
 * Create a new instance, passing the settings you want to use globally
 *
 * ```js
 * 	window.lightbox = new Lightbox({
 * 		baseUrl: baseUrl,
 * 		currentUrl: currentUrl,
 * 	});
 * ```
 *
 * @copyright Awyiss 2024
 * @license MIT
 * @version 1.0.0
 */
export default class Lightbox {
	/**
	 * The base URL to prepend to relative URLs.
	 * @var {string} baseUrl
	 */
	baseUrl = null;
	/**
	 * Whether to bind the default click event to the elements.
	 * @type {boolean} bindDefaultClick
	 */
	bindDefaultClick = true;
	/**
	 * The index of the currently displayed element.
	 * @type {number} currentIndex
	 */
	currentIndex = 0;
	/**
	 * The currently displayed elements.
	 * @type {array} currentElements
	 */
	currentElements = [];
	/**
	 * The currently preloaded element.
	 * @type {null}
	 */
	currentPreload = null;
	/**
	 * The current URL.
	 * It is used to detect inline elements and AJAX requests.
	 * @var {string} currentUrl
	 */
	currentUrl = null;
	/**
	 * The default settings for the lightbox.
	 * @type {object} defaultSettings
	 */
	defaultSettings = {
		autoplay: false,
		autoplayInterval: 10000,
		captionAttribute: false,
		counterFormat: '{current} / {total}',
		hrefAttribute: false,
		i18n: {
			'de': {
				'close': 'Schließen',
				'previous': 'Zurück',
				'next': 'Weiter',
				'zoomIn': 'Vergrößern',
				'zoomOut': 'Verkleinern',
			},
			'en': {
				'close': 'Close',
				'previous': 'Previous',
				'next': 'Next',
				'zoomIn': 'Zoom In',
				'zoomOut': 'Zoom Out',
			},
			'es': {
				'close': 'Cerrar',
				'previous': 'Anterior',
				'next': 'Siguiente',
				'zoomIn': 'Acercar',
				'zoomOut': 'Alejar',
			},
			'fr': {
				'close': 'Fermer',
				'previous': 'Précédent',
				'next': 'Suivant',
				'zoomIn': 'Agrandir',
				'zoomOut': 'Réduire',
			},
			'it': {
				'close': 'Chiudere',
				'previous': 'Precedente',
				'next': 'Successivo',
				'zoomIn': 'Ingrandire',
				'zoomOut': 'Ridurre',
			},
			'ru': {
				'close': 'Закрыть',
				'previous': 'Назад',
				'next': 'Вперёд',
				'zoomIn': 'Увеличить',
				'zoomOut': 'Уменьшить',
			}

		},
		index: 0,
		loaderIcon: 'assets/img/lightbox/loader.svg',
		loop: true,
		panzoom: {
			animate: true,
			maxScale: 5,
			minScale: 1,
			step: .25,
			panOnlyWhenZoomed: false,
			transition: false,
		},
		showArrows: true,
		showCounter: false,
		showPagination: true,
	};
	/**
	 * The DOM nodes for the lightbox.
	 * @type {Object}
	 * @property {HTMLElement} [lightbox] - The main lightbox container
	 * @property {HTMLElement} [stage] - The area where content is displayed
	 * @property {Object} [buttons] - Container for control buttons
	 * @property {HTMLButtonElement} [buttons.close] - Button to close the lightbox
	 * @property {HTMLButtonElement} [buttons.zoomIn] - Button to zoom in
	 * @property {HTMLButtonElement} [buttons.zoomOut] - Button to zoom out
	 * @property {HTMLElement} [loader] - Loading indicator element
	 * @property {HTMLElement} [caption] - Caption display area
	 * @property {HTMLElement} [arrows] - Container for navigation arrows
	 * @property {HTMLButtonElement} [arrows.arrowLeft] - Left navigation arrow button
	 * @property {HTMLButtonElement} [arrows.arrowRight] - Right navigation arrow button
	 * @property {HTMLUListElement} [pagination] - List of pagination items
	 * @property {HTMLElement} [counter] - Element showing current position
	 */
	domNodes = {};
	/**
	 * The language to use for the lightbox.
	 * @type {string}
	 */
	language = 'en';
	/**
	 * The Panzoom instance.
	 * @type {panzoom} panzoom
	 */
	panzoom = null;
	/**
	 * The settings for the lightbox.
	 * @type {object}
	 */
	settings = {};
	/**
	 * The element that opened the lightbox.
	 * @type {HTMLElement}
	 */
	sourceElement = null;
	/**
	 * Whether to change the URL when the lightbox is opened.
	 * @type {boolean}
	 */
	urlChange = true;

	/**
	 * @param {object} settings
	 */
	constructor(settings) {
		for (const key in settings) {
			if (settings.hasOwnProperty(key)) {
				this[key] = settings[key];
			}
		}

		if (!this.defaultSettings.loaderIcon.startsWith(this.baseUrl)) {
			this.defaultSettings.loaderIcon = this.baseUrl + this.defaultSettings.loaderIcon;
		}

		if (this.bindDefaultClick) {
			this.bindDefaultClicks();
		}

		// Observe changes to the url
		window.addEventListener('hashchange', this.handleHashChange.bind(this));
		// Trigger an initial hashchange event to handle the current URL
		window.dispatchEvent(new HashChangeEvent('hashchange'));
	}

	/**
	 * Bind the default click event to the elements.
	 * This will open the lightbox when an element is clicked.
	 */
	bindDefaultClicks() {
		document.querySelectorAll('a[rel*="lightbox"]').forEach(element => {
			element.addEventListener('click', event => {
				event.preventDefault();

				const settings = {};
				let group = [];

				let rel = element.rel;
				if (rel !== 'lightbox') {
					// Find the part of the rel attribute that contains 'lightbox'
					rel = element.getAttribute('rel').split(' ').find(value => value.startsWith('lightbox'));
				}

				if (rel !== 'lightbox') {
					group = Array.from(document.querySelectorAll(`[rel*="${rel}"]`));
					group.forEach((item, index) => {
						if (item === element) {
							settings.index = index;
						}
					});
				}

				// Get all data attributes that start with 'data-lightbox-'
				const dataAttributes = Array.from(element.attributes).filter(attr => attr.name.startsWith('data-lightbox-'));
				dataAttributes.forEach(attr => {
					const key = attr.name.replace('data-lightbox-', '');
					const value = attr.value;

					// If the value is a boolean, convert it to a boolean
					if (value === 'true') {
						settings[key] = true;
					}
					else if (value === 'false') {
						settings[key] = false;
					}
					else {
						settings[key] = value;
					}
				});

				this.sourceElement = element;
				this.init(element, settings, group);
			});
		});
	}

	/**
	 * Initialize the lightbox for the given element,
	 * using the settings and the group of elements.
	 *
	 * @param {HTMLElement} element
	 * @param {object} settings
	 * @param {array} group
	 */
	init(element, settings, group) {
		// Merge the provided settings with the default settings
		this.settings = {...this.defaultSettings, ...settings};

		if (this.settings.panzoom) {
			this.settings.panzoom = {...this.defaultSettings.panzoom, ...this.settings.panzoom};
		}

		// If the group is empty, use the element as the group
		if (group.length === 0) {
			group = [element];
		}
		// Prepare the elements
		this.currentElements = group.map(element => this.prepareElement(element));
		// Filter out null values
		this.currentElements = this.currentElements.filter(element => element !== null);

		this.createLightbox();

		this.buildPagination();

		this.open();

		this.showItem(this.settings.index);
	}

	/**
	 * Create a lightbox item from the given element.
	 *
	 * @param {HTMLElement|object} sourceElement
	 * @returns {object}
	 */
	prepareElement(sourceElement) {
		let element = {};

		// Dispatch a custom event to allow for customizing the element
		const beforeEvent = new CustomEvent('beforeLightboxElementPrepare', {detail: this, element: element, sourceElement: sourceElement});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't create the element
		if (beforeEvent.defaultPrevented) {
			return null
		}

		// If the source element is not an object or an HTMLElement, it cannot be used
		if (typeof sourceElement !== 'object') {
			console.error('Invalid element. Expected an object or HTMLElement, got', typeof sourceElement);
			return null;
		}

		// If the source element is an HTMLElement, extract the necessary data
		if (sourceElement instanceof HTMLElement) {
			element.href = this.settings.hrefAttribute ? sourceElement.getAttribute(this.settings.hrefAttribute) : sourceElement.href;
			element.caption = this.settings.captionAttribute ? sourceElement.getAttribute(this.settings.captionAttribute) : null;
			element.sourceElement = sourceElement;
		}
		// If the source element is an object, use the data as is
		else {
			element = sourceElement;

			element.caption ??= null;
		}

		// If the element is missing a href, it cannot be displayed
		if (!element.href) {
			console.error('Missing `href`-key for element', element);
			return null;
		}

		// If the href is a relative URL, prepend the base URL
		if (!element.href.includes('://')) {
			element.href = this.baseUrl + element.href.replace(/^\/+/, '');
		}

		// If the element is missing a type, detect it
		if (!element.type) {
			element.type = this.detectType(element);
		}

		// Dispatch a custom event to allow for customizing the element
		const afterEvent = new CustomEvent('afterLightboxElementPrepare', {detail: this, element: element, sourceElement: sourceElement});
		document.dispatchEvent(afterEvent);

		return element;
	}

	/**
	 * Detect the type of the given element.
	 *
	 * @param {object} element
	 * @returns {string}
	 */
	detectType(element) {
		const url = new URL(element.href);

		// If the URL has a hash, it's an inline element or an AJAX request
		if (url.hash.length > 0) {
			const currentUrl = new URL(this.currentUrl);

			element.selector = url.hash;

			// If the URL is the same as the current URL, the target is an element already on the page
			if (url.hostname === currentUrl.hostname && url.pathname === currentUrl.pathname) {
				return 'inline';
			}

			return 'ajax';
		}

		if (url.pathname.match(/\.(avif|gif|jpe?g|png|svg|webp)$/i)) {
			return 'image';
		}

		if (url.pathname.match(/\.mp4$/i)) {
			return 'video';
		}

		// While we're at it, let's also check for YouTube and Vimeo URLs
		if (url.hostname.includes('vimeo.com')) {
			let videoId = url.pathname;

			if (videoId.startsWith('/')) {
				videoId = videoId.slice(1);
				const slashIndex = videoId.indexOf('/');
				if (slashIndex > -1) {
					videoId = videoId.slice(0, slashIndex);
				}
			}

			element.href = `//player.vimeo.com/video/${videoId}${helper.search}`;
		}
		else if (url.hostname.includes('youtube.com') || url.hostname.includes('youtu.be') || url.hostname.includes('youtube-nocookie.com')) {
			element.href = `//www.youtube-nocookie.com/embed/${url.search.replace('?v=', '')}`;
		}

		return 'iframe';
	}

	/**
	 * Create the lightbox elements.
	 */
	createLightbox() {
		// If the lightbox already exists, don't create it and all other elements again
		if (this.domNodes.lightbox) {
			return;
		}

		// Dispatch a custom event to allow for customizing the lightbox
		const beforeEvent = new CustomEvent('beforeLightboxCreate', {detail: this});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't create the lightbox
		if (beforeEvent.defaultPrevented) {
			return;
		}

		this.domNodes.lightbox = this.createElement('dialog', {id: 'Lightbox'}, document.body);
		this.domNodes.lightbox.addEventListener('click', this.handleClick.bind(this));
		document.addEventListener('keydown', this.handleKeyDown.bind(this));

		this.domNodes.stage = this.createElement('div', {class: 'Lightbox-Stage'}, this.domNodes.lightbox);
		this.domNodes.stage.addEventListener('wheel', event => {
			if (this.panzoom) {
				this.panzoom.zoomWithWheel(event);
			}
		});

		this.domNodes.buttons = this.createElement('div', {class: 'Lightbox-Buttons'}, this.domNodes.lightbox);
		// Create the buttons to close and zoom in/out
		this.domNodes.buttons.close = this.createElement('button', {class: 'Lightbox-Close'}, this.domNodes.buttons);
		this.domNodes.buttons.close.setAttribute('title', this.settings.i18n[this.language].close);
		this.domNodes.buttons.close.textContent = this.settings.i18n[this.language].close;

		this.domNodes.buttons.zoomIn = this.createElement('button', {class: 'Lightbox-Zoom Lightbox-Zoom-In Disabled', inert: true}, this.domNodes.buttons);
		this.domNodes.buttons.zoomIn.addEventListener('click', () => this.panzoom?.zoomIn());
		this.domNodes.buttons.zoomIn.setAttribute('title', this.settings.i18n[this.language].zoomIn);
		this.domNodes.buttons.zoomIn.textContent = this.settings.i18n[this.language].zoomIn;

		this.domNodes.buttons.zoomOut = this.createElement('button', {class: 'Lightbox-Zoom Lightbox-Zoom-Out Disabled', inert: true}, this.domNodes.buttons);
		this.domNodes.buttons.zoomOut.addEventListener('click', () => this.panzoom?.zoomOut());
		this.domNodes.buttons.zoomOut.setAttribute('title', this.settings.i18n[this.language].zoomOut);
		this.domNodes.buttons.zoomOut.textContent = this.settings.i18n[this.language].zoomOut;

		this.domNodes.loader = this.createElement('div', {class: 'Lightbox-Loader'}, this.domNodes.lightbox);
		if (this.settings.loaderIcon) {
			this.createElement('img', {src: this.settings.loaderIcon}, this.domNodes.loader);
		}

		this.domNodes.caption = this.createElement('div', {class: 'Lightbox-Caption'}, this.domNodes.lightbox);

		this.domNodes.arrows = this.createElement('div', {class: 'Lightbox-Arrows'}, this.domNodes.lightbox);
		this.domNodes.arrows.arrowLeft = this.createElement('button', {class: 'Lightbox-Arrow Lightbox-Arrow-Left', 'aria-hidden': true}, this.domNodes.arrows);
		this.domNodes.arrows.arrowLeft.setAttribute('title', this.settings.i18n[this.language].previous);
		this.domNodes.arrows.arrowLeft.textContent = this.settings.i18n[this.language].previous;

		this.domNodes.arrows.arrowRight = this.createElement('button', {class: 'Lightbox-Arrow Lightbox-Arrow-Right', 'aria-hidden': true}, this.domNodes.arrows);
		this.domNodes.arrows.arrowRight.setAttribute('title', this.settings.i18n[this.language].next);
		this.domNodes.arrows.arrowRight.textContent = this.settings.i18n[this.language].next;

		this.domNodes.pagination = this.createElement('ul', {class: 'Lightbox-Pagination'}, this.domNodes.lightbox);

		this.domNodes.counter = this.createElement('div', {class: 'Lightbox-Counter'}, this.domNodes.lightbox);

		// Dispatch a custom event to allow for customizing the lightbox
		const afterEvent = new CustomEvent('afterLightboxCreate', {detail: this});
		document.dispatchEvent(afterEvent);
	}

	/**
	 * Helper function to create an element with the given tag, attributes and parent.
	 *
	 * @param {string} tag
	 * @param {object} attributes
	 * @param {HTMLElement} parent
	 * @returns {HTMLElement}
	 */
	createElement(tag, attributes, parent) {
		const element = document.createElement(tag);

		for (const key in attributes) {
			element.setAttribute(key, attributes[key]);
		}

		if (parent) {
			parent.appendChild(element);
		}

		return element;
	}

	/**
	 * Open the lightbox.
	 * If it doesn't exist yet, create it.
	 */
	open() {
		// Dispatch a custom event to allow for customizing the lightbox
		const beforeEvent = new CustomEvent('beforeLightboxOpen', {detail: this});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't open the lightbox
		if (beforeEvent.defaultPrevented) {
			return;
		}

		this.domNodes.arrows.classList.toggle('Visible', this.settings.showArrows && this.currentElements.length > 1);
		this.domNodes.counter.classList.toggle('Visible', this.settings.showCounter);
		this.domNodes.pagination.classList.toggle('Visible', this.settings.showPagination && this.currentElements.length > 1);

		document.documentElement.classList.add('Lightbox-Opened');

		requestAnimationFrame(() => {
			setTimeout(() => {
				this.domNodes.lightbox.showModal();
			}, 50); // Adjust the delay as needed
		});

		// Dispatch a custom event to allow for customizing the lightbox
		const afterEvent = new CustomEvent('afterLightboxOpen', {detail: this});
		document.dispatchEvent(afterEvent);

		// If the event wasn't canceled and the settings allow it, change the URL
		if (!afterEvent.defaultPrevented && this.urlChange) {
			// Use the xpath of the source element
			const sourceElementPath = this.getXPath(this.sourceElement);

			if (window.location.hash !== '#Lightbox') {
				window.history.pushState({
					sourceElementPath: sourceElementPath,
				}, '', `${this.currentUrl}#Lightbox`);
			}
			else {
				window.history.replaceState({
					sourceElementPath: sourceElementPath,
				}, '', `${this.currentUrl}#Lightbox`);
			}
		}
	}

	/**
	 * Close the lightbox.
	 */
	close() {
		// Dispatch a custom event to allow for customizing the lightbox
		const beforeEvent = new CustomEvent('beforeLightboxClose', {detail: this});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't close the lightbox
		if (beforeEvent.defaultPrevented) {
			return;
		}

		this.domNodes.lightbox.close();
		this.domNodes.loader.classList.remove('Visible');

		// Make sure the stage is empty to not have videos playing in the background
		this.domNodes.stage.innerHTML = '';

		document.documentElement.classList.remove('Lightbox-Opened');

		if (this.currentPreload) {
			this.currentPreload = null;
		}

		this.panzoom?.destroy();
		this.panzoom = null;

		this.sourceElement.focus();

		// Dispatch a custom event to allow for customizing the lightbox
		const afterEvent = new CustomEvent('afterLightboxClose', {detail: this});
		document.dispatchEvent(afterEvent);

		// If the url contains the lightbox hash, go back to the previous state
		if (window.location.hash === '#Lightbox') {
			window.history.back();
		}
	}

	/**
	 * Build the pagination pagination buttons.
	 */
	buildPagination() {
		// Remove all existing pagination buttons
		this.domNodes.pagination.innerHTML = '';

		if (!this.settings.showPagination || this.currentElements.length <= 1) {
			return;
		}

		// Dispatch a custom event to allow for customizing the pagination
		const beforeEvent = new CustomEvent('beforeLightboxPaginationBuild', {detail: this});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't create the pagination
		if (beforeEvent.defaultPrevented) {
			return;
		}

		this.currentElements.forEach((element, index) => {
			const li = document.createElement('li');
			const button = document.createElement('button');

			button.dataset.lightboxIndex = index;
			button.classList.add('Pagination-Button');

			li.appendChild(button);

			this.domNodes.pagination.appendChild(li);

			// Dispatch a custom event to allow for customizing the pagination button
			const buttonEvent = new CustomEvent('beforeLightboxPaginationButtonBuild', {detail: this, element: element, index: index, button: button});
			document.dispatchEvent(buttonEvent);
		});

		// Dispatch a custom event to allow for customizing the pagination
		const afterEvent = new CustomEvent('afterLightboxPaginationBuild', {detail: this});
		document.dispatchEvent(afterEvent);
	}

	/**
	 * Show the lightbox item with the given index.
	 * @param {string|number} index
	 */
	showItem(index) {
		// Dispatch a custom event to allow for customizing the item
		const beforeEvent = new CustomEvent('beforeLightboxItemShow', {detail: this, index: index});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't show the item
		if (beforeEvent.defaultPrevented) {
			return;
		}

		if (this.currentPreload) {
			this.currentPreload = false;
		}

		this.panzoom?.destroy();
		this.panzoom = null;

		let nextIndex = parseInt(index);

		if (index === 'previous') {
			nextIndex = this.currentIndex - 1;
		}
		else if (index === 'next') {
			nextIndex = this.currentIndex + 1;
		}

		// If the index is below 0, loop to the last element if looping is enabled
		// Otherwise, set the index to 0
		if (nextIndex < 0) {
			nextIndex = this.settings.loop ? this.currentElements.length - 1 : 0;
		}
			// If the index is larger than the number of elements, loop to the first element if looping is enabled
		// Otherwise, set the index to the last element
		else if (nextIndex >= this.currentElements.length) {
			nextIndex = this.settings.loop ? 0 : this.currentElements.length - 1;
		}

		this.domNodes.arrows.arrowLeft.classList.toggle('Hidden', this.currentElements.length === 1);
		this.domNodes.arrows.arrowRight.classList.toggle('Hidden', this.currentElements.length === 1);

		const atBeginning = !this.settings.loop && nextIndex === 0;
		this.domNodes.arrows.arrowLeft.classList.toggle('Disabled', atBeginning);
		this.domNodes.arrows.arrowLeft.inert = atBeginning;

		const atEnd = !this.settings.loop && nextIndex === this.currentElements.length - 1;
		this.domNodes.arrows.arrowRight.classList.toggle('Disabled', atEnd);
		this.domNodes.arrows.arrowRight.inert = atEnd;

		// Remove all elements from the stage
		this.domNodes.stage.innerHTML = '';
		// Reset the stage class
		this.domNodes.stage.className = 'Lightbox-Stage';

		this.domNodes.buttons.zoomIn.classList.remove('Visible');
		this.domNodes.buttons.zoomOut.classList.remove('Visible');

		setTimeout(() => {
			this.loadItem(nextIndex);

			// Dispatch a custom event
			const afterEvent = new CustomEvent('afterLightboxItemShow', {detail: this, index: index, realIndex: this.currentIndex});
			document.dispatchEvent(afterEvent);
		}, 300);
	}

	/**
	 * Show the loader and load the item with the given index.
	 */
	loadItem(nextIndex) {
		const element = this.currentElements[nextIndex];

		// Dispatch a custom event to allow for customizing the item
		const beforeEvent = new CustomEvent('beforeLightboxItemLoad', {detail: this, element: element});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't load the item
		if (beforeEvent.defaultPrevented) {
			return;
		}

		// Show the loader
		this.domNodes.loader.classList.add('Visible');

		// Hide the zoom buttons
		this.domNodes.buttons.zoomIn.classList.remove('Visible');
		this.domNodes.buttons.zoomOut.classList.remove('Visible');

		this.domNodes.caption.classList.remove('Visible');

		if (this.settings.counterFormat && this.domNodes.counter) {
			let counter = this.settings.counterFormat;

			counter = counter.replace('{current}', this.currentIndex + 1);
			counter = counter.replace('{total}', this.currentElements.length);

			this.domNodes.counter.innerHTML = counter;
		}

		if (this.settings.showPagination && this.currentElements.length > 1) {
			this.domNodes.pagination.querySelectorAll('li').forEach((li, i) => {
				li.classList.toggle('Active', i === nextIndex);
			});
		}

		this.currentIndex = nextIndex;

		switch (element.type) {
			case 'image':
				this.currentPreload = this.loadImage(element);
				break;
			case 'video':
				this.currentPreload = this.loadVideo(element);
				break;
			case 'inline':
				this.currentPreload = this.loadInline(element);
				break;
			case 'ajax':
				this.currentPreload = this.loadAjax(element);
				break;
			case 'iframe':
				this.currentPreload = this.loadIframe(element);
				break;
			default:
				console.error(`Unsupported item type: ${element.type}`);
		}

		if (!this.currentPreload) {
			return;
		}

		this.currentPreload.then(() => {
			if (!this.domNodes.lightbox.open) {
				// Remove all elements from the stage
				this.domNodes.stage.innerHTML = '';

				return;
			}

			this.domNodes.stage.classList.add('Loaded');

			this.domNodes.caption.classList.toggle('Visible', !!element.caption);
			this.domNodes.caption.innerHTML = element.caption || '';

			// Dispatch a custom event
			const afterEvent = new CustomEvent('afterLightboxItem', {detail: this, element: element, error: null});
			document.dispatchEvent(afterEvent);
		}).catch((e) => {
			this.domNodes.stage.classList.add('Error');
			console.error(e);

			// Dispatch a custom event
			const afterEvent = new CustomEvent('afterLightboxItem', {detail: this, element: element, error: e});
			document.dispatchEvent(afterEvent);
		}).finally(() => {
			this.domNodes.loader.classList.remove('Visible');
		});
	}

	/**
	 * Load the image for the given item.
	 *
	 * @param {object} element
	 * @returns {Promise<unknown>}
	 */
	loadImage(element) {
		this.domNodes.stage.classList.add('Image');

		return new Promise((resolve, reject) => {
			const image = new Image();

			image.onload = () => {
				this.domNodes.stage.appendChild(image);
				this.initPanzoom();

				resolve();
			};

			image.onerror = () => {
				reject(new Error('Failed to load image'));
			};

			image.src = element.href;
		});
	}

	/**
	 * Load the video for the given item.
	 *
	 * @param {object} element
	 * @returns {Promise<unknown>}
	 */
	loadVideo(element) {
		this.domNodes.stage.classList.add('Video');

		return new Promise((resolve, reject) => {
			const video = document.createElement('video');

			video.onloadeddata = () => {
				resolve();
			}

			video.onerror = () => {
				reject(new Error('Failed to load video'));
			}

			const source = document.createElement('source');
			source.src = element.href;

			['playsinline', 'controls', 'autoplay', 'loop'].forEach(attr => video.setAttribute(attr, ''));

			this.domNodes.stage.appendChild(video);
			video.appendChild(source);
		});
	}

	/**
	 * Load the inline element for the given item.
	 *
	 * @param {object} element
	 * @returns {Promise<unknown>}
	 */
	loadInline(element) {
		this.domNodes.stage.classList.add('Inline');

		return new Promise((resolve, reject) => {
			const targetElement = document.querySelector(element.selector);

			if (!targetElement) {
				reject(new Error('Element not found'));

				return;
			}

			this.domNodes.stage.appendChild(targetElement.cloneNode(true));

			resolve();
		});
	}

	/**
	 * Load the content of specified URL via AJAX.
	 *
	 * @param {object} element
	 * @returns {Promise<void>}
	 */
	loadAjax(element) {
		this.domNodes.stage.classList.add('Ajax');

		return fetch(element.href)
		.then(response => response.text())
		.then(data => {
			// Parse the response as HTML
			const parser = new DOMParser();
			const doc = parser.parseFromString(data, 'text/html');

			if (element.selector) {
				// Find the element with the selector
				const ajax = doc.querySelector(element.selector);
				if (!ajax) {
					throw new Error('Element not found');
				}

				this.domNodes.stage.appendChild(ajax);

				return;
			}

			// If no selector is provided, add the entire document
			this.domNodes.stage.innerHTML = doc.body.innerHTML;
		});
	}

	/**
	 * Load the iframe for the given item.
	 *
	 * @param {object} element
	 * @returns {Promise<unknown>}
	 */
	loadIframe(element) {
		this.domNodes.stage.classList.add('Iframe');

		return new Promise((resolve, reject) => {
			const iframe = document.createElement('iframe');

			if (element.href.includes('player.vimeo.com/video') || element.href.includes('www.youtube-nocookie.com')) {
				iframe.classList.add('Video');
			}

			iframe.onload = () => {
				resolve();
			}

			iframe.onerror = () => {
				reject(new Error('Failed to load iframe'));
			};

			iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');

			this.domNodes.stage.appendChild(iframe);

			iframe.src = element.href;
		});
	}

	/**
	 * Initialize the panzoom for the current element.
	 */
	initPanzoom() {
		const element = this.domNodes.stage.children[0];

		// Dispatch a custom event to allow for customizing the panzoom
		const beforeEvent = new CustomEvent('beforeLightboxPanzoomInit', {detail: this, element: element});
		document.dispatchEvent(beforeEvent);

		// If the event was canceled, don't initialize the panzoom
		if (beforeEvent.defaultPrevented) {
			return;
		}

		this.domNodes.buttons.zoomIn.classList.add('Visible');
		this.domNodes.buttons.zoomOut.classList.add('Visible');

		this.domNodes.buttons.zoomIn.classList.remove('Disabled');
		this.domNodes.buttons.zoomIn.inert = false;
		this.domNodes.buttons.zoomOut.classList.add('Disabled');
		this.domNodes.buttons.zoomOut.inert = true;

		this.panzoom = Panzoom(element, this.settings.panzoom);

		element.addEventListener('panzoomzoom', event => {
			const atMaxScale = event.detail.scale >= this.panzoom.getOptions().maxScale;
			this.domNodes.buttons.zoomIn.classList.toggle('Disabled', atMaxScale);
			this.domNodes.buttons.zoomIn.inert = atMaxScale;

			const atMinScale = event.detail.scale <= this.panzoom.getOptions().minScale;
			this.domNodes.buttons.zoomOut.classList.toggle('Disabled', atMinScale);
			this.domNodes.buttons.zoomOut.inert = atMinScale;

			if (atMinScale) {
				this.panzoom.pan(0, 0);
			}
		});

		// Dispatch a custom event to allow for customizing the panzoom
		const afterEvent = new CustomEvent('afterLightboxPanzoomInit', {detail: this, element: element, panzoom: this.panzoom});
		document.dispatchEvent(afterEvent);
	}

	/**
	 * Handle the click event.
	 *
	 * @param {MouseEvent} event
	 */
	handleClick(event) {
		if (event.target === this.domNodes.arrows.arrowLeft) {
			event.preventDefault();
			this.showItem('previous');
		}
		else if (event.target === this.domNodes.arrows.arrowRight) {
			event.preventDefault();
			this.showItem('next');
		}
		else if (event.target === this.domNodes.buttons.close) {
			event.preventDefault();
			this.close();
		}
		else if (event.target.nodeName === 'BUTTON' && this.domNodes.pagination && this.domNodes.pagination.contains(event.target)) {
			event.preventDefault();
			this.showItem(event.target.dataset.lightboxIndex);
		}
	}

	/**
	 * Handle the keydown event.
	 * @param {KeyboardEvent} event
	 */
	handleKeyDown(event) {
		if (!this.domNodes.lightbox.open) {
			return;
		}

		if (event.key === 'Escape') {
			event.preventDefault();
			this.close();
			return;
		}

		if (event.key === 'ArrowLeft') {
			if (
				this.domNodes.arrows.arrowLeft.classList.contains('Disabled') ||
				this.domNodes.arrows.arrowLeft.classList.contains('Hidden')
			) {
				return;
			}

			event.preventDefault();
			this.showItem('previous');
			return;
		}

		if (event.key === 'ArrowRight') {
			if (
				this.domNodes.arrows.arrowRight.classList.contains('Disabled') ||
				this.domNodes.arrows.arrowRight.classList.contains('Hidden')
			) {
				return;
			}

			event.preventDefault();
			this.showItem('next');
		}
	}


	/**
	 * Handle the hash change event.
	 * If the hash is empty, close the lightbox.
	 *
	 * If the hash is not empty, open the lightbox with the given index
	 * if it can be found, otherwise remove the hash.
	 *
	 * @param {HashChangeEvent} event
	 */
	handleHashChange(event) {
		if (!this.urlChange) {
			return;
		}

		const hasLightboxHash = window.location.hash === '#Lightbox';

		if (!hasLightboxHash) {
			if (this.domNodes.lightbox) {
				this.close();
			}

			return;
		}

		// Get the sourceElementPath from the history state if it exists
		const sourceElementPath = window.history.state?.sourceElementPath;

		// If the sourceElementPath empty, there's nothing to do
		if (!sourceElementPath) {
			return;
		}

		const element = document.evaluate(sourceElementPath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
		// If the element isn't found or has no "lightbox" in its rel attribute, remove the hash
		if (
			!element ||
			!element.matches('[rel*="lightbox"]')
		) {
			return;
		}

		// Trigger a click event on the element to open the lightbox
		const clickEvent = new MouseEvent('click', { bubbles: true, cancelable: true });
		element.dispatchEvent(clickEvent);
	}


	/**
	 * Get the XPath of the given element.
	 *
	 * @param {HTMLElement} element
	 */
	getXPath(element) {
		if (element.id) {
			return `//*[@id="${element.id}"]`;
		}

		const parts = [];
		while (element && element.nodeType === Node.ELEMENT_NODE) {
			let part = element.nodeName.toLowerCase();

			if (element.id) {
				part = `/${part}[@id="${element.id}"]`;

				// If the element has an id, it's unique and we can stop here
				parts.unshift(part);
				break;
			}
			else {
				// Check if the element has siblings of the same type
				if (element.parentNode) {
					const siblings = Array.from(element.parentNode.children).filter(
						child => child.nodeName === element.nodeName
					);

					if (siblings.length > 1) {
						// Add position index (1-based) if multiple siblings of the same type exist
						const position = siblings.indexOf(element) + 1;
						part += `[${position}]`;
					}
				}

				if (
					element.className &&
					// No class name for html- and body-tag
					['html', 'body'].indexOf(element.nodeName.toLowerCase()) === -1
				) {
					part += `[@class="${element.className}"]`;
				}
			}

			parts.unshift(part);
			element = element.parentNode;
		}

		return '/' + parts.join('/');
	}
}

// phpcs:disable
/**
 * Panzoom 4.6.1 for panning and zooming elements using CSS transforms
 * Copyright Timmy Willison and other contributors
 * https://github.com/timmywil/panzoom/blob/main/MIT-License.txt
 */
((t,e)=>{"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).Panzoom=e()})(this,function(){var a,X=function(){return(X=Object.assign||function(t){for(var e,n=1,o=arguments.length;n<o;n++)for(var r in e=arguments[n])Object.prototype.hasOwnProperty.call(e,r)&&(t[r]=e[r]);return t}).apply(this,arguments)},i=("undefined"!=typeof window&&(window.NodeList&&!NodeList.prototype.forEach&&(NodeList.prototype.forEach=Array.prototype.forEach),"function"!=typeof window.CustomEvent)&&(window.CustomEvent=function(t,e){e=e||{bubbles:!1,cancelable:!1,detail:null};var n=document.createEvent("CustomEvent");return n.initCustomEvent(t,e.bubbles,e.cancelable,e.detail),n}),"undefined"!=typeof document&&!!document.documentMode);var c=["webkit","moz","ms"],l={};function Y(t){if(l[t])return l[t];var e=a=a||document.createElement("div").style;if(t in e)return l[t]=t;for(var n=t[0].toUpperCase()+t.slice(1),o=c.length;o--;){var r="".concat(c[o]).concat(n);if(r in e)return l[t]=r}}function o(t,e){return parseFloat(e[Y(t)])||0}function s(t,e,n){void 0===n&&(n=window.getComputedStyle(t));t="border"===e?"Width":"";return{left:o("".concat(e,"Left").concat(t),n),right:o("".concat(e,"Right").concat(t),n),top:o("".concat(e,"Top").concat(t),n),bottom:o("".concat(e,"Bottom").concat(t),n)}}function C(t,e,n){t.style[Y(e)]=n}function N(t){var e=t.parentNode,n=window.getComputedStyle(t),o=window.getComputedStyle(e),r=t.getBoundingClientRect(),a=e.getBoundingClientRect();return{elem:{style:n,width:r.width,height:r.height,top:r.top,bottom:r.bottom,left:r.left,right:r.right,margin:s(t,"margin",n),border:s(t,"border",n)},parent:{style:o,width:a.width,height:a.height,top:a.top,bottom:a.bottom,left:a.left,right:a.right,padding:s(e,"padding",o),border:s(e,"border",o)}}}var T={down:"mousedown",move:"mousemove",up:"mouseup mouseleave"};function L(t,e,n,o){T[t].split(" ").forEach(function(t){e.addEventListener(t,n,o)})}function V(t,e,n){T[t].split(" ").forEach(function(t){e.removeEventListener(t,n)})}function G(t,e){for(var n=t.length;n--;)if(t[n].pointerId===e.pointerId)return n;return-1}function I(t,e){if(e.touches)for(var n=0,o=0,r=e.touches;o<r.length;o++){var a=r[o];a.pointerId=n++,I(t,a)}else-1<(n=G(t,e))&&t.splice(n,1),t.push(e)}function R(t){for(var e,n=(t=t.slice(0)).pop();e=t.pop();)n={clientX:(e.clientX-n.clientX)/2+n.clientX,clientY:(e.clientY-n.clientY)/2+n.clientY};return n}function W(t){var e;return t.length<2?0:(e=t[0],t=t[1],Math.sqrt(Math.pow(Math.abs(t.clientX-e.clientX),2)+Math.pow(Math.abs(t.clientY-e.clientY),2)))}"undefined"!=typeof window&&("function"==typeof window.PointerEvent?T={down:"pointerdown",move:"pointermove",up:"pointerup pointerleave pointercancel"}:"function"==typeof window.TouchEvent&&(T={down:"touchstart",move:"touchmove",up:"touchend touchcancel"}));var Z=/^http:[\w\.\/]+svg$/;var q={animate:!1,canvas:!1,cursor:"move",disablePan:!1,disableZoom:!1,disableXAxis:!1,disableYAxis:!1,duration:200,easing:"ease-in-out",exclude:[],excludeClass:"panzoom-exclude",handleStartEvent:function(t){t.preventDefault(),t.stopPropagation()},maxScale:4,minScale:.125,overflow:"hidden",panOnlyWhenZoomed:!1,pinchAndPan:!1,relative:!1,setTransform:function(t,e,n){var o=e.x,r=e.y,a=e.isSVG;C(t,"transform","scale(".concat(e.scale,") translate(").concat(o,"px, ").concat(r,"px)")),a&&i&&(e=window.getComputedStyle(t).getPropertyValue("transform"),t.setAttribute("transform",e))},startX:0,startY:0,startScale:1,step:.3,touchAction:"none"};function t(u,f){if(!u)throw new Error("Panzoom requires an element as an argument");if(1!==u.nodeType)throw new Error("Panzoom requires an element with a nodeType of 1");if(!(t=>{for(var e=t;e&&e.parentNode;){if(e.parentNode===document)return 1;e=e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}})(u))throw new Error("Panzoom should be called on elements that have been attached to the DOM");f=X(X({},q),f);t=u;var t,l=Z.test(t.namespaceURI)&&"svg"!==t.nodeName.toLowerCase(),n=u.parentNode;n.style.overflow=f.overflow,n.style.userSelect="none",n.style.touchAction=f.touchAction,(f.canvas?n:u).style.cursor=f.cursor,u.style.userSelect="none",u.style.touchAction=f.touchAction,C(u,"transformOrigin","string"==typeof f.origin?f.origin:l?"0 0":"50% 50%");var r,a,i,c,s,d,m=0,h=0,v=1,p=!1;function g(t,e,n){n.silent||(n=new CustomEvent(t,{detail:e}),u.dispatchEvent(n))}function y(o,r,t){var a={x:m,y:h,scale:v,isSVG:l,originalEvent:t};return requestAnimationFrame(function(){var t,e,n;"boolean"==typeof r.animate&&(r.animate?(t=u,e=r,n=Y("transform"),C(t,"transition","".concat(n," ").concat(e.duration,"ms ").concat(e.easing))):C(u,"transition","none")),r.setTransform(u,a,r),g(o,a,r),g("panzoomchange",a,r)}),a}function w(t,e,n,o){var r,a,i,c,l,s,d=X(X({},f),o),p={x:m,y:h,opts:d};return(null!=o&&o.force||!(d.disablePan||d.panOnlyWhenZoomed&&v===d.startScale))&&(t=parseFloat(t),e=parseFloat(e),d.disableXAxis||(p.x=(d.relative?m:0)+t),d.disableYAxis||(p.y=(d.relative?h:0)+e),d.contain&&(t=((e=(t=(o=N(u)).elem.width/v)*n)-t)/2,a=((r=(a=o.elem.height/v)*n)-a)/2,"inside"===d.contain?(i=(-o.elem.margin.left-o.parent.padding.left+t)/n,c=(o.parent.width-e-o.parent.padding.left-o.elem.margin.left-o.parent.border.left-o.parent.border.right+t)/n,p.x=Math.max(Math.min(p.x,c),i),l=(-o.elem.margin.top-o.parent.padding.top+a)/n,s=(o.parent.height-r-o.parent.padding.top-o.elem.margin.top-o.parent.border.top-o.parent.border.bottom+a)/n,p.y=Math.max(Math.min(p.y,s),l)):"outside"===d.contain&&(i=(-(e-o.parent.width)-o.parent.padding.left-o.parent.border.left-o.parent.border.right+t)/n,c=(t-o.parent.padding.left)/n,p.x=Math.max(Math.min(p.x,c),i),l=(-(r-o.parent.height)-o.parent.padding.top-o.parent.border.top-o.parent.border.bottom+a)/n,s=(a-o.parent.padding.top)/n,p.y=Math.max(Math.min(p.y,s),l))),d.roundPixels)&&(p.x=Math.round(p.x),p.y=Math.round(p.y)),p}function b(t,e){var n,o,r,a=X(X({},f),e),i={scale:v,opts:a};return(null!=e&&e.force||!a.disableZoom)&&(e=f.minScale,n=f.maxScale,a.contain&&(r=(a=N(u)).elem.width/v,o=a.elem.height/v,1<r)&&1<o&&(r=(a.parent.width-a.parent.border.left-a.parent.border.right)/r,a=(a.parent.height-a.parent.border.top-a.parent.border.bottom)/o,"inside"===f.contain?n=Math.min(n,r,a):"outside"===f.contain&&(e=Math.max(e,r,a))),i.scale=Math.min(Math.max(t,e),n)),i}function x(t,e,n,o){t=w(t,e,v,n);return m!==t.x||h!==t.y?(m=t.x,h=t.y,y("panzoompan",t.opts,o)):{x:m,y:h,scale:v,isSVG:l,originalEvent:o}}function S(t,e,n){var o,r=b(t,e),a=r.opts;if(null!=e&&e.force||!a.disableZoom)return t=r.scale,e=m,r=h,a.focal&&(e=((o=a.focal).x/t-o.x/v+m*t)/t,r=(o.y/t-o.y/v+h*t)/t),o=w(e,r,t,{relative:!1,force:!0}),m=o.x,h=o.y,v=t,y("panzoomzoom",a,n)}function e(t,e){e=X(X(X({},f),{animate:!0}),e);return S(v*Math.exp((t?1:-1)*e.step),e)}function E(t,e,n,o){var r=N(u),a=r.parent.width-r.parent.padding.left-r.parent.padding.right-r.parent.border.left-r.parent.border.right,i=r.parent.height-r.parent.padding.top-r.parent.padding.bottom-r.parent.border.top-r.parent.border.bottom,c=e.clientX-r.parent.left-r.parent.padding.left-r.parent.border.left-r.elem.margin.left,e=e.clientY-r.parent.top-r.parent.padding.top-r.parent.border.top-r.elem.margin.top,r=(l||(c-=r.elem.width/v/2,e-=r.elem.height/v/2),{x:c/a*(a*t),y:e/i*(i*t)});return S(t,X(X({},n),{animate:!1,focal:r}),o)}S(f.startScale,{animate:!1,force:!0}),setTimeout(function(){x(f.startX,f.startY,{animate:!1,force:!0})});var M=[];function o(t){((t,e)=>{for(var n,o,r=t;null!=r;r=r.parentNode)if(n=r,o=e.excludeClass,1===n.nodeType&&-1<" ".concat((n.getAttribute("class")||"").trim()," ").indexOf(" ".concat(o," "))||-1<e.exclude.indexOf(r))return 1})(t.target,f)||(I(M,t),p=!0,f.handleStartEvent(t),g("panzoomstart",{x:r=m,y:a=h,scale:v,isSVG:l,originalEvent:t},f),t=R(M),i=t.clientX,c=t.clientY,s=v,d=W(M))}function A(t){var e,n,o;p&&void 0!==r&&void 0!==a&&void 0!==i&&void 0!==c&&(I(M,t),e=R(M),n=1<M.length,o=v,n&&(0===d&&(d=W(M)),E(o=b((W(M)-d)*f.step/80+s).scale,e,{animate:!1},t)),n&&!f.pinchAndPan||x(r+(e.clientX-i)/o,a+(e.clientY-c)/o,{animate:!1},t))}function P(t){1===M.length&&g("panzoomend",{x:m,y:h,scale:v,isSVG:l,originalEvent:t},f);var e=M;if(t.touches)for(;e.length;)e.pop();else{t=G(e,t);-1<t&&e.splice(t,1)}p&&(p=!1,r=a=i=c=void 0)}var O=!1;function z(){O||(O=!0,L("down",f.canvas?n:u,o),L("move",document,A,{passive:!0}),L("up",document,P,{passive:!0}))}return f.noBind||z(),{bind:z,destroy:function(){O=!1,V("down",f.canvas?n:u,o),V("move",document,A),V("up",document,P)},eventNames:T,getPan:function(){return{x:m,y:h}},getScale:function(){return v},getOptions:function(){var t,e=f,n={};for(t in e)e.hasOwnProperty(t)&&(n[t]=e[t]);return n},handleDown:o,handleMove:A,handleUp:P,pan:x,reset:function(t){var t=X(X(X({},f),{animate:!0,force:!0}),t),e=(v=b(t.startScale,t).scale,w(t.startX,t.startY,v,t));return m=e.x,h=e.y,y("panzoomreset",t)},resetStyle:function(){n.style.overflow="",n.style.userSelect="",n.style.touchAction="",n.style.cursor="",u.style.cursor="",u.style.userSelect="",u.style.touchAction="",C(u,"transformOrigin","")},setOptions:function(t){for(var e in t=void 0===t?{}:t)t.hasOwnProperty(e)&&(f[e]=t[e]);(t.hasOwnProperty("cursor")||t.hasOwnProperty("canvas"))&&(n.style.cursor=u.style.cursor="",(f.canvas?n:u).style.cursor=f.cursor),t.hasOwnProperty("overflow")&&(n.style.overflow=t.overflow),t.hasOwnProperty("touchAction")&&(n.style.touchAction=t.touchAction,u.style.touchAction=t.touchAction)},setStyle:function(t,e){return C(u,t,e)},zoom:S,zoomIn:function(t){return e(!0,t)},zoomOut:function(t){return e(!1,t)},zoomToPoint:E,zoomWithWheel:function(t,e){t.preventDefault();var e=X(X(X({},f),e),{animate:!1}),n=0===t.deltaY&&t.deltaX?t.deltaX:t.deltaY;return E(b(v*Math.exp((n<0?1:-1)*e.step/3),e).scale,t,e,t)}}}return t.defaultOptions=q,t});
// phpcs:enable
