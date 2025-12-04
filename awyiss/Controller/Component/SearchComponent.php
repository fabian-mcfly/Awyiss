<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Utility\Inflector;
use Cake\Controller\Component;
use Cake\ORM\Query\SelectQuery;


/**
 * This component provides and handles search-specific logic.
 * It also provides the view vars for the filter form.
 * The filter form is autoloaded for overview actions for the given controller (scope).
 *
 * Other controllers/actions can call setFilterVars with a specific scope
 * to load the filter vars for another controller/model
 */
class SearchComponent extends Component {
	/**
	 * @inheritDoc
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'autoload' => ['overview'], //can be a boolean value or an array containing all action names for which the settings should get autoloaded
		'blocklistedColumns' => [], //columns that should not be included in the filter form
	];


	/**
	 * Called after `Controller::beforeFilter()` method, and before the controller action is called.
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		$controller = $this->getController();
		$action = $controller->getRequest()->getParam('action');
		$autoload = $this->getConfig('autoload');

		//Shall we autoload the records?
		if (
			$autoload === true ||
			(
				is_array($autoload) &&
				in_array($action, $autoload)
			) ||
			(
				is_string($autoload) &&
				$action === $autoload
			)
		) {
			$this->setFilterVars($controller->getName());
		}
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $query): SelectQuery {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $query->getRepository()->getBehavior('Search')->filterQuery($query);
	}


	/**
	 * @param string $tableName
	 * @param array $blocklistedColumns
	 * @return void
	 */
	public function setFilterVars(string $tableName, array $blocklistedColumns = []): void {
		$controller = $this->getController();
		$table = $this->getController()->fetchTable($tableName);

		if (!$table->hasBehavior('Search')) {
			return;
		}

		$view = $controller->viewBuilder();

		$filterSettings = $view->getVar('_filter') ?? [];

		if (!$filterSettings) {
			$filterSettings = [
				// Unset the regex operator. Regex to the database isn't quiet secure
				'operators' => array_filter(ComparisonOperator::cases(), fn ($operator) => $operator !== ComparisonOperator::Regexp),
			];
		}

		$name = Inflector::underscore($tableName);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$vars = $table->getFilterColumns($blocklistedColumns);

		// Handle post or already saved filter settings
		if ($controller->getRequest()->is('post')) {
			$this->handlePostFilterSettings($name, $tableName, $vars);
		}

		$this->handleSessionFilterSettings($filterSettings, $tableName);

		// Set the selected columns to the default columns if they are not set
		$filterSettings[ $name ]['selectedColumns'] ??= [];
		$filterSettings[ $name ]['active'] ??= false;

		// Order the columns by the order in the selectedColumns array
		$this->orderVars($vars);

		// Set the vars in the array
		$filterSettings[ $name ]['columns'] = $vars;
		// Set the vars in the view
		$view->setVar('_filter', $filterSettings);
	}


	/**
	 * @param array $vars
	 * @param string $tableName
	 * @return array
	 */
	protected function getDefaultSelectedColumns(array $vars, string $tableName): array {
		$table = $this->getController()->fetchTable($tableName);

		$defaultSelectedColumns = $table->getBehavior('Search')->getConfig('defaultSelectedColumns');

		if (is_array($defaultSelectedColumns)) {
			return $defaultSelectedColumns;
		}

		$defaultSelectedColumns = [];
		if (array_key_exists('active', $vars)) {
			$defaultSelectedColumns[] = 'active';
		}

		return $defaultSelectedColumns;
	}


	/**
	 * @param array &$vars
	 * @return void
	 */
	protected function orderVars(array &$vars): void {
		uksort($vars, function (string $a, string $b): int {
			// Always put 'active' first
			if ($a === 'active') {
				return -1;
			}
			if ($b === 'active') {
				return 1;
			}

			return 0;
		});
	}


	/**
	 * @param string $name
	 * @param string $tableName
	 * @param array $vars
	 * @return void
	 */
	protected function handlePostFilterSettings(string $name, string $tableName, array $vars): void {
		$request = $this->getController()->getRequest();
		$postData = $request->getData('filter.' . $name);

		$table = $this->getController()->fetchTable($tableName);
		$sessionIdentifier = $table->getBehavior('Search')->getConfig('sessionIdentifier');

		if ($request->getData('submit_type') === 'reset') {
			$request->getSession()->delete($sessionIdentifier);

			// Redirect to the same page to prevent resubmission
			$this->getController()->redirect([]);

			return;
		}

		if (!$postData || !is_array($postData)) {
			return;
		}

		$settings = [
			'operators' => [],
			'selectedColumns' => [],
			'values' => [],
		];

		foreach ($postData as $column => $columnSettings) {
			if (!isset($vars[ $column ]) || !($columnSettings['active'] ?? false)) {
				continue;
			}

			if ($columnSettings['order'] ?? null) {
				$settings['selectedColumns'][ $columnSettings['order'] ] = $column;
			}
			else {
				$settings['selectedColumns'][] = $column;
			}

			$value = $columnSettings['value'] ?? null;

			if (
				$value === '' ||
				(
					$value === 'all' &&
					$vars[ $column ]['type'] === 'boolean'
				)
			) {
				$value = null;
			}

			$settings['operators'][ $column ] = $columnSettings['operator'] ?? '=';
			$settings['values'][ $column ] = $value;
		}

		// Reset the order
		$settings['selectedColumns'] = array_values($settings['selectedColumns']);

		if (!$settings['selectedColumns']) {
			// Delete the session data if no columns are selected
			$request->getSession()->delete($sessionIdentifier);
		}
		else {
			$request->getSession()->write($sessionIdentifier, $settings);
		}

		// Redirect to the same page to prevent resubmission
		$this->getController()->redirect([]);
	}


	/**
	 * @param array &$filterSettings
	 * @param string $tableName
	 * @return void
	 */
	protected function handleSessionFilterSettings(array &$filterSettings, string $tableName): void {
		$request = $this->getController()->getRequest();
		$table = $this->getController()->fetchTable($tableName);

		$sessionIdentifier = $table->getBehavior('Search')->getConfig('sessionIdentifier');

		$sessionFilterSettings = $request->getSession()->read($sessionIdentifier);

		if (!$sessionFilterSettings) {
			return;
		}

		$filterSettings[ Inflector::underscore($tableName) ] = [
			'active' => true,
			'selectedColumns' => $sessionFilterSettings['selectedColumns'],
		];
	}
}
