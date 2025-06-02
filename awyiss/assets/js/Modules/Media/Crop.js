// noinspection JSUnusedGlobalSymbols

/**
 * Class for cropping an image within a container.
 * The size of the resulting image is calculated based on the natural size of the image
 * and the size of the container.
 */
export default class ImageCropper {
	/**
	 * The canvas element.
	 * @type {HTMLCanvasElement}
	 */
	canvas = document.createElement('canvas');
	/**
	 * The container element.
	 * @type {HTMLElement}
	 */
	container = null;
	/**
	 * The crop frame with default size and position.
	 * @type {Object}
	 */
	cropFrame;
	/**
	 * The context of the canvas.
	 * @type {CanvasRenderingContext2D}
	 */
	ctx = this.canvas.getContext('2d');
	/**
	 * The edge of the crop frame that is currently being dragged.
	 * @type {string|null}
	 */
	draggedEdge = null;
	/**
	 * Whether the user is currently dragging the crop frame.
	 * @type {boolean}
	 */
	dragging = false;
	/**
	 * The focus point of the crop frame.
	 * @type {string}
	 */
	focusPoint = [1, 1];
	/**
	 * The section of the crop frame the cursor is in.
	 * @type {null}
	 */
	highlightedSection = null;
	/**
	 * The image element.
	 * @type {HTMLImageElement}
	 */
	image = new Image();
	/**
	 * The dimensions and position of the image on the canvas.
	 * @type {Object}
	 */
	imageOnCanvas = {x: 0, y: 0, width: 0, height: 0};
	/**
	 * The input elements for the crop frame dimensions.
	 * @type {{x: HTMLInputElement, y: HTMLInputElement, width: HTMLInputElement, height: HTMLInputElement, resizeWidth: HTMLInputElement, resizeHeight: HTMLInputElement}}
	 */
	inputs = {}
	/**
	 * The source of the image.
	 * @type {string}
	 */
	imageSrc = '';
	/**
	 * The container for the measurements.
	 * @type {HTMLElement}
	 */
	measurementsContainer;
	/**
	 * The time when the mouse was pressed down.
	 * @type {number}
	 */
	mouseDownTime = 0;
	/**
	 * The observer for the crop frame.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * Whether the user is currently resizing the crop frame.
	 * @type {boolean}
	 */
	resizing = false;
	/**
	 * Whether the resize inputs were manually modified.
	 * @type {boolean}
	 */
	resizeWasModified = false;

	/**
	 * @param {HTMLElement} container
	 */
	constructor(container) {
		if (container) {
			this.initCropArea(container);
		}

		// Add event listeners for mouse events
		this.canvas.addEventListener('mousedown', this.onMouseDown.bind(this));
		this.canvas.addEventListener('mouseup', this.onMouseUp.bind(this));
		this.canvas.addEventListener('mousemove', this.onMouseMove.bind(this));
		this.canvas.addEventListener('mouseleave', this.onMouseLeave.bind(this));

		this.canvas.addEventListener('click', () => {
			// Make sure the click event is not triggered by a drag operation
			if (Date.now() - this.mouseDownTime > 200 || !this.highlightedSection) {
				return;
			}

			this.focusPoint = this.highlightedSection;
			this.inputs.focusPoint.value = this.focusPoint.join(',');

			this.clearFrame();
			this.drawCropFrame();
		});

		window.eventHandler.add('resize', this.handleResize.bind(this));

		this.observer.addObserver(this.observeCropArea.bind(this));
	}

