/**
 * TinyMCE Typography Fixer Plugin
 *
 * Fixes typography in the editor content based on the current language.
 */
class TypographyFixer {
	/**
	 * @type {tinymce.Editor} editor - The editor instance.
	 */
	editor;

	/**
	 * @param {tinymce.Editor} editor
	 */
	constructor(editor) {
		this.editor = editor;
	}

	/**
	 * Return the metadata for the help plugin.
	 *
	 * @returns {{name: string, url: string}}
	 */
	getMetadata() {
		return {
			name: 'Typography Fixer Plugin',
			url: '#'
		};
	}

	/**
	 * Create and show loader overlay
	 *
	 * @returns {HTMLElement}
	 */
	showLoader() {
		const editorBody = this.editor.getBody();
		const container = this.editor.getContainer();

		const overlay = document.createElement('div');
		overlay.className = 'typography-fixer-overlay';
		overlay.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; '
			+ 'background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; '
			+ 'z-index: 9999;';

		const spinner = document.createElement('div');
		spinner.className = 'typography-fixer-spinner';
		spinner.style.cssText = 'width: 40px; height: 40px; border: 4px solid rgba(255, 255, 255, 0.3); '
			+ 'border-top-color: #fff; border-radius: 50%; animation: typography-spin 0.8s linear infinite;';

		overlay.appendChild(spinner);
		container.appendChild(overlay);

		// Add animation keyframes if not already added
		if (!document.getElementById('typography-fixer-styles')) {
			const style = document.createElement('style');
			style.id = 'typography-fixer-styles';
			style.textContent = '@keyframes typography-spin { to { transform: rotate(360deg); } }';
			document.head.appendChild(style);
		}

		editorBody.style.opacity = '0.3';
		editorBody.style.pointerEvents = 'none';

		return overlay;
	}

	/**
	 * Remove loader overlay
	 *
	 * @param {HTMLElement} overlay
	 */
	hideLoader(overlay) {
		const editorBody = this.editor.getBody();

		if (overlay && overlay.parentNode) {
			overlay.parentNode.removeChild(overlay);
		}

		editorBody.style.opacity = '';
		editorBody.style.pointerEvents = '';
	}

	/**
	 * Fix the typography in the editor content
	 *
	 * @returns {Promise<void>}
	 */
	async fixTypography() {
		const content = this.editor.getContent();
		const language = this.getEditorLanguage();

		if (!content || !language) {
			return;
		}

		let overlay = null;

		try {
			overlay = this.showLoader();

			const response = await fetch(`${baseUrl}backend/${languageShortcode}/contents/fix-typography/`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body: JSON.stringify({
					content: content,
					language: language,
				}),
			});

			if (!response.ok) {
				throw new Error('Failed to fix typography');
			}

			const data = await response.json();

			if (data.success && data.data?.content) {
				this.editor.setContent(data.data.content);
			}
		}
		catch (error) {
			console.error('Typography fixer error:', error);
		}
		finally {
			if (overlay) {
				this.hideLoader(overlay);

				const isVisualCharsEnabled = (editor) => editor.plugins.visualCharsPlus ? editor.plugins.visualCharsPlus.isEnabled() : false;

				// If VisualCharsPlus is enabled, disable and enable it to refresh the display of non-breaking spaces
				if (isVisualCharsEnabled(this.editor)) {
					this.editor.execCommand('mceVisualCharsPlus');
					setTimeout(() => {
						this.editor.execCommand('mceVisualCharsPlus');
					}, 100);
				}
			}
		}
	}

	/**
	 * Get the language from the editor's HTML tag or fallback to the current language
	 *
	 * @returns {string}
	 */
	getEditorLanguage() {
		const editorDoc = this.editor.getDoc();
		const htmlElement = editorDoc.documentElement;
		const lang = htmlElement.getAttribute('lang');

		if (lang) {
			return lang.split('-')[0];
		}

		return languageShortcode;
	}
}

(function () {
	tinymce.PluginManager.add('typographyFixer', function (editor) {
		const typographyFixer = new TypographyFixer(editor);

		editor.ui.registry.addIcon(
			'typography',
			'<svg width="24" height="24" viewBox="0 0 24 24">'
			+ '<path d="M9.93,13.5h4.14L12,7.98ZM20,2H4A2,2 0 0,0 2,4V20A2,2 0 0,0 4,22H20A2,2 0 0,0 22,20V4A2,2 0 0,0 20,2M15.95,18.5L15,15.5H9L8.05,18.5H5.5L10.5,5.5H13.5L18.5,18.5H15.95Z" fill="currentColor"/>'
			+ '</svg>'
		);

		editor.ui.registry.addButton('typographyFixer', {
			icon: 'typography',
			tooltip: 'Fix Typography',
			onAction: function () {
				// noinspection JSIgnoredPromiseFromCall
				typographyFixer.fixTypography();
			}
		});

		return typographyFixer;
	});
})();


