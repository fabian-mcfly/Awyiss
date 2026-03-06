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
	 * @param array{fullBase?: bool, escape?: bool, withParams?: mixed, withoutParams?: mixed} $options
	 * @return string
	 */
	public function build(array|string|null $url = null, array $options = []): string {
		$options += [
			'fullBase' => false,
			'escape' => true,
			'withParams' => null,
			'withoutParams' => null,
		];

		if (is_null($url)) {
			$url = [];
		}

		if (is_array($url)) {
			$cleanUrlParts = [];
			foreach ($url as $key => $value) {
				if (is_numeric($key) || str_starts_with($key, '_')) {
					$cleanUrlParts[ $key ] = $value;
					continue;
				}

				$cleanUrlParts[ Inflector::dasherize($key) ] = $value;
			}

			if (!empty($options['withParams']) || !empty($options['withoutParams'])) {
				$cleanUrlParts += $this->buildParameters($options);
			}

			if (
				(
					!array_key_exists('_name', $cleanUrlParts) ||
					$cleanUrlParts['_name'] === null
				) &&
				empty($cleanUrlParts['plugin'])
			) {
				unset($cleanUrlParts['_name']);
			}

			$url = Router::url($cleanUrlParts, $options['fullBase']);
		}
		else {
			$url = Router::url($url, $options['fullBase']);
		}

		if ($options['escape']) {
			return h($url);
		}

		return $url;
	}


	/**
	 * @param array $options
	 * @return array
	 */
	protected function buildParameters(array $options): array {
		$params = [];

		$currentParts = [];
		foreach (($this->_View->getRequest()->getParam('parts') ?: []) as $key => $value) {
			if (is_numeric($key)) {
				continue;
			}

			$currentParts[ Inflector::dasherize($key) ] = $value;
		}

		$this->addParams($params, $options['withParams'], $currentParts);

		$this->removeParams($params, $options['withoutParams'], $currentParts);

		return $params;
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
		$withParams ??= [];

		if (!$withParams) {
			return;
		}

		if (!is_array($withParams)) {
			$withParams = [$withParams];
		}

		$withParams = Hash::flatten($withParams);

		if (in_array(true, $withParams, true)) {
			$params = $currentParts;

			return;
		}

		foreach ($withParams as $param) {
			if (array_key_exists($param, $currentParts)) {
				$params[ $param ] = $currentParts[ $param ];
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
		$withoutParams ??= [];

		if (!$withoutParams) {
			return;
		}

		if (!is_array($withoutParams)) {
			$withoutParams = [$withoutParams];
		}

		$withoutParams = Hash::flatten($withoutParams);

		if (in_array(true, $withoutParams, true)) {
			$params = [];
		}
		else {
			if (!$params) {
				$params = array_diff_key($currentParts, array_flip($withoutParams));
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
			 */
			$params[ reset($withoutParams) ] = false;
		}
	}
}
