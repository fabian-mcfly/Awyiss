// noinspection JSUnusedGlobalSymbols

/**
 * @typedef {Object} DialogButtons
 * @property {HTMLButtonElement} close - Close button
 * @property {HTMLButtonElement} zoomIn - Zoom in button
 * @property {HTMLButtonElement} zoomOut - Zoom out button
 * @property {HTMLButtonElement} fullscreen - Fullscreen toggle button
 *
 * @typedef {import('../Panzoom/panzoom').default} Panzoom
 *
 * @typedef {HTMLDialogElement & {
 *   panzoom: Panzoom,
 *   buttons: DialogButtons
 * }} EnhancedDialog
 */

import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';
import Panzoom from 'Panzoom/panzoom';

export default class SurveysController {
	/**
	 * The config statuses object.
	 * @type {Object}
	 */
	configStatuses
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The form element
	 * @type {HTMLFormElement}
	 */
	form;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The settings used for panzoom.
	 * @type {object}
	 */
	panzoomSettings = {
		animate: true,
		maxScale: 5,
		minScale: 1,
		step: .25,
		panOnlyWhenZoomed: false,
		//transition: false,
	};


	constructor() {
		if (!document.body.classList.contains('SurveysController')) {
			return;
		}

		if (document.body.classList.contains('OverviewAction') || document.body.classList.contains('AnalyzeAction')) {
			const diagramButtons = document.querySelectorAll('.Button-ShowDiagram');
			diagramButtons.forEach(button => {
				this.eventHandler.add('click', this.handleOverviewDiagramButton.bind(this), button);
			});
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.Surveys.Form'));
		}

		if (document.body.classList.contains('DiagramAction')) {
			const dialog = document.getElementById('Surveys-Diagram');
			mermaid.run({
				querySelector: '#Surveys-Diagram .Diagram',
			}).then(() => {
				this.initDiagramDialog(dialog);
			});
		}
	}


	/**
	 * Initialize the form logic
	 * @param {HTMLFormElement} form The form element
	 * @returns {void}
	 */
	initForm(form) {
		this.form = form.querySelector('#SurveyForm');

		this.eventHandler.add('beforeUpdate', () => {
			this.form.scrollPosition = window.scrollY;
		}, this.form);

		this.eventHandler.add('afterUpdate', () => {
			setTimeout(() => {
				window.scrollTo(0, this.form.scrollPosition);
			}, 400);
		}, this.form);

		// Load all config statuses from localStorage
		this.configStatuses = JSON.parse(localStorage.getItem('questionConfigStatuses')) || {};

		const availableQuestionsList = form.querySelector('ul.AvailableQuestions-List');
		this.initAvailableQuestionsList(availableQuestionsList);

		const assignedQuestionsList = form.querySelector('ul.AssignedQuestions-List');
		this.initAssignedQuestionsList(assignedQuestionsList);

		const diagramButtons = form.querySelectorAll('.Button-ShowDiagram');
		diagramButtons.forEach(button => {
			this.eventHandler.add('click', this.handleDiagramButton.bind(this), button);
		});

		// If the document is loaded,
		mermaid.initialize({startOnLoad: false});

		this.observer.addObserver(this.observeMutations.bind(this), form);
	}