	/**
	 * Initializes the crop area.
	 * @param {HTMLElement} container - The container element.
	 */
	initCropArea(container) {
		this.container = container;

		this.imageSrc = baseUrl + container.dataset.imageSrc;

		const parent = container.parentElement;
		this.inputs = {
			x: parent.querySelector('input[name="crop[x]"]'),
			y: parent.querySelector('input[name="crop[y]"]'),
			width: parent.querySelector('input[name="crop[width]"]'),
			height: parent.querySelector('input[name="crop[height]"]'),
			resizeWidth: parent.querySelector('input[name="crop[resize_width]"]'),
			resizeHeight: parent.querySelector('input[name="crop[resize_height]"]'),
			focusPoint: parent.querySelector('input[name="focus_point"]')
		}

		this.focusPoint = this.inputs.focusPoint.value.split(',').map(Number);

		this.container.appendChild(this.canvas);

		this.image.onload = () => {
			// Set the natural size of the image as data attributes if they're not set
			if (!container.dataset.imageWidth) {
				this.container.dataset.imageWidth = this.image.naturalWidth;
			}
			if (!container.dataset.imageHeight) {
				this.container.dataset.imageHeight = this.image.naturalHeight;
			}

			// Resize the canvas to fit the container and draw the image
			this.resizeCanvas();

			const realSize = this.getCropFrameRealSize();

			// Update the input values
			this.inputs.x.value = Math.round(realSize.x);
			this.inputs.y.value = Math.round(realSize.y);
			this.inputs.width.value = Math.round(realSize.width);
			this.inputs.height.value = Math.round(realSize.height);
			this.inputs.resizeWidth.value = Math.round(realSize.width);
			this.inputs.resizeHeight.value = Math.round(realSize.height);
		};

		this.image.src = this.imageSrc;

		// Add a fullscreen button
		const fullscreenButton = document.createElement('button');
		fullscreenButton.type = 'button';
		fullscreenButton.textContent = 'Fullscreen';
		fullscreenButton.classList.add('Button', 'Button-Small', 'Button-Fullscreen');
		fullscreenButton.addEventListener('click', () => {
			if (document.fullscreenElement) {
				// noinspection JSIgnoredPromiseFromCall
				document.exitFullscreen();
			}
			else {
				// noinspection JSIgnoredPromiseFromCall
				this.container.requestFullscreen();
			}
		});
		this.container.appendChild(fullscreenButton);

		this.measurementsContainer = this.container.querySelector('.Measurements');
		window.eventHandler.add('input', this.handleInput.bind(this), this.measurementsContainer);
		window.eventHandler.add('keydown', this.handleKeyDown.bind(this), this.measurementsContainer);
	}

	/**
	 * Handle the input event.
	 * @param {Event} event
	 */
	handleInput(event) {
		// noinspection JSUnusedLocalSymbols
		const {
			x: xInput,
			y: yInput,
			width: widthInput,
			height: heightInput,
			resizeWidth: resizeWidthInput,
			resizeHeight: resizeHeightInput,
		} = this.inputs;
		const realSize = this.getCropFrameRealSize();

		if (event.target === resizeWidthInput || event.target === resizeHeightInput) {
			this.resizeWasModified = true;

			if (resizeWidthInput.value * 1 > resizeWidthInput.max * 1) {
				resizeWidthInput.value = resizeWidthInput.max;
			}
			if (resizeHeightInput.value * 1 > resizeHeightInput.max * 1) {
				resizeHeightInput.value = resizeHeightInput.max;
			}

			// The aspect ratio of the crop frame must be maintained for the resize inputs as well
			const aspectRatio = this.cropFrame.width / this.cropFrame.height;
			let width = resizeWidthInput.value;
			let height = resizeHeightInput.value;

			// Depending on the input that was changed, adjust the other input to maintain the aspect ratio
			if (event.target === resizeWidthInput) {
				height = width / aspectRatio;
				resizeHeightInput.value = Math.round(height);
			}
			else {
				width = height * aspectRatio;
				resizeWidthInput.value = Math.round(width);
			}

			return;
		}

		// The maximum width and height of the crop frame are the dimensions of the image minus the x and y values, translated to the scale of the image
		const maxWidth = this.container.dataset.imageWidth * 1 - realSize.x;
		const maxHeight = this.container.dataset.imageHeight * 1 - realSize.y;

		if (widthInput.value * 1 > maxWidth) {
			widthInput.value = maxWidth;
		}

		if (heightInput.value * 1 > maxHeight) {
			heightInput.value = maxHeight;
		}

		let width = widthInput.value;
		// Translate the width to the scale of the image
		const scaleX = this.container.dataset.imageWidth / this.imageOnCanvas.width;
		width = width / scaleX;

		let height = heightInput.value;
		// Translate the height to the scale of the image
		const scaleY = this.container.dataset.imageHeight / this.imageOnCanvas.height;
		height = height / scaleY;

		// Update the crop frame based on the input values
		this.setCropFrame(this.cropFrame.x, this.cropFrame.y, width, height);
	}

	/**
	 * Handle the keydown event.
	 * @param {KeyboardEvent} event
	 */
	handleKeyDown(event) {
		if (event.key === 'Enter') {
			event.preventDefault();
		}
	}

	/**
	 * Handle the resize event.
	 */
	handleResize = () => {
		requestAnimationFrame(this.resizeCanvas.bind(this));
	}

