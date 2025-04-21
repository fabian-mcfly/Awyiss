//noinspection JSUnusedGlobalSymbols

/**
 * Represents a draggable sidebar toggle component.
 * Handles user interaction to enable dragging functionality for opening and closing a sidebar.
 */
export default class DraggableSidebarToggle {
	/**
	 * The button area element.
	 * @type {HTMLElement}
	 */
	buttonArea;
	/**
	 * The instance of the HTML element.
	 * @type {HTMLElement}
	 */
	buttonAreaToggle = null;
	/**
	 * The ID of the current animation frame request
	 * @type {number|null}
	 */
	animationFrameId = null;
	/**
	 * The current calculated X position
	 * @type {number}
	 */
	currentX = 0;
	/**
	 * The current calculated Y position
	 * @type {number}
	 */
	 currentY = 0;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * A boolean flag indicating whether a drag operation is currently active.
	 * @type {boolean}
	 */
	isDragging = false;
	/**
	 * The initial horizontal position of the pointer
	 * @type {number}
	 */
	initialX = 0;
	/**
	 * The initial vertical position of the pointer
	 * @type {number}
	 */
	initialY = 0;
	/**
	 * The initial transform value for the button area toggle.
	 * @type {number}
	 */
	initialTransform = 0;
	/**
	 * Defines the maximum allowed width for an element or component.
	 * @type {number}
	 */
	maxSize = 300;
	/**
	 * The position of the button area toggle.
	 * @type {string}
	 */
	position = 'right';
	/**
	 * The timeout instance for managing drag events.
	 * @type {null|number}
	 */
	timeout = null;


	/**
	 * @returns {void}
	 */
	constructor(buttonAreaToggle, buttonArea, maxSize = 300, position = 'right') {
		// The first .ButtonArea element
		this.buttonArea = buttonArea;
		this.buttonAreaToggle = buttonAreaToggle;
		this.maxSize = maxSize;
		this.position = position;

		if (!this.buttonAreaToggle) {
			return;
		}

		this.html = document.documentElement;

		// Pre-bind methods to maintain reference for event removal
		this.boundHandleDrag = this.handleDrag.bind(this);
		this.boundHandleDragEnd = this.handleDragEnd.bind(this);

		// Add event listener
		this.eventHandler.add('mousedown', this.handleStart.bind(this, true), this.buttonAreaToggle);
		this.eventHandler.add('touchstart', this.handleStart.bind(this, false), this.buttonAreaToggle);
	}

	/**
	 * Handle mouse down event
	 * @param {boolean} isMouseEvent
	 * @param {MouseEvent|TouchEvent} event
	 * @returns {void}
	 */
	handleStart(isMouseEvent = false, event) {
		if (event.button === 2) {
			return;
		}

		clearTimeout(this.timeout);

		this.timeout = setTimeout(() => {
			this.isDragging = true;
			this.buttonAreaToggle.classList.add('Dragging');
			this.buttonArea.classList.add('Dragging');

			this.initialX = isMouseEvent ? event.clientX : event.touches[0].clientX;
			this.initialY = isMouseEvent ? event.clientY : event.touches[0].clientY;
			this.initialTransform = 0;

			if (this.buttonArea.classList.contains('Visible')) {
				const transformValue = window.getComputedStyle(this.buttonAreaToggle).transform;
				if (transformValue && transformValue !== 'none') {
					const matrix = new DOMMatrixReadOnly(transformValue);
					if (['top', 'bottom'].includes(this.position)) {
						this.initialTransform = matrix.m42;
					}
					else {
						this.initialTransform = matrix.m41;
					}
				}
			}

			event.preventDefault();
			this.eventHandler.add(isMouseEvent ? 'mousemove' : 'touchmove', this.boundHandleDrag, document);
		}, 200);

		this.eventHandler.add(isMouseEvent ? 'mouseup' : 'touchend', this.boundHandleDragEnd, document);
	}

