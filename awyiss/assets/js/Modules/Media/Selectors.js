// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import NestedListHandler from 'NestedListHandler';
import Sortable from 'SortableJS/sortable';

export default class Selectors {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The single media selector.
	 * @type {string}
	 */
	singleFileSelector = '.MediaSelector-SingleFile';
	/**
	 * The multi media selector.
	 * @type {string}
	 */
	multiFileSelector = '.MediaSelector-MultiFile';
	/**
	 * The overlay instance.
	 * @type {Overlay}
	 */
	overlay = null;

	constructor() {
		// Initialize the selectors
		document.querySelectorAll(this.singleFileSelector).forEach(this.initSelector.bind(this));
		document.querySelectorAll(this.multiFileSelector).forEach(this.initSelector.bind(this));

		new MediaFolderSelect();

		// Observe the document for new elements that match the selectors
		const observer = window.observer;
		observer.addObserver(this.observeSelectors.bind(this));
	}

	/**
	 * @param {HTMLElement} element
	 */
	initSelector(element) {
		const preview = element.querySelector('.MediaSelector-Preview');

		if (element.matches(this.singleFileSelector)) {
			this.eventHandler.add('click', this.openOverlay.bind(this), preview);

			// Bind the remove handler
			const removeButton = element.querySelector('.MediaSelector-Remove');
			if (removeButton) {
				this.eventHandler.add('click', this.removeMedia.bind(this, element), removeButton);
			}
		}
		else {
			const selector = element.querySelector('.MediaSelector-MediaSelect');
			this.eventHandler.add('click', this.openOverlay.bind(this), selector);
			this.eventHandler.add('click', this.removeMedia.bind(this, element), preview);

			element.sortable = Sortable.create(preview, {
				chosenClass: 'SortableChosen',
				filter: '.MediaSelector-MediaSelect',
				ghostClass: 'SortableGhost',
				swapThreshold: .6,
				onEnd: () => {
					if (typeof window.formLeaveConfirmation === 'object') {
						window.formLeaveConfirmation.formChanged();
					}
				},
				onMove: event => {
					if (event.related.matches('.MediaSelector-MediaSelect')) {
						return false;
					}
				},
			});
		}

		if (element.matches(this.singleFileSelector)) {
			element.useMedia = this.useMedia.bind(this, element);

			if (element.dataset.mediaIdInputSelector) {
				element.mediaIdInput = element.querySelector(element.dataset.mediaIdInputSelector);
			}
			else {
				element.mediaIdInput = element.querySelector('input[name^="media_assignments"][name$="[media_id]"]');
			}

			if (!element.mediaIdInput) {
				element.mediaIdInput = element.querySelector('.MediaSelector-MediaId') || element.querySelector('input[type="text"]');
			}
		}
		else {
			element.useMedia = this.addMedia.bind(this, element);
			element.selector = element.querySelector('.MediaSelector-MediaSelect');
		}

		element.preview = preview;
	}

	/**
	 * @param {MouseEvent} event - The event that triggered the overlay.
	 */
	openOverlay(event) {
		if (event.target.closest('.MediaSelector-Remove')) {
			return;
		}

		const openEvent = new CustomEvent('overlay.open', {
			detail: {
				opener: event.target.closest('.MediaSelector'),
			},
		});

		this.overlay.openOverlay(openEvent);
	}

