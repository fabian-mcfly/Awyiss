<?php declare(strict_types=1);


namespace Awyiss\Routing\Route;


use Awyiss\Utility\Inflector;
use BackedEnum;
use Cake\Routing\Route\DashedRoute;
use InvalidArgumentException;


/**
 * {@inheritDoc}
 *
 * It also handles parameters and their values by appending them after the action.
 * - param-value pairs are separated by '/'
 * - each param and its value are separated by ':'
 *
 * The URI `/my-controller/my-action/param1:value1/param2:value2` is parsed as
 *
 * ```
 * [
 *    'controller' => 'MyController',
 *    'action' => 'myAction',
 *    'param1' => 'value1',
 *    'param2' => 'value2',
 * ]
 * ```
 */
class AwyissRoute extends DashedRoute {
	/**
	 * Parses the given URL and method into an array of route parameters
	 *
	 * @param string $url The URL to parse.
	 * @param string $method The method to parse. Defaults to an empty string.
	 * @return array|null The parsed route parameters, or null if the URL or method did not match the route.
	 */
	public function parse(string $url, string $method = ''): ?array {
		//Stupid workaround since _writeRoute() unsets defaults;
		$defaults = $this->defaults;

		// Normalizing and validating the method if it's not an empty string.
		if ($method !== '') {
			$method = $this->normalizeAndValidateMethods($method);
		}

		// Compiling the route and parsing the URL extension.
		$compiledRoute = $this->compile();
		[$url, $ext] = $this->_parseExtension($url);

		/**
		 * Checking if the URL matches the compiled route and if the method matches the default method.
		 * If either check fails, it returns null.
		 */
		if (!preg_match($compiledRoute, urldecode($url), $route)) {
			return null;
		}
		if (isset($this->defaults['_method']) && !in_array($method, (array)$this->defaults['_method'], true)) {
			return null;
		}

		// Processing the route parameters, dropping any with numeric keys and setting any empty values to their defaults.
		foreach ($route as $key => $value) {
			//Drop values with numeric keys. Those are not Awyiss params
			if (is_numeric($key)) {
				unset($route[ $key ]);
			}

			//Use the default for empty values
			if (empty($value) && isset($defaults[ $key ])) {
				$route[ $key ] = $defaults[ $key ];
			}
		}

		// Initializing the 'pass', 'parts', 'slug', and 'fullSlug' keys of the route array.
		$route['pass'] = $route['parts'] = [];
		$route['slug'] ??= '';
		$route['fullSlug'] ??= '';

		// Assigning any remaining default values to the route array.
		foreach ($defaults as $key => $value) {
			if (isset($route[ $key ])) {
				continue;
			}
			$route[ $key ] = $value;
		}

		// Setting the '_ext' key if an extension was found in the URL.
		if (!empty($ext)) {
			$route['_ext'] = $ext;
		}


		// The route array is then passed to the setRouteArguments function and the result is returned.
		return $this->setRouteArguments($route);
	}


	/**
	 * Matches the given URL and context to a route.
	 * It compiles the route if not already compiled, dasherizes the URL and defaults,
	 * prepares the context, persists parameters if needed, and calls the _match function.
	 *
	 * @param array $url The URL to match.
	 * @param array $context The context to match. Defaults to an empty array.
	 * @return string|null The matched route, or null if no route was matched.
	 */
	public function match(array $url, array $context = []): ?string {
		//Stupid workaround since `_writeRoute()` inside `compile();` unsets defaults;
		$defaults = $this->defaults;

		// If the route has not been compiled, compile it.
		if (empty($this->_compiledRoute)) {
			$this->compile();
		}

		if ($defaults !== $this->defaults) {
			$this->defaults = $defaults;
		}

		// Dasherize the URL.
		$url = $this->_dasherize($url);

		// If the defaults have not been inflected, dasherize them.
		if (!$this->_inflectedDefaults) {
			$this->_inflectedDefaults = $this->_dasherize($this->defaults);
		}

		// Prepare the context by merging it with a default context array.
		$context += ['params' => [], '_port' => null, '_scheme' => null, '_host' => null];

		// If the 'persist' option is set and is an array, persist the parameters from the context into the URL.
		if (!empty($this->options['persist']) && is_array($this->options['persist'])) {
			$url = $this->_persistParams($url, $this->_dasherize($context['params']));
		}

		// Don't match a route with parameters.
		unset($context['params']);

		// Intersect the keys of the URL and the context to get the host options.
		$hostOptions = array_intersect_key($url, $context);

		// Call the _match function with the URL, host options, and context, and return the result.
		return $this->_match($url, $hostOptions, $context);
	}


