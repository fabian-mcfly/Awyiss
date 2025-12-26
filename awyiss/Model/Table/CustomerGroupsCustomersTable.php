<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * CustomerGroupsCustomers Model
 *
 * @property \Awyiss\Model\Table\CustomerGroupsTable&\Awyiss\ORM\Association\BelongsTo $CustomerGroups
 * @property \Awyiss\Model\Table\CustomersTable&\Awyiss\ORM\Association\BelongsTo $Customers
 * @method \Awyiss\Model\Entity\CustomerGroupsCustomer newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class CustomerGroupsCustomersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'customer_groups_customers';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('CustomerGroups', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Customers', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('customerGroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('customerId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
		$rules->add($rules->existsIn('customerGroupId', 'CustomerGroups'), 'customerGroupExists', [
			'errorField' => 'customerGroupId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_customer_group_exists'),
		]);


		$rules->add($rules->existsIn('customerId', 'Customers'), 'customerExists', [
			'errorField' => 'customerId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_customer_exists'),
		]);


		return $rules;
	}
}
