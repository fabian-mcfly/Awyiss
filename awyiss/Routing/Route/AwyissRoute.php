<?php declare(strict_types=1);


namespace Awyiss\Routing\Route;


use Cake\Utility\Inflector;


class AwyissRoute extends \Cake\Routing\Route\DashedRoute {
	/**
	 * Checks to see if the given URL can be parsed by this route.
	 *
	 * If the route can be parsed an array of parameters will be returned; if not
	 * false will be returned. String URLs are parsed if they match a routes regular expression.
	 *
	 * @param string $ls_url The URL to attempt to parse.
	 * @param string $as_method The HTTP method of the request being parsed.
	 *
	 * @return array|null An array of request parameters, or null on failure.
	 * @throws \InvalidArgumentException When method is not an empty string or in `VALID_METHODS` list.
	 */
	public function parse (string $as_url, string $as_method = ''): ?array {
		if ($as_method !== '') {
			$as_method = $this->normalizeAndValidateMethods($as_method);
		}
		$ls_compiledRoute = $this->compile();
		[$ls_url, $ls_ext] = $this->_parseExtension($as_url);

		if ( ! preg_match($ls_compiledRoute, urldecode($ls_url), $la_route)) {
			return NULL;
		}

		if (isset($this->defaults['_method']) && ! in_array($as_method, (array) $this->defaults['_method'], TRUE)) {
			return NULL;
		}

		if (($this->defaults['prefix'] ?? NULL) != 'Backend') {
			if ( ! in_array('slug', $this->keys)) {
				$this->keys[] = 'slug';
			}

			if ( ! in_array('params', $this->keys)) {
				$this->keys[] = 'params';
			}
			//unset($this->defaults['action'], $this->defaults['controller']);
		}

		array_shift($la_route);
		for ($li_i = 0, $li_keys = count($this->keys); $li_i <= $li_keys; $li_i++) {
			unset($la_route[ $li_i ]);
		}
		$la_route['pass'] = $la_route['parts'] = [];
		$la_route['slug'] = $la_route['fullSlug'] = '';

		// Assign defaults, set passed args to pass
		foreach ($this->defaults as $lx_key => $lx_value) {
			if (isset($la_route[ $lx_key ])) {
				continue;
			}

			$la_route[ $lx_key ] = $lx_value;
		}


		if (isset($la_route['_args_'])) {
			/** @psalm-suppress PossiblyInvalidArgument */
			$la_pass = [];
			$lb_foundParams = FALSE;
			foreach ($this->_parseArgs($la_route['_args_'], $la_route) as $ls_part) {
				if (strpos($ls_part, ':') !== FALSE) {
					$lb_foundParams = TRUE;
					[$ls_key, $ls_value] = explode(':', $ls_part);
					$la_route[ $ls_key ] = $ls_value;

					$la_pass[ $ls_key = Inflector::variable(str_replace('-', '_', $ls_key)) ] = $ls_value;
					$la_route['parts'][ $ls_key ] = $ls_value;
				}
				elseif ( ! $lb_foundParams) {
					$la_route['slug'] .= '/' . $ls_part;
					$la_route['parts'][] = $ls_part;
				}
			}
			$la_route['slug'] = ltrim($la_route['slug'], '/');

			$la_route['fullSlug'] = implode('/', array_map(function($ax_value, $ax_key) {
				if (is_numeric($ax_key)) {
					return $ax_value;
				}

				return $ax_key . ':' . $ax_value;
			}, $la_route['parts'], array_keys($la_route['parts'])));

			$la_route['pass'] = array_merge($la_route['pass'], $la_pass);
			unset($la_route['_args_']);
		}

		if ( ! empty($ls_ext)) {
			$la_route['_ext'] = $ls_ext;
		}

		// pass the name if set
		if (isset($this->options['_name'])) {
			$la_route['_name'] = $this->options['_name'];
		}

		//TODO: restructure 'pass' key route params
		if (isset($this->options['pass'])) {
		}

		$la_route['_matchedRoute'] = $this->template;
		if (count($this->middleware) > 0) {
			$la_route['_middleware'] = $this->middleware;
		}

		if ( ! empty($la_route['controller'])) {
			$la_route['controller'] = Inflector::camelize($la_route['controller'], '-');
		}
		if ( ! empty($la_route['plugin'])) {
			$la_route['plugin'] = $this->_camelizePlugin($la_route['plugin']);
		}
		if ( ! empty($la_route['action'])) {
			$la_route['action'] = Inflector::variable(str_replace('-', '_', $la_route['action']));
		}

		//for now, we disable passing url parameters to method calls
		unset($la_route['pass']);

		return $la_route;
	}


