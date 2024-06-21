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
	settings = {
		content_css: [
			'assets/css/tinymce.css',
		],
		font_css: [],
	};
	/**
	 * The style formats for the TinyMCE editor
	 * @type {Array}
	 */
	styleFormats = [
		{
			title: 'Text',
			items: [
				{title: 'Großbuchstaben', inline: 'span', classes: 'Uppercase'},
				{
					title: 'Größe',
					items: [
						{title: 'Wie H1', selector: 'p', classes: 'Textsize-LikeH1'},
						{title: 'Wie H2', selector: 'p', classes: 'Textsize-LikeH2'},
						{title: 'Wie H3', selector: 'p', classes: 'Textsize-LikeH3'},
						{title: 'Wie H4', selector: 'p', classes: 'Textsize-LikeH4'}
					]
				}
			]
		},
		{
			title: 'Links',
			items: [
				{title: 'Externer Link ohne Icon', selector: 'a[target]', classes: 'NoIcon'},
				{title: 'Button', selector: 'a, button', classes: 'Button'}
			]
		}
	];
	/**
	 * The user language of the backend
	 * @type {String}
	 */
	userLanguage;

	/**
	 *
	 * @param {string} language
	 * @param {string} userLanguage
	 * @param {Object} designVariables
	 */
	constructor(language, userLanguage, designVariables) {
		/*
		 * Insert alternative font at offset 1 in styleFormats[0].items
		 * if `fontNameAlternative` is set in designVariables and not empty
		 */
		if (designVariables.fontNameAlternative) {
			this.styleFormats[0].items.splice(1, 0, {title: 'Schmuckschrift', selector: 'p, h1, h2, h3, h4, h5, h6, li', classes: 'FontAlternative'});
		}
	}

	/**
	 * Returns the custom settings, merged with the default settings
	 *
	 * @param {Object} defaultSettings
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
	 * @returns {Array}
	 */
	getStyleFormats(defaultStyleFormats, designVariables) {
		return [...defaultStyleFormats, ...this.styleFormats];
	}
}
