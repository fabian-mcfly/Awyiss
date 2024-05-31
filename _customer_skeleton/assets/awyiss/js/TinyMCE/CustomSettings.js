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