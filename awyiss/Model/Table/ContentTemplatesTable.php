<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
 *
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsToMany $ContentAreas
 * @property \Awyiss\Model\Table\ContentTemplateElementsTable&\Awyiss\ORM\Association\HasMany $ContentTemplateElements
 * @method \Awyiss\Model\Entity\ContentTemplate newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
#[MediaElementAssignable(MediaElementAssignable::ENTITY_LEVEL)]
class ContentTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_templates';


	/**
	 * Available content elements.
	 * The key is the identifier of the element,
	 * the value is a boolean, indicating whether the element is optional.
	 *
	 * @var array<string, bool>
	 */
	protected array $availableContentElements = [
		'active' => false,
		'content_template_id' => false,
		'language_shortcode' => false,
		'page_id' => false,
		'content_area_id' => false,
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
		'duplicate_of' => true,
		'form_id' => true,
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
	protected array $availableContentAttributes;
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
		$this->hasMany('Contents', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);

		$this->belongsToMany('ContentAreas', [
			'through' => 'ContentTemplateContentAreas',
		]);

		$this->hasMany('ContentTemplateElements', [
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
			'used_for_contents' => $query->func()->count('Contents.id'),
		])->leftJoinWith('Contents', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('ContentTemplates.id');
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableContentElements(): array {
		return $this->availableContentElements;
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableFieldsets(): array {
		return $this->availableFieldsets;
	}


	/**
	 * @param ContentTemplate $contentTemplate
	 * @return array
	 */
	public function getAssignedContentAttributes(ContentTemplate $contentTemplate): array {
		$la_availableContentAttributes = $this->getAvailableContentAttributes();

		if (!isset($contentTemplate->contentTemplateElements)) {
			//Load ContentTemplateAreas in case the entity is missing that key
			$this->loadInto($contentTemplate, [
				'ContentTemplateElements',
			]);
		}

		$la_availableContentElementIdentifiers = array_column($contentTemplate->contentTemplateElements, 'identifier');
		$la_assignedContentAttributes = [];
		foreach ($la_availableContentAttributes as $la_attribute) {
			if (in_array('attributes.' . $la_attribute['identifier'], $la_availableContentElementIdentifiers)) {
				$la_assignedContentAttributes[] = $la_attribute['identifier'];
			}
		}


		return $la_assignedContentAttributes;
	}


	/**
	 * @param bool $includeInactive
	 * @return array<int, array>
	 */
	public function getAvailableContentAttributes(bool $includeInactive = false): array {
		if (isset($this->availableContentAttributes)) {
			return $this->availableContentAttributes;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_attributesTable */
		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$this->availableContentAttributes = $lo_attributesTable->find($includeInactive ? 'all' : 'active')->where(['scope' => 'contents'])->all()->indexBy('id')->map(function (Attribute $attribute): array {
			return [
				'title' => $attribute->title,
				'label' => $attribute->label,
				'identifier' => $attribute->identifier,
				'active' => $attribute->active,
				'type' => $attribute->type,
				'inputType' => $attribute->inputType,
			];
		})->toArray();


		return $this->availableContentAttributes;
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


		$rules->add(function (ContentTemplate $entity): bool {
			$lb_valid = true;

			$la_availableAttributes = array_column($this->getAvailableContentAttributes(), 'identifier');
			foreach ($entity->contentTemplateElements as $lo_assignedContentElement) {
				if (str_starts_with($lo_assignedContentElement->identifier, 'attributes.')) {
					$ls_identifier = substr($lo_assignedContentElement->identifier, 11);

					if (!in_array($ls_identifier, $la_availableAttributes)) {
						$lb_valid = false;
						break;
					}

					continue;
				}

				if (!in_array($lo_assignedContentElement->identifier, array_keys($this->availableContentElements))) {
					$lb_valid = false;
					break;
				}
			}


			return $lb_valid;
		}, 'validContentElements', [
			'errorField' => 'contentTemplateElements',
			//No domain fallback, since this is a message, specific to content templates.
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_content_elements'),
		]);


		$rules->addDelete(
			$rules->isNotLinkedTo('Contents', 'contents'),
			'noLinkedContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_contents'),
			]
		);

		return $rules;
	}
}