	/**
	 * Check if a URL array matches this route instance.
	 *
	 * If the URL matches the route parameters and settings, then
	 * return a generated string URL. If the URL doesn't match the route parameters, false will be returned.
	 * This method handles the reverse routing or conversion of URL arrays into string URLs.
	 *
	 * @param array $aa_url An array of parameters to check matching with.
	 * @param array $aa_context An array of the current request context.
	 *   Contains information such as the current host, scheme, port, base
	 *   directory and other url params.
	 *
	 * @return string|null Either a string URL for the parameters if they match or null.
	 */
	public function match (array $aa_url, array $aa_context = []): ?string {
		if (empty($this->_compiledRoute)) {
			$this->compile();
		}

		$la_url = $this->_dasherize($aa_url);
		if ( ! $this->_inflectedDefaults) {
			$this->_inflectedDefaults = TRUE;
			$this->defaults = $this->_dasherize($this->defaults);
		}
		$la_context = $aa_context + ['params' => [], '_port' => NULL, '_scheme' => NULL, '_host' => NULL];

		if ( ! empty($this->options['persist']) && is_array($this->options['persist'])) {
			$la_url = $this->_persistParams($la_url, $la_context['params']);
		}
		unset($la_context['params']);
		$la_hostOptions = array_intersect_key($la_url, $la_context);

		// Apply the _host option if possible
		if (isset($this->options['_host'])) {
			if ( ! isset($la_hostOptions['_host']) && strpos($this->options['_host'], '*') === FALSE) {
				$la_hostOptions['_host'] = $this->options['_host'];
			}
			if ( ! isset($la_hostOptions['_host'])) {
				$la_hostOptions['_host'] = $la_context['_host'];
			}

			// The host did not match the route preferences
			if ( ! $this->hostMatches((string) $la_hostOptions['_host'])) {
				return NULL;
			}
		}

		// Check for properties that will cause an
		// absolute url. Copy the other properties over.
		if (isset($la_hostOptions['_scheme']) || isset($la_hostOptions['_port']) || isset($la_hostOptions['_host'])) {
			$la_hostOptions += $la_context;

			if ($la_hostOptions['_scheme'] && getservbyname($la_hostOptions['_scheme'], 'tcp') === $la_hostOptions['_port']) {
				unset($la_hostOptions['_port']);
			}
		}

		// If no base is set, copy one in.
		if ( ! isset($la_hostOptions['_base']) && isset($la_context['_base'])) {
			$la_hostOptions['_base'] = $la_context['_base'];
		}

		$query = ! empty($la_url['?']) ? (array) $la_url['?'] : [];

		unset($la_url['_host'], $la_url['_scheme'], $la_url['_port'], $la_url['_base'], $la_url['?']);

		// Move extension into the hostOptions so its not part of
		// reverse matches.
		if (isset($la_url['_ext'])) {
			$la_hostOptions['_ext'] = $la_url['_ext'];
			unset($la_url['_ext']);
		}

		// Check the method first as it is special.
		if ( ! $this->_matchMethod($la_url)) {
			return NULL;
		}
		unset($la_url['_method'], $la_url['[method]'], $this->defaults['_method']);

		// Missing defaults is a fail.
		if (array_diff_key($this->defaults, $la_url) !== []) {
			return NULL;
		}

		/*// Defaults with different values are a fail.
		if (array_intersect_key($url, $defaults) != $defaults) {
			return NULL;
		}*/

		// If this route uses pass option, and the passed elements are
		// not set, rekey elements.
		if (isset($this->options['pass'])) {
			foreach ($this->options['pass'] as $i => $name) {
				if (isset($la_url[ $i ]) && ! isset($la_url[ $name ])) {
					$la_url[ $name ] = $la_url[ $i ];
					unset($la_url[ $i ]);
				}
			}
		}

		// check that all the key names are in the url
		$la_keyNames = array_flip($this->keys);
		if (array_intersect_key($la_keyNames, $la_url) !== $la_keyNames) {
			return NULL;
		}

		if (($this->defaults['prefix'] ?? NULL) != 'Backend') {
			if ( ! in_array('slug', $this->keys)) {
				$this->keys[] = 'slug';
				$la_keyNames['slug'] = 0;
			}

			if ( ! in_array('params', $this->keys)) {
				$this->keys[] = 'params';
				$la_keyNames['params'] = 0;
			}

			unset($this->defaults['controller'], $this->defaults['action']);
		}

		$la_pass = [];
		foreach ($la_url as $lx_key => $lx_value) {
			// If the key is a routed key, it's not different yet.
			if (array_key_exists($lx_key, $la_keyNames)) {
				continue;
			}

			// pull out passed args
			$lb_unknownKey = ! in_array($lx_key, ['controller', 'action', 'plugin', '#', 'parts', 'pass', 'fullSlug', '_matchedRoute', '_ext']);
			if ($lb_unknownKey && isset($this->defaults[ $lx_key ]) && $this->defaults[ $lx_key ] === $lx_value) {
				continue;
			}
			if ($lb_unknownKey) {
				if (is_numeric($lx_key)) {
					$la_pass[] = is_array($lx_value) ? implode(',', $lx_value) : $lx_value;
				}
				else {
					$la_pass[ $lx_key ] = is_array($lx_value) ? implode(',', $lx_value) : $lx_value;
				}
				unset($la_url[ $lx_key ]);
			}
		}

		// if not a greedy route, no extra params are allowed.
		if ( ! $this->_greedy && ! empty($la_pass)) {
			return NULL;
		}

		// check patterns for routed params
		if ( ! empty($this->options)) {
			foreach ($this->options as $key => $pattern) {
				if (isset($la_url[ $key ]) && ! preg_match('#^' . $pattern . '$#u', (string) $la_url[ $key ])) {
					return NULL;
				}
			}
		}

		$la_url += $la_hostOptions;

		// Ensure controller/action keys are not null.
		if ((isset($la_keyNames['controller']) && ! isset($la_url['controller'])) || (isset($la_keyNames['action']) && ! isset($la_url['action'])) || (isset($la_keyNames['slug']) && ! isset($la_url['slug']))) {
			return NULL;
		}

		return $this->_writeUrl($la_url, $la_pass, $query);
	}


