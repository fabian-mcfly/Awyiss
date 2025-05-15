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
	settings = {};
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

	/**
	 * @param {HTMLElement} element
	 * @param {string} language
	 * @param {string} userLanguage
	 * @param {Object} designVariables
	 */
	constructor(element, language, userLanguage, designVariables) {

	}

	/**
	 * Returns the custom settings, merged with the default settings
	 *
	 * @param defaultSettings
	 * @param {Object} designVariables
	 * @returns {*}
	 */
	getSettings(defaultSettings, designVariables) {
		return {...defaultSettings, ...this.settings};
	}

	/**
	 * Returns the custom style formats, merged with the default style formats
	 *
	 * @param {Object} defaultStyleFormats
	 * @param {Object} designVariables
	 * @returns {Object}
	 */
	getStyleFormats(defaultStyleFormats, designVariables) {
		return {...defaultStyleFormats, ...this.styleFormats};
	}
}