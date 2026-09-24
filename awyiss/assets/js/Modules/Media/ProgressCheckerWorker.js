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

/**
 * Reads the progress response directly and processes newline-delimited JSON messages.
 *
 * @param {ReadableStream<Uint8Array>} body - The response body stream.
 * @param {string} type - The progress type associated with the response.
 * @returns {Promise<void>} A promise that resolves after the stream has ended.
 */
async function readProgressResponse(body, type) {
	const reader = body.getReader();
	const decoder = new TextDecoder();
	let buffer = '';

	/**
	 * Parses and forwards one complete JSON line.
	 *
	 * @param {string} line - A complete line from the response body.
	 * @returns {Promise<boolean>} Whether the response indicates that polling is done.
	 */
	const processLine = async line => {
		const message = line.trim();
		if (!message) {
			return false;
		}

		let data;
		try {
			data = JSON.parse(message);
		}
		catch (error) {
			console.error('Error parsing JSON:', error);

			return false;
		}

		const clients = await self.clients.matchAll({type: 'window', includeUncontrolled: true});
		clients.forEach(client => {
			client.postMessage({
				command: 'serverMessage',
				data: data,
				type: type,
				workerId: 'mediaProgressChecker',
			});
		});

		return data.message === 'done';
	};

	try {
		while (true) {
			const result = await reader.read();
			const {done, value} = result;

			if (done) {
				buffer += decoder.decode();
				if (buffer.trim()) {
					await processLine(buffer);
				}

				return;
			}

			const chunk = decoder.decode(value, {stream: true});
			buffer += chunk;
			let newlineIndex;
			while ((newlineIndex = buffer.indexOf('\n')) !== -1) {
				const line = buffer.slice(0, newlineIndex);
				buffer = buffer.slice(newlineIndex + 1);
				if (await processLine(line)) {
					await reader.cancel();
					isFetching[ type ] = false;

					return;
				}
			}
		}
	}
	catch (error) {
		console.error('Media progress response stream failed:', error);

		throw error;
	}
}

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
					'X-CSRF-Token': event.data.csrfToken,
				},
				body: JSON.stringify({
					elements: event.data.elements,
					type: event.data.type,
				}),
			})
			.then(response => {
				if (!response.ok) {
					throw new Error(`Media progress request failed with HTTP ${response.status}.`);
				}

				if (!response.body) {
					throw new Error('Media progress request returned no response body.');
				}

				return readProgressResponse(response.body, event.data.type);
			})
			.catch(error => {
				// Log any errors that occur during the fetch operation
				console.error('Fetch error: ', error);
				const errorMessage = error instanceof Error ? error.message : String(error);

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
							data: errorMessage,
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