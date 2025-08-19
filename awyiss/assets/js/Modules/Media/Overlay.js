// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import DraggableSidebarToggle from 'DraggableSidebarToggle';
import NestedListHandler from 'NestedListHandler';
import Crop from 'Media/Crop';
import Selectors from 'Media/Selectors';
import Sortable from 'Media/Sortable';
import Upload from 'Media/Upload';
import {Sortable as SortableJS} from 'SortableJS/sortable';

export default class Overlay {
	/**
	 * The crop area instance
	 * @type {Crop}
	 */
	static cropArea = null;

	/**
	 * The id of the active folder
	 * If the opener requests another folder than this,
	 * a new fetch request will be made.
	 * @type {int}
	 */
	activeFolderId;
	/**
	 * The overlay close button
	 * @type {HTMLElement}
	 */
	closeButton;
	/**
	 * The overlay element
	 * @type {HTMLDialogElement}
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
	 * The element that opened the overlay.
	 * Will receive the selected media item when clicking on the use button.
	 *
	 * @type {HTMLElement|function|null}
	 */
	opener = null;
	/**
	 * The selector for elements that open the overlay.
	 *
	 * @type {string}
	 */
	selector = 'a[href$="/media/overview/"], a[href*="/media/overview/media-folder-id:"]';
	/**
	 * The selectors instance.
	 * @type {Selectors}
	 */
	selectors = null;

	/**
	 * Initialize the media overlay.
	 */
	constructor() {
		// If the current controller is Media, we don't need to initialize the overlay.
		if (document.body.classList.contains('MediaController')) {
			return;
		}

		// Bind a click event to all links whose href ends with '/media/overview/',
		// or contain '/media/overview/media-folder-id:'.
		const links = document.querySelectorAll(this.selector);
		links.forEach(link => {
			this.eventHandler.add('click', this.openOverlay.bind(this), link);
		});

		// Initialize the selectors
		this.selectors = new Selectors();
		this.selectors.overlay = this;

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		// Listen for hash changes
		this.eventHandler.add('hashchange', this.handleHashChange.bind(this), window);
		// Check hash on initial page load
		this.handleHashChange();
	}

