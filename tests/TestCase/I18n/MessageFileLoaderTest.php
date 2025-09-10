<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\I18n;


use Awyiss\Awyiss;
use Awyiss\I18n\MessagesFileLoader;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Test\TestSuite\TestCase;


/**
 * MessageFileLoader Test Case
 *
 * @see \Awyiss\I18n\MessagesFileLoader
 */
class MessageFileLoaderTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\I18n\MessagesFileLoader::__invoke()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeAddsFilesFromMultiplePaths(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$loader = new MessagesFileLoader('default', 'en_ZW');
		$package = $loader();
		$messages = $package->getMessages();

		$this->assertCount(2, $messages);

		$message = $package->getMessage('dummy_string');
		$this->assertNotEmpty($message);
		$this->assertArrayHasKey('_context', $message);
		$this->assertArrayHasKey('', $message['_context']);
		$this->assertSame('This is a dummy string for testing purposes in en_ZW locale.', $message['_context']['']);

		$message = $package->getMessage('another_dummy');
		$this->assertNotEmpty($message);
		$this->assertArrayHasKey('_context', $message);
		$this->assertArrayHasKey('', $message['_context']);
		$this->assertSame('Another dummy string to ensure localization works correctly.', $message['_context']['']);
	}
}
