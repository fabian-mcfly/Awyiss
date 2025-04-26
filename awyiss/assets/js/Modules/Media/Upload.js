// noinspection JSUnusedGlobalSymbols

/**
 * Class Upload
 * Handles the file drop zone initialization, file upload queue, and fetch requests.
 */
export default class Upload {
	/**
	 * The number of currently active uploads.
	 * @type {number}
	 */
	activeUploads = 0;
	/**
	 * The form element.
	 * @type {HTMLElement}
	 */
	addForm;
	/**
	 * The maximum number of concurrent uploads.
	 * @type {number}
	 */
	concurrentUploads;
	/**
	 * The drop zone element.
	 * @type {HTMLElement}
	 */
	dropZone;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler
	/**
	 * The class name of the list item element.
	 * @type {string}
	 */
	listItemClass;
	/**
	 * The maximum allowed file size in bytes.
	 * @type {number}
	 */
	maxFileSize;
	/**
	 * The list of media itemst the files will be uploaded to.
	 * @type {HTMLElement}
	 */
	mediaList;
	/**
	 * The queue of files to be uploaded.
	 * @type {Array}
	 */
	queue = [];
	/**
	 * The queue element.
	 * @type {HTMLElement|null}
	 */
	queueElement = null;
	/**
	 * Additional data to be sent with the upload request.
	 * @type {{}}
	 */
	uploadData;
	/**
	 * The path to the upload endpoint.
	 * @type {string}
	 */
	uploadPath;


	/**
	 * @param {HTMLElement} mediaList - The list of media items.
	 * @param {Object} settings - The settings for the upload module.
	 */
	constructor(mediaList, settings = {}) {
		this.mediaList = mediaList;

		// Override default settings with user settings
		const defaultSettings = {
			addForm: null,
			concurrentUploads: 5,
			dropZone: null,
			listItemClass: 'Media-ListItem',
			maxFileSize: 10485760,
			queueElement: null,
			uploadData: {},
			uploadPath: '/upload',
		};

		for (const key in defaultSettings) {
			this[key] = settings.hasOwnProperty(key) ? settings[key] : defaultSettings[key];
		}

		this.initDropZone();
		this.initForm();
	}

	/**
	 * Sets up the drop zone on the whole browser viewport.
	 */
	initDropZone() {
		if (!this.dropZone) {
			// Create an element to display the drop zone
			this.dropZone = document.createElement('div');
			this.dropZone.id = 'UploadQueue-DropZone';
			this.dropZone.textContent = 'Drop files here to upload';

			// Append the drop zone to the body
			document.body.appendChild(this.dropZone);
		}

		window.eventHandler.add('dragover', (event) => {
			event.preventDefault();

			// Check if the dragged item is a file from your system
			if (Array.from(event.dataTransfer.types).includes('Files')) {
				this.dropZone.classList.add('Visible');
			}
		}, document.body);

		// Hide the dropzone when the drag event ends or leaves the body
		window.eventHandler.add('dragleave', () => {
			this.dropZone.classList.remove('Visible');
		}, document.body);

		window.eventHandler.add('dragend', () => {
			this.dropZone.classList.remove('Visible');
		}, document.body);

		window.eventHandler.add('drop', (event) => {
			event.preventDefault();

			const startIndex = (this.mediaList.dataset.systemOrderStartIndex || this.mediaList.children.length) * 1 + 1 + this.queue.length;

			const files = Array.from(event.dataTransfer.files);
			files.forEach((file, index) => {
				const xhr = new XMLHttpRequest();
				const queueItem = this.createQueueItem(file, xhr, startIndex + index);
				(this.queueElement || this.mediaList).appendChild(queueItem.element);

				this.queue.push(queueItem);
			});

			this.processQueue();

			this.dropZone.classList.remove('Visible');
		}, this.dropZone);
	}

