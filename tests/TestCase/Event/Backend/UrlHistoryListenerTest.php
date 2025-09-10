<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\UrlHistoryListener;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;


/**
 * UrlHistoryListener Test Case
 *
 * @see \Awyiss\Event\Backend\UrlHistoryListener
 */
class UrlHistoryListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\UrlHistoryListener
	 */
	protected UrlHistoryListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new UrlHistoryListener();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UrlHistoryListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.UrlHistory.beforeMarshal' => 'beforeMarshal',
		], $result);
	}


	/**
	 * Test beforeMarshal method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshal(): void {
		$event = new Event('Model.UrlHistory.beforeMarshal', $this->listener);

		// Case 1: scope is empty
		$data = new ArrayObject([
			'scope' => '',
			'foreignKey' => 123,
			'target' => 'some-target',
		]);

		$this->listener->beforeMarshal($event, $data, new ArrayObject());

		$this->assertNull($data['scope']);
		$this->assertNull($data['foreignKey']);
		$this->assertSame('some-target', $data['target']);

		// Case 2: scope is not empty
		$data = new ArrayObject([
			'scope' => 'some-scope',
			'foreignKey' => 456,
			'target' => 'some-target',
		]);

		$this->listener->beforeMarshal($event, $data, new ArrayObject());

		$this->assertSame('some-scope', $data['scope']);
		$this->assertSame(456, $data['foreignKey']);
		$this->assertNull($data['target']);
	}
}