	/**
	 * Handle the resize event and redraw the image on the canvas.
	 */
	resizeCanvas() {
		this.canvas.remove();

		// Define the margin
		const margin = 40;
		const containerWidth = this.container.clientWidth;
		const containerHeight = this.container.clientHeight;

		// Initialize the dimensions to maintain aspect ratio
		let drawWidth = containerWidth - 2 * margin; // Subtract double the margin
		let drawHeight = containerHeight - 2 * margin; // Subtract double the margin
		const imageRatio = this.container.dataset.imageWidth / this.container.dataset.imageHeight;
		const canvasRatio = drawWidth / drawHeight;

		if (imageRatio > canvasRatio) {
			drawHeight = drawWidth / imageRatio;
		}
		else {
			drawWidth = drawHeight * imageRatio;
		}

		// Ensure the image never becomes larger than its original size
		drawWidth = Math.min(drawWidth, this.container.dataset.imageWidth);
		drawHeight = Math.min(drawHeight, this.container.dataset.imageHeight);

		// Calculate the position to center the image
		const drawX = (containerWidth - drawWidth) / 2;
		const drawY = (containerHeight - drawHeight) / 2;

		// Copy the current image data
		const currentImageOnCanvas = {...this.imageOnCanvas};

		// Reattach the canvas to its parent element
		this.container.appendChild(this.canvas);

		this.canvas.width = containerWidth;
		this.canvas.height = containerHeight;

		// Store the dimensions and position of the image on the canvas
		this.imageOnCanvas = {x: drawX, y: drawY, width: drawWidth, height: drawHeight};

		if (!this.cropFrame) {
			// Set the crop frame to the size of the image
			this.cropFrame = {...this.imageOnCanvas};
		}
		else {
			// Update the crop frame to keep the same position and size, relative to the image
			// Calculate the scale factors for width and height
			const scaleX = this.imageOnCanvas.width / currentImageOnCanvas.width;
			const scaleY = this.imageOnCanvas.height / currentImageOnCanvas.height;

			// Calculate the new position of the crop frame relative to the new image position
			const newX = (this.cropFrame.x - currentImageOnCanvas.x) * scaleX + this.imageOnCanvas.x;
			const newY = (this.cropFrame.y - currentImageOnCanvas.y) * scaleY + this.imageOnCanvas.y;

			// Update the crop frame's position and size
			this.cropFrame = {
				x: newX,
				y: newY,
				width: this.cropFrame.width * scaleX,
				height: this.cropFrame.height * scaleY
			};
		}

		// Draw the image onto the canvas
		this.ctx.drawImage(this.image, drawX, drawY, drawWidth, drawHeight);
		this.drawCropFrame();
	}

	/**
	 * Clears the canvas and redraws the image
	 */
	clearFrame() {
		this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

		// Redraw the image using the stored dimensions and position
		this.ctx.drawImage(this.image, this.imageOnCanvas.x, this.imageOnCanvas.y, this.imageOnCanvas.width, this.imageOnCanvas.height);
	}

