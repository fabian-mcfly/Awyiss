<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\BootstrapColumnSystem;
use Awyiss\Utility\Inflector;
use Cake\Database\Driver\Mysql;
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
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'attributes';
	/**
	 * @var string Regex that matches "type(length)", like "varchar(255)" or "int(10,4)" or "tinyint"
	 * https://regex101.com/r/0h9ziN/1
	 */
	public const TYPE_PATTERN = '/^(\w*)(?:\((\d+(?:,\d+)*)+\)+)?$/';


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
		'date',
		'datetime',
		'time',
		'checkbox',
		'multicheckbox',
		'select',
		'select_multiple',
		'input_list',
		'input_key_value_list',
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

		$this->columnSpans = BootstrapColumnSystem::getColumnWidths();
	}


	/**
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public function buildCategories(): array {
		/** @var \Awyiss\Model\Table\DatatablesTable $lo_datatablesTable */
		$lo_datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$la_datatables = $lo_datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$la_pageRoles = $lo_pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$la_attributeScopes = [];
		foreach ($this->attributeScopes as $ls_identifier => $ls_className) {
			$ls_identifier = Inflector::underscore($ls_identifier);

			if (isset($la_pageRoles[ $ls_identifier ]) && $ls_identifier !== 'pages') {
				$la_attributeScopes[ $ls_identifier ] = $la_pageRoles[ $ls_identifier ]->label;

				continue;
			}

			if (isset($la_datatables[ $ls_identifier ])) {
				$la_attributeScopes[ $ls_identifier ] = $la_datatables[ $ls_identifier ]->label;

				continue;
			}

			$la_attributeScopes[ $ls_identifier ] = __d($ls_identifier, 'menu_title');
		}

		uasort($la_attributeScopes, function ($a, $b) {
			return strnatcasecmp($a, $b);
		});

		return $la_attributeScopes;
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(function (Attribute $entity) {
			if (!$entity->getError('scope')) {
				$lx_table = $this->getAvailableScopes()[ $entity->scope ];
				/** @var Table $lo_table */
				$lo_table = is_string($lx_table) ? FactoryLocator::get('Table')->get($lx_table) : $lx_table;
				if ($lo_table->getSchema()->getColumn($entity->identifier)) {
					return false;
				}
			}

			$la_reservedWords = [];

			$lo_connection = ConnectionManager::get('default');
			if ($lo_connection->getDriver() instanceof Mysql) {
				$la_reservedWords = [
					'accessible', 'add', 'all', 'alter', 'analyze', 'and', 'as', 'asc', 'asensitive', 'before', 'between', 'bigint', 'binary', 'blob', 'both', 'by', 'call',
					'cascade', 'case', 'change', 'char', 'character', 'check', 'collate', 'column', 'condition', 'constraint', 'continue', 'convert', 'create', 'cross',
					'current_date', 'current_role', 'current_time', 'current_timestamp', 'current_user', 'cursor', 'database', 'databases', 'day_hour', 'day_microsecond',
					'day_minute', 'day_second', 'dec', 'decimal', 'declare', 'default', 'delayed', 'delete', 'delete_domain_id', 'desc', 'describe', 'deterministic', 'distinct',
					'distinctrow', 'div', 'do_domain_ids', 'double', 'drop', 'dual', 'each', 'else', 'elseif', 'enclosed', 'escaped', 'except', 'exists', 'exit', 'explain',
					'false', 'fetch', 'float', 'float4', 'float8', 'for', 'force', 'foreign', 'from', 'fulltext', 'general', 'grant', 'group', 'having', 'high_priority',
					'hour_microsecond', 'hour_minute', 'hour_second', 'if', 'ignore', 'ignore_domain_ids', 'ignore_server_ids', 'in', 'index', 'infile', 'inner', 'inout',
					'insensitive', 'insert', 'int', 'int1', 'int2', 'int3', 'int4', 'int8', 'integer', 'intersect', 'interval', 'into', 'is', 'iterate', 'join', 'key', 'keys',
					'kill', 'leading', 'leave', 'left', 'like', 'limit', 'linear', 'lines', 'load', 'localtime', 'localtimestamp', 'lock', 'long', 'longblob', 'longtext', 'loop',
					'low_priority', 'master_heartbeat_period', 'master_ssl_verify_server_cert', 'match', 'maxvalue', 'mediumblob', 'mediumint', 'mediumtext', 'middleint',
					'minute_microsecond', 'minute_second', 'mod', 'modifies', 'natural', 'not', 'no_write_to_binlog', 'null', 'numeric', 'offset', 'on', 'optimize', 'option',
					'optionally', 'or', 'order', 'out', 'outer', 'outfile', 'over', 'page_checksum', 'parse_vcol_expr', 'partition', 'position', 'precision', 'primary',
					'procedure', 'purge', 'range', 'read', 'reads', 'read_write', 'real', 'recursive', 'ref_system_id', 'references', 'regexp', 'release', 'rename', 'repeat',
					'replace', 'require', 'resignal', 'restrict', 'return', 'returning', 'revoke', 'right', 'rlike', 'row_number', 'rows', 'schema', 'schemas',
					'second_microsecond', 'select', 'sensitive', 'separator', 'set', 'show', 'signal', 'slow', 'smallint', 'spatial', 'specific', 'sql', 'sqlexception',
					'sqlstate', 'sqlwarning', 'sql_big_result', 'sql_calc_found_rows', 'sql_small_result', 'ssl', 'starting', 'stats_auto_recalc', 'stats_persistent',
					'stats_sample_pages', 'straight_join', 'table', 'terminated', 'then', 'tinyblob', 'tinyint', 'tinytext', 'to', 'trailing', 'trigger', 'true', 'undo', 'union',
					'unique', 'unlock', 'unsigned', 'update', 'usage', 'use', 'using', 'utc_date', 'utc_time', 'utc_timestamp', 'values', 'varbinary', 'varchar', 'varcharacter',
					'varying', 'when', 'where', 'while', 'window', 'write', 'xor', 'year_month', 'zerofill',
				];
			}


			return !in_array($entity->identifier, $la_reservedWords);
		}, 'validIdentifier', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_reserved_identifier'),
		]);


		$rules->add(
			$rules->isUnique(['identifier', 'scope']),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique_for_scope'),
			]
		);


		$rules->add(function (Attribute $entity/*, array $options*/): bool {
			$la_availableFieldsets = $this->getAvailableFieldsets();


			//Check if the provided fieldset is valid. For scope `contents` and `widgets`, always return true
			return in_array($entity->fieldset, $la_availableFieldsets) || in_array($entity->scope, ['contents', 'widgets']);
		}, 'validFieldset', [
			'errorField' => 'fieldset',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_fieldset'),
		]);


		$rules->add(function (Attribute $entity/*, array $options*/): bool {
			$la_availableInputTypes = $this->getAvailableInputTypes();


			//Check if the provided scope can have attributes
			return in_array($entity->inputType, $la_availableInputTypes);
		}, 'validInputType', [
			'errorField' => 'inputType',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_input_type'),
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

		$la_classes = App::classes('*', 'Model/Table', 'Table', null, null, ['GenericDatatablesTable']);

		/** @var class-string<\Awyiss\Model\Table> $ls_className */
		foreach ($la_classes as $ls_className) {
			/**
			 * If an entry exists or if the table does not allow attributes, skip it
			 *
			 * @noinspection PhpIllegalArrayKeyTypeInspection
			 */
			if (isset($this->attributeScopes[ $ls_className::TABLE ]) || !$ls_className::ATTRIBUTABLE) {
				continue;
			}

			//If the given table is not a subclass of \Awyiss\Model\Table, skip it
			if (!is_subclass_of($ls_className, Table::class)) {
				continue;
			}

			/** @noinspection PhpIllegalArrayKeyTypeInspection */
			$this->attributeScopes[ $ls_className::TABLE ] = $ls_className;
		}
		/**
		 * Get all page roles because we want them to have attributes too
		 *
		 * @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum
		 */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_identifier = Inflector::pluralize(Inflector::underscore($le_pageRole->name));

			if ($ls_identifier === 'Pages' || isset($this->attributeScopes[ $ls_identifier ])) {
				continue;
			}

			/** @var \Awyiss\Model\Table\PagesTable $lo_pageTable */
			$lo_pageTable = FactoryLocator::get('Table')->get(Inflector::camelize($ls_identifier));

			//If an entry exists or if the table does not allow attributes, skip it
			if (!$lo_pageTable::ATTRIBUTABLE) {
				continue;
			}

			$this->attributeScopes[ $ls_identifier ] = $lo_pageTable;
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