	initForm() {
		if (!this.addForm) {
			return;
		}

		// On change of the file input, upload the file
		this.eventHandler.add('change', (event) => {
			const startIndex = (this.mediaList.dataset.systemOrderStartIndex || this.mediaList.children.length) * 1 + 1 + this.queue.length;

			const files = Array.from(event.target.files);
			files.forEach((file, index) => {
				const xhr = new XMLHttpRequest();
				const queueItem = this.createQueueItem(file, xhr, startIndex + index);
				(this.queueElement || this.mediaList).appendChild(queueItem.element);

				this.queue.push(queueItem);
			});

			this.processQueue();
		}, this.addForm);
	}

	/**
	 * Starts uploading files from the queue, up to the maximum number of concurrent uploads.
	 */
	processQueue() {
		if (this.queue.length > 0 && this.activeUploads === 0) {
			// Emit an event when the queue processing starts
			this.emitEvent('uploadQueueProcessingStarted');
		}

		let queueLength = this.queue.length;
		while (this.activeUploads < this.concurrentUploads && queueLength > 0) {
			const queueItem = this.queue.shift();
			queueLength = this.queue.length;

			// If the element's xhr is false, the file size exceeded the maximum allowed file size
			if (!queueItem.xhr) {
				if (this.queue.length === 0) {
					this.emitEvent('uploadQueueProcessingFinished');
				}

				continue;
			}

			// Upload the file
			this.uploadFile(queueItem);
		}
	}

	/**
	 * Uploads a single file and handles the response.
	 * @param {{element: DocumentFragment, file: File, systemOrder: number, xhr: XMLHttpRequest}} queueItem - The queue item containing the file and the XMLHttpRequest instance.
	 */
	uploadFile(queueItem) {
		// Increment the count of active uploads
		this.activeUploads++;

		// Get the order of the file in the list of media items
		const systemOrder = queueItem.systemOrder;

		// Create a new FormData instance to hold the file and its order
		const formData = new FormData();

		// Append the file and its order to the FormData instance
		formData.append('system_order', systemOrder.toString());

		// Add the additional data if it exists
		for (const key in this.uploadData) {
			if (this.uploadData.hasOwnProperty(key)) {
				formData.append(key, this.uploadData[key]);
			}
		}

		formData.append('file', queueItem.file);

		const listItem = queueItem.element;

		const xhr = queueItem.xhr;

		// Open a POST request to the '/upload' URL
		xhr.open('POST', this.uploadPath, true);

		// Set the HTTP_X_REQUESTED_WITH header
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

		// Set up a progress event handler to update the progress bar as the file uploads
		xhr.upload.onprogress = function (event) {
			if (event.lengthComputable) {
				listItem.querySelector('progress').value = (event.loaded / event.total) * 100;
			}
		};

		// Set up a load event handler to handle the response when the upload completes
		xhr.onload = () => this.onLoad(xhr, listItem, systemOrder);

		// Set up an error event handler to handle any errors that occur during the upload
		xhr.onerror = () => {
			const errorElement = listItem.querySelector('.Status');
			if (errorElement) {
				errorElement.textContent = 'Upload failed: ' + xhr.statusText;
			}

			// Process the next file in the queue
			this.processQueue();
		};

		// Send the POST request with the FormData instance
		xhr.send(formData);
	}

	/**
	 * Handles the response from the server after the upload has completed.
	 *
	 * @param xhr
	 * @param listItem
	 * @param systemOrder
	 */
	onLoad(xhr, listItem, systemOrder) {
		// Decrement the count of active uploads
		this.activeUploads--;

		let data;
		try {
			// Parse the response as JSON
			data = JSON.parse(xhr.responseText);
		} catch (error) {
			// Do nothing
		}

		// Check the status code of the response
		// If the upload was successful, handle the success case
		if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
			// If there is a queue element, remove the list item from it
			if (this.queueElement) {
				this.queueElement.removeChild(listItem);
			}

			// noinspection JSUnresolvedReference
			if (data.html) {
				this.attachElement(data, listItem, systemOrder);
			}
		}
		else {
			// If the status code is not in the 200-299 range, handle the error case
			listItem.classList.remove('UploadQueue-Item-InProgress');
			listItem.classList.add('UploadQueue-Item-Error');
			const errorElement = listItem.querySelector('.Status');
			if (errorElement) {
				errorElement.textContent = data ? data.message : 'Upload failed: ' + xhr.statusText;
			}
		}

