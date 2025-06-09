<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\ORM\Behavior;
use Awyiss\Routing\Router;
use BackedEnum;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;


/**
 * This behavior provides and handles search-specific logic.
 */
class SearchBehavior extends Behavior {
	/**
	 * Default configuration
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'blocklistedColumns' => [],
		'implementedMethods' => [
			'getPossibleFieldValues' => 'getPossibleFieldValues',
			'getFilterColumns' => 'getFilterColumns',
			'normalizeColumnType' => 'normalizeColumnType',
			'searchIsActive' => 'isActive',
		],
		'sessionIdentifier' => null,
		'operators' => [],
		'values' => [],
	];
	/**
	 * Array of all backend users
	 *
	 * @var array
	 */
	protected array $users;


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config = []): void {
		$ls_sessionIdentifier = '_filter.' . $this->table()->getAlias();
		$this->setConfig('sessionIdentifier', $ls_sessionIdentifier);

		$lo_request = Router::getRequest();
		$lo_session = $lo_request?->getSession();

		if (!$lo_session) {
			return;
		}

		$la_sessionSettings = $lo_session->read($this->getConfig('sessionIdentifier'), []);
		$this->setConfig('operators', $la_sessionSettings['operators'] ?? []);
		$this->setConfig('values', $la_sessionSettings['values'] ?? []);
	}


	/**
	 * Get columns that should be included in the filter form.
	 *
	 * @param array $blocklistedColumns
	 * @return array<string, {disabledOperators: ?array, nullable: bool, maxLength: ?int, order: int, operator: string, type: string, value: mixed, values: ?array}>
	 */
	public function getFilterColumns(array $blocklistedColumns = []): array {
		if ($this->getConfig('columns')) {
			return $this->getConfig('columns');
		}

		$lo_schema = $this->table()->getSchema();

		$la_blocklistedColumns = array_merge($blocklistedColumns, $this->getConfig('blocklistedColumns', []), ['deleted', 'deleted_on', 'deleted_by']);

		$la_selectedOperators = $this->getConfig('operators');
		$la_selectedValues = $this->getConfig('values');

		$la_columns = [];
		foreach ($lo_schema->columns() as $ls_column) {
			if (in_array($ls_column, $la_blocklistedColumns)) {
				continue;
			}

			$la_column = $lo_schema->getColumn($ls_column);
			$ls_type = $this->table()->normalizeColumnType($la_column['type']);
			$la_values = $this->table()->getPossibleFieldValues($ls_column, $la_column['type']);

			$la_disabledOperators = $this->disabledOperators($ls_type);

			$la_columns[ $ls_column ] = [
				'disabledOperators' => $la_disabledOperators,
				'nullable' => $la_column['null'],
				'maxLength' => $la_column['length'],
				'operator' => $la_selectedOperators[ $ls_column ] ?? null,
				'type' => $ls_type,
				'value' => $la_selectedValues[ $ls_column ] ?? null,
				'values' => $la_values,
			];
		}

		if ($this->table()->hasAttributes()) {
			$lo_table = $this->table()->getAttributesTable();
			$lo_schema = $lo_table->getSchema();
			foreach ($this->table()->getAttributes() as $lo_attribute) {
				if (!$lo_schema->getColumn($lo_attribute->identifier)) {
					continue;
				}

				$la_column = $lo_schema->getColumn($lo_attribute->identifier);
				$ls_type = $lo_table->normalizeColumnType($la_column['type']);
				$la_values = $lo_table->getPossibleFieldValues($lo_attribute->identifier, $la_column['type']);

				$la_disabledOperators = $this->disabledOperators($ls_type);

				$la_columns['attributes__' . $lo_attribute->identifier ] = [
					'disabledOperators' => $la_disabledOperators,
					'nullable' => $la_column['null'],
					'maxLength' => $la_column['length'],
					'operator' => $la_selectedOperators['attributes__' . $lo_attribute->identifier ] ?? null,
					'title' => $lo_attribute->title,
					'type' => $ls_type,
					'value' => $la_selectedValues['attributes__' . $lo_attribute->identifier ] ?? null,
					'values' => $la_values,
				];
			}
		}

		$this->setConfig('columns', $la_columns);

		return $la_columns;
	}


	/**
	 * Check if the search is active.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		$la_sessionData = Router::getRequest()->getSession()->read($this->getConfig('sessionIdentifier'));

		return !empty($la_sessionData['values']);
	}


	/**
	 * Get possible field values for the search form.
	 *
	 * @param string $column
	 * @param string|null $type
	 * @return array|null
	 * @throws \ReflectionException
	 */
	public function getPossibleFieldValues(string $column, ?string $type = null): ?array {
		$lo_table = $this->table();

		if ($lo_table->hasBehavior('Categories')) {
			/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_categoriesBehavior */
			$lo_categoriesBehavior = $lo_table->getBehavior('Categories');
			if (
				$lo_categoriesBehavior->getConfig('enabled') &&
				$lo_categoriesBehavior->getConfig('foreignKey') === $column
			) {
				return $lo_categoriesBehavior->getCategories();
			}
		}

		if ($lo_table->hasBehavior('Nest')) {
			/** @var \Awyiss\Model\Behavior\NestBehavior $lo_nestBehavior */
			$lo_nestBehavior = $lo_table->getBehavior('Nest');
			if (
				$lo_nestBehavior->getConfig('enabled') &&
				$lo_nestBehavior->getConfig('parent.foreignKey') === $column
			) {
				$lo_query = $lo_table->find('all');


				if (
					in_array($lo_table->getAlias(), ['Contents', 'Widgets']) &&
					$lo_table->hasAssociation('MediaAssignments')
				) {
					$lo_query->find('mediaAssignments', useMediaEntity: true);
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $lo_nestBehavior->listNested($lo_query)->printer('label', 'id', '- ')->toArray();
			}
		}

		if ($column === 'language_shortcode') {
			$la_languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);

			return array_column($la_languages, 'label', 'shortcode');
		}

		$lo_attributesBehavior = $this->table()->hasBehavior('Attributes') ? $lo_table->getBehavior('Attributes') : null;
		if ($lo_attributesBehavior && $lo_attributesBehavior->getConfig('isAttributesTable')) {
			$lo_attributeOptions = AttributeOptionsProvider::getAttributeOptionsFile(
				$lo_attributesBehavior->getConfig('sourceTable'),
				true
			)?->getAttributeOption($column);

			if ($lo_attributeOptions) {
				return $lo_attributeOptions->getOptions(true) ?? [];
			}
		}

		if (in_array($column, ['created_by', 'changed_by'])) {
			return $this->getUsers();
		}

		if ($type && str_starts_with($type, 'enum-')) {
			$lo_dbType = TypeFactory::build($type);
			if ($lo_dbType instanceof EnumType) {
				return $this->getEnumValues($lo_dbType);
			}
		}

		return null;
	}


	/**
	 * @param string $type
	 * @return string
	 */
	public function normalizeColumnType(string $type): string {
		if (str_starts_with($type, 'enum-')) {
			return 'enum';
		}

		return match ($type) {
			'char', 'json', 'string', 'text' => 'text',
			default => $type,
		};
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $query): SelectQuery {
		$la_sessionData = Router::getRequest()->getSession()->read($this->getConfig('sessionIdentifier'));

		if (!($la_sessionData['values'] ?? []) || !$this->getFilterColumns()) {
			return $query;
		}

		foreach ($this->getFilterColumns() as $ls_column => $la_columnSettings) {
			if (!array_key_exists($ls_column, $la_sessionData['values'])) {
				continue;
			}

			$this->addFilterCondition($query, $ls_column, $la_columnSettings);
		}

		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param string $operator
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpFunctionCyclomaticComplexityInspection
	 */
	protected function addFilterCondition(SelectQuery $query, string $column, array $columnSettings): SelectQuery {
		$ls_operator = $columnSettings['operator'] ?? '=';
		if (in_array($ls_operator, $columnSettings['disabledOperators'] ?? [])) {
			return $query;
		}

		if ($columnSettings['type'] === 'boolean' && $columnSettings['value'] === null) {
			return $query;
		}

		$ls_column = $column;
		if (str_starts_with($column, 'attributes__')) {
			$ls_column = $this->table()->getAttributesTableName(true) . '.' . substr($column, 12);
		}
		elseif (!str_contains('.', $ls_column)) {
			$ls_column = $query->getRepository()->getAlias() . '.' . $ls_column;
		}

		return match ($ls_operator) {
			'=' => $this->addEqualsCondition($query, $ls_column, $columnSettings),
			'!=' => $this->addEqualsCondition($query, $ls_column, $columnSettings, true),
			'<' => $this->addGreaterThanCondition($query, $ls_column, $columnSettings, false, true),
			'<=' => $this->addGreaterThanCondition($query, $ls_column, $columnSettings, true, true),
			'>' => $this->addGreaterThanCondition($query, $ls_column, $columnSettings),
			'>=' => $this->addGreaterThanCondition($query, $ls_column, $columnSettings, true),
			'between' => $this->addBetweenCondition($query, $ls_column, $columnSettings),
			'not_between' => $this->addBetweenCondition($query, $ls_column, $columnSettings, true),
			'length_equal' => $this->addLengthEqualToCondition($query, $ls_column, $columnSettings),
			'length_not_equal' => $this->addLengthEqualToCondition($query, $ls_column, $columnSettings, true),
			'shorter_than' => $this->addLongerThanCondition($query, $ls_column, $columnSettings, false, true),
			'shorter_than_or_equal' => $this->addLongerThanCondition($query, $ls_column, $columnSettings, true, true),
			'longer_than' => $this->addLongerThanCondition($query, $ls_column, $columnSettings),
			'longer_than_or_equal' => $this->addLongerThanCondition($query, $ls_column, $columnSettings, true),
			'in' => $this->addInCondition($query, $ls_column, $columnSettings),
			'not_in' => $this->addInCondition($query, $ls_column, $columnSettings, true),
			'contains' => $this->addContainsCondition($query, $ls_column, $columnSettings),
			'not_contains' => $this->addContainsCondition($query, $ls_column, $columnSettings, true),
			'starts_with' => $this->addContainsCondition($query, $ls_column, $columnSettings, false, 'end'),
			'not_starts_with' => $this->addContainsCondition($query, $ls_column, $columnSettings, true, 'end'),
			'ends_with' => $this->addContainsCondition($query, $ls_column, $columnSettings, false, 'start'),
			'not_ends_with' => $this->addContainsCondition($query, $ls_column, $columnSettings, true, 'start'),
			default => $query,
		};
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addEqualsCondition(SelectQuery $query, string $column, array $columnSettings, bool $not = false): SelectQuery {
		$lx_value = $this->normalizeValue($columnSettings['value'], $columnSettings);

		if ($lx_value === null) {
			$ls_operator = $not ? ' IS NOT' : ' IS';
		}
		else {
			$ls_operator = $not ? ' !=' : '';
		}

		return $query->where([
			$column . $ls_operator => $lx_value,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $orEqual
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addGreaterThanCondition(SelectQuery $query, string $column, array $columnSettings, bool $orEqual = false, bool $not = false): SelectQuery {
		$lx_value = $this->normalizeValue($columnSettings['value'], $columnSettings);

		if ($lx_value === null) {
			return $query;
		}

		$ls_operator = $not ? ' <' : ' >';
		if ($orEqual) {
			$ls_operator = $not ? ' <=' : ' >=';
		}

		return $query->where([
			$column . $ls_operator => $lx_value,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addBetweenCondition(SelectQuery $query, string $column, array $columnSettings, bool $not = false): SelectQuery {
		$la_values = is_null($columnSettings['value']) ? [] : explode(',', $columnSettings['value']);

		if (!$la_values || count($la_values) !== 2) {
			return $query;
		}

		// Normalize all
		$la_values = array_map(fn ($value) => $this->normalizeValue($value, $columnSettings), $la_values);

		if (!$not) {
			return $query->where([
				$column . ' >=' => $la_values[0],
				$column . ' <=' => $la_values[1],
			]);
		}

		return $query->where([
			'OR' => [
				$column . ' <' => $la_values[0],
				$column . ' >' => $la_values[1],
			],
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function addLengthEqualToCondition(SelectQuery $query, string $column, array $columnSettings, bool $not = false): SelectQuery {
		if ($columnSettings['value'] === null) {
			return $query;
		}

		$li_value = (int)$columnSettings['value'];

		return $query->where(function (QueryExpression $exp) use ($column, $query, $li_value, $not) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_lengthExp = $query->func()->length([$column => 'identifier']);

			return $not ? $exp->notEq($lo_lengthExp, $li_value) : $exp->eq($lo_lengthExp, $li_value);
		});
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $orEqual
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function addLongerThanCondition(SelectQuery $query, string $column, array $columnSettings, bool $orEqual = false, bool $not = false): SelectQuery {
		if ($columnSettings['value'] === null) {
			return $query;
		}

		$li_value = (int)$columnSettings['value'];

		$ls_operator = $not ? ' <' : ' >';
		if ($orEqual) {
			$ls_operator = $not ? ' <=' : ' >=';
		}

		return $query->where(function (QueryExpression $exp) use ($column, $query, $li_value, $ls_operator) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_lengthExp = $query->func()->length([$column => 'identifier']);

			return match ($ls_operator) {
				' >' => $exp->gt($lo_lengthExp, $li_value),
				' >=' => $exp->gte($lo_lengthExp, $li_value),
				' <' => $exp->lt($lo_lengthExp, $li_value),
				' <=' => $exp->lte($lo_lengthExp, $li_value),
				default => $exp,
			};
		});
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addInCondition(SelectQuery $query, string $column, array $columnSettings, bool $not = false): SelectQuery {
		$la_values = is_null($columnSettings['value']) ? [] : explode(',', $columnSettings['value']);

		if (!$la_values) {
			return $query;
		}

		// Normalize all
		$la_values = array_map(fn ($value) => $this->normalizeValue($value, $columnSettings), $la_values);

		if (!$not) {
			return $query->where([
				$column . ' IN' => $la_values,
			]);
		}

		return $query->where([
			$column . ' NOT IN' => $la_values,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param array $columnSettings
	 * @param bool $not
	 * @param string $wildcard
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function addContainsCondition(SelectQuery $query, string $column, array $columnSettings, bool $not = false, string $wildcard = 'both'): SelectQuery {
		$lx_value = $this->normalizeValue($columnSettings['value'], $columnSettings);

		$ls_wildcard = $wildcard;
		if (!in_array($wildcard, ['both', 'start', 'end'])) {
			$ls_wildcard = 'both';
		}

		if ($lx_value === null) {
			return $query;
		}

		$ls_expression = '%' . $lx_value . '%';
		if ($ls_wildcard === 'start') {
			$ls_expression = '%' . $lx_value;
		}
		elseif ($ls_wildcard === 'end') {
			$ls_expression = $lx_value . '%';
		}

		if ($not) {
			return $query->where(function (QueryExpression $exp) use ($column, $ls_expression) {
				return $exp->notLike($column, $ls_expression, 'string');
			});
		}

		return $query->where(function (QueryExpression $exp) use ($column, $ls_expression) {
			return $exp->like($column, $ls_expression, 'string');
		});
	}


	/**
	 * @param \Cake\Database\Type\EnumType $dbType
	 * @return array
	 */
	protected function getEnumValues(EnumType $dbType): array {
		$ls_enumClass = $dbType->getEnumClassName();

		$la_values = [];

		foreach ($ls_enumClass::cases() as $le_case) {
			if ($le_case instanceof EnumLabelInterface) {
				$la_values[ $le_case->value ] = $le_case->label();
			}
			elseif ($le_case instanceof BackedEnum) {
				$la_values[ $le_case->value ] = $le_case->name;
			}
			else {
				$la_values[ $le_case->name ] = $le_case->name;
			}
		}

		return $la_values;
	}


	/**
	 * @return array
	 */
	protected function getUsers(): array {
		if (isset($this->users)) {
			return $this->users;
		}

		$this->users = FactoryLocator::get('Table')->get('Users')->find('list', keyField: 'id', valueField: 'label')->toArray();

		return $this->users;
	}


	/**
	 * Returns a list of operators that should be disabled for the given column type.
	 *
	 * @param string $type
	 * @return array|null
	 */
	protected function disabledOperators(string $type): ?array {
		$la_operators = [];

		if ($type === 'text') {
			$la_operators = [
				ComparisonOperator::LessThan,
				ComparisonOperator::LessThanOrEqual,
				ComparisonOperator::GreaterThan,
				ComparisonOperator::GreaterThanOrEqual,
				ComparisonOperator::Between,
				ComparisonOperator::NotBetween,
			];
		}

		if (in_array($type, ['date', 'time', 'datetime'])) {
			$la_operators = [
				ComparisonOperator::LengthEqual,
				ComparisonOperator::LengthNotEqual,
				ComparisonOperator::ShorterThan,
				ComparisonOperator::ShorterThanOrEqual,
				ComparisonOperator::LongerThan,
				ComparisonOperator::LongerThanOrEqual,
				ComparisonOperator::Contains,
				ComparisonOperator::NotContains,
				ComparisonOperator::StartsWith,
				ComparisonOperator::NotStartsWith,
				ComparisonOperator::EndsWith,
				ComparisonOperator::NotEndsWith,
			];
		}

		if ($la_operators) {
			return array_map(function (ComparisonOperator $operator) {
				return $operator->value;
			}, $la_operators);
		}


		return null;
	}


	/**
	 * @param mixed $value
	 * @param array $columnSettings
	 * @return mixed|string
	 */
	protected function normalizeValue(mixed $value, array $columnSettings): mixed {
		$lx_value = $value;

		if (!$columnSettings['nullable'] && $lx_value === null) {
			$lx_value = '';
		}

		if ($lx_value === null) {
			return null;
		}

		if ($columnSettings['type'] === 'boolean') {
			if ($columnSettings['nullable'] && empty($lx_value)) {
				return null;
			}

			return $lx_value !== '0';
		}

		if ($columnSettings['type'] === 'float') {
			return (float)str_replace(',', '.', (string)$lx_value);
		}

		if ($columnSettings['type'] === 'integer') {
			return (int)$lx_value;
		}

		return $lx_value;
	}
}
