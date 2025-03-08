<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\AwyissColumnSystem;
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
 */
class FormElementsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'form_elements';


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
		'select_multiple',
		'file',
		'hidden',
		'fieldset',
		'free_text',
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
	protected array $systemOrder = [
		'relatedColumns' => ['formId', 'parentId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'title_email',
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
			'foreignKey' => 'form_id',
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

		$la_types = [];

		foreach ($this->availableTypes as $ls_type) {
			$la_types[ $ls_type ] = __d('form_elements', 'type_' . $ls_type);
		}

		return $la_types;
	}


	/**
	 * @return class-string<\Awyiss\Utility\Content\ColumnSystemInterface>
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

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_className */
		$ls_className = $this->columnSystem['className'];
		$ls_className::setMaxDenominator($this->columnSystem['maxColumns']);

		$this->columnWidths = $ls_className::getColumnWidths();
		$this->columnIndents = $ls_className::getColumnIndents();
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
			return ($context['data']['type'] ?? '') !== 'free_text';
		});

		$validator->requirePresence([
			'identifier',
		], function (array $context): bool {
			return !in_array($context['data']['type'] ?? '', ['free_text', 'submit']);
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
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier', null, function (array $context): bool {
			return ($context['data']['type'] ?? '') !== 'free_text';
		});
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title', null, function (array $context): bool {
			return ($context['data']['type'] ?? '') !== 'free_text';
		});
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('title_email', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('placeholder', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
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
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('required', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
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
		$rules->add($rules->isUnique(['identifier', 'form_id']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
		]);

		$rules->add(function (FormElement $entity/*, array $options*/): bool {
			$la_availableInputTypes = $this->getAvailableTypes();

			return in_array($entity->type, $la_availableInputTypes);
		}, 'validInputType', [
			'errorField' => 'type',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_input_type'),
		]);

		$rules->add(function (FormElement $entity): bool {
			/** @var \Awyiss\Utility\Content\ColumnInterface $lo_width */
			$lo_width = $entity->column['width'];
			/** @var \Awyiss\Utility\Content\ColumnInterface $lo_indent */
			$lo_indent = $entity->column['indent'];

			$lf_totalWidth = $lo_width->getPercentage() + ($lo_indent?->getPercentage() ?? 0);

			if ($lf_totalWidth > 1) {
				return false;
			}

			return true;
		}, 'validWidthIndentCombination', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_width_indent_combination'),
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