	/**
	 * Binds the auto-overwrite checkbox input and its associated label to handle change events.
	 * Updates the label title based on the checkbox state and sends the updated value to the server.
	 *
	 * @return {void}
	 */
	bindAutoOverwriteChangeLabel() {
		const autoOverwrite = this.element.querySelector('input[name="upload_auto_overwrite"]');
		const label = this.element.querySelector('label[for="UploadAutoOverwrite"]');

		if (!autoOverwrite || !label) {
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
					label.title = event.target.checked ? label.dataset.titleActive : label.dataset.titleInactive;
				}
			});
		});
	}

	/**
	 * Bind a click event to the close button.
	 * When the close button is clicked, the overlay will be hidden.
	 *
	 * @return {void}
	 */
	bindCloseButton() {
		this.closeButton = this.element.querySelector('.Button-Close');

		// Bind a click event to the close button
		window.eventHandler.add('click', () => {
			this.element.close();

			// If the url contains the media hash, remove it by going back to the previous state
			if (window.location.hash === '#Media') {
				window.history.back();
			}

			// Check if the OverlayForm is visible and contains the media folders form
			const overlayForm = document.querySelector('#OverlayForm');
			if (
				overlayForm?.open &&
				(
					overlayForm.querySelector('.Media.Form') ||
					overlayForm.querySelector('.MediaFolders.Form')
				)
			) {
				// Close the overlay form
				window.overlayForm.closeOverlay();
			}
		}, this.closeButton);
	}

	/**
	 * Open the media overlay.
	 * If the overlay element doesn't exist yet, fetch it.
	 *
	 * @param {MouseEvent} event
	 * @param {int|null} mediaFolderId
	 */
	initOverlay(event, mediaFolderId = null) {
		// Maybe it already exists in the DOM. If not, we'll fetch it later.
		this.element = document.querySelector('#MediaOverlay');

		if (this.element) {
			// There's no reason why they overlay should exist, bit if it does, we'll work with it.
			this.#initComponents();

			return;
		}

		// Create a new overlay element
		const overlayPlaceholder = document.createElement('dialog');
		overlayPlaceholder.id = 'MediaOverlay';
		overlayPlaceholder.classList.add('FetchInProgress');
		document.body.appendChild(overlayPlaceholder);

		overlayPlaceholder.showModal();

		let url = `${baseUrl}backend/${languageShortcode}/media/overview/paginate:false/`
		if (event?.target && event.target.matches('a[href]')) {
			url = event.target.href + 'paginate:false/';
		}

		if (mediaFolderId) {
			url += `media-folder-id:${mediaFolderId}/`;
		}

		if (event.ctrlKey || event.metaKey) {
			url += 'include-hidden:1/';
		}

		// Fetch the overlay element
		return fetch(url, {
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

			// Find the active folder
			const activeFolder = this.folderList.querySelector('.MediaFolders-ListItem.Active');
			if (activeFolder) {
				this.activeFolderId = parseInt(activeFolder.id.replace(/^\D+/g, ''));
			}

			this.folderList.dataset.includeHidden = event.ctrlKey || event.metaKey ? 'true' : 'false';

			// Focus the overlay
			this.element.showModal();
			//this.element.focus();

			return html;
		});
	}

	/**
	 * Initialize the overlay components.
	 * This method is called after the overlay element has been fetched.
	 *
	 * @return {void}
	 */
	#initComponents() {
		this.folderList = this.element.querySelector('#MediaFolders-List');
		this.mediaList = this.element.querySelector('#Media-List');

		this.bindCloseButton();

		this.bindAutoOverwriteChangeLabel();

		// Bind the click event to the folder list items
		this.bindFolderListItemClick();

		this.eventHandler.add('cancel', (event) => {
			event.preventDefault();
			event.stopPropagation();
		}, this.element);
		this.eventHandler.add('click', this.handleMediaItemClick.bind(this), this.mediaList);
		this.eventHandler.add('dblclick', this.handleMediaItemDoubleClick.bind(this), this.mediaList);

		this.bindOverlayFormLoadedEvent();

		this.bindFolderSaveEvent();

		this.initSortableReceiver();

		this.initUpload();

		// If the opener exists, select the media item in the overlay
		// noinspection JSUnresolvedReference
		if (this.opener?.mediaIdInput) {
			// noinspection JSUnresolvedReference
			const mediaId = this.opener.mediaIdInput.value;
			const mediaItem = this.mediaList.querySelector(`#Media-ListItem${mediaId}`);
			if (mediaItem) {
				mediaItem.classList.add('Selected');
			}
		}

		const useButtons = this.element.querySelectorAll('.Button-UseFiles');
		useButtons.forEach(button => {
			this.addUseButtonEvents(button);
		});

		const buttonAreaToggle = this.element.querySelector('#MediaButtonArea-Toggle');
		if (buttonAreaToggle) {
			this.addToggleButtonEvents(buttonAreaToggle);
		}

		const mediaFoldersToggle = this.element.querySelector('#MediaFolders-Toggle');
		if (mediaFoldersToggle) {
			this.addMediaFoldersToggleButtonEvents(mediaFoldersToggle);
		}
	}

	/**
	 * Add the events for the media folders toggle button.
	 *
	 * @param {HTMLElement} mediaFoldersToggle
	 */
	addMediaFoldersToggleButtonEvents(mediaFoldersToggle) {
		this.eventHandler.add('click', () => {
			this.element.querySelector('#MediaFolders-List').classList.toggle('Visible');
		}, mediaFoldersToggle);

		this.eventHandler.add('click', event => {
			if (
				event.target.closest('#MediaFolders-List') ||
				event.target.matches('#MediaFolders-Toggle')
			) {
				return;
			}

			this.element.querySelector('#MediaFolders-List').classList.remove('Visible');
		}, this.element);

		const draggable = new DraggableSidebarToggle(
			mediaFoldersToggle,
			this.element.querySelector('#MediaFolders-List'),
			document.body.clientWidth <= 420
				? 240
				: (document.body.clientWidth <= 540 ? 290 : 300),
			'left',
		);

		this.eventHandler.add('resize', requestAnimationFrame.bind(window, () => {
			// Set the max width in the draggable instance
			draggable.maxSize = document.body.clientWidth <= 420
				? 240
				: (document.body.clientWidth <= 540 ? 290 : 300);
		}), window);

		draggable.oldUpdatePosition = draggable.updatePosition;
		draggable.updatePosition = function () {
			this.oldUpdatePosition();

			// Y is an offset between 0 and 20, depending on the progress of the x drag
			let y = this.currentX / this.maxSize * 20;

			// Update DOM elements
			this.buttonAreaToggle.style.transform = `translate(${this.currentX}px, ${y}px)`;
		};
	}

	/**
	 * Add the click event to the use button.
	 *
	 * @param {HTMLElement} button
	 */
	addUseButtonEvents(button) {
		this.eventHandler.add('click', () => {
			if (typeof this.opener === 'function') {
				// Find all selected items
				const firstSelectedItem = this.mediaList.querySelector('.Media-ListItem.Selected');

				const link = firstSelectedItem.querySelector('.Link');

				let href = link.href;

				if (href.indexOf(baseUrl) === 0) {
					href = href.substring(baseUrl.length);
				}

				this.opener(href);

				// Close the overlay
				this.closeButton.dispatchEvent(new MouseEvent('click'));

				return;
			}

			// noinspection JSUnresolvedReference
			if (typeof this.opener.useMedia !== 'function') {
				return;
			}

			// Find all selected items
			const selectedItems = this.mediaList.querySelectorAll('.Media-ListItem.Selected');

			selectedItems.forEach(item => {
				// noinspection JSUnresolvedReference
				this.opener.useMedia(item);
				item.classList.remove('Selected');
			});

			// Close the overlay
			this.closeButton.dispatchEvent(new MouseEvent('click'));
		}, button);
	}

	/**
	 * Add the toggle button events to the button area toggle.
	 *
	 * @param {HTMLElement} buttonAreaToggle
	 */
	addToggleButtonEvents(buttonAreaToggle) {
		this.eventHandler.add('click', () => {
			this.element.querySelector('.ButtonArea')?.classList.toggle('Visible');
		}, buttonAreaToggle);

		this.eventHandler.add('click', event => {
			if (
				event.target.closest('.ButtonArea') ||
				event.target.matches('#MediaButtonArea-Toggle')
			) {
				return;
			}

			this.element.querySelector('.ButtonArea')?.classList.remove('Visible');
		}, this.element);

		const draggable = new DraggableSidebarToggle(
			buttonAreaToggle,
			this.element.querySelector('.ButtonArea'),
			document.body.clientWidth <= 420
				? 240
				: (document.body.clientWidth <= 540 ? 290 : 300),
		);

		this.eventHandler.add('resize', requestAnimationFrame.bind(window, () => {
			// Set the max width in the draggable instance
			draggable.maxSize = document.body.clientWidth <= 420
				? 240
				: (document.body.clientWidth <= 540 ? 290 : 300);
		}), window);

		draggable.oldUpdatePosition = draggable.updatePosition;
		draggable.updatePosition = function () {
			this.oldUpdatePosition();

			// Y is an offset between 0 and 20, depending on the progress of the x drag
			let y = this.currentX / this.maxSize * -20;

			// Update DOM elements
			this.buttonAreaToggle.style.transform = `translate(${this.currentX}px, ${y}px)`;
		};
	}

	/**
	 * Open the overlay.
	 * If the overlay element doesn't exist yet, fetch it.
	 *
	 * @param {MouseEvent|object} event
	 * @param {int|null} mediaFolderId
	 */
	openOverlay(event, mediaFolderId = null) {
		event.preventDefault();

		// noinspection JSUnresolvedReference
		this.opener = event.detail.opener || null;

		if (!this.element) {
			// noinspection JSIgnoredPromiseFromCall
			this.initOverlay(event, mediaFolderId);
		}
		else {
			// Show the overlay
			this.element.showModal();

			// If the opener exists, select the media item in the overlay
			// noinspection JSUnresolvedReference
			if (this.opener?.mediaIdInput) {
				// noinspection JSUnresolvedReference
				const mediaId = this.opener.mediaIdInput.value;
				const mediaItem = this.mediaList.querySelector(`#Media-ListItem${mediaId}`);
				if (mediaItem) {
					mediaItem.classList.add('Selected');
				}
			}

			this.sortable.toggleButtonState(this.sortable.multiSelection.getSelectedItems().length);

			if (mediaFolderId || event.target?.matches('a[href]')) {
				this.ensureFolderIsVisible(mediaFolderId ? parseInt(mediaFolderId) : event.target);
			}

			if (
				(
					(event.ctrlKey || event.metaKey) &&
					this.folderList.dataset.includeHidden === 'false'
				) ||
				(
					!event.ctrlKey &&
					!event.metaKey &&
					this.folderList.dataset.includeHidden === 'true'
				)
			) {
				// If the includeHidden state is different from the current state, fetch the folder list again
				this.fetchFolderList(this.activeFolderId, languageShortcode, event.ctrlKey || event.metaKey).then(html => {
					// noinspection JSCheckFunctionSignatures
					this.replaceMediaList(html, false);
				});
			}

			// Focus the overlay
			this.element.focus();
		}

		// If the url doesn't contain the media hash, add it
		if (!window.location.hash) {
			// Add the media hash to the URL
			window.history.pushState({
				includeHidden: event.ctrlKey || event.metaKey,
				url: event.target?.matches('a[href]') ? event.target.href : null,
			}, '', `${currentUrl}#Media`);
		}
		else {
			window.history.replaceState({
				includeHidden: event.ctrlKey || event.metaKey,
				url: event.target?.matches('a[href]') ? event.target.href : null,
			}, '', `${currentUrl}#Media`);
		}
	}

	/**
	 * Bind the click event to the folder list items.
	 * When a folder list item is clicked, the media items of that folder will be fetched.
	 *
	 * @return {void}
	 */
	bindFolderListItemClick() {
		this.folderList.querySelectorAll('.MediaFolders-ListItem').forEach(listItem => {
			window.eventHandler.add('click', this.fetchFolderFiles.bind(this), listItem);
		});

		// Create a new instance of the nested list handler
		new NestedListHandler('ul#MediaFolders-List');
	}

	/**
	 * Bind the event to the `overlayFormLoaded`-event.
	 *
	 * @return {void}
	 */
	bindOverlayFormLoadedEvent() {
		window.eventHandler.add('overlayFormLoaded', (event) => {
			const form = event.detail.form.parentElement;

			if (form.classList.contains('Media')) {
				const cropAreaElement = document.querySelector('.CropArea');
				if (cropAreaElement) {
					if (!Overlay.cropArea) {
						Overlay.cropArea = new Crop(cropAreaElement);
					}
					else {
						// noinspection JSUndefinedPropertyAssignment
						Overlay.cropArea.cropFrame = null;
					}
				}
			}
		});
	}

	/**
	 * Bind the event to the folder save event.
	 *
	 * @return {void}
	 */
	bindFolderSaveEvent() {
		window.eventHandler.add('overlayFormSubmitted', (event) => {
			const form = event.detail.form;
			const formParent = form.parentElement;

			if (formParent.matches('.MediaFolders.Add')) {
				// noinspection JSIgnoredPromiseFromCall
				this.fetchFolderList(this.activeFolderId, languageShortcode);
			}
			else if (formParent.matches('.Media.Edit')) {
				// noinspection JSUndefinedPropertyAssignment
				Overlay.cropArea.cropFrame = null;
				// After updating a file, we need to fetch the media items again
				this.folderList.querySelector('.Active').dispatchEvent(new Event('click'));
			}
		});
	}

	/**
	 * Ensure that the folder of the clicked target is visible
	 * in the folder list.
	 * If it isn't, fetch the folder list again.
	 *
	 * @param {HTMLAnchorElement|int} target
	 *
	 * @return {void}
	 */
	ensureFolderIsVisible(target) {
		let folderId;

		if (typeof target === 'number') {
			folderId = target;
		}
		else {
			// Get the folder ID from the link
			const url = new URL(target.href);
			const parts = url.pathname.split('/');
			const part = parts.filter(part => part.includes('media-folder-id:'));

			if (part.length === 0) {
				return;
			}

			folderId = parseInt(part[0].replace('media-folder-id:', ''));

			if (folderId === this.activeFolderId) {
				return;
			}
		}

		const folder = this.folderList.querySelector(`#MediaFolders-ListItem${folderId}`);
		if (folder) {
			if (typeof target !== 'number' && !folder.classList.contains('Active')) {
				folder.dispatchEvent(new Event('click'));
			}

			return;
		}

		// If the folder doesn't exist, fetch the folder list again
		this.fetchFolderList(folderId, languageShortcode).then(html => {
			// noinspection JSCheckFunctionSignatures
			this.replaceMediaList(html, false);
		});
	}

	/**
	 * Fetch the media items of a folder.
	 *
	 * @param {MouseEvent} event
	 */
	fetchFolderFiles(event) {
		if (event.target.matches('.NestedListToggle')) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const listItem = event.currentTarget;
		const folderId = parseInt(listItem.id.replace(/^\D+/g, ''));

		this._fetchFolderFiles(folderId, listItem.dataset.languageShortcode || languageShortcode).then(() => {
			listItem.classList.add('Active');
			listItem.sortable.option('disabled', true);
		});
	}

	/**
	 * Fetch the folder list and replace the current folder list with the new one.
	 *
	 * @param {int} folderId
	 * @param {string} languageShortcode
	 * @param {boolean} includeHidden
	 *
	 * @returns {Promise<void>}
	 */
	fetchFolderList(folderId, languageShortcode, includeHidden = false) {
		// noinspection JSValidateTypes
		includeHidden = includeHidden ? 'include-hidden:1/' : '';

		// noinspection JSValidateTypes
		return fetch(`${baseUrl}backend/${languageShortcode}/media/overview/media-folder-id:${folderId}/paginate:false/${includeHidden}`, {
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

			this.folderList.dataset.includeHidden = includeHidden ? 'true' : 'false';

			this.bindFolderListItemClick();

			this.initSortableReceiver();

			return html;
		});
	}

	/**
	 * Fetch the media items of a folder.
	 *
	 * @param {int} folderId
	 * @param {string} languageShortcode
	 * @returns {Promise<void>}
	 */
	async _fetchFolderFiles(folderId, languageShortcode) {
		if (this.mediaList.classList.contains('FetchInProgress')) {
			return;
		}

		// Disable the delete buttons
		this.sortable.deleteButtons.forEach(button => button.disabled = true);

		this.upload.uploadData.media_folder_id = folderId;

		this.mediaList.classList.add('FetchInProgress');

		// noinspection JSValidateTypes
		return fetch(`${baseUrl}backend/${languageShortcode}/media/overview/media-folder-id:${folderId}/paginate:false/`, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		})
		.then(response => response.text())
		.then(html => {
			this.replaceMediaList(html);

			// Update the media folder ID in the data attribute
			// noinspection JSValidateTypes
			this.mediaList.dataset.mediaFolderId = folderId;

			this.activeFolderId = folderId;

			return html;
		});
	}

	/**
	 * Extract the media items from the HTML string and replace the current media list with the new one.
	 *
	 * @param {string} html
	 * @param {boolean} removeActiveStatus - If true, the active status of the folder will be removed.
	 */
	replaceMediaList(html, removeActiveStatus = true) {
		// Parse the HTML string into a Document object
		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');

		const newMediaList = doc.querySelector('#Media-List')

		// Remove all children from the media list and replace them with the children of the new media list
		this.mediaList.innerHTML = newMediaList?.innerHTML || '';

		this.mediaList.classList.remove('FetchInProgress');

		if (removeActiveStatus) {
			const activeFolder = this.folderList.querySelector('.Active');
			if (activeFolder) {
				activeFolder.classList.remove('Active');
				activeFolder.sortable.option('disabled', false);
			}
		}

		this.sortable.saveOrderButtons.forEach(button => {
			button.disabled = true;
			button.classList.toggle('Button-Success', false);
		});
	}

	/**
	 * Handle the click event on a media item.
	 * If the click event is on the edit button, open the overlay form.
	 *
	 * @param {MouseEvent} event
	 */
	handleMediaItemClick(event) {
		if (!event.target.matches('.Button-Edit')) {
			return;
		}

		event.preventDefault();

		window.overlayForm.openOverlay(event);
	}

	/**
	 * Handle the double click event on a media item.
	 *
	 * @param {MouseEvent} event
	 */
	handleMediaItemDoubleClick(event) {
		if (!event.target.closest('.Media-ListItem') || !this.opener) {
			return;
		}

		if (typeof this.opener === 'function') {
			const element = event.target.closest('.Media-ListItem');
			const link = element.querySelector('.Link');

			let href = link.href;

			if (href.indexOf(baseUrl) === 0) {
				href = href.substring(baseUrl.length);
			}

			this.opener(href);

			// Close the overlay
			this.closeButton.dispatchEvent(new MouseEvent('click'));

			return;
		}

		// If the opener has a useMedia method, call it with the media item.
		// noinspection JSUnresolvedReference
		if (typeof this.opener.useMedia === 'function') {
			// noinspection JSUnresolvedReference
			this.opener.useMedia(event.target.closest('.Media-ListItem'));

			// Find all selected items
			const selectedItems = this.mediaList.querySelectorAll('.Media-ListItem.Selected');
			selectedItems.forEach(item => item.classList.remove('Selected'));

			// Close the overlay
			this.closeButton.dispatchEvent(new MouseEvent('click'));
		}
	}


	/**
	 * Initialize the sortable for media items in the folder list
	 *
	 * @return {void}
	 */
	initSortableReceiver() {
		this.sortable = new Sortable(this.mediaList, {
			deleteButtons: this.element.querySelectorAll('.ButtonArea .Button-Delete'),
			saveSystemOrderButtons: this.element.querySelectorAll('.Button-SaveSystemOrder'),
			useFilesButtons: this.element.querySelectorAll('.Button-UseFiles'),
		});
		this.sortable.overlay = this;

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
	 *
	 * @return {void}
	 */
	initUpload() {
		if (!parseInt(this.mediaList.dataset.canCreate)) {
			return;
		}

		this.upload = new Upload(this.mediaList, {
			addForm: this.element.querySelector('.Button-Add form'),
			dropZone: this.element.querySelector('#UploadQueue-DropZone'),
			maxFileSize: this.element.querySelector('#uploadQueueItemTemplate').dataset.maxFileSize,
			uploadData: {
				media_folder_id: this.mediaList.dataset.mediaFolderId,
			},
			uploadPath: `${baseUrl}backend/${languageShortcode}/media/add/`
		});
	}


	/**
	 * Move a media item to a different folder.
	 *
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
				const response = await fetch(`${baseUrl}backend/${languageShortcode}/media/edit/id:${mediaId}`, {
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

	/**
	 * Handle URL hash changes
	 *
	 * @returns {void}
	 */
	handleHashChange() {
		const hasMediaHash = window.location.hash === '#Media';

		if (hasMediaHash) {
			let url = `${baseUrl}backend/${languageShortcode}/media/overview/`;

			if (window.history.state?.url) {
				url = window.history.state.url;
			}

			// Check if an element with the url as href exists
			let element = document.querySelector(`a[href="${url}"]`);

			if (!element && url.startsWith(baseUrl)) {
				url = url.substring(baseUrl.length - folder.length);
				element = document.querySelector(`a[href="${url}"]`);
			}

			if (element) {
				// Open the overlay
				this.openOverlay({
					ctrlKey: window.history.state?.includeHidden || false,
					metaKey: window.history.state?.includeHidden || false,
					detail: {},
					preventDefault: () => {},
					stopPropagation: () => {},
					target: element,
				});
			}
		}
		else {
			// If the media overlay is open, close it
			if (this.element?.open) {
				this.element.close();
				this.closeButton.dispatchEvent(new MouseEvent('click'));
			}
		}
	}

	/**
	 * Observe mutations in the DOM.
	 *
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		// If nodes were added
		if (mutation.addedNodes.length > 0) {
			// Iterate over each added node
			mutation.addedNodes.forEach(node => {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}

				if (node.matches(this.selector)) {
					this.eventHandler.add('click', this.openOverlay.bind(this), node);
				}

				const links = node.querySelectorAll(this.selector);
				links.forEach(link => {
					this.eventHandler.add('click', this.openOverlay.bind(this), link);
				});
			})
		}
	}
}