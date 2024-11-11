//noinspection JSUnusedGlobalSymbols

/**
 * The FooterReveal class handles the visibility of a footer element based on the user's scroll position.
 * It reveals the footer when the user scrolls to the bottom of the page and hides it when the user scrolls up.
 */
export default class FooterReveal {
	/**
	 * The bound requestTick method.
	 * @type {function}
	 */
	boundRequestTick;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * @property {boolean} footerIsVisible - A flag indicating whether the footer is currently visible.
	 * It is initially set to false, meaning the footer is not visible at the start.
	 */
	footerIsVisible = false;
	/**
	 * @property {number} lastScrollPosition - Stores the last scroll position of the window.
	 * It is initially set to 0, representing the top of the page.
	 */
	lastScrollPosition = 0;
	/**
	 * @property {boolean} ticking - A flag used to optimize the scroll event handler.
	 * It is used in conjunction with the requestAnimationFrame method to ensure that the scroll event handler
	 * is not called more times than needed. It is initially set to false.
	 */
	ticking = false;

	constructor() {
		if (document.documentElement.classList.contains('👀')) {
			return;
		}

		// Bind the requestTick method to the current instance
		this.boundRequestTick = this.requestTick.bind(this);

		// Add a scroll event listener to the window
		this.eventHandler.add('scroll', this.boundRequestTick);
	}

	/**
	 * Handles the scroll event. It checks if the user has scrolled to the bottom of the page.
	 * If they have, it reveals the footer by adjusting a CSS variable and scrolls the page to the footer.
	 * If the user scrolls up and the footer is visible, it hides the footer.
	 */
	handleScroll(event) {
		const footer = document.getElementById('HiddenFooter');
		const body = document.body;
		const currentScrollPosition = window.scrollY || document.documentElement.scrollTop;
		const bodyHeight = document.body.offsetHeight + parseInt(document.body.style.marginBottom.replace('px', '') || 0);

		// Check if the event is a wheel or touchmove event and the user is scrolling up
		// or if there is a timeout set. If so, return early, but not before setting the last scroll position.
		if (
			((event.type === 'wheel' || event.type === 'touchmove') && event.deltaY < 0) ||
			event.deltaX ||
			this.eventTimeout ||
			window.isAdjustingScroll
		) {
			this.lastScrollPosition = currentScrollPosition;
			this.ticking = false;

			return;
		}

		// Check if the user has scrolled to the bottom of the page
		if (
			!this.footerIsVisible &&
			(window.innerHeight + window.scrollY) >= bodyHeight &&
			currentScrollPosition >= this.lastScrollPosition
		) {
			// If the user attempts to scroll further down after reaching the bottom, reveal the footer
			if (currentScrollPosition !== this.lastScrollPosition) {
				this.lastScrollPosition = currentScrollPosition;
				this.ticking = false;

				// Set a timeout to add event listeners for wheel and touchmove events
				this.eventTimeout = setTimeout(() => {
					this.eventTimeout = null;

					// Add a wheel event listener to the window
					window.addEventListener('wheel', this.boundRequestTick);

					// Add a touchmove event listener to the window
					window.addEventListener('touchmove', this.boundRequestTick);
				}, 500);

				return;
			}

			body.style.marginBottom = `${footer.offsetHeight * 1.25}px`;
			body.style.setProperty('--bottomOffset', `${footer.offsetHeight}px`);
			footer.style.setProperty('--bottomOffset', `${footer.offsetHeight * -1.25}px`);

			this.footerIsVisible = true;

			// Calculate the position of the footer
			const footerPosition = footer.offsetTop;

			// Scroll to the footer
			this.smoothScroll(footerPosition, 500);

			footer.classList.add('Fixed');

			// Remove the wheel event listener from the window
			window.removeEventListener('wheel', this.boundRequestTick);

			// Remove the touchmove event listener from the window
			window.removeEventListener('touchmove', this.boundRequestTick);
		}
		// Check if the user is scrolling back up past a certain point in the document, and the footer is visible
		else if (
			this.footerIsVisible &&
			currentScrollPosition < this.lastScrollPosition &&
			window.scrollY + window.innerHeight <= bodyHeight + footer.offsetHeight
		) {
			clearTimeout(this.eventTimeout);

			body.style.marginBottom = '0px';
			body.style.setProperty('--bottomOffset', '0px');
			footer.style.setProperty('--bottomOffset', '0px');

			setTimeout(() => {
				footer.classList.remove('Fixed');
			}, 500);

			this.eventTimeout = setTimeout(() => {
				this.eventTimeout = null;
			}, 500);

			this.footerIsVisible = false;
		}

		this.lastScrollPosition = currentScrollPosition;
		this.ticking = false;
	}

	/**
	 * Requests a frame if one has not been requested already.
	 * This ensures that the handleScroll method is not called more times than needed.
	 */
	requestTick(event) {
		if (!this.ticking) {
			requestAnimationFrame(() => this.handleScroll(event));
			this.ticking = true;
		}
	}

	/**
	 * Smoothly scrolls to a target position within a specified duration.
	 * @param {number} target - The target position to scroll to.
	 * @param {number} duration - The duration of the scrolling animation in milliseconds.
	 */
	smoothScroll(target, duration) {
		const start = window.scrollY || document.documentElement.scrollTop;
		const distance = target - start;
		let startTime = null;

		const animation = (currentTime) => {
			if (startTime === null) {
				startTime = currentTime;
			}

			const timeElapsed = currentTime - startTime;
			const run = ease(timeElapsed, start, distance, duration);
			window.scrollTo(0, run);

			if (timeElapsed < duration) {
				requestAnimationFrame(animation);
			}
		}

		const ease = (t, b, c, d) => {
			return c * t / d + b;
		}

		requestAnimationFrame(animation);
	}
}