<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * WidgetTemplates Model
 *
 * @property \Awyiss\Model\Table\WidgetsTable&\Awyiss\ORM\Association\HasMany $Widgets
 * @property \Awyiss\Model\Table\WidgetTemplateElementsTable&\Awyiss\ORM\Association\HasMany $WidgetTemplateElements
 * @method \Awyiss\Model\Entity\WidgetTemplate newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
#[MediaElementAssignable(MediaElementAssignable::ENTITY_LEVEL)]
class WidgetTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'widget_templates';


	/**
	 * @var array<int, string>
	 */
	protected array $availableWidgetElements = [
		'active' => false,
		'widget_template_id' => false,
		'identifier' => false,
		'parent_id' => true,
		'system_order' => false,
		'css_class' => true,
		'column_width' => true,
		'column_indent' => true,
		'column_last' => true,
		'column_rtl' => true,
		'title' => true,
		'title_tag' => true,
		'subtitle' => true,
		'subtitle_tag' => true,
		'text' => true,
		'link' => true,
	];
	/**
	 * @var array<int, string>
	 */
	protected array $availableFieldsets = [
		'presentation',
		'conditions',
		'general',
		'content',
		'media',
		'attributes',
		'data',
		'publication',
	];
	/**
	 * @var array<int, array{title: string, label: string, identifier: string, active: bool, type: string,
	 *     inputType:string}>
	 */
	protected array $availableWidgetAttributes;
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('Widgets', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);

		$this->hasMany('WidgetTemplateElements', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * @param SelectQuery $query
	 * @param array $options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findWithUsages(SelectQuery $query): SelectQuery {
		return $query->enableAutoFields()->select([
			'used_for_widgets' => $query->func()->count('Widgets.id'),
		])->leftJoinWith('Widgets', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('WidgetTemplates.id');
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableWidgetElements(): array {
		return $this->availableWidgetElements;
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableFieldsets(): array {
		return $this->availableFieldsets;
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @return array
	 */
	public function getAssignedWidgetAttributes(WidgetTemplate $widgetTemplate): array {
		$la_availableWidgetAttributes = $this->getAvailableWidgetAttributes();

		if (!isset($widgetTemplate->widgetTemplateElements)) {
			//Load WidgetTemplateElements in case the entity is missing that key
			$this->loadInto($widgetTemplate, [
				'WidgetTemplateElements',
			]);
		}

		$la_availableWidgetElementIdentifiers = array_column($widgetTemplate->widgetTemplateElements, 'identifier');
		$la_assignedWidgetAttributes = [];
		foreach ($la_availableWidgetAttributes as $la_attribute) {
			if (in_array('attributes.' . $la_attribute['identifier'], $la_availableWidgetElementIdentifiers)) {
				$la_assignedWidgetAttributes[] = $la_attribute['identifier'];
			}
		}


		return $la_assignedWidgetAttributes;
	}


	/**
	 * @param bool $includeInactive
	 * @return array<int, array>
	 */
	public function getAvailableWidgetAttributes(bool $includeInactive = false): array {
		if (isset($this->availableWidgetAttributes)) {
			return $this->availableWidgetAttributes;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_attributesTable */
		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$this->availableWidgetAttributes = $lo_attributesTable->find($includeInactive ? 'all' : 'active')->where(['scope' => 'widgets'])->all()->indexBy('id')->map(function (Attribute $attribute): array {
			return [
				'title' => $attribute->title,
				'label' => $attribute->label,
				'identifier' => $attribute->identifier,
				'active' => $attribute->active,
				'type' => $attribute->type,
				'inputType' => $attribute->inputType,
			];
		})->toArray();


		return $this->availableWidgetAttributes;
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
			'fileName',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('fileName');
		$validator->add('fileName', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$rules->add($rules->isUnique(['fileName']), 'fileNameUnique', [
			'errorField' => 'fileName',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_file_name_unique'),
		]);


		$rules->add(function (WidgetTemplate $entity): bool {
			$lb_valid = true;

			$la_availableAttributes = array_column($this->getAvailableWidgetAttributes(), 'identifier');
			foreach ($entity->widgetTemplateElements as $lo_assignedWidgetElement) {
				if (str_starts_with($lo_assignedWidgetElement->identifier, 'attributes.')) {
					$ls_identifier = substr($lo_assignedWidgetElement->identifier, 11);

					if (!in_array($ls_identifier, $la_availableAttributes)) {
						$lb_valid = false;
						break;
					}

					continue;
				}

				if (!in_array($lo_assignedWidgetElement->identifier, array_keys($this->availableWidgetElements))) {
					$lb_valid = false;
					break;
				}
			}


			return $lb_valid;
		}, 'validWidgetElements', [
			'errorField' => 'widgetTemplateElements',
			//No domain fallback, since this is a message, specific to widget templates.
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_widget_elements'),
		]);


		$rules->addDelete(
			$rules->isNotLinkedTo('Widgets', 'widgets'),
			'noLinkedWidgets',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_widgets'),
			]
		);

		return $rules;
	}
}
