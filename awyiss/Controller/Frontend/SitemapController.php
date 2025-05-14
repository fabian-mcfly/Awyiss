<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Awyiss;
use Awyiss\Controller\AppController;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\View\XmlView;


/**
 * SitemapController handles the sitemap generation
 */
class SitemapController extends AppController {
	/**
	 * Create the sitemap.xml
	 *
	 * @return void
	 */
	public function index(): void {
		$lo_pagesTable = $this->fetchTable('Pages');

		$lo_query = $lo_pagesTable->find('threaded', skipPageRoleCheck: true)
			->find('published')
			->where([
				'active' => true,
				'parents_active' => true,
			])
			->contain([
				'Contents' => function (SelectQuery $query) {
					return $query->find('latestForPages');
				},
			]);

		$lo_pages = $lo_query->all();

		/**
		 * Filter out all records with a parent_id that is not null
		 * This is necessary because the threaded finder
		 * returns all records in the tree, even if a record's
		 * parent is not active or not published.
		 */
		$lo_pages = $lo_pages->filter(function (Page $page): bool {
			return $page->parentId === null;
		});

		$lo_pages = $lo_pages->listNested()->indexBy('id');

		// Get the first language
		$la_languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		/** @var \Awyiss\Model\Entity\Language $lo_firstLanguage */
		$lo_firstLanguage = reset($la_languages);

		$lb_includeLanguageShortcode = Configure::read('Route.includeLanguageShortcode');
		$la_firstPagesOfLanguage = [];

		$la_urls = [];
		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			// Skip pages that are not indexable
			if (!$lo_page->robotsIndex) {
				continue;
			}

			$lo_lastMod = $lo_page->changedOn ?? $lo_page->createdOn;

			if (isset($lo_page->contents[0])) {
				$lo_lastMod = max($lo_lastMod, $lo_page->contents[0]->changedOn ?? $lo_page->contents[0]->createdOn);
			}

			if (isset($la_firstPagesOfLanguage[ $lo_page->languageShortcode ])) {
				if ($lb_includeLanguageShortcode) {
					$ls_url = Router::url(['lang' => $lo_page->languageShortcode, 'slug' => $lo_page->slug, '_full' => true]);
				}
				else {
					$ls_url = Router::url(['slug' => $lo_page->slug, '_full' => true]);
				}
			}
			else {
				$la_firstPagesOfLanguage[ $lo_page->languageShortcode ] = true;

				if ($lo_page->languageShortcode === $lo_firstLanguage->shortcode) {
					$ls_url = Router::url(['_full' => true, '_name' => 'FrontendRoot']);
				}
				elseif ($lb_includeLanguageShortcode) {
					$ls_url = Router::url(['lang' => $lo_page->languageShortcode, '_full' => true, '_name' => 'FrontendLanguageRoot']);
				}
				else {
					$ls_url = Router::url(['slug' => $lo_page->slug, '_full' => true]);
				}
			}

			$la_urls[] = [
				'loc' => $ls_url,
				'lastmod' => $lo_lastMod->format('Y-m-d'),
				'changefreq' => 'weekly',
				'priority' => '0.5',
			];
		}

		// Define a custom root node in the generated document.
		$this->viewBuilder()->setOption('rootNode', 'urlset')->setOption('serialize', ['xmlns:', 'url']);
		$this->set([
			// Define an attribute on the root node.
			'url' => $la_urls,
			'xmlns:' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
		]);
	}


	/**
	 * Create the robots.txt
	 *
	 * @return \Cake\Http\Response
	 */
	public function robots(): Response {
		// Generate absolute sitemap URL based on routing
		$ls_sitemapUrl = $this->request->getAttribute('webroot') . 'sitemap.xml';
		$ls_sitemapUrl = $this->request->getUri()->withPath($ls_sitemapUrl)->__toString();

		$ls_content = "User-agent: *\n";
		$ls_content .= "Disallow:\n";
		$ls_content .= "Sitemap: $ls_sitemapUrl\n";

		$this->response = $this->response->withType('text/plain')->withStringBody($ls_content);

		return $this->response;
	}


	/**
	 * @return array<int, class-string>
	 */
	public function viewClasses(): array {
		return [XmlView::class];
	}
}
