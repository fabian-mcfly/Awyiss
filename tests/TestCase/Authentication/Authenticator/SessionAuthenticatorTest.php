<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authentication\Authenticator;


use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Model\Entity\User;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequestFactory;
use Cake\Http\Session;


/**
 * SessionAuthenticatorTest class
 */
class SessionAuthenticatorTest extends TestCase {
	/**
	 * @return void
	 */
	public function testCallableAuthentication(): void {
		$authenticator = new SessionAuthenticator(null, [
			'sessionKey' => 'Backend.Auth',
		]);

		$authenticator->setConfig('identify', function ($user) {
			$this->assertInstanceOf(User::class, $user);

			return false;
		});

		$request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/dummy/'],
			[],
			[],
		);

		$users = FactoryLocator::get('Table')->get('Users');
		$user = $users->get(1);

		$sessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['read', 'write', 'delete', 'renew', 'check'])->getMock();
		$sessionMock->expects($this->once())->method('read')->with('Backend.Auth')->willReturn($user);

		$request = $request->withAttribute('session', $sessionMock);

		$authenticator->authenticate($request);
	}
}
