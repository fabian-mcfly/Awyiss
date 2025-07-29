<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\HasOne $PageTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $Pages
 * @method \Awyiss\Model\Entity\PageRole newDefaultEntity(array $additionalData = [], array $options = [])
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
			/** @uses \Awyiss\Model\Table::findTranslations() */
			static::$cachedPageRoles = static::find('translations')->all();
		}


		return static::$cachedPageRoles;
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
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('includeInLinklist', [
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
		$lo_rules = $rules;
		$rules->add(
			function (PageRole $entity, array $options) use ($lo_rules): bool|string {
				if (
					($options['isCopy'] ?? false) === false &&
					$entity->hasOriginal('identifier') &&
					$entity->get('identifier') !== $entity->getOriginal('identifier')
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_unchanged');
				}

				$ls_pluralIdentifier = Inflector::pluralize($entity->identifier);

				/** @var \Awyiss\Model\Table\DatatablesTable $lo_datatablesTable */
				$lo_datatablesTable = FactoryLocator::get('Table')->get('Datatables');
				$lo_datatables = $lo_datatablesTable->findAllAndCache();

				if (
					$entity->isDirty('identifier') &&
					(
						str_starts_with($entity->identifier, 'attributes_') ||
						in_array($entity->identifier, $this->blocklistedIdentifiers) ||
						App::className(Inflector::camelize($ls_pluralIdentifier), 'Controller/Backend', 'Controller') ||
						$lo_datatables->firstMatch(['active' => true, 'identifier' => $ls_pluralIdentifier])
					)
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_allowed');
				}

				$lo_isUnique = $lo_rules->isUnique(['identifier'], [
					'errorField' => '_dummy',
				]);
				$lb_isUnique = $lo_isUnique($entity, $options);

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

		$rules->addDelete(
			function (PageRole $entity/*, array $options*/): bool {
				return $entity->identifier !== 'page';
			},
			'notPageRolePageDeletion',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_page_role_page_deletion'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('PageTemplates', 'page_templates'),
			'noLinkedPageTemplates',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_linked_page_templates'),
			]
		);


		return $rules;
	}
}
