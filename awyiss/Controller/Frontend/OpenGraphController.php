<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;


/**
 * The Open Graph Controller handles Open Graph actions.
 */
class OpenGraphController extends AppController {
	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		AppController::initialize();

		$this->viewBuilder()->setClassName('Frontend');
	}


	/**
	 * Returns the Open Graph image for a page
	 *
	 * @param int $pageId The page ID
	 * @return \Cake\Http\Response
	 */
	public function image(int $pageId): Response {
		$page = $this->getPage($pageId);

		if (!$page) {
			return $this->renderLoginLogo();
		}

		$pageData = $page->extract();

		if (isset($pageData['attributes'])) {
			$pageData['attributes'] = $pageData['attributes']->extract();
		}

		$hash = md5(json_encode($pageData));

		$image = Cache::read('og_image_' . $hash . '.png', 'persistent');
		if ($image) {
			$response = $this->getResponse()->withType('jpg');

			return $response->withStringBody($image);
		}

		$success = $this->fetchOgImageScreenshot($page);

		if ($success === false) {
			return $this->renderLoginLogo();
		}

		Cache::write('og_image_' . $hash . '.png', $success, 'persistent');

		$response = $this->getResponse()->withType('jpg');

		return $response->withStringBody($success);
	}


	/**
	 * @param int $pageId
	 * @return void
	 */
	public function template(int $pageId): void {
		$page = $this->getPage($pageId);

		if (!$page) {
			throw new NotFoundException();
		}

		/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $className */
		$className = App::className('MediaRenderOptions', 'Utility/Media');
		$mediaRenderOptions = new $className();

		$this->set([
			'page' => $page,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);

		$this
			->viewBuilder()
			->setTemplate($page->pageRole->identifier)
			->setTemplatePath('Frontend/open_graph')
			->setLayout('open_graph')
		;
	}


	/**
	 * @inheritDoc
	 */
	public function render(?string $template = null, ?string $layout = null): Response {
		try {
			return parent::render($template, $layout);
		}
		catch (MissingTemplateException) {
			return parent::render('default', $layout);
		}
	}


	/**
	 * Render the login logo as response
	 *
	 * @return \Cake\Http\Response
	 */
	protected function renderLoginLogo(): Response {
		// Find the customer logo
		$logoPath = $this->getLoginLogoPath();
		if (!$logoPath) {
			$logoPath = APP . 'assets' . DS . 'img' . DS . 'logo-awyiss.png';
		}

		// Return the logo as response
		return $this
			->getResponse()
			->withFile(
				$logoPath,
				['download' => false, 'name' => 'login-logo']
			)
		;
	}


	/**
	 * Get the path to the login logo.
	 * This method checks for the existence of a login logo in the customer's `assets` directory
	 * with the name `login-logo` and the extensions `png`, `jpg`, or `svg`.
	 *
	 * @return string|null
	 */
	protected function getLoginLogoPath(): ?string {
		$extensions = ['svg', 'png', 'jpg'];
		$basePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'img' . DS . 'login-logo.';

		// For each extension, check if the file exists
		foreach ($extensions as $extension) {
			$tempPath = $basePath . $extension;
			if (file_exists($tempPath)) {
				return $tempPath;
			}
		}

		return null;
	}


	/**
	 * Fetches the Open Graph image screenshot from screenshots.2f.media
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return string|false
	 */
	protected function fetchOgImageScreenshot(Page $page): string|false {
		$url = 'https://screenshots.2f.media/api?';
		$url .= http_build_query(['url' => Router::url('/_open-graph-template/id:' . $page->id . '/', true)]);

		$client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$response = $client->get($url);
		$data = $response->getJson();

		if (
			!isset($data['screenshot'])
			|| !isset($data['hash'])
			|| !isset($data['signature'])
		) {
			return false;
		}

		$pubPem = file_get_contents(APP . DS . 'config' . DS . 'screenshots.2f.media.pem');
		if ($pubPem === false) {
			return false;
		}

		$sig = base64_decode($data['signature']);
		if ($sig === false) {
			return false;
		}

		$status = openssl_verify(base64_decode($data['hash']), $sig, $pubPem, OPENSSL_ALGO_SHA256);

		if ($status !== 1) {
			return false;
		}

		return base64_decode($data['screenshot']);
	}


	/**
	 * @param int $pageId
	 * @return \Awyiss\Model\Entity\Page|null
	 */
	protected function getPage(int $pageId): ?Page {
		return $this
			->fetchTable('Pages')
			->find('active', skipPageRoleCheck: true)
			->find('published')
			->where(['id' => $pageId])
			->contain(['PageRoles'])
			->first()
		;
	}
}
