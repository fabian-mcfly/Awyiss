<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\Routing\Router;
use Cake\Utility\Hash;


/**
 * @inheritDoc
 */
class UrlHelper extends \Cake\View\Helper\UrlHelper {
	final public const PARAMS_ALL = '_all';
	//final public const PARAMS_PAGINATION = ['page', 'limit', 'sort', 'direction'];
	//final public const PARAMS_SORT = ['limit', 'sort', 'direction'];


	/**
	 * @inheritDoc
	 *
	 * @param $ax_url
	 * @param array $aa_options
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function build ($ax_url = NULL, array $aa_options = []): string {
		$lx_url = $ax_url;
		$la_options = $aa_options + [
				'fullBase' => FALSE,
				'escape' => TRUE,
			];

		$la_params = [];
		if ( ! is_string($lx_url) && ( ! empty($la_options['withParams']) || ! empty($la_options['withoutParams']))) {
			$la_params = $this->buildParameters($la_options, $la_params);
		}

		if (is_null($lx_url)) {
			$lx_url = [];
		}

		if (is_array($lx_url)) {
			$lx_url += $la_params;

			if (isset($lx_url['controller']) && ! array_key_exists('_name', $lx_url) && defined('IS_BACKEND') && IS_BACKEND) {
				$lx_url['_name'] = 'backend';

				if ( ! array_key_exists('action', $lx_url)) {
					$lx_url['action'] = 'overview';
				}
			}

			if (empty($lx_url['_name'])) {
				unset($lx_url['_name']);
			}
		}

		$ls_url = Router::url($lx_url, $la_options['fullBase']);

		if ($la_options['escape']) {
			$ls_url = h($ls_url);
		}

		return $ls_url;
	}


	/**
	 * @param array $aa_options
	 * @param mixed $aa_params
	 *
	 * @return array
	 */
	protected function buildParameters (array $aa_options, mixed $aa_params): array {
		$la_options = $aa_options;
		$la_params = $aa_params;

		$la_currentParts = $this->_View->getRequest()->getParam('parts');

		if ($la_withParams = ($la_options['withParams'] ?? [])) {
			if ( ! is_array($la_withParams)) {
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

		if ($la_withoutParams = ($la_options['withoutParams'] ?? [])) {
			if ( ! is_array($la_withoutParams)) {
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

		if (empty($la_params) && ! empty($la_withoutParams)) {
			/**
			 * This is a workaround for how Router::url() builds a URL
			 * If the first paramter is empty, it'll use all existing values.
			 * This results in parameters in the URL even though we explicitely
			 * that said we didn't want them.
			 */
			$la_params[ reset($la_withoutParams) ] = FALSE;
		}

		return $la_params;
	}
}