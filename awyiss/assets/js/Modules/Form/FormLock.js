//noinspection JSUnusedGlobalSymbols

/**
 * FormLocks class
 * This class is used to handle form updates and reloads.
 */
export default class FormLock {
	/**
	 * The selector for forms to be handled
	 * @type {string}
	 */
	selector = 'form[data-locked]';

	constructor() {
		const forms = document.querySelectorAll(this.selector);
		forms.forEach(form => {
			this.initForm(form);
		});

		// Observe the document
		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		window.eventHandler.add('click', this.handleClickEvent.bind(this));
		window.eventHandler.add('unload', this.handleUnload.bind(this, this.selector));
	}

	/**
	 * Initializes the given form by handling its lock/unlock state and setting up relevant timers or dialogs.
	 *
	 * @param {HTMLFormElement} form - The form element to be initialized, containing lock-related attributes and elements.
	 * @return {void} This method does not return a value.
	 */
	initForm(form) {
		// Get the form's unlock time
		const unlockTime = form.dataset.lockedUntil;

		// The unlock time is stored in ISO format
		const unlockDate = new Date(unlockTime);

		let remainingTime = unlockDate - Date.now();
		let timeoutTime = remainingTime - 300_000;
		if (timeoutTime < 0) {
			timeoutTime = 0;
		}

		if (!form.lockDialog) {
			form.lockDialog = form.querySelector('.LockDialog');
		}

		// If the form is not locked, start a timer to show a message
		// to inform the user that the form will be unlocked in a few minutes
		if (form.dataset.locked === 'false') {
			if (timeoutTime) {
				form.lockDialog.classList.remove('Visible');
			}

			if (form.lockTimeout) {
				clearTimeout(form.lockTimeout);
			}

			form.lockTimeout = setTimeout(() => {
				this.showUnlockWarning(form);
			}, timeoutTime);

			return;
		}

		// If the form is locked, show the warning immediately
		if (form.querySelector('.LockDialog')) {
			this.showLockedWarning(form);
		}
	}

	/**
	 * Handles a click event and processes the request lock button logic.
	 *
	 * @param {Event} event - The event object triggered by the click action.
	 * @return {void} This method does not return a value.
	 */
	handleClickEvent(event) {
		const requestLockButton = event.target.closest('.Button-RequestLock');
		if (requestLockButton) {
			event.stopPropagation();
			event.preventDefault();

			if (requestLockButton.classList.contains('FetchInProgress')) {
				return;
			}

			// noinspection JSIgnoredPromiseFromCall
			this.requestLock(event.target.closest('form'), requestLockButton);
		}
	}

	/**
	 * Handles the unload event for forms and releases locks on the specified entities using navigator.sendBeacon.
	 *
	 * @param {string} selector The CSS selector used to identify the forms to process. If not provided, defaults to `this.selector`.
	 * @return {void} No return value. Executes release lock actions for matched forms.
	 */
	handleUnload(selector) {
		const forms = document.querySelectorAll(selector || this.selector);
		if (!forms.length) {
			return;
		}

		forms.forEach(form => {
			const formData = new FormData();
			formData.append('controller', form.lockDialog.dataset.controller);
			formData.append('id', parseInt(form.lockDialog.dataset.entityId));
			formData.append('created_on', form.lockDialog.dataset.createdOn);

			navigator.sendBeacon(`${baseUrl}backend/${languageShortcode}/${form.lockDialog.dataset.controller}/release-lock/`, formData);
		});
	}

	/**
	 * Locks a form to prevent further submissions or modifications by setting
	 * a "locked" state and clearing the form's `action` attribute.
	 *
	 * @param {HTMLFormElement} form - The form element to be locked.
	 * @return {void} This method does not return a value.
	 */
	lockForm(form) {
		// The form should now be unlocked for others.
		form.dataset.locked = 'true';

		if (form.action) {
			form.dataset.action = form.action;
			// Unset the action attribute
			form.action = '#';
		}
	}

	/**
	 * Unlocks a given form, allowing interactions and restoring its action attribute if previously modified.
	 *
	 * @param {HTMLFormElement} form - The HTML form element to be unlocked.
	 * @return {void} This method does not return any value.
	 */
	unlockForm(form) {
		// The form should now be locked for others.
		form.dataset.locked = 'false';

		if (form.dataset.action) {
			form.action = form.dataset.action;
			delete form.dataset.action;
		}
	}