	/**
	 * Draws the crop frame.
	 */
	drawCropFrame() {
		// Assuming you have a CSS variable --my-color defined in the :root or on the body
		const colorValue = getComputedStyle(document.documentElement)
		.getPropertyValue('--colorInfo')
		.trim();

		// Draw a semi-transparent overlay on the area outside the crop frame
		this.ctx.fillStyle = 'rgba(0, 0, 0, .5)';

		let topHeight = Math.round(this.cropFrame.y);
		let leftWidth = Math.round(this.cropFrame.x);
		let rightWidth = Math.round(this.canvas.width - this.cropFrame.x - this.cropFrame.width);
		let bottomHeight = Math.round(this.canvas.height - this.cropFrame.y - this.cropFrame.height);

		this.ctx.fillRect(0, 0, Math.round(this.canvas.width), topHeight); // top
		this.ctx.fillRect(0, topHeight, leftWidth, Math.round(this.cropFrame.height)); // left
		this.ctx.fillRect(leftWidth + Math.round(this.cropFrame.width), topHeight, rightWidth, Math.round(this.cropFrame.height)); // right
		this.ctx.fillRect(0, topHeight + Math.round(this.cropFrame.height), Math.round(this.canvas.width), bottomHeight); // bottom

		// Draw the crop frame
		this.ctx.strokeStyle = colorValue;
		this.ctx.setLineDash([]);
		this.ctx.lineWidth = 2;
		this.ctx.strokeRect(this.cropFrame.x, this.cropFrame.y, this.cropFrame.width, this.cropFrame.height);

		// Draw two vertical and two horizontal lines dividing the crop frame into thirds
		this.ctx.strokeStyle = 'rgba(255, 255, 255, .5)';
		this.ctx.setLineDash([5, 5]);
		this.ctx.lineWidth = 1;
		const thirdWidth = this.cropFrame.width / 3;
		const thirdHeight = this.cropFrame.height / 3;
		for (let i = 1; i <= 2; i++) {
			this.ctx.beginPath();
			this.ctx.moveTo(this.cropFrame.x + i * thirdWidth, this.cropFrame.y);
			this.ctx.lineTo(this.cropFrame.x + i * thirdWidth, this.cropFrame.y + this.cropFrame.height);
			this.ctx.stroke();

			this.ctx.beginPath();
			this.ctx.moveTo(this.cropFrame.x, this.cropFrame.y + i * thirdHeight);
			this.ctx.lineTo(this.cropFrame.x + this.cropFrame.width, this.cropFrame.y + i * thirdHeight);
			this.ctx.stroke();
		}

		// Draw squares on each corner and the middle of each edge of the crop frame
		this.ctx.fillStyle = colorValue;
		const squareSize = 8;
		const positions = [
			{x: this.cropFrame.x, y: this.cropFrame.y}, // top-left
			{x: this.cropFrame.x + this.cropFrame.width / 2, y: this.cropFrame.y}, // top-middle
			{x: this.cropFrame.x + this.cropFrame.width, y: this.cropFrame.y}, // top-right
			{x: this.cropFrame.x, y: this.cropFrame.y + this.cropFrame.height / 2}, // middle-left
			{x: this.cropFrame.x + this.cropFrame.width, y: this.cropFrame.y + this.cropFrame.height / 2}, // middle-right
			{x: this.cropFrame.x, y: this.cropFrame.y + this.cropFrame.height}, // bottom-left
			{x: this.cropFrame.x + this.cropFrame.width / 2, y: this.cropFrame.y + this.cropFrame.height}, // bottom-middle
			{x: this.cropFrame.x + this.cropFrame.width, y: this.cropFrame.y + this.cropFrame.height} // bottom-right
		];
		positions.forEach(pos => {
			this.ctx.fillRect(pos.x - squareSize / 2, pos.y - squareSize / 2, squareSize, squareSize);
		});

		// Draw a small cross in the center of the crop frame
		this.ctx.setLineDash([]);
		this.ctx.beginPath();
		this.ctx.moveTo(this.cropFrame.x + this.cropFrame.width / 2 - 5, this.cropFrame.y + this.cropFrame.height / 2);
		this.ctx.lineTo(this.cropFrame.x + this.cropFrame.width / 2 + 5, this.cropFrame.y + this.cropFrame.height / 2);
		this.ctx.moveTo(this.cropFrame.x + this.cropFrame.width / 2, this.cropFrame.y + this.cropFrame.height / 2 - 5);
		this.ctx.lineTo(this.cropFrame.x + this.cropFrame.width / 2, this.cropFrame.y + this.cropFrame.height / 2 + 5);
		this.ctx.stroke();

		let drawActiveHeart = false;
		// Draw the highlighted section with 2px distance from its edges
		if (this.highlightedSection) {
			const xSegment = this.highlightedSection[1];
			const ySegment = this.highlightedSection[0];

			const selected = this.highlightedSection[0] === this.focusPoint[0] && this.highlightedSection[1] === this.focusPoint[1];

			const width = this.cropFrame.width / 3 - 4;
			const height = this.cropFrame.height / 3 - 4;

			const x = this.cropFrame.x + xSegment * this.cropFrame.width / 3 + 2;
			const y = this.cropFrame.y + ySegment * this.cropFrame.height / 3 + 2;

			this.ctx.fillStyle = 'rgba(255, 255, 255, .2)';
			this.ctx.fillRect(x, y, width, height);

			// Draw a heart shape in the center of the highlighted section
			this.drawHeart(x + width / 2, y + height / 2, selected);

			if (!selected && this.focusPoint.length === 2) {
				drawActiveHeart = true;
			}
		}
		else if (this.focusPoint.length === 2) {
			drawActiveHeart = true;
		}

		if (drawActiveHeart) {
			const xSegment = this.focusPoint[1];
			const ySegment = this.focusPoint[0];

			const width = this.cropFrame.width / 3 - 4;
			const height = this.cropFrame.height / 3 - 4;

			const x = this.cropFrame.x + xSegment * this.cropFrame.width / 3 + 2;
			const y = this.cropFrame.y + ySegment * this.cropFrame.height / 3 + 2;

			this.drawHeart(x + width / 2, y + height / 2, true);
		}

		// Adjust the position of the measurements container
		const translateX = this.cropFrame.x + this.cropFrame.width / 2 - this.canvas.width / 2;
		const translateY = this.cropFrame.y + this.cropFrame.height - this.container.offsetHeight + 40;
		this.measurementsContainer.style.transform = `translate(${translateX}px, ${translateY}px)`;
	}

