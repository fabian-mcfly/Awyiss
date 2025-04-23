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
		a11y_advanced_options: true,
		anchor_bottom: false,
		anchor_top: false,
		autoresize_bottom_margin: 5,
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
		image_caption: true,
		image_dimensions: false,
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
		plugins: 'anchor autolink autoresize awyissModule charmap code fullscreen image link lists nonbreaking table visualblocks visualchars wordcount',
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
		toolbar: 'bold italic underline strikethrough styles removeformat | undo redo | link unlink anchor | blockquote bullist numlist'
		+ ' | image | hr subscript superscript nonbreaking charmap | table | awyissModule | aligncenter alignright alignjustify outdent indent'
		+ ' | copy cut paste pastetext | visualblocks visualchars | wordcount code | fullscreen',
		toolbar_sticky: true,
		toolbar_sticky_offset: document.documentElement.classList.contains('👀')
			|| document.body.clientWidth <= 768
			? 0 : 105,
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

		await this.initSettings(element);

		const settings = {...this.settings};

		if (element.closest('#TranslationDialog')) {
			settings.ui_mode = 'split';
		}

		let contentStyle = settings.content_style || '';
		// Get the success color from the root element
		const successColor = getComputedStyle(document.documentElement).getPropertyValue('--colorSuccess');
		if (successColor && successColor !== '#63D1A5') {
			// Add the success color to the content style
			// to style elements in the editor with that color
			contentStyle = `:root { --customColor:${successColor}; }` + contentStyle;
			settings.content_style = contentStyle;
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
	async initSettings(element) {
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

		if (document.body.classList.contains('EmailTemplatesController')) {
			this.settings.toolbar = this.settings.toolbar.replace('awyissModule', '');
			this.settings.toolbar = this.settings.toolbar.replace(' styles ', '');
			this.settings.toolbar += 'formPlaceholders';
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

		if (document.body.classList.contains('EmailTemplatesController')) {
			this.addEmailTemplatesPlaceholderOptions(editor);
		}
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
				'Insert/Edit Image': 'image',
				'Insert/Edit Link': 'link',
				'Row Properties': 'tablerowprops',
				'Source Code': 'sourcecode',
				'Special Character': 'charmap',
				'Table Properties': 'tableprops',
				'Word Count': 'wordcount',
			};

			// The a11y adds the caption checkbox inside a panel. Free it
			if (config.title === 'Insert/Edit Image') {
				let captionKey = config.body.items.findIndex(item => {
					return item.type === 'panel' &&
					item.items[0].label === 'Caption';
				});

				/**
				 * Set the image dimensions here instead of using the
				 * config settings.
				 * This way, TinyMCE will not force both values to be set
				 * but allows one of them, or even both, to be empty.
				 */
				if (captionKey > -1) {
					config.body.items[ captionKey + 1 ] = config.body.items[ captionKey ].items[0];
					// Add a custom image dimensions object
					config.body.items[ captionKey ] = {name: 'dimensions', type: 'sizeinput'};
				}
				else {
					// The caption checkbox is not inside a panel
					// Add a custom image dimensions object
					config.body.items.push({name: 'dimensions', type: 'sizeinput'});
				}
			}

			const instance = editor.windowManager._originalOpen(config, params);

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

				// Check if .tox-dialog__footer-end contains more than one buttons
				const footerEnd = dialog.querySelector('.tox-dialog__footer-end');
				const buttons = footerEnd?.querySelectorAll('button');
				if (buttons.length > 1) {
					// If there's a save button ([data-mce-name="Save"]) inside, move it to the start
					const saveButton = footerEnd.querySelector('[data-mce-name="Save"]');
					if (saveButton) {
						footerEnd.prepend(saveButton);
					}
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
		const fileSelection = filepath => {
			const dialog = document.querySelector('.tox-dialog');
			const urlInput = dialog?.querySelector('input[type="url"]');
			if (urlInput) {
				urlInput.value = filepath;
			}
			else {
				callback(filepath);
			}
		}

		const openEvent = new CustomEvent('overlay.open', {
			detail: {
				opener: fileSelection,
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

	addEmailTemplatesPlaceholderOptions(editor) {
		if (!availablePlaceholders) {
			return;
		}

		/* example, adding a toolbar menu button */
		editor.ui.registry.addMenuButton('formPlaceholders', {
			text: 'Placeholders',
			fetch: (callback) => {
				const placeholders = [];

				// Traverse availablePlaceholders
				for (const [key, value] of Object.entries(availablePlaceholders)) {
					if (typeof(value) === 'object') {
						// Traverse the subelements
						const submenu = [];
						for (const [subKey, subValue] of Object.entries(value)) {
							submenu.push({
								type: 'menuitem',
								text: subValue,
								onAction: () => editor.insertContent('{{$' + subKey + '}}')
							});
						}

						placeholders.push({
							type: 'nestedmenuitem',
							text: key,
							getSubmenuItems: () => submenu,
						});

						continue;
					}

					placeholders.push({
						type: 'menuitem',
						text: value,
						onAction: () => editor.insertContent('{{$' + key + '}}'),
					});
				}

				callback(placeholders);
			}
		});
	}
}
