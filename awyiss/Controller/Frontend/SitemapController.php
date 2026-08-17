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
		$pagesTable = $this->fetchTable('Pages');

		/**
		 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
		 */
		$query = $pagesTable
			->find('threaded', skipPageRoleCheck: true)
			->find('published')
			->where([
				'active' => true,
				'parentsActive' => true,
			])
			->contain([
				/** @see \Awyiss\Model\Table\ContentsTable::findLatestForPages() */
				'Contents' => fn(SelectQuery $query) => $query->find('latestForPages'),
			])
		;

		$pages = $query->all();

		/**
		 * Filter out all records with a parent_id that is not null
		 * This is necessary because the threaded finder
		 * returns all records in the tree, even if a record's
		 * parent is not active or not published.
		 */
		$pages = $pages->filter(fn(Page $page) => $page->parentId === null);

		$pages = $pages->listNested()->indexBy('id');

		// Get the first language
		$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		/** @var \Awyiss\Model\Entity\Language $firstLanguage */
		$firstLanguage = reset($languages);

		$includeLanguageShortcode = Configure::read('Route.includeLanguageShortcode');
		$firstPagesOfLanguage = [];

		$urls = [];
		/** @var \Awyiss\Model\Entity\Page $page */
		foreach ($pages as $page) {
			// Skip pages that are not indexable
			if (!$page->robotsIndex) {
				continue;
			}

			$lastMod = $page->changedOn ?? $page->createdOn;

			if (isset($page->contents[0])) {
				$lastMod = max($lastMod, $page->contents[0]->changedOn ?? $page->contents[0]->createdOn);
			}

			if (isset($firstPagesOfLanguage[ $page->languageShortcode ])) {
				if ($includeLanguageShortcode) {
					$urlParts = ['lang' => $page->languageShortcode, 'slug' => $page->slug, '_full' => true];
				}
				else {
					$urlParts = ['slug' => $page->slug, '_full' => true];
				}
			}
			else {
				$firstPagesOfLanguage[ $page->languageShortcode ] = true;

				if ($page->languageShortcode === $firstLanguage->shortcode) {
					$urlParts = ['_full' => true, '_name' => 'FrontendRoot'];
				}
				elseif ($includeLanguageShortcode) {
					$urlParts = ['lang' => $page->languageShortcode, '_full' => true, '_name' => 'FrontendLanguageRoot'];
				}
				else {
					$urlParts = ['slug' => $page->slug, '_full' => true];
				}
			}

			$url = Router::url($urlParts);

			$urls[] = [
				'loc' => $url,
				'lastmod' => $lastMod->format('Y-m-d'),
				'changefreq' => $page->sitemapChangefreq ?? 'weekly',
				'priority' => $page->sitemapPriority ?? '0.5',
			];
		}

		// Define a custom root node in the generated document.
		$this
			->viewBuilder()
			->setOption('rootNode', 'urlset')
			->setOption('serialize', ['xmlns:', 'url'])
		;
		/** @noinspection HttpUrlsUsage */
		$this->set([
			// Define an attribute on the root node.
			'url' => $urls,
			'xmlns:' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
		]);
	}


	/**
	 * Create the robots.txt
	 *
	 * @return \Cake\Http\Response
	 * @noinspection PhpUnused
	 */
	public function robots(): Response {
		// Generate absolute sitemap URL based on routing
		$sitemapUrl = $this->request->getAttribute('webroot') . 'sitemap.xml';
		$sitemapUrl = $this->request
			->getUri()
			->withPath($sitemapUrl)
			->__toString()
		;

		$content = "User-agent: *\n";
		$content .= "Disallow:\n";
		$content .= "Sitemap: $sitemapUrl\n";

		$this->response = $this->response->withType('text/plain')->withStringBody($content);

		return $this->response;
	}


	/**
	 * @return array<int, class-string>
	 */
	public function viewClasses(): array {
		return [XmlView::class];
	}
}
