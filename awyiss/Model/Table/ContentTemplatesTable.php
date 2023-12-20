<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
 *
 * @method ContentTemplate newDefaultEntity(array $aa_additionalData = [])
 *
 * TODO: delete contents
 * TODO Or: disallow deletion if a content with that template exits
 */
class ContentTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_templates';
	protected array $_defaultConfig = [
		'translate' => [
			'fields' => ['title'],
		],
	];
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
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->hasMany('Contents', [
			'cascadeCallbacks' => TRUE,
			'dependent' => TRUE,
		]);

		/*$this->belongsToMany('ContentAreas', [
			'foreignKey' => [
				'content_template_id',
				'page_template_id',
			],
			'through' => 'ContentTemplateContentAreas',
		]);*/

		$this->hasMany('ContentTemplateContentAreas', [
			'cascadeCallbacks' => TRUE,
			'dependent' => TRUE,
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('ContentTemplateElements', [
			'cascadeCallbacks' => TRUE,
			'dependent' => TRUE,
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * @return string[]
	 */
	public function getAvailableContentElements(): array {
		return $this->availableContentElements;
	}


	/**
	 * @return string[]
	 */
	public function getAvailableFieldsets(): array {
		return $this->availableFieldsets;
	}


	/**
	 * @param ContentTemplate $ao_contentTemplate
	 *
	 * @return array
	 */
	public function getAssignedContentAttributes(ContentTemplate $ao_contentTemplate): array {
		$la_availableContentAttributes = $this->getAvailableContentAttributes();

		if (!isset($ao_contentTemplate->contentTemplateElements)) {
			//Load ContentTemplateAreas in case the entity is missing that key
			$this->skipAuthorizationCheckOnce();
			$this->loadInto($ao_contentTemplate, [
				'ContentTemplateElements' => [
					'finder' => [
						'all' => [
							'authorize' => [
								'skip' => TRUE,
							],
						],
					],
				],
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

		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$this->availableContentAttributes = $lo_attributesTable->find()->where(['scope' => 'contents'])->applyOptions([
			'authorize' => [
				'skip' => TRUE,
			],
		])->all()->indexBy('id')->map(function (Attribute $ao_attribute): array {
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
	 *
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'title',
			'filename',
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


		$ao_validator->notEmptyString('filename');
		$ao_validator->add('filename', [
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
	 *
	 * @return RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['filename']), 'uniqueFilename', [
			'errorField' => 'filename',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'content_templates', 'error_unique_filename'),
		]);


		$ao_rules->add(function (ContentTemplate $ao_entity): bool {
			$lb_valid = TRUE;

			$la_availableAttributes = array_column($this->getAvailableContentAttributes(), 'identifier');
			foreach ($ao_entity->contentTemplateElements as $lo_assignedContentElement) {
				if (str_starts_with($lo_assignedContentElement->identifier, 'attributes.')) {
					$ls_identifier = substr($lo_assignedContentElement->identifier, 11);

					if (!in_array($ls_identifier, $la_availableAttributes)) {
						$lb_valid = FALSE;
						break;
					}

					continue;
				}

				if (!in_array($lo_assignedContentElement->identifier, $this->availableContentElements)) {
					$lb_valid = FALSE;
					break;
				}
			}


			return $lb_valid;
		}, 'validContentElements', [
			'errorField' => 'contentTemplateElements',
			//No domain fallback, since this is a message, specific to content templates.
			'message' => __d($this->getI18nDomain(), 'error_valid_content_elements'),
		]);


		$ao_rules->addDelete(function (ContentTemplate $ao_entity): bool {
			dump($ao_entity);
			dd(__FILE__, __LINE__);
		});


		return $ao_rules;
	}
}
