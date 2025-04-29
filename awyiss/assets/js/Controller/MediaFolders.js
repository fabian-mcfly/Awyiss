// noinspection JSUnusedGlobalSymbols

export default class MediaFoldersController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		// In the overview, initialize the nested list handler
		if (document.querySelector('.MediaFolders.Overview')) {
			// noinspection JSUnresolvedReference
			nestedListHandler.setGroupIdentifierAttribute((list, items) => {
				const languageShortcode = list.closest('ul').dataset.languageShortcode;

				items.forEach((item, index) => {
					items[index] = {
						id: item,
						languageShortcode: languageShortcode,
						systemOrder: index + 1,
					}
				});

				return languageShortcode;
			});

			const deleteButtons = document.querySelectorAll('.MediaFolders.Overview .Button-Delete');
			deleteButtons.forEach((button) => {
				// Add click event listener to the document
				button.addEventListener('click', async(event) => {
					const response = await this.handleDelete(event);

					window.buttonHandler.dialog.confirmYes.onclick = undefined
					window.buttonHandler.dialog.confirmNo.onclick = undefined;

					if (!response) {
						event.preventDefault();
						return false;
					}

					window.location.href = event.target.href;
				});
			});
		}
	}

	/**
	 * Handle the delete button click event.
	 * @param {Event} event - The click event.
	 */
	async handleDelete(event) {
		event.preventDefault();
		event.stopPropagation();

		let folderId = event.currentTarget.closest('.MediaFolders-ListItem ').id;
		folderId = parseInt(folderId.replace('MediaFolders-ListItem', ''));

		if (folderId === 0) {
			return false;
		}

		const response = await fetch(`${baseUrl}backend/${languageShortcode}/media-folders/check-usage/id:${folderId}/`, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		if (!response.ok) {
			return false;
		}

		const data = await response.json();

		if (data.inUse === false && data.hiddenChildren === false) {
			return true;
		}

		window.buttonHandler.dialog.message.innerHTML = data.message;
		window.buttonHandler.dialog.confirmYes.disabled = true;

		window.buttonHandler.dialog.showModal();
		window.buttonHandler.dialog.focus();

		return new Promise((resolve, reject) => {
			window.buttonHandler.dialog.confirmNo.onclick = () => {
				window.buttonHandler.dialog.confirmYes.disabled = false;
				return resolve(false);
			};
		})
	}
}

/**
 * Expose the class globally
 * @global
 * @type {MediaFoldersController}
 */
window.MediaFoldersController = MediaFoldersController;
