<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\View\StringTemplate;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper\PaginatorHelper as BasePaginatorHelper;
use Cake\View\StringTemplate as BaseStringTemplate;
use Cake\View\View;


/**
 * @inheritDoc
 */
class PaginatorHelper extends BasePaginatorHelper {
	/**
	 * Constructor. Overridden to merge passed args with URL options.
	 *
	 * @param View $ao_view The View this helper is being attached to.
	 * @param array<string, mixed> $aa_config Configuration settings for the helper.
	 */
	public function __construct(View $ao_view, array $aa_config = []) {
		parent::__construct($ao_view, $aa_config + ['templateClass' => StringTemplate::class,]);

		$la_query = $this->_View->getRequest()->getParam('parts', []);

		if (!empty($la_query['sort']) && !$this->getConfig('params.sort')) {
			$this->setConfig('params.sort', $la_query['sort']);
		}

		$la_query['page'] = $la_query['limit'] = $la_query['sort'] = $la_query['direction'] = false;

		$this->setConfig('options.url', array_merge($this->_View->getRequest()->getParam('pass', []), $la_query));
	}


	/**
	 * @inheritDoc
	 * @param array $aa_options
	 * @return string|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function meta(array $aa_options = []): ?string {
		if (!isset($this->paginated)) {
			return null;
		}

		return parent::meta($aa_options);
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
		$la_options = $aa_options;
		$la_options += ['url' => [], 'escape' => true];

		$ls_title = $ax_title;
		if (empty($ls_title)) {
			$ls_title = __($as_key);
		}

		// If the key is the current sort key, set the default direction to desc
		if ($as_key === $this->currentSortKey()) {
			$la_options['direction'] = 'desc';
		}

		$ls_url = $la_options['url'];
		unset($la_options['url']);

		$ls_defaultDir = isset($la_options['direction']) ? strtolower($la_options['direction']) : 'asc';
		unset($la_options['direction']);

		$ls_sortKey = (string)$this->param('sort');
		$ls_alias = $this->param('alias');
		[$ls_table, $ls_field] = explode('.', $as_key . '.');
		if (!$ls_field) {
			$ls_field = $ls_table;
			$ls_table = $ls_alias;
		}
		$lb_isSorted = ($ls_sortKey === $ls_table . '.' . $ls_field || $ls_sortKey === $ls_alias . '.' . $as_key || $ls_table . '.' . $ls_field === $ls_alias . '.' . $ls_sortKey);

		$ls_template = 'sort';
		$ls_dir = $ls_defaultDir;
		if ($lb_isSorted) {
			$ls_dir = $this->sortDir() === 'asc' ? 'desc' : 'asc';
			$ls_template = $ls_dir === 'asc' ? 'sortDesc' : 'sortAsc';
		}

		$la_paging = [
			'sort' => $as_key,
			'direction' => $ls_dir,
			'page' => 1,
		];

		$la_vars = [
			'text' => $la_options['escape'] ? h($ls_title) : $ls_title,
			'identifier' => Inflector::camelize($as_key),
			'url' => $this->generateUrl($la_paging, $ls_url),
		];

		return $this->templater()->format($ls_template, $la_vars);
	}


	/**
	 * Returns the current sort key
	 *
	 * @return string|null
	 */
	public function currentSortKey(): ?string {
		$ls_sortKey = $this->param('sort');
		if (!$ls_sortKey) {
			$ls_sortKey = $this->param('sortDefault');
		}

		if (!$ls_sortKey) {
			return null;
		}

		// Return the original sort key if its aliased
		$la_aliasFields = $this->getConfig('aliasedFields');
		if (isset($la_aliasFields[ $ls_sortKey ])) {
			$ls_sortKey = $la_aliasFields[ $ls_sortKey ];
		}

		return $ls_sortKey;
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
				'label' => __d('pagination', 'limit_per_page'),
				'options' => $la_limits,
				'type' => 'select',
				'value' => $this->param('perPage'),
			]
		);
		$ls_output .= $this->Form->end();


		return $ls_output;
	}


	/**
	 * Convenient function to render the pagination element (paginator/pagination.twig)
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
	 * Formats a number for the paginator number output.
	 *
	 * @param \Cake\View\StringTemplate $templater StringTemplate instance.
	 * @param array<string, mixed> $options Options from the numbers() method.
	 * @return string
	 */
	protected function _formatNumber(StringTemplate|BaseStringTemplate $templater, array $options): string {
		$la_vars = [
			'page' => __d('pagination', 'page'),
			'text' => $options['text'],
			'url' => $this->generateUrl(['page' => $options['page']], $options['url']),
		];

		return $templater->format('number', $la_vars);
	}


	/**
	 * Generates the numbers for the paginator numbers() method.
	 *
	 * @param \Cake\View\StringTemplate $templater StringTemplate instance.
	 * @param array<string, mixed> $params Params from the numbers() method.
	 * @param array<string, mixed> $options Options from the numbers() method.
	 * @return string Markup output.
	 */
	protected function _numbers(StringTemplate|BaseStringTemplate $templater, array $params, array $options): string {
		$ls_out = '';
		$ls_out .= $options['before'];

		for ($li_i = 1; $li_i <= $params['pageCount']; $li_i++) {
			if ($li_i === $params['currentPage']) {
				$ls_out .= $templater->format('current', [
					'page' => __d('pagination', 'page'),
					'text' => $this->Number->format($params['currentPage']),
					'url' => $this->generateUrl(['page' => $li_i], $options['url']),
				]);
			}
			else {
				$la_vars = [
					'page' => __d('pagination', 'page'),
					'text' => $this->Number->format($li_i),
					'url' => $this->generateUrl(['page' => $li_i], $options['url']),
				];
				$ls_out .= $templater->format('number', $la_vars);
			}
		}
		$ls_out .= $options['after'];

		return $ls_out;
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