	/**
	 * Draws a heart shape on the canvas.
	 *
	 * @param {number} x - The x-coordinate of the heart.
	 * @param {number} y - The y-coordinate of the heart.
	 * @param {boolean} selected - Whether the heart is selected.
	 */
	drawHeart(x, y, selected) {
		this.ctx.save();
		this.ctx.translate(x-22.5, y-21);

		this.ctx.fillStyle = selected ? 'rgba(255, 0, 0, .75)' : 'rgba(255, 255, 255, .5)';

		const path = new Path2D(
			'M41.365,4.081C39.009,1.463,35.889.032,32.56.032s-6.459,1.442-8.815,4.06l-1.231,1.367-1.25-1.389C18.908,1.452,15.77,0,12.44,0S5.991,1.442,3.644,4.049C1.288,6.667-.009,10.144,0,13.844,0,17.543,1.307,21.009,3.663,23.627l17.916,17.907c.248.276.582.424.906.424s.658-.138.906-.413l17.954-17.875c2.356-2.618,3.654-6.095,3.654-9.794.01-3.699-1.278-7.176-3.635-9.794Z'
		);

		// Draw a heart shape in the center of the highlighted section
		this.ctx.fill(path);

		this.ctx.restore();

	}

	/**
	 * Sets the crop frame.
	 * @param {number} x
	 * @param {number} y
	 * @param {number} width
	 * @param {number} height
	 */
	setCropFrame(x, y, width, height) {
		this.cropFrame = {x, y, width, height};

		this.clearFrame();
		this.drawCropFrame();

		const realSize = this.getCropFrameRealSize();

		// Update the input values
		this.inputs.x.value = Math.round(realSize.x);
		this.inputs.y.value = Math.round(realSize.y);
		this.inputs.width.value = Math.round(realSize.width);
		this.inputs.height.value = Math.round(realSize.height);

		// Update the maximum values for the width and height inputs
		let maxWidth = this.container.dataset.imageWidth - realSize.x;
		let maxHeight = this.container.dataset.imageHeight - realSize.y;
		this.inputs.width.max = Math.round(maxWidth);
		this.inputs.height.max = Math.round(maxHeight);

		let maxResizeWidth = this.cropFrame.width;
		maxResizeWidth *= this.container.dataset.imageWidth / this.imageOnCanvas.width;
		let maxResizeHeight = this.cropFrame.height;
		maxResizeHeight *= this.container.dataset.imageHeight / this.imageOnCanvas.height;
		this.inputs.resizeWidth.max = Math.round(maxResizeWidth);
		this.inputs.resizeHeight.max = Math.round(maxResizeHeight);

		if (!this.resizeWasModified) {
			this.inputs.resizeWidth.value = Math.round(maxResizeWidth);
			this.inputs.resizeHeight.value = Math.round(maxResizeHeight);
		}
		else {
			// Update the resize inputs
			if (this.inputs.resizeWidth.value * 1 > maxResizeWidth * 1) {
				this.inputs.resizeWidth.value = Math.round(maxResizeWidth);
			}
			if (this.inputs.resizeHeight.value * 1 > maxResizeHeight * 1) {
				this.inputs.resizeHeight.value = Math.round(maxResizeHeight);
			}
		}
	}

	/**
	 * Gets the real size of the crop frame.
	 * @returns {Object}
	 */
	getCropFrameRealSize() {
		const scaleX = this.container.dataset.imageWidth / this.imageOnCanvas.width;
		const scaleY = this.container.dataset.imageHeight / this.imageOnCanvas.height;

		return {
			x: (this.cropFrame.x - this.imageOnCanvas.x) * scaleX,
			y: (this.cropFrame.y - this.imageOnCanvas.y) * scaleY,
			width: this.cropFrame.width * scaleX,
			height: this.cropFrame.height * scaleY
		};
	}

	/**
	 * Handle the mousedown event.
	 * @param {MouseEvent} event
	 */
	onMouseDown(event) {
		// Check if the mouse is on the edge of the crop frame for resizing
		if (this.isPointOnCropFrameEdge(event.clientX, event.clientY)) {
			this.resizing = true;
			this.container.classList.add('ResizeInProgress');
		}
		// Check if the mouse is within the crop frame
		else if (this.isPointInCropFrame(event.clientX, event.clientY)) {
			this.dragging = true;
		}

		this.mouseDownTime = Date.now();
	}

