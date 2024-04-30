// noinspection JSUnusedGlobalSymbols

export default class MediaFoldersController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
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
	}
}

/**
 * Expose the class globally
 * @global
 * @type {MediaFoldersController}
 */
window.MediaFoldersController = MediaFoldersController;