	/**
	 * Initialize the available questions list
	 * @param {HTMLUListElement} availableQuestionsList
	 * @returns {void}
	 */
	initAvailableQuestionsList(availableQuestionsList) {
		availableQuestionsList.removeAttribute('data-sortable');

		window.nestedListHandler.initSortable(availableQuestionsList, {
			groupName: {
				name: 'AssignedQuestions-List',
				pull: 'clone',
				put: false,
			},
			handle: '.ListItem-Inner',
			sort: false,
			onEnd: function (event) {
				const sortable = window.nestedListHandler;
				if (event.to.matches('.AvailableQuestions-List')) {
					sortable.onEnd(event);
					return false;
				}

				event.clone.id = event.item.id;

				const repeatedQuestions = event.to.dataset.repeatedQuestions === 'true';
				if (!repeatedQuestions) {
					event.clone.dataset.sortable = 'false';
				}

				// Create a random hash
				const randHash = Math.random().toString(36).substring(2, 15);

				event.item.classList.remove(event.item.id, 'AvailableQuestions-ListItem');
				event.item.id = `AssignedQuestions-ListItem${randHash}`
				event.item.classList.add(event.item.id, 'AssignedQuestions-ListItem');
				event.item.removeAttribute('data-adjust-scroll-position');

				const nextIndex = event.to.dataset.nextIndex;

				// All inputs with name starting with survey_survey_questions[x], where x is a number
				// replace the x with the number of the question
				const inputs = event.item.querySelectorAll('input[name^="survey_survey_questions["], select[name^="survey_survey_questions["]');
				inputs.forEach(input => {
					// noinspection RegExpRedundantEscape
					input.name = input.name.replace(/survey_survey_questions\[(\d+)\]/, `survey_survey_questions[${nextIndex}]`);
					input.disabled = false;
				});

				sortable.onEnd(event);

				this.reloadForm();
			}.bind(this)
		});

		// Add an add/remove button to each list item
		const items = availableQuestionsList.querySelectorAll(`.ListItem`);
		items.forEach((item) => {
			if (item.dataset.sortable === 'false') {
				return;
			}

			const addButton = document.createElement('button');
			addButton.type = 'button';

			addButton.classList.add('Button', 'Button-Add');
			item.appendChild(addButton);
		});

		// Add event listeners to the add/remove buttons
		window.eventHandler.add('click', this.handleAddButtonClick.bind(this), availableQuestionsList);
	}


	/**
	 * Initialize the assigned questions list
	 * @param {HTMLUListElement} assignedQuestionsList
	 * @returns {void}
	 */
	initAssignedQuestionsList(assignedQuestionsList) {
		this.eventHandler.add('click', this.handleClick.bind(this), assignedQuestionsList);

		const questions = assignedQuestionsList.querySelectorAll('.AssignedQuestions-ListItem');
		questions.forEach(question => {
			const questionIdentifier = question.querySelector('input[name^="survey_survey_questions["][name$="[identifier]"]').value;
			const isOpen = this.configStatuses[questionIdentifier] || false;

			const settings = question.querySelectorAll('.Settings');
			// Toggle the visibility of each settings element based on the configStatuses
			settings.forEach(element => {
				element.classList.toggle('Visible', isOpen);
			})
		});

		const diagramButtons = document.querySelectorAll('.Button-ShowDiagram');
		diagramButtons.forEach(button => {
			button.classList.toggle('Disabled', questions.length === 0);
		});

		// Initialize the sortable for the answers lists
		const answersLists = assignedQuestionsList.querySelectorAll('.Answers-List');
		answersLists.forEach(answersList => {
			window.nestedListHandler.initSortable(answersList, {
				//groupName: `Answers-List[${nextIndex}]`,
				handle: '.SortableHandle',
			});


			const sortable = answersList.sortable;

			const onMoveDefault = sortable.option('onMove');
			sortable.option('onMove', event => {
				// noinspection JSUnresolvedReference
				const defaultReturn = onMoveDefault(event);

				// If the default return is false, then we don't want to do anything
				if (defaultReturn === false) {
					return false;
				}

				const customAnswer = answersList.querySelector('.Answers-ListItem-CustomAnswer');

				if (!customAnswer) {
					return defaultReturn;
				}

				// Prevent moving the custom answer item
				if (event.related === customAnswer && event.willInsertAfter === true) {
					return false;
				}

				return defaultReturn;
			});
		});
	}


	/**
	 * Handle the click event
	 * @param {MouseEvent} event
	 * @returns {void}
	 */
	handleClick(event) {
		const target = event.target;

		if (target.matches('.Button-Configure')) {
			return this.handleConfigureButton(target);
		}

		if (target.matches('.Button-Delete')) {
			return this.handleDeleteButton(target);
		}

		if (target.matches('.Button-PickQuestion')) {
			return this.handlePickQuestionButton(target);
		}
		else if (document.documentElement.classList.contains('PickQuestionMode')) {
			if (target.closest('.AssignedQuestions-ListItem.Disabled')) {
				return;
			}

			this.cancelPickQuestionMode(target.closest('.AssignedQuestions-ListItem'));
		}
	}

