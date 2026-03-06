<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\FormElementsListener;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;


/**
 * FormElementsListener Test Case
 *
 * @see \Awyiss\Event\Backend\FormElementsListener
 */
class FormElementsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\FormElementsListener
	 */
	protected FormElementsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new FormElementsListener();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormElementsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.FormElements.beforeCopy' => 'beforeCopy',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormElementsListener::beforeCopy()
	 */
	public function testBeforeCopyRenamesNestedChildrenIdentifiers(): void {
		$formElementsTable = $this->fetchTable('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		$formElement = $formElementsTable->get(1);

		$formElement->childFormElements = $formElement->getNestedChildren();

		$children = collection($formElement->childFormElements)->listNested('desc', 'childFormElements');

		foreach ($children as $child) {
			$this->assertStringNotContainsString('Copy', $child->identifier);
		}

		$data = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.FormElements.beforeCopy', $formElementsTable);

		$this->listener->beforeCopy($event, $formElement, $data);

		foreach ($children as $child) {
			$this->assertStringContainsString('Copy', $child->identifier);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\FormElementsListener::beforeCopy()
	 */
	public function testBeforeCopyNotRenamesNestedChildrenIdentifiersWhenNotPrimary(): void {
		$formElementsTable = $this->fetchTable('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		$formElement = $formElementsTable->get(1);

		$formElement->childFormElements = $formElement->getNestedChildren();

		$children = collection($formElement->childFormElements)->listNested('desc', 'childFormElements');

		foreach ($children as $child) {
			$this->assertStringNotContainsString('Copy', $child->identifier);
		}

		$data = new ArrayObject(['_primary' => false]);
		$event = new Event('Model.FormElements.beforeCopy', $formElementsTable);

		$this->listener->beforeCopy($event, $formElement, $data);

		$children = collection($formElement->childFormElements)->listNested('desc', 'childFormElements');

		foreach ($children as $child) {
			$this->assertStringNotContainsString('Copy', $child->identifier);
		}
	}
}
