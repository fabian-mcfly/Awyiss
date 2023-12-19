<?php declare(strict_types=1);


namespace Awyiss\Routing\Route;


use Cake\Routing\Route\DashedRoute;
use Cake\Utility\Inflector;
use InvalidArgumentException;


/**
 * @todo comment this class, lol
 *
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
 * 	'controller' => 'MyController',
 * 	'action' => 'myAction',
 * 	'param1' => 'value1',
 * 	'param2' => 'value2',
 * ]
 * ```
 *
 */
class AwyissRoute extends DashedRoute {
	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function parse (string $as_url, string $as_method = ''): ?array {
		/**
		 * Stupid workaround since _writeRoute() unsets defaults;
		 */
		$la_defaults = $this->defaults;

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

		/*if (($this->defaults['prefix'] ?? NULL) != 'Backend') {
			if ( ! in_array('slug', $this->keys)) {
				$this->keys[] = 'slug';
			}

			if ( ! in_array('params', $this->keys)) {
				$this->keys[] = 'params';
			}
			//unset($this->defaults['action'], $this->defaults['controller']);
		}*/

		foreach ($la_route AS $lx_key => $lx_value) {
			if (is_numeric($lx_key)) {
				unset($la_route[ $lx_key ]);
			}
			if (empty($lx_value) && isset($la_defaults[ $lx_key ])) {
				$la_route[ $lx_key ] = $la_defaults[ $lx_key ];
			}
		}

		$la_route['pass'] = $la_route['parts'] = [];
		$la_route['slug'] = $la_route['fullSlug'] = '';

		// Assign defaults, set passed args to pass
		foreach ($la_defaults as $lx_key => $lx_value) {
			if (isset($la_route[ $lx_key ])) {
				continue;
			}
			$la_route[ $lx_key ] = $lx_value;
		}


		if ( ! empty($ls_ext)) {
			$la_route['_ext'] = $ls_ext;
		}

		$la_route = $this->setRouteArguments($la_route);

		//for now, we disable passing url parameters to method calls
		unset($la_route['pass']);

		return $la_route;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function match (array $aa_url, array $aa_context = []): ?string {
		if (empty($this->_compiledRoute)) {
			$this->compile();
		}

		$la_url = $this->_dasherize($aa_url);
		if ( ! $this->_inflectedDefaults) {
			$this->_inflectedDefaults = $this->_dasherize($this->defaults);
		}
		$la_context = $aa_context + ['params' => [], '_port' => NULL, '_scheme' => NULL, '_host' => NULL];
		if ( ! empty($this->options['persist']) && is_array($this->options['persist'])) {
			$la_url = $this->_persistParams($la_url, $this->_dasherize($la_context['params']));
		}
		unset($la_context['params']);
		$la_hostOptions = array_intersect_key($la_url, $la_context);

		return $this->_match($la_url, $la_hostOptions, $la_context);
	}


	/**
	 * Converts a matching route array into a URL string.
	 *
	 * Composes the string URL using the template
	 * used to create the route.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function buildUrl (array $aa_params, array $aa_pass = [], array $aa_query = []): string {
		$ls_pass = implode('/', array_map(function($ax_value, $ax_key) {
			if (is_numeric($ax_key) || $ax_value === FALSE) {
				return NULL;
			}

			return rawurlencode(Inflector::dasherize((string) $ax_key)) . ':' . rawurlencode(Inflector::dasherize((string) $ax_value));
		}, $aa_pass, array_keys($aa_pass)));
		$ls_url = $this->template;

		$la_search = $la_replace = [];
		foreach ($this->keys as $ls_key) {

			if ($ls_key == 'params') {
				continue;
			}

			if ( ! array_key_exists($ls_key, $aa_params)) {
				throw new InvalidArgumentException(sprintf('Missing required route key `%s`', $ls_key));
			}
			$la_search[] = '{' . $ls_key . '}';
			$la_replace[] = $aa_params[ $ls_key ];
		}

		if (str_contains($this->template, '**')) {
			array_push($la_search, '**', '%2F');
			array_push($la_replace, $ls_pass, '/');
		}
		elseif (str_contains($this->template, '*')) {
			$la_search[] = '*';
			if ( ! empty($aa_params['slug'])) {
				$la_replace[] = $aa_params['slug'] . (! empty($ls_pass) ? '/' . $ls_pass : NULL);
			}
			else {
				$la_replace[] = $ls_pass;
			}
		}

		$ls_url = str_replace($la_search, $la_replace, $ls_url);

		return $this->completeUrlScheme($ls_url, $aa_params, $aa_query);
	}


	/**
	 * @param array $aa_route
	 *
	 * @return array
	 */
	protected function setRouteArguments (array $aa_route): array {
		$la_route = $aa_route;

		if (isset($la_route['_args_'])) {
			/** @psalm-suppress PossiblyInvalidArgument */
			$la_pass = [];
			$lb_foundParams = FALSE;
			foreach ($this->_parseArgs($la_route['_args_'], $la_route) as $ls_part) {
				if (str_contains($ls_part, ':')) {
					$lb_foundParams = TRUE;
					[$ls_key, $ls_value] = explode(':', $ls_part);
					$ls_value = Inflector::underscore($ls_value);

					$ls_key = Inflector::variable(Inflector::underscore($ls_key));

					$la_route[ $ls_key ] = $ls_value;

					$la_pass[ $ls_key ] = $ls_value;
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

		// pass the name if set
		if (isset($this->options['_name'])) {
			$la_route['_name'] = $this->options['_name'];
		}

		//TODO: restructure 'pass' key route params
		/*if (isset($this->options['pass'])) {
		}*/

		$la_route['_route'] = $this;
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
			$la_route['action'] = Inflector::variable(Inflector::underscore($la_route['action']));
		}

		return $la_route;
	}


	/**
	 * @param array $aa_hostOptions
	 * @param array $aa_context
	 *
	 * @return NULL|array
	 */
	protected function applyHostOptions (array $aa_hostOptions, array $aa_context): ?array {
		$la_hostOptions = $aa_hostOptions;

		// Apply the _host option if possible
		if (isset($this->options['_host'])) {
			if ( ! isset($la_hostOptions['_host']) && ! str_contains($this->options['_host'], '*')) {
				$la_hostOptions['_host'] = $this->options['_host'];
			}
			if ( ! isset($la_hostOptions['_host'])) {
				$la_hostOptions['_host'] = $aa_context['_host'];
			}

			// The host did not match the route preferences
			if ( ! $this->hostMatches((string) $la_hostOptions['_host'])) {
				return NULL;
			}
		}

		// Check for properties that will cause an
		// absolute url. Copy the other properties over.
		if (isset($la_hostOptions['_scheme']) || isset($la_hostOptions['_port']) || isset($la_hostOptions['_host'])) {
			$la_hostOptions += $aa_context;

			if ($la_hostOptions['_scheme'] && getservbyname($la_hostOptions['_scheme'], 'tcp') === $la_hostOptions['_port']) {
				unset($la_hostOptions['_port']);
			}
		}

		// If no base is set, copy one in.
		if ( ! isset($la_hostOptions['_base']) && isset($aa_context['_base'])) {
			$la_hostOptions['_base'] = $aa_context['_base'];
		}

		return $la_hostOptions;
	}


	/**
	 * @param string $as_url
	 * @param array $aa_params
	 * @param array $aa_query
	 *
	 * @return array|string|string[]
	 */
	protected function completeUrlScheme (string $as_url, array $aa_params, array $aa_query): string|array {
		$ls_url = $as_url;

		// add base url if applicable.
		if (isset($aa_params['_base'])) {
			$ls_url = $aa_params['_base'] . $ls_url;
			unset($aa_params['_base']);
		}

		$ls_url = str_replace('//', '/', $ls_url);
		if (isset($aa_params['_scheme']) || isset($aa_params['_host']) || isset($aa_params['_port'])) {
			$ls_host = $aa_params['_host'];

			// append the port & scheme if they exist.
			if (isset($aa_params['_port'])) {
				$ls_host .= ':' . $aa_params['_port'];
			}
			$ls_scheme = $aa_params['_scheme'] ?? 'http';
			$ls_url = $ls_scheme . '://' . $ls_host . $ls_url;
		}
		if ( ! empty($aa_params['_ext']) || ! empty($aa_query)) {
			$ls_url = rtrim($ls_url, '/');
		}
		if ( ! empty($aa_params['_ext'])) {
			$ls_url .= '.' . $aa_params['_ext'];
		}
		if ( ! empty($aa_query)) {
			$ls_url .= rtrim('?' . http_build_query($aa_query), '?');
		}

		return $ls_url;
	}


	/**
	 * If this route uses pass option, and the passed elements are not set, rekey elements.
	 *
	 * If not all named arguments are present in the url, return NULL so that match() can return NULL as well.
	 *
	 * @param array $aa_url
	 * @param array $aa_keyNames
	 *
	 * @return null|array
	 */
	protected function rekeyParameters (array $aa_url, array &$aa_keyNames = []): ?array {
		$la_url = $aa_url;


		if (isset($this->options['pass'])) {
			foreach ($this->options['pass'] as $li_i => $ls_name) {
				if (isset($la_url[ $li_i ]) && ! isset($la_url[ $ls_name ])) {
					$la_url[ $ls_name ] = $la_url[ $li_i ];
					unset($la_url[ $li_i ]);
				}
			}
		}

		if (array_intersect_key($aa_keyNames, $la_url) !== $aa_keyNames) {
			return NULL;
		}

		/*if (($this->defaults['prefix'] ?? NULL) != 'Backend') {
			if ( ! in_array('slug', $this->keys)) {
				$this->keys[] = 'slug';
				$aa_keyNames['slug'] = 0;
			}

			if ( ! in_array('params', $this->keys)) {
				$this->keys[] = 'params';
				$aa_keyNames['params'] = 0;
			}
			//unset($this->defaults['controller'], $this->defaults['action']);
		}*/

		return $la_url;
	}


	/**
	 * @param array $aa_url
	 * @param array $aa_keyNames
	 *
	 * @return array
	 */
	protected function handlePassedParameters (array $aa_url, array $aa_keyNames): array {
		$la_url = $aa_url;
		$la_keyNames = $aa_keyNames;

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
					$la_pass[ Inflector::dasherize($lx_key) ] = is_array($lx_value) ? implode(',', $lx_value) : $lx_value;
				}
				unset($la_url[ $lx_key ]);
			}
		}

		return [$la_url, $la_pass];
	}


