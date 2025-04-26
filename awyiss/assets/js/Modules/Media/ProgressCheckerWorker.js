/*
 * This worker is used to check the progress of resize/preview/webp generation
 * and send the progress to the main JavaScript context
 */

/**
 * Flag to indicate if a fetch request is in progress
 * @type {Object}
 */
let isFetching = {
	preview: false,
	resize: false,
	webp: false,
};

self.addEventListener('message', function (event) {
	// Only start a new fetch if one is not already in progress
	if (event.data.command === 'startChecking' && !isFetching[event.data.type]) {
		// Set the flag to true for the specified type to indicate a fetch request is in progress
		isFetching[event.data.type] = true;

		event.waitUntil(new Promise(function (resolve) {
			setTimeout(resolve, 180000);
		}));

		// Fetch the data from the server
		event.waitUntil(
			fetch(event.data.url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					elements: event.data.elements,
					type: event.data.type,
				}),
			})
			.then(response => {
				// Create a new Reader object
				const reader = response.body.getReader();

				/**
				 * Returns a new ReadableStream to the main JavaScript context.
				 * The ReadableStream is used to read the response body of the fetch request.
				 *
				 * @returns {ReadableStream} A ReadableStream that can be used to read the response body.
				 */
				return new ReadableStream({
					/**
					 * The start method is called immediately when the ReadableStream is created.
					 *
					 * @param {ReadableStreamDefaultController} controller - The controller instance associated with the ReadableStream.
					 */
					start(controller) {
						/**
						 * The push function reads a chunk of data from the response body and enqueues it in the ReadableStream.
						 * If the end of the response body is reached, the ReadableStream is closed and the isFetching flag is set to false.
						 */
						function push() {
							reader.read().then(({done, value}) => {
								if (done) {
									controller.close();
									isFetching[event.data.type] = false;
									return;
								}
								controller.enqueue(value);
								push();
							});
						}

						push();
					}
				});
			})
			.then(stream => {
				/**
				 * A Reader object to read the stream.
				 * @type {ReadableStreamDefaultReader}
				 */
				const reader = stream.getReader();

				/**
				 * A TextDecoder object to decode the stream into text.
				 * @type {TextDecoder}
				 */
				let decoder = new TextDecoder();

				/**
				 * The read function reads a chunk of data from the stream and decodes it into text.
				 * If the end of the stream is reached, the function returns.
				 * Otherwise, it posts the decoded text to the main JavaScript context and calls itself recursively to read the next chunk of data.
				 */
				function read() {
					reader.read().then(({value, done}) => {
						if (done) {
							isFetching[event.data.type] = false;

							return;
						}

						/**
						 * The decoded text message.
						 * @type {string}
						 */
						let message = decoder.decode(value, {stream: !done});
						message = message.trim();

						// If there's a newline in the message, it's likely that the message contains multiple JSON objects.
						if (message && message.indexOf('\n') !== -1) {
							// Split the message by newline and use the last row as the data.
							const messages = message.split('\n');
							message = messages[messages.length - 1].trim();
						}

						let data;

						try {
							data = JSON.parse(message);
						}
						catch (e) {
							console.error('Error parsing JSON:', e);
							return;
						}

						// Post the data to the main JavaScript context
						self.clients.matchAll().then(function (clients) {
							clients.forEach(function (client) {
								/**
								 * Post a message to the client with the workerId, command, and data.
								 */
								client.postMessage({
									command: 'serverMessage',
									data: data,
									type: event.data.type,
									workerId: 'mediaProgressChecker',
								});
							});
						});

						if (data.message === 'done') {
							// Set the flag back to false when the fetch request is complete
							isFetching[event.data.type] = false;
							return;
						}

						// Recursive call to read the next chunk of data
						read();
					});
				}

				// Initial call to the read function
				read();
			})
			.catch(error => {
				// Log any errors that occur during the fetch operation
				console.error('Fetch error: ', error);

				/**
				 * If an error occurs during the fetch request, the isFetching flag is set back to false.
				 * This allows new fetch requests to be initiated.
				 */
				isFetching[event.data.type] = false;

				/**
				 * The clients.matchAll() method returns a Promise that resolves to an array of Client objects representing all clients.
				 * This includes clients controlled by this service worker and clients in the same origin that are not controlled by this service worker.
				 * @returns {Promise<Array<Client>>} A Promise that resolves to an array of Client objects.
				 */
				self.clients.matchAll().then(function (clients) {
					/**
					 * For each client, post a message with the workerId, command, and data.
					 * The workerId is 'mediaProgressChecker', the command is 'serverError', and the data is the error message.
					 */
					clients.forEach(function (client) {
						client.postMessage({
							command: 'serverError',
							data: error.message,
							type: event.data.type,
							workerId: 'mediaProgressChecker',
						});
					});
				});
			})
		);
	}
});


self.addEventListener('unload', function () {
	self.clients.matchAll().then(function (clients) {
		clients.forEach(function (client) {
			client.postMessage({
				workerId: 'mediaProgressChecker',
				command: 'workerShutdown',
				data: 'Service worker is shutting down.'
			});
		});
	});
});