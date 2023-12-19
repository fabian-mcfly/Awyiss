<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;
use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
 * @method \Awyiss\Model\Entity\PageRole newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\PageRole patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class PageRolesTable extends \Awyiss\Model\Table {
	protected array $_defaultConfig = [
		'translate' => [
			'fields' => ['title'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('page_roles');
		$this->setPrimaryKey('id');
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('title')->maxLength('title', 32)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('identifier')->maxLength('identifier', 32)->requirePresence('identifier', 'create')->notEmptyString('identifier');

		$ao_validator->boolean('include_in_linklist')->notEmptyString('include_in_linklist');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}
}
