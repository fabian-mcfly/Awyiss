//noinspection JSUnusedGlobalSymbols

export default class UsergroupsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (document.querySelector('.Usergroups.Form')) {
			const permissions = document.querySelector('.Fieldset-Permissions');
			window.eventHandler.add('click', e => {
				if (!e.target.matches('label')) {
					return;
				}

				const labels = e.target.closest('.Labels');

				clearTimeout(labels.timeout);

				labels.classList.add('Animate');
				labels.timeout = setTimeout(() => labels.classList.remove('Animate'), 500);
			}, permissions);
		}
	}
}

/**
 * Expose the class globally
 * @global
 * @type {UsergroupsController}
 */
window.UsergroupsController = UsergroupsController;
