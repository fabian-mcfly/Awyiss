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
	 * @throws \Exception
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
		$lo_page = $this->getPage($pageId);

		if (!$lo_page) {
			return $this->renderLoginLogo();
		}

		$la_pageData = $lo_page->extract();

		if (isset($la_pageData['attributes'])) {
			$la_pageData['attributes'] = $la_pageData['attributes']->extract();
		}

		$ls_hash = md5(json_encode($la_pageData));

		$ls_image = Cache::read('og_image_' . $ls_hash . '.png', 'persistent');
		if ($ls_image) {
			$lo_response = $this->getResponse()->withType('jpg');
			$lo_response = $lo_response->withStringBody($ls_image);

			return $lo_response;
		}

		$lx_success = $this->fetchOgImageScreenshot($lo_page);

		if ($lx_success === false) {
			return $this->renderLoginLogo();
		}

		Cache::write('og_image_' . $ls_hash . '.png', $lx_success, 'persistent');

		$lo_response = $this->getResponse()->withType('jpg');
		$lo_response = $lo_response->withStringBody($lx_success);
		return $lo_response;
	}


	/**
	 * @param int $pageId
	 * @return void
	 */
	public function template(int $pageId): void {
		$lo_page = $this->getPage($pageId);

		if (!$lo_page) {
			throw new NotFoundException();
		}

		/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $ls_className */
		$ls_className = App::className('MediaRenderOptions', 'Utility/Media');
		$lo_mediaRenderOptions = new $ls_className();

		$this->set([
			'page' => $lo_page,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);

		$this->viewBuilder()
			->setTemplate($lo_page->pageRole->identifier)
			->setTemplatePath('Frontend/open_graph')
			->setLayout('open_graph');
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
		$ls_logoPath = $this->getLoginLogoPath();
		if (!$ls_logoPath) {
			$ls_logoPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'img' . DS . 'logo-awyiss.png';
		}

		// Return the logo as response
		$lo_response = $this->getResponse()->withFile(
			$ls_logoPath,
			['download' => false, 'name' => 'login-logo']
		);

		return $lo_response;
	}


	/**
	 * Get the path to the login logo.
	 * This method checks for the existence of a login logo in the customer's `assets` directory
	 * with the name `login-logo` and the extensions `png`, `jpg`, or `svg`.
	 *
	 * @return string|null
	 */
	protected function getLoginLogoPath(): ?string {
		$ls_extensions = ['svg', 'png', 'jpg'];
		$ls_basePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'img' . DS . 'login-logo.';

		// For each extension, check if the file exists
		foreach ($ls_extensions as $ls_extension) {
			$ls_tempPath = $ls_basePath . $ls_extension;
			if (file_exists($ls_tempPath)) {
				return $ls_tempPath;
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
		$ls_url = 'https://screenshots.2f.media/api?';
		$ls_url .= http_build_query(['url' => Router::url('/_open-graph-template/id:' . $page->id . '/', true)]);

		$lo_client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$lo_response = $lo_client->get($ls_url);
		$la_data = $lo_response->getJson();

		if (
			!isset($la_data['screenshot']) ||
			!isset($la_data['hash']) ||
			!isset($la_data['signature'])
		) {
			return false;
		}

		$ls_pubPem = file_get_contents(ROOT . DS . 'awyiss' . DS . 'config' . DS . 'screenshots.2f.media.pem');
		if ($ls_pubPem === false) {
			return false;
		}

		$ls_sig = base64_decode($la_data['signature']);
		if ($ls_sig === false) {
			return false;
		}

		$li_status = openssl_verify(base64_decode($la_data['hash']), $ls_sig, $ls_pubPem, OPENSSL_ALGO_SHA256);

		if ($li_status !== 1) {
			return false;
		}

		return base64_decode($la_data['screenshot']);
	}


	/**
	 * @param int $pageId
	 * @return \Awyiss\Model\Entity\Page|null
	 */
	protected function getPage(int $pageId): ?Page {
		return $this->fetchTable('Pages')
			->find('active', skipPageRoleCheck: true)
			->find('published')
			->where(['id' => $pageId])
			->contain(['PageRoles'])
			->first();
	}
}
