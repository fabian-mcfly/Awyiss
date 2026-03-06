<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Content\BackendColumnSystem;
use Awyiss\Utility\Inflector;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Attributes Model
 *
 * @method \Awyiss\Model\Entity\Attribute newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class AttributesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes';
	/**
	 * @var string Regex that matches "type(length)", like "varchar(255)" or "int(10,4)" or "tinyint"
	 * https://regex101.com/r/0h9ziN/1
	 */
	public const string TYPE_PATTERN = '/^(\w*)(?:\((\d+(?:,\d+)*)+\)+)?$/';


	/**
	 * @var array All reserved words organized by database type
	 */
	protected static array $reservedWords = [
		'common' => [
			'add', 'all', 'alter', 'analyze', 'and', 'as', 'asc', 'before', 'between', 'by',
			'cascade', 'case', 'check', 'collate', 'column', 'constraint', 'create', 'cross',
			'current_date', 'current_time', 'current_timestamp', 'default', 'delete', 'desc', 'distinct',
			'drop', 'each', 'else', 'except', 'exists', 'explain', 'false', 'for', 'foreign', 'from',
			'group', 'having', 'if', 'in', 'index', 'inner', 'insert', 'intersect', 'into', 'is',
			'join', 'left', 'like', 'limit', 'match', 'natural', 'not', 'null', 'on', 'or', 'order',
			'outer', 'primary', 'references', 'regexp', 'release', 'rename', 'replace', 'right',
			'select', 'set', 'table', 'then', 'to', 'trigger', 'true', 'union', 'unique', 'update',
			'using', 'values', 'when', 'where',
		],
		'mysql' => [
			'accessible', 'asensitive', 'bigint', 'binary', 'blob', 'both', 'call', 'change', 'char',
			'character', 'condition', 'continue', 'convert', 'current_role', 'current_user', 'cursor',
			'database', 'databases', 'day_hour', 'day_microsecond', 'day_minute', 'day_second', 'dec',
			'decimal', 'declare', 'delayed', 'delete_domain_id', 'describe', 'deterministic', 'distinctrow',
			'div', 'do_domain_ids', 'double', 'dual', 'elseif', 'enclosed', 'escaped', 'exit', 'fetch',
			'float', 'float4', 'float8', 'force', 'fulltext', 'general', 'grant', 'high_priority',
			'hour_microsecond', 'hour_minute', 'hour_second', 'ignore', 'ignore_domain_ids', 'ignore_server_ids',
			'infile', 'inout', 'insensitive', 'int', 'int1', 'int2', 'int3', 'int4', 'int8', 'integer',
			'interval', 'iterate', 'key', 'keys', 'kill', 'leading', 'leave', 'linear', 'lines', 'load',
			'localtime', 'localtimestamp', 'lock', 'long', 'longblob', 'longtext', 'loop', 'low_priority',
			'master_heartbeat_period', 'master_ssl_verify_server_cert', 'maxvalue', 'mediumblob', 'mediumint',
			'mediumtext', 'middleint', 'minute_microsecond', 'minute_second', 'mod', 'modifies', 'no_write_to_binlog',
			'numeric', 'offset', 'optimize', 'option', 'optionally', 'out', 'outfile', 'over', 'page_checksum',
			'parse_vcol_expr', 'partition', 'position', 'precision', 'procedure', 'purge', 'range', 'read',
			'reads', 'read_write', 'real', 'recursive', 'ref_system_id', 'repeat', 'require', 'resignal',
			'restrict', 'return', 'returning', 'revoke', 'rlike', 'row_number', 'rows', 'schema', 'schemas',
			'second_microsecond', 'sensitive', 'separator', 'show', 'signal', 'slow', 'smallint', 'spatial',
			'specific', 'sql', 'sqlexception', 'sqlstate', 'sqlwarning', 'sql_big_result', 'sql_calc_found_rows',
			'sql_small_result', 'ssl', 'starting', 'stats_auto_recalc', 'stats_persistent', 'stats_sample_pages',
			'straight_join', 'terminated', 'tinyblob', 'tinyint', 'tinytext', 'trailing', 'undo', 'unlock',
			'unsigned', 'usage', 'use', 'utc_date', 'utc_time', 'utc_timestamp', 'varbinary', 'varchar',
			'varcharacter', 'varying', 'while', 'window', 'write', 'xor', 'year_month', 'zerofill',
		],
		'sqlite' => [
			'abort', 'action', 'after', 'attach', 'autoincrement', 'begin', 'cast', 'commit', 'conflict',
			'deferrable', 'deferred', 'detach', 'end', 'escape', 'exclusive', 'fail', 'glob',
			'immediate', 'indexed', 'initially', 'instead', 'isnull', 'notnull', 'of',
			'plan', 'pragma', 'query', 'raise', 'reindex', 'rollback', 'row',
			'savepoint', 'temp', 'temporary', 'transaction', 'vacuum', 'view', 'virtual', 'with', 'without',
		],
	];


	/**
	 * @var array
	 */
	protected array $availableFieldsets;
	/**
	 * @var array
	 */
	protected array $availableInputTypes;
	/**
	 * @var array
	 */
	protected array $attributeScopes;
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'enabled' => true,
		'identifier' => 'scope',
		'useDatasource' => false,
	];
	/**
	 * @var array The column widths
	 */
	protected array $columnSpans;
	/**
	 * @var array
	 */
	protected array $defaultAvailableFieldsets = [
		'presentation',
		'conditions',
		'general',
		'content',
		'media',
		'attributes',
	];
	/**
	 * @var array
	 */
	protected array $defaultAvailableInputTypes = [
		'text',
		'color',
		'date',
		'datetime',
		'time',
		'checkbox',
		'multicheckbox',
		'select',
		'selectMultiple',
		'inputList',
		'inputKeyValueList',
		'textarea',
		'texteditor',
		'password',
		'hidden',
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['scope'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['scope', 'fieldset'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->columnSpans = BackendColumnSystem::getColumnWidths();
	}


	/**
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public function buildCategories(): array {
		/** @var \Awyiss\Model\Table\DatatablesTable $datatablesTable */
		$datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$datatables = $datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$pageRoles = $pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::camelize(Inflector::pluralize($pageRole->identifier));
		})->toArray();

		$attributeScopes = [];
		if (!isset($this->attributeScopes)) {
			$this->getAvailableScopes();
		}

		foreach ($this->attributeScopes as $identifier => $className) {
			if (isset($pageRoles[ $identifier ]) && $identifier !== 'Pages') {
				$attributeScopes[ $identifier ] = $pageRoles[ $identifier ]->label;

				continue;
			}

			if (isset($datatables[ $identifier ])) {
				$attributeScopes[ $identifier ] = $datatables[ $identifier ]->label;

				continue;
			}

			$attributeScopes[ $identifier ] = __d($identifier, 'menu_title');
		}

		Arrays::naturalSort($attributeScopes);

		return $attributeScopes;
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'scope',
			'title',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
			'typeRegex' => ['rule' => ['custom', static::TYPE_PATTERN]],
		]);


		$validator->add('hasIndex', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('fieldset');
		$validator->add('fieldset', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('inputType', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 30]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('defaultValue', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
		]);


		$validator->add('required', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('translatable', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('columnSpan', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->columnSpans),
				],
			],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(function (Attribute $entity) {
			if (!$entity->getError('scope')) {
				$table = $this->getAvailableScopes()[ $entity->scope ];
				$table = is_string($table) ? FactoryLocator::get('Table')->get($table) : $table;
				/** @var \Awyiss\Model\Table $table */
				if ($table->getSchema()->getColumn($entity->identifier)) {
					return false;
				}
			}

			$reservedWords = static::$reservedWords['common'];

			$connection = ConnectionManager::get('default');
			if ($connection->getDriver() instanceof Mysql) {
				$reservedWords = array_merge($reservedWords, static::$reservedWords['mysql']);
			}
			elseif ($connection->getDriver() instanceof Sqlite) {
				$reservedWords = array_merge($reservedWords, static::$reservedWords['sqlite']);
			}

			return !in_array($entity->identifier, $reservedWords);
		}, 'validIdentifier', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_reserved_identifier'),
		]);


		$rules->add(
			$rules->isUnique(['identifier', 'scope']),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique_for_scope'),
			]
		);


		$rules->add(function (Attribute $entity/*, array $options*/): bool {
			$availableFieldsets = $this->getAvailableFieldsets();


			//Check if the provided fieldset is valid. For scope `contents` and `global_contents`, always return true
			return in_array($entity->fieldset, $availableFieldsets) || in_array($entity->scope, ['Contents', 'GlobalContents']);
		}, 'validFieldset', [
			'errorField' => 'fieldset',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_fieldset'),
		]);


		$rules->add(function (Attribute $entity/*, array $options*/): bool {
			$availableInputTypes = $this->getAvailableInputTypes();


			//Check if the provided scope can have attributes
			return in_array($entity->inputType, $availableInputTypes);
		}, 'validInputType', [
			'errorField' => 'inputType',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_input_type'),
		]);


		return $rules;
	}


	/**
	 * @param string|null $scope A scope to specify the available fieldsets.
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getAvailableFieldsets(?string $scope = null): array {
		if (!isset($this->availableFieldsets)) {
			$this->availableFieldsets = $this->defaultAvailableFieldsets;
		}

		return $this->availableFieldsets;
	}


	/**
	 * @return array
	 */
	public function getAvailableInputTypes(): array {
		if (!isset($this->availableInputTypes)) {
			$this->availableInputTypes = $this->defaultAvailableInputTypes;
		}

		return $this->availableInputTypes;
	}


	/**
	 * Returns all available scopes that can have attributes.
	 */
	public function getAvailableScopes(): array {
		if (isset($this->attributeScopes)) {
			return $this->attributeScopes;
		}

		$classes = App::classes('*', 'Model/Table', 'Table', null, null, ['GenericDatatablesTable']);

		/** @var class-string<\Awyiss\Model\Table> $className */
		foreach ($classes as $className) {
			/**
			 * If an entry exists or if the table does not allow attributes, skip it
			 *
			 * @noinspection PhpIllegalArrayKeyTypeInspection
			 */
			if (isset($this->attributeScopes[ $className::TABLE ]) || !$className::ATTRIBUTABLE) {
				continue;
			}

			//If the given table is not a subclass of \Awyiss\Model\Table, skip it
			if (!is_subclass_of($className, Table::class)) {
				continue;
			}

			/** @noinspection PhpParamsInspection, PhpStrictTypeCheckingInspection */
			$this->attributeScopes[ Inflector::camelize($className::TABLE) ] = $className;
		}
		/**
		 * Get all page roles because we want them to have attributes too
		 *
		 * @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum
		 */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$identifier = Inflector::camelize(Inflector::pluralize($pageRole->name));

			if ($identifier === 'Pages' || isset($this->attributeScopes[ $identifier ])) {
				continue;
			}

			/** @var \Awyiss\Model\Table\PagesTable $pageTable */
			$pageTable = FactoryLocator::get('Table')->get(Inflector::camelize($identifier));

			//If an entry exists or if the table does not allow attributes, skip it
			if (!$pageTable::ATTRIBUTABLE) {
				continue;
			}

			$this->attributeScopes[ $identifier ] = $pageTable;
		}

		ksort($this->attributeScopes);


		return $this->attributeScopes;
	}


	/**
	 * @return array
	 */
	public function getColumnSpans(): array {
		return $this->columnSpans;
	}
}
