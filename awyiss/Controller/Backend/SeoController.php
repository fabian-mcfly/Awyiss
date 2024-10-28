<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;


class SeoController extends BackendController {
	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';
	/**
	 * @var array<string>
	 */
	protected string $pageRole;
	/**
	 * @var array<string>
	 */
	protected array $pageRoles;
	/**
	 * @var string|null Session identifier for the selected page role
	 */
	protected ?string $selectedPageRoleSessionIdentifier = null;
	/**
	 * @var \Awyiss\Model\Table\PagesTable
	 */
	protected PagesTable $table;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function initialize(): void {
		parent::initialize();

		$this->initPageRoles();

		$this->selectedPageRoleSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.page_role';

		$lo_session = $this->request->getSession();

		if ($this->request->getParam('action') !== 'analyze') {
			$ls_pageRole = $lo_session->read($this->selectedPageRoleSessionIdentifier, 'all');
			if (!array_key_exists($ls_pageRole, $this->pageRoles) && $ls_pageRole !== 'all') {
				$ls_pageRole = array_key_first($this->pageRoles);
			}

			$this->setTable($ls_pageRole);

			return;
		}

		//Is there a request parameter with the name 'pageRole'?
		$ls_pageRole = $this->request->getParam('pageRole');

		if ($ls_pageRole) {
			$lo_session->write($this->selectedPageRoleSessionIdentifier, $ls_pageRole);
			$ls_pageRole = Inflector::underscore($ls_pageRole);
		}
		else {
			$ls_pageRole = $lo_session->read($this->selectedPageRoleSessionIdentifier, array_key_first($this->pageRoles));
		}

		//If the selected page role is not inside the available page roles, reset it to the first available one.
		if (!array_key_exists($ls_pageRole, $this->pageRoles) && $ls_pageRole !== 'all') {
			$ls_pageRole = array_key_first($this->pageRoles);

			$lo_session->write($this->selectedPageRoleSessionIdentifier, $ls_pageRole);

			//Redirect to remove the invalid scope parameter from the URL
			$this->redirect(['action' => 'analyze']);
		}

		$this->setTable($ls_pageRole);
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->table->find('forCurrentLanguage')->where($this->getOverviewWhere());
	}


	/**
	 * Analyze method
	 *
	 * @throws \Exception
	 */
	public function analyze(): void {
		$this->Authorization->ensure('analyze');

		$lo_query = $this->getOverviewQuery()->contain(['PageRoles']);

		if ($this->pageRole === 'all') {
			$lo_query->applyOptions([
				'skipPageRoleCheck' => true,
			]);
		}

		$lo_pages = $lo_query->find('threaded')->all();

		$la_summary = [
			'title' => [
				'empty' => [],
				'error' => [],
				'ok' => [],
				'warning' => [],
			],
			'description' => [
				'empty' => [],
				'error' => [],
				'ok' => [],
				'warning' => [],
			],
		];

		$ls_metaAppendix = Configure::read('Awyiss.System.Frontend.meta.titleSeparator') . Configure::read('Awyiss.System.Frontend.meta.titleAppendix');
		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages->listNested() as $lo_page) {
			$ls_pageTitle = $lo_page->metaTitle ?? $lo_page->title . $ls_metaAppendix;

			$lo_page->set('metaStatus', [
				'description' => [
					'length' => match (true) {
						strlen($lo_page->metaDescription ?? '') >= 160 => 'error',
						strlen($lo_page->metaDescription ?? '') >= 129 => 'warning',
						strlen($lo_page->metaDescription ?? '') === 0 => 'empty',
						default => 'ok',
					},
				],
				'title' => [
					'length' => match (true) {
						strlen($ls_pageTitle) >= 70 => 'error',
						strlen($ls_pageTitle) >= 56 => 'warning',
						strlen($ls_pageTitle) === 0 => 'empty',
						default => 'ok',
					},
					'set' => match (true) {
						$lo_page->metaTitle === null => 'auto',
						default => 'manual',
					},
				],
			]);

			$la_summary['title'][ $lo_page->metaStatus['title']['length'] ][] = $lo_page;
			$la_summary['description'][ $lo_page->metaStatus['description']['length'] ][] = $lo_page;

			$lo_page->set('metaTitle', $ls_pageTitle);
		}

		$this->set([
			'pages' => $lo_pages,
			'pageRole' => $this->pageRole,
			'pageRoles' => $this->pageRoles,
			'summary' => $la_summary,
			'attributes' => $this->table->getAttributes(),
		]);
	}


	/**
	 * @return array<string>
	 */
	#[NoDirectAccess]
	protected function initPageRoles(): array {
		$lo_pageRoles = $this->fetchTable('PageRoles')->find('active')->all();

		foreach ($lo_pageRoles as $lo_pageRole) {
			$this->pageRoles[ Inflector::pluralize($lo_pageRole->identifier) ] = $lo_pageRole->title;
		}

		return $this->pageRoles;
	}


	/**
	 * @param mixed $ls_pageRole
	 * @return void
	 */
	protected function setTable(mixed $ls_pageRole): void {
		$this->pageRole = $ls_pageRole;

		if ($ls_pageRole === 'all') {
			$ls_pageRole = 'pages';
		}

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable(Inflector::camelize($ls_pageRole));
	}
}
