//noinspection JSUnusedGlobalSymbols

/**
 * TitleAttributeSetter class.
 * This class is used to set the title attribute of HTML elements.
 * The title attribute is set to the trimmed text content of each element.
 */
export default class TitleAttributeSetter {
	/**
	 * @param {string} className - The class name of the elements to set the title for.
	 */
	constructor(className) {
		const elements = document.getElementsByClassName(className);
		const elementsLength = elements.length;
		for (let i = 0; i < elementsLength; i++) {
			let text = elements[i].textContent.trim();

			// Replace all types of line breaks and tabs with a single space
			text = text.replace(/[\r\n\t]+/g, ' ');

			// Replace multiple spaces with a single space
			text = text.replace(/ {2,}/g, ' ');

			elements[i].setAttribute('title', text);
		}
	}
}