/**
 * Custom TinyMCE Plugin: Narrow Nonbreaking Space
 * Adds support for both regular (&nbsp;) and narrow (&#8239;) non-breaking spaces
 */

(function () {
	'use strict';

	var global$1 = tinymce.util.Tools.resolve('tinymce.PluginManager');

	/* eslint-disable @typescript-eslint/no-wrapper-object-types */
	const isSimpleType = (type) => (value) => typeof value === type;
	const isBoolean = isSimpleType('boolean');
	const isNumber = isSimpleType('number');

	const option = (name) => (editor) => editor.options.get(name);
	const register$2 = function (editor) {
		const registerOption = editor.options.register;
		registerOption('nonbreaking_force_tab', {
			processor: (value) => {
				if (isBoolean(value)) {
					return {
						value: value ? 3 : 0,
						valid: true,
					};
				}
				else {
					if (isNumber(value)) {
						return {
							value,
							valid: true,
						};
					}
					else {
						return {
							valid: false,
							message: 'Must be a boolean or number.',
						};
					}
				}
			},
			default: false,
		});

		registerOption('nonbreaking_wrap', {
			processor: 'boolean',
			default: true,
		});
	};

	const getKeyboardSpaces = option('nonbreaking_force_tab');
	const wrapNbsps = option('nonbreaking_wrap');

	const stringRepeat = (string, repeats) => {
		let str = '';
		for (let index = 0; index < repeats; index++) {
			str += string;
		}
		return str;
	};

	const isVisualCharsEnabled = (editor) => editor.plugins.visualchars ? editor.plugins.visualchars.isEnabled() : false;

	// Regular non-breaking space (&nbsp;)
	const insertNbsp = (editor, times) => {
		const classes = () => isVisualCharsEnabled(editor) ? 'mce-nbsp-wrap mce-nbsp' : 'mce-nbsp-wrap';
		const nbspSpan = () => `<span class="${classes()}" contenteditable="false">${stringRepeat('&nbsp;', times)}</span>`;
		const shouldWrap = wrapNbsps(editor);
		const html = shouldWrap || editor.plugins.visualchars ? nbspSpan() : stringRepeat('&nbsp;', times);
		editor.undoManager.transact(() => editor.insertContent(html));
	};

	// Narrow non-breaking space (&#8239;)
	const insertNarrowNbsp = (editor, times) => {
		const classes = () => isVisualCharsEnabled(editor) ? 'mce-nbsp-wrap mce-narrow-nbsp' : 'mce-nbsp-wrap';
		const nbspSpan = () => `<span class="${classes()}" contenteditable="false">${stringRepeat('&#8239;', times)}</span>`;
		const shouldWrap = wrapNbsps(editor);
		const html = shouldWrap || editor.plugins.visualchars ? nbspSpan() : stringRepeat('&#8239;', times);
		editor.undoManager.transact(() => editor.insertContent(html));
	};

	const register$1 = (editor) => {
		// Regular non-breaking space command
		editor.addCommand('mceNonBreaking', () => {
			insertNbsp(editor, 1);
		});

		// Narrow non-breaking space command
		editor.addCommand('mceNarrowNonBreaking', () => {
			insertNarrowNbsp(editor, 1);
		});
	};

	var global = tinymce.util.Tools.resolve('tinymce.util.VK');

	const setup = (editor) => {
		const spaces = getKeyboardSpaces(editor);
		if (spaces > 0) {
			editor.on('keydown', (e) => {
				if (e.keyCode === global.TAB && !e.isDefaultPrevented()) {
					if (e.shiftKey) {
						return;
					}
					e.preventDefault();
					e.stopImmediatePropagation();
					insertNbsp(editor, spaces);
				}
			});
		}
	};

	const onSetupEditable = (editor) => (api) => {
		const nodeChanged = () => {
			api.setEnabled(editor.selection.isEditable());
		};
		editor.on('NodeChange', nodeChanged);
		nodeChanged();
		return () => {
			editor.off('NodeChange', nodeChanged);
		};
	};

	const register = (editor) => {
		// Regular non-breaking space button
		const onAction = () => editor.execCommand('mceNonBreaking');
		editor.ui.registry.addButton('nonbreaking', {
			icon: 'non-breaking',
			tooltip: 'Nonbreaking space',
			onAction,
			onSetup: onSetupEditable(editor),
		});
		editor.ui.registry.addMenuItem('nonbreaking', {
			icon: 'non-breaking',
			text: 'Nonbreaking space',
			onAction,
			onSetup: onSetupEditable(editor),
		});

		// Narrow non-breaking space button
		const onActionNarrow = () => editor.execCommand('mceNarrowNonBreaking');
		editor.ui.registry.addButton('narrownonbreaking', {
			icon: 'non-breaking',
			tooltip: 'Narrow nonbreaking space',
			onAction: onActionNarrow,
			onSetup: onSetupEditable(editor),
		});
		editor.ui.registry.addMenuItem('narrownonbreaking', {
			icon: 'non-breaking',
			text: 'Narrow nonbreaking space',
			onAction: onActionNarrow,
			onSetup: onSetupEditable(editor),
		});
	};

	/**
	 * This class contains all core logic for the narrownonbreaking plugin.
	 *
	 * @class tinymce.narrownonbreaking.Plugin
	 * @private
	 */
	var Plugin = () => {
		global$1.add('narrownonbreaking', (editor) => {
			register$2(editor);
			register$1(editor);
			register(editor);
			setup(editor);
		});
	};

	Plugin();
	/** *****
	 * DO NOT EXPORT ANYTHING
	 *
	 * IF YOU DO ROLLUP WILL LEAVE A GLOBAL ON THE PAGE
	 *******/

})();
