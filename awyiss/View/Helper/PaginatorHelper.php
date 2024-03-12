<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\View\StringTemplate;
use Cake\Utility\Hash;
use Cake\View\Helper\PaginatorHelper as BasePaginatorHelper;
use Cake\View\View;


/**
 * @inheritDoc
 */
class PaginatorHelper extends BasePaginatorHelper {
	/**
	 * Constructor. Overridden to merge passed args with URL aa_options.
	 *
	 * @param View $ao_view The View this helper is being attached to.
	 * @param array<string, mixed> $aa_config Configuration settings for the helper.
	 */
	public function __construct(View $ao_view, array $aa_config = []) {
		parent::__construct($ao_view, $aa_config + ['templateClass' => StringTemplate::class,]);

		$la_query = $this->_View->getRequest()->getParam('parts', []);

		$la_query['page'] = $la_query['limit'] = $la_query['sort'] = $la_query['direction'] = false;

		$this->setConfig('options.url', array_merge($this->_View->getRequest()->getParam('pass', []), $la_query));
	}


	/**
	 * @inheritDoc
	 * @param string $as_key
	 * @param array|string|null $ax_title
	 * @param array $aa_options
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function sort(string $as_key, array|string|null $ax_title = null, array $aa_options = []): string {
		$ls_title = $ax_title;
		if (empty($ls_title)) {
			$ls_title = __($as_key);
		}


		return parent::sort($as_key, $ls_title, $aa_options);
	}


	/**
	 * @inheritDoc
	 * @param array $aa_options
	 * @param array $aa_url
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function generateUrlParams(array $aa_options = [], array $aa_url = []): array {
		$la_params = $this->_View->getRequest()->getParam('parts');

		foreach ($aa_options as $lx_key => $lx_value) {
			$lx_key = str_replace('_', '-', $lx_key);
			if (gettype($lx_value) === 'string') {
				$lx_value = str_replace('_', '-', $lx_value);
			}

			$la_params[ $lx_key ] = $lx_value;
		}

		$la_params += ['page' => null, 'limit' => null, 'sort' => null, 'direction' => null];
		$la_params = Hash::filter($la_params, function ($ax_value): bool {
			return $ax_value !== null;
		});

		//If the sorting-column and -direction equal their default value, set both to false, so they won't be part of the generated URI
		if (
			isset($la_params['sortDefault'], $la_params['directionDefault'], $la_params['sort'], $la_params['direction']) &&
			$la_params['sort'] === $la_params['sortDefault'] &&
			strtolower($la_params['direction']) === strtolower($la_params['directionDefault'])
		) {
			$la_params['sort'] = $la_params['direction'] = false;
		}

		//If the page parameter is empty or if it's page one, set it to false, so it won't be part of the generated URI
		if (!empty($aa_options['page']) && $aa_options['page'] === 1) {
			$la_params['page'] = false;
		}


		return $la_params;
	}


	/**
	 * @param array $aa_limits
	 * @param int|null $ai_default
	 * @param array $options
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function limitControl(array $aa_limits = [], ?int $ai_default = null, array $aa_options = []): string {
		$la_limits = $aa_limits ?: [
			'20' => '20',
			'50' => '50',
			'100' => '100',
		];

		$la_limits += [$this->param('perPage') => $this->param('perPage')];

		natsort($la_limits);

		$li_defaultPerPage = $ai_default ?? $this->paginated()->perPage();

		$ls_output = $this->Form->create(null, ['url' => ['action' => 'userConfiguration']]);
		$ls_output .= $this->Form->hidden('identifier', ['val' => 'paginate.limit']);
		$ls_output .= $this->Form->control(
			'value',
			$aa_options + [
				'default' => $li_defaultPerPage,
				'empty' => false,
				'label' => __('limit_per_page'),
				'options' => $la_limits,
				'onChange' => 'this.form.submit()',
				'type' => 'select',
				'value' => $this->param('perPage'),
			]
		);
		$ls_output .= $this->Form->end();


		return $ls_output;
	}


	/**
	 * Convenient function to render the pagination element (paginator/pagination.twig)
	 *
	 * If there's only one page to display, don't output the pagination.
	 *
	 * @return string
	 */
	public function render(): string {
		if (empty($this->param('pageCount')) || $this->param('pageCount') == 1) {
			return '';
		}


		return $this->_View->element('paginator/pagination');
	}


	/**
	 * When trying to output this helper, it'll automatically call the `render()`-method
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->render();
	}
}