	/**
	 * Converts a matching route array into a URL string.
	 *
	 * Composes the string URL using the template
	 * used to create the route.
	 */
	protected function buildUrlString(array $params, array $pass = [], array $query = []): string {
		/**
		 * Implode the passed parameters into a string, separating each parameter with a '/'.
		 * For each parameter, if the key is numeric or the value is false or null, skip it.
		 * Otherwise, combine the key and value using a ":", creating one url part of .../key1:param1/...
		 * If the value is a scalar, dasherize it.
		 * If the value is an array, implode it into a string, separating each element with a ',' and dasherize each element.
		 * If the value is an instance of BackedEnum, dasherize its value.
		 * Finally, url encode the key and value and return them as a string in the format "key:value".
		 */
		$passed = implode('/', array_map(function ($value, $key) {
			if (is_numeric($key) || $value === false || $value === null) {
				return null;
			}

			if (is_scalar($value)) {
				$value = Inflector::dasherize((string)$value);
			}
			elseif (is_array($value)) {
				$value = implode(',', array_map(fn (string|int $value) => Inflector::dasherize((string)$value), $value));
			}
			elseif ($value instanceof BackedEnum) {
				$value = Inflector::dasherize((string)$value->value);
			}


			return rawurlencode(Inflector::dasherize((string)$key)) . ':' . rawurlencode($value);
		}, $pass, array_keys($pass)));
		$passed = rtrim($passed, '/');

		// Get the template of the route.
		$url = $this->template;

		$search = $replace = [];
		/**
		 * For each key in the route, if the key is 'params', skip it.
		 * Otherwise, if the key does not exist in the parameters, throw an InvalidArgumentException.
		 * Add the key surrounded by '{}' to the search array and its corresponding value in the parameters to the replace-array.
		 */
		foreach ($this->keys as $key) {
			if ($key == 'params') {
				continue;
			}

			if (!array_key_exists($key, $params)) {
				throw new InvalidArgumentException(sprintf('Missing required route key `%s`', $key));
			}

			$search[] = '{' . $key . '}';
			$replace[] = $params[ $key ];
		}

		/**
		 * If the template contains '**', add '**' and '%2F' to the search array and the passed parameters string and '/' to the replace-array.
		 * If the template contains '*', add '*' to the search array.
		 * If the 'slug' parameter is not empty, add the 'slug' parameter and the passed parameters string (if not empty) separated by '/' to the replace-array.
		 * Otherwise, add the passed parameters string to the replace-array.
		 */
		if (str_contains($this->template, '**')) {
			array_push($search, '**', '%2F');
			array_push($replace, $passed, '/');
		}
		elseif (str_contains($this->template, '*')) {
			$search[] = '*';
			$replace[] = $passed;
		}

		// Replace keys surrounded by {} in the url with their corresponding values.
		$url = str_replace($search, $replace, $url);

		// Complete the url scheme and return the url.
		return $this->completeUrlScheme($url, $params, $query);
	}


