// noinspection JSUnusedGlobalSymbols

export default class SurveysController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (
			document.body.classList.contains('AddAction') ||
			document.body.classList.contains('EditAction')
		) {
			this.initForm();
		}
	}

	/**
	 * Initialize the form logic
	 * @returns {void}
	 */
	initForm() {
		const availableQuestionsList = document.querySelector('ul.AvailableQuestions-List');
		this.initAvailableQuestionsList(availableQuestionsList);

		const assignedQuestionsList = document.querySelector('ul.AssignedQuestions-List');
		this.initAssignedQuestionsList(assignedQuestionsList);

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
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
			sort: false,
			onEnd: function (event) {
				if (event.to.matches('.Pages-AvailableQuestions-List')) {
					this.onEnd(event);
					return false;
				}

				const repeatedQuestions = event.to.dataset.repeatedQuestions === 'true';
				if (!repeatedQuestions) {
					event.clone.dataset.sortable = 'false';
				}

				const randHash = Math.random().toString(36).substring(2, 10);

				event.item.classList.remove(event.item.id, 'AvailableQuestion-ListItem');
				event.item.id = `AssignedQuestions-ListItem${randHash}`
				event.item.classList.add(event.item.id, 'AssignedQuestion-ListItem');

				const nextIndex = event.to.dataset.nextIndex;

				// All inputs with name starting with survey_survey_questions[x], where x is a number
				// replace the x with the number of the question
				const inputs = event.item.querySelectorAll('input[name^="survey_survey_questions["]');
				inputs.forEach(input => {
					input.name = input.name.replace(/survey_survey_questions\[(\d+)\]/, `survey_survey_questions[${nextIndex}]`);
					input.disabled = false;

					if (input.name.endsWith('][identifier]')) {
						input.value = randHash;
					}

					if (!input.id) {
						return;
					}

					const oldId = input.id;
					input.id = input.id.replace(/Survey-SurveySurveyQuestions\[(\d+)\]/, `Survey-SurveySurveyQuestions[${nextIndex}]`);

					const label = event.item.querySelector(`label[for="${oldId}"]`);
					if (label) {
						label.setAttribute('for', input.id);
					}
				});

				// Update the next index
				event.to.dataset.nextIndex = parseInt(nextIndex) + 1;

				this.initSortable(event.item.querySelectorAll('.Answers-List'), {
					//groupName: `Answers-List[${nextIndex}]`,
				});

				this.onEnd(event);
			}
		});
	}


	/**
	 * Initialize the assigned questions list
	 * @param {HTMLUListElement} assignedQuestionsList
	 * @returns {void}
	 */
	initAssignedQuestionsList(assignedQuestionsList) {
		assignedQuestionsList.removeAttribute('data-sortable');

		this.eventHandler.add('click', this.handleClick.bind(this), assignedQuestionsList);

		window.nestedListHandler.initSortable(assignedQuestionsList, {
			groupName: 'AssignedQuestions-List',
			handle: '.ListItem-Inner',
		});

		// Initialize the sortable for the answers lists
		window.nestedListHandler.initSortable(assignedQuestionsList.querySelectorAll('.Answers-List'), {
			//groupName: `Answers-List[${nextIndex}]`,
		});
	}

	/**
	 * Handle the click event
	 * @param {MouseEvent} event
	 * @returns {void}
	 */
	handleClick(event) {
		const target = event.target;
		if (target.matches('.Button-Remove')) {
			const answer = target.closest('.Answers-ListItem');
			if (answer) {
				answer.remove();
				window.formLeaveConfirmation.formChanged();
				return;
			}

			const question = target.closest('.AssignedQuestion-ListItem');
			if (question) {
				const questionId = +question.querySelector('input[name^="survey_survey_questions["][name$="[survey_question_id]"]').value;

				const selector = `input[name^="survey_survey_questions["][name$="[survey_question_id]"][value="${questionId}"]`;
				const input = document.getElementById('AvailableQuestions-List').querySelector(selector);

				if (input) {
					input.closest('.ListItem').dataset.sortable = 'true';
				}

				question.remove();
				window.formLeaveConfirmation.formChanged();
				return;
			}
		}
	}

	/**
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
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