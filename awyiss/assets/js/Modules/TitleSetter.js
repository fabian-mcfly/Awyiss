//noinspection JSUnusedGlobalSymbols

/**
 * TitleAttributeSetter class.
 * This class is used to set the title attribute of HTML elements.
 * The title attribute is set to the trimmed text content of each element.
 */
export default class TitleSetter {
	/**
	 * @param {string} selector - The selector of the elements to set the title attribute for.
	 */
	constructor(selector) {
		const elements = document.querySelectorAll(selector);
		elements.forEach((element) => {
			if (element.title) {
				// If the element already has a title, skip it
				return;
			}

			let text = element.textContent.trim();

			// Replace all types of line breaks and tabs with a single space
			text = text.replace(/[\r\n\t]+/g, ' ');

			// Replace multiple spaces with a single space
			text = text.replace(/ {2,}/g, ' ');

			element.setAttribute('title', text);
		});
	}
}
