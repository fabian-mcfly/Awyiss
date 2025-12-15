<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\Search\FilterColumnSettings;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Enum\DateComparisonOperator;
use Awyiss\ORM\Behavior;
use Awyiss\Routing\Router;
use BackedEnum;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
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
	protected array $_defaultConfig = [ // phpcs:ignore
		'blocklistedColumns' => [],
		'handleNulls' => true,
		'implementedMethods' => [
			'getPossibleFieldValues' => 'getPossibleFieldValues',
			'getFilterColumns' => 'getFilterColumns',
			'searchFilterQuery' => 'filterQuery',
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
		$sessionIdentifier = '_filter.' . $this->table()->getAlias();
		$this->setConfig('sessionIdentifier', $sessionIdentifier);

		$request = Router::getRequest();
		$session = $request?->getSession();

		if (!$session) {
			return;
		}

		$sessionSettings = $session->read($this->getConfig('sessionIdentifier'), []);
		$this->setConfig('operators', $sessionSettings['operators'] ?? []);
		$this->setConfig('values', $sessionSettings['values'] ?? []);
	}


	/**
	 * Get columns that should be included in the filter form.
	 *
	 * @param array $blocklistedColumns
	 * @param array|null $selectedOperators
	 * @param array|null $selectedValues
	 * @param bool $includePossibleValues
	 * @return array<string, \Awyiss\Model\Behavior\Search\FilterColumnSettings>
	 */
	public function getFilterColumns(array $blocklistedColumns = [], ?array $selectedOperators = null, ?array $selectedValues = null, bool $includePossibleValues = true): array {
		$schema = $this->table()->getSchema();

		$blocklistedColumns = array_merge($blocklistedColumns, $this->getConfig('blocklistedColumns', []), ['deleted', 'deleted_on', 'deleted_by']);

		if ($this->getConfig('columns') && $selectedOperators === null && $selectedValues === null) {
			$columns = $this->getConfig('columns');

			return array_diff_key($columns, array_flip($blocklistedColumns));
		}

		$selectedOperators ??= $this->getConfig('operators');
		$selectedValues ??= $this->getConfig('values');

		$columns = [];
		foreach ($schema->columns() as $column) {
			$columnData = $schema->getColumn($column);
			$type = $this->table()->normalizeColumnType($columnData['type']);
			$values = $includePossibleValues ? $this->table()->getPossibleFieldValues($column, $columnData['type']) : [];

			$disabledOperators = $this->disabledOperators($type);

			$columns[$column] = new FilterColumnSettings(
				disabledOperators: $disabledOperators,
				nullable: $columnData['null'],
				maxLength: $columnData['length'],
				operator: $selectedOperators[ $column ] ?? null,
				type: $type,
				value: $selectedValues[ $column ] ?? null,
				values: $values,
			);
		}

		if ($this->table()->hasAttributes()) {
			$attributesTable = $this->table()->getAttributesTable();
			$schema = $attributesTable->getSchema();
			foreach ($this->table()->getAttributes() as $attribute) {
				if (!$schema->getColumn($attribute->identifier)) {
					continue;
				}

				$columnData = $schema->getColumn($attribute->identifier);
				$type = $attributesTable->normalizeColumnType($columnData['type']);
				$values = $includePossibleValues ? $attributesTable->getPossibleFieldValues($attribute->identifier, $columnData['type']) : [];

				$disabledOperators = $this->disabledOperators($type);

				$columns['attributes__' . $attribute->identifier] = new FilterColumnSettings(
					disabledOperators: $disabledOperators,
					nullable: $columnData['null'],
					maxLength: $columnData['length'],
					operator: $selectedOperators['attributes__' . $attribute->identifier ] ?? null,
					title: $attribute->title,
					type: $type,
					value: $selectedValues['attributes__' . $attribute->identifier ] ?? null,
					values: $values,
				);
			}
		}

		// Only cache the columns if no specific selections are made
		if ($selectedOperators === null && $selectedValues === null && $includePossibleValues) {
			$this->setConfig('columns', $columns);
		}

		return array_diff_key($columns, array_flip($blocklistedColumns));
	}


	/**
	 * Check if the search is active.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		$sessionData = Router::getRequest()->getSession()->read($this->getConfig('sessionIdentifier'));

		return !empty($sessionData['values']);
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
		$table = $this->table();

		if ($table->hasBehavior('Categories')) {
			/** @var \Awyiss\Model\Behavior\CategoriesBehavior $categoriesBehavior */
			$categoriesBehavior = $table->getBehavior('Categories');
			if (
				$categoriesBehavior->getConfig('enabled') &&
				$categoriesBehavior->getConfig('foreignKey') === $column
			) {
				return $categoriesBehavior->getCategories();
			}
		}

		if ($table->hasBehavior('Nest')) {
			/** @var \Awyiss\Model\Behavior\NestBehavior $nestBehavior */
			$nestBehavior = $table->getBehavior('Nest');
			if (
				$nestBehavior->getConfig('enabled') &&
				$nestBehavior->getConfig('parent.foreignKey') === $column
			) {
				$query = $table->find('all');

				if (
					in_array($table->getAlias(), ['Contents', 'GlobalContents']) &&
					$table->hasAssociation('MediaAssignments')
				) {
					$query->find('mediaAssignments', useMediaEntity: true);
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $nestBehavior->listNested($query)->printer('label', 'id', '- ')->toArray();
			}
		}

		if ($column === 'language_shortcode') {
			$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);

			return array_column($languages, 'label', 'shortcode');
		}

		$attributesBehavior = $this->table()->hasBehavior('Attributes') ? $table->getBehavior('Attributes') : null;
		if ($attributesBehavior && $attributesBehavior->getConfig('isAttributesTable')) {
			$attributeOptions = AttributeOptionsProvider::getAttributeOptionsFile(
				$attributesBehavior->getConfig('sourceTable'),
				true
			)?->getAttributeOption($column);

			if ($attributeOptions) {
				return $attributeOptions->getOptions(true) ?? [];
			}
		}

		if (in_array($column, ['created_by', 'changed_by'])) {
			return $this->getUsers();
		}

		// Try to get the type from the table schema if not provided
		if (!$type && $table->getSchema()->hasColumn($column)) {
			$type = $table->getSchema()->getColumnType($column);
		}

		if ($type && str_starts_with($type, 'enum-')) {
			$dbType = TypeFactory::build($type);
			if ($dbType instanceof EnumType) {
				return $this->getEnumValues($dbType);
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
	 * @param array|null $filterColumns
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $query, ?array $filterColumns = null): SelectQuery {
		$filterColumns ??= $this->getFilterColumns([], null, null, false);

		if (!$filterColumns) {
			return $query;
		}

		foreach ($filterColumns as $column => $columnSettings) {
			// If the operator is null and the type is not boolean,
			// or the type is boolean and the value is null, skip this column
			if (
				$columnSettings->operator === null &&
				(
					$columnSettings->type !== 'boolean' ||
					$columnSettings->value === null
				)
			) {
				continue;
			}

			$this->addFilterCondition($query, $column, $columnSettings);
		}

		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addFilterCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings): SelectQuery {
		$operator = $columnSettings->operator ?? '=';
		if (is_array($columnSettings->disabledOperators) && in_array($operator, $columnSettings->disabledOperators)) {
			return $query;
		}

		if ($columnSettings->type === 'boolean' && $columnSettings->value === null) {
			return $query;
		}

		if (str_starts_with($column, 'attributes__')) {
			$column = $this->table()->getAttributesTableName(true) . '.' . substr($column, 12);
		}
		elseif (!str_contains('.', $column)) {
			$column = $query->getRepository()->getAlias() . '.' . $column;
		}

		return match ($operator) {
			'=' => $this->addEqualsCondition($query, $column, $columnSettings),
			'!=' => $this->addEqualsCondition($query, $column, $columnSettings, true),
			'<' => $this->addGreaterThanCondition($query, $column, $columnSettings, false, true),
			'<=' => $this->addGreaterThanCondition($query, $column, $columnSettings, true, true),
			'>' => $this->addGreaterThanCondition($query, $column, $columnSettings),
			'>=' => $this->addGreaterThanCondition($query, $column, $columnSettings, true),
			'between' => $this->addBetweenCondition($query, $column, $columnSettings),
			'not_between' => $this->addBetweenCondition($query, $column, $columnSettings, true),
			'length_equal' => $this->addLengthEqualToCondition($query, $column, $columnSettings),
			'length_not_equal' => $this->addLengthEqualToCondition($query, $column, $columnSettings, true),
			'shorter_than' => $this->addLongerThanCondition($query, $column, $columnSettings, false, true),
			'shorter_than_or_equal' => $this->addLongerThanCondition($query, $column, $columnSettings, true, true),
			'longer_than' => $this->addLongerThanCondition($query, $column, $columnSettings),
			'longer_than_or_equal' => $this->addLongerThanCondition($query, $column, $columnSettings, true),
			'in' => $this->addInCondition($query, $column, $columnSettings),
			'not_in' => $this->addInCondition($query, $column, $columnSettings, true),
			'contains' => $this->addContainsCondition($query, $column, $columnSettings),
			'not_contains' => $this->addContainsCondition($query, $column, $columnSettings, true),
			'starts_with' => $this->addContainsCondition($query, $column, $columnSettings, false, 'end'),
			'not_starts_with' => $this->addContainsCondition($query, $column, $columnSettings, true, 'end'),
			'ends_with' => $this->addContainsCondition($query, $column, $columnSettings, false, 'start'),
			'not_ends_with' => $this->addContainsCondition($query, $column, $columnSettings, true, 'start'),
			'since_last_login' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::SinceLastLogin),
			'last_24_hours' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::Last24Hours),
			'today' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::Today),
			'yesterday' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::Yesterday),
			'this_week' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::ThisWeek),
			'last_week' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::LastWeek),
			'this_month' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::ThisMonth),
			'last_month' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::LastMonth),
			'this_year' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::ThisYear),
			'last_year' => $this->addDateComparisonCondition($query, $column, DateComparisonOperator::LastYear),
			default => $query,
		};
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addEqualsCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $not = false): SelectQuery {
		$value = $this->normalizeValue($columnSettings->value, $columnSettings);

		if ($value === null) {
			$operator = $not ? ' IS NOT' : ' IS';
		}
		else {
			$operator = $not ? ' !=' : '';

			if ($not && $this->getConfig('handleNulls', true) === true) {
				/**
				 * If the value is not null and the operator is "not equal",
				 * null values would not be included in the result set, even though
				 * we want to find all records that do not match a specific value.
				 * So we add a second condition to include null values.
				 */
				return $query->where([
					'OR' => [
						$column . $operator => $value,
						$column . ' IS' => null,
					],
				]);
			}
		}

		return $query->where([
			$column . $operator => $value,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $orEqual
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addGreaterThanCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $orEqual = false, bool $not = false): SelectQuery {
		$value = $this->normalizeValue($columnSettings->value, $columnSettings);

		if ($value === null) {
			return $query;
		}

		$operator = $not ? ' <' : ' >';
		if ($orEqual) {
			$operator = $not ? ' <=' : ' >=';
		}

		if ($not && $this->getConfig('handleNulls', true) === true) {
			/**
			 * If the operator is "lower than",
			 * null values would not be included in the result set, even though
			 * we want to find all records that have a value lower than a specific value.
			 * So we add a second condition to include null values.
			 */
			return $query->where([
				'OR' => [
					$column . $operator => $value,
					$column . ' IS' => null,
				],
			]);
		}

		return $query->where([
			$column . $operator => $value,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addBetweenCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $not = false): SelectQuery {
		$values = $columnSettings->value ?? [];

		if (!is_array($values)) {
			$values = explode(',', $values);
		}

		// Trim and remove non-numeric values
		$values = array_map(function (mixed $value): mixed {
			if ($value === null) {
				return '';
			}

			return is_string($value) ? trim($value) : $value;
		}, $values);
		$values = array_filter($values, fn ($value) => is_numeric($value));

		if (!$values || count($values) !== 2) {
			return $query;
		}

		// Normalize all
		$values = array_map(fn ($value) => $this->normalizeValue($value, $columnSettings), $values);

		if ($not) {
			$where = [
				$column . ' <' => $values[0],
				$column . ' >' => $values[1],
			];

			if ($this->getConfig('handleNulls', true) === true) {
				/**
				 * If the operator is "not between",
				 * null values would not be included in the result set, even though
				 * we want to find all records that do not match a specific range.
				 * So we add a third condition to include null values.
				 */
				$where[ $column . ' IS' ] = null;
			}

			return $query->where(['OR' => $where]);
		}

		return $query->where([
			$column . ' >=' => $values[0],
			$column . ' <=' => $values[1],
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addLengthEqualToCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $not = false): SelectQuery {
		if ($columnSettings->value === null) {
			return $query;
		}

		$value = (int)$columnSettings->value;

		if (
			$this->getConfig('handleNulls', true) === true &&
			(
				($not && $value !== 0) ||
				(!$not && $value === 0)
			)
		) {
			/**
			 * If the operator is
			 * - "length not equal" and the value is not "0" or
			 * - "length equal" and the value is "0",
			 * null values would not be included in the result set, even though
			 * we want to find all records that do/don't match a specific length.
			 * So we add a second condition to include null values.
			 */
			return $query->where([
				'OR' => [
					function (QueryExpression $exp) use ($column, $query, $value, $not) {
						/** @noinspection PhpUndefinedMethodInspection */
						$lengthExp = $query->func()->length([$column => 'identifier']);

						return $not ? $exp->notEq($lengthExp, $value, 'integer') : $exp->eq($lengthExp, $value, 'integer');
					},
					$column . ' IS' => null,
				],
			]);
		}

		return $query->where(function (QueryExpression $exp) use ($column, $query, $value, $not) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lengthExp = $query->func()->length([$column => 'identifier']);

			return $not ? $exp->notEq($lengthExp, $value, 'integer') : $exp->eq($lengthExp, $value, 'integer');
		});
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $orEqual
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addLongerThanCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $orEqual = false, bool $not = false): SelectQuery {
		if ($columnSettings->value === null) {
			return $query;
		}

		$value = (int)$columnSettings->value;

		$operator = $not ? ' <' : ' >';
		if ($orEqual) {
			$operator = $not ? ' <=' : ' >=';
		}

		if ($this->getConfig('handleNulls', true) === true) {
			if (!$not && $orEqual && $value === 0) {
				/**
				 * If the operator is "longer than or equal" and the value is "0",
				 * null values would not be included in the result set, even though
				 * we want to find all records that have a length greater than or equal to "0".
				 * So we add a second condition to include null values.
				 */
				return $query->where([
					'OR' => [
						function (QueryExpression $exp) use ($column, $query, $value, $operator) {
							/** @noinspection PhpUndefinedMethodInspection */
							$lengthExp = $query->func()->length([$column => 'identifier']);

							return $exp->gte($lengthExp, $value, 'integer');
						},
						$column . ' IS' => null,
					],
				]);
			}

			if ($not && ($value !== 0 || $orEqual)) {
				/**
				 * If the operator is
				 * - "shorter than" and the value is "0" or
				 * - "shorter than or equal" and the value is "0",
				 * null values would not be included in the result set, even though
				 * we want to find all records that have a length shorter than a specific value.
				 * So we add a second condition to include null values.
				 */
				return $query->where([
					'OR' => [
						function (QueryExpression $exp) use ($column, $query, $value, $operator) {
							/** @noinspection PhpUndefinedMethodInspection */
							$lengthExp = $query->func()->length([$column => 'identifier']);

							return match ($operator) {
								' <' => $exp->lt($lengthExp, $value, 'integer'),
								' <=' => $exp->lte($lengthExp, $value, 'integer'),
							};
						},
						$column . ' IS' => null,
					],
				]);
			}
		}


		return $query->where(function (QueryExpression $exp) use ($column, $query, $value, $operator) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lengthExp = $query->func()->length([$column => 'identifier']);

			return match ($operator) {
				' >' => $exp->gt($lengthExp, $value, 'integer'),
				' >=' => $exp->gte($lengthExp, $value, 'integer'),
				' <' => $exp->lt($lengthExp, $value, 'integer'),
				' <=' => $exp->lte($lengthExp, $value, 'integer'),
				default => $exp,
			};
		});
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $not
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addInCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $not = false): SelectQuery {
		$values = $columnSettings->value ?? [];

		if (!is_array($values)) {
			$values = explode(',', $values);
		}

		// Trim and remove duplicate values
		$values = array_map(function (mixed $value): mixed {
			if ($value === null) {
				return '';
			}

			return is_string($value) ? trim($value) : $value;
		}, $values);
		$values = array_unique($values);

		if (!$values) {
			return $query;
		}

		$hasEmpty = in_array('', $values, true);

		// Normalize all
		$values = array_map(fn ($value) => $this->normalizeValue($value, $columnSettings), $values);

		$operator = $not ? ' NOT IN' : ' IN';
		if (
			($not && !$hasEmpty) ||
			(!$not && $hasEmpty)
		) {
			/**
			 * If the operator is
			 * - "in" and there are no empty values or
			 * - "not in" and there are empty values,
			 * null values would not be included in the result set, even though
			 * we want to find all records that do/don't match a set of values.
			 * So we add a second condition to include null values.
			 */
			return $query->where([
				'OR' => [
					$column . $operator => $values,
					$column . ' IS' => null,
				],
			]);
		}

		return $query->where([
			$column . $operator => $values,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @param bool $not
	 * @param string $wildcard
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addContainsCondition(SelectQuery $query, string $column, FilterColumnSettings $columnSettings, bool $not = false, string $wildcard = 'both'): SelectQuery {
		$value = $this->normalizeValue($columnSettings->value, $columnSettings);

		if (!in_array($wildcard, ['both', 'start', 'end'])) {
			$wildcard = 'both';
		}

		if ($value === null) {
			return $query;
		}

		$expression = '%' . $value . '%';
		if ($wildcard === 'start') {
			$expression = '%' . $value;
		}
		elseif ($wildcard === 'end') {
			$expression = $value . '%';
		}

		if ($not) {
			/**
			 * If the operator is "not contains",
			 * null values would not be included in the result set, even though
			 * we want to find all records that do not match a specific value.
			 * So we add a second condition to include null values.
			 */
			return $query->where([
				'OR' => [
					function (QueryExpression $exp) use ($column, $expression) {
						return $exp->notLike($column, $expression, 'string');
					},
					$column . ' IS' => null,
				],
			]);
		}

		return $query->where(function (QueryExpression $exp) use ($column, $expression) {
			return $exp->like($column, $expression, 'string');
		});
	}


	/**
	 * @param \Cake\Database\Type\EnumType $dbType
	 * @return array
	 */
	protected function getEnumValues(EnumType $dbType): array {
		$enumClass = $dbType->getEnumClassName();

		$values = [];

		foreach ($enumClass::cases() as $case) {
			if ($case instanceof EnumLabelInterface) {
				$values[ $case->value ] = $case->label();
			}
			elseif ($case instanceof BackedEnum) {
				$values[ $case->value ] = $case->name;
			}
			else {
				$values[ $case->name ] = $case->name;
			}
		}

		return $values;
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
		$operators = [];

		if ($type === 'text') {
			$operators = [
				ComparisonOperator::LessThan,
				ComparisonOperator::LessThanOrEqual,
				ComparisonOperator::GreaterThan,
				ComparisonOperator::GreaterThanOrEqual,
				ComparisonOperator::Between,
				ComparisonOperator::NotBetween,
			];
		}

		if (in_array($type, ['date', 'time', 'datetime'])) {
			$operators = [
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

		if ($operators) {
			return array_map(function (ComparisonOperator $operator) {
				return $operator->value;
			}, $operators);
		}


		return null;
	}


	/**
	 * @param mixed $value
	 * @param \Awyiss\Model\Behavior\Search\FilterColumnSettings $columnSettings
	 * @return mixed|string
	 */
	protected function normalizeValue(mixed $value, FilterColumnSettings $columnSettings): mixed {
		if (!$columnSettings->nullable && $value === null) {
			$value = '';
		}

		if ($value === null) {
			return null;
		}

		if ($columnSettings->type === 'boolean') {
			if ($columnSettings->nullable && empty($value)) {
				return null;
			}

			return $value !== '0';
		}

		if ($columnSettings->type === 'float') {
			return (float)str_replace(',', '.', (string)$value);
		}

		if ($columnSettings->type === 'integer') {
			return (int)$value;
		}

		return $value;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @param \Awyiss\Model\Enum\DateComparisonOperator $dateComparisonOperator
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addDateComparisonCondition(SelectQuery $query, string $column, DateComparisonOperator $dateComparisonOperator): SelectQuery {
		$now = DateTime::now();

		return match ($dateComparisonOperator) {
			DateComparisonOperator::SinceLastLogin => $this->addSinceLastLoginCondition($query, $column),
			DateComparisonOperator::Last24Hours => $query->where([
				$column . ' >=' => $now->subHours(24),
			]),
			DateComparisonOperator::Today => $query->where([
				$column . ' >=' => $now->startOfDay(),
				$column . ' <' => $now->addDays(1)->startOfDay(),
			]),
			DateComparisonOperator::Yesterday => $query->where([
				$column . ' >=' => $now->subDays(1)->startOfDay(),
				$column . ' <' => $now->startOfDay(),
			]),
			DateComparisonOperator::ThisWeek => $query->where([
				$column . ' >=' => $now->startOfWeek(),
				$column . ' <' => $now->addWeeks(1)->startOfWeek(),
			]),
			DateComparisonOperator::LastWeek => $query->where([
				$column . ' >=' => $now->subWeeks(1)->startOfWeek(),
				$column . ' <' => $now->startOfWeek(),
			]),
			DateComparisonOperator::ThisMonth => $query->where([
				$column . ' >=' => $now->startOfMonth(),
				$column . ' <' => $now->addMonths(1)->startOfMonth(),
			]),
			DateComparisonOperator::LastMonth => $query->where([
				$column . ' >=' => $now->subMonths(1)->startOfMonth(),
				$column . ' <' => $now->startOfMonth(),
			]),
			DateComparisonOperator::ThisYear => $query->where([
				$column . ' >=' => $now->startOfYear(),
				$column . ' <' => $now->addYears(1)->startOfYear(),
			]),
			DateComparisonOperator::LastYear => $query->where([
				$column . ' >=' => $now->subYears(1)->startOfYear(),
				$column . ' <' => $now->startOfYear(),
			]),
		};
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $column
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addSinceLastLoginCondition(SelectQuery $query, string $column): SelectQuery {
		$request = Router::getRequest();
		$session = $request->getSession();
		$lastLogin = $session->read('Backend.lastLogin');

		if (!$lastLogin) {
			// If there is no last login time, return an empty result set
			return $query->where(['1 = 0']);
		}

		return $query->where([
			$column . ' >=' => $lastLogin,
		]);
	}
}