	/**
	 * @typedef {Object} LockResponseData
	 * @property {string} lockedUntil - ISO date string of when the lock expires
	 * @property {boolean} isOwnLock - Whether the current user owns the lock
	 * @property {string} referenceDate - Reference date for comparison
	 * @property {string} createdOn - When the lock was created
	 * @property {string} lockTimedOutMessage - HTML message for timed out lock
	 * @property {string} lockWarningMessage - HTML message for warning
	 * @property {string} lockedMessage - HTML message for locked state

	 * @typedef {Object} LockResponse
	 * @property {string} status - Response status ('success' or error)
	 * @property {LockResponseData} data - The lock data

	 * Request a lock for the form
	 * @param {HTMLFormElement} form - The form to lock
	 * @param {HTMLButtonElement} button - The button that was clicked
	 * @returns {Promise<LockResponse>} The response from the server
	 */
	requestLock(form, button) {
		// Disable the button
		if (!button.querySelector('.Loading')) {
			button.appendChild(document.createElement('div')).className = 'Loading';
		}
		button.classList.add('FetchInProgress');

		// Reset width and height of the button since disabled buttons have no pointer events
		// so the mouse leave event won't be triggered
		const hoverElement = button.querySelector('.Hover');
		if (hoverElement) {
			hoverElement.style.width = '';
			hoverElement.style.height = '';
		}

		const data = {
			controller: form.lockDialog.dataset.controller,
			id: parseInt(form.lockDialog.dataset.entityId),
		};

		return fetch(`${baseUrl}backend/${languageShortcode}/${form.lockDialog.dataset.controller}/request-lock/`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify(data)
		}).then(response => {
			button.classList.remove('FetchInProgress');

			if (!response.ok) {
				throw new Error('Failed to renew the lock.');
			}

			return response.json();
		}).then(data => {
			if (data.status !== 'success') {
				throw new Error('Failed to renew the lock.');
			}

			// Update the form's unlock time
			form.dataset.lockedUntil = data.data.lockedUntil;

			if (data.data.isOwnLock) {
				if (form.lockDialog.dataset.referenceDate === data.data.referenceDate) {
					this.unlockForm(form);
				}
				else {
					// Reload the whole page
					window.formLeaveConfirmation.unlock();
					window.location.reload();
				}
			}
			else {
				this.lockForm(form);
			}

			form.lockDialog.dataset.createdOn = data.data.createdOn;

			form.querySelector('.LockTimedOutMessage .Message').innerHTML = data.data.lockTimedOutMessage;
			form.querySelector('.LockWarningMessage .Message').innerHTML = data.data.lockWarningMessage;
			form.querySelector('.LockedMessage .Message').innerHTML = data.data.lockedMessage;

			// Re-init the form (resets the timeouts);
			this.initForm(form);
		});
	}

	showLockedWarning(form) {
		form.lockDialog.classList.add('Visible');
		form.lockDialog.dataset.status = 'locked';

		this.updateLockedWarning(form);
	}

	updateLockedWarning(form) {
		if (form.lockTimeout) {
			clearTimeout(form.lockTimeout);
		}

		// Get the form's unlock time
		const unlockTime = form.dataset.lockedUntil;

		// The unlock time is stored in ISO format
		const unlockDate = new Date(unlockTime);

		let remainingTime = unlockDate - Date.now();

		let text = form.lockDialog.dataset.minutesSingular;
		if (remainingTime > 60_000) {
			remainingTime = Math.ceil(remainingTime / 60_000);

			text = form.lockDialog.dataset.minutesPlural;
			text = text.replace('{0}', remainingTime);
		}

		const textElement = form.lockDialog.querySelector('.LockedMessage')
		textElement.querySelector('.Minutes').innerHTML = text;

		form.lockTimeout = setTimeout(() => {
			this.updateLockedWarning(form);
		}, Math.min(remainingTime, 60_000));
	}

	showUnlockWarning(form) {
		form.lockDialog.classList.add('Visible');
		form.lockDialog.form = form;

		form.lockDialog.dataset.status = 'owned';

		this.updateUnlockWarning(form);
	}

	updateUnlockWarning(form) {
		if (form.lockTimeout) {
			clearTimeout(form.lockTimeout);
		}

		// Get the form's unlock time
		const unlockTime = form.dataset.lockedUntil;

		// The unlock time is stored in ISO format
		const unlockDate = new Date(unlockTime);

		let remainingTime = unlockDate - Date.now();

		if (remainingTime <= 0) {
			this.lockForm(form);

			form.lockDialog.dataset.status = 'lockTimedOut';

			return;
		}

		let text = form.lockDialog.dataset.minutesSingular;
		if (remainingTime > 60_000) {
			remainingTime = Math.ceil(remainingTime / 60_000);

			text = form.lockDialog.dataset.minutesPlural;
			text = text.replace('{0}', remainingTime);
		}

		const textElement = form.lockDialog.querySelector('.LockWarningMessage')
		textElement.querySelector('.Minutes').innerHTML = text;

		form.lockTimeout = setTimeout(() => {
			this.updateUnlockWarning(form);
		}, Math.min(remainingTime, 60_000));
	}

	/**
	 * Handle a mutation
	 * @param {MutationRecord} mutation - The mutation record
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initForm(node);
			}

			const forms = node.querySelectorAll(this.selector);
			forms.forEach(form => {
				this.initForm(form);
			});
		})
	}
}