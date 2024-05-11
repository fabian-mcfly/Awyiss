// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import NestedListHandler from 'NestedListHandler';
import Crop from 'Media/Crop';
import Sortable from 'Media/Sortable';
import Upload from 'Media/Upload';
import {Sortable as SortableJS} from '../../SortableJS/sortable.core.esm.js';
import Coloris from '../../Coloris/Coloris';

export default class Overlay {
	/**
	 * The crop area instance
	 * @type {Crop}
	 */
	static cropArea = null;

	/**
	 * The overlay close button
	 * @type {HTMLElement}
	 */
	closeButton;
	/**
	 * The overlay element
	 * @type {HTMLElement}
	 */
	element;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The folder list element
	 * @type {HTMLElement}
	 */
	folderList;
	/**
	 * The media list element
	 * @type {HTMLElement}
	 */
	mediaList;

	/**
	 * Initialize the media overlay.
	 */
	constructor() {
		// If the current controller is Media, we don't need to initialize the overlay.
		if (document.documentElement.classList.contains('MediaController')) {
			return;
		}

		// Bind a click event to all links which href ends with '/media/overview/',
		// or contain '/media/overview/media-folder-id:'.
		const links = document.querySelectorAll('a[href$="/media/overview/"], a[href*="/media/overview/media-folder-id:"]');
		for (const link of links) {
			this.eventHandler.add('click', this.openOverlay.bind(this), link);
		}
	}

	/**
	 * Bind a click event to the close button.
	 * When the close button is clicked, the overlay will be hidden.
	 */
	bindCloseButton() {
		// Bind a click event to the close button
		window.eventHandler.add('click', () => {
			this.element.classList.remove('Visible');
		}, this.element.querySelector('.Button-Close'));
	}