	/**
	 * This function sets the route arguments for a given route array.
	 *
	 * @param array $route The route array to set arguments for.
	 * @return array The route array with arguments set.
	 */
	protected function setRouteArguments(array $route): array {
		// If the '_args_' key is set in the route array, parse the arguments.
		if (isset($route['_args_'])) {
			$foundParams = false;
			// For each parsed argument, if it contains a ':', split it into a key and a value.
			foreach ($this->_parseArgs($route['_args_'], $route) as $part) {
				if (str_contains($part, ':')) {
					$foundParams = true;
					[$key, $value] = explode(':', $part);
					// camelBack the value.
					$value = Inflector::variable($value);

					// camelBack the key.
					$key = Inflector::variable($key);

					// If the key does not exist in the route array, add it.
					if (!array_key_exists($key, $route)) {
						$route[ $key ] = $value;
					}

					// Add the key and value to the 'parts' array in the route array.
					$route['parts'][ $key ] = $value;
				}
				// If no parameters have been found yet, add the part to the 'slug'.
				elseif (!$foundParams) {
					$route['slug'] .= '/' . $part;
					$route['parts'][] = $part;
				}
			}

			// Remove the leading '/' from the 'slug'.
			$route['slug'] = ltrim($route['slug'], '/');

			// Implode the 'parts' array into a string, separating each part with a '/'.
			$route['fullSlug'] = implode('/', array_map(function ($value, $key) {
				if (is_numeric($key)) {
					return Inflector::dasherize($value);
				}

				return $key . ':' . Inflector::dasherize($value);
			}, $route['parts'], array_keys($route['parts'])));

			// Unset the '_args_' key from the route array.
			unset($route['_args_']);
		}

		// Initialize the 'pass' array.
		$pass = [];
		// For each key in the 'pass' option, if it exists in the route array, add it to the 'pass' array and remove it from the route array.
		foreach ($this->options['pass'] ?? [] as $key) {
			$value = null;
			if (isset($route[ $key ])) {
				$value = $route[ $key ];
				unset($route[ $key ]);
			}

			$pass[ $key ] = $value;
		}

		// Set the 'pass' key in the route array to the 'pass' array.
		$route['pass'] = $pass;
		// Merge the 'pass' array and the 'parts' array in the route array.
		$route['parts'] = array_merge($pass, $route['parts']);

		// If the '_name' option is set, set the '_name' key in the route array to it.
		if (isset($this->options['_name'])) {
			$route['_name'] = $this->options['_name'];
		}

		// Set the '_route' key in the route array to this route.
		$route['_route'] = $this;
		// Set the '_matchedRoute' key in the route array to the template of this route.
		$route['_matchedRoute'] = $this->template;
		// If there is middleware for this route, set the '_middleware' key in the route array to it.
		if (count($this->middleware) > 0) {
			$route['_middleware'] = $this->middleware;
		}

		// If the 'controller' key is set in the route array, camelCase it.
		if (!empty($route['controller'])) {
			$route['controller'] = Inflector::camelize($route['controller'], '-');
		}

		// If the 'plugin' key is set in the route array, camelCase it.
		if (!empty($route['plugin'])) {
			$route['plugin'] = $this->_camelizePlugin($route['plugin']);
		}

		// If the 'action' key is set in the route array, underscore it and then camelCase it.
		if (!empty($route['action'])) {
			$route['action'] = Inflector::variable(Inflector::underscore($route['action']));
		}


		// Return the route array.
		return $route;
	}


