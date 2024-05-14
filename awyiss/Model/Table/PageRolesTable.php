<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
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
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected static ResultSetInterface $cachedPageRoles;


	/**
	 * @var array<int, string> A list of identifiers a page role can't have, since they're used by the system
	 * or because they are template folder names
	 */
	protected array $blocklistedIdentifiers = [
		'cell',
		'content_area',
		'email',
		'element',
		'generic_page',
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
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
		]);
	}


	/**
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	public function findAllAndCache(): ResultSetInterface {
		if (!isset(static::$cachedPageRoles)) {
			static::$cachedPageRoles = static::find('translations')->all();
		}


		return static::$cachedPageRoles;
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
		$ao_rules->add(
			function (PageRole $ao_entity, array $aa_options) use ($ao_rules): bool|string {
				if (
					$aa_options['isCopy'] === false &&
					$ao_entity->hasOriginal('identifier') &&
					$ao_entity->get('identifier') !== $ao_entity->getOriginal('identifier')
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_unchanged');
				}

				$ls_pluralIdentifier = Inflector::pluralize($ao_entity->identifier);

				/** @var \Awyiss\Model\Table\DatatablesTable $lo_datatablesTable */
				$lo_datatablesTable = FactoryLocator::get('Table')->get('Datatables');
				$lo_datatables = $lo_datatablesTable->findAllAndCache();

				if (
					$ao_entity->isDirty('identifier') &&
					(
						str_starts_with($ao_entity->identifier, 'attributes_') ||
						in_array($ao_entity->identifier, $this->blocklistedIdentifiers) ||
						App::className(Inflector::camelize($ls_pluralIdentifier), 'Controller/Backend', 'Controller') ||
						$lo_datatables->firstMatch(['active' => true, 'identifier' => $ls_pluralIdentifier])
					)
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_allowed');
				}

				$lo_isUnique = $ao_rules->isUnique(['identifier'], [
					'errorField' => '_dummy',
				]);
				$lb_isUnique = $lo_isUnique($ao_entity, $aa_options);

				if (!$lb_isUnique) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_unique');
				}

				return true;
			},
			'validIdentifier',
			[
				'errorField' => 'identifier',
			]
		);

		$ao_rules->addDelete(
			function (PageRole $ao_entity/*, array $aa_options*/): bool {
				return $ao_entity->identifier !== 'page';
			},
			'notPageRolePageDeletion',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_page_role_page_deletion'),
			]
		);

		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo('PageTemplates', 'page_templates'),
			'noLinkedPageTemplates',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_linked_page_templates'),
			]
		);


		return $ao_rules;
	}
}
