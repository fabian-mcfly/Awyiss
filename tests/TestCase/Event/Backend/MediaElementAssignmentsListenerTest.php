<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Event\Backend\MediaElementAssignmentsListener;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;


/**
 * MediaElementAssignmentsListener Test Case
 *
 * @see \Awyiss\Event\Backend\MediaElementAssignmentsListener
 */
class MediaElementAssignmentsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\MediaElementAssignmentsListener
	 */
	protected MediaElementAssignmentsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new MediaElementAssignmentsListener();

		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$mediaAssignmentsTable->deleteAll(['mediaElementId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaElementAssignmentsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.MediaElementAssignments.afterDelete' => 'afterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaElementAssignmentsListener::afterDelete()
	 * @throws \Exception
	 */
	public function testAfterDeleteDeletesMediaAssignmentsWithSameMediaElementIdInHasManyAssociations(): void {
		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$contentsTable = $this->fetchTable('Contents');
		$mediaElementAssignmentsTable = $this->fetchTable('MediaElementAssignments');
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');

		$contents = [
			$contentsTable->newDefaultEntity([
				'pageId' => 124,
				'contentAreaId' => 1,
				'contentTemplateId' => 124,
			]),
			$contentsTable->newDefaultEntity([
				'pageId' => 124,
				'contentAreaId' => 1,
				'contentTemplateId' => 124,
			]),
		];

		$contents[0]->id = 580;
		$contents[1]->id = 590;

		$result = $contentsTable->saveMany($contents, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$mediaAssignments = [
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 10,
				'mediaFolderId' => null,
				'scope' => 'Contents',
				'foreignKey' => 590,
				'systemOrder' => 2,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => null,
				'scope' => 'Contents',
				'foreignKey' => 580,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => null,
				'scope' => 'Contents',
				'foreignKey' => 57,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => null,
				'scope' => 'GlobalContents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => null,
				'scope' => 'ContentTemplates',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
		];
		$result = $mediaAssignmentsTable->saveMany($mediaAssignments, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$mediaAssignments = $mediaAssignmentsTable->find('all')->where(['mediaElementId' => 890])->all();
		$this->assertCount(5, $mediaAssignments);

		$mediaElementAssignment = $mediaElementAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 890,
			'scope' => 'ContentTemplates',
			'foreignKey' => 124,
		]);

		$event = new Event('Model.MediaElementSelectors.afterSave', $mediaElementAssignmentsTable);

		$this->listener->afterDelete($event, $mediaElementAssignment);

		$mediaAssignments = $mediaAssignmentsTable->find('all')->where(['mediaElementId' => 890])->all();
		$this->assertCount(3, $mediaAssignments);

		$contentsTable->deleteAll(['id IN' => [580, 590]]);
		$mediaAssignmentsTable->deleteAll(['mediaElementId' => 890]);
	}
}
