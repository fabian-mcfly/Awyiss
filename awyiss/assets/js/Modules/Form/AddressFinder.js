// noinspection JSUnusedGlobalSymbols

/**
 * AddressFinder class
 *
 * Provides functionality to find geographic coordinates
 * based on a given address using the _route endpoint
 */
export default class AddressFinder {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The selector for the address input field.
	 * @type {string}
	 */
	selector = 'input[data-geocode="true"]';


	constructor() {
		const inputs = document.querySelectorAll(this.selector);
		inputs.forEach((input) => {
			this.initInput(input);
		});

		this.observer.addObserver(this.observeMutations.bind(this));
	}


	/**
	 * Initialize the address input field.
	 *
	 * @param {HTMLInputElement} input - The address input field.
	 */
	initInput(input) {
		const form = input.closest('form');

		const settings = JSON.parse(input.dataset.geocodeSettings || '{}');
		const lat = form.querySelector(settings.lat || 'input[name="lat"]');
		const lng = form.querySelector(settings.lng || 'input[name="lng"]');

		if (!lat || !lng) {
			return;
		}

		input.latInput = lat;
		input.lngInput = lng;
		input.buttonLabel = settings.buttonLabel || 'Geocode';

		this.createButton(input);
	}


	/**
	 * Create a button to trigger the geocode search.
	 *
	 * @param {HTMLInputElement} input - The address input field.
	 */
	createButton(input) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'Button Button-Geocode';
		button.innerHTML = input.buttonLabel;

		input.parentNode.insertBefore(button, input.nextSibling);

		button.addEventListener('click', this.getCoordinates.bind(this, button, input));
	}


	/**
	 * Fetch the coordinates based on the address input.
	 *
	 * @param {HTMLButtonElement} button - The button that triggered the event.
	 * @param {HTMLInputElement} input - The address input field.
	 */
	async getCoordinates(button, input) {
		if (button.classList.contains('FetchInProgress')) {
			return;
		}

		button.classList.add('FetchInProgress');

		if (!button.querySelector('.Loading')) {
			button.appendChild(document.createElement('div')).className = 'Loading';
		}

		// Reset width and height of the button since disabled buttons have no pointer events
		// so the mouse leave event won't be triggered
		const hoverElement = button.querySelector('.Hover');
		if (hoverElement) {
			hoverElement.style.width = '';
			hoverElement.style.height = '';
		}


		const search = input.value;

		if (!search) {
			button.classList.remove('FetchInProgress');
			return;
		}

		const latInput = input.latInput;
		const lngInput = input.lngInput;

		const url = `${baseUrl}${languageShortcode}/_route/find-coordinates/${encodeURIComponent(search)}/`;
		const response = await fetch(url, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		const data = await response.json();

		button.classList.remove('FetchInProgress');

		if (!response.ok) {
			if (!data.addresses) {
				return;
			}

			// Handle 300 response that offers a list of routes
			// and requires the user to select one before proceeding.
			if (response.status === 300) {
				this.buildDialog(data, latInput, lngInput);
			}

			return false;
		}

		const address = data.addresses[0];
		latInput.value = address.lat;
		lngInput.value = address.lng;
	}


	/**
	 * Build a dialog to display the list of routes.
	 *
	 * @param {Object} data - The data containing the routes.
	 * @param {HTMLInputElement} latInput - The latitude input field.
	 * @param {HTMLInputElement} lngInput - The longitude input field.
	 */
	buildDialog(data, latInput, lngInput) {
		const dialog = document.createElement('dialog');
		dialog.id = 'GeocodeMultipleResultsDialog';
		dialog.dataset.title = data.title || 'Multiple results found';

		document.body.appendChild(dialog);

		const helpingDialog = document.querySelector('#WidgetConfigurationOverlay, #OverlayForm');
		let closeButton;
		if (helpingDialog) {
			// Borrow the close button from the dialog
			closeButton = helpingDialog.querySelector('.Button-Close');
		}

		if (closeButton) {
			closeButton = closeButton.cloneNode(true);
		}
		else {
			closeButton = document.createElement('button');
			closeButton.type = 'button';
			closeButton.className = 'Button Button-Close';
			closeButton.textContent = 'Close';
		}

		const buttonArea = document.createElement('div');
		buttonArea.classList.add('ButtonArea');
		dialog.appendChild(buttonArea);
		buttonArea.appendChild(closeButton);

		const inner = document.createElement('div');
		inner.classList.add('Inner');
		dialog.appendChild(inner);

		// Create a list of addresses
		const list = document.createElement('ul');
		list.classList.add('AddressList');
		inner.appendChild(list);

		data.addresses.forEach(item => {
			const listItem = document.createElement('li');
			listItem.classList.add('AddressList-Item');

			listItem.textContent = item.name;
			listItem.dataset.lat = item.lat;
			listItem.dataset.lng = item.lng;

			list.appendChild(listItem);

			listItem.addEventListener('click', () => {
				latInput.value = item.lat;
				lngInput.value = item.lng;
				dialog.close();
			});
		});

		// Append the close button to the inner dialog
		inner.appendChild(closeButton.cloneNode(true));

		dialog.addEventListener('close', () => {
			dialog.remove();
		});

		dialog.addEventListener('click', event => this.handleDialogClick(event));

		dialog.showModal();
	}


	/**
	 * Handle the click event on the dialog.
	 */
	handleDialogClick(event) {
		if (event.target.closest('.Button-Close')) {
			event.target.closest('dialog').close();
		}
	}


	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		const addedNodes = mutation.addedNodes || [];
		addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initInput(node);
			}

			const inputs = node.querySelectorAll(this.selector);
			inputs.forEach((input) => {
				this.initInput(input);
			});
		})
	}
}
