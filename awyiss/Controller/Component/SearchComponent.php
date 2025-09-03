<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Routing\Router;
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
	protected array $_defaultConfig = [
		'autoload' => ['overview'], //can be a boolean value or an array containing all action names for which the settings should get autoloaded
		'blocklistedColumns' => [], //columns that should not be included in the filter form
	];


	/**
	 * Called after `Controller::beforeFilter()` method, and before the controller action is called.
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		$lo_controller = $this->getController();
		$ls_action = $lo_controller->getRequest()->getParam('action');
		$lx_autoload = $this->getConfig('autoload');

		//Shall we autoload the records?
		if (
			$lx_autoload === true ||
			(
				is_array($lx_autoload) &&
				in_array($ls_action, $lx_autoload)
			) ||
			(
				is_string($lx_autoload) &&
				$ls_action === $lx_autoload
			)
		) {
			$this->setFilterVars($lo_controller->getName());
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
		$lo_controller = $this->getController();
		$lo_table = $this->getController()->fetchTable($tableName);

		if (!$lo_table->hasBehavior('Search')) {
			return;
		}

		$lo_view = $lo_controller->viewBuilder();

		$la_filterSettings = $lo_view->getVar('_filter') ?? [];

		if (!$la_filterSettings) {
			$la_filterSettings = [
				// Unset the regex operator. Regex to the database isn't quiet secure
				'operators' => array_filter(ComparisonOperator::cases(), fn ($operator) => $operator !== ComparisonOperator::Regexp),
			];
		}

		$ls_name = Inflector::underscore($tableName);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_vars = $lo_table->getFilterColumns($blocklistedColumns);

		// Handle post or already saved filter settings
		if ($lo_controller->getRequest()->is('post')) {
			$this->handlePostFilterSettings($ls_name, $tableName, $la_vars);
		}

		$this->handleSessionFilterSettings($la_filterSettings, $tableName);

		// Set the selected columns to the default columns if they are not set
		$la_filterSettings[ $ls_name ]['selectedColumns'] ??= [];
		$la_filterSettings[ $ls_name ]['active'] ??= false;

		// Order the columns by the order in the selectedColumns array
		$this->orderVars($la_vars);

		// Set the vars in the array
		$la_filterSettings[ $ls_name ]['columns'] = $la_vars;
		// Set the vars in the view
		$lo_view->setVar('_filter', $la_filterSettings);
	}


	/**
	 * @param array $vars
	 * @param string $tableName
	 * @return array
	 */
	protected function getDefaultSelectedColumns(array $vars, string $tableName): array {
		$lo_table = $this->getController()->fetchTable($tableName);

		$la_defaultSelectedColumns = $lo_table->getBehavior('Search')->getConfig('defaultSelectedColumns');

		if (is_array($la_defaultSelectedColumns)) {
			return $la_defaultSelectedColumns;
		}

		$la_defaultSelectedColumns = [];
		if (array_key_exists('active', $vars)) {
			$la_defaultSelectedColumns[] = 'active';
		}

		return $la_defaultSelectedColumns;
	}


	/**
	 * @param array &$vars
	 * @return void
	 */
	protected function orderVars(array &$vars): void {
		/** @noinspection PhpVariableNamingConventionInspection */
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
		$lo_request = $this->getController()->getRequest();
		$la_postData = $lo_request->getData('filter.' . $name);

		$lo_table = $this->getController()->fetchTable($tableName);
		$lo_sessionIdentifier = $lo_table->getBehavior('Search')->getConfig('sessionIdentifier');

		if ($lo_request->getData('submit_type') === 'reset') {
			$lo_request->getSession()->delete($lo_sessionIdentifier);

			// Redirect to the same page to prevent resubmission
			$this->getController()->redirect(Router::url());

			return;
		}

		if (!$la_postData || !is_array($la_postData)) {
			return;
		}

		$la_settings = [
			'operators' => [],
			'selectedColumns' => [],
			'values' => [],
		];

		foreach ($la_postData as $ls_column => $la_columnSettings) {
			if (!isset($vars[ $ls_column ]) || !($la_columnSettings['active'] ?? false)) {
				continue;
			}

			if ($la_columnSettings['order'] ?? null) {
				$la_settings['selectedColumns'][ $la_columnSettings['order'] ] = $ls_column;
			}
			else {
				$la_settings['selectedColumns'][] = $ls_column;
			}

			$ls_value = $la_columnSettings['value'] ?? null;

			if (
				$ls_value === '' ||
				(
					$ls_value === 'all' &&
					$vars[ $ls_column ]['type'] === 'boolean'
				)
			) {
				$ls_value = null;
			}

			$la_settings['operators'][ $ls_column ] = $la_columnSettings['operator'] ?? '=';
			$la_settings['values'][ $ls_column ] = $ls_value;
		}

		// Reset the order
		$la_settings['selectedColumns'] = array_values($la_settings['selectedColumns']);

		if (!$la_settings['selectedColumns']) {
			// Delete the session data if no columns are selected
			$lo_request->getSession()->delete($lo_sessionIdentifier);
		}
		else {
			$lo_request->getSession()->write($lo_sessionIdentifier, $la_settings);
		}

		// Redirect to the same page to prevent resubmission
		$this->getController()->redirect(Router::url());
	}


	/**
	 * @param array &$filterSettings
	 * @param string $tableName
	 * @return void
	 */
	protected function handleSessionFilterSettings(array &$filterSettings, string $tableName): void {
		$lo_request = $this->getController()->getRequest();
		$lo_table = $this->getController()->fetchTable($tableName);

		$lo_sessionIdentifier = $lo_table->getBehavior('Search')->getConfig('sessionIdentifier');

		$la_sessionFilterSettings = $lo_request->getSession()->read($lo_sessionIdentifier);

		if (!$la_sessionFilterSettings) {
			return;
		}

		$ls_name = Inflector::underscore($tableName);
		/** @noinspection PhpVariableNamingConventionInspection */
		$filterSettings[ $ls_name ] = [
			'active' => true,
			'selectedColumns' => $la_sessionFilterSettings['selectedColumns'],
		];
	}
}
