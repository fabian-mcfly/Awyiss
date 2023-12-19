<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace Awyiss\View\Helper;


use Cake\Routing\Router;

class UrlHelper extends \Cake\View\Helper\UrlHelper {
	public const PARAMS_ALL = '_all';
	public const PARAMS_PAGINATION = ['page', 'limit', 'sort', 'direction'];
	public const PARAMS_SORT = ['limit', 'sort', 'direction'];

	/**
	 * Returns a URL based on provided parameters.
	 *
	 * ### Options:
	 *
	 * - `escape`: If false, the URL will be returned unescaped, do only use if it is manually
	 *    escaped afterwards before being displayed.
	 * - `fullBase`: If true, the full base URL will be prepended to the result
	 *
	 * @param string|array|null $ax_url Either a relative string URL like `/products/view/23` or
	 *    an array of URL parameters. Using an array for URLs will allow you to leverage
	 *    the reverse routing features of CakePHP.
	 * @param array $aa_options Array of options.
	 *
	 * @return string Full translated URL with base path.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function build ($ax_url = NULL, array $aa_options = []): string {
		$lx_url = $ax_url;
		$la_options = $aa_options + [
			'fullBase' => FALSE,
			'escape' => TRUE,
		];

		$la_params = [];
		if (!is_string($lx_url) && (!empty($la_options['withParams']) || !empty($la_options['withoutParams']))) {
			$la_currentParts = $this->_View->getRequest()->getParam('parts');

			if ($la_withParams = ($la_options['withParams'] ?? [])) {
				if ( ! is_array($la_withParams)) {
					$la_withParams = [$la_withParams];
				}

				$la_withParams = \Cake\Utility\Hash::flatten($la_withParams);

				if (in_array(static::PARAMS_ALL, $la_withParams)) {
					$la_params = $la_currentParts;
				}
				else {
					foreach ($la_withParams AS $ls_param) {
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

				$la_withoutParams = \Cake\Utility\Hash::flatten($la_withoutParams);

				if (in_array(static::PARAMS_ALL, $la_withoutParams)) {
					$la_params = [];
				}
				else {
					if (empty($la_params)) {
						$la_params = $la_currentParts;

						foreach ($la_withoutParams AS $ls_param) {
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
				$la_params[ reset($la_withoutParams) ] = FALSE;
			}
		}

		if (is_null($lx_url)) $lx_url = [];
		if (is_array($lx_url)) {
			$lx_url += $la_params;

			if (isset($lx_url['controller']) && !array_key_exists('_name', $lx_url)
				&& defined('IS_BACKEND') && IS_BACKEND
			) {
				$lx_url['_name'] = 'backend';

				if (!array_key_exists('action', $lx_url)) {
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
}