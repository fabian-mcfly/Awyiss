// noinspection JSUnusedGlobalSymbols

import 'Frontend/MapLibre/maplibre-gl';
// noinspection NpmUsedModulesInstalled
import mapLibreLocale from 'Frontend/MapLibre/locale';

/**
 * @typedef {HTMLElement & {
 *   form: HTMLFormElement,
 *   lazyLoadObserver: MutationObserver,
 *   map: HTMLElement,
 *   mapLibre: maplibregl.Map,
 *   messageArea: HTMLElement,
 *   route: HTMLElement,
 *   startChoices: HTMLElement
 * }} RoutePlannerElement
 */

/**
 * @typedef {Object} RouteStep
 * @property {number} distance
 * @property {number} duration
 * @property {string} instruction
 * @property {string} name
 * @property {string} type
 * @property {number[]} way_points
 */

/**
 * @typedef {Object} Segment
 * @property {RouteStep[]} steps
 */

/**
 * @typedef {Object} Summary
 * @property {number} distance
 * @property {number} duration
 */

/**
 * @typedef {Object} FeatureProperties
 * @property {Summary} summary
 * @property {Segment[]} segments
 * @property {array} way_points
 */

/**
 * @typedef {import('geojson').Feature<FeatureProperties>} Feature
 */

/**
 * @typedef {Object} AddressData
 * @property {float} lat
 * @property {float} lng
 * @property {string} name
 */

/**
 * @typedef {Object} AddressResponse
 * @property {string} status
 * @property {string} message
 * @property {AddressData[]} [addresses]
 */

/**
 * @typedef {Object} GeoJson
 * @property {[float, float, float, float]} bbox
 * @property {string} type
 * @property {FeatureProperties} properties
 * @property {Feature[]} features
 */

/**
 * @typedef {Object} RouteData
 * @property {AddressData} start
 * @property {AddressData} end
 * @property {GeoJson} geoJson
 */

/**
 * @typedef {Object} RouteResponse
 * @property {string} status
 * @property {string} message
 * @property {RouteData} route
 * @property {AddressData[]} [addresses]
 */

export default class RoutePlanner {
	defaults = {
		selector: '.Widget-RoutePlanner',
		mapSelector: ':scope > .RoutePlanner-Map',
		formSelector: ':scope > .RoutePlanner-Form',
		messageAreaSelector: '.RoutePlanner-Message',
		routeSelector: '.RoutePlanner-Route',
		startInputSelector: 'input[name^="route_planner"][name$="[start]"]',
		startChoicesSelector: '.RoutePlanner-StartChoices',
		transportationInputSelector: '[name^="route_planner"][name$="[transportation_mode]"]',
		coordinates: {
			lat: undefined,
			lng: undefined,
		},
		lazyLoad: true,
		lazyLoadClass: 'Lazyload',
		lazyLoadedClass: 'Visible',
	}
	observer = null;
	observers = [];
	settings = {}

	constructor(settings) {
		// Merge default settings with user-provided settings
		this.settings = {...this.defaults, ...settings};

		// Create a new MutationObserver instance and set its callback
		this.observer = new MutationObserver((mutationsList, observer) => {
			// For each mutation, call all registered observer callbacks
			for (let mutation of mutationsList) {
				this.observers.forEach(callback => callback(mutation, observer));
			}
		});

		const routePlanners = document.querySelectorAll(this.settings.selector);
		routePlanners.forEach(/** @param {RoutePlannerElement} routePlanner */ routePlanner => {
			if (!this.settings.lazyLoad) {
				this.initRoutePlanner(routePlanner);
				return;
			}

			// Check if the route planner is already loaded
			if (
				routePlanner.classList.contains(this.settings.lazyLoadClass) &&
				routePlanner.classList.contains(this.settings.lazyLoadedClass)
			) {
				this.initRoutePlanner(routePlanner);
				return;
			}

			// Observe the route planner element for changes to its classes
			routePlanner.lazyLoadObserver = this.observeLazyLoad.bind(this);
			this.observers.push(routePlanner.lazyLoadObserver);
		});

		this.observer.observe(document.body, {
			attributes: true,
			attributeFilter: ['class'],
			attributeOldValue: true,
			childList: true,
			subtree: true
		});
	}


