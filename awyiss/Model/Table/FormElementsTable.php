<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\ColumnSystem\AwyissColumnSystem;
use Awyiss\Utility\Inflector;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * FormElements Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\FormElementsTable&\Awyiss\ORM\Association\HasMany $ChildFormElements
 * @property \Awyiss\Model\Table\FormElementsTable&\Awyiss\ORM\Association\BelongsTo $ParentFormElements
 * @method \Awyiss\Model\Entity\FormElement newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\FormElement getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class FormElementsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'form_elements';


	/**
	 * @var array The available types for form elements
	 */
	protected array $availableTypes = [
		'text',
		'textarea',
		'email',
		'url',
		'number',
		'tel',
		'date',
		'time',
		'datetime',
		'range',
		'checkbox',
		'radio',
		'select',
		'selectMultiple',
		'file',
		'hidden',
		'fieldset',
		'freeText',
		'submit',
	];
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'Forms',
		'enabled' => true,
		'identifier' => 'form',
		'threaded' => false,
	];
	/**
	 * @var array The column system
	 */
	protected array $columnSystem = [
		'className' => AwyissColumnSystem::class,
		'maxColumns' => 5,
	];
	/**
	 * @var array The column widths
	 */
	protected array $columnWidths;
	/**
	 * @var array The column indents
	 */
	protected array $columnIndents;
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'enabled' => true,
		'relatedColumns' => ['formId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['formId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['formId', 'parentId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'titleEmail',
			'placeholder',
			'text',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		$this->customConfigProperties[] = 'columnSystem';

		parent::initialize($config);

		$this->initializeColumnSystem();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @param bool $translated
	 * @return array
	 */
	public function getAvailableTypes(bool $translated = false): array {
		if (!$translated) {
			return $this->availableTypes;
		}

		$types = [];

		foreach ($this->availableTypes as $type) {
			$types[ $type ] = __d($this->getI18nDomain(), 'type_' . Inflector::underscore($type));
		}

		return $types;
	}


	/**
	 * @return class-string<\Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface>
	 */
	public function getColumnSystemClass(): string {
		return $this->columnSystem['className'];
	}


	/**
	 * @return array
	 */
	public function getColumnWidths(): array {
		return $this->columnWidths;
	}


	/**
	 * @return array
	 */
	public function getColumnIndents(): array {
		return $this->columnIndents;
	}


	/**
	 * @return void
	 */
	protected function initializeColumnSystem(): void {
		// Use the column system of contents
		$this->columnSystem = array_merge($this->columnSystem, LocalConfig::read('columnSystem', [], 'Contents'));

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface> $className */
		$className = $this->columnSystem['className'];
		$className::setMaxDenominator($this->columnSystem['maxColumns']);

		$this->columnWidths = $className::getColumnWidths();
		$this->columnIndents = $className::getColumnIndents();
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'formId',
			'type',
		], 'create');

		$validator->requirePresence([
			'title',
		], function (array $context): bool {
			return ($context['data']['type'] ?? '') !== 'freeText';
		});

		$validator->requirePresence([
			'identifier',
		], function (array $context): bool {
			return !in_array($context['data']['type'] ?? '', ['freeText', 'submit']);
		});


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('formId');
		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier', null, function (array $context): bool {
			return !in_array($context['data']['type'] ?? '', ['freeText', 'submit']);
		});
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title', null, function (array $context): bool {
			return ($context['data']['type'] ?? '') !== 'freeText';
		});
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('titleEmail', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('placeholder', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('options', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->add('columnWidth', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->getColumnWidths()),
				],
			],
		]);


		$validator->add('columnIndent', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->getColumnIndents()),
				],
			],
		]);


		$validator->add('columnLast', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('columnRtl', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('cssClass', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('required', [
			'boolean' => ['rule' => 'boolean'],
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
		$rules->add($rules->isUnique(['identifier', 'formId']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique'),
		]);

		$rules->add(function (FormElement $entity/*, array $options*/): bool {
			$availableInputTypes = $this->getAvailableTypes();

			return in_array($entity->type, $availableInputTypes);
		}, 'validInputType', [
			'errorField' => 'type',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_input_type'),
		]);

		$rules->add(function (FormElement $entity): bool {
			/** @var \Awyiss\Utility\Content\ColumnSystem\ColumnInterface $width */
			$width = $entity->column['width'];
			/** @var \Awyiss\Utility\Content\ColumnSystem\ColumnInterface $indent */
			$indent = $entity->column['indent'];

			$totalWidth = $width->getPercentage() + ($indent?->getPercentage() ?? 0);

			if ($totalWidth > 1) {
				return false;
			}

			return true;
		}, 'validWidthIndentCombination', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_width_indent_combination'),
		]);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('options', 'json');
	}


	/**
	 * @return void
	 */
	public function disableCascadeCallbacks(): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildFormElements->setDependent(false)->setCascadeCallbacks(false);
	}


	/**
	 * @return void
	 */
	public function enableCascadeCallbacks(): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildFormElements->setDependent(true)->setCascadeCallbacks(true);
	}
}
