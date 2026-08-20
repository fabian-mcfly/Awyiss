<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\ColumnSystem\AwyissColumnSystem;
use Awyiss\Validation\Validator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\LogTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator as BaseValidator;


/**
 * GlobalContents Model
 *
 * @property \Awyiss\Model\Table\GlobalContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $GlobalContentTemplates
 * @property \Awyiss\Model\Table\GlobalContentsTable&\Awyiss\ORM\Association\BelongsTo $ParentGlobalContents
 * @property \Awyiss\Model\Table\GlobalContentsTable&\Awyiss\ORM\Association\HasMany $ChildGlobalContents
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Surveys
 * @method \Awyiss\Model\Entity\GlobalContent newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\GlobalContent getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class GlobalContentsTable extends Table {
	use LogTrait;


	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'global_contents';


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
		'relatedColumns' => ['identifier'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['identifier', 'parentId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'subtitle',
			'text',
			'link',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];
	/**
	 * @var string
	 */
	private string $forScope;


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
		]);

		$this->belongsTo('GlobalContentTemplates', [
			'foreignKey' => 'globalContentTemplateId',
			'propertyName' => 'globalContentTemplate',
		]);

		$this->belongsTo('Surveys', [
			'foreignKey' => 'surveyId',
		]);
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
	 */
	public function validationDefault(BaseValidator $validator): BaseValidator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'identifier',
			'globalContentTemplateId',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('globalContentTemplateId');
		$validator->add('globalContentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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


		$validator->add('cssClass', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('css', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('data', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('surveyId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
	public function buildRules(RulesChecker|BaseRulesChecker $rules): BaseRulesChecker {
		$rules->add(function (GlobalContent $entity/*, array $options*/): bool {
			/*
			 * Retrieve the global content template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate */
				$globalContentTemplate = $this->GlobalContentTemplates->get(
					$entity->globalContentTemplateId,
					contain: [
						'GlobalContentTemplateElements',
					],
				);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				// Global Content Template not found
				$entity->setError(
					'globalContentTemplateId',
					__df($this->getI18nDomain(), 'Validation', 'error_valid_global_content_template_id')
				);

				return false;
			}

			/**
			 * @var \Awyiss\Validation\Validator $validator
			 * @noinspection DuplicatedCode
			 */
			$validator = new $this->_validatorClass();
			$validator->setI18nDomain($this->getI18nDomain());

			$data = $entity->extract();
			if ($this->hasAttributes() && !empty($entity->attributes)) {
				/** @var \Awyiss\Validation\Validator $attributesValidator */
				$attributesValidator = new $this->_validatorClass();
				$attributesValidator->setI18nDomain($this->getI18nDomain());

				$data['attributes'] = $entity->attributes->extract();
			}

			$errors = $this->validateInputFields($data, $entity, $validator, $attributesValidator ?? null, $globalContentTemplate);

			if ($this->hasAttributes() && !empty($errors['attributes'])) {
				$entity->attributes->setErrors($errors['attributes']);
			}

			$entity->setErrors($errors);


			return empty($errors);
		}, 'validInputFields');


		$rules->add($rules->existsIn(['formId'], 'Forms', ['allowNullableNulls' => true]), 'validFormId', ['errorField' => 'formId']);


		$rules->add(
			$rules->existsIn(['surveyId'], 'Surveys', ['allowNullableNulls' => true]),
			'validSurveyId',
			['errorField' => 'surveyId']
		);


		$rules->add(function (GlobalContent $entity): bool {
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

		$schema->setColumnType('data', 'json');
	}


	/**
	 * Groups the result of a query by their `identifier`-value and returns a
	 * new collection with all global_contents nested and added `level`-property.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function nestedByIdentifier(SelectQuery $query): CollectionInterface {
		return $query
			->find('threaded')
			->all()
			->groupBy('identifier')
			->map(function (array $globalContents): CollectionInterface {
				$globalContents = new Collection($globalContents)->listNested();

				/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
				foreach ($globalContents as $globalContent) {
					$globalContent->setVirtual(['level'], true);
					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					/** @noinspection PhpUndefinedFieldInspection */
					$globalContent->level = $globalContents->getDepth();
				}

				return $globalContents;
			})
		;
	}


	/**
	 * @param array $data
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @return array
	 */
	protected function validateInputFields(
		array $data,
		GlobalContent $entity,
		Validator $validator,
		?Validator $attributesValidator,
		GlobalContentTemplate $globalContentTemplate
	): array {
		$globalContentAttributes = $this->GlobalContentTemplates->getAvailableGlobalContentAttributes();

		$this->validateAssignedElements($globalContentTemplate, $entity, $validator, $globalContentAttributes, $attributesValidator);

		$this->validateUnassignedElements($globalContentTemplate, $entity, $validator);

		if (isset($attributesValidator)) {
			$this->validateUnassignedAttributes($globalContentTemplate, $entity, $globalContentAttributes, $attributesValidator);

			// If validateUnassignedAttributes() added any rules, add the attributes validator to the main validator
			if ($attributesValidator->count()) {
				$validator->addNested('attributes', $attributesValidator);
			}
		}

		//Validate the extracted entity data and return the errors
		return $validator->validate($data, $entity->isNew());
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param array $globalContentAttributes
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function validateAssignedElements(
		GlobalContentTemplate $globalContentTemplate,
		GlobalContent $entity,
		Validator $validator,
		array $globalContentAttributes,
		?Validator $attributesValidator
	): void {
		//Traverse all elements that are available and assigned to the global content template
		foreach ($globalContentTemplate->globalContentTemplateElements as $globalContentTemplateElement) {
			if (!str_starts_with($globalContentTemplateElement->identifier, 'attributes.')) {
				if ($globalContentTemplateElement->required === true) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$validator
						->requirePresence($globalContentTemplateElement->identifier)
						->notEmptyString(
							$globalContentTemplateElement->identifier
						)
					;
					//TODO check if notEmptyString is enough. Some fields might need notEmpty*
				}

				continue;
			}

			// Nothing more to do if there's no attributes validator
			if (!$attributesValidator) {
				continue;
			}

			// Strip the 'attributes.'/'attributes_' prefix from the identifier
			$identifier = substr($globalContentTemplateElement->identifier, 11);

			// If the field already has an error or if it's not required, skip it.
			if (
				$entity->attributes->getError($identifier)
				|| $globalContentTemplateElement->required !== true
			) {
				continue;
			}

			$attributesValidator->requirePresence($identifier);

			switch ($globalContentAttributes[ $identifier ]['inputType']) {
				case 'checkbox':
					$attributesValidator->add($identifier, [
						'checkboxChecked' => [
							'rule' => ['equalTo', true],
						],
					]);
					break;
				default:
					$attributesValidator->notEmptyString($identifier);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function validateUnassignedElements(
		GlobalContentTemplate $globalContentTemplate,
		GlobalContent $entity,
		Validator $validator
	): void {
		//Traverse all elements that are available but not assigned to the global content template
		foreach (
			array_diff(
				array_keys($this->GlobalContentTemplates->getAvailableGlobalContentElements()),
				array_column($globalContentTemplate->globalContentTemplateElements, 'identifier')
			) as $element
		) {
			if ($entity->getError($element)) {
				continue;
			}

			if ($element === 'columnWidth') {
				$columnWidths = $this->getColumnWidths();

				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', key($columnWidths)],
					],
				]);

				continue;
			}

			if ($element === 'columnLast') {
				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			if ($element === 'columnRtl') {
				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			$validator->add($element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @param array $globalContentAttributes
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
	 * @return void
	 */
	protected function validateUnassignedAttributes(
		GlobalContentTemplate $globalContentTemplate,
		GlobalContent $entity,
		array $globalContentAttributes,
		?Validator $attributesValidator
	): void {
		$attributes = array_keys($globalContentAttributes);

		// Traverse all attributes that are available but not assigned to the global content template
		foreach (
			array_diff(
				$attributes,
				$this->GlobalContentTemplates->getAssignedGlobalContentAttributes($globalContentTemplate)
			) as $element
		) {
			if (!$entity->attributes->isDirty($element)) {
				continue;
			}

			$attributesValidator->add($element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
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
		if ($column === 'formId') {
			return $this
				->getAssociation('Forms')
				->find('list', valueField: 'label')
				->toArray()
			;
		}

		if ($column === 'surveyId') {
			return $this
				->getAssociation('Surveys')
				->find('list', valueField: 'label')
				->toArray()
			;
		}

		if ($column === 'globalContentTemplateId') {
			return $this
				->getAssociation('GlobalContentTemplates')
				->find('list', valueField: 'label')
				->toArray()
			;
		}


		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}
}
