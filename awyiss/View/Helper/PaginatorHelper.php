<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\View\StringTemplate;
use Cake\Utility\Hash;
use Cake\View\View;


/**
 * @inheritDoc
 */
class PaginatorHelper extends \Cake\View\Helper\PaginatorHelper {
	/**
	 * Constructor. Overridden to merge passed args with URL aa_options.
	 *
	 * @param \Cake\View\View $view The View this helper is being attached to.
	 * @param array<string, mixed> $config Configuration settings for the helper.
	 */
	public function __construct (View $view, array $config = []) {
		parent::__construct($view, $config + ['templateClass' => StringTemplate::class,]);

		$la_query = $this->_View->getRequest()->getParam('parts');

		unset($la_query['page'], $la_query['limit'], $la_query['sort'], $la_query['direction']);

		$this->setConfig('aa_options.aa_url', array_merge($this->_View->getRequest()->getParam('parts', []), $la_query));
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $as_key
	 * @param $ax_title
	 * @param array $aa_options
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function sort (string $as_key, $ax_title = NULL, array $aa_options = []): string {
		$ls_title = $ax_title;
		if (empty($ls_title)) {
			$ls_title = _('::' . $as_key);
		}

		return parent::sort($as_key, $ls_title, $aa_options);
	}


	/**
	 * @inheritDoc
	 *
	 * @param array $aa_options
	 * @param null|string $as_modelName
	 * @param array $aa_url
	 *
	 * @return array
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function generateUrlParams (array $aa_options = [], ?string $as_modelName = NULL, array $aa_url = []): array {
		$la_params = $this->_View->getRequest()->getParam('parts');

		foreach ($aa_options as $lx_key => $lx_value) {
			$lx_key = str_replace('_', '-', $lx_key);
			if (gettype($lx_value) === 'string') {
				$lx_value = str_replace('_', '-', $lx_value);
			}

			$la_params[ $lx_key ] = $lx_value;
		}

		$la_params += ['page' => NULL, 'limit' => NULL, 'sort' => NULL, 'direction' => NULL];
		$la_params = Hash::filter($la_params, function($ax_value): bool {
			return $ax_value !== NULL;
		});

		//If the sorting-column and -direction equal their default value, set both to FALSE, so they won't be part of the generated URI
		if (isset($la_params['sortDefault'], $la_params['directionDefault'], $la_params['sort'], $la_params['direction']) && $la_params['sort'] === $la_params['sortDefault'] && strtolower($la_params['direction']) === strtolower($la_params['directionDefault'])) {
			$la_params['sort'] = $la_params['direction'] = FALSE;
		}

		//If the page parameter is empty or if it's page one, set it to FALSE, so it won't be part of the generated URI
		if ( ! empty($aa_options['page']) && $aa_options['page'] === 1) {
			$la_params['page'] = FALSE;
		}

		return $la_params;
	}


	/**
	 * Convenient function to render the pagination element (paginator/pagination.twig)
	 *
	 * If there's only one page to display, don't output the pagination.
	 *
	 * @return string
	 */
	public function render (): string {
		if (empty($this->param('pageCount')) || $this->param('pageCount') == 1) {
			return '';
		}

		//if (!$this->params) return '';

		return $this->_View->element('paginator/pagination');
	}


	/**
	 * When trying to output this helper, it'll automatically call the `render()`-method
	 *
	 * @return string
	 */
	public function __toString (): string {
		return $this->render();
	}
}