	/**
	 * Handle the add/remove button click
	 * @param {MouseEvent} event
	 */
	handleAddButtonClick(event) {
		const button = event.target.closest('.Button-Add');

		if (!button) {
			return;
		}

		const item = button.closest('.ListItem').cloneNode(true);
		const targetList = this.form.querySelector('ul.AssignedQuestions-List');

		// Move the item to the target list
		targetList.appendChild(item);

		// Create a random hash
		const randHash = Math.random().toString(36).substring(2, 15);

		item.classList.remove(item.id, 'AvailableQuestions-ListItem');
		item.id = `AssignedQuestions-ListItem${randHash}`
		item.classList.add(item.id, 'AssignedQuestions-ListItem');
		item.removeAttribute('data-adjust-scroll-position');

		const nextIndex = targetList.dataset.nextIndex;

		// All inputs with name starting with survey_survey_questions[x], where x is a number
		// replace the x with the number of the question
		const inputs = item.querySelectorAll('input[name^="survey_survey_questions["], select[name^="survey_survey_questions["]');
		inputs.forEach(input => {
			// noinspection RegExpRedundantEscape
			input.name = input.name.replace(/survey_survey_questions\[(\d+)\]/, `survey_survey_questions[${nextIndex}]`);
			input.disabled = false;
		});

		this.reloadForm();
	}


	/**
	 * Cancel the pick question mode.
	 * @param {HTMLElement|null} target - The target element that triggered the cancel action.
	 */
	cancelPickQuestionMode(target = null) {
		const activeButton = document.querySelector('.Button-PickQuestion.Active');
		if (activeButton) {
			document.documentElement.classList.remove('PickQuestionMode');
			activeButton.classList.remove('Active');

			activeButton.dragData = {
				time: Date.now(),
				scrollPos: window.scrollY,
				elementPos: activeButton.getBoundingClientRect().top + window.scrollY
			};

			// Start adjusting the scroll position
			// noinspection JSUnresolvedReference
			window.nestedListHandler.adjustScrollPosition(activeButton, false);

			if (target) {
				const targetIdentifier = target.querySelector('input[name^="survey_survey_questions["][name$="[identifier]"]').value;
				const nextActionSelect = activeButton.closest('.Settings').querySelector('select[name^="survey_survey_questions["][name$="[next_action_target]"]');
				if (nextActionSelect) {
					nextActionSelect.value = targetIdentifier;
					nextActionSelect.dispatchEvent(new Event('input', { bubbles: true }));
				}

				setTimeout(() => {
					// If the active button is not in view, scroll to it
					if (activeButton.getBoundingClientRect().top < 0 || activeButton.getBoundingClientRect().bottom > window.innerHeight) {
						activeButton.scrollIntoView({behavior: 'smooth', block: 'center'});
					}
				}, 300);
			}
		}

		const activeListItem = document.querySelector('.AssignedQuestions-ListItem.Disabled');
		if (activeListItem) {
			activeListItem.classList.remove('Disabled');
		}

		this.eventHandler.remove('keydown', this.boundKeyDownHandler);
	}

	/**
	 * Handle the key press event.
	 * @param {KeyboardEvent} event
	 */
	handleKeyDown(event) {
		if (event.key === 'Escape') {
			this.cancelPickQuestionMode();
		}
	}


	/**
	 * Handle the delete button click event.
	 * @param {HTMLElement} target
	 */
	handlePickQuestionButton(target) {
		const start = !document.documentElement.classList.contains('PickQuestionMode');

		// Update the end time of the drag operation, scroll position, and element position directly on the event.item object
		// noinspection JSUnresolvedReference
		target.dragData = {
			time: Date.now(),
			scrollPos: window.scrollY,
			elementPos: target.getBoundingClientRect().top + window.scrollY
		};

		// Start adjusting the scroll position
		// noinspection JSUnresolvedReference
		window.nestedListHandler.adjustScrollPosition(target, start);

		document.documentElement.classList.toggle('PickQuestionMode');
		target.classList.toggle('Active');

		if (start) {
			this.boundKeyDownHandler = this.handleKeyDown.bind(this);
			this.eventHandler.add('keydown', this.boundKeyDownHandler);

			target.closest('.AssignedQuestions-ListItem').classList.add('Disabled');
		}
		else {
			this.cancelPickQuestionMode();
		}
	}