	/**
	 * Converts a matching route array into a URL string.
	 *
	 * Composes the string URL using the template
	 * used to create the route.
	 *
	 * @param array $aa_params The params to convert to a string url
	 * @param array $pass The additional passed arguments
	 * @param array $aa_query An array of parameters
	 *
	 * @return string Composed route string.
	 */
	protected function _writeUrl (array $aa_params, array $aa_pass = [], array $aa_query = []): string {
		$ls_pass = implode('/', array_map(function($ax_value, $ax_key) {
			if (is_numeric($ax_key) || $ax_value === FALSE) {
				return NULL;
			}

			return rawurlencode((string) $ax_key) . ':' . rawurlencode((string) $ax_value);
		}, $aa_pass, array_keys($aa_pass)));
		$ls_out = $this->template;

		$la_search = $la_replace = [];
		foreach ($this->keys as $ls_key) {
			if ($ls_key == 'params') {
				continue;
			}

			if ( ! array_key_exists($ls_key, $aa_params)) {
				throw new \InvalidArgumentException("Missing required route key `{$ls_key}`");
			}
			$string = $aa_params[ $ls_key ];
			if ($this->braceKeys) {
				$la_search[] = "{{$ls_key}}";
			}
			else {
				$la_search[] = ':' . $ls_key;
			}
			$la_replace[] = $string;
		}

		if (strpos($this->template, '**') !== FALSE) {
			array_push($la_search, '**', '%2F');
			array_push($la_replace, $ls_pass, '/');
		}
		elseif (strpos($this->template, '*') !== FALSE) {
			$la_search[] = '*';
			if ( ! empty($aa_params['slug'])) {
				$la_replace[] = $aa_params['slug'] . (! empty($aa_pass) ? '/' . $ls_pass : NULL);
			}
			else {
				$la_replace[] = $ls_pass;
			}
		}


		$ls_out = str_replace($la_search, $la_replace, $ls_out);

		// add base url if applicable.
		if (isset($aa_params['_base'])) {
			$ls_out = $aa_params['_base'] . $ls_out;
			unset($aa_params['_base']);
		}

		$ls_out = str_replace('//', '/', $ls_out);
		if (isset($aa_params['_scheme']) || isset($aa_params['_host']) || isset($aa_params['_port'])) {
			$host = $aa_params['_host'];

			// append the port & scheme if they exists.
			if (isset($aa_params['_port'])) {
				$host .= ':' . $aa_params['_port'];
			}
			$scheme = $aa_params['_scheme'] ?? 'http';
			$ls_out = "{$scheme}://{$host}{$ls_out}";
		}
		if ( ! empty($aa_params['_ext']) || ! empty($aa_query)) {
			$ls_out = rtrim($ls_out, '/');
		}
		if ( ! empty($aa_params['_ext'])) {
			$ls_out .= '.' . $aa_params['_ext'];
		}
		if ( ! empty($aa_query)) {
			$ls_out .= rtrim('?' . http_build_query($aa_query), '?');
		}

		return $ls_out;
	}
}