<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Utility\Inflector;
use Awyiss\View\StringTemplate;
use Cake\Utility\Hash;
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
	 * @param View $view The View this helper is being attached to.
	 * @param array<string, mixed> $config Configuration settings for the helper.
	 */
	public function __construct(View $view, array $config = []) {
		parent::__construct($view, $config + ['templateClass' => StringTemplate::class,]);

		$la_params = $this->_View->getRequest()->getParam('parts', []);

		if (!empty($la_params['sort']) && !$this->getConfig('params.sort')) {
			$this->setConfig('params.sort', $la_params['sort']);
		}

		$la_params['page'] = $la_params['limit'] = $la_params['sort'] = $la_params['direction'] = false;

		$this->setConfig('options.url', array_merge($this->_View->getRequest()->getParam('pass', []), $la_params));
	}


	/**
	 * Overridden to not throw an exception
	 * if the paginator is not set
	 *
	 * @inheritDoc
	 * @param array $options
	 * @return string|null
	 */
	public function meta(array $options = []): ?string {
		if (!isset($this->paginated)) {
			return null;
		}

		return parent::meta($options);
	}


	/**
	 * Overridden to
	 * - use the key as-is for the translation
	 * - use the underscored sort parameter as the sort key
	 * - use `static::isCurrentSortKey()` to determine if the key is the current sort key
	 * If the key is the current sort key, the default direction is set to desc
	 * - pass the camelized sort key as identifier to the templater
	 *
	 * @inheritDoc
	 * @param string $key
	 * @param array|string|null $title
	 * @param array $options
	 * @return string
	 */
	public function sort(string $key, array|string|null $title = null, array $options = []): string {
		$la_options = $options;
		$la_options += ['url' => [], 'escape' => true];

		$ls_key = Inflector::underscore($key);

		$ls_title = $title ?: __($ls_key);

		$la_url = $la_options['url'];
		unset($la_options['url']);

		$ls_sortKey = Inflector::underscore((string)$this->param('sort'));
		$ls_alias = $this->param('alias');
		[$ls_table, $ls_field] = explode('.', $ls_key . '.');

		if (!$ls_field) {
			$ls_field = $ls_table;
			$ls_table = $ls_alias;
		}

		$ls_dir = isset($la_options['direction']) ? strtolower($la_options['direction']) : 'asc';
		unset($la_options['direction']);
		// If the key is the current sort key, set the default direction to desc
		if ($this->isCurrentSortKey($ls_key)) {
			$ls_dir = 'desc';
		}

		$ls_template = 'sort';

		if (
			$ls_sortKey === ($ls_table . '.' . $ls_field) ||
			$ls_sortKey === ($ls_alias . '.' . $ls_key) ||
			($ls_table . '.' . $ls_field) === ($ls_alias . '.' . $ls_sortKey)
		) {
			$ls_dir = $this->sortDir() === 'asc' ? 'desc' : 'asc';
			$ls_template = $ls_dir === 'asc' ? 'sortDesc' : 'sortAsc';
		}

		if (is_array($title) && array_key_exists($ls_dir, $title)) {
			$ls_title = $title[ $ls_dir ];
		}

		$la_paging = [
			'sort' => $ls_key,
			'direction' => $ls_dir,
			'page' => 1,
		];

		$la_vars = [
			'text' => $la_options['escape'] ? h($ls_title) : $ls_title,
			'identifier' => Inflector::camelize($ls_key),
			'url' => $this->generateUrl($la_paging, $la_url),
		];

		return $this->templater()->format($ls_template, $la_vars);
	}


	/**
	 * Returns whether the given key is the current sort key
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isCurrentSortKey(string $key): bool {
		$ls_currentKey = $this->currentSortKey();

		if (!$ls_currentKey) {
			return false;
		}

		return Inflector::underscore($key) === Inflector::underscore($ls_currentKey);
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
	 * Overridden to
	 * - use the params present in the request (as set by the routing)
	 * - replace underscores with dashes in the key
	 * - replace underscores with dashes in the value
	 * - filters out null values
	 * - sets page to false (not null) if it's the first page
	 * - sorts the parameters so that sort, order, limit & page are at the end (and in that order)
	 *
	 * @inheritDoc
	 * @param array $options
	 * @param array $url
	 * @return array
	 */
	public function generateUrlParams(array $options = [], array $url = []): array {
		$la_params = $this->_View->getRequest()->getParam('parts');

		foreach ($options as $lx_key => $lx_value) {
			if (is_string($lx_key)) {
				$lx_key = str_replace('_', '-', $lx_key);
				$lx_key = trim($lx_key, '-');
			}

			if (is_string($lx_value)) {
				$lx_value = str_replace('_', '-', $lx_value);
				$lx_value = trim($lx_value, '-');
			}

			$la_params[ $lx_key ] = $lx_value;
		}

		$la_params += ['page' => null, 'limit' => null, 'sort' => null, 'direction' => null];
		$la_params = Hash::filter($la_params, function ($value): bool {
			return $value !== null;
		});

		if (isset($la_params['sortDefault']) && is_string($la_params['sortDefault'])) {
			$la_params['sortDefault'] = str_replace('_', '-', $la_params['sortDefault']);
			$la_params['sortDefault'] = trim($la_params['sortDefault'], '-');
		}

		if (isset($la_params['directionDefault']) && is_string($la_params['directionDefault'])) {
			$la_params['directionDefault'] = str_replace('_', '-', $la_params['directionDefault']);
			$la_params['directionDefault'] = trim($la_params['directionDefault'], '-');
		}

		//If the sorting-column and -direction equal their default value, set both to false, so they won't be part of the generated URI
		if (
			isset($la_params['sortDefault'], $la_params['directionDefault'], $la_params['sort'], $la_params['direction']) &&
			$la_params['sort'] === $la_params['sortDefault'] &&
			strtolower($la_params['direction']) === strtolower($la_params['directionDefault'])
		) {
			$la_params['sort'] = $la_params['direction'] = false;
		}

		unset($la_params['sortDefault'], $la_params['directionDefault']);

		//If the page parameter is empty or if it's page one, set it to false, so it won't be part of the generated URI
		if (!empty($options['page']) && $options['page'] === 1) {
			$la_params['page'] = false;
		}

		// Sort the parameters so that `sort`, `order`, `limit` & `page` are at the end (and in that order)
		uksort($la_params, $this->sortUrlParams(...));

		return $la_params;
	}


	/**
	 * Sorts the parameters so that sort, order, limit & page are at the end (and in that order)
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	protected function sortUrlParams(string $a, string $b): int {
		// Define the order of the special keys we want to move to the end
		$la_specialKeys = ['sort', 'direction', 'limit', 'page'];

		// Check if $a or $b are in the special keys
		$lb_aInSpecial = array_search($a, $la_specialKeys);
		$lb_bInSpecial = array_search($b, $la_specialKeys);

		// If both are not in special keys, return 0 (keep original order)
		if ($lb_aInSpecial === false && $lb_bInSpecial === false) {
			return 0;
		}

		// If $a is in the special keys but $b is not, $a should go after $b
		if ($lb_aInSpecial !== false && $lb_bInSpecial === false) {
			return 1;
		}

		// If $b is in the special keys but $a is not, $b should go after $a
		if ($lb_aInSpecial === false && $lb_bInSpecial !== false) {
			return -1;
		}

		// If both $a and $b are in the special keys, sort them by their predefined order
		return $lb_aInSpecial - $lb_bInSpecial;
	}


	/**
	 * Overridden to
	 * - allow setting the perPage value via the user configuration
	 * - sort the limits array naturally
	 * - set the form action to the userConfiguration action
	 *
	 * @param array $limits
	 * @param int|null $default
	 * @param array $options
	 * @return string
	 */
	public function limitControl(array $limits = [], ?int $default = null, array $options = []): string {
		$la_limits = $limits ?: [
			'20' => '20',
			'50' => '50',
			'100' => '100',
		];

		$la_limits += [$this->param('perPage') => $this->param('perPage')];

		natsort($la_limits);

		$li_defaultPerPage = $default ?? $this->paginated()->perPage();

		$ls_output = $this->Form->create(null, ['url' => ['action' => 'userConfiguration']]);
		$ls_output .= $this->Form->hidden('identifier', ['val' => 'paginate.limit']);
		$ls_output .= $this->Form->control(
			'value',
			$options + [
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
		if (!isset($this->paginated)) {
			return '';
		}

		if (empty($this->param('pageCount')) || $this->param('pageCount') == 1) {
			return '';
		}

		return $this->getView()->element('paginator/pagination', [
			'PaginatorHelper' => $this,
		]);
	}


	/**
	 * Formats a number for the paginator number output.
	 *
	 * @param \Awyiss\View\StringTemplate|\Cake\View\StringTemplate $templater StringTemplate instance.
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
	 * @param \Awyiss\View\StringTemplate|\Cake\View\StringTemplate $templater StringTemplate instance.
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
