<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Collection\Collection;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Phinx\Db\Adapter\AdapterInterface;
use ReflectionClass;


/**
 * Attributes Model
 *
 * @method \Awyiss\Model\Entity\Configuration newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\Attribute patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class AttributesTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'attributes';
	/**
	 * @var string Regex that matches "type(length)", like "varchar(255)" or "int(10,4)" or "tinyint"
	 * https://regex101.com/r/0h9ziN/1
	 */
	protected const TYPE_PATTERN = '/^(\w*)(?:\((\d+(?:,\d+)*)+\)+)?$/';
	/**
	 * @var array
	 */
	protected array $attributeScopes;


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->scalar('name')->maxLength('name', 50)->requirePresence('name', 'create')->notEmptyString('name');

		$ao_validator->scalar('default_value')->maxLength('default_value', 100)->allowEmptyString('default_value');

		$ao_validator->scalar('scope')->maxLength('scope', 40)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->scalar('fieldset')->maxLength('fieldset', 30)->allowEmptyString('fieldset');

		$ao_validator->scalar('input_type')->maxLength('input_type', 30)->notEmptyString('input_type');

		$ao_validator->scalar('type')->maxLength('type', 20)->notEmptyString('type')->regex('type', static::TYPE_PATTERN);

		$ao_validator->boolean('has_index')->notEmptyString('has_index');

		$ao_validator->boolean('required')->notEmptyString('required');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['name', 'scope']), ['errorField' => 'filename']);

		//TODO: check if scope exists
		//TODO: check if type is valid

		return $ao_rules;
	}


	/**
	 * @throws \ReflectionException
	 */
	public function getScopes (): array {
		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Model\Table\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', '*Table.php',]),
			'\Awyiss\Model\Table\\' => implode(DS, [ROOT, APP_DIR, 'Model', 'Table', '*Table.php']),
		];

		if ( ! isset($this->attributeScopes)) {
			$this->attributeScopes = [];

			foreach ($la_paths as $ls_namespace => $ls_path) {
				foreach (glob($ls_path) as $ls_filePath) {
					$ls_tableName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
					/** @var Table::class $ls_tableClass */
					$ls_tableClass = $ls_namespace . $ls_tableName;

					if (isset($this->attributeScopes[ $ls_tableClass::TABLE ]) || ! $ls_tableClass::ATTRIBUTABLE) {
						continue;
					}

					$lo_reflection = new \ReflectionClass($ls_tableClass);

					if ( ! $lo_reflection->isSubclassOf(Table::class)) {
						continue;
					}

					$this->attributeScopes[ $ls_tableClass::TABLE ] = $ls_tableClass;
				}
			}
		}

		return $this->attributeScopes;
	}


	/**
	 * Splits strings into valid phinx column types and length
	 * 		"varchar(255)" => ['string', 255]
 	 * 		"int(10,4)" => ['integer', '10,4']
	 * 		"tinyint" => ['tinyint', NULL]
	 *
	 * If no valid type is found, 'string' is returned
	 *
	 * @param null|string $as_type
	 *
	 * @return array
	 */
	public function getTypeAndLength (?string $as_type): array {
		$ls_type = $as_type ?: 'varchar(255)';

		if (!preg_match(static::TYPE_PATTERN, $ls_type, $la_typeMatches, PREG_UNMATCHED_AS_NULL)) {
			return ['string', 255];
		}

		$lo_reflector = new ReflectionClass(AdapterInterface::class);
		$lo_collection = new Collection($lo_reflector->getConstants());

		$la_validTypes = $lo_collection->filter(function ($value, $constant) {
			return str_starts_with($constant, 'PHINX_TYPE_');
		})->toArray();

		if (empty($la_typeMatches[1]) || !in_array($la_typeMatches[1], $la_validTypes)) {
			if ($la_typeMatches[1] == 'int') {
				$la_typeMatches[1] = 'integer';
			}
			elseif ($la_typeMatches[1] == 'tinyint') {
				$la_typeMatches[1] = 'tinyinteger';
			}
			else {
				$la_typeMatches[1] = 'string';
			}
		}

		return [$la_typeMatches[1], $la_typeMatches[2] ?: NULL];
	}
}