	/**
	 * Handle the mousemove event.
	 * @param {MouseEvent} event
	 */
	onMouseMove(event) {
		// Use requestAnimationFrame to smooth out the resizing and dragging operations
		requestAnimationFrame(() => {
			const isPointInCropFrame = this.isPointInCropFrame(event.clientX, event.clientY);
			const isPointOnCropFrameEdge = this.isPointOnCropFrameEdge(event.clientX, event.clientY);

			if (!isPointOnCropFrameEdge && isPointInCropFrame) {
				this.canvas.style.cursor = 'move';
			}
			else {
				// Set cursor based on edge proximity
				switch (this.draggedEdge) {
					case 'top-left':
					case 'bottom-right':
						this.canvas.style.cursor = 'nwse-resize';
						break;
					case 'top-right':
					case 'bottom-left':
						this.canvas.style.cursor = 'nesw-resize';
						break;
					case 'left':
					case 'right':
						this.canvas.style.cursor = 'ew-resize';
						break;
					case 'top':
					case 'bottom':
						this.canvas.style.cursor = 'ns-resize';
						break;
					default:
						this.canvas.style.cursor = 'default';
				}
			}

			this.highlightSection(event.clientX, event.clientY);

			if (this.resizing) {
				return this.resizeCropFrame(event);
			}
			else if (this.dragging) {
				return this.dragCropFrame(event);
			}
			else {
				this.clearFrame();
				return this.drawCropFrame();
			}
		});
	}

	/**
	 * Handle the mouseup event.
	 */
	onMouseUp() {
		this.dragging = false;
		this.resizing = false;
		this.draggedEdge = null;

		this.container.classList.remove('ResizeInProgress');
	}

	/**
	 * Handle the mouseleave event.
	 */
	onMouseLeave() {
		this.dragging = false;
		this.resizing = false;
		this.draggedEdge = null;

		this.container.classList.remove('ResizeInProgress');
	}

	/**
	 * Check if a point is within the crop frame.
	 * @param {number} x
	 * @param {number} y
	 * @returns {boolean}
	 */
	isPointInCropFrame(x, y) {
		// Get the bounding rectangle of the canvas
		const rect = this.canvas.getBoundingClientRect();

		// Calculate the coordinates relative to the canvas
		const canvasX = x - rect.left;
		const canvasY = y - rect.top;

		return canvasX >= this.cropFrame.x && canvasX <= this.cropFrame.x + this.cropFrame.width &&
			canvasY >= this.cropFrame.y && canvasY <= this.cropFrame.y + this.cropFrame.height;
	}

	/**
	 * Checks if a point is on the edge of the crop frame.
	 * @param {number} x - The x-coordinate of the point.
	 * @param {number} y - The y-coordinate of the point.
	 * @returns {boolean} - Returns true if the point is on the edge of the crop frame, false otherwise.
	 */
	isPointOnCropFrameEdge(x, y) {
		if (this.resizing) {
			return true;
		}

		// Get the bounding rectangle of the canvas
		const rect = this.canvas.getBoundingClientRect();
		const canvasX = x - rect.left;
		const canvasY = y - rect.top;

		// Define the edge size and calculate the crop frame boundaries including edge area
		const edgeSize = 10;
		const left = this.cropFrame.x - edgeSize;
		const right = this.cropFrame.x + this.cropFrame.width + edgeSize;
		const top = this.cropFrame.y - edgeSize;
		const bottom = this.cropFrame.y + this.cropFrame.height + edgeSize;

		// Early exit if point is out of extended crop frame boundaries
		if (canvasX < left || canvasX > right || canvasY < top || canvasY > bottom) {
			this.draggedEdge = null;
			return false;
		}

		// Determine proximity to crop frame edges
		const nearLeft = canvasX <= this.cropFrame.x + edgeSize;
		const nearRight = canvasX >= this.cropFrame.x + this.cropFrame.width - edgeSize;
		const nearTop = canvasY <= this.cropFrame.y + edgeSize;
		const nearBottom = canvasY >= this.cropFrame.y + this.cropFrame.height - edgeSize;

		// Set draggedEdge based on edge proximity
		if (nearTop && nearLeft) {
			this.draggedEdge = 'top-left';
		}
		else if (nearTop && nearRight) {
			this.draggedEdge = 'top-right';
		}
		else if (nearBottom && nearLeft) {
			this.draggedEdge = 'bottom-left';
		}
		else if (nearBottom && nearRight) {
			this.draggedEdge = 'bottom-right';
		}
		else if (nearLeft) {
			this.draggedEdge = 'left';
		}
		else if (nearRight) {
			this.draggedEdge = 'right';
		}
		else if (nearTop) {
			this.draggedEdge = 'top';
		}
		else if (nearBottom) {
			this.draggedEdge = 'bottom';
		}
		else {
			this.draggedEdge = null;
			return false;
		}

		return true;
	}

