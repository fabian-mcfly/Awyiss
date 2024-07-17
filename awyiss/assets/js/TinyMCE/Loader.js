// noinspection JSUnusedGlobalSymbols

/**
 * TinyMCE Loader class
 */
export default class Loader {
	/**
	 * If the custom settings module is currently loading
	 * @type {boolean}
	 */
	isModuleLoading = false;
	/**
	 * The link list for the TinyMCE editor
	 * @type {Array}
	 */
	linkList = null;
	/**
	 * The selector for the TinyMCE editor
	 * @type {string}
	 */
	selector = 'textarea[data-editor]';
	/**
	 * The options for the TinyMCE editor
	 * @type {object}
	 */
	settings = {
		anchor_bottom: false,
		anchor_top: false,
		autoresize_bottom_margin: 0,
		charmap_append: [
			[173, 'soft hyphen']
		],
		custom_elements: 'module',
		document_base_url: baseUrl,
		extended_valid_elements: 'module[class]',
		external_plugins: {
			awyissModule: '../awyiss_module.js'
		},
		fix_list_elements: true,
		branding: false,
		file_picker_callback: callback => this.filePickerCallback(callback),
		init_instance_callback: editor => this.initInstanceCallback(editor),
		license_key: 'gpl',
		link_context_toolbar: true,
		link_list: async(success) => { // called on link dialog open
			const links = await this.fetchPageLinks();
			success(links);
		},
		link_rel_list: [
			{title: ' ', value: ''},
			{title: 'Lightbox', value: 'lightbox'},
			{title: 'nofollow', value: 'nofollow'},
		],
		link_title: false,
		menubar: false,
		min_height: 300,
		object_resizing: false,
		paste_as_text: true,
		paste_block_drop: false,
		paste_data_images: false,
		plugins: 'anchor autolink autoresize awyissModule charmap code fullscreen link lists nonbreaking table visualblocks visualchars wordcount',
		relative_urls: true,
		setup: (editor) => this.setup(editor),
		shortcuts: [],
		skin: 'oxide-dark',
		smart_paste: false,
		style_formats_autohide: true,
		suffix: '.min',
		table_advtab: false,
		table_appearance_options: false,
		table_cell_advtab: false,
		table_default_attributes: {},
		table_default_styles: {},
		table_header_type: 'sectionCells',
		table_resize_bars: false,
		table_row_advtab: false,
		table_sizing_mode: 'responsive',
		table_use_colgroups: false,
		toolbar1: 'undo redo | copy cut paste pastetext | bold italic underline strikethrough styles removeformat | aligncenter alignright alignjustify outdent indent | awyissModule',
		toolbar2: 'link unlink anchor | blockquote bullist numlist | hr subscript superscript nonbreaking charmap | table | visualblocks visualchars | wordcount code | fullscreen',
		toolbar_sticky: true,
		toolbar_sticky_offset: 100,
		//visualchars_default_state: true,
	}
	/**
	 * If the settings are already set (merged defaults with custom settings)
	 * @type {boolean}
	 */
	settingsSet = false;
	/**
	 * The style formats for the TinyMCE editor
	 * @type {Array}
	 */
	styleFormats = [
		{
			title: 'Headings',
			items: [
				{ title: 'Heading 1', format: 'h1' },
				{ title: 'Heading 2', format: 'h2' },
				{ title: 'Heading 3', format: 'h3' },
				{ title: 'Heading 4', format: 'h4' },
				{ title: 'Heading 5', format: 'h5' },
				{ title: 'Heading 6', format: 'h6' }
			],
		},
		{
			title: 'Inline',
			items: [
				{ title: 'Bold', format: 'bold' },
				{ title: 'Italic', format: 'italic' },
				{ title: 'Underline', format: 'underline' },
				{ title: 'Strikethrough', format: 'strikethrough' },
				{ title: 'Superscript', format: 'superscript' },
				{ title: 'Subscript', format: 'subscript' },
				{ title: 'Code', format: 'code' }
			],
		},
		{
			title: 'Blocks',
			items: [
				{ title: 'Paragraph', format: 'p' },
				{ title: 'Blockquote', format: 'blockquote' },
				{ title: 'Div', format: 'div' },
				{ title: 'Pre', format: 'pre' }
			],
		},
		{
			title: 'Align',
			items: [
				{ title: 'Center', format: 'aligncenter' },
				{ title: 'Right', format: 'alignright' },
				{ title: 'Justify', format: 'alignjustify' }
			],
		}
	];


