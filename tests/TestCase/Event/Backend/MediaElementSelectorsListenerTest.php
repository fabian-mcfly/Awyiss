<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\MediaElementSelectorsListener;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;


/**
 * MediaElementSelectorsListener Test Case
 *
 * @see \Awyiss\Event\Backend\MediaElementSelectorsListener
 */
class MediaElementSelectorsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\MediaElementSelectorsListener
	 */
	protected MediaElementSelectorsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new MediaElementSelectorsListener();

		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$mediaAssignmentsTable->deleteAll(['foreign_key' => 124]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaElementSelectorsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.MediaElementSelectors.afterSave' => 'afterSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaElementSelectorsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveUpdatesIdentifierWhenChanged(): void {
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$mediaElementSelectorsTable = $this->fetchTable('MediaElementSelectors');

		$mediaAssignments = [
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 10,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 2,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 11,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'other-identifier',
				'mediaId' => 10,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 891,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 11,
				'scope' => 'contents',
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

		$mediaElementSelector = $mediaElementSelectorsTable->newDefaultEntity([
			'mediaElementId' => 890,
			'mediaSelectorId' => 10,
			'title' => 'Test',
			'identifier' => 'old-identifier',
		]);
		$mediaElementSelector->setNew(false);
		$mediaElementSelector->clean();

		$mediaElementSelector->identifier = 'new-identifier';

		$event = new Event('Model.MediaElementSelectors.afterSave', $mediaElementSelectorsTable);

		$this->listener->afterSave($event, $mediaElementSelector);

		$assignments = $mediaAssignmentsTable->find()->where(['foreign_key' => 124])->orderByAsc('id')->all();

		$this->assertCount(4, $assignments);

		$assignmentIdentifiers = $assignments->extract('mediaElementSelectorIdentifier')->toArray();

		$this->assertSame([
			'new-identifier',
			'new-identifier',
			'other-identifier',
			'old-identifier',
		], $assignmentIdentifiers);

		$mediaAssignmentsTable->deleteAll(['foreign_key' => 124]);
	}

	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaElementSelectorsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesIdentifierWhenUnchanged(): void {
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$mediaElementSelectorsTable = $this->fetchTable('MediaElementSelectors');

		$mediaAssignments = [
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 10,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 2,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 890,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 11,
				'scope' => 'contents',
				'foreignKey' => 124,
				'systemOrder' => 1,
			]),
			$mediaAssignmentsTable->newDefaultEntity([
				'mediaElementId' => 891,
				'mediaElementSelectorIdentifier' => 'old-identifier',
				'mediaId' => 11,
				'scope' => 'contents',
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

		$mediaElementSelector = $mediaElementSelectorsTable->newDefaultEntity([
			'mediaElementId' => 890,
			'mediaSelectorId' => 10,
			'title' => 'Test',
			'identifier' => 'old-identifier',
		]);
		$mediaElementSelector->setNew(false);
		$mediaElementSelector->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mediaElementSelector->identifier = 'new-identifier';
		$mediaElementSelector->identifier = 'old-identifier';


		$event = new Event('Model.MediaElementSelectors.afterSave', $mediaElementSelectorsTable);

		$this->listener->afterSave($event, $mediaElementSelector);

		$assignments = $mediaAssignmentsTable->find()->where(['foreign_key' => 124])->all();

		$this->assertCount(3, $assignments);

		/** @var \Awyiss\Model\Entity\MediaAssignment $assignment */
		foreach ($assignments as $assignment) {
			$this->assertSame('old-identifier', $assignment->mediaElementSelectorIdentifier);
		}

		$mediaAssignmentsTable->deleteAll(['foreign_key' => 124]);
	}
}
