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
	 * An identifier to use all current parameters or none at all
	 */
	final public const PARAMS_ALL = '_all';


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
		];

		$la_params = [];
		if (!is_string($lx_url) && (!empty($la_options['withParams']) || !empty($la_options['withoutParams']))) {
			$la_params = $this->buildParameters($la_options, $la_params);
		}

		if (is_null($lx_url)) {
			$lx_url = [];
		}

		if (is_array($lx_url)) {
			$lx_url += $la_params;

			if (empty($lx_url['_name'])) {
				unset($lx_url['_name']);
			}
		}

		$ls_url = Router::url($lx_url, $la_options['fullBase']);

		if ($la_options['escape']) {
			return h($ls_url);
		}

		return $ls_url;
	}


	/**
	 * @param array $options
	 * @param mixed $params
	 * @return array
	 */
	protected function buildParameters(array $options, mixed $params): array {
		$la_options = $options;
		$la_params = $params;

		$la_currentParts = [];
		foreach (($this->_View->getRequest()->getParam('parts') ?: []) as $lx_key => $lx_value) {
			if (is_numeric($lx_key)) {
				continue;
			}

			$la_currentParts[ Inflector::dasherize($lx_key) ] = $lx_value;
		}

		$la_withParams = $la_options['withParams'] ?? [];
		if ($la_withParams) {
			if (!is_array($la_withParams)) {
				$la_withParams = [$la_withParams];
			}

			$la_withParams = Hash::flatten($la_withParams);

			if (in_array(static::PARAMS_ALL, $la_withParams)) {
				$la_params = $la_currentParts;
			}
			else {
				foreach ($la_withParams as $ls_param) {
					if (array_key_exists($ls_param, $la_currentParts)) {
						$la_params[ $ls_param ] = $la_currentParts[ $ls_param ];
					}
				}
			}
		}

		$la_withoutParams = $la_options['withoutParams'] ?? [];
		if ($la_withoutParams) {
			if (!is_array($la_withoutParams)) {
				$la_withoutParams = [$la_withoutParams];
			}

			$la_withoutParams = Hash::flatten($la_withoutParams);

			if (in_array(static::PARAMS_ALL, $la_withoutParams)) {
				$la_params = [];
			}
			else {
				if (empty($la_params)) {
					$la_params = $la_currentParts;

					foreach ($la_withoutParams as $ls_param) {
						unset($la_params[ $ls_param ]);
					}
				}
			}
		}

		if (empty($la_params) && !empty($la_withoutParams)) {
			/**
			 * This is a workaround for how Router::url() builds a URL
			 * If the first paramter is empty, it'll use all existing values.
			 * This results in parameters in the URL even though we explicitely
			 * that said we didn't want them.
			 */
			$la_params[ reset($la_withoutParams) ] = false;
		}


		return $la_params;
	}
}
