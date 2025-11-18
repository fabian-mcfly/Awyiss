// noinspection JSUnusedGlobalSymbols

import CodeCup from 'CodeCup/CodeCup';

export default class CodeEditor {
	/**
	 * The selector for the CSS code editor textareas
	 * @type {string}
	 */
	selector = 'textarea[data-css-editor="1"]';

	constructor() {
		const codeEditors = document.querySelectorAll(this.selector);
		codeEditors.forEach((codeEditor) => {
			this.initCodeEditor(codeEditor);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Initialize the code editor.
	 *
	 * * @param {HTMLTextAreaElement} element
	 */
	initCodeEditor(element) {
		if (element.codeCupInitialized) {
			return;
		}

		const wrapper = document.createElement('div');
		wrapper.classList.add('CodeEditor');
		element.parentNode.insertBefore(wrapper, element);

		const codecupElement = document.createElement('div');
		codecupElement.classList.add('CodeCup');
		wrapper.appendChild(codecupElement);

		element.codeCup = new CodeCup(codecupElement, {
			language: 'css',
			lineNumbers: true,
			maxLines: 20,
			minLines: 10,
		});

		let content = element.value.trim();

		element.codeCup.onUpdate(code => {
			if (element.codeCupInitialized) {
				element.value = code;
			}
		});

		element.codeCup.updateCode(content);
		element.codeCupInitialized = true;

		wrapper.appendChild(element);
	}


	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		const addedNodes = mutation.addedNodes;
		addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initCodeEditor(node);
			}

			const codeEditors = node.querySelectorAll(this.selector);
			codeEditors.forEach((codeEditor) => {
				this.initCodeEditor(codeEditor);
			});
		});
	}
}