	/**
	 * Initialize the TinyMCE editor
	 */
	constructor() {
		// noinspection JSIgnoredPromiseFromCall
		this.initElements();

		const observer = window.observer;
		observer.addObserver(this.observeForNewInputs.bind(this));
	}

	/**
	 * Initialize the TinyMCE editor for all elements
	 * @returns {Promise<void>}
	 */
	async initElements() {
		const elements = Array.from(document.querySelectorAll(this.selector));
		for (const element of elements) {
			await this.initElement(element);
		}
	}

	/**
	 * Initialize the TinyMCE editor for a specific element
	 * @param {HTMLElement} element
	 */
	async initElement(element) {
		if (element.closest('.TranslatableTexts') && !element.closest('.IsCurrentLanguage')) {
			return;
		}

		await this.initSettings();

		const settings = {...this.settings};

		if (element.closest('#TranslationDialog')) {
			settings.ui_mode = 'split';
		}

		settings.target = element;

		element.placeholder = '';

		tinymce.init(settings).then((editor) => {
			element.tinymce = editor[0];

			// noinspection JSUnresolvedReference
			this.extendTinyMCE(element.tinymce);
		});
	}

	/**
	 * Callback for the TinyMCE editor's init_instance_callback event
	 *
	 * @param editor
	 */
	initInstanceCallback(editor) {
		editor.on('blur', function () {
			editor.targetElm.innerHTML = editor.getContent();
		});

		editor.on('focus', function () {
			// noinspection JSUnresolvedReference
			if (!this.wasFocusedBefore) {
				this.execCommand('mceVisualChars');
				this.wasFocusedBefore = true;
			}
		}.bind(editor));
	}

	/**
	 * Initialize the settings for the TinyMCE editor
	 *
	 * @returns {Promise<void>}
	 */
	async initSettings() {
		if (this.isModuleLoading || this.settingsSet) {
			return;
		}

		this.isModuleLoading = true;

		if (['de_DE', 'fr_FR', 'es_ES'].includes(userLanguage.locale)) {
			this.settings.language = userLanguage.locale;
			this.settings.language_url = `awyiss/assets/js/TinyMCE/langs/${this.settings.language}.js`;
		}

		try {
			// noinspection NpmUsedModulesInstalled
			const {default: CustomSettings} = await import('TinyMCE/CustomSettings');

			if (CustomSettings) {
				const customSettings = new CustomSettings(language, userLanguage, designVariables, designVariables);
				this.settings = customSettings.getSettings(this.settings, designVariables);
				this.styleFormats = customSettings.getStyleFormats(this.styleFormats, designVariables);
			}

			this.settings.style_formats = this.styleFormats;
		}
		catch (e) {
			console.error(e);
		}
		finally {
			this.isModuleLoading = false;
		}

		this.settingsSet = true;
	}

	/**
	 * Set up the TinyMCE editor
	 *
	 * @param editor
	 */
	setup(editor) {
		editor.once('Dirty', () => {
			if (typeof window.formLeaveConfirmation === 'object') {
				window.formLeaveConfirmation.formChanged();
			}
		});

		editor.on('keydown', function (e) {
			if (e.keyCode === 9) { // Tab key
				let content = this.getContent({format: 'text'}).trim();
				if (content === 'lorem') {
					e.preventDefault();
					let dummyText = `<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquid, animi commodi cum dolor enim et expedita impedit libero magni, modi nulla, quae quia quibusdam quis rem
						suscipit tempora vero voluptatem?</p>
						<p>Aliquam consectetur delectus maiores voluptates. Ad aliquid commodi ea, est impedit incidunt ipsam iusto laudantium maxime modi mollitia nostrum odio officia optio
						pariatur perspiciatis quo, sint tenetur ullam unde voluptatibus?</p>`;
					this.setContent(dummyText);

					// Move the cursor to the end
					let lastNode = this.getBody().lastChild;
					this.selection.select(lastNode);
					this.selection.collapse(false);
				}
			}
		});
	}

