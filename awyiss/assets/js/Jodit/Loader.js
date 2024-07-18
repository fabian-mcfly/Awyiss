// noinspection JSUnusedGlobalSymbols

/**
 * Jodit Loader class
 */
export default class Loader {
	/**
	 * If the custom settings module is currently loading
	 * @type {boolean}
	 */
	isModuleLoading = false;
	/**
	 * The selector for the Jodit editor
	 * @type {string}
	 */
	selector = 'textarea[data-editor]';
	// noinspection JSUnresolvedReference
	/**
	 * The options for the Jodit editor
	 * @type {object}
	 */
	settings = {
		allowTags: {
			module: true // Allow "module" as a valid tag
		},
		beautifyHTML: false,
		buttons: [
			'undo', 'redo', '|', 'cut', 'copy', 'paste', '|',
			'bold', 'italic', 'underline', 'strikethrough', 'paragraph', 'classSpan', 'eraser', '|',
			{
				icon: 'left',
				name: 'alignLeft',
				tooltip: 'Align left',
				exec: (editor) => {
					// noinspection JSUnresolvedReference
					editor.s.eachSelection((current) => {
						if (!current) {
							return;
						}

						// noinspection JSUnresolvedReference
						const Dom = Jodit.modules.Dom;
						const currentBox = Dom.closest(current, Dom.isBlock, editor.editor) || editor.editor;

						// noinspection JSUnresolvedReference
						if (!Dom.isHTMLElement(currentBox) || !currentBox.style.textAlign) {
							return;
						}

						currentBox.style.textAlign = '';

						if (!currentBox.style.cssText.trim().length) {
							currentBox.removeAttribute('style');
						}
					});
				}
			},
			'center', 'right', 'justify', 'outdent', 'indent', '|', 'awyissModuleConfig', '\n',
			'link', 'unlink', '|', 'ul', 'ol', '|', 'hr', 'superscript', 'subscript',
			{
				icon: 'insertNbsp',
				name: 'insertNbsp',
				tooltip: 'Insert non-breaking space',
				exec: (editor) => {
					editor.s.insertHTML('&nbsp;');
				}
			},
			'symbols', '|', 'table', '|', 'source', '|', 'fullsize'
		],
		controls: {
			classSpan: {
				list: {},
			},
			paragraph: {
				list: Jodit.atom({
					h1: 'Heading 1',
					h2: 'Heading 2',
					h3: 'Heading 3',
					h4: 'Heading 4',
					h5: 'Heading 5',
					h6: 'Heading 6',
					p: 'Paragraph',
					blockquote: 'Quote',
					pre: 'Source code'
				})
			},
			paste: {
				list: undefined
			},
			ul: {
				list: undefined
			},
			ol: {
				list: undefined
			},
		},
		defaultActionOnPaste: 'insert_clear_html',
		disablePlugins: [
			'about', 'add-new-line', 'ai-assistant', 'drag-and-drop', 'drag-and-drop-element', 'file', 'font',
			'speech-recognize', 'spellcheck', 'video', 'resize-cells', 'print', 'preview', 'powered-by-jodit', 'paste-storage',
			'placeholder', 'mobile', 'media', 'line-height', 'limit', 'image-properties', 'image-processor', 'image', 'color'
		],
		i18n: {
			de: {
				'Class name': 'CSS-Klasse',
				'Heading 5': 'Überschrift 5',
				'Heading 6': 'Überschrift 6',
				'Insert non-breaking space': 'Geschütztes Leerzeichen',
			}
		},
		iframe: true,
		iframeBaseUrl: baseUrl,
		iframeCSSLinks: [],
		indentMargin: 40,
		popup: {
			a: Jodit.atom(Jodit.defaultOptions.popup.a.slice(0, 3)),
		},
		resizer: false,
		sourceEditor: 'area',
		specialCharacters: [
			...Jodit.defaultOptions.specialCharacters,
			'&nbsp;',
			'&shy;',
			'&ndash;',
			'&mdash;',
		],
		toolbarButtonSize: 'large',
		toolbarInlineForSelection: false,
		toolbarStickyOffset: 100,
		uploader: {url: 'none'},
	}
	/**
	 * If the settings are already set (merged defaults with custom settings)
	 * @type {boolean}
	 */
	settingsSet = false;
	/**
	 * The style formats for the Jodit editor
	 * @type {object}
	 */
	styleFormats = {};