	/**
	 * This function applies host options to a given host options array and context array.
	 *
	 * @param array $hostOptions The host options array to apply options to.
	 * @param array $context The context array to use for applying options.
	 * @return array|null The host options array with options applied, or null if the host did not match the route preferences.
	 */
	protected function applyHostOptions(array $hostOptions, array $context): ?array {
		// Apply the _host option if possible
		if (isset($this->options['_host'])) {
			// If the _host key is not set in the host options array and the _host option does not contain a '*', set the _host key in the host options array to the _host option.
			if (!isset($hostOptions['_host']) && !str_contains($this->options['_host'], '*')) {
				$hostOptions['_host'] = $this->options['_host'];
			}
			// If the _host key is not set in the host options array, set it to the _host key in the context array.
			if (!isset($hostOptions['_host'])) {
				$hostOptions['_host'] = $context['_host'];
			}

			// If the host does not match the route preferences, return null.
			if (!$this->hostMatches((string)$hostOptions['_host'])) {
				return null;
			}
		}

		// Check for properties that will cause an absolute url. Copy the other properties over.
		if (isset($hostOptions['_scheme']) || isset($hostOptions['_port']) || isset($hostOptions['_host'])) {
			$hostOptions += $context;

			// If the _scheme key is set in the host options array and the service name for the _scheme key in the host options array is the same as the _port key in the host options array, unset the _port key.
			if ($hostOptions['_scheme'] && getservbyname($hostOptions['_scheme'], 'tcp') === $hostOptions['_port']) {
				unset($hostOptions['_port']);
			}
		}

		// If no base is set, copy one in.
		if (!isset($hostOptions['_base']) && isset($context['_base'])) {
			$hostOptions['_base'] = $context['_base'];
		}

		return $hostOptions;
	}


	/**
	 * This function completes the URL scheme for a given URL, parameters array, and query array.
	 *
	 * @param string $url The URL to complete the scheme for.
	 * @param array $params The parameters array to use for completing the scheme.
	 * @param array $query The query array to use for completing the scheme.
	 * @return array|string The URL with the scheme completed.
	 */
	protected function completeUrlScheme(string $url, array $params, array $query): string|array {
		// If the '_base' key is set in the parameters array, prepend it to the URL and unset it from the parameters array.
		if (isset($params['_base'])) {
			$url = $params['_base'] . $url;
			unset($params['_base']);
		}

		// Replace any double slashes in the URL with a single slash.
		$url = str_replace('//', '/', $url);

		/**
		 * If the '_scheme', '_host', or '_port' key is set in the parameters array, construct the host and prepend it to the URL.
		 * If the '_port' key is set in the parameters array, append it to the host.
		 * The scheme defaults to 'https' if the '_scheme' key is not set in the parameters array.
		 */
		if (isset($params['_scheme']) || isset($params['_host']) || isset($params['_port'])) {
			$host = $params['_host'];

			if (isset($params['_port'])) {
				$host .= ':' . $params['_port'];
			}

			$scheme = $params['_scheme'] ?? 'https';
			$url = $scheme . '://' . $host . $url;
		}

		$url = rtrim($url, '/');
		// If the '_ext' key is set in the parameters array, append it to the URL with a '.'.
		if (!empty($params['_ext'])) {
			$url .= '.' . $params['_ext'];
		}
		else {
			// Make sure the URL ends with a slash if it doesn't contain a query string.
			$url = rtrim($url, '/') . '/';
		}

		/**
		 * If the query array is not empty, append it to the URL with a '?'.
		 * The query array is converted to a string using http_build_query.
		 * Any trailing '?' is removed.
		 */
		if (!empty($query)) {
			$url .= rtrim('?' . http_build_query($query), '?');
		}

		// Return the URL.
		return $url;
	}