	/**
	 * Detect the section of the crop frame the cursor is in.
	 *
	 * @param {number} x - The x-coordinate of the cursor.
	 * @param {number} y - The y-coordinate of the cursor.
	 */
	highlightSection(x, y) {
		const thirdWidth = this.cropFrame.width / 3;
		const thirdHeight = this.cropFrame.height / 3;

		// Get the bounding rectangle of the canvas
		const rect = this.canvas.getBoundingClientRect();

		const cropFrameX = this.cropFrame.x + rect.left;
		const cropFrameY = this.cropFrame.y + rect.top;

		this.highlightedSection = [];

		if (y >= cropFrameY && y <= cropFrameY + thirdHeight) {
			this.highlightedSection.push(0);
		}
		else if (y >= cropFrameY + 2 * thirdHeight && y <= cropFrameY + this.cropFrame.height) {
			this.highlightedSection.push(2);
		}
		else if (y > cropFrameY + thirdHeight && y < cropFrameY + 2 * thirdHeight) {
			this.highlightedSection.push(1);
		}

		if (x >= cropFrameX && x <= cropFrameX + thirdWidth) {
			this.highlightedSection.push(0);
		}
		else if (x >= cropFrameX + 2 * thirdWidth && x <= cropFrameX + this.cropFrame.width) {
			this.highlightedSection.push(2);
		}
		else if (x > cropFrameX + thirdWidth && x < cropFrameX + 2 * thirdWidth) {
			this.highlightedSection.push(1);
		}

		if (this.highlightedSection.length !== 2) {
			this.highlightedSection = null;
		}
	}

	/**
	 * Resizes the crop frame based on the user's mouse movement.
	 * @param {MouseEvent} event - The mouse event.
	 */
	resizeCropFrame(event) {
		const {width: imgWidth, height: imgHeight, x: imgX, y: imgY} = this.imageOnCanvas;
		let newWidth = this.cropFrame.width;
		let newHeight = this.cropFrame.height;
		let newX = this.cropFrame.x;
		let newY = this.cropFrame.y;

		const aspectRatio = this.cropFrame.width / this.cropFrame.height;
		const ctrlPressed = event.ctrlKey;
		const minimumSize = 40;

		switch (this.draggedEdge) {
			case 'left':
				if (newX + event.movementX >= imgX && newX + event.movementX <= newX + newWidth - minimumSize) {
					newWidth -= event.movementX;
					newX += event.movementX;
				}
				break;
			case 'right':
				if (newX + newWidth + event.movementX <= imgX + imgWidth) {
					newWidth += event.movementX;
				}
				break;
			case 'top':
				if (newY + event.movementY >= imgY && newY + event.movementY <= newY + newHeight - minimumSize) {
					newHeight -= event.movementY;
					newY += event.movementY;
				}
				break;
			case 'bottom':
				if (newY + newHeight + event.movementY <= imgY + imgHeight) {
					newHeight += event.movementY;
				}
				break;
			case 'top-left':
				if (ctrlPressed) {
					// Calculate new size based on aspect ratio and mouse movement
					const movementY = Math.min(event.movementY, (newHeight - minimumSize));

					// Calculate the changes maintaining the aspect ratio
					let deltaX = Math.min(event.movementX, (newWidth - minimumSize)); // Change in width
					let deltaY = deltaX / aspectRatio; // Change in height calculated from width change

					// If the calculated height change is more than the actual mouse movement, adjust by height change instead
					if (Math.abs(deltaY) > Math.abs(movementY)) {
						deltaY = movementY;
						deltaX = deltaY * aspectRatio;
					}

					// Calculate potential new dimensions
					let potentialNewWidth = newWidth - deltaX;
					let potentialNewHeight = newHeight - deltaY;

					// Ensure the new dimensions do not go beyond the image boundaries
					if ((this.cropFrame.x + this.cropFrame.width - potentialNewWidth >= imgX) &&
						(this.cropFrame.y + this.cropFrame.height - potentialNewHeight >= imgY) &&
						potentialNewWidth >= minimumSize && potentialNewHeight >= minimumSize) {
						newWidth = potentialNewWidth;
						newHeight = potentialNewHeight;
						newX = this.cropFrame.x + this.cropFrame.width - newWidth;
						newY = this.cropFrame.y + this.cropFrame.height - newHeight;
					}
				}
				else if (newX + event.movementX >= imgX && newX + event.movementX <= newX + newWidth - minimumSize &&
					newY + event.movementY >= imgY && newY + event.movementY <= newY + newHeight - minimumSize) {
					newWidth -= event.movementX;
					newX += event.movementX;
					newHeight -= event.movementY;
					newY += event.movementY;
				}
				break;
			case 'top-right':
				if (ctrlPressed) {
					// Calculate the changes maintaining the aspect ratio
					let deltaX = Math.min(event.movementX, (imgX + imgWidth - newX - newWidth)); // Change in width
					let deltaY = deltaX / aspectRatio; // Change in height calculated from width change

					// Calculate potential new dimensions
					let potentialNewWidth = newWidth + deltaX;
					let potentialNewHeight = newHeight + deltaY; // Adjust the height based on the new width

					// Ensure the new dimensions do not go beyond the image boundaries
					if ((newX + potentialNewWidth <= imgX + imgWidth) &&
						(newY - deltaY >= imgY) &&
						potentialNewWidth >= minimumSize && potentialNewHeight >= minimumSize) {
						newWidth = potentialNewWidth;
						newHeight = potentialNewHeight;
						newY = newY - deltaY; // Adjust the Y position to keep the bottom edge fixed
					}
				}
				else if (newX + newWidth + event.movementX <= imgX + imgWidth &&
					newY + event.movementY >= imgY && newY + event.movementY <= newY + newHeight - minimumSize) {
					newWidth += event.movementX;
					newHeight -= event.movementY;
					newY += event.movementY;
				}
				break;
			case 'bottom-left':
				if (ctrlPressed) {
					// Calculate how much the width can potentially decrease to the left
					let maxChangeX = newX - imgX;  // Maximum leftward movement

					// Determine the width change from the mouse movement, constrained by the left boundary
					let widthChange = Math.max(event.movementX, -maxChangeX);

					// Calculate the corresponding height change to maintain the aspect ratio
					let heightChange = -widthChange * aspectRatio;

					// Compute the new dimensions
					let potentialNewWidth = newWidth - widthChange;
					let potentialNewHeight = newHeight + heightChange;

					// Calculate the new left position to keep the top-right corner fixed
					let potentialNewX = newX + widthChange;

					// Ensure the dimensions do not fall below the minimum size or exceed image boundaries
					if (potentialNewWidth >= minimumSize && potentialNewHeight >= minimumSize &&
						potentialNewX >= imgX && newY + potentialNewHeight <= imgY + imgHeight) {
						// Update dimensions and position if all conditions are satisfied
						newWidth = potentialNewWidth;
						newHeight = potentialNewHeight;
						newX = potentialNewX;
					}
				}
				else if (newX + event.movementX >= imgX && newX + event.movementX <= newX + newWidth - minimumSize &&
					newY + newHeight + event.movementY <= imgY + imgHeight) {
					newWidth -= event.movementX;
					newX += event.movementX;
					newHeight += event.movementY;
				}
				break;
			case 'bottom-right':
				if (ctrlPressed) {
					const size = Math.max(newWidth + event.movementX, minimumSize);
					if (newX + size <= imgX + imgWidth && newY + size / aspectRatio <= imgY + imgHeight) {
						newWidth = size;
						newHeight = size / aspectRatio; // maintain aspect ratio
					}
				}
				else if (newX + newWidth + event.movementX <= imgX + imgWidth &&
					newY + newHeight + event.movementY <= imgY + imgHeight) {
					newWidth += event.movementX;
					newHeight += event.movementY;
				}
				break;
		}

		// Ensure the crop frame does not exceed the image boundaries or shrink below the minimum size
		newX = Math.max(imgX, Math.min(newX, imgX + imgWidth - newWidth));
		newY = Math.max(imgY, Math.min(newY, imgY + imgHeight - newHeight));
		newWidth = Math.min(newWidth, imgX + imgWidth - newX);
		newHeight = Math.min(newHeight, imgY + imgHeight - newY);

		// Update the crop frame with new dimensions
		this.setCropFrame(newX, newY, newWidth, newHeight);
	}

