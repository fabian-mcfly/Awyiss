<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\View\Helper\UrlHelper as BaseUrlHelper;


/**
 * @inheritDoc
 */
class UrlHelper extends BaseUrlHelper {
	/**
	 * @inheritDoc
	 * @param array|string|null $url
	 * @param array $options
	 * @return string
	 */
	public function build(array|string|null $url = null, array $options = []): string {
		$lx_url = $url;
		$la_options = $options + [
			'fullBase' => false,
			'escape' => true,
			'withParams' => null,
			'withoutParams' => null,
		];

		if (is_null($lx_url)) {
			$lx_url = [];
		}

		if (is_array($lx_url)) {
			$la_url = [];
			foreach ($lx_url as $lx_key => $lx_value) {
				if (is_numeric($lx_key) || str_starts_with($lx_key, '_')) {
					$la_url[ $lx_key ] = $lx_value;
					continue;
				}

				$la_url[ Inflector::dasherize($lx_key) ] = $lx_value;
			}

			if (!empty($la_options['withParams']) || !empty($la_options['withoutParams'])) {
				$la_url += $this->buildParameters($la_options);
			}

			if (
				(
					!array_key_exists('_name', $la_url) ||
					$la_url['_name'] === null
				) &&
				empty($la_url['plugin'])
			) {
				unset($la_url['_name']);
			}

			$ls_url = Router::url($la_url, $la_options['fullBase']);
		}
		else {
			$ls_url = Router::url($lx_url, $la_options['fullBase']);
		}

		if ($la_options['escape']) {
			return h($ls_url);
		}

		return $ls_url;
	}


	/**
	 * @param array $options
	 * @return array
	 */
	protected function buildParameters(array $options): array {
		$la_options = $options;
		$la_params = [];

		$la_currentParts = [];
		foreach (($this->_View->getRequest()->getParam('parts') ?: []) as $lx_key => $lx_value) {
			if (is_numeric($lx_key)) {
				continue;
			}

			$la_currentParts[ Inflector::dasherize($lx_key) ] = $lx_value;
		}

		$this->addParams($la_params, $la_options['withParams'], $la_currentParts);

		$this->removeParams($la_params, $la_options['withoutParams'], $la_currentParts);

		return $la_params;
	}


	/**
	 * Add all defined parameters to the URL.
	 * If any of the passed values is `true`, all parameters will be added.
	 *
	 * If the `$withParams` parameter is empty, no parameters will be added.
	 *
	 * @param array $params
	 * @param mixed $withParams
	 * @param array $currentParts
	 * @return void
	 */
	protected function addParams(array &$params, mixed $withParams, array $currentParts): void {
		$la_withParams = $withParams ?? [];

		if (!$la_withParams) {
			return;
		}

		if (!is_array($la_withParams)) {
			$la_withParams = [$la_withParams];
		}

		$la_withParams = Hash::flatten($la_withParams);

		if (in_array(true, $la_withParams, true)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$params = $currentParts;

			return;
		}

		foreach ($la_withParams as $ls_param) {
			if (array_key_exists($ls_param, $currentParts)) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$params[ $ls_param ] = $currentParts[ $ls_param ];
			}
		}
	}


	/**
	 * Remove all defined parameters from the URL.
	 * If any of the passed values is `true`, all parameters will be removed.
	 *
	 * If the `$withoutParams` parameter is empty, no parameters will be removed.
	 *
	 * @param array $params
	 * @param mixed $withoutParams
	 * @param array $currentParts
	 * @return void
	 */
	protected function removeParams(array &$params, mixed $withoutParams, array $currentParts): void {
		$la_withoutParams = $withoutParams ?? [];

		if (!$la_withoutParams) {
			return;
		}

		if (!is_array($la_withoutParams)) {
			$la_withoutParams = [$la_withoutParams];
		}

		$la_withoutParams = Hash::flatten($la_withoutParams);

		if (in_array(true, $la_withoutParams, true)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$params = [];
		}
		else {
			if (!$params) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$params = array_diff_key($currentParts, array_flip($la_withoutParams));
			}
		}

		if (!$params) {
			/**
			 * This is a workaround for how Router::url() builds a URL:
			 * If the first parameter is empty, it'll use all existing values.
			 * This results in parameters in the URL even though we explicitly
			 * said we didn't want them.
			 *
			 * The router will remove a parameter if its value is `false`,
			 * so pass at least one parameter with `false`.
			 *
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$params[ reset($la_withoutParams) ] = false;
		}
	}
}
