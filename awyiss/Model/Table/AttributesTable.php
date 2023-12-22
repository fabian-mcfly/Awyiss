<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ReflectionClass;


/**
 * Attributes Model
 *
 * @method Attribute newDefaultEntity(array $aa_additionalData = [])
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
	 * @todo change this from a protected property to something that can be extended. Maybe even different fieldsets per controller
	 * @var array
	 */
	protected array $availableFieldsets = [
		'presentation',
		'conditions',
		'general',
		'content',
		'media',
		'attributes',
	];
	/**
	 * @todo change this from a protected property to something that can be extended.
	 * @var array
	 */
	protected array $availableInputTypes = [
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
	 * @var array
	 */
	protected array $attributeScopes;
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
		$ao_rules->add(function (Attribute $ao_entity/*, array $aa_options*/): bool {
			//Check if the provided scope can have attributes
			return in_array($ao_entity->scope, array_keys($this->getAvailableScopes()));
		}, 'validScope', [
			'errorField' => 'scope',
			'message' => __d($this->getI18nDomain(), 'error_valid_scope'),
		]);


		$ao_rules->add(function (Attribute $ao_entity) {
			if (!$ao_entity->getError('scope')) {
				/** @var Table $lo_table */
				$lo_table = FactoryLocator::get('Table')->get($this->getAvailableScopes()[ $ao_entity->scope ]);
				if ($lo_table->getSchema()->getColumn($ao_entity->identifier)) {
					return false;
				}
			}

			$la_reservedWords = [];

			$lo_connection = ConnectionManager::get('default');
			if ($lo_connection->getDriver() instanceof Mysql) {
				$la_reservedWords = [
					'accessible',
					'add',
					'all',
					'alter',
					'analyze',
					'and',
					'as',
					'asc',
					'asensitive',
					'before',
					'between',
					'bigint',
					'binary',
					'blob',
					'both',
					'by',
					'call',
					'cascade',
					'case',
					'change',
					'char',
					'character',
					'check',
					'collate',
					'column',
					'condition',
					'constraint',
					'continue',
					'convert',
					'create',
					'cross',
					'current_date',
					'current_role',
					'current_time',
					'current_timestamp',
					'current_user',
					'cursor',
					'database',
					'databases',
					'day_hour',
					'day_microsecond',
					'day_minute',
					'day_second',
					'dec',
					'decimal',
					'declare',
					'default',
					'delayed',
					'delete',
					'delete_domain_id',
					'desc',
					'describe',
					'deterministic',
					'distinct',
					'distinctrow',
					'div',
					'do_domain_ids',
					'double',
					'drop',
					'dual',
					'each',
					'else',
					'elseif',
					'enclosed',
					'escaped',
					'except',
					'exists',
					'exit',
					'explain',
					'false',
					'fetch',
					'float',
					'float4',
					'float8',
					'for',
					'force',
					'foreign',
					'from',
					'fulltext',
					'general',
					'grant',
					'group',
					'having',
					'high_priority',
					'hour_microsecond',
					'hour_minute',
					'hour_second',
					'if',
					'ignore',
					'ignore_domain_ids',
					'ignore_server_ids',
					'in',
					'index',
					'infile',
					'inner',
					'inout',
					'insensitive',
					'insert',
					'int',
					'int1',
					'int2',
					'int3',
					'int4',
					'int8',
					'integer',
					'intersect',
					'interval',
					'into',
					'is',
					'iterate',
					'join',
					'key',
					'keys',
					'kill',
					'leading',
					'leave',
					'left',
					'like',
					'limit',
					'linear',
					'lines',
					'load',
					'localtime',
					'localtimestamp',
					'lock',
					'long',
					'longblob',
					'longtext',
					'loop',
					'low_priority',
					'master_heartbeat_period',
					'master_ssl_verify_server_cert',
					'match',
					'maxvalue',
					'mediumblob',
					'mediumint',
					'mediumtext',
					'middleint',
					'minute_microsecond',
					'minute_second',
					'mod',
					'modifies',
					'natural',
					'not',
					'no_write_to_binlog',
					'null',
					'numeric',
					'offset',
					'on',
					'optimize',
					'option',
					'optionally',
					'or',
					'order',
					'out',
					'outer',
					'outfile',
					'over',
					'page_checksum',
					'parse_vcol_expr',
					'partition',
					'position',
					'precision',
					'primary',
					'procedure',
					'purge',
					'range',
					'read',
					'reads',
					'read_write',
					'real',
					'recursive',
					'ref_system_id',
					'references',
					'regexp',
					'release',
					'rename',
					'repeat',
					'replace',
					'require',
					'resignal',
					'restrict',
					'return',
					'returning',
					'revoke',
					'right',
					'rlike',
					'row_number',
					'rows',
					'schema',
					'schemas',
					'second_microsecond',
					'select',
					'sensitive',
					'separator',
					'set',
					'show',
					'signal',
					'slow',
					'smallint',
					'spatial',
					'specific',
					'sql',
					'sqlexception',
					'sqlstate',
					'sqlwarning',
					'sql_big_result',
					'sql_calc_found_rows',
					'sql_small_result',
					'ssl',
					'starting',
					'stats_auto_recalc',
					'stats_persistent',
					'stats_sample_pages',
					'straight_join',
					'table',
					'terminated',
					'then',
					'tinyblob',
					'tinyint',
					'tinytext',
					'to',
					'trailing',
					'trigger',
					'true',
					'undo',
					'union',
					'unique',
					'unlock',
					'unsigned',
					'update',
					'usage',
					'use',
					'using',
					'utc_date',
					'utc_time',
					'utc_timestamp',
					'values',
					'varbinary',
					'varchar',
					'varcharacter',
					'varying',
					'when',
					'where',
					'while',
					'window',
					'write',
					'xor',
					'year_month',
					'zerofill',
				];
			}


			return !in_array($ao_entity->identifier, $la_reservedWords);
		}, 'validIdentifier', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'attributes', 'error_reserved_identifier'),
		]);


		$ao_rules->add($ao_rules->isUnique([
			'identifier',
			'scope',
		]), 'identifierUniqueForScope', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'attributes', 'error_identifier_unique'),
		]);


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
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Attribute $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, Attribute|EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ($ao_entity->scope === 'contents') {
			//For contents, the content template decides where an attribute will go
			$ao_entity->fieldset = '';
			//For contents, the content template decides whether an attribute is required
			$ao_entity->required = false;
		}

		$la_pageRoles = array_keys(array_filter($this->getAvailableScopes(), function ($ax_table) {
			return !is_string($ax_table);
		}));

		//Contents, Menu Entries and all types of pages don't need to have translatable attributes since they all are translations themselves
		if (in_array($ao_entity->scope, array_merge(['contents', 'menu_entries', 'pages'], $la_pageRoles))) {
			$ao_entity->translatable = false;
		}
	}


	/**
	 * @param string|null $as_scope A scope to specify the available fieldsets.
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getAvailableFieldsets(?string $as_scope = null): array {
		return $this->availableFieldsets;
	}


	/**
	 * @return array
	 */
	public function getAvailableInputTypes(): array {
		return $this->availableInputTypes;
	}


	/**
	 * Returns all available scopes that can have attributes.
	 *
	 * @throws \ReflectionException
	 */
	public function getAvailableScopes(): array {
		if (!isset($this->attributeScopes)) {
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
					/** @var Table::class $ls_tableClass */
					$ls_tableClass = $ls_namespace . $ls_tableName;

					//dump($ls_tableClass, $ls_tableClass::TABLE, $ls_tableClass::ATTRIBUTABLE);

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
		}


		//Get all page roles from the database because we want them to have policies too
		$lo_pageRoles = FactoryLocator::get('Table')->get('PageRoles')->find('active')->all();

		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRoles as $lo_pageRole) {
			$ls_identifier = Inflector::pluralize($lo_pageRole->identifier);
			/** @var PagesTable $lo_newTable */
			$lo_newTable = FactoryLocator::get('Table')->get($ls_identifier);

			//If an entry exists or if the table does not allow attributes, skip it
			if (isset($this->attributeScopes[ $ls_identifier ]) || !$lo_newTable::ATTRIBUTABLE) {
				continue;
			}

			$this->attributeScopes[ $ls_identifier ] = $lo_newTable;
		}

		ksort($this->attributeScopes);


		return $this->attributeScopes;
	}
}
