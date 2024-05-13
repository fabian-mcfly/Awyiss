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
			window.eventHandler.add('click', event => {
				if (!event.target.matches('label')) {
					return;
				}

				const labels = event.target.closest('.Labels');

				clearTimeout(labels.timeout);

				labels.classList.add('Animate');
				labels.timeout = setTimeout(() => labels.classList.remove('Animate'), 500);
			}, permissions);

			const permissionTitles = permissions.querySelectorAll('.Fieldset-Permission > .Title');
			permissionTitles.forEach(title => {
				this.eventHandler.add('click', () => {
					// Get all radio buttons in the permission fieldset
					const radios = Array.from(title.parentElement.querySelectorAll('input[type="radio"]'));

					// Group the radio buttons by name
					const radioGroups = radios.reduce((groups, radio) => {
						const name = radio.name;
						if (!groups[name]) {
							groups[name] = [];
						}
						groups[name].push(radio);
						return groups;
					}, {});

					// Create an array that contains the value of each group
					const groupValues = Object.values(radioGroups).map(group => {
						// Filter out the radio buttons that have a value of '1'
						const grantedRadios = group.filter(radio => radio.value === '1');

						// Check if all 'granted' radio buttons are checked
						return grantedRadios.every(radio => radio.checked) ? '1' : '0';
					});

					// Check if all groups have the same value
					const allSameValue = groupValues.every(value => value === groupValues[0]);

					// Determine the new value based on whether all groups have the same value or not
					const newValue = allSameValue ? (groupValues[0] === '1' ? '0' : '1') : '1';

					// Apply the new value to all radio buttons
					Object.values(radioGroups).forEach(group => {
						group.forEach(radio => {
							radio.checked = radio.value === newValue;

							if (radio.checked) {
								const labels = radio.parentElement.querySelector('.Labels');

								clearTimeout(labels.timeout);

								labels.classList.add('Animate');
								labels.timeout = setTimeout(() => labels.classList.remove('Animate'), 500);
							}
						});
					});
				}, title);
			});
		}
	}
}

/**
 * Expose the class globally
 * @global
 * @type {UsergroupsController}
 */
window.UsergroupsController = UsergroupsController;