	/**
	 * Initialize the Jodit editor
	 */
	constructor() {
		// noinspection JSUnresolvedReference
		Jodit.modules.Icon.set('insertNbsp', `
			<svg class="svg-icon" style="fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
				<path d="M448 576l-192 0 0-128 192 0 0-192 128 0 0 192 192 0 0 128-192 0 0 192-128 0zM1024 640l0 384-1024 0 0-384 128 0 0 256 768 0 0-256z"  />
			</svg>
		`);

		// noinspection JSIgnoredPromiseFromCall
		this.initElements();

		const observer = window.observer;
		observer.addObserver(this.observeForNewInputs.bind(this));
	}

	/**
	 * Initialize the Jodit editor for all elements
	 * @returns {Promise<void>}
	 */
	async initElements() {
		const elements = Array.from(document.querySelectorAll(this.selector));
		for (const element of elements) {
			await this.initElement(element);
		}
	}

	/**
	 * Initialize the Jodit editor for a specific element
	 * @param {HTMLElement} element
	 */
	async initElement(element) {
		if (element.closest('.TranslatableTexts') && !element.closest('.IsCurrentLanguage')) {
			return;
		}

		await this.initSettings();

		const settings = {...this.settings};

		if (element.closest('#TranslationDialog')) {
			settings.toolbarSticky = false;
			settings.buttons = settings.buttons.filter(button => button !== 'fullsize');
			settings.shadowRoot = element.closest('#TranslationDialog');
		}

		element.addEventListener('change', function () {
			if (window.formLeaveConfirmation) {
				window.formLeaveConfirmation.formChanged();
			}
		})

		// noinspection JSUnresolvedReference
		element.jodit = Jodit.make(element, settings);
	}

	/**
	 * Initialize the settings for the Jodit editor
	 *
	 * @returns {Promise<void>}
	 */
	async initSettings() {
		if (this.isModuleLoading || this.settingsSet) {
			return;
		}

		this.isModuleLoading = true;

		this.settings.language = userLanguage.locale.slice(0, 2);

		try {
			// noinspection NpmUsedModulesInstalled
			const {default: CustomSettings} = await import('Jodit/CustomSettings');

			if (CustomSettings) {
				const customSettings = new CustomSettings(language, userLanguage);
				this.settings = customSettings.getSettings(this.settings);
				this.styleFormats = customSettings.getStyleFormats(this.styleFormats);
			}

			this.settings.controls.classSpan.list = this.styleFormats;

			// Remove the styles if its empty
			if (!Object.keys(this.styleFormats).length) {
				this.settings.buttons = this.settings.buttons.filter(button => button !== 'classSpan');
			}
		} catch (e) {
			console.error(e);
		} finally {
			this.isModuleLoading = false;
		}

		// noinspection NpmUsedModulesInstalled
		await import('Jodit/awyiss_module');

		this.settingsSet = true;
	}

	/**
	 * Initialize the Jodit editor for new elements
	 *
	 * @param {MutationRecord} mutation - The mutation record
	 */
	async observeForNewInputs(mutation) {
		// Remove the editor if the element is removed
		mutation.removedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector) && node.jodit) {
				// noinspection JSUnresolvedReference
				node.jodit.destruct();
			}

			node.querySelectorAll(this.selector).forEach((element) => {
				if (element.jodit) {
					// noinspection JSUnresolvedReference
					element.jodit.destruct();
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
}
