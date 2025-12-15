<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * GlobalContentTemplates Model
 *
 * @property \Awyiss\Model\Table\GlobalContentsTable&\Awyiss\ORM\Association\HasMany $GlobalContents
 * @property \Awyiss\Model\Table\GlobalContentTemplateElementsTable&\Awyiss\ORM\Association\HasMany $GlobalContentTemplateElements
 * @method \Awyiss\Model\Entity\GlobalContentTemplate newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
#[MediaElementAssignable(MediaElementAssignable::ENTITY_LEVEL)]
class GlobalContentTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'global_content_templates';


	/**
	 * Available Global Content elements.
	 * The key is the identifier of the element,
	 * the value is a boolean, indicating whether the element is optional.
	 *
	 * @var array<string, bool>
	 */
	protected array $availableGlobalContentElements = [
		'active' => false,
		'global_content_template_id' => false,
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
		'form_id' => true,
		'survey_id' => true,
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
	 * @var array<string, array<string, array{title: string, label: string, identifier: string, active: bool, type: string,inputType:string}|null>>
	 */
	protected array $availableGlobalContentAttributes = [
		'withInactive' => null,
		'active' => null,
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
	public function initializeAssociations(): void {
		$this->hasMany('GlobalContents', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);

		$this->hasMany('GlobalContentTemplateElements', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findWithUsages(SelectQuery $query): SelectQuery {
		return $query->enableAutoFields()->select([
			'used_for_global_contents' => $query->func()->count('GlobalContents.id'),
		])->leftJoinWith('GlobalContents', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('GlobalContentTemplates.id');
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @return array
	 */
	public function getAssignedGlobalContentAttributes(GlobalContentTemplate $globalContentTemplate): array {
		if (!isset($globalContentTemplate->globalContentTemplateElements)) {
			//Load GlobalContentTemplateElements in case the entity is missing that key
			$this->loadInto($globalContentTemplate, [
				'GlobalContentTemplateElements',
			]);
		}

		$availableGlobalContentAttributes = $this->getAvailableGlobalContentAttributes();
		$availableGlobalContentElementIdentifiers = array_column($globalContentTemplate->globalContentTemplateElements ?? [], 'identifier');
		$assignedGlobalContentAttributes = [];
		foreach ($availableGlobalContentAttributes as $attribute) {
			if (in_array('attributes.' . $attribute['identifier'], $availableGlobalContentElementIdentifiers)) {
				$assignedGlobalContentAttributes[] = $attribute['identifier'];
			}
		}


		return $assignedGlobalContentAttributes;
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableFieldsets(): array {
		return $this->availableFieldsets;
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableGlobalContentElements(): array {
		return $this->availableGlobalContentElements;
	}


	/**
	 * @param bool $includeInactive
	 * @return array<int, array>
	 */
	public function getAvailableGlobalContentAttributes(bool $includeInactive = false): array {
		$key = $includeInactive ? 'withInactive' : 'active';

		if (isset($this->availableGlobalContentAttributes[ $key ])) {
			return $this->availableGlobalContentAttributes[ $key ];
		}

		/** @var \Awyiss\Model\Table\AttributesTable $attributesTable */
		$attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$this->availableGlobalContentAttributes[ $key ] = $attributesTable->find($includeInactive ? 'all' : 'active')->where(['scope' => 'global_contents'])->all()->indexBy('identifier')->map(
			function (Attribute $attribute): array {
				return [
					'title' => $attribute->title,
					'label' => $attribute->label,
					'identifier' => $attribute->identifier,
					'active' => $attribute->active,
					'type' => $attribute->type,
					'inputType' => $attribute->inputType,
				];
			}
		)->toArray();


		return $this->availableGlobalContentAttributes[ $key ];
	}


	/**
	 * @inheritDoc
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
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('fileName');
		$validator->add('fileName', [
			'ascii' => ['rule' => 'ascii'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$rules->add($rules->isUnique(['fileName']), 'fileNameUnique', [
			'errorField' => 'fileName',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_file_name_unique'),
		]);


		$rules->add(function (GlobalContentTemplate $entity): bool {
			$valid = true;

			$availableAttributes = array_keys($this->getAvailableGlobalContentAttributes());
			foreach (($entity->globalContentTemplateElements ?? []) as $assignedGlobalContentElement) {
				if (str_starts_with($assignedGlobalContentElement->identifier, 'attributes.')) {
					$identifier = substr($assignedGlobalContentElement->identifier, 11);

					if (!in_array($identifier, $availableAttributes)) {
						$valid = false;
						break;
					}

					continue;
				}

				if (!in_array($assignedGlobalContentElement->identifier, array_keys($this->availableGlobalContentElements))) {
					$valid = false;
					break;
				}
			}


			return $valid;
		}, 'validGlobalContentElements', [
			'errorField' => 'globalContentTemplateElements',
			//No domain fallback, since this is a message, specific to global content templates.
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_global_content_elements'),
		]);


		$rules->addDelete(
			$rules->isNotLinkedTo('GlobalContents', 'global_contents'),
			'noLinkedGlobalContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_global_contents'),
			]
		);

		return $rules;
	}
}