	/**
	 * @param {HTMLElement} element
	 * @param {HTMLElement} media
	 */
	addMedia(element, media) {
		const template = document.querySelector('template');

		// Clone the media item
		// noinspection DuplicatedCode
		const mediaListItem = document.createElement('div');
		mediaListItem.classList.add('Media-ListItem');
		mediaListItem.title = media.title;

		// Remove any children that aren't .Preview or .Name
		media.childNodes.forEach(child => {
			if (child.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (child.classList.contains('Preview') || child.classList.contains('Name')) {
				const clonedChild = child.cloneNode(true);
				mediaListItem.appendChild(clonedChild);
			}
		});

		template.content.childNodes.forEach(child => {
			if (child.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			child = child.cloneNode(true);
			mediaListItem.appendChild(child);
		});

		const id = parseInt(media.getAttribute('id').replace(/\D/g, ''));
		const mediaIdInput = mediaListItem.querySelector('input[name^="media_assignments"][name$="[]"]');
		mediaIdInput.value = id;

		// Add the media item into the preview area but before the add button
		// noinspection JSUnresolvedReference
		element.preview.insertBefore(mediaListItem, element.selector);

		if (typeof window.formLeaveConfirmation === 'object') {
			window.formLeaveConfirmation.formChanged();
		}
	}


	/**
	 * @param {HTMLElement} element
	 * @param {HTMLElement} media
	 */
	useMedia(element, media) {
		// Remove any existing media items
		element.querySelectorAll('.Media-ListItem').forEach(media => media.remove());

		// Clone the media item
		// noinspection DuplicatedCode
		const mediaListItem = document.createElement('div');
		mediaListItem.classList.add('Media-ListItem');
		mediaListItem.title = media.title;

		// Remove any children that aren't .Preview or .Name
		media.childNodes.forEach(child => {
			if (child.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (child.classList.contains('Preview') || child.classList.contains('Name')) {
				const clonedChild = child.cloneNode(true);
				mediaListItem.appendChild(clonedChild);
			}
		});

		const id = parseInt(media.getAttribute('id').replace(/\D/g, ''));

		// Add the media item to the selector
		// noinspection JSUnresolvedReference
		element.preview.appendChild(mediaListItem);

		// noinspection JSUnresolvedReference
		element.mediaIdInput.value = id;

		if (typeof window.formLeaveConfirmation === 'object') {
			window.formLeaveConfirmation.formChanged();
		}
	}

	/**
	 * @param {HTMLElement} element
	 * @param {MouseEvent} event - The event that triggered the removal.
	 */
	removeMedia(element, event) {
		if (element.matches(this.singleFileSelector)) {
			if (typeof window.formLeaveConfirmation === 'object') {
				window.formLeaveConfirmation.formChanged();
			}

			// noinspection JSUnresolvedReference
			element.preview.querySelectorAll('.Media-ListItem').forEach(media => media.remove());
			// noinspection JSUnresolvedReference
			element.mediaIdInput.value = '';

			return;
		}

		if (event.target.matches('.MediaSelector-Remove')) {
			if (typeof window.formLeaveConfirmation === 'object') {
				window.formLeaveConfirmation.formChanged();
			}

			event.target.closest('.Media-ListItem').remove();
		}
	}

	/**
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeSelectors(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.singleFileSelector) || node.matches(this.multiFileSelector)) {
				this.initSelector(node);
			}

			// Also check the children of the node
			node.querySelectorAll(this.singleFileSelector).forEach(this.initSelector.bind(this));
			node.querySelectorAll(this.multiFileSelector).forEach(this.initSelector.bind(this));
		});
	}
}

/**
 * Class to handle the media folder selection
 */
export class MediaFolderSelect {
	/**
	 * The input element the selection was triggered from.
	 *
	 * @type {HTMLInputElement}
	 */
	activeInput;
	/**
	 * The overlay element.
	 *
	 * @type {HTMLDialogElement} overlay
	 */
	dialog;
	/**
	 * The folder selector.
	 * @type {string}
	 */
	folderSelector = '.MediaSelector-Folder';
	/**
	 * Whether the form has been changed when opening the overlay.
	 *
	 * @type {boolean} isFormChanged
	 */
	isFormChanged = false;

	constructor() {
		document.querySelectorAll(this.folderSelector).forEach(this.initFolderSelector.bind(this));

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Create the dialog element.
	 *
	 * @returns {void}
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'MediaFolderSelectOverlay';

		this.dialog.addEventListener('close', () => {
			// Remove all children from the dialog
			while (this.dialog.firstChild) {
				this.dialog.removeChild(this.dialog.firstChild);
			}

			// Reset the form changed status
			window.formLeaveConfirmation.isFormChanged = this.isFormChanged;
		});

		this.dialog.addEventListener('click', event => this.handleDialogClick(event));

		this.dialog.addEventListener('dblclick', event => {
			// If the target is a checkbox, use
			if (event.target.matches('label')) {
				const checkbox = event.target.querySelector('input[type="checkbox"]');
				const title = checkbox.parentElement.querySelector('.Title').textContent;

				this.activeInput.value = checkbox.value || '';
				this.activeInput.parentElement.querySelector('output').textContent = title;
				this.isFormChanged = true;

				this.dialog.close();
			}
		});

		this.dialog.addEventListener('keypress', event => {
			// Prevent the dialog from closing when pressing the enter key
			if (event.key === 'Enter') {
				event.preventDefault();
			}
		});

		// Append dialog to body
		document.body.appendChild(this.dialog);
	}


	/**
	 * Initialize the folder selector.
	 */
	initFolderSelector(element) {
		element.addEventListener('click', event => this.openOverlay(event, element));
	}


	/**
	 * Fetch the duplicate configuration form.
	 *
	 * @returns {Promise<Element>}
	 */
	async fetchMediaFolderSelect(event) {
		let includeHidden = '';
		if (event.ctrlKey || event.metaKey) {
			includeHidden = 'include-hidden:1/';
		}

		const response = await fetch(`${baseUrl}backend/${languageShortcode}/media/folder-select/${includeHidden}`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				media_folder_id: this.activeInput.value,
			}),
		});

		const html = await response.text();

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, "text/html");

		return doc.querySelector('#Content')?.querySelector('.Form');
	}


