<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\Widget;
use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\AwyissColumnSystem;
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
 * Widgets Model
 *
 * @property \Awyiss\Model\Table\WidgetTemplatesTable&\Awyiss\ORM\Association\BelongsTo $WidgetTemplates
 * @property \Awyiss\Model\Table\WidgetsTable&\Awyiss\ORM\Association\BelongsTo $ParentWidgets
 * @property \Awyiss\Model\Table\WidgetsTable&\Awyiss\ORM\Association\HasMany $ChildWidgets
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Surveys
 * @method \Awyiss\Model\Entity\Widget newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\Widget getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 */
class WidgetsTable extends Table {
	use LogTrait;


	/**
	 * @inheritDoc
	 */
	public const TABLE = 'widgets';


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
	 * @var string
	 */
	private string $forScope;
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
		$this->belongsTo('Forms');

		$this->belongsTo('Surveys');

		$this->belongsTo('WidgetTemplates');
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
	 * @inheritDoc
	 */
	public function validationDefault(BaseValidator $validator): BaseValidator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'identifier',
			'widgetTemplateId',
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


		$validator->notEmptyString('widgetTemplateId');
		$validator->add('widgetTemplateId', [
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): BaseRulesChecker {
		$rules->add(function (Widget $entity/*, array $options*/): bool {
			/*
			 * Retreive tthe widget template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var WidgetTemplate $lo_widgetTemplate */
				$lo_widgetTemplate = $this->WidgetTemplates->get(
					$entity->widgetTemplateId,
					contain: [
						'WidgetTemplateElements',
					],
				);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				//Widget template not found
				$entity->setError('widget_template_id', __df($this->getI18nDomain(), 'validation', 'error_valid_widget_template_id'));

				return false;
			}

			/** @var \Awyiss\Validation\Validator $lo_validator */
			/** @noinspection DuplicatedCode */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getI18nDomain());

			$la_data = $entity->extract();
			if (!empty($entity->attributes)) {
				/** @var \Awyiss\Validation\Validator $lo_attributesValidator */
				$lo_attributesValidator = new $this->_validatorClass();
				$lo_attributesValidator->setI18nDomain($this->getI18nDomain());

				$la_data['attributes'] = $entity->attributes->extract();
			}

			$this->validateInputFields($entity, $lo_validator, $lo_attributesValidator ?? null, $lo_widgetTemplate);

			//Validate the entity using the
			$la_errors = $lo_validator->validate($la_data, $entity->isNew());

			$la_errors = $this->getEntityClass()::mapFields($la_errors, true);

			if ($this->hasAttributes() && !empty($la_errors['attributes'])) {
				$la_errors['attributes'] = $this->getAttributesTable()->getEntityClass()::mapFields($la_errors['attributes'], true);
				$entity->attributes->setErrors($la_errors['attributes']);
			}

			$entity->setErrors($la_errors);


			return empty($la_errors);
		}, 'validInputFields');


		$rules->add($rules->existsIn(['formId'], 'Forms', ['allowNullableNulls' => true]), 'validFormId', ['errorField' => 'formId']);


		$rules->add($rules->existsIn(['surveyId'], 'Surveys', ['allowNullableNulls' => true]), 'validSurveyId', ['errorField' => 'surveyId']);