	/**
	 * Handle drag event
	 * @param {MouseEvent|TouchEvent} event
	 * @returns {void}
	 */
	handleDrag(event) {
		if (!this.isDragging) {
			return;
		}

		let clientX = event.type === 'mousemove' ? event.clientX : event.touches[0].clientX;
		let clientY = event.type === 'mousemove' ? event.clientY : event.touches[0].clientY;

		// Calculate position but don't update DOM directly
		const x = clientX - this.initialX + this.initialTransform;
		const y = clientY - this.initialY + this.initialTransform;

		if (this.position === 'right') {
			this.currentX = Math.max(this.maxSize * -1, Math.min(x, 0));
		}
		else {
			this.currentX = Math.min(this.maxSize, Math.max(x, 0));
		}

		if (this.position === 'bottom') {
			this.currentY = Math.max(this.maxSize * -1, Math.min(y, 0));
		}
		else {
			this.currentY = Math.min(this.maxSize, Math.max(y, 0));
		}

		// Schedule visual update
		if (this.animationFrameId === null) {
			this.animationFrameId = requestAnimationFrame(this.updatePosition.bind(this));
		}

		event.preventDefault();
	}

	/**
	 * Handle end drag event
	 * @param {MouseEvent} event
	 * @returns {void}
	 */
	handleDragEnd(event) {
		clearTimeout(this.timeout);

		// Cancel any pending animation frame
		if (this.animationFrameId !== null) {
			cancelAnimationFrame(this.animationFrameId);
			this.animationFrameId = null;
		}

		const isMouseEvent = event.type === 'mousemove' || event.type === 'mouseup';

		this.buttonAreaToggle.classList.remove('Dragging');
		this.buttonArea.classList.remove('Dragging');

		if (this.isDragging) {
			let clientX = isMouseEvent ? event.clientX : event.changedTouches[0].clientX;
			let clientY = isMouseEvent ? event.clientY : event.changedTouches[0].clientY;

			if (
				(
					['right', 'left'].includes(this.position) &&
					Math.abs(clientX - this.initialX) > (this.maxSize * .5)
				) ||
				(
					['top', 'bottom'].includes(this.position) &&
					Math.abs(clientY - this.initialY) > (this.maxSize * .5)
				)
			) {
				const customEvent = new MouseEvent('click', {
					bubbles: true,
					cancelable: true,
				});

				this.buttonAreaToggle.dispatchEvent(customEvent);
			}

			this.buttonAreaToggle.style.transform = '';
			this.buttonArea.style.transform = '';
			this.html.style.removeProperty('--opacity');

			this.isDragging = false;
		}

		this.eventHandler.remove(isMouseEvent ? 'mousemove' : 'touchmove', this.boundHandleDrag, document);
		this.eventHandler.remove(isMouseEvent ? 'mouseup' : 'touchend', this.boundHandleDragEnd, document);
	}


	/**
	 * Update the position of elements using requestAnimationFrame
	 * @returns {void}
	 */
	updatePosition() {
		let currentPosition;
		if (['right', 'left'].includes(this.position)) {
			// Update DOM elements
			this.buttonAreaToggle.style.transform = `translateX(${this.currentX}px)`;

			if (this.position === 'right') {
				this.buttonArea.style.transform = `translateX(${this.maxSize + this.currentX}px)`;
			}
			else {
				this.buttonArea.style.transform = `translateX(${this.currentX - this.maxSize}px)`;
			}

			currentPosition = this.currentX;
		}
		else {
			this.buttonAreaToggle.style.transform = `translateY(${this.currentY}px)`;

			if (this.position === 'bottom') {
				this.buttonArea.style.transform = `translateY(${this.maxSize + this.currentY}px)`;
			}
			else {
				this.buttonArea.style.transform = `translateY(${this.currentY - this.maxSize}px)`;
			}

			currentPosition = this.currentY;
		}

		const opacity = Math.min(1, Math.abs(currentPosition / this.maxSize));
		this.html.style.setProperty('--opacity', opacity);

		// Reset animation frame ID
		this.animationFrameId = null;
	}
}