// noinspection JSUnusedGlobalSymbols

export default class CustomSettings {
	/**
	 * The frontend language selected by the user
	 * @type {String}
	 */
	language;
	/**
	 * The options for the Jodit editor
	 * @type {object}
	 */
	settings = {
		iframeCSSLinks: [
			// Use a random timestamp to prevent caching in the frontend editor iframe
			'assets/css/jodit.' + this.generateRandomTimestamp() + '.css',
		],
	};
	/**
	 * The style formats for the Jodit editor
	 * @type {object}
	 */
	styleFormats = {};
	/**
	 * The user language of the backend
	 * @type {String}
	 */
	userLanguage;

	constructor(language, userLanguage) {

	}

	/**
	 * Returns the custom settings, merged with the default settings
	 *
	 * @param defaultSettings
	 * @returns {*}
	 */
	getSettings(defaultSettings) {
		return {...defaultSettings, ...this.settings};
	}

	/**
	 * Returns the custom style formats, merged with the default style formats
	 *
	 * @param {Object} defaultStyleFormats
	 * @returns {Object}
	 */
	getStyleFormats(defaultStyleFormats) {
		return {...defaultStyleFormats, ...this.styleFormats};
	}

	/**
	 * Generates a random timestamp
	 * @returns {number}
	 */
	generateRandomTimestamp() {
		const now = new Date();
		const randomOffset = Math.floor(Math.random() * 1000000000); // Generate a random number of milliseconds
		const randomDate = new Date(now.getTime() + randomOffset);
		return Math.floor(randomDate.getTime() / 1000); // Return the Unix timestamp
	}
}