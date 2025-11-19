<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Table\ContentsTable;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\Http\ServerRequest;
use Customer\Model\Enum\PageRole;


/**
 * Test case for AssociationCollection
 *
 * @see \Awyiss\ORM\AssociationCollection
 */
class AssociationCollectionTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentsTable
	 */
	protected ContentsTable $contentsTable;
	/**
	 * @var \Cake\Datasource\Locator\LocatorInterface
	 */
	protected LocatorInterface $tableLocator;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentsTable = FactoryLocator::get('Table')->get('Contents');
		$this->contentsTable->forPageRole(PageRole::Page);

		$this->tableLocator = FactoryLocator::get('Table');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		// Drop the custom TableLocator to avoid conflicts
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::save()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNestedContentsCanBeCopied(): void {
		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'es',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$user = $this->login(1); // Simulate a logged-in user with ID 1
		$request = $request->withAttribute('identity', $user);

		Router::setRequest($request);

		/**
		 * @var \Awyiss\Model\Entity\Content $content
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 */
		$content = $this->contentsTable->findById(50)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		$content->systemOrder = 2;

		$result = $this->contentsTable->save($content, ['asCopy' => true]);

		$this->assertNotFalse($result);
	}
}
