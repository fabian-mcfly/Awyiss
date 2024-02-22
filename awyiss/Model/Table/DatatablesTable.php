<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * Datatables Model
 *
 * @method \Awyiss\Model\Entity\Datatable newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class DatatablesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'datatables';


	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected static ResultSetInterface $cachedDatatables;


	/**
	 * @var array<int, string> A list of identifiers a datatable can't have, since they're used by the system
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
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);
		$this->setDisplayField('title');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('identifier');
		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
			function (Datatable $ao_entity, array $aa_options) use ($ao_rules): bool|string {
				if (
					$ao_entity->hasOriginal('identifier') && $ao_entity->get('identifier') !== $ao_entity->getOriginal('identifier')
				) {
					return __d($this->getI18nDomain(), 'error_identifier_unchanged');
				}

				/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
				$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

				if (
					$ao_entity->isDirty('identifier') &&
					(
						str_starts_with($ao_entity->identifier, 'attributes_') ||
						in_array($ao_entity->identifier, $this->blocklistedIdentifiers) ||
						App::className(Inflector::camelize($ao_entity->identifier), 'Controller/Backend', 'Controller') ||
						$ls_pageRoleEnum::tryFromName($ao_entity->identifier)
					)
				) {
					return __dfx($this->getI18nDomain(), 'validation', 'datatable', 'error_identifier_allowed');
				}

				$lo_isUnique = $ao_rules->isUnique(['identifier'], [
					'message' => __dfx($this->getI18nDomain(), 'validation', 'datatable', 'error_identifier_unique'),
				]);
				$lb_isUnique = $lo_isUnique($ao_entity, $aa_options);

				if (!$lb_isUnique) {
					return false;
				}


				return true;
			},
			'validIdentifier',
			[
				'errorField' => 'identifier',
			]
		);


		return $ao_rules;
	}


	/**
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	public function findAllAndCache(): ResultSetInterface {
		if (!isset(static::$cachedDatatables)) {
			static::$cachedDatatables = static::find('translations')->all();
		}


		return static::$cachedDatatables;
	}
}