	/**
	 * Initializes the route planner by setting up the map and form elements.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element to initialize.
	 */
	initRoutePlanner(routePlanner) {
		routePlanner.form ??= routePlanner.querySelector(this.settings.formSelector);
		routePlanner.map ??= routePlanner.querySelector(this.settings.mapSelector);
		routePlanner.messageArea ??= routePlanner.querySelector(this.settings.messageAreaSelector);
		routePlanner.route ??= routePlanner.querySelector(this.settings.routeSelector);
		routePlanner.startChoices ??= routePlanner.querySelector(this.settings.startChoicesSelector);

		routePlanner.startChoices.addEventListener('click', this.handleSelectChoice.bind(this, routePlanner));
		routePlanner.startChoices.addEventListener('keydown', (event) => {
			// If the user presses the Escape key, close the start choices
			if (event.key === 'Escape') {
				routePlanner.startChoices.setAttribute('aria-hidden', 'true');
				routePlanner.startChoices.classList.remove('Visible');
				routePlanner.startChoices.inert = true;

				routePlanner.form.inert = false;

				const startInput = routePlanner.form.querySelector(this.settings.startInputSelector);
				setTimeout(() => startInput.focus(), 100);
			}
		});

		routePlanner.form.coordinates = {
			lat: this.settings.coordinates.lat || routePlanner.map.dataset.lat,
			lng: this.settings.coordinates.lng || routePlanner.map.dataset.lng,
		};

		routePlanner.form.classList.add('Loading');

		routePlanner.mapLibre = this.initMap(routePlanner, routePlanner.map, routePlanner.form.coordinates.lat, routePlanner.form.coordinates.lng);

		// Initialize the form if it exists
		if (routePlanner.form) {
			this.initForm(routePlanner.form, routePlanner);
		}
	}


	/**
	 * Initializes the form by adding event listeners and a geolocator button.
	 *
	 * @param {HTMLFormElement} form - The form element to initialize.
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 */
	initForm(form, routePlanner) {
		this.addGeoLocatorButton(form);
		form.addEventListener('submit', this.handleFormSubmit.bind(this, form, routePlanner));
	}


	/**
	 * Initializes the map using MapLibre.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {HTMLElement} container - The container element for the map.
	 * @param {number} lat - The latitude of the target.
	 * @param {number} lng - The longitude of the target.
	 */
	initMap(routePlanner, container, lat, lng) {
		let style = routePlanner.dataset.tileStyle || 'graybeard';
		if (!['graybeard', 'colorful'].includes(style)) {
			style = 'graybeard';
		}

		let zoom = parseInt(routePlanner.dataset.zoom) || 13;
		if (zoom < 0 || zoom > 20) {
			zoom = 13;
		}

		const mapLibreSettings = {
			center: [lng, lat],
			container: container,
			cooperativeGestures: true,
			locale: mapLibreLocale,
			style: `https://tiles.versatiles.org/assets/styles/${style}/style.json`,
			zoom: zoom,
		};

		// Add the mapLibre settings from the class settings
		if (this.settings.mapLibre) {
			Object.assign(mapLibreSettings, this.settings.mapLibre);
		}

		const map = new maplibregl.Map(mapLibreSettings);

		map.addControl(new maplibregl.NavigationControl({
			visualizePitch: true,
			visualizeRoll: true,
			showZoom: true,
			showCompass: true
		}));

		// Add a marker for the target
		map.endMarker = new maplibregl.Marker({
			color: getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerEndMarker') || getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerMarker') || '#63d1a5',
		}).setLngLat([lng, lat]).addTo(map);

		// Add a layer for the route
		map.on('load', () => {
			map.addSource('route', {
				type: 'geojson',
				data: {
					type: 'FeatureCollection',
					features: []
				}
			});

			map.addLayer({
				id: 'route',
				type: 'line',
				source: 'route',
				layout: {
					'line-cap': 'round',
					'line-join': 'round',
				},
				paint: {
					'line-color': getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerRoute') || '#63d1a5',
					'line-width': 6,
				},
			});

			// add empty highlight source
			map.addSource('route-highlight', {
				type: 'geojson',
				data: {
					type: 'Feature',
					geometry: {type: 'LineString', coordinates: []}
				}
			});

			// add highlight layer on top
			map.addLayer({
				id: 'route-highlight',
				type: 'line',
				source: 'route-highlight',
				layout: {
					'line-cap': 'round',
					'line-join': 'round',
				},
				paint: {
					'line-color': getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerRouteHighlight') || '#FFFFFF',
					'line-dasharray': [4, 4],
					'line-width': 2,
				},
			});

			routePlanner.form.classList.remove('Loading');
		});

