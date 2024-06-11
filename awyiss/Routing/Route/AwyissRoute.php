<?php declare(strict_types=1);


namespace Awyiss\Routing\Route;


use BackedEnum;
use Cake\Routing\Route\DashedRoute;
use Cake\Utility\Inflector;
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
	 * Parses the given URL and method into an array of route parameters.
	 * This function first saves the default route parameters, then compiles the route and parses the URL extension.
	 * It then checks if the URL matches the compiled route and if the method matches the default method.
	 * If either check fails, it returns null.
	 * It then processes the route parameters, dropping any with numeric keys and setting any empty values to their defaults.
	 * It also initializes the 'pass', 'parts', 'slug', and 'fullSlug' keys of the route array.
	 * Finally, it assigns any remaining default values to the route array and sets the '_ext' key if an extension was found in the URL.
	 * The route array is then passed to the setRouteArguments function and the result is returned.
	 *
	 * @param string $url The URL to parse.
	 * @param string $method The method to parse. Defaults to an empty string.
	 * @return array|null The parsed route parameters, or null if the URL or method did not match the route.
	 */
	public function parse(string $url, string $method = ''): ?array {
		//Stupid workaround since _writeRoute() unsets defaults;
		$la_defaults = $this->defaults;

		// Normalizing and validating the method if it's not an empty string.
		$ls_method = $method;
		if ($method !== '') {
			$ls_method = $this->normalizeAndValidateMethods($method);
		}

		// Compiling the route and parsing the URL extension.
		$ls_compiledRoute = $this->compile();
		[$ls_url, $ls_ext] = $this->_parseExtension($url);

		/**
		 * Checking if the URL matches the compiled route and if the method matches the default method.
		 * If either check fails, it returns null.
		 */
		if (!preg_match($ls_compiledRoute, urldecode($ls_url), $la_route)) {
			return null;
		}
		if (isset($this->defaults['_method']) && !in_array($ls_method, (array)$this->defaults['_method'], true)) {
			return null;
		}

		// Processing the route parameters, dropping any with numeric keys and setting any empty values to their defaults.
		foreach ($la_route as $lx_key => $lx_value) {
			//Drop values with numeric keys. Those are not Awyiss params
			if (is_numeric($lx_key)) {
				unset($la_route[ $lx_key ]);
			}

			//Use the default for empty values
			if (empty($lx_value) && isset($la_defaults[ $lx_key ])) {
				$la_route[ $lx_key ] = $la_defaults[ $lx_key ];
			}
		}

		// Initializing the 'pass', 'parts', 'slug', and 'fullSlug' keys of the route array.
		$la_route['pass'] = $la_route['parts'] = [];
		$la_route['slug'] ??= '';
		$la_route['fullSlug'] ??= '';

		// Assigning any remaining default values to the route array.
		foreach ($la_defaults as $lx_key => $lx_value) {
			if (isset($la_route[ $lx_key ])) {
				continue;
			}
			$la_route[ $lx_key ] = $lx_value;
		}

		// Setting the '_ext' key if an extension was found in the URL.
		if (!empty($ls_ext)) {
			$la_route['_ext'] = $ls_ext;
		}


		// The route array is then passed to the setRouteArguments function and the result is returned.
		return $this->setRouteArguments($la_route);
	}


	/**
	 * Matches the given URL and context to a route.
	 * This function first checks if the route has been compiled, and if not, compiles it.
	 * It then dasherizes the URL and the defaults (if they haven't been dasherized already).
	 * The context is then prepared by merging it with a default context array.
	 * If the 'persist' option is set and is an array, the function persists the parameters from the context into the URL.
	 * The function then unsets the 'params' key from the context, as routes with parameters are not matched.
	 * The function then intersects the keys of the URL and the context to get the host options.
	 * Finally, it calls the _match function with the URL, host options, and context, and returns the result.
	 *
	 * @param array $url The URL to match.
	 * @param array $context The context to match. Defaults to an empty array.
	 * @return string|null The matched route, or null if no route was matched.
	 */
	public function match(array $url, array $context = []): ?string {
		// If the route has not been compiled, compile it.
		if (empty($this->_compiledRoute)) {
			$this->compile();
		}

		// Dasherize the URL.
		$la_url = $this->_dasherize($url);

		// If the defaults have not been inflected, dasherize them.
		if (!$this->_inflectedDefaults) {
			$this->_inflectedDefaults = $this->_dasherize($this->defaults);
		}

		// Prepare the context by merging it with a default context array.
		$la_context = $context + ['params' => [], '_port' => null, '_scheme' => null, '_host' => null];

		// If the 'persist' option is set and is an array, persist the parameters from the context into the URL.
		if (!empty($this->options['persist']) && is_array($this->options['persist'])) {
			$la_url = $this->_persistParams($la_url, $this->_dasherize($la_context['params']));
		}

		// Don't match a route with parameters.
		unset($la_context['params']);

		// Intersect the keys of the URL and the context to get the host options.
		$la_hostOptions = array_intersect_key($la_url, $la_context);


		// Call the _match function with the URL, host options, and context, and return the result.
		return $this->_match($la_url, $la_hostOptions, $la_context);
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
		$ls_pass = implode('/', array_map(function ($value, $key) {
			if (is_numeric($key) || $value === false || $value === null) {
				return null;
			}

			$ls_value = $value;
			if (is_scalar($value)) {
				$ls_value = Inflector::dasherize((string)$value);
			}
			elseif (is_array($value)) {
				$ls_value = implode(',', array_map(fn (string|int $value) => Inflector::dasherize((string)$value), $value));
			}
			elseif ($value instanceof BackedEnum) {
				$ls_value = Inflector::dasherize((string)$value->value);
			}


			return rawurlencode(Inflector::dasherize((string)$key)) . ':' . rawurlencode($ls_value);
		}, $pass, array_keys($pass)));
		$ls_pass = rtrim($ls_pass, '/');

		// Get the template of the route.
		$ls_url = $this->template;

		$la_search = $la_replace = [];
		/**
		 * For each key in the route, if the key is 'params', skip it.
		 * Otherwise, if the key does not exist in the parameters, throw an InvalidArgumentException.
		 * Add the key surrounded by '{}' to the search array and its corresponding value in the parameters to the replace-array.
		 */
		foreach ($this->keys as $ls_key) {
			if ($ls_key == 'params') {
				continue;
			}

			if (!array_key_exists($ls_key, $params)) {
				throw new InvalidArgumentException(sprintf('Missing required route key `%s`', $ls_key));
			}

			$la_search[] = '{' . $ls_key . '}';
			$la_replace[] = $params[ $ls_key ];
		}

		/**
		 * If the template contains '**', add '**' and '%2F' to the search array and the passed parameters string and '/' to the replace-array.
		 * If the template contains '*', add '*' to the search array.
		 * If the 'slug' parameter is not empty, add the 'slug' parameter and the passed parameters string (if not empty) separated by '/' to the replace-array.
		 * Otherwise, add the passed parameters string to the replace-array.
		 */
		if (str_contains($this->template, '**')) {
			array_push($la_search, '**', '%2F');
			array_push($la_replace, $ls_pass, '/');
		}
		elseif (str_contains($this->template, '*')) {
			$la_search[] = '*';
			if (!empty($params['slug'])) {
				$la_replace[] = $params['slug'] . (!empty($ls_pass) ? '/' . $ls_pass : null);
			}
			else {
				$la_replace[] = $ls_pass;
			}
		}

		// Replace keys surrounded by {} in the url with their corresponding values.
		$ls_url = str_replace($la_search, $la_replace, $ls_url);


		// Complete the url scheme and return the url.
		return $this->completeUrlScheme($ls_url, $params, $query);
	}


	/**
	 * This function sets the route arguments for a given route array.
	 *
	 * @param array $route The route array to set arguments for.
	 * @return array The route array with arguments set.
	 */
	protected function setRouteArguments(array $route): array {
		// Copy the route array.
		$la_route = $route;

		// If the '_args_' key is set in the route array, parse the arguments.
		if (isset($la_route['_args_'])) {
			$lb_foundParams = false;
			// For each parsed argument, if it contains a ':', split it into a key and a value.
			foreach ($this->_parseArgs($la_route['_args_'], $la_route) as $ls_part) {
				if (str_contains($ls_part, ':')) {
					$lb_foundParams = true;
					[$ls_key, $ls_value] = explode(':', $ls_part);
					// Underscore and then camelCase the value.
					$ls_value = Inflector::underscore($ls_value);

					// Underscore and then camelCase the key.
					$ls_key = Inflector::variable(Inflector::underscore($ls_key));

					// If the key does not exist in the route array, add it.
					if (!array_key_exists($ls_key, $la_route)) {
						$la_route[ $ls_key ] = $ls_value;
					}

					// Add the key and value to the 'parts' array in the route array.
					$la_route['parts'][ $ls_key ] = $ls_value;
				}
				// If no parameters have been found yet, add the part to the 'slug'.
				elseif (!$lb_foundParams) {
					$la_route['slug'] .= '/' . $ls_part;
					$la_route['parts'][] = $ls_part;
				}
			}

			// Remove the leading '/' from the 'slug'.
			$la_route['slug'] = ltrim($la_route['slug'], '/');

			// Implode the 'parts' array into a string, separating each part with a '/'.
			$la_route['fullSlug'] = implode('/', array_map(function ($value, $key) {
				if (is_numeric($key)) {
					return $value;
				}


				return $key . ':' . $value;
			}, $la_route['parts'], array_keys($la_route['parts'])));

			// Unset the '_args_' key from the route array.
			unset($la_route['_args_']);
		}

		// Initialize the 'pass' array.
		$la_pass = [];
		// For each key in the 'pass' option, if it exists in the route array, add it to the 'pass' array and remove it from the route array.
		foreach ($this->options['pass'] ?? [] as $ls_key) {
			$ls_value = null;
			if (isset($la_route[ $ls_key ])) {
				$ls_value = $la_route[ $ls_key ];
				unset($la_route[ $ls_key ]);
			}

			$la_pass[ $ls_key ] = $ls_value;
		}

		// Set the 'pass' key in the route array to the 'pass' array.
		$la_route['pass'] = $la_pass;
		// Merge the 'pass' array and the 'parts' array in the route array.
		$la_route['parts'] = array_merge($la_pass, $la_route['parts']);

		// If the '_name' option is set, set the '_name' key in the route array to it.
		if (isset($this->options['_name'])) {
			$la_route['_name'] = $this->options['_name'];
		}

		// Set the '_route' key in the route array to this route.
		$la_route['_route'] = $this;
		// Set the '_matchedRoute' key in the route array to the template of this route.
		$la_route['_matchedRoute'] = $this->template;
		// If there is middleware for this route, set the '_middleware' key in the route array to it.
		if (count($this->middleware) > 0) {
			$la_route['_middleware'] = $this->middleware;
		}

		// If the 'controller' key is set in the route array, camelCase it.
		if (!empty($la_route['controller'])) {
			$la_route['controller'] = Inflector::camelize($la_route['controller'], '-');
		}

		// If the 'plugin' key is set in the route array, camelCase it.
		if (!empty($la_route['plugin'])) {
			$la_route['plugin'] = $this->_camelizePlugin($la_route['plugin']);
		}

		// If the 'action' key is set in the route array, underscore it and then camelCase it.
		if (!empty($la_route['action'])) {
			$la_route['action'] = Inflector::variable(Inflector::underscore($la_route['action']));
		}


		// Return the route array.
		return $la_route;
	}


	/**
	 * This function applies host options to a given host options array and context array.
	 *
	 * @param array $hostOptions The host options array to apply options to.
	 * @param array $context The context array to use for applying options.
	 * @return array|null The host options array with options applied, or null if the host did not match the route preferences.
	 */
	protected function applyHostOptions(array $hostOptions, array $context): ?array {
		$la_hostOptions = $hostOptions;

		// Apply the _host option if possible
		if (isset($this->options['_host'])) {
			// If the _host key is not set in the host options array and the _host option does not contain a '*', set the _host key in the host options array to the _host option.
			if (!isset($la_hostOptions['_host']) && !str_contains($this->options['_host'], '*')) {
				$la_hostOptions['_host'] = $this->options['_host'];
			}
			// If the _host key is not set in the host options array, set it to the _host key in the context array.
			if (!isset($la_hostOptions['_host'])) {
				$la_hostOptions['_host'] = $context['_host'];
			}

			// If the host does not match the route preferences, return null.
			if (!$this->hostMatches((string)$la_hostOptions['_host'])) {
				return null;
			}
		}

		// Check for properties that will cause an absolute url. Copy the other properties over.
		if (isset($la_hostOptions['_scheme']) || isset($la_hostOptions['_port']) || isset($la_hostOptions['_host'])) {
			$la_hostOptions += $context;

			// If the _scheme key is set in the host options array and the service name for the _scheme key in the host options array is the same as the _port key in the host options array, unset the _port key.
			if ($la_hostOptions['_scheme'] && getservbyname($la_hostOptions['_scheme'], 'tcp') === $la_hostOptions['_port']) {
				unset($la_hostOptions['_port']);
			}
		}

		// If the _base key is not set in the host options array and the _base key is set in the context array, set the _base key in the host options array to the _base key in the context array.
		if (!isset($la_hostOptions['_base']) && isset($context['_base'])) {
			$la_hostOptions['_base'] = $context['_base'];
		}


		// Return the host options array.
		return $la_hostOptions;
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
		// Initialize the URL.
		$ls_url = $url;

		// If the '_base' key is set in the parameters array, prepend it to the URL and unset it from the parameters array.
		if (isset($params['_base'])) {
			$ls_url = $params['_base'] . $ls_url;
			/** @noinspection PhpVariableNamingConventionInspection */
			unset($params['_base']);
		}

		// Replace any double slashes in the URL with a single slash.
		$ls_url = str_replace('//', '/', $ls_url);

		/**
		 * If the '_scheme', '_host', or '_port' key is set in the parameters array, construct the host and prepend it to the URL.
		 * If the '_port' key is set in the parameters array, append it to the host.
		 * The scheme defaults to 'https' if the '_scheme' key is not set in the parameters array.
		 */
		if (isset($params['_scheme']) || isset($params['_host']) || isset($params['_port'])) {
			$ls_host = $params['_host'];

			if (isset($params['_port'])) {
				$ls_host .= ':' . $params['_port'];
			}

			$ls_scheme = $params['_scheme'] ?? 'https';
			$ls_url = $ls_scheme . '://' . $ls_host . $ls_url;
		}

		// If the '_ext' key is set in the parameters array or the query array is not empty, remove any trailing slashes from the URL.
		if (!empty($params['_ext']) || !empty($query)) {
			$ls_url = rtrim($ls_url, '/');
		}

		// If the '_ext' key is set in the parameters array, append it to the URL with a '.'.
		if (!empty($params['_ext'])) {
			$ls_url .= '.' . $params['_ext'];
		}

		/**
		 * If the query array is not empty, append it to the URL with a '?'.
		 * The query array is converted to a string using http_build_query.
		 * Any trailing '?' is removed.
		 */
		if (!empty($query)) {
			$ls_url .= rtrim('?' . http_build_query($query), '?');
		}


		// Return the URL.
		return $ls_url;
	}


	/**
	 * This function handles passed parameters for a given URL array and key names array.
	 *
	 * @param array $url The URL array to handle parameters for.
	 * @param array $keyNames The key names array to use for handling parameters.
	 * @return array An array containing the modified URL array and the pass array.
	 */
	protected function handlePassedParameters(array $url, array $keyNames): array {
		// Copy the URL array.
		$la_url = $url;

		// Initialize the pass array.
		$la_pass = [];
		// For each key-value pair in the URL array...
		foreach ($la_url as $lx_key => $lx_value) {
			// If the key is a routed key, it's not different yet, so skip it.
			if (array_key_exists($lx_key, $keyNames)) {
				continue;
			}

			// Pull out passed args
			// If the key is not a known key, and it is set in the defaults and its value is the same as in the defaults, skip it.
			$lb_unknownKey = !in_array($lx_key, ['controller', 'action', 'plugin', '#', 'parts', 'pass', 'fullSlug', '_matchedRoute', '_ext']);
			if ($lb_unknownKey && isset($this->defaults[ $lx_key ]) && $this->defaults[ $lx_key ] === $lx_value) {
				continue;
			}

			// If the key is not a known key...
			if ($lb_unknownKey) {
				// Make sure the value isn't an array.
				$ls_value = is_array($lx_value) ? implode(',', $lx_value) : $lx_value;

				// If the key is numeric, add the value to the pass array.
				// Otherwise, dasherize the key and add it and its value to the pass array.
				if (is_numeric($lx_key)) {
					$la_pass[] = $ls_value;
				}
				else {
					$la_pass[ Inflector::dasherize($lx_key) ] = $ls_value;
				}

				// Remove the key-value pair from the URL array.
				unset($la_url[ $lx_key ]);
			}
		}


		// Return an array containing the modified URL array and the pass array.
		return [$la_url, $la_pass];
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
		// Copy the URL array.
		$la_url = $url;

		// Apply host options to the host options array. If the host does not match the route preferences, return null.
		$la_hostOptions = $this->applyHostOptions($hostOptions, $context);
		if (!$la_hostOptions) {
			return null;
		}

		// If the '?' key is set in the URL array, cast its value to an array and assign it to the query variable. Otherwise, assign an empty array to the query variable.
		$ls_query = !empty($la_url['?']) ? (array)$la_url['?'] : [];

		// Unset the '_host', '_scheme', '_port', '_base', and '?' keys from the URL array.
		unset($la_url['_host'], $la_url['_scheme'], $la_url['_port'], $la_url['_base'], $la_url['?']);

		// If the '_ext' key is set in the URL array, move its value into the host options array and unset it from the URL array.
		if (isset($la_url['_ext'])) {
			$la_hostOptions['_ext'] = $la_url['_ext'];
			unset($la_url['_ext']);
		}

		// Check the method first as it is special. If the method does not match, return null.
		if (!$this->_matchMethod($la_url)) {
			return null;
		}

		// Unset the '_method' and '[method]' keys from the URL array and the '_method' key from the defaults array.
		unset($la_url['_method'], $la_url['[method]'], $this->defaults['_method']);

		// If there are keys in the defaults array that do not exist in the URL array, return null.
		if (array_diff_key($this->defaults, $la_url) !== []) {
			return null;
		}

		// Flip the keys array and assign it to the key names variable.
		// If the intersection of the key names array and the URL array does not equal the key names array, return null.
		$la_keyNames = array_flip($this->keys);
		if (array_intersect_key($la_keyNames, $la_url) !== $la_keyNames) {
			return null;
		}

		// Handle passed parameters for the URL array and key names array and assign the result to the URL array and pass array.
		[$la_url, $la_pass] = $this->handlePassedParameters($la_url, $la_keyNames);

		// If this is not a greedy route and the pass array is not empty, return null.
		if (!$this->_greedy && !empty($la_pass)) {
			return null;
		}

		// If the options array is not empty, for each key-value pair in the options array...
		if (!empty($this->options)) {
			foreach ($this->options as $lx_key => $ls_pattern) {
				// If the key exists in the URL array and its value does not match the pattern, return null.
				if (isset($la_url[ $lx_key ]) && !preg_match('#^' . $ls_pattern . '$#u', (string)$la_url[ $lx_key ])) {
					return null;
				}
			}
		}

		// Add the host options array to the URL array.
		$la_url += $la_hostOptions;

		// If the 'controller', 'action', or 'slug' key is set in the key names array and is not set in the URL array, return null.
		if (
			(isset($la_keyNames['controller']) && !isset($la_url['controller'])) ||
			(isset($la_keyNames['action']) && !isset($la_url['action'])) ||
			(isset($la_keyNames['slug']) && !isset($la_url['slug']))
		) {
			return null;
		}


		// Build the URL string from the URL array, pass array, and query variable and return it.
		return $this->buildUrlString($la_url, $la_pass, $ls_query);
	}
}