		$rules->add(function (Widget $entity): bool {
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
	 * Groups the result of a query by their `identifier`-value and returns a new collection with all
	 * widgets nested and an added `level`-property.
	 *
	 * @param SelectQuery $query
	 * @return CollectionInterface
	 */
	public function nestedByIdentifier(SelectQuery $query): CollectionInterface {
		return $query->find('threaded')->all()->groupBy('identifier')->map(function (array $widgets): CollectionInterface {
			$lo_widgets = (new Collection($widgets))->listNested();

			/** @var Widget $lo_widget */
			foreach ($lo_widgets as $lo_widget) {
				$lo_widget->setVirtual(['level'], true);
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_widget->level = $lo_widgets->getDepth();
			}


			return $lo_widgets;
		});
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @return void
	 */
	protected function validateInputFields(Widget $entity, Validator $validator, ?Validator $attributesValidator, WidgetTemplate $widgetTemplate): void {
		$la_widgetAttributes = $this->WidgetTemplates->getAvailableWidgetAttributes();

		$this->validateAssignedElements($widgetTemplate, $entity, $validator, $la_widgetAttributes, $attributesValidator);

		$this->validateUnassignedElements($widgetTemplate, $entity, $validator);

		if (isset($attributesValidator)) {
			$this->validateUnassignedAttributes($widgetTemplate, $entity, $la_widgetAttributes, $attributesValidator);

			if ($attributesValidator->count()) {
				$validator->addNested('attributes', $attributesValidator);
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('data', 'json');
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param array $widgetAttributes
	 * @param \Awyiss\Validation\Validator $attributesValidator
	 * @return void
	 */
	protected function validateUnassignedAttributes(WidgetTemplate $widgetTemplate, Widget $entity, array $widgetAttributes, ?Validator $attributesValidator): void {
		$la_attributes = array_keys($widgetAttributes);

		foreach (
			array_diff(
				$la_attributes,
				$this->WidgetTemplates->getAssignedWidgetAttributes($widgetTemplate)
			) as $ls_element
		) {
			if (!$entity->attributes->isDirty($ls_element)) {
				continue;
			}

			$attributesValidator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function validateUnassignedElements(WidgetTemplate $widgetTemplate, Widget $entity, Validator $validator): void {
		foreach (
			array_diff(
				array_keys($this->WidgetTemplates->getAvailableWidgetElements()),
				array_column($widgetTemplate->widgetTemplateElements, 'identifier')
			) as $ls_element
		) {
			if ($entity->getError($ls_element)) {
				continue;
			}

			if ($ls_element === 'column_width') {
				$la_columnWidths = $this->getColumnWidths();

				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', key($la_columnWidths)],
					],
				]);

				continue;
			}

			if ($ls_element === 'column_last') {
				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			if ($ls_element === 'column_rtl') {
				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			$validator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param array $widgetAttributes
	 * @param \Awyiss\Validation\Validator $attributesValidator
	 * @return void
	 */
	protected function validateAssignedElements(
		WidgetTemplate $widgetTemplate,
		Widget $entity,
		Validator $validator,
		array $widgetAttributes,
		?Validator $attributesValidator
	): void {
		//Traverse all elements that are available inside the widget template
		foreach ($widgetTemplate->widgetTemplateElements as $lo_widgetTemplateElement) {
			if (!str_starts_with($lo_widgetTemplateElement->identifier, 'attributes.')) {
				if ($lo_widgetTemplateElement->required === true) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$validator->requirePresence($lo_widgetTemplateElement->identifier)->notEmptyString($lo_widgetTemplateElement->identifier);
					//TODO check if notEmptyString is enough. Some fields might need notEmpty*
				}

				continue;
			}

			if (!$attributesValidator) {
				continue;
			}

			$ls_identifier = substr($lo_widgetTemplateElement->identifier, 11);

			if ($entity->attributes->getError($ls_identifier)) {
				continue;
			}

			if ($lo_widgetTemplateElement->required === true) {
				$attributesValidator->requirePresence($ls_identifier);

				switch ($widgetAttributes[ $ls_identifier ]['inputType']) {
					case 'checkbox':
						$attributesValidator->add($ls_identifier, [
							'checkboxChecked' => [
								'rule' => ['equalTo', true],
							],
						]);
						break;
					default:
						$attributesValidator->notEmptyString($ls_identifier);
				}
			}
		}
	}


	/**
	 * Get possible field values for the search form.
	 *
	 * @param string $column
	 * @param string|null $type
	 * @return array|null
	 */
	public function getPossibleFieldValues(string $column, ?string $type = null): ?array {
		if ($column === 'form_id') {
			return $this->getAssociation('Forms')->find('list', valueField: 'label')->toArray();
		}

		if ($column === 'widget_template_id') {
			return $this->getAssociation('WidgetTemplates')->find('list', valueField: 'label')->toArray();
		}

		if ($column === 'survey_id') {
			return $this->getAssociation('Surveys')->find('list', valueField: 'label')->toArray();
		}


		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}
}