		const padding = getComputedStyle(routePlanner.map).padding.split(' ');

		map.setPadding({
			top: parseInt(padding[0]),
			right: parseInt(padding[1]),
			bottom: parseInt(padding[2]),
			left: parseInt(padding[3]),
		});

		return map;
	}


	/**
	 * Handles the form submission event.
	 *
	 * @param {HTMLFormElement} form - The form element that was submitted.
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {SubmitEvent} event - The submit event.
	 */
	async handleFormSubmit(form, routePlanner, event) {
		event.preventDefault();

		routePlanner.messageArea.setAttribute('aria-hidden', 'true');
		routePlanner.messageArea.classList.remove('Visible');
		routePlanner.messageArea.inert = true;

		routePlanner.route.setAttribute('aria-hidden', 'true');
		routePlanner.route.classList.remove('Visible');
		routePlanner.route.inert = true;

		routePlanner.form.classList.add('Loading');
		routePlanner.form.inert = true;

		const start = form.querySelector(this.settings.startInputSelector).value;

		let transportationModeInput = form.querySelector(this.settings.transportationInputSelector);
		if (transportationModeInput?.matches('[type="hidden"], [type="radio"]')) {
			// Get the selected transportation mode
			transportationModeInput = form.querySelector(this.settings.transportationInputSelector + ':checked') || transportationModeInput;
		}

		const transportationMode = transportationModeInput?.value || form.dataset.transportationMode || '';

		const response = await this.getRoute(start, form.coordinates, transportationMode, routePlanner);

		if (!response) {
			return;
		}

		// Check if the route is valid
		if (response.status !== 'success') {
			routePlanner.messageArea.setAttribute('aria-hidden', 'false');
			routePlanner.messageArea.classList.add('Visible');
			routePlanner.messageArea.inert = false;
			routePlanner.messageArea.innerHTML = response.message;

			routePlanner.form.inert = false;

			return;
		}

		this.buildRoute(response.route, routePlanner);
	}


	/**
	 * Handles the click event on a route step.
	 *
	 * @param {RoutePlannerElement} routePlanner
	 * @param {RouteStep} step
	 * @param {MouseEvent} event
	 * @returns {Promise<void>}
	 */
	async handleRouteStepHighlight(routePlanner, step, event) {
		const target = event.target;

		target.classList.add('Active');

		// Get the start and end waypoints and their coordinates
		const start = +target.dataset.waypointStart;
		const end = +target.dataset.waypointEnd;

		// Get the coordinates of the start and end waypoints
		const data = await routePlanner.mapLibre.getSource('route').getData();
		const coordinates = data.geometry.coordinates;

		routePlanner.mapLibre.flyTo({
			center: coordinates[start],
			zoom: 14,
		});

		const segmentCoordinates = coordinates.slice(Math.max(start - 1, 0), end + 1);

		if (target.classList.contains('Active')) {
			// Mark other elements as inactive
			routePlanner.route.querySelectorAll('.Active').forEach(routePlanner => {
				if (routePlanner !== target) {
					routePlanner.classList.remove('Active');
				}
			});

			// Add a new source for the highlighted route
			routePlanner.mapLibre.getSource('route-highlight').setData({
				type: 'Feature',
				geometry: {
					type: 'LineString',
					coordinates: segmentCoordinates,
				}
			});
		}
		else {
			// Remove the highlight source
			routePlanner.mapLibre.getSource('route-highlight').setData({
				type: 'Feature',
				geometry: {
					type: 'LineString',
					coordinates: [],
				}
			});
		}
	}


	/**
	 * Handles the selection of a route choice.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {MouseEvent} event - The click event.
	 */
	handleSelectChoice(routePlanner, event) {
		event.preventDefault();

		const target = event.target;
		if (target.closest('.Button-RoutePlanner-StartChoices-Close')) {
			routePlanner.startChoices.setAttribute('aria-hidden', 'true');
			routePlanner.startChoices.classList.remove('Visible');
			routePlanner.startChoices.inert = true;

			routePlanner.form.inert = false;

			return;
		}

		const choice = target.closest('.Button-RoutePlanner-StartChoices-Choice');
		if (choice) {
			const coordinates = {
				lat: target.dataset.lat,
				lng: target.dataset.lng,
			};

			routePlanner.startChoices.setAttribute('aria-hidden', 'true');
			routePlanner.startChoices.classList.remove('Visible');
			routePlanner.startChoices.inert = true;

			routePlanner.form.inert = false;

			let formInput = routePlanner.form.querySelector(this.settings.startInputSelector);
			if (!formInput) {
				// Assume the first input is the start input
				formInput = routePlanner.form.querySelector('input');
			}

			formInput.value = `${coordinates.lat}, ${coordinates.lng}`;

			// Submit the form with the selected choice
			routePlanner.form.requestSubmit();
		}
	}

	/**
	 * Fetches the route from the server.
	 *
	 * @param {string} start - The starting point for the route.
	 * @param {Object} coordinates - The coordinates object containing lat and lng.
	 * @param {string} transportationMode - The transportation mode for the route.
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @returns {Promise<RouteResponse|false>} - The route data.
	 */
	async getRoute(start, coordinates, transportationMode, routePlanner) {
		start = encodeURIComponent(start);
		let end = encodeURIComponent(coordinates.lat + ',' + coordinates.lng);
		transportationMode = encodeURIComponent(transportationMode);

		const response = await fetch(`${baseUrl}${languageShortcode}/_route/start:${start}/end:${end}/transportation-mode:${transportationMode}`, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		routePlanner.form.classList.remove('Loading');

		if (!response.ok) {
			const data = await response.json();

			// Handle 300 response that offers a list of routes
			// and requires the user to select one before proceeding.
			if (response.status === 300) {
				this.buildStartChoices(routePlanner, data.message, data.addresses);
			}
			else {
				routePlanner.messageArea.setAttribute('aria-hidden', 'false');
				routePlanner.messageArea.classList.add('Visible');
				routePlanner.messageArea.inert = false;
				routePlanner.messageArea.innerHTML = data.message;

				routePlanner.form.inert = false;
			}

			return false;
		}

		return await response.json();
	}

	/**
	 * Adds a geolocator button to the form.
	 *
	 * @param {HTMLFormElement} form - The form element to which the button will be added.
	 */
	addGeoLocatorButton(form) {
		// Add a geolocator button
		const geolocatorButton = document.createElement('button');
		geolocatorButton.type = 'button';
		geolocatorButton.classList.add('Button', 'Button-RoutePlanner-Geolocator');
		geolocatorButton.textContent = mapLibreLocale['GeolocateControl.FindMyLocation'];

		let formInput = form.querySelector(this.settings.startInputSelector);
		if (!formInput) {
			// Assume the first input is the start input
			formInput = form.querySelector('input');
		}

		if (formInput) {
			formInput.parentNode.insertBefore(geolocatorButton, formInput.nextSibling);
		}

		geolocatorButton.addEventListener('click', () => {
			if (!navigator.geolocation) {
				console.error('Geolocation is not supported by this browser.');
				return;
			}

			navigator.geolocation.getCurrentPosition((position) => {
				// Set the coordinates in the input field
				formInput.value = `${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;

				// Request form submit
				form.requestSubmit();
			},
			() => {

			},
			{
				enableHighAccuracy: true,
				timeout: 10000,
				maximumAge: 0
			});
		});
	}


	/**
	 * Builds the route on the map using the response data.
	 *
	 * @param {Object} data - The response data from the server.
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 */
	buildRoute(data, routePlanner) {
		if (routePlanner.mapLibre.startMarker) {
			// Remove the old marker
			routePlanner.mapLibre.startMarker.remove();
		}
		// Add the new marker
		routePlanner.mapLibre.startMarker = new maplibregl.Marker({
			color: getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerStartMarker') || getComputedStyle(routePlanner).getPropertyValue('--colorRoutePlannerMarker') || '#63d1a5',
		}).setLngLat([data.start.lng, data.start.lat]).addTo(routePlanner.mapLibre);

		// If bbox is set, set the map bounds
		if (data.geoJson.bbox) {
			routePlanner.mapLibre.fitBounds(data.geoJson.bbox);
		}

		// Set the route data to the map
		routePlanner.mapLibre.getSource('route').setData(data.geoJson);

		routePlanner.route.scrollTo(0, 0);

		// Remove call children from the route element, except `<template>`-elements
		const children = routePlanner.route.querySelectorAll('*:not(template)');
		children.forEach((child) => {
			child.remove();
		});

		this.buildRouteSummary(routePlanner, data);

		this.buildRouteSteps(routePlanner, data);

		// Show the route
		routePlanner.route.setAttribute('aria-hidden', 'false');
		routePlanner.route.classList.add('Visible');
		routePlanner.route.inert = false;

		routePlanner.route.focus();
	}

	/**
	 * Builds the route summary.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {Object} data - The response data from the server.
	 */
	buildRouteSummary(routePlanner, data) {
		// Create a summary, based on the `.Template-Summary`-template
		const summaryTemplate = routePlanner.route.querySelector('template.Template-Summary');
		const summary = summaryTemplate.content.cloneNode(true).firstElementChild;
		routePlanner.route.appendChild(summary);

		let innerHTML = summary.innerHTML;

		const startInput = routePlanner.form.querySelector(this.settings.startInputSelector);

		innerHTML = innerHTML.replace(/{{distance}}/g, this.readableDistance(data.geoJson.properties.summary.distance));
		innerHTML = innerHTML.replace(/{{duration}}/g, this.readableDuration(data.geoJson.properties.summary.duration, true));
		innerHTML = innerHTML.replace(/{{start}}/g, startInput.value);
		innerHTML = innerHTML.replace(/{{end}}/g, routePlanner.map.dataset.end);

		summary.innerHTML = innerHTML;

		summary.querySelector('.Button-RoutePlanner-Reset')?.addEventListener('click', () => {
			routePlanner.route.setAttribute('aria-hidden', 'true');
			routePlanner.route.classList.remove('Visible');
			routePlanner.route.inert = true;

			routePlanner.form.inert = false;

			// Reset the validation state of the form
			startInput.value = '';
			setTimeout(() => startInput.focus(), 100);
		})
	}

	/**
	 * Builds the route steps in the route list.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {Object} data - The response data from the server.
	 */
	buildRouteSteps(routePlanner, data) {
		// Create a new list, based on the `.Template-List`-template
		const listTemplate = routePlanner.route.querySelector('template.Template-List');
		const listItemTemplate = routePlanner.route.querySelector('template.Template-ListItem');
		let routeList = listTemplate.content.cloneNode(true).firstElementChild;

		routePlanner.route.appendChild(routeList);

		// Add the route steps to the list
		data.geoJson.properties.segments.forEach((segment) => {
			if (routeList.innerHTML !== '') {
				// Create a new list
				routeList = listTemplate.content.cloneNode(true);
				routePlanner.route.appendChild(routeList);
			}

			segment.steps.forEach((step) => {
				this.buildRouteStep(routePlanner, listItemTemplate, routeList, step);
			});
		});
	}

	/**
	 * Builds a route step in the route list.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {HTMLTemplateElement} listItemTemplate - The template for the list item.
	 * @param {HTMLElement} routeList - The route list element.
	 * @param {Object} step - The route step data.
	 */
	buildRouteStep(routePlanner, listItemTemplate, routeList, step) {
		const listItem = listItemTemplate.content.cloneNode(true).firstElementChild;
		routeList.appendChild(listItem);

		let innerHTML = listItem.innerHTML;

		innerHTML = innerHTML.replace(/{{distance}}/g, this.readableDistance(step.distance));
		innerHTML = innerHTML.replace(/{{duration}}/g, this.readableDuration(step.duration));
		innerHTML = innerHTML.replace(/{{instruction}}/g, step.instruction);
		innerHTML = innerHTML.replace(/{{name}}/g, step.name);

		listItem.innerHTML = innerHTML;

		listItem.dataset.type = step.type;
		listItem.dataset.waypointStart = step.way_points[0];
		listItem.dataset.waypointEnd = step.way_points[1];

		listItem.addEventListener('focus', this.handleRouteStepHighlight.bind(this, routePlanner, step));
	}


	/**
	 * Builds the start choices for the route planner.
	 *
	 * @param {RoutePlannerElement} routePlanner - The route planner element.
	 * @param {string} message - The message to display.
	 * @param {AddressData[]} addresses - The data containing the start choices.
	 */
	buildStartChoices(routePlanner, message, addresses) {
		routePlanner.startChoices.setAttribute('aria-hidden', 'false');
		routePlanner.startChoices.classList.add('Visible');
		routePlanner.startChoices.inert = false;
		routePlanner.startChoices.scrollTo(0, 0);

		// Clear existing choices by removing all children
		const startChoices = routePlanner.startChoices.querySelectorAll('.RoutePlanner-StartChoices-Message, .Button-RoutePlanner-StartChoices-Choice');
		startChoices.forEach((choice) => {
			choice.remove();
		});

		const closeButton = routePlanner.startChoices.querySelector('.Button-RoutePlanner-StartChoices-Close');

		const messageArea = document.createElement('p');
		messageArea.classList.add('RoutePlanner-StartChoices-Message');
		messageArea.innerHTML = message;
		routePlanner.startChoices.insertBefore(messageArea, closeButton);

		// Create a new list of choices
		addresses.forEach((address) => {
			const choiceElement = document.createElement('button');

			choiceElement.type = 'button';
			choiceElement.classList.add('Button', 'Button-RoutePlanner-StartChoices-Choice');
			choiceElement.dataset.lat = '' + address.lat;
			choiceElement.dataset.lng = '' + address.lng;
			choiceElement.textContent = address.name;

			// Insert the item before the close button
			routePlanner.startChoices.insertBefore(choiceElement, closeButton);
		});

		routePlanner.startChoices.focus();
	}


	/**
	 * Observes the route planner element for changes to its classes.
	 *
	 * @param {MutationRecord} mutation - The mutation that occurred.
	 */
	observeLazyLoad(mutation) {
		if (!mutation.target.matches(this.settings.selector)) {
			return;
		}

		if (
			mutation.type !== 'attributes' ||
			mutation.attributeName !== 'class'
		) {
			return;
		}

		const classList = mutation.target.classList;
		const oldClasses = mutation.oldValue ? mutation.oldValue.split(' ') : [];

		// If the old classes include the lazy loaded class, we don't need to do anything
		// because the element is already loaded and initialized.
		if (oldClasses.includes(this.settings.lazyLoadedClass)) {
			return;
		}

		// If the new classes include the lazy load and lazy loaded class, we need to initialize the route planner
		if (
			classList.contains(this.settings.lazyLoadClass) &&
			classList.contains(this.settings.lazyLoadedClass)
		) {
			this.initRoutePlanner(mutation.target);
			this.observers.splice(this.observers.indexOf(mutation.target.lazyLoadObserver), 1);
		}
	}

	/**
	 * Converts a distance in meters to a human-readable format.
	 *
	 * @param {number} distance - The distance in meters.
	 */
	readableDistance(distance) {
		let kilo = false;

		if (distance > 1000) {
			distance = (distance / 1000);
			kilo = true;
		}

		return Math.ceil(distance) + ' ' + (kilo ? 'k' : '') + 'm';
	}

	/**
	 * Converts a duration in seconds to a human-readable format.
	 *
	 * @param {number} duration - The duration in seconds.
	 * @param {boolean} [stripSeconds=false] - Whether to strip seconds from the output.
	 */
	readableDuration(duration, stripSeconds = false) {
		const hours = Math.floor(duration / 3600);
		let minutes = Math.floor((duration % 3600) / 60);
		const seconds = Math.floor(duration % 60);

		let result = '';
		if (hours > 0) {
			result += hours + ' h ';
			stripSeconds = true;
		}

		if (minutes > 0) {
			if (minutes > 9) {
				// If minutes are greater than 9, we can strip seconds
				stripSeconds = true;
				minutes++;
			}
			else if (seconds > 0 && stripSeconds) {
				// If seconds are to be stripped, we need to add 1 minute
				minutes++;
			}

			result += minutes + ' min ';
		}

		if (seconds > 0 && !stripSeconds) {
			result += seconds + ' s ';
		}

		return result.trim();
	}
}
