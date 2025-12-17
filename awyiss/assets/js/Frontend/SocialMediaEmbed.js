// noinspection JSUnusedGlobalSymbols

/**
 * Social Media Embed - 2-Click Consent Handler
 *
 * Implements a privacy-respecting 2-click consent mechanism for embedded social media content.
 * First click displays information about the external provider and request consent.
 * Second click loads the actual embed from the third-party service.
 *
 * Supports: YouTube, Vimeo, Instagram
 */

export default class SocialMediaEmbed {
	/**
	 * HTML templates for embeds - use {embedId} as placeholder and {autoplay} for YouTube/Vimeo
	 * @type {Object}
	 */
	embedTemplates = {
		youtube: '<iframe class="SocialMediaEmbed-Iframe" src="https://www.youtube-nocookie.com/embed/{embedId}{autoplay}" title="YouTube video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
		vimeo: '<iframe class="SocialMediaEmbed-Iframe" src="https://player.vimeo.com/video/{embedId}{autoplay}" title="Vimeo video" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
		instagram: '<blockquote class="instagram-media" data-instgrm-version="14"><a href="https://www.instagram.com/p/{embedId}/"></a></blockquote>',
	};
	/**
	 * Track which scripts have been loaded to avoid duplicate loads
	 * @type {Set<string>}
	 */
	loadedScripts = new Set();
	/**
	 * MutationObserver instance for watching new widgets
	 *
	 * @type {MutationObserver|null}
	 */
	observer = null;
	/**
	 * CSS selector for widgets
	 * @type {string}
	 */
	selector = '.SocialMediaEmbed';
	/**
	 * localStorage key for storing all consents
	 * @type {string}
	 */
	storageKey = 'social_media_embed_consents';
	/**
	 * Service configurations for script loading
	 * @type {Object}
	 */
	serviceScripts = {
		instagram: 'https://www.instagram.com/embed.js',
	};

	/**
	 * Constructor - initializes all consent containers on the page and watches for new ones
	 */
	constructor() {
		const widgets = document.querySelectorAll(this.selector);
		widgets.forEach((widget) => {
			this.initWidget(widget);
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
	 * Initialize a single widget
	 * @param {HTMLElement} widget - The widget element
	 */
	initWidget(widget) {
		const button = widget.querySelector('.ConsentButton');
		const service = widget.dataset.socialMediaEmbedService;

		if (!button || !widget) {
			return;
		}

		// Check if consent already given in localStorage
		if (this.hasConsent(service)) {
			this.handleConsentClick(widget);
			return;
		}

		button.addEventListener('click', event => {
			event.preventDefault();
			const rememberCheckbox = widget.querySelector('.RememberConsent');
			const remember = rememberCheckbox ? rememberCheckbox.checked : false;
			this.handleConsentClick(widget, remember);
		});
	}

	/**
	 * Check if consent is already given for a service
	 * @param {string} service - The service name
	 * @returns {boolean}
	 */
	hasConsent(service) {
		const consents = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
		return consents[service] === true;
	}

	/**
	 * Save consent to localStorage
	 * @param {string} service - The service name
	 */
	saveConsent(service) {
		const consents = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
		consents[service] = true;
		localStorage.setItem(this.storageKey, JSON.stringify(consents));
	}

	/**
	 * Handle the consent button click
	 * @param {HTMLElement} widget - The widget element
	 * @param {boolean} remember - Whether to remember consent
	 */
	handleConsentClick(widget, remember = false) {
		const service = widget.dataset.socialMediaEmbedService;
		const embedId = widget.dataset.socialMediaEmbedEmbedId;

		if (remember) {
			this.saveConsent(service);
		}

		// Show embed for clicked widget with autoplay
		this.showEmbed(widget, service, embedId, true);

		// Show all other embeds for this service without autoplay
		this.showAllEmbedsForService(service, widget);

		// Load script if needed for this service
		if (this.serviceScripts[service]) {
			this.loadScript(this.serviceScripts[service]);
		}
	}

	/**
	 * Show embeds for all widgets with the given service (except the one that triggered)
	 * @param {string} service - The service name
	 * @param {HTMLElement} triggeringWidget - The widget that triggered consent
	 */
	showAllEmbedsForService(service, triggeringWidget) {
		const widgets = document.querySelectorAll(`${this.selector}[data-social-media-embed-service="${service}"]`);
		widgets.forEach((widget) => {
			// Skip the widget that already triggered (it's already shown with autoplay)
			if (widget === triggeringWidget) {
				return;
			}

			const embedId = widget.dataset.socialMediaEmbedEmbedId;
			// Show without autoplay
			this.showEmbed(widget, service, embedId, false);
		});
	}

	/**
	 * Show the embed and hide consent container
	 * @param {HTMLElement} widget - The widget element
	 * @param {string} service - The service name
	 * @param {string} embedId - The embed ID
	 * @param {boolean} autoplay - Whether to enable autoplay for this embed
	 */
	showEmbed(widget, service, embedId, autoplay = false) {
		widget.innerHTML = '';

		const template = this.embedTemplates[service];
		if (template) {
			let html = template.replace(/{embedId}/g, encodeURIComponent(embedId));

			// Replace autoplay placeholder for YouTube and Vimeo
			if (service === 'youtube' || service === 'vimeo') {
				const autoplayParam = autoplay ? '?autoplay=1' : '';
				html = html.replace(/{autoplay}/g, autoplayParam);
			}
			else {
				// Remove autoplay placeholder for other services
				html = html.replace(/{autoplay}/g, '');
			}

			widget.insertAdjacentHTML('beforeend', html);
		}
	}

	/**
	 * Load an external script only once (deduplication by URL)
	 * @param {string} scriptUrl - URL of the script to load
	 */
	loadScript(scriptUrl) {
		if (this.loadedScripts.has(scriptUrl)) {
			return;
		}

		this.loadedScripts.add(scriptUrl);

		const script = document.createElement('script');
		script.src = scriptUrl;
		script.async = true;

		script.onerror = () => {
			console.error(`Failed to load script: ${scriptUrl}`);
			this.loadedScripts.delete(scriptUrl);
		};

		document.body.appendChild(script);
	}

	/**
	 * Handle mutations from MutationObserver
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
				this.initWidget(node);
			}

			const widgets = node.querySelectorAll(this.selector);
			widgets.forEach((widget) => {
				this.initWidget(widget);
			});
		});
	}
}