	/**
	 * This function handles passed parameters for a given URL array and key names array.
	 *
	 * @param array $url The URL array to handle parameters for.
	 * @param array $keyNames The key names array to use for handling parameters.
	 * @return array An array containing the modified URL array and the pass array.
	 */
	protected function handlePassedParameters(array $url, array $keyNames): array {
		// Initialize the pass array.
		$pass = [];
		// For each key-value pair in the URL array...
		foreach ($url as $key => $value) {
			// If the key is a routed key, it's not different yet, so skip it.
			if (array_key_exists($key, $keyNames)) {
				continue;
			}

			// Pull out passed args
			// If the key is not a known key, and it is set in the defaults and its value is the same as in the defaults, skip it.
			$unknownKey = !in_array($key, ['controller', 'action', 'plugin', '#', 'parts', 'pass', 'fullSlug', '_matchedRoute', '_ext']);
			if ($unknownKey && isset($this->defaults[ $key ]) && $this->defaults[ $key ] === $value) {
				continue;
			}

			// If the key is not a known key...
			if ($unknownKey) {
				// Make sure the value isn't an array.
				$value = is_array($value) ? implode(',', $value) : $value;

				// If the key is numeric, add the value to the pass array.
				// Otherwise, dasherize the key and add it and its value to the pass array.
				if (is_numeric($key)) {
					$pass[] = $value;
				}
				else {
					$pass[ Inflector::dasherize($key) ] = $value;
				}

				// Remove the key-value pair from the URL array.
				unset($url[ $key ]);
			}
		}

		return [$url, $pass];
	}


	/**
	 * This function matches a given URL, host options, and context to a route.
	 *
	 * @param array $url The URL to match.
	 * @param array $hostOptions The host options to match.
	 * @param array $context The context to match.
	 * @return string|null The matched route, or null if no route was matched.
	 */
	protected function _match(array $url, array $hostOptions, array $context): ?string {
		// Apply host options to the host options array. If the host does not match the route preferences, return null.
		$hostOptions = $this->applyHostOptions($hostOptions, $context);
		if (!$hostOptions) {
			return null;
		}

		// If the '?' key is set in the URL array, cast its value to an array and assign it to the query variable. Otherwise, assign an empty array to the query variable.
		$query = !empty($url['?']) ? (array)$url['?'] : [];

		// Unset the '_host', '_scheme', '_port', '_base', and '?' keys from the URL array.
		unset($url['_host'], $url['_scheme'], $url['_port'], $url['_base'], $url['?']);

		// If the '_ext' key is set in the URL array, move its value into the host options array and unset it from the URL array.
		if (isset($url['_ext'])) {
			$hostOptions['_ext'] = $url['_ext'];
			unset($url['_ext']);
		}

		// Check the method first as it is special. If the method does not match, return null.
		if (!$this->_matchMethod($url)) {
			return null;
		}

		// Unset the '_method' and '[method]' keys from the URL array and the '_method' key from the defaults array.
		unset($url['_method'], $url['[method]'], $this->defaults['_method']);

		// If there are keys in the defaults array that do not exist in the URL array, return null.
		if (array_diff_key($this->defaults, $url) !== []) {
			return null;
		}

		// Flip the keys array and assign it to the key names variable.
		// If the intersection of the key names array and the URL array does not equal the key names array, return null.
		$keyNames = array_flip($this->keys);
		if (array_intersect_key($keyNames, $url) !== $keyNames) {
			return null;
		}

		// Handle passed parameters for the URL array and key names array and assign the result to the URL array and pass array.
		[$url, $pass] = $this->handlePassedParameters($url, $keyNames);

		// If this is not a greedy route and the pass array is not empty, return null.
		if (!$this->_greedy && !empty($pass)) {
			return null;
		}

		// If the options array is not empty, for each key-value pair in the options array...
		if (!empty($this->options)) {
			// If the key exists in the URL array and its value does not match the pattern, return null.
			if (array_any($this->options, fn ($pattern, $key) => isset($url[ $key ]) && !preg_match('#^' . $pattern . '$#u', (string)$url[ $key ]))) {
				return null;
			}
		}

		// Add the host options array to the URL array.
		$url += $hostOptions;

		// If the 'controller', 'action', or 'slug' key is set in the key names array and is not set in the URL array, return null.
		if (
			(isset($keyNames['controller']) && !isset($url['controller'])) ||
			(isset($keyNames['action']) && !isset($url['action'])) ||
			(isset($keyNames['slug']) && !isset($url['slug']))
		) {
			return null;
		}


		// Build the URL string from the URL array, pass array, and query variable and return it.
		return $this->buildUrlString($url, $pass, $query);
	}
}
