// noinspection JSUnusedGlobalSymbols

export default class ProgressChecker {
	checkerUrls = {
		preview: `${baseUrl}backend/${languageShortcode}/media/check-preview-progress/`,
		thumbnail: `${baseUrl}backend/${languageShortcode}/media/check-thumbnail-progress/`,
		webp: `${baseUrl}backend/${languageShortcode}/media/check-webp-progress/`,
	};
	/**
	 * The service worker registration object
	 * @type {ServiceWorkerRegistration}
	 */
	worker;
	workerStateListenerBound = {
		preview: false,
		thumbnail: false,
		webp: false,
	};

	constructor() {
		// Register the service worker for checking the progress of file creation
		navigator.serviceWorker.register(`${baseUrl}awyiss/assets/js/modules/Media/ProgressCheckerWorker.js`, {scope: `${baseUrl}`})
		.then((registration) => {
			this.worker = registration;

			// Check if there are elements that need to be checked
			let elements = document.querySelectorAll('.AwaitingPreview');
			if (elements.length > 0) {
				this.startChecking('preview');
			}

			// Check if there are elements that need to be checked
			elements = document.querySelectorAll('.AwaitingThumbnail');
			if (elements.length > 0) {
				this.startChecking('thumbnail');
			}
		})
		.catch((error) => {
			// The service worker registration failed
			console.error('Service Worker Registration Failed: ', error);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Check if there are elements that need to be checked.
	 * If there are, start checking.
	 * Check check check.
	 *
	 * @param {string} type - The type of progress to check.
	 */
	startChecking(type) {
		// Make sure the worker is active, or wait for it to become active
		if (this.worker && this.worker.active.state === 'activated') {
			this.#_startChecking(type);
		}
		else if (!this.workerStateListenerBound[type]) {
			this.worker.addEventListener('statechange', () => {
				if (this.worker.active.state === 'activated') {
					this.#_startChecking(type);
				}
			});

			this.workerStateListenerBound[type] = true;
		}
	}

	/**
	 * Start checking the progress of the image generation.
	 *
	 * @param {string} type - The type of progress to check.
	 */
	#_startChecking(type) {
		if (!this.workerMessageListenerBound) {
			navigator.serviceWorker.addEventListener('message', this.handleMessage.bind(this));
			this.workerMessageListenerBound = true;
		}

		// Send a message to the worker to start checking
		this.worker.active.postMessage({
			command: 'startChecking',
			type: type,
			url: this.checkerUrls[type],
		});
	}

	/**
	 * Handle the message from the service worker.
	 * @param event
	 */
	handleMessage(event) {
		// noinspection JSIncompatibleTypesComparison
		if (!event.data.workerId === 'mediaProgressChecker') {
			return;
		}

		if (event.data.type === 'preview') {
			this.handlePreviewMessage(event.data.data);
		}
	}

	/**
	 * Handle the message from the service worker for preview progress.
	 * @param {Object} data
	 */
	handlePreviewMessage(data) {
		const completed = data.completed || [];
		completed.forEach(elementId => {
			const element = document.getElementById(`AwaitingPreview${elementId}`);
			if (element) {
				element.classList.remove('AwaitingPreview');

				let image;
				// If the element is an image, use it for the image
				if (element.tagName === 'IMG') {
					image = element;
				}
				else if (element.querySelector('img')) {
					image = element.querySelector('img');
				}

				// If there is an image, set the src attribute
				if (image) {
					image.src = image.dataset.src;

					if (element.parentElement.classList.contains('AwaitingPreview')) {
						// Replace the parent element with the image
						element.parentElement.replaceWith(image);
					}
				}
			}
		});

		// For all the elements that have been checked but failed, remove the AwaitingPreview class and the image
		// noinspection JSUnresolvedReference
		const failed = data.failed || [];
		failed.forEach(elementId => {
			const element = document.getElementById(`AwaitingPreview${elementId}`);
			if (element) {
				// Check if the element or its parent has the AwaitingPreview class and remove it
				element.parentElement.classList.remove('AwaitingPreview');

				// If the element is an image, remove it
				if (element.tagName === 'IMG') {
					element.remove();
				}
			}
		});

		if (data.message === 'done') {
			const elements = document.querySelectorAll('.AwaitingPreview');
			elements.forEach(element => {
				element.classList.remove('AwaitingPreview');

				// Remove the image from the element since there is no preview available
				const img = element.querySelector('img');
				if (img) {
					img.remove();
				}
			});
		}
	}

	/**
	 * Unregister the service worker.
	 * Normally this is not needed, but it can be useful for debugging.
	 */
	unregisterWorker() {
		// Check if the worker is defined
		if (this.worker) {
			// Unregister the service worker
			this.worker.unregister()
			.then((success) => {
				if (success) {
					console.log('Unregistration of the Service Worker succeeded.');
				}
				else {
					console.log('Unregistration of the Service Worker failed.');
				}
			})
			.catch((error) => {
				// The service worker unregistration failed
				console.error('Unregistration of the Service Worker failed: ', error);
			});
		}
	}

	/**
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		// If nodes were added
		if (!mutation.addedNodes.length) {
			return;
		}

		const nodes = Array.from(mutation.addedNodes);

		// Check if the added nodes contain any elements that need to be checked, either directly or as descendants of the added nodes
		let previewCheckRequired = nodes.some(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return false;
			}

			return node.matches('.AwaitingPreview') || node.querySelectorAll('.AwaitingPreview').length > 0;
		});

		if (previewCheckRequired) {
			this.startChecking('preview');
		}

		// Check if the added nodes contain any elements that need to be checked, either directly or as descendants of the added nodes
		let thumbnailCheckRequired = nodes.some(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return false;
			}

			return node.matches('.AwaitingThumbnail') || node.querySelectorAll('.AwaitingThumbnail').length > 0;
		});

		if (thumbnailCheckRequired) {
			this.startChecking('thumbnail');
		}
	}
}