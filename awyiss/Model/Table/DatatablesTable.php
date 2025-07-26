<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Datatables Model
 *
 * @method \Awyiss\Model\Entity\Datatable newDefaultEntity(array $additionalData = [], array $options = [])
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
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
			function (Datatable $entity, array $options) use ($lo_rules): bool|string {
				if (
					($options['isCopy'] ?? false) === false &&
					$entity->hasOriginal('identifier') &&
					$entity->get('identifier') !== $entity->getOriginal('identifier')
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_identifier_unchanged');
				}

				/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
				$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

				if (
					$entity->isDirty('identifier') &&
					(
						str_starts_with($entity->identifier, 'attributes_') ||
						in_array($entity->identifier, $this->blocklistedIdentifiers) ||
						App::className(Inflector::camelize($entity->identifier), 'Controller/Backend', 'Controller') ||
						$ls_pageRoleEnum::tryFromName($entity->identifier)
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


		return $rules;
	}


	/**
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	public function findAllAndCache(): ResultSetInterface {
		if (!isset(static::$cachedDatatables)) {
			/** @uses \Awyiss\Model\Table::findTranslations() */
			static::$cachedDatatables = static::find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->all();
		}


		return static::$cachedDatatables;
	}
}