	/**
	 * @param array $aa_url
	 * @param array $aa_hostOptions
	 * @param array $aa_context
	 *
	 * @return null|string
	 */
	protected function _match (array $aa_url, array $aa_hostOptions, array $aa_context): ?string {
		$la_url = $aa_url;

		$la_hostOptions = $this->applyHostOptions($aa_hostOptions, $aa_context);
		if ( ! $la_hostOptions) {
			return NULL;
		}

		$ls_query = ! empty($la_url['?']) ? (array) $la_url['?'] : [];

		unset($la_url['_host'], $la_url['_scheme'], $la_url['_port'], $la_url['_base'], $la_url['?']);

		// Move extension into the hostOptions, so it's not part of reverse matches.
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

		// check that all the key names are in the url
		$la_keyNames = array_flip($this->keys);
		$la_url = $this->rekeyParameters($la_url, $la_keyNames);

		if ( ! $la_url) {
			return NULL;
		}

		[$la_url, $la_pass] = $this->handlePassedParameters($la_url, $la_keyNames);
		// if not a greedy route, no extra params are allowed.
		if ( ! $this->_greedy && ! empty($la_pass)) {
			return NULL;
		}

		// check patterns for routed params
		if ( ! empty($this->options)) {
			foreach ($this->options as $lx_key => $ls_pattern) {
				if (isset($la_url[ $lx_key ]) && ! preg_match('#^' . $ls_pattern . '$#u', (string) $la_url[ $lx_key ])) {
					return NULL;
				}
			}
		}

		$la_url += $la_hostOptions;

		// Ensure controller/action keys are not NULL.
		if ((isset($la_keyNames['controller']) && ! isset($la_url['controller'])) || (isset($la_keyNames['action']) && ! isset($la_url['action'])) || (isset($la_keyNames['slug']) && ! isset($la_url['slug']))) {
			return NULL;
		}

		return $this->buildUrl($la_url, $la_pass, $ls_query);
	}
}
