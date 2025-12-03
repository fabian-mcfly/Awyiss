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

		$params = $this->_View->getRequest()->getParam('parts', []);

		if (!empty($params['sort']) && !$this->getConfig('params.sort')) {
			$this->setConfig('params.sort', $params['sort']);
		}

		$params['page'] = $params['limit'] = $params['sort'] = $params['direction'] = false;

		$this->setConfig('options.url', array_merge($this->_View->getRequest()->getParam('pass', []), $params));
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
		$options += ['url' => [], 'escape' => true];

		$key = Inflector::underscore($key);

		$title = $title ?: __($key);

		$url = $options['url'];
		unset($options['url']);

		$sortKey = Inflector::underscore((string)$this->param('sort'));
		$alias = $this->param('alias');
		[$tableName, $field] = explode('.', $key . '.');

		if (!$field) {
			$field = $tableName;
			$tableName = $alias;
		}

		$direction = isset($options['direction']) ? strtolower($options['direction']) : 'asc';
		unset($options['direction']);
		// If the key is the current sort key, set the default direction to desc
		if ($this->isCurrentSortKey($key)) {
			$direction = 'desc';
		}

		$templateName = 'sort';

		if (
			$sortKey === ($tableName . '.' . $field) ||
			$sortKey === ($alias . '.' . $key) ||
			($tableName . '.' . $field) === ($alias . '.' . $sortKey)
		) {
			$direction = $this->sortDir() === 'asc' ? 'desc' : 'asc';
			$templateName = $direction === 'asc' ? 'sortDesc' : 'sortAsc';
		}

		if (is_array($title) && array_key_exists($direction, $title)) {
			$title = $title[ $direction ];
		}

		$paging = [
			'sort' => $key,
			'direction' => $direction,
			'page' => 1,
		];

		$vars = [
			'text' => $options['escape'] ? h($title) : $title,
			'identifier' => Inflector::camelize($key),
			'url' => $this->generateUrl($paging, $url),
		];

		return $this->templater()->format($templateName, $vars);
	}


	/**
	 * Returns whether the given key is the current sort key
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isCurrentSortKey(string $key): bool {
		$currentKey = $this->currentSortKey();

		if (!$currentKey) {
			return false;
		}

		return Inflector::underscore($key) === Inflector::underscore($currentKey);
	}


	/**
	 * Returns the current sort key
	 *
	 * @return string|null
	 */
	public function currentSortKey(): ?string {
		$sortKey = $this->param('sort');
		if (!$sortKey) {
			$sortKey = $this->param('sortDefault');
		}

		if (!$sortKey) {
			return null;
		}

		// Return the original sort key if its aliased
		$aliasFields = $this->getConfig('aliasedFields');
		if (isset($aliasFields[ $sortKey ])) {
			$sortKey = $aliasFields[ $sortKey ];
		}

		return $sortKey;
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
		$params = $this->_View->getRequest()->getParam('parts');

		foreach ($options as $key => $value) {
			if (is_string($key)) {
				$key = str_replace('_', '-', $key);
				$key = trim($key, '-');
			}

			if (is_string($value)) {
				$value = str_replace('_', '-', $value);
				$value = trim($value, '-');
			}

			$params[ $key ] = $value;
		}

		$params += ['page' => null, 'limit' => null, 'sort' => null, 'direction' => null];
		$params = Hash::filter($params, function ($value): bool {
			return $value !== null;
		});

		if (isset($params['sortDefault']) && is_string($params['sortDefault'])) {
			$params['sortDefault'] = str_replace('_', '-', $params['sortDefault']);
			$params['sortDefault'] = trim($params['sortDefault'], '-');
		}

		if (isset($params['directionDefault']) && is_string($params['directionDefault'])) {
			$params['directionDefault'] = str_replace('_', '-', $params['directionDefault']);
			$params['directionDefault'] = trim($params['directionDefault'], '-');
		}

		//If the sorting-column and -direction equal their default value, set both to false, so they won't be part of the generated URI
		if (
			isset($params['sortDefault'], $params['directionDefault'], $params['sort'], $params['direction']) &&
			$params['sort'] === $params['sortDefault'] &&
			strtolower($params['direction']) === strtolower($params['directionDefault'])
		) {
			$params['sort'] = $params['direction'] = false;
		}

		unset($params['sortDefault'], $params['directionDefault']);

		//If the page parameter is empty or if it's page one, set it to false, so it won't be part of the generated URI
		if (!empty($options['page']) && $options['page'] === 1) {
			$params['page'] = false;
		}

		// Sort the parameters so that `sort`, `order`, `limit` & `page` are at the end (and in that order)
		uksort($params, $this->sortUrlParams(...));

		return $params;
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
		$specialKeys = ['sort', 'direction', 'limit', 'page'];

		// Check if $a or $b are in the special keys
		$aInSpecial = array_search($a, $specialKeys);
		$bInSpecial = array_search($b, $specialKeys);

		// If both are not in special keys, return 0 (keep original order)
		if ($aInSpecial === false && $bInSpecial === false) {
			return 0;
		}

		// If $a is in the special keys but $b is not, $a should go after $b
		if ($aInSpecial !== false && $bInSpecial === false) {
			return 1;
		}

		// If $b is in the special keys but $a is not, $b should go after $a
		if ($aInSpecial === false && $bInSpecial !== false) {
			return -1;
		}

		// If both $a and $b are in the special keys, sort them by their predefined order
		return $aInSpecial - $bInSpecial;
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
		$limits = $limits ?: [
			'20' => '20',
			'50' => '50',
			'100' => '100',
		];

		$limits += [$this->param('perPage') => $this->param('perPage')];

		natsort($limits);

		$defaultPerPage = $default ?? $this->paginated()->perPage();

		$output = $this->Form->create(null, ['url' => ['action' => 'userConfiguration']]);
		$output .= $this->Form->hidden('identifier', ['val' => 'paginate.limit']);
		$output .= $this->Form->control(
			'value',
			$options + [
				'default' => $defaultPerPage,
				'empty' => false,
				'label' => __d('pagination', 'limit_per_page'),
				'options' => $limits,
				'type' => 'select',
				'value' => $this->param('perPage'),
			]
		);
		$output .= $this->Form->end();


		return $output;
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
		$vars = [
			'page' => __d('pagination', 'page'),
			'text' => $options['text'],
			'url' => $this->generateUrl(['page' => $options['page']], $options['url']),
		];

		return $templater->format('number', $vars);
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
		$output = '';
		$output .= $options['before'];

		for ($i = 1; $i <= $params['pageCount']; $i++) {
			if ($i === $params['currentPage']) {
				$output .= $templater->format('current', [
					'page' => __d('pagination', 'page'),
					'text' => $this->Number->format($params['currentPage']),
					'url' => $this->generateUrl(['page' => $i], $options['url']),
				]);
			}
			else {
				$vars = [
					'page' => __d('pagination', 'page'),
					'text' => $this->Number->format($i),
					'url' => $this->generateUrl(['page' => $i], $options['url']),
				];
				$output .= $templater->format('number', $vars);
			}
		}
		$output .= $options['after'];

		return $output;
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
