//noinspection JSUnusedGlobalSymbols

export default class UsergroupsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('UsergroupsController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.Usergroups.Form'));
		}
	}

	/**
	 * Initialize the form related functionality.
	 *
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		const permissions = form.querySelector('.Fieldset-Permissions');
		window.eventHandler.add('click', event => {
			if (!event.target.matches('label')) {
				return;
			}

			const labels = event.target.closest('.Labels');

			clearTimeout(labels.timeout);

			labels.classList.add('Animate');
			labels.timeout = setTimeout(() => labels.classList.remove('Animate'), 500);
		}, permissions);

		// Add drag functionality for .Labels elements
		const labelsElements = permissions.querySelectorAll('.Labels');
		labelsElements.forEach(labels => {
			let isDragging = false;

			const handleMouseDown = (e) => {
				isDragging = true;
				labels.classList.add('Dragging');

				// Set initial --dragX based on currently selected radio
				const radios = Array.from(labels.parentElement.querySelectorAll('input[type="radio"]'));
				const checkedRadio = radios.find(radio => radio.checked);
				const currentValue = checkedRadio ? checkedRadio.value : '';

				let initialDragX = 0;
				if (currentValue !== '') {
					initialDragX = currentValue === '1' ? 28 : -28;
				}

				labels.style.setProperty('--dragX', `${initialDragX}px`);
				e.preventDefault();
			};

			const handleMouseMove = (e) => {
				if (!isDragging) {
					return;
				}

				// Calculate position relative to center of the element
				const rect = labels.getBoundingClientRect();
				const centerX = rect.left + rect.width / 2;
				const dragX = Math.max(-28, Math.min(28, e.clientX - centerX));

				labels.style.setProperty('--dragX', `${dragX}px`);

				// Calculate which third of the element we're in
				const relativeX = e.clientX - rect.left;
				const thirdWidth = rect.width / 3;

				// Get all radio buttons in this permission
				const radios = Array.from(labels.parentElement.querySelectorAll('input[type="radio"]'));

				// Determine which radio to select based on position
				let targetValue = '1';
				if (relativeX < thirdWidth) {
					// First third - disabled (value '0')
					targetValue = '0';
				}
				else if (relativeX < thirdWidth * 2) {
					// Middle third - indifferent (value '')
					targetValue = '';
				}

				// Check the appropriate radio
				radios.forEach(radio => {
					radio.checked = radio.value === targetValue;
				});
			};

			const handleMouseUp = () => {
				if (!isDragging) {
					return;
				}

				isDragging = false;
				labels.classList.remove('Dragging');
				labels.style.removeProperty('--dragX');
			};

			labels.addEventListener('mousedown', handleMouseDown);
			document.addEventListener('mousemove', handleMouseMove);
			document.addEventListener('mouseup', handleMouseUp);
		});

		// Swiping right will change from '0' to '' to '1'
		// Swiping left will change from '1' to '' to '0'
		const permissionElements = permissions.querySelectorAll('.Permission');
		permissionElements.forEach(permission => {
			let startX = 0;
			let startY = 0;

			permission.addEventListener('touchstart', e => {
				startX = e.touches[0].clientX;
				startY = e.touches[0].clientY;
			}, {passive: true});

			permission.addEventListener('touchend', e => {
				const endX = e.changedTouches[0].clientX;
				const endY = e.changedTouches[0].clientY;
				const deltaX = endX - startX;
				const deltaY = endY - startY;

				// Only consider horizontal swipes with a threshold and ensure it's more horizontal than vertical
				if (Math.abs(deltaX) < 50 || Math.abs(deltaY) > Math.abs(deltaX)) {
					return;
				}

				// Find all radio buttons in this single permission (one radio group)
				const radios = Array.from(permission.querySelectorAll('input[type="radio"]'));

				// Get the currently checked radio
				const checkedRadio = radios.find(radio => radio.checked);
				const currentValue = checkedRadio ? checkedRadio.value : '';

				// Swipe right: '0' → '' → '1', Swipe left: '1' → '' → '0'
				const newValue = deltaX > 0
					? (currentValue === '0' ? '' : '1')
					: (currentValue === '1' ? '' : '0');

				// Apply the new value to this radio group
				radios.forEach(radio => radio.checked = radio.value === newValue);

				// Animate the labels of the newly checked radio
				const newCheckedRadio = radios.find(radio => radio.checked);
				const labels = newCheckedRadio?.parentElement.querySelector('.Labels');

				clearTimeout(labels.timeout);
				labels.classList.add('Animate');
				labels.timeout = setTimeout(() => labels.classList.remove('Animate'), 500);
			}, {passive: true});
		});

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

/**
 * Expose the class globally
 * @global
 * @type {UsergroupsController}
 */
window.UsergroupsController = UsergroupsController;
