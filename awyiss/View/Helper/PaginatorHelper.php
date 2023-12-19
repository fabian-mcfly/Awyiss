<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\Utility\Hash;


class PaginatorHelper extends \Cake\View\Helper\PaginatorHelper {
	public function __construct (\Cake\View\View $view, array $config = []) {
		parent::__construct($view, $config + ['templateClass' => \Awyiss\View\StringTemplate::class,]);

		$la_query = $this->_View->getRequest()->getParam('parts');

		unset($la_query['page'], $la_query['limit'], $la_query['sort'], $la_query['direction']);

		$this->setConfig('options.url', array_merge($this->_View->getRequest()->getParam('parts', []), $la_query));
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function sort(string $as_key, $ax_title = null, array $aa_options = []): string {
		$ls_title = $ax_title;
		if (empty($ls_title)) {
			$ls_title = _('::' . $as_key);
		}

		return parent::sort($as_key, $ls_title, $aa_options);
	}


	/**
	 * Merges passed URL options with current pagination state to generate a pagination URL.
	 *
	 * @param array $options Pagination/URL options array
	 * @param string|null $model Which model to paginate on
	 * @param array $url URL.
	 *
	 * @return array An array of URL parameters
	 */
	public function generateUrlParams (array $options = [], ?string $model = NULL, array $url = []): array {
		$la_params = $this->_View->getRequest()->getParam('parts');
		foreach ($options as $lx_key => $lx_value) {
			$lx_key = str_replace('_', '-', $lx_key);
			if (gettype($lx_value) === 'string') {
				$lx_value = str_replace('_', '-', $lx_value);
			}

			$la_params[ $lx_key ] = $lx_value;
		}

		$la_params += ['page' => NULL, 'limit' => NULL, 'sort' => NULL, 'direction' => NULL];
		$la_params = Hash::filter($la_params, function ($var): bool {
			return $var !== NULL;
		});

		if (isset($paging['sortDefault'], $paging['directionDefault'], $la_params['sort'], $la_params['direction']) && $la_params['sort'] === $paging['sortDefault'] && strtolower($la_params['direction']) === strtolower($paging['directionDefault'])) {
			$la_params['sort'] = $la_params['direction'] = FALSE;
		}

		if ( ! empty($options['page']) && $options['page'] === 1) {
			$la_params['page'] = FALSE;
		}

		return $la_params;
	}


	public function render (): string {
		if (empty($this->param('pageCount')) || $this->param('pageCount') == 1) return '';
		//if (!$this->params) return '';

		return $this->_View->element('paginator/pagination');
	}


	public function __toString (): string {
		return $this->render();
	}
}