<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Routing\Router;
use Cake\Controller\Controller;
use Cake\Http\Response;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;


/**
 * AppController
 */
abstract class AppController extends Controller {
	/**
	 * 1:1 implementation to use \Awyiss\Routing\Router
	 *
	 * @inheritDoc
	 * @param \Psr\Http\Message\UriInterface|array|string $url
	 * @param int $status
	 * @return \Cake\Http\Response|null
	 */
	public function redirect(UriInterface|array|string $url, int $status = 302): ?Response {
		$this->autoRender = false;

		if ($status < 300 || $status > 399) {
			throw new InvalidArgumentException(
				sprintf(
					'Invalid status code `%s`. It should be within the range ' . '`300` - `399` for redirect responses.',
					$status
				)
			);
		}

		$this->response = $this->response->withStatus($status);
		$event = $this->dispatchEvent('Controller.beforeRedirect', [$url, $this->response]);
		$result = $event->getResult();
		if ($result instanceof Response) {
			return $this->response = $result;
		}
		if ($event->isStopped()) {
			return null;
		}

		$response = $this->response;
		if (!$response->getHeaderLine('Location')) {
			$response = $response->withLocation(Router::url($url, true));
		}


		return $this->response = $response;
	}
}
