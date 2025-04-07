//noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import Crop from 'Media/Crop';
import Sortable from 'Media/Sortable';
import Upload from 'Media/Upload';
import Coloris from 'Coloris/Coloris';

export default class MediaController {
	/**
	 * The timeout for fetching media items after an upload
	 * @type {number}
	 */
	afterUploadFetchTimeout
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The sortable selector
	 * @type {string}
	 */
	selector = '#Media-List';
	/**
	 * The sortable instance
	 * @type {Sortable}
	 * @type {import('../modules/Media/Sortable').default}
	 */
	sortable;

	constructor() {
		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}
	}

	/**
	 * Initialize the form
	 * @returns {void}
	 */
	initForm() {
		const cropArea = document.querySelector('.CropArea');
		if (cropArea) {
			new Crop(cropArea);

			const fileInput = document.querySelector('input[type="file"]');
			fileInput.addEventListener('change', () => {
				const fieldset = cropArea.closest('fieldset');
				// Remove the crop area if it exists
				if (fieldset) {
					fieldset.remove();
				}
			});
		}
	}

	/**
	 * Initialize the overview
	 * @returns {void}
	 */
	initOverview() {
		this.bindAutoOverwriteChangeLabel();

		const mediaList = document.querySelector(this.selector);

		if (mediaList) {
			this.sortable = new Sortable(mediaList, {
				deleteButtons: document.querySelectorAll('.ButtonArea .Button-Delete'),
				saveSystemOrderButtons: document.querySelectorAll('.Button-SaveSystemOrder')
			});

			// No add button? Most likely not authorized to add media items
			if (document.querySelector('.Button-Add')) {
				this.upload = new Upload(mediaList, {
					dropZone: document.querySelector('#UploadQueue-DropZone'),
					maxFileSize: document.querySelector('#uploadQueueItemTemplate').dataset.maxFileSize,
					uploadData: {
						media_folder_id: mediaList.dataset.mediaFolderId,
					},
					uploadPath: `${baseUrl}backend/${languageShortcode}/media/add/paginate:false/`
				});
			}
		}
		else {
			// No add button? Most likely not authorized to add media items
			if (!document.querySelector('.Button-Add')) {
				return;
			}

			const overviewTable = document.querySelector('.Overview-Table');
			const uploadQueue = document.querySelector('#UploadQueue');

			this.upload = new Upload(overviewTable.querySelector('tbody'), {
				dropZone: document.querySelector('#UploadQueue-DropZone'),
				maxFileSize: document.querySelector('#uploadQueueItemTemplate').dataset.maxFileSize,
				queueElement: uploadQueue.querySelector('tbody'),
				uploadData: {
					media_folder_id: overviewTable.dataset.mediaFolderId,
				},
				uploadPath: `${baseUrl}backend/${languageShortcode}/media/add/paginate:true/`
			});

			window.eventHandler.add('uploadQueueProcessingStarted', () => {
				uploadQueue.classList.add('Visible');
				overviewTable.classList.add('FetchInProgress');
			});

			this.eventHandler.add('uploadQueueUploadComplete', () => {
				// Clear the fetch timeout to prevent multiple requests
				clearTimeout(this.afterUploadFetchTimeout);

				// Start a timer to fetch the media items after a file has been uploaded
				this.afterUploadFetchTimeout = setTimeout(() => {
					// noinspection JSIgnoredPromiseFromCall
					this.fetchPaginatedOverview();
				}, 1000);
			}, uploadQueue);

			this.eventHandler.add('uploadQueueProcessingFinished', () => {
				if (!uploadQueue.querySelector('.UploadQueue-Item-Error')) {
					uploadQueue.classList.remove('Visible');
				}

				// Initialize the rebuilding of the system order
				// Make a POST request to the '/save-order' URL with the current order and the controller name
				// noinspection JSIgnoredPromiseFromCall
				fetch(`${baseUrl}backend/${languageShortcode}/media/rebuild-system-order/media-folder-id:${overviewTable.dataset.mediaFolderId}`, {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
				});

				// Clear the fetch timeout to prevent multiple requests
				clearTimeout(this.afterUploadFetchTimeout);

				// noinspection JSIgnoredPromiseFromCall
				this.fetchPaginatedOverview();
			}, uploadQueue);
		}
	}

	bindAutoOverwriteChangeLabel() {
		const autoOverwrite = document.querySelector('input[name="upload_auto_overwrite"]');
		const labels = document.querySelectorAll('label[for="UploadAutoOverwrite"]');

		if (!autoOverwrite || !labels.length) {
			return;
		}

		// If the autoOverwrite input is checked, set the label title to the active title
		autoOverwrite.addEventListener('change', event => {
			event.preventDefault();
			event.stopPropagation();

			const newValue = event.target.checked ? 'true' : 'false';

			// Revert the checkbox state to the opposite of the new value
			event.target.checked = newValue !== 'true';

			// Send a fetch request to save the new value
			fetch(`${baseUrl}backend/${languageShortcode}/media/user-configuration/identifier:upload.auto-overwrite/value:${newValue}/`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'X-Requested-With': 'XMLHttpRequest',
				},
			})
			.then(response => response.json())
			.then(response => {
				if (response.success) {
					// Only now we can set the checkbox state to the new value
					event.target.checked = newValue === 'true';

					// Set the label title to the active or inactive title
					labels.forEach(label => {
						// Set the label title to the active or inactive title
						label.title = event.target.checked ? label.dataset.titleActive : label.dataset.titleInactive;
					});
				}
			});
		});
	}

	/**
	 * Fetch the paginated overview table
	 * @returns {Promise<void>} - The fetch promise
	 */
	fetchPaginatedOverview() {
		let url = currentUrl;
		// Make sure the url ends with a slash
		if (!url.endsWith('/')) {
			url += '/';
		}

		return fetch(`${url}paginate:true`, {
			method: 'POST',
			headers: {
				'Accept': 'text/html',
				'Content-Type': 'text/html',
				'X-Requested-With': 'XMLHttpRequest',
			}
		})
		.then(response => response.text())
		.then(html => {
			// Parse the HTML string into a Document object
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			// Select the table
			const table = doc.querySelector('.Overview-Table');

			// Replace the table in the document with the new table
			document.querySelector('.Overview-Table').replaceWith(table);

			this.upload.mediaList = table.querySelector('tbody');
		})
		.catch(error => {
			console.error('There has been a problem with the fetch operation:', error);
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {MediaController}
 */
window.MediaController = MediaController;
