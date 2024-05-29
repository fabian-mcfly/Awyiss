// noinspection JSUnusedGlobalSymbols

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
	singleMediaSelector = '.MediaSelector-SingleMedia';
	/**
	 * The multi media selector.
	 * @type {string}
	 */
	multiMediaSelector = '.MediaSelector-MultiMedia';
	/**
	 * The overlay instance.
	 * @type {Overlay}
	 */
	overlay = null;

	constructor() {
		// Initialize the selectors
		document.querySelectorAll(this.singleMediaSelector).forEach(this.initSelector.bind(this));
		document.querySelectorAll(this.multiMediaSelector).forEach(this.initSelector.bind(this));

		// Observe the document for new elements that match the selectors
		const observer = window.observer;
		observer.addObserver(this.observeSelectors.bind(this));
	}

	/**
	 * @param {HTMLElement} element
	 */
	initSelector(element) {
		const preview = element.querySelector('.MediaSelector-Preview');

		if (element.matches(this.singleMediaSelector)) {
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
				onMove: function (event) {
					if (event.related.matches('.MediaSelector-MediaSelect')) {
						return false;
					}
				},
			});
		}

		if (element.matches(this.singleMediaSelector)) {
			element.useMedia = this.useMedia.bind(this, element);
			element.mediaIdInput = element.querySelector('input[name^="media_assignments"][name$="[media_id]"]');
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
		element.preview.insertBefore(mediaListItem, element.selector);
	}


	/**
	 * @param {HTMLElement} element
	 * @param {HTMLElement} media
	 */
	useMedia(element, media) {
		// Remove any existing media items
		element.querySelectorAll('.Media-ListItem').forEach(media => media.remove());

		// Clone the media item
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
		element.preview.appendChild(mediaListItem);

		element.mediaIdInput.value = id;
	}

	/**
	 * @param {HTMLElement} element
	 * @param {MouseEvent} event - The event that triggered the removal.
	 */
	removeMedia(element, event) {
		if (element.matches(this.singleMediaSelector)) {
			element.preview.querySelectorAll('.Media-ListItem').forEach(media => media.remove());
			element.mediaIdInput.value = '';

			return;
		}

		if (event.target.matches('.MediaSelector-Remove')) {
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

			if (node.matches(this.singleMediaSelector) || node.matches(this.multiMediaSelector)) {
				this.initSelector(node);
			}

			// Also check the children of the node
			node.querySelectorAll(this.singleMediaSelector).forEach(this.initSelector.bind(this));
			node.querySelectorAll(this.multiMediaSelector).forEach(this.initSelector.bind(this));
		});
	}
}