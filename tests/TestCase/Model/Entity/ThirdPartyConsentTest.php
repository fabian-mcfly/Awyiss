<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\ThirdPartyConsent;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * ThirdPartyConsent Entity Test Case
 *
 * @see \Awyiss\Model\Entity\ThirdPartyConsent
 */
class ThirdPartyConsentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ThirdPartyConsent::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ThirdPartyConsentsTable $table */
		$table = FactoryLocator::get('Table')->get('ThirdPartyConsents');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ThirdPartyConsent::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new ThirdPartyConsent();

		$this->assertSame([
			'consentId' => true,
			'acceptType' => true,
			'acceptedCategories' => true,
			'rejectedCategories' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ThirdPartyConsent
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'consent_id' => 'consent-12345',
			'accept_type' => 'all',
			'accepted_categories' => ['analytics', 'marketing'],
			'rejected_categories' => ['social'],
			'created_on' => '2025-01-06 12:00:00',
		];

		$entity = new ThirdPartyConsent($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('consent-12345', $entity->consentId);
		$this->assertEquals('all', $entity->acceptType);
		$this->assertEquals(['analytics', 'marketing'], $entity->acceptedCategories);
		$this->assertEquals(['social'], $entity->rejectedCategories);
		$this->assertNotNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ThirdPartyConsent::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'consent_id' => 'test-consent',
			'accept_type' => 'partial',
			'accepted_categories' => ['essential'],
			'rejected_categories' => [],
		];
		$entity = new ThirdPartyConsent($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
