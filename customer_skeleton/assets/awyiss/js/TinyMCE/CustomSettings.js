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
			// Use a random timestamp to prevent caching in the frontend editor iframe
			'assets/css/tinymce.' + this.generateRandomTimestamp() + '.css',
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
				{title: 'Hauptfarbe', inline: 'span', classes: 'Textcolor-Main'},
				{title: 'Kontrastfarbe', inline: 'span', classes: 'Textcolor-Contrast'},
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
			title: 'Bilder',
			items: [
				{title: 'Links vom Text', selector: 'img', classes: 'ImageAlign-Left'},
				{title: 'Rechts vom Text', selector: 'img', classes: 'ImageAlign-Right'},
			]
		},
		{
			title: 'Links',
			items: [
				{title: 'Externer Link ohne Icon', selector: 'a[target]', classes: 'NoExternalLinkIcon'},
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
	 * @param {HTMLElement} element
	 * @param {string} language
	 * @param {string} userLanguage
	 * @param {Object} designVariables
	 */
	constructor(element, language, userLanguage, designVariables) {
		/*
		 * Insert alternative font at offset 1 in styleFormats[0].items
		 * if `fontNameAlternative` is set in designVariables and not empty
		 */
		if (designVariables.fontNameAlternative) {
			this.styleFormats[0].items.splice(2, 0, {title: 'Schmuckschrift', selector: 'p, h1, h2, h3, h4, h5, h6, li', classes: 'FontAlternative'});
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