		// Emit an event when the upload completes
		this.emitEvent('uploadQueueUploadComplete');

		// If there are no more active uploads and the queue is empty, emit an event
		if (this.activeUploads === 0 && this.queue.length === 0) {
			this.emitEvent('uploadQueueProcessingFinished');
		}

		// Process the next file in the queue
		this.processQueue();
	}

	/**
	 * Attaches the element to the media list or replaces the list item with the new element.
	 *
	 * @param data
	 * @param listItem
	 * @param systemOrder
	 */
	attachElement(data, listItem, systemOrder) {
		// Parse the HTML string into a Document object
		const parser = new DOMParser();
		// noinspection JSUnresolvedReference
		const doc = parser.parseFromString(data.html, 'text/html');

		// Select the first element child
		const element = doc.body.firstElementChild;

		if (element) {
			// If the item is not new and exists, replace the existing item with the new one
			// noinspection JSUnresolvedReference
			if (data.id && this.mediaList.querySelector(`#${data.id}`)) {
				const existingItem = this.mediaList.querySelector(`#${data.id}`);
				existingItem.replaceWith(element);

				// Remove the additionally created list item that is no longer needed when the item is replaced
				listItem.remove();
			}
			// In all other cases, insert the new div into the media list or replace the list item with the new div
			else {
				if (this.queueElement) {
					this.mediaList.insertBefore(element, this.mediaList.children[systemOrder - 1]);
				}
				else {
					listItem.replaceWith(element);
				}
			}
		}
	}

	/**
	 * Creates an item in the queue with a progress bar and a cancel button.
	 * @param {File} file - The file to add to the queue.
	 * @param {XMLHttpRequest|boolean} xhr - The XMLHttpRequest instance for the file upload.
	 * @param {number} systemOrder - The systemOrder of the file in the list of media items.
	 * @returns {{element: Element, file: File, systemOrder: number, xhr: XMLHttpRequest|boolean}} The created list item.
	 */
	createQueueItem(file, xhr, systemOrder) {
		// Select the template and clone it
		const template = document.querySelector('#uploadQueueItemTemplate');
		const listItem = template.content.cloneNode(true).firstElementChild;

		// Select the elements inside the cloned template
		const fileName = listItem.querySelector('.Name');
		const cancelButton = listItem.querySelector('.Button');
		const errorElement = listItem.querySelector('.Status');

		// Set the properties of the elements
		fileName.textContent = file.name;
		fileName.title = file.name;

		// Check if the file size exceeds the maximum allowed file size
		if (file.size > this.maxFileSize) {
			listItem.classList.add('UploadQueue-Item-Error');
			listItem.classList.remove('UploadQueue-Item-InProgress');

			errorElement.textContent = template.dataset.errorMaxFilesize;

			this.eventHandler.add('click', () => {
				(this.queueElement || this.mediaList).removeChild(listItem);

				this.emitEvent('uploadQueueItemCancelled');
			}, cancelButton);

			xhr = false;
		}
		else {
			// Add event listener to the cancel button
			this.eventHandler.add('click', () => {
				// If the upload has completed (XMLHttpRequest is done, might have failed though), remove the list item from the list
				(this.queueElement || this.mediaList).removeChild(listItem);

				// If the upload is still in progress, abort it
				if (xhr.readyState !== 4) {
					xhr.abort();

					// Process the next file in the queue
					this.processQueue();
				}

				this.emitEvent('uploadQueueItemCancelled');

				if (this.queue.length === 0) {
					this.emitEvent('uploadQueueProcessingFinished');
				}
			}, cancelButton);
		}

		return {
			element: listItem,
			file: file,
			systemOrder: systemOrder,
			xhr: xhr,
		};
	}

	/**
	 * Emits a custom event.
	 * @param {string} eventName - The name of the event.
	 */
	emitEvent(eventName) {
		const event = new CustomEvent(eventName, {bubbles: true});

		if (this.queueElement) {
			this.queueElement.dispatchEvent(event);
		}

		this.mediaList.dispatchEvent(event);
	}
}