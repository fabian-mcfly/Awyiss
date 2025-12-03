<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase;


use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Utility\Hash;


/**
 * AwyissTest
 * Test the application methods
 */
class AwyissTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Awyiss::console()
	 */
	public function testConsoleAddsCommands(): void {
		$commands = $this->getMockBuilder(CommandCollection::class)->onlyMethods(['addMany'])->getMock();

		$capturedArgs = [];

		$commands->expects($this->exactly(2))->method('addMany')
		->willReturnCallback(function ($args) use (&$capturedArgs, $commands) {
			$capturedArgs[] = $args;

			return $commands;
		});

		$awyiss = $this->getMockBuilder(Awyiss::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

		$awyiss->console($commands);

		$this->assertArrayHasKey('awyiss backup', $capturedArgs[0]);
		$this->assertArrayHasKey('awyiss install', $capturedArgs[0]);

		$this->assertArrayHasKey('integrity_check', $capturedArgs[1]);
		$this->assertArrayHasKey('cache clear', $capturedArgs[1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::getRealm()
	 */
	public function testGetRealm(): void {
		Awyiss::setRealm('Frontend');
		$this->assertSame('Frontend', Awyiss::getRealm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::setRealm()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetRealm(): void {
		$eventManager = $this->createMock(EventManager::class);
		$eventManager->expects($this->once())->method('dispatch')->with(
			$this->callback(function (Event $event) {
				return $event->getName() === 'Awyiss.setRealm' && $event->getData('realm') === 'Backend';
			})
		);

		EventManager::instance($eventManager);

		Awyiss::setRealm('Backend');
		$this->assertSame('Backend', Awyiss::getRealm());

		$eventManager = $this->createMock(EventManager::class);
		$eventManager->expects($this->once())->method('dispatch')->with(
			$this->callback(function (Event $event) {
				return $event->getName() === 'Awyiss.setRealm' && $event->getData('realm') === null;
			})
		);

		EventManager::instance($eventManager);

		Awyiss::setRealm(null);
		$this->assertNull(Awyiss::getRealm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::getRealms()
	 */
	public function testGetRealms(): void {
		$expectedRealms = ['Frontend', 'Backend'];
		$this->assertSame($expectedRealms, Awyiss::getRealms());
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::getDatabaseConfiguration()
	 */
	public function testGetDatabaseConfiguration(): void {
		$frontendLanguage = 'en';
		$backendLanguage = 'de';

		$expectedConfig = [
			'Awyiss.News.Backend.somethingTranslatable' => 'deutsch',
			'Awyiss.News.Frontend.somethingTranslatableForFrontend' => 'english',
			'Awyiss.Newscategories.Backend.categories.allowAggregation' => true,
			'Awyiss.ContentTemplates.Backend.overview.displayedFields' => ['file_name', 'created_by', 'created_on'],
		];

		$result = Awyiss::getDatabaseConfiguration($frontendLanguage, $backendLanguage);

		foreach ($expectedConfig as $key => $value) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($value, $result[ $key ]);
		}

		$frontendLanguage = 'de';
		$backendLanguage = 'en';

		$expectedConfig = [
			'Awyiss.News.Backend.somethingTranslatable' => 'english',
			'Awyiss.News.Frontend.somethingTranslatableForFrontend' => 'deutsch',
			'Awyiss.Newscategories.Backend.categories.allowAggregation' => true,
			'Awyiss.ContentTemplates.Backend.overview.displayedFields' => ['file_name', 'created_by', 'created_on'],
		];

		$result = Awyiss::getDatabaseConfiguration($frontendLanguage, $backendLanguage);

		foreach ($expectedConfig as $key => $value) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($value, $result[ $key ]);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::getFileConfiguration()
	 */
	public function testGetFileConfiguration(): void {
		$result = Awyiss::getFileConfiguration([]);

		$expectedConfig = [
			'Awyiss.Pages.Backend.publicationData.enabled' => true,
			'Awyiss.Pages.Backend.sampleEntry' => true,
			'Awyiss.Attributes.Backend.overview.displayedFields' => ['identifier', 'input_type', 'default_value'],
		];

		foreach ($expectedConfig as $key => $value) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($value, $result[ $key ]);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Awyiss::loadUserConfiguration()
	 */
	public function testAddUserConfiguration(): void {
		$this->login();

		Configure::write('Awyiss');

		Awyiss::loadUserConfiguration();

		$config = Hash::flatten(Configure::read('Awyiss', []));

		$expectedConfig = [
			'Attributes.Backend.paginate.limit' => 1,
			'System.Backend.interface.darkMode' => true,
			'UrlHistory.Backend.paginate.limit' => 50,
		];

		foreach ($expectedConfig as $key => $value) {
			$this->assertArrayHasKey($key, $config);
			$this->assertSame($value, $config[ $key ]);
		}
	}
}