	/**
	 * Handle the delete button click event.
	 * @param {HTMLElement} target
	 */
	handleDeleteButton(target) {
		const answer = target.closest('.Answers-ListItem');
		if (answer) {
			answer.remove();
			window.formLeaveConfirmation.formChanged();

			return;
		}

		const question = target.closest('.AssignedQuestions-ListItem');
		if (!question) {
			return;
		}

		const questionId = +question.querySelector('input[name^="survey_survey_questions["][name$="[survey_question_id]"]').value;

		const selector = `input[name^="survey_survey_questions["][name$="[survey_question_id]"][value="${questionId}"]`;
		const input = document.getElementById('AvailableQuestions-List').querySelector(selector);

		if (input) {
			input.closest('.ListItem').dataset.sortable = 'true';
		}

		question.remove();
		window.formLeaveConfirmation.formChanged();

		this.reloadForm();
	}


	/**
	 * Handle the configure button click event.
	 * @param {HTMLElement} target
	 */
	handleConfigureButton(target) {
		const question = target.closest('.AssignedQuestions-ListItem');
		if (!question) {
			return;
		}

		const questionIdentifier = question.querySelector('input[name^="survey_survey_questions["][name$="[identifier]"]').value;
		const isOpen = this.configStatuses[questionIdentifier] || false;

		const settings = question.querySelectorAll('.Settings');
		// Toggle the visibility of each settings element based on the configStatuses
		settings.forEach(element => {
			element.classList.toggle('Visible', !isOpen);
		})

		this.configStatuses[questionIdentifier] = !isOpen;
		localStorage.setItem('questionConfigStatuses', JSON.stringify(this.configStatuses));
	}


	/**
	 * Handle the flow chart button click event.
	 */
	handleDiagramButton() {
		const diagramButtons = document.querySelectorAll('.Button-ShowDiagram');
		diagramButtons.forEach(button => {
			button.classList.add('Disabled');
		});

		this.reloadForm(() => {
			/** @type {EnhancedDialog} */
			const dialog = document.getElementById('SurveyForm').querySelector('.Questions-Diagram');

			dialog.showModal();

			if (!dialog.querySelector('.Diagram').dataset.processed) {
				setTimeout(async() => {
					await mermaid.run({
						querySelector: '.Questions-Diagram .Diagram',
					}).then(() => {
						this.initDiagramDialog(dialog);
					});
				}, 300);
			}
			else if (!dialog.panzoom) {
				this.initPanzoom(dialog);
			}
		});
	}


	/**
	 * Handle the overview diagram button click event.
	 * @param {MouseEvent} event
	 */
	handleOverviewDiagramButton(event) {
		event.preventDefault();
		event.stopPropagation();

		let dialog = document.getElementById('Surveys-Diagram');
		if (dialog) {
			dialog.remove();
		}

		const target = event.target;

		return fetch(`${target.href}`, {
			method: 'POST',
			headers: {
				'Accept': 'text/html',
				'Content-Type': 'text/html',
				'X-Requested-With': 'XMLHttpRequest',
			}
		})
		.then(response => response.text())
		.then(html => {
			// Parse the HTML string into a Document object
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			// Select the dialog
			const dialog = doc.querySelector('#Surveys-Diagram');

			// Append the dialog to the body
			document.body.appendChild(dialog);

			dialog.showModal();

			setTimeout(async() => {
				await mermaid.run({
					querySelector: '#Surveys-Diagram .Diagram',
				}).then(() => {
					this.initDiagramDialog(dialog);
				});
			}, 300);
		})
		.catch(error => {
			console.error('There has been a problem with the fetch operation:', error);
		});
	}


