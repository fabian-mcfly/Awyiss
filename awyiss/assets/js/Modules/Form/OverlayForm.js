// noinspection JSUnusedGlobalSymbols

export default class OverlayForm {
	/**
	 * The overlay close button
	 * @type {HTMLElement}
	 */
	closeButton;
	/**
	 * The overlay element
	 * @type {HTMLDialogElement}
	 */
	dialog;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The element selector that will open the overlay.
	 */
	elementSelector = 'a.OverlayForm';
	/**
	 * The iframe element for the overlay
	 * @type {HTMLIFrameElement}
	 */
	iframe
	/**
	 * The Observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The element that opened the overlay.
	 * @type {HTMLElement}
	 */
	opener;
	/**
	 * The remembered state of the form changed flag.
	 * @type {boolean}
	 */
	savedIsFormChanged = false;

	/**
	 * Initialize the overlay form.
	 * Bind a click event to the close button.
	 * Add a submit event to the overlay.
	 */
	constructor() {
		this.dialog = document.getElementById('OverlayForm');

		if (!this.dialog) {
			this.dialog = document.createElement('dialog');
			this.dialog.id = 'OverlayForm';
			document.body.appendChild(this.dialog);
		}

		// Check if the form inside the iframe has changed before trying to close the overlay
		this.eventHandler.add('cancel', this.handleRequestClose.bind(this), this.dialog);

		this.iframe = document.createElement('iframe');
		this.dialog.appendChild(this.iframe);

		// Bind a click event to all links that should open their target in an overlay.
		const elements = document.querySelectorAll(this.elementSelector);
		for (const element of elements) {
			this.bindOpenOverlayButton(element);
		}

		this.observer.addObserver(this.observeMutations.bind(this));

		// Listen for a close request from the iframe
		window.addEventListener('message', (event) => {
			if (event.data === 'closeOverlayForm') {
				this.closeOverlay();
			}

			if (event.data === 'closeOverlayFormAndReload') {
				this.closeOverlay(true);
			}

			if (event.data === 'unlockOverlayForm') {
				this.iframe.classList.add('Visible');
			}
		});

		if (
			window.parent !== window &&
			document.body.classList.contains('OverlayForm') &&
			(
				document.body.classList.contains('AddAction') ||
				document.body.classList.contains('EditAction')
			)
		) {
			// Find the close buttons and send an event to the parent window
			const closeButtons = document.getElementById('Content').querySelectorAll(':scope > .Form > .ButtonArea .Button-Close');
			closeButtons.forEach(closeButton => {
				this.eventHandler.add('click', () => {
					if (window.formLeaveConfirmation.isFormChanged) {
						window.formLeaveConfirmation.showCustomDialog(() => {
							window.formLeaveConfirmation.unlock();
							window.parent.postMessage('closeOverlayForm', '*');
						});
					}
					else {
						window.parent.postMessage('closeOverlayForm', '*');
					}
				}, closeButton);
			});

			window.parent.postMessage('unlockOverlayForm', '*');

			document.querySelector('.ButtonArea').dataset.title = document.querySelector('h1').textContent;
		}
	}

	/**
	 * Bind a click event to open the overlay.
	 * @param {HTMLElement} element
	 */
	bindOpenOverlayButton(element) {
		this.eventHandler.add('click', this.openOverlay.bind(this, element), element);
	}

	/**
	 * Close the overlay.
	 *
	 * @param {boolean} reload Whether to reload the parent form.
	 */
	closeOverlay(reload = false) {
		this.dialog.close();

		this.iframe.classList.remove('Visible');
		this.iframe.contentWindow.location.replace('about:blank');

		if (reload) {
			const parentForm = this.opener?.closest('form');
			if (parentForm?.length) {
				// If the opener is inside a form, reload the form
				window.formUpdater.sendRequest(parentForm);
			}
		}
	}

	handleRequestClose(event) {
		// Get the iframe's window
		const iframeWindow = this.iframe.contentWindow;

		if (!iframeWindow?.formLeaveConfirmation.isFormChanged) {
			return;
		}

		event.preventDefault();

		iframeWindow.formLeaveConfirmation.showCustomDialog(() => {
			iframeWindow.formLeaveConfirmation.unlock();
			this.closeOverlay();
		});
	}

	/**
	 * Open the overlay and fetch the target URL.
	 * Place the fetched content in the overlay, then show the overlay.
	 * For consistency, the overlay will not show the h1 and only the "save and close" button.
	 *
	 * @param {HTMLElement} opener
	 * @param {Event} event
	 */
	openOverlay(opener, event) {
		event.preventDefault();

		this.opener = opener;
		const element = event.target;

		let target = element.getAttribute('href');
		if (!target.endsWith('/')) {
			target += '/';
		}
		target += 'overlay-form:1/';

		// Show the overlay and mark it as loading
		this.dialog.classList.add('FetchInProgress');
		this.dialog.showModal();

		this.iframe.contentWindow.location.replace(target);
	}

	/**
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length) {
			return;
		}

		for (const node of mutation.addedNodes) {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.elementSelector)) {
				this.bindOpenOverlayButton(node);
			}

			// Also check all children
			const elements = node.querySelectorAll(this.elementSelector);
			for (const element of elements) {
				this.bindOpenOverlayButton(element);
			}
		}
	}
}