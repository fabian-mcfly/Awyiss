//noinspection JSUnusedGlobalSymbols

/**
 * Observer class
 * This class is used to observe mutations on target elements and notify all registered observers when a mutation occurs.
 * Each target element has its own MutationObserver instance for optimal performance.
 */
export default class Observer {
	// Map to hold callbacks organized by target element
	// Key: target element, Value: Array of callback functions
	observersByTarget = new Map();

	// Default configuration for MutationObserver
	defaultConfig = {childList: true, subtree: true};

	/**
	 * Handles mutations detected by a specific MutationObserver.
	 * Calls all registered callbacks for the associated target element.
	 * @param {MutationRecord[]} mutationsList - List of mutations detected.
	 * @param {MutationObserver} observer - The MutationObserver instance.
	 * @param {Node} target - The target element to look up callbacks for.
	 * @param {string} configKey - The configuration key to identify the correct set of callbacks.
	 */
	handleMutations(mutationsList, observer, target, configKey) {
		if (
			!this.observersByTarget.has(target) ||
			!this.observersByTarget.get(target).has(configKey)
		) {
			return;
		}

		const callbacks = this.observersByTarget.get(target).get(configKey);

		if (!callbacks) {
			return;
		}

		for (let mutation of mutationsList) {
			callbacks.forEach(callback => {
				callback(mutation, observer);
			});
		}
	}

	/**
	 * Adds a new observer callback for a specific target element.
	 * @param {function} callback - The observer callback to add.
	 * @param {Node} [target=document.body] - The target element to observe. Defaults to document.body.
	 * @param {MutationObserverInit} [config={}] - Additional configuration options that will be merged with defaults.
	 */
	addObserver(callback, target = document.body, config = {}) {
		// Merge provided config with defaults
		const mergedConfig = {...this.defaultConfig, ...config};

		const configKey = JSON.stringify(mergedConfig);

		// Check if we already have an observer for this target
		if (
			!this.observersByTarget.has(target) ||
			!this.observersByTarget.get(target).has(configKey)
		) {
			let targetMap = this.observersByTarget.has(target) ? this.observersByTarget.get(target) : null;
			if (!targetMap) {
				targetMap = new Map();
				this.observersByTarget.set(target, targetMap);
			}

			targetMap.set(configKey, []);

			// First observer for this target - create new MutationObserver and start observing
			const mutationObserver = new MutationObserver((mutationsList, observer) => {
				this.handleMutations(mutationsList, observer, target, configKey);
			});
			mutationObserver.observe(target, mergedConfig);
		}

		// Add the callback to the target's callbacks array
		this.observersByTarget.get(target).get(configKey).push(callback);
	}
}