//noinspection JSUnusedGlobalSymbols

/**
 * EventHandler class to manage event listeners
 */
export default class EventHandler {
	/**
	 * Map of events
	 * @type {Map<any, any>}
	 */
	events = new Map();
	/**
	 * Stored unique IDs for elements
	 * @type {WeakMap<WeakKey, any>}
	 */
	elementIds = new WeakMap();
	/**
	 * Flag to prevent multiple requestAnimationFrame calls
	 * @type {number}
	 */
	elementIdCounter = 0;
	/**
	 * Map of event listeners
	 * @type {Map<any, any>}
	 */
	listeners = new Map();

	/**
	 * Creates an event listener for a given event name and element.
	 * @param {string} eventName - The name of the event.
	 * @param {object} element - The element to attach the event to.
	 * @param {object} options - The options for the event listener.
	 * @returns {function} - The event listener function.
	 */
	createListener(eventName, element, options) {
		return (event) => {
			if (!this.events.has(element) || !this.events.get(element)[eventName]) {
				return;
			}

			const handlersMap = this.events.get(element)[eventName];
			const handlers = handlersMap.get(JSON.stringify(options));

			if (['scroll', 'resize'].includes(eventName)) {
				if (!this.frameRequested) {
					this.frameRequested = true;
					requestAnimationFrame(() => {
						this.frameRequested = false;
						for (const handler of handlers) {
							if (handler.callback(event) === false) {
								break;
							}
						}
					});
				}
			}
			else {
				for (const handler of handlers) {
					if (handler.callback(event) === false) {
						break;
					}
				}
			}
		}
	}

	/**
	 * Adds an event listener to an element.
	 * @param {string} eventName - The name of the event.
	 * @param {function} callback - The callback function to be called when the event is triggered.
	 * @param {HTMLElement|Window} [element=window] - The element to attach the event to. Defaults to the window object.
	 * @param {object|boolean} [options={}] - The options for the event listener. Defaults to an empty object.
	 * @param {number} [priority=10] - The priority of the event handler. Defaults to 10.
	 */
	add(eventName, callback, element = window, options = {}, priority = 10) {
		// If options is true, set it to capture
		if (options === true) {
			options = {capture: true};
		}

		// Create the handler object
		const handler = {callback, options, priority};

		// If the element doesn't have an id in the WeakMap, assign one to it
		if (!this.elementIds.has(element)) {
			this.elementIds.set(element, `element-${this.elementIdCounter++}`);
		}

		// Use the element's id from the WeakMap as part of the key
		const key = JSON.stringify([eventName, this.elementIds.get(element), options]);

		// If a listener has not already been created for the event and element, create one
		if (!this.listeners.has(key)) {
			this.listeners.set(key, this.createListener(eventName, element, options));
		}

		// Set the listener function for the handler
		handler.listener = this.listeners.get(key);

		// If the event already exists for the element, add the handler to it
		if (this.events.has(element) && this.events.get(element)[eventName] && this.events.get(element)[eventName].has(JSON.stringify(options))) {
			this.events.get(element)[eventName].get(JSON.stringify(options)).push(handler);
		}
		// Otherwise, create a new array for the event and attach a single event listener
		else {
			const elementEvents = this.events.get(element) || {};
			elementEvents[eventName] = elementEvents[eventName] || new Map();
			elementEvents[eventName].set(JSON.stringify(options), [handler]);
			this.events.set(element, elementEvents);

			element.addEventListener(eventName, handler.listener, {...options});
		}

		// Get the handlers for the event
		let handlersMap = this.events.get(element)[eventName];

		// Loop through the handlers, sort them, and replace the original array with the sorted array
		for (let [optionsKey, handlers] of handlersMap.entries()) {
			handlers.sort((a, b) => a.priority - b.priority);
			handlersMap.set(optionsKey, handlers);
		}
	}

	/**
	 * Remove an event listener
	 * @param {string} eventName - The name of the event
	 * @param {function} callback - The callback function
	 * @param {object} element - The element to detach the event from
	 * @param {{}|boolean} options - The options for the event listener, or a boolean value for capture
	 */
	remove(eventName, callback, element = window, options = {}) {
		// If the event exists for the element
		if (!this.events.has(element) || !this.events.get(element)[eventName]) {
			return;
		}

		// If options is true, set it to capture
		if (options === true) {
			options = {capture: true};
		}

		// Get the handlers for the event
		let handlersMap = this.events.get(element)[eventName];

		// Loop through the handlers, find the handler with the matching callback, and remove it
		for (let [optionsKey, handlers] of handlersMap.entries()) {
			const index = handlers.findIndex(handler => handler.callback === callback);

			if (index !== -1) {
				let listener = handlers[index].listener; // Store the listener before deleting the handler
				handlers.splice(index, 1);

				// If there are no more handlers for the event, remove the event from the element
				if (handlers.length === 0) {
					// Detach the event listener using the stored listener function
					element.removeEventListener(eventName, listener, {...options});

					handlersMap.delete(optionsKey);

					// If there are no more options for the event type, remove it from the element events
					if (handlersMap.size === 0) {
						delete this.events.get(element)[eventName];
					}

					// If there are no more event types for the element, remove the element from the map
					if (Object.keys(this.events.get(element)).length === 0) {
						this.events.delete(element);
					}
				}
			}
		}
	}

	/**
	 * Check if an event exists for an element
	 * @param eventName
	 * @param element
	 * @returns {false|*}
	 */
	has(eventName, element = window) {
		return this.events.has(element) && this.events.get(element)[eventName];
	}
}