	/**
	 * Drags the crop frame based on the user's mouse movement.
	 * @param {MouseEvent} event - The mouse event.
	 */
	dragCropFrame(event) {
		// Calculate the new position of the crop frame
		let newX = this.cropFrame.x + event.movementX;
		let newY = this.cropFrame.y + event.movementY;

		// Check if the new position is within the boundaries of the image
		if (newX < this.imageOnCanvas.x) {
			newX = this.imageOnCanvas.x;
		}
		else if (newX + this.cropFrame.width > this.imageOnCanvas.x + this.imageOnCanvas.width) {
			newX = this.imageOnCanvas.x + this.imageOnCanvas.width - this.cropFrame.width;
		}

		if (newY < this.imageOnCanvas.y) {
			newY = this.imageOnCanvas.y;
		}
		else if (newY + this.cropFrame.height > this.imageOnCanvas.y + this.imageOnCanvas.height) {
			newY = this.imageOnCanvas.y + this.imageOnCanvas.height - this.cropFrame.height;
		}

		// Update the position of the crop frame
		this.setCropFrame(newX, newY, this.cropFrame.width, this.cropFrame.height);
	}

	/**
	 * Observes the document for added crop areas.
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeCropArea(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches('.CropArea')) {
				this.initCropArea(node);
			}

			// Also check the children of the node
			node.querySelectorAll('.CropArea').forEach((childNode) => {
				this.initCropArea(childNode);
			});
		});
	}
}
