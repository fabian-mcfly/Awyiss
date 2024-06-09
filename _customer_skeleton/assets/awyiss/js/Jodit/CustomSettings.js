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
			'assets/css/jodit.css',
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
}