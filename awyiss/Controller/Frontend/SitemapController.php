<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Routing\Router;
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

		$lo_query = $lo_pagesTable->find('threaded')
		->find('all', skipPageRoleCheck: true)
		->where([
			'active' => true,
			'parents_active' => true,
			'robots_index' => true,
		])
		->contain([
			'Contents' => function (SelectQuery $query) {
				return $query->find('latestForPages');
			},
		]);

		$lo_pages = $lo_query->all()->listNested()->indexBy('id');
		$la_urls = [];

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$lo_lastMod = $lo_page->changedOn ?? $lo_page->createdOn;

			if (isset($lo_page->contents[0])) {
				$lo_lastMod = max($lo_lastMod, $lo_page->contents[0]->changedOn ?? $lo_page->contents[0]->createdOn);
			}

			$la_urls[] = [
				'loc' => Router::url(['lang' => $lo_page->languageShortcode, 'slug' => $lo_page->slug, '_full' => true]),
				'lastmod' => $lo_lastMod->format('Y-m-d'),
				'changefreq' => 'weekly',
				'priority' => '0.5',
			];
		}

		// Define a custom root node in the generated document.
		$this->viewBuilder()->setOption('rootNode', 'urlset')->setOption('serialize', ['@xmlns', 'url']);
		$this->set([
			// Define an attribute on the root node.
			'@xmlns' => 'https://www.sitemaps.org/schemas/sitemap/0.9/',
			'url' => $la_urls,
		]);
	}


	/**
	 * @return array<int, class-string>
	 */
	public function viewClasses(): array {
		return [XmlView::class];
	}
}
