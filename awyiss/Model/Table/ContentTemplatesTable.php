<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


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
 * @property ContentAreasTable&\Awyiss\ORM\Association\BelongsToMany $ContentAreas
 * @method ContentTemplate newDefaultEntity(array $aa_additionalData = [])
 */
class ContentTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_templates';


	/**
	 * @var array<int, string>
	 */
	protected array $availableContentElements = [
		'parent_id',
		'css_class',
		'columnwidth',
		'title',
		'subtitle',
		'text',
		'link',
		'duplicate_of',
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
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findWithUsages(SelectQuery $ao_query): SelectQuery {
		return $ao_query->enableAutoFields()->select([
			'usedForContents' => $ao_query->func()->count('Contents.id'),
		])->leftJoinWith('Contents', function (SelectQuery $ao_query) {
			return $ao_query->applyOptions([
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
	 * @param ContentTemplate $ao_contentTemplate
	 * @return array
	 */
	public function getAssignedContentAttributes(ContentTemplate $ao_contentTemplate): array {
		$la_availableContentAttributes = $this->getAvailableContentAttributes();

		if (!isset($ao_contentTemplate->contentTemplateElements)) {
			//Load ContentTemplateAreas in case the entity is missing that key
			$this->loadInto($ao_contentTemplate, [
				'ContentTemplateElements',
			]);
		}

		$la_availableContentElementIdentifiers = array_column($ao_contentTemplate->contentTemplateElements, 'identifier');
		$la_assignedContentAttributes = [];
		foreach ($la_availableContentAttributes as $la_attribute) {
			if (in_array('attributes.' . $la_attribute['identifier'], $la_availableContentElementIdentifiers)) {
				$la_assignedContentAttributes[] = $la_attribute['identifier'];
			}
		}


		return $la_assignedContentAttributes;
	}


	/**
	 * @return array<int, array>
	 */
	public function getAvailableContentAttributes(): array {
		if (isset($this->availableContentAttributes)) {
			return $this->availableContentAttributes;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_attributesTable */
		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$this->availableContentAttributes = $lo_attributesTable->find()->where(['scope' => 'contents'])->all()->indexBy('id')->map(function (Attribute $ao_attribute): array {
			return [
				'title' => $ao_attribute->title,
				'label' => $ao_attribute->label,
				'identifier' => $ao_attribute->identifier,
				'active' => $ao_attribute->active,
				'type' => $ao_attribute->type,
				'inputType' => $ao_attribute->inputType,
			];
		})->toArray();


		return $this->availableContentAttributes;
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
			'title',
			'fileName',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('fileName');
		$ao_validator->add('fileName', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$ao_rules->add($ao_rules->isUnique(['fileName']), 'fileNameUnique', [
			'errorField' => 'fileName',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'content_templates', 'error_file_name_unique'),
		]);


		$ao_rules->add(function (ContentTemplate $ao_entity): bool {
			$lb_valid = true;

			$la_availableAttributes = array_column($this->getAvailableContentAttributes(), 'identifier');
			foreach ($ao_entity->contentTemplateElements as $lo_assignedContentElement) {
				if (str_starts_with($lo_assignedContentElement->identifier, 'attributes.')) {
					$ls_identifier = substr($lo_assignedContentElement->identifier, 11);

					if (!in_array($ls_identifier, $la_availableAttributes)) {
						$lb_valid = false;
						break;
					}

					continue;
				}

				if (!in_array($lo_assignedContentElement->identifier, $this->availableContentElements)) {
					$lb_valid = false;
					break;
				}
			}


			return $lb_valid;
		}, 'validContentElements', [
			'errorField' => 'contentTemplateElements',
			//No domain fallback, since this is a message, specific to content templates.
			'message' => __d($this->getI18nDomain(), 'error_valid_content_elements'),
		]);


		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo('Contents', 'contents'),
			'noLinkedContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'system', 'error_linked_contents'),
			]
		);

		return $ao_rules;
	}
}