	/**
	 * Open the overlay to configure the module.
	 *
	 * @param {Event} event
	 * @param {tinymce.Editor} editor
	 * @param {HTMLElement} node
	 * @returns {Promise<void>}
	 */
	async openOverlay(event, editor, node) {
		this.isFormChanged = window.formLeaveConfirmation.isFormChanged;
		this.activeInput = event.target;

		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();

		const form = await this.fetchMediaFolderSelect(event);

		if (!form) {
			return;
		}

		form.classList.remove('Contents');
		this.dialog.appendChild(form);

		// Create a new instance of the nested list handler
		new NestedListHandler('ul#MediaFolders-List');
	}


	/**
	 * Handle the click event on the dialog.
	 *
	 * @param {MouseEvent} event
	 */
	handleDialogClick(event) {
		if (event.target.matches('.Button-Save')) {
			event.preventDefault();

			// Get the ckecked checkbox and use its value
			const checkbox = this.dialog.querySelector('input[type="checkbox"]:checked');
			const title = checkbox?.parentElement.querySelector('.Title').textContent;

			this.activeInput.value = checkbox?.value || '';
			this.activeInput.parentElement.querySelector('output').textContent = title;
			this.isFormChanged = true;

			// Trigger an input event
			const inputEvent = new Event('input', {
				bubbles: true,
			});
			this.activeInput.dispatchEvent(inputEvent);

			this.dialog.close();

			return;
		}

		// If the click event was on an input, uncheck all other checkboxes
		if (event.target.matches('input[name="media_folder_id"]')) {
			const checkbox = event.target;
			const checked = this.dialog.querySelectorAll('input[type="checkbox"]:checked');

			for (const item of checked) {
				if (item !== checkbox) {
					item.checked = false;
				}
			}
		}

		if (event.target.matches('.Button-Close')) {
			event.preventDefault();
			this.dialog.close();
		}
	}


	/**
	 * Observe mutations in the DOM and set up the duplicate of configuration.
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType === Node.ELEMENT_NODE) {
				if (node.matches(this.folderSelector)) {
					this.initFolderSelector(node);
				}

				const elements = node.querySelectorAll(this.folderSelector);
				elements.forEach((element) => {
					this.initFolderSelector(element);
				});
			}
		});
	}
}