	/**
	 * Initialize the TinyMCE editor for new elements
	 *
	 * @param {MutationRecord} mutation - The mutation record
	 */
	async observeForNewInputs(mutation) {
		// Remove the editor if the element is removed
		mutation.removedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector) && node.tinymce) {
				node.tinymce.remove();
			}

			node.querySelectorAll(this.selector).forEach((element) => {
				if (element.tinymce) {
					element.tinymce.remove();
				}
			});
		});

		// noinspection DuplicatedCode
		for (const node of mutation.addedNodes) {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				continue;
			}

			// Init the matching element
			if (node.matches(this.selector)) {
				await this.initElement(node);
			}

			// Init the matching children elements
			const elements = Array.from(node.querySelectorAll(this.selector));
			for (const element of elements) {
				await this.initElement(element);
			}
		}
	}

	/**
	 * Extend the TinyMCE editor's native functions
	 *
	 * @param {Object} editor
	 */
	extendTinyMCE(editor) {
		editor.windowManager._originalOpen = editor.windowManager.open;

		editor.windowManager.open = function (config, params) {
			const knownOpeners = {
				'Anchor': 'anchor',
				'Cell Properties': 'tablecellprops',
				'Insert/Edit Link': 'link',
				'Row Properties': 'tablerowprops',
				'Source Code': 'sourcecode',
				'Special Character': 'charmap',
				'Table Properties': 'tableprops',
				'Word Count': 'wordcount',
			};

			const instance = editor.windowManager._originalOpen(config, params);

			console.log(config.title);

			const dialog = document.querySelector('.tox-dialog');

			if (dialog) {
				const opener = knownOpeners[ config.title ];

				if (opener) {
					dialog.classList.add(`tox-dialog--${opener}`);
				}

				const container = editor.targetElm.closest('#TranslationDialog');
				if (container) {
					container.appendChild(dialog.closest('.tox-tinymce-aux'));
				}
			}

			return instance;
		};
	}

	/**
	 * Callback for the file picker
	 * @param {function} callback
	 */
	filePickerCallback(callback) {
		const openEvent = new CustomEvent('overlay.open', {
			detail: {
				opener: callback,
			},
		});

		// noinspection JSUnresolvedReference
		window.mediaOverlay.openOverlay(openEvent)
	}

	/**
	 * Fetch the page links for the link dialog
	 * @returns {Object}
	 */
	async fetchPageLinks() {
		if (this.linkList) {
			return this.linkList;
		}

		const response = await fetch(
			`${baseUrl}backend/${languageShortcode}/pages/link-list/`,
			{
				method: 'GET',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			}
		);

		const responseData = await response.json();

		if (responseData.success) {
			const data = responseData.data;

			// If data has only one key, there is no need to show nested links
			if (Object.keys(data).length === 1) {
				let links = data[ Object.keys(data)[0] ].links;

				// Transform each item of that single key into an object with keys `title` and `value` (link)
				links = links.map((link) => {
					return {
						title: link.title,
						value: link.link,
					};
				})

				this.linkList = links;

				return links;
			}

			// If data has multiple keys, show nested links. Each key is a page role
			const links = [];

			Object.keys(data).forEach((roleData) => {
				// noinspection JSUnresolvedReference
				const pageRole = {title: data[roleData].pageRole.title, menu: []};

				data[roleData].links.forEach((link) => {
					pageRole.menu.push({
						title: link.title,
						value: link.link,
					});
				});

				links.push(pageRole);
			});

			this.linkList = links;

			return links;
		}
	}
}