	/**
	 * Open the media overlay.
	 * If the overlay element doesn't exist yet, fetch it.
	 *
	 * @param {boolean} [show=false] - Whether to show the overlay immediately after initialization.
	 */
	initOverlay(show = false) {
		// Maybe it already exists in the DOM. If not, we'll fetch it later.
		this.element = document.querySelector('#MediaOverlay');

		if (!this.element) {
			// Create a new overlay element
			const overlayPlaceholder = document.createElement('div');
			overlayPlaceholder.id = 'MediaOverlay';
			document.body.appendChild(overlayPlaceholder);

			setTimeout(() => {
				overlayPlaceholder.classList.add('FetchInProgress');
			}, 100);

			// Fetch the overlay element
			fetch(`/backend/${languageShortcode}/media/overview/paginate:false/`, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			})
			.then(response => response.text())
			.then(html => {
				// Parse the HTML string into a Document object
				const parser = new DOMParser();
				const doc = parser.parseFromString(html, 'text/html');

				this.element = doc.querySelector('#MediaOverlay');

				overlayPlaceholder.replaceWith(this.element);

				this.#initComponents();

				if (show) {
					this.element.classList.add('Visible');
				}
			});
		}
		else {
			// There's no reason why they overlay should exist, bit if it does, we'll work with it.
			this.#initComponents();
		}
	}

	/**
	 * Initialize the overlay components.
	 * This method is called after the overlay element has been fetched.
	 */
	#initComponents() {
		this.folderList = this.element.querySelector('#MediaFolders-List');
		this.mediaList = this.element.querySelector('#Media-List');

		this.bindCloseButton();

		// Bind the click event to the folder list items
		this.bindFolderListItemClick();

		this.eventHandler.add('click', this.handleMediaItemClick.bind(this), this.mediaList);

		this.bindOverlayFormLoadedEvent();

		this.bindFolderSaveEvent();

		this.initSortableReceiver();

		this.initUpload();
	}

	/**
	 * Open the overlay.
	 * If the overlay element doesn't exist yet, fetch it.
	 * @param {Event} event
	 */
	openOverlay(event) {
		event.preventDefault();

		if (!this.element) {
			this.initOverlay();
		}
		else {
			// Show the overlay
			this.element.classList.add('Visible');
		}
	}

	/**
	 * Bind the click event to the folder list items.
	 * When a folder list item is clicked, the media items of that folder will be fetched.
	 */
	bindFolderListItemClick() {
		this.folderList.querySelectorAll('.MediaFolders-ListItem').forEach(listItem => {
			window.eventHandler.add('click', this.fetchFolderFiles.bind(this), listItem);
		});

		// Create a new instance of the nested list handler
		new NestedListHandler('ul#MediaFolders-List');
	}

	/**
	 * Bind the event to the overlay form loaded event.
	 */
	bindOverlayFormLoadedEvent() {
		window.eventHandler.add('overlayFormLoaded', (event) => {
			const form = event.detail.form.parentElement;

			if (form.classList.contains('Media')) {
				// After loading the media form, initialize the color picker
				const coloris = new Coloris({
					element: form.querySelector('input[name="average_color"]'),
					theme: 'large',
					themeMode: document.documentElement.classList.contains('🌚') ? 'dark' : 'light',
				});

				const cropAreaElement = document.querySelector('.CropArea');
				if (cropAreaElement) {
					if (!Overlay.cropArea) {
						Overlay.cropArea = new Crop(cropAreaElement);
					}
					else {
						Overlay.cropArea.cropFrame = null;
					}
				}
			}
		});
	}

	/**
	 * Bind the event to the folder save event.
	 */
	bindFolderSaveEvent() {
		window.eventHandler.add('overlayFormSubmitted', (event) => {
			const form = event.detail.form;
			const formParent = form.parentElement;

			if (formParent.matches('.MediaFolders.Add')) {
				// Fetch the folder list again
				fetch(`/backend/${languageShortcode}/media/overview/paginate:false/`, {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
					},
				})
				.then(response => response.text())
				.then(html => {
					// Parse the HTML string into a Document object
					const parser = new DOMParser();
					const doc = parser.parseFromString(html, 'text/html');

					const folderList = doc.querySelector('#MediaFolders-List');

					this.folderList.replaceWith(folderList);

					this.folderList = folderList;

					this.bindFolderListItemClick();

					this.initSortableReceiver();
				});
			}
			else if (formParent.matches('.Media.Edit')) {
				Overlay.cropArea.cropFrame = null;
				// After updating a file, we need to fetch the media items again
				this.folderList.querySelector('.Active').dispatchEvent(new Event('click'));
			}
		});
	}

	/**
	 * Fetch the media items of a folder.
	 * @param {Event} event
	 */
	fetchFolderFiles(event) {
		if (event.target.matches('.NestedListToggle')) {
			return;
		}

		event.preventDefault();

		// Disable the delete buttons
		this.sortable.deleteButtons.forEach(button => button.disabled = true);

		if (this.mediaList.classList.contains('FetchInProgress')) {
			return;
		}

		const listItem = event.currentTarget;
		const folderId = parseInt(listItem.id.replace(/^\D+/g, ''));

		this.upload.uploadData.media_folder_id = folderId;

		this.mediaList.classList.add('FetchInProgress');

		fetch(`/backend/${listItem.dataset.languageShortcode || languageShortcode}/media/overview/media-folder-id:${folderId}/paginate:false/`, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		})
		.then(response => response.text())
		.then(html => {
			// Parse the HTML string into a Document object
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			const newMediaList = doc.querySelector('#Media-List')

			// Remove all children from the media list and replace them with the children of the new media list
			this.mediaList.innerHTML = newMediaList?.innerHTML || '';

			// Update the media folder ID in the data attribute
			// noinspection JSValidateTypes
			this.mediaList.dataset.mediaFolderId = folderId;

			this.mediaList.classList.remove('FetchInProgress');

			const activeFolder = this.folderList.querySelector('.Active');
			if (activeFolder) {
				activeFolder.classList.remove('Active');
				activeFolder.sortable.option('disabled', false);
			}

			listItem.classList.add('Active');
			listItem.sortable.option('disabled', true);
		});
	}


	handleMediaItemClick(event) {
		if (!event.target.matches('.Button-Edit')) {
			return;
		}

		event.preventDefault();

		window.overlayForm.openOverlay(event);
	}


	/**
	 * Initialize the sortable for media items in the folder list
	 */
	initSortableReceiver() {
		this.sortable = new Sortable(this.mediaList, {
			deleteButtons: this.element.querySelectorAll('.ButtonArea .Button-Delete'),
			saveSystemOrderButtons: this.element.querySelectorAll('.Button-SaveSystemOrder')
		});

		// For all folders, add a faux sortable list
		const folders = this.folderList.querySelectorAll('.MediaFolders-ListItem');
		folders.forEach(folder => {
			const list = document.createElement('ul');
			list.classList.add('MediaFolders-ListItem-Droppable');
			folder.querySelector('.ListItem-Inner').appendChild(list);

			folder.sortable = SortableJS.create(list, {
				chosenClass: 'SortableChosen',
				dataIdAttr: 'id',
				fallbackOnBody: true,
				ghostClass: 'SortableGhost',
				group: 'Media',
				//invertSwap: true,
				preventOnFilter: false,
				swapThreshold: .6,
				onAdd: (event) => {
					setTimeout(() => {
						// noinspection JSIgnoredPromiseFromCall
						this.moveMediaItems(event, folder, list);
					}, 100);
				},
			});

			// If the folder is active, disable the sortable
			if (folder.classList.contains('Active')) {
				folder.sortable.option('disabled', true);
			}
		});
	}

	/**
	 * Initialize the upload dropzone & queue.
	 */
	initUpload() {
		this.upload = new Upload(this.mediaList, {
			dropZone: this.element.querySelector('#UploadQueue-DropZone'),
			maxFileSize: this.element.querySelector('#uploadQueueItemTemplate').dataset.maxFileSize,
			uploadData: {
				media_folder_id: this.mediaList.dataset.mediaFolderId,
			},
			uploadPath: `/backend/${languageShortcode}/media/add/`
		});
	}


	/**
	 * Move a media item to a different folder.
	 * @param {Event} event
	 * @param {HTMLElement} folder
	 * @param {HTMLElement} list - The sortable list of the folder
	 */
	async moveMediaItems(event, folder, list) {
		this.mediaList.classList.add('FetchInProgress');

		const folderId = folder.id.replace(/^\D+/g, '');

		// Get all files from the list
		const items = Array.from(list.children);

		// Move all items to the new folder
		for (const item of items) {
			const mediaId = item.id.replace(/^\D+/g, '');

			try {
				const response = await fetch(`/backend/${languageShortcode}/media/edit/id:${mediaId}`, {
					method: 'PATCH',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					body: JSON.stringify({
						media_folder_id: folderId,
						system_order: 99999,
					}),
				});

				const data = await response.json();

				if (data.success) {
					item.remove();
				}
				else {
					// Move the item back to the original folder
					this.mediaList.appendChild(item);
				}
			} catch (error) {
				// Move the item back to the original folder
				this.mediaList.appendChild(item);
				console.error('There has been a problem with the fetch operation:', error);
			}
		}

		this.mediaList.classList.remove('FetchInProgress');

		// If there are items in the media list, we need to update the system order
		if (this.mediaList.children.length > 0) {
			this.sortable.saveSystemOrder();
		}
	}
}