	/**
	 * Reload the form and update the UI.
	 * @param {Function|null} thenCallback - Optional callback to execute after the form is reloaded.
	 */
	reloadForm(thenCallback = null) {
		// noinspection JSIgnoredPromiseFromCall
		window.formUpdater.sendRequest(this.form).then(() => {
			if (thenCallback) {
				thenCallback();
			}
		});
	}


	/**
	 * Initialize the dialog element.
	 * @param {EnhancedDialog} dialog
	 */
	initDiagramDialog(dialog) {
		dialog.buttons = {};

		dialog.buttons.close = dialog.querySelector('.Button-Close');
		dialog.buttons.close.addEventListener('click', event => {
			dialog.close();

			dialog.panzoom?.destroy();
			dialog.panzoom = null;

			event.preventDefault();
			event.stopPropagation();
		});

		dialog.buttons.zoomIn = dialog.querySelector('.Button-Zoom.Button-ZoomIn');
		dialog.buttons.zoomIn.addEventListener('click', event => {
			// noinspection JSValidateTypes
			dialog.panzoom.zoomIn();
			event.preventDefault();
			event.stopPropagation();
		});

		dialog.buttons.zoomOut = dialog.querySelector('.Button-Zoom.Button-ZoomOut');
		dialog.buttons.zoomOut.addEventListener('click', event => {
			// noinspection JSValidateTypes
			dialog.panzoom.zoomOut();
			event.preventDefault();
			event.stopPropagation();
		});

		dialog.buttons.fullscreen = dialog.querySelector('.Button-Fullscreen');
		dialog.buttons.fullscreen.addEventListener('click', () => {
			if (document.fullscreenElement) {
				// noinspection JSIgnoredPromiseFromCall
				document.exitFullscreen();
			}
			else {
				// noinspection JSIgnoredPromiseFromCall
				dialog.querySelector('.Inner').requestFullscreen();
			}
		});

		dialog.addEventListener('wheel', event => {
			dialog.panzoom.zoomWithWheel(event);
		});

		this.initPanzoom(dialog);
	}

	/**
	 * Initialize the panzoom for the dialog element.
	 * @param {EnhancedDialog} dialog
	 */
	initPanzoom(dialog) {
		const element = dialog.querySelector('.flowchart');

		dialog.buttons.zoomIn.classList.add('Visible');
		dialog.buttons.zoomOut.classList.add('Visible');

		dialog.buttons.zoomIn.classList.remove('Disabled');
		dialog.buttons.zoomIn.inert = false;
		dialog.buttons.zoomOut.classList.add('Disabled');
		dialog.buttons.zoomOut.inert = true;

		dialog.panzoom = Panzoom(element, this.panzoomSettings);

		element.addEventListener('panzoomzoom', event => {
			const atMaxScale = event.detail.scale >= dialog.panzoom.getOptions().maxScale;
			dialog.buttons.zoomIn.classList.toggle('Disabled', atMaxScale);
			dialog.buttons.zoomIn.inert = atMaxScale;

			const atMinScale = event.detail.scale <= dialog.panzoom.getOptions().minScale;
			dialog.buttons.zoomOut.classList.toggle('Disabled', atMinScale);
			dialog.buttons.zoomOut.inert = atMinScale;

			if (atMinScale) {
				dialog.panzoom.pan(0, 0);
			}
		});
	}


	/**
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
			if (!(node instanceof HTMLElement)) {
				return;
			}

			if (node.matches('ul.AvailableQuestions-List')) {
				this.initAvailableQuestionsList(node);
			}

			const availableQuestionsList = node.querySelector('ul.AvailableQuestions-List');
			if (availableQuestionsList) {
				this.initAvailableQuestionsList(availableQuestionsList);
			}

			if (node.matches('ul.AssignedQuestions-List')) {
				this.initAssignedQuestionsList(node);
			}

			const assignedQuestionsList = node.querySelector('ul.AssignedQuestions-List');
			if (assignedQuestionsList) {
				this.initAssignedQuestionsList(assignedQuestionsList);
			}
		})
	}
}

/**
 * Expose the class globally
 * @global
 * @type {SurveysController}
 */
window.SurveysController = SurveysController;