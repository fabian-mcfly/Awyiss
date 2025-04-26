//noinspection JSUnusedGlobalSymbols

/**
 * Observer class
 * This class is used to observe mutations on a target element and notify all registered observers when a mutation occurs.
 */
export default class Observer {
	// Array to hold all registered observer callbacks
	observers = [];

	/**
	 * Constructor for Observer class.
	 * Initializes the observers array and the MutationObserver instance.
	 */
	constructor() {
		// Create a new MutationObserver instance and set its callback
		this.mutationObserver = new MutationObserver((mutationsList, observer) => {
			// For each mutation, call all registered observer callbacks
			for (let mutation of mutationsList) {
				this.observers.forEach(callback => callback(mutation, observer));
			}
		});
	}

	/**
	 * Starts observing mutations on the target element.
	 * @param {Node} target - The target element to observe.
	 * @param {MutationObserverInit} config - The configuration object for the MutationObserver.
	 */
	observe(target, config) {
		this.mutationObserver.observe(target, config);
	}

	/**
	 * Adds a new observer callback to the observers array.
	 * @param {function} callback - The observer callback to add.
	 */
	addObserver(callback) {
		this.observers.push(callback);
	}
}