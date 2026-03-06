<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Event\Backend\FormsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * FormsListener Test Case
 *
 * @see \Awyiss\Event\Backend\FormsListener
 */
class FormsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\FormsListener
	 */
	protected FormsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new FormsListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Forms.afterCopy' => 'afterCopy',
			'Model.Forms.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Forms.beforeDelete' => 'beforeDelete',
			'Model.Forms.afterSoftDelete' => 'afterSoftDelete',
			'Model.Forms.afterDelete' => 'afterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::afterCopy()
	 * @throws \Exception
	 */
	public function testAfterCopyCopiesFormElements(): void {
		$formsTable = $this->fetchTable('Forms');
		$formElementsTable = $this->fetchTable('FormElements');

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $formsTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$form->originalEntity = unserialize(serialize($form));
		$form->unset('id');

		$form->id = 369;

		$this->assertCount(19, $formElementsTable->find()->where(['formId' => 1])->all());
		$this->assertCount(0, $formElementsTable->find()->where(['formId' => 369])->all());

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Forms.afterCopy', $formsTable);

		$this->listener->afterCopy($event, $form, $options);

		$this->assertCount(19, $formElementsTable->find()->where(['formId' => 1])->all());
		$this->assertCount(19, $formElementsTable->find()->where(['formId' => 369])->all());

		$formElementsTable->deleteAll(['formId' => 369]);
		$formsTable->deleteAll(['id' => 369]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::afterCopy()
	 */
	public function testAfterCopyCopiesMediaAssignmentsOfFormElements(): void {
		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$formsTable = $this->fetchTable('Forms');
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $formsTable->get(1);

		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$this->assertCount(2, $mediaAssignmentsTable->find()->where(['scope' => 'FormElements'])->all());

		$form->identifier .= '-copy';
		$form = $formsTable->save($form, ['asCopy' => true, 'audit' => ['skip' => true], 'systemOrder' => ['skip' => true]]);
		$this->assertNotFalse($form);

		$this->assertCount(3, $mediaAssignmentsTable->find()->where(['scope' => 'FormElements'])->all());

		$formsTable->deleteAll(['id' => $form->id]);
		$mediaAssignmentsTable->deleteAll(['id >' => 37]);
		$this->fetchTable('FormElements')->deleteAll(['formId' => $form->id]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::afterCopy()
	 */
	public function testAfterCopyCopiesTranslationsOfFormElements(): void {
		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$formsTable = $this->fetchTable('Forms');
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $formsTable->get(1);

		/** @var \Awyiss\Model\Table\I18nTable $i18nTable */
		$i18nTable = $this->fetchTable('i18n');
		$i18n = $i18nTable->newDefaultEntity([
			'locale' => 'it',
			'model' => 'FormElements',
			'foreignKey' => 4,
			'field' => 'title',
			'content' => 'Titolo in italiano',
		]);
		$this->assertNotFalse($i18nTable->save($i18n, ['audit' => ['skip' => true]]));

		$this->assertCount(1, $i18nTable->find()->where(['model' => 'FormElements'])->all());

		$form->identifier .= '-copy';
		$form = $formsTable->save($form, ['asCopy' => true, 'audit' => ['skip' => true], 'systemOrder' => ['skip' => true]]);
		$this->assertNotFalse($form);

		$this->assertCount(2, $i18nTable->find()->where(['model' => 'FormElements'])->all());

		$formsTable->deleteAll(['id' => $form->id]);
		$i18nTable->deleteAll(['id >' => 3]);
		$this->fetchTable('FormElements')->deleteAll(['formId' => $form->id]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::afterCopy()
	 * @throws \Exception
	 */
	public function testAfterCopySkipsWhenNotPrimary(): void {
		$formsTable = $this->fetchTable('Forms');
		$formElementsTable = $this->fetchTable('FormElements');

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $formsTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$form->originalEntity = $form;

		$options = new ArrayObject(['_primary' => false]);
		$event = new Event('Model.Forms.afterCopy', $formsTable);

		$initialCount = $formElementsTable->find()->count();

		$this->listener->afterCopy($event, $form, $options);

		$finalCount = $formElementsTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::beforeSoftDelete()
	 * @throws \Exception
	 */
	public function testBeforeSoftDeleteDisabledCascadingOnChildFormElements(): void {
		$formsTable = $this->fetchTable('Forms');

		$this->assertTrue($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertTrue($formsTable->FormElements->ChildFormElements->getDependent());

		$event = new Event('Model.Forms.beforeSoftDelete', $formsTable);

		$this->listener->beforeSoftDelete($event);

		$this->assertFalse($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertFalse($formsTable->FormElements->ChildFormElements->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::beforeDelete()
	 * @throws \Exception
	 */
	public function testBeforeDeleteDisabledCascadingOnChildFormElements(): void {
		$formsTable = $this->fetchTable('Forms');

		$this->assertTrue($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertTrue($formsTable->FormElements->ChildFormElements->getDependent());

		$event = new Event('Model.Forms.beforeDelete', $formsTable);

		$this->listener->beforeDelete($event);

		$this->assertFalse($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertFalse($formsTable->FormElements->ChildFormElements->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormsListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteEnabledCascadingOnChildFormElements(): void {
		$formsTable = $this->fetchTable('Forms');

		$event = new Event('Model.Forms.beforeSoftDelete', $formsTable);

		$this->listener->beforeSoftDelete($event);

		$this->assertFalse($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertFalse($formsTable->FormElements->ChildFormElements->getDependent());

		$event = new Event('Model.Forms.afterSoftDelete', $formsTable);
		$this->listener->afterSoftDelete($event);

		$this->assertTrue($formsTable->FormElements->ChildFormElements->getCascadeCallbacks());
		$this->assertTrue($formsTable->FormElements->ChildFormElements->getDependent());
	}
}
