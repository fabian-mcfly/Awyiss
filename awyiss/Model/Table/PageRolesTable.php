<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
 * @todo When renaming a page role identifier => update usergroup_permissions
 * @todo When renaming a page role identifier => update attributes
 * @todo When deleting a page role: delete usergroup_permissions
 * @todo When deleting a page role: delete attributes
 * @todo When deleting a page role: delete page templates
 * @todo When deleting a page role: delete pages
 * @todo Add all other page roles?! <-- what does this even mean?
 * @todo On delete, remove cascadeCallbacks for nested pages in PagesTable
 * @todo Or: disallow deletion if a page with that role exits
 * @todo: disable deleting pagerole "page"
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\HasOne $PageTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $Pages
 * @method \Awyiss\Model\Entity\PageRole newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PageRolesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'page_roles';


	/**
	 * @var array<int, string> A list of identifiers a page role can't have, since they're used by the system
	 * or because they are template folder names
	 */
	protected array $blocklistedIdentifiers = [
		'cell',
		'email',
		'element',
		'generic_pages',
		'layout',
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
		$this->hasOne('PageTemplates');

		$this->hasMany('Pages', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('identifier');
		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('includeInLinklist', [
			'boolean' => ['rule' => 'boolean'],
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
		//TODO: merge all 3 rules into one.

		$ao_rules->add($ao_rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'page_role', 'error_identifier_unique'),
		]);


		$ao_rules->addCreate(function (PageRole $ao_entity): bool {
			$ls_identifier = strtolower(Inflector::underscore($ao_entity->identifier));

			if (in_array($ls_identifier, $this->blocklistedIdentifiers)) {
				return false;
			}


			return App::className(Inflector::camelize(Inflector::tableize($ls_identifier)), 'Controller/Backend', 'Controller') === null;
		}, 'identifierAllowed', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'page_role', 'error_identifier_allowed'),
		]);


		$ao_rules->addUpdate(function (PageRole $ao_entity/*, array $aa_options*/): bool {
			return !$ao_entity->hasOriginal('identifier') && !$ao_entity->isDirty('identifier');
		}, 'identifierUnchanged', [
			'errorField' => 'identifier',
			'message' => __d($this->getI18nDomain(), 'error_identifier_unchanged'),
		]);


		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo('PageTemplates', 'page_templates'),
			'noLinkedPageTemplates',
			[
				'errorField' => '_general',
				'message' => __d($this->getI18nDomain(), 'error_linked_page_templates'),
			]
		);


		return $ao_rules;
	}
}
