<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;


/**
 * Event listener for customer-related tasks
 */
class CustomersListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Customers.cleanupUnverifiedCustomers' => 'cleanupUnverifiedCustomers',
		];
	}


	/**
	 * Clean up unverified customers whose verification period has expired
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function cleanupUnverifiedCustomers(): void {
		// Check if cleanup is enabled
		$cleanupEnabled = Configure::read('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		if (!$cleanupEnabled) {
			return;
		}

		$verificationCodeValidity = Configure::read('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 86400);
		if (!$verificationCodeValidity) {
			return;
		}

		/** @var \Awyiss\Model\Table\CustomersTable $customersTable */
		$customersTable = FactoryLocator::get('Table')->get('Customers');

		// Find and delete unverified customers whose verification timeout has passed
		$cutoffTime = DateTime::now()->subSeconds($verificationCodeValidity);

		$unverifiedCustomers = $customersTable->find()
			->where([
				'verified' => false,
				'created_on <' => $cutoffTime,
			])
			->all();

		foreach ($unverifiedCustomers as $customer) {
			$customersTable->delete($customer, [
				'audit' => ['skip' => true],
				'softDelete' => ['skip' => true],
			]);
		}
	}
}
