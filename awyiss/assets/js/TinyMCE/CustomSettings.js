// noinspection JSUnusedGlobalSymbols

export default class CustomSettings {
	/**
	 * The frontend language selected by the user
	 * @type {String}
	 */
	language;
	/**
	 * The options for the TinyMCE editor
	 * @type {object}
	 */
	settings = {};
	/**
	 * The style formats for the TinyMCE editor
	 * @type {Array}
	 */
	styleFormats = [];
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
	 * @param defaultStyleFormats
	 * @returns {Array}
	 */
	getStyleFormats(defaultStyleFormats) {
		return [...defaultStyleFormats, ...this.styleFormats];
	}
}
