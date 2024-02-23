<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ReflectionClass;


/**
 * Attributes Model
 *
 * @method \Awyiss\Model\Entity\Attribute newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
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
		'media',
		'checkbox',
		'multicheckbox',
		'select',
		'select_multiple',
		'custom_select',
		'custom_select_multiple',
		'textarea',
		'textarea_plain',
		'password',
		'hidden',
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
	 * @return array
	 */
	public function buildCategories(): array {
		$la_attributeScopes = $this->attributeScopes;

		array_walk($la_attributeScopes, function (&$as_label, $as_identifier): void {
			$as_label = __d($as_identifier, 'title_menu');
		});

		uasort($la_attributeScopes, function ($a, $b) {
			return strnatcasecmp($a, $b);
		});

		return $la_attributeScopes;
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'scope',
			'title',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('scope');
		$ao_validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('identifier');
		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
			'typeRegex' => ['rule' => ['custom', static::TYPE_PATTERN]],
		]);


		$ao_validator->add('hasIndex', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->notEmptyString('fieldset');
		$ao_validator->add('fieldset', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('inputType', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 30]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('defaultValue', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
		]);


		$ao_validator->add('required', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('translatable', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(function (Attribute $ao_entity) {
			if (!$ao_entity->getError('scope')) {
				$lx_table = $this->getAvailableScopes()[ $ao_entity->scope ];
				/** @var Table $lo_table */
				$lo_table = is_string($lx_table) ? FactoryLocator::get('Table')->get($lx_table) : $lx_table;
				if ($lo_table->getSchema()->getColumn($ao_entity->identifier)) {
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


			return !in_array($ao_entity->identifier, $la_reservedWords);
		}, 'validIdentifier', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'attributes', 'error_reserved_identifier'),
		]);


		$ao_rules->add(
			$ao_rules->isUnique(['identifier', 'scope']),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'attributes', 'error_identifier_unique_for_scope'),
			]
		);


		$ao_rules->add(function (Attribute $ao_entity/*, array $aa_options*/): bool {
			$la_availableFieldsets = $this->getAvailableFieldsets();


			//Check if the provided fieldset is valid. For scope `contents`, always return true
			return in_array($ao_entity->fieldset, $la_availableFieldsets) || $ao_entity->scope === 'contents';
		}, 'validFieldset', [
			'errorField' => 'fieldset',
			'message' => __d($this->getI18nDomain(), 'error_valid_fieldset'),
		]);


		$ao_rules->add(function (Attribute $ao_entity/*, array $aa_options*/): bool {
			$la_availableInputTypes = $this->getAvailableInputTypes();


			//Check if the provided scope can have attributes
			return in_array($ao_entity->inputType, $la_availableInputTypes);
		}, 'validInputType', [
			'errorField' => 'inputType',
			'message' => __d($this->getI18nDomain(), 'error_valid_input_type'),
		]);


		return $ao_rules;
	}


	/**
	 * @param string|null $as_scope A scope to specify the available fieldsets.
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getAvailableFieldsets(?string $as_scope = null): array {
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
	 *
	 * @throws \ReflectionException
	 */
	public function getAvailableScopes(): array {
		if (isset($this->attributeScopes)) {
			return $this->attributeScopes;
		}

		$this->attributeScopes = [];

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Model\Table\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', '*Table.php',]),
			'\Awyiss\Model\Table\\' => implode(DS, [ROOT, APP_DIR, 'Model', 'Table', '*Table.php']),
		];

		//Traverse both namespaces
		foreach ($la_paths as $ls_namespace => $ls_path) {
			//Look for files with name "*Table.php"
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_tableName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);

				if ($ls_tableName === 'GenericDatatablesTable') {
					continue;
				}

				/** @var Table::class $ls_tableClass */
				$ls_tableClass = $ls_namespace . $ls_tableName;

				//If an entry exists or if the table does not allow attributes, skip it
				if (isset($this->attributeScopes[ $ls_tableClass::TABLE ]) || !$ls_tableClass::ATTRIBUTABLE) {
					continue;
				}

				//If the given table is not a subclass of \Awyiss\Model\Table, skip it
				$lo_reflection = new ReflectionClass($ls_tableClass);
				if (!$lo_reflection->isSubclassOf(Table::class)) {
					continue;
				}

				$this->attributeScopes[ $ls_tableClass::TABLE ] = $ls_tableClass;
			}
		}

		//Get all page roles because we want them to have attributes too
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		/** @var \Awyiss\Model\Table\PageRolesTable $lo_table */
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
}
