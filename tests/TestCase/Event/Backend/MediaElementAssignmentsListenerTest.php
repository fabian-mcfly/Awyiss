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
		$mediaAssignmentsTable->deleteAll(['foreign_key' => 124]);
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

		$mediaElementAssignmentsTable = $this->fetchTable('MediaElementAssignments');
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');

		$mediaAssignments = [
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 10,
				'mediaFolderId' => 1,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 2,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => 1,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => 1,
				'scope' => 'global_contents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'identifier',
				'mediaId' => 11,
				'mediaFolderId' => 1,
				'scope' => 'content_templates',
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

		$mediaElementAssignment = $mediaElementAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 890,
			'scope' => 'content_templates',
			'foreignKey' => 1,
		]);

		$event = new Event('Model.MediaElementSelectors.afterSave', $mediaElementAssignmentsTable);

		$this->listener->afterDelete($event, $mediaElementAssignment);

		$mediaAssignments = $mediaAssignmentsTable->find('all')->where(['media_element_id' => 890])->all();
		$this->assertCount(2, $mediaAssignments);
	}
}
