<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
use Awyiss\Utility\Design\ScssCompiler;
use Awyiss\Utility\Design\ScssVariableProvider;
use Awyiss\Utility\Design\ScssVariableType;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Security;
use Exception;
use SplFileInfo;


/**
 * Designs Controller
 *
 * @property \Awyiss\Model\Table\DesignsTable $Designs
 */
class DesignsController extends Controller {
	/**
	 * @var \Cake\Collection\Collection
	 */
	protected Collection $designs;
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->Designs->find()->where($this->getOverviewWhere());
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'is_preview' => false,
		];
	}


	/**
	 * @return void
	 * @throws \Exception|\ScssPhp\ScssPhp\Exception\SassException
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_design = $this->ensureIdentifier();

		$la_designerConfig = Configure::read('Design');

		$lo_scssVariableProvider = new ScssVariableProvider($la_designerConfig);
		$la_internalVariables = $lo_scssVariableProvider->getNormalizedInternalVariables();

		$ls_webfontProviderClass = App::className('WebfontProvider', 'Utility/Design');
		$lo_webfontProvider = new $ls_webfontProviderClass();
		$la_webfonts = $lo_webfontProvider->getWebfonts();

		$lo_preview = null;
		if ($this->request->is(['patch', 'post', 'put'])) {
			if ($this->request->getData('cancel_preview') !== null) {
				$this->cancelPreview();
			}
			elseif ($this->request->getData('preview') !== null) {
				$lo_preview = $this->savePreviewData($la_internalVariables, $la_webfonts, isset($lo_scssVariableProvider->getInternalVariables()['includeColumnSystem']));
			}
			elseif ($this->request->getData('reset') !== null) {
				$this->reset();
			}
			else {
				$this->save($lo_design, $la_internalVariables, $la_webfonts, isset($lo_scssVariableProvider->getInternalVariables()['includeColumnSystem']));
			}
		}

		// If the design is a preview, save its identifier in the session
		$lo_session = $this->request->getSession();
		if (!$lo_design->inUse) {
			$lo_session->write('designPreviewIdentifier', $lo_design->identifier);
		}
		else {
			$lo_session->delete('designPreviewIdentifier');
		}

		/**
		 * Nest the variables that are associated with others
		 * The associated variables will be removed from the main array.
		 */
		$la_variables = $this->nestVariables($la_internalVariables);

		// Group the variables by their 'category' attribute. If a variable does not have a group, it will be placed in the 'variables' group.
		$la_variables = array_reduce(array_keys($la_variables), function ($carry, $key) use ($la_variables) {
			$la_item = $la_variables[ $key ];
			$ls_group = $la_item['category'] ?? 'variables';
			/** @noinspection PhpVariableNamingConventionInspection */
			$carry[ $ls_group ][ $key ] = $la_item;

			return $carry;
		}, []);

		$la_fontWeights = [];
		for ($li_i = 100; $li_i <= 900; $li_i += 100) {
			$la_fontWeights[ $li_i ] = $li_i;
		}

		$la_fontStacks = $lo_scssVariableProvider->getConfig('fontStacks');
		foreach ($la_fontStacks as $ls_category => $la_fonts) {
			$la_fontStacks[ $ls_category ] = array_combine($la_fonts, $la_fonts);
		}

		$lo_webfonts = new Collection($la_webfonts);
		$la_webfonts = $lo_webfonts->groupBy('category')->toArray();
		foreach ($la_webfonts as $ls_category => $la_fonts) {
			$lo_fonts = new Collection($la_fonts);
			$la_webfonts[ $ls_category ] = $lo_fonts->indexBy('id')->toArray();
		}

		$lo_query = $this->getOverviewQuery();
		$lo_designs = $this->paginate($lo_query);

		$this->set([
			'design' => $lo_design,
			'designs' => $lo_designs,
			'fontStacks' => $la_fontStacks,
			'fontWeights' => $la_fontWeights,
			'preview' => $lo_preview ,
			'previewIdentifier' => $lo_preview?->identifier,
			'units' => $lo_scssVariableProvider->getConfig('units'),
			'variables' => $la_variables,
			'webfonts' => $la_webfonts,
		]);
	}


	/**
	 * @param Design $design
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @param bool $includeColumnSystem
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 * @throws \Exception
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	protected function save(Design $design, array $internalVariables, array $webfonts, bool $includeColumnSystem = false): void {
		$la_requestData = $this->request->getData();
		$lb_use = !empty($la_requestData['use']);

		if ($lb_use) {
			$this->Authorization->ensure('use');
		}
		else {
			$this->Authorization->ensure('save');

			$design->unset('id');
			$design->setNew(true);
		}

		$la_requestData = $this->normalizeRequestData($la_requestData, $internalVariables, $webfonts);

		if ($lb_use) {
			$design->inUse = true;
		}
		else {
			$la_requestData['identifier'] = Security::randomString(12);

			$design->title = null;
			$design->description = null;
			$design->inUse = false;
		}

		$design->isPreview = false;

		$this->Designs->patchEntity($design, $la_requestData, [
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$design->title) {
			/** @var \Awyiss\Model\Entity\User $lo_identity */
			$lo_identity = $this->request->getAttribute('identity');

			$design->title = $lo_identity->username . ', ' . (new DateTime('now'))->format('Y-m-d H:i');

			if ($lb_use) {
				$design->title = 'Standard (' . $design->title . ')';
			}
		}

		$design->css = $this->generateCss($la_requestData, $includeColumnSystem);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Designs->save($design, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__('save_succeeded'));
				}

				$lo_session = $this->request->getSession();
				$lo_session->delete('designPreviewIdentifier');

				throw new RedirectException(Router::url(['action' => 'overview', 'identifier' => $lb_use ? $design->identifier : null], true), 302);
			}

			$this->Flash->error(__('save_failed'));
			foreach ($design->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * Traverse the internal variables and nest those that are associated with others,
	 * removing them from the main array.
	 *
	 * @param array $variables
	 * @return array
	 */
	protected function nestVariables(array $variables): array {
		$la_variables = $variables;
		foreach ($la_variables as &$la_options) {
			if (empty($la_options['associatedVariables'])) {
				continue;
			}

			foreach ($la_options['associatedVariables'] as $ls_associatedVariable) {
				if (isset($la_variables[ $ls_associatedVariable ])) {
					$la_options['associatedVariables'][ $ls_associatedVariable ] = $la_variables[ $ls_associatedVariable ];
					unset($la_variables[ $ls_associatedVariable ]);
				}
			}

			$la_options['associatedVariables'] = array_filter($la_options['associatedVariables'], function ($options, $key) use ($la_variables) {
				return !is_numeric($key) && is_array($options);
			}, ARRAY_FILTER_USE_BOTH);
		}

		return $la_variables;
	}


	/**
	 * @param array $requestData
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @return array
	 */
	protected function normalizeRequestData(array $requestData, array $internalVariables, array $webfonts): array {
		$la_requestData = ['settings' => []];

		$lo_internalVariables = new Collection($internalVariables);

		// Create a map of the internal variables and their underscored names
		$la_underscoredNames = array_map(fn ($key) => Inflector::underscore($key), array_keys($internalVariables));
		$la_variableMap = array_combine($la_underscoredNames, array_keys($internalVariables));

		foreach ($requestData as $ls_key => $lx_value) {
			if (in_array($ls_key, ['custom', 'font_variants', 'save_as_copy', 'reload_form', 'preview', 'save', 'use'])) {
				continue;
			}

			if (in_array($ls_key, ['title', 'description', '_translations'])) {
				$la_requestData[ $ls_key ] = $lx_value;
				continue;
			}

			if ($lx_value === 'custom') {
				$lx_value = $requestData['custom'][ $ls_key ] ?? '';
			}

			$la_variableOptions = $lo_internalVariables->filter(function ($variableOptions, $key) use ($ls_key) {
				return Inflector::underscore($key) === $ls_key;
			})->first();

			if ($la_variableOptions && $la_variableOptions['type'] === ScssVariableType::FontName) {
				if (isset($webfonts[ $lx_value ])) {
					$lx_value = [
						'font' => $webfonts[ $lx_value ],
						'variants' => $requestData['font_variants'][ $ls_key ] ?? [],
					];
				}
				else {
					$lx_value = [
						'font' => [
							'name' => $lx_value,
						],
						'variants' => $requestData['font_variants'][ $ls_key ] ?? [],
					];
				}
			}

			$ls_key = $la_variableMap[ $ls_key ] ?? $ls_key;

			if (str_ends_with($ls_key, '_unit')) {
				$ls_key = substr($ls_key, 0, -5);
				$ls_key = $la_variableMap[ $ls_key ] ?? $ls_key;
				$ls_key .= 'Unit';
			}

			$la_requestData['settings'][ $ls_key ] = $lx_value;

			if (!empty($lx_value) && isset($la_variableOptions['forcedUnit'])) {
				$la_requestData['settings'][ $ls_key . 'Unit' ] = $la_variableOptions['forcedUnit'];
			}
		}

		return $la_requestData;
	}


	/**
	 * @return \Awyiss\Model\Entity\Design
	 * @throws \Exception
	 */
	protected function ensureIdentifier(): Design {
		$this->loadDesigns();

		if (!$this->request->getParam('identifier') && !$this->request->getParam('preview')) {
			/** @var \Awyiss\Model\Entity\User $lo_identity */
			$lo_identity = $this->request->getAttribute('identity');

			// Check if the user has a preview design
			$ls_identifier = $this->designs->firstMatch([
				'isPreview' => true,
				'createdBy' => $lo_identity->id,
			])?->get('identifier');

			if ($ls_identifier) {
				// If the user has a preview design, redirect to the overview page
				throw new RedirectException(Router::url([
					'action' => 'overview',
					'preview' => $ls_identifier,
				], true), 302);
			}

			// If the user does not have a preview design, redirect to the first design in use
			$ls_identifier = $this->designs->firstMatch(['inUse' => true])->get('identifier');

			throw new RedirectException(Router::url([
				'action' => 'overview',
				'identifier' => $ls_identifier,
			], true), 302);
		}

		if ($this->request->getParam('preview')) {
			$lo_design = $this->designs->firstMatch([
				'identifier' => $this->request->getParam('preview'),
				'isPreview' => true,
			]);

			if ($lo_design) {
				return $lo_design;
			}
		}

		$ls_identifier = $this->request->getParam('identifier');

		// Get the design by its identifier
		/** @var \Awyiss\Model\Entity\Design $lo_design */
		$lo_design = $this->designs->firstMatch([
			'identifier' => $ls_identifier,
		]);

		if (!$lo_design) {
			$lo_design = $this->designs->firstMatch(['inUse' => true]);
		}

		if (!$lo_design) {
			throw new Exception('Design not found');
		}

		if (!$lo_design->inUse) {
			// If the design is not in use, make sure the user has the permission to load designs
			$this->Authorization->ensure('load');
		}

		if ($ls_identifier !== $lo_design->identifier) {
			throw new RedirectException(Router::url([
				'action' => 'overview',
				'identifier' => $lo_design->identifier,
			], true), 302);
		}

		return $lo_design;
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function loadDesigns(): void {
		if (!isset($this->designs)) {
			$this->designs = $this->Designs->find('translations')
			->contain([
				'CreatedByUser',
			])
			->orderByDesc('Designs.id')
			->all()->compile();
		}

		if (!$this->designs->count()) {
			$lo_design = $this->Designs->newDefaultEntity([
				'identifier' => Security::randomString(12),
				'title' => 'Standard',
				'inUse' => true,
			]);

			if (!$this->Designs->save($lo_design)) {
				throw new Exception('Could not create a default design');
			}

			$this->designs = $this->designs->append([$lo_design])->compile();
		}
	}


	/**
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @param bool $includeColumnSystem
	 * @return \Awyiss\Model\Entity\Design|null
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	protected function savePreviewData(array $internalVariables, array $webfonts, bool $includeColumnSystem = false): ?Design {
		$la_previewData = $this->request->getData();
		$la_previewData = $this->normalizeRequestData($la_previewData, $internalVariables, $webfonts);

		$lo_preview = null;
		if ($this->request->getData('preview')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_preview = $this->Designs->findByIdentifier($this->request->getData('preview'))->first();
		}

		if (!$lo_preview || !$lo_preview->isPreview) {
			$lo_preview = $this->Designs->newDefaultEntity([
				'identifier' => Security::randomString(12),
				'inUse' => false,
				'isPreview' => true,
			]);
		}

		unset($la_previewData['_translations']);

		$this->Designs->patchEntity($lo_preview, $la_previewData, ['validate' => false]);

		/** @var \Awyiss\Model\Entity\User $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');
		$lo_now = new DateTime('now');
		$lo_preview->title = sprintf('Preview (%s, %s)', $lo_identity->username, $lo_now->format('Y-m-d H:i'));

		$lo_preview->css = $this->generateCss($la_previewData, $includeColumnSystem);

		if ($this->Designs->save($lo_preview)) {
			throw new RedirectException(Router::url(['action' => 'overview', 'preview' => $lo_preview->identifier, '#' => 'Preview'], true), 302);
		}

		return null;
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Design $lo_design */
		$lo_design = $this->Designs->findById($id)->first();
		if (!$lo_design) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Designs->delete($lo_design)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_design->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @return void
	 */
	protected function reset(): void {
		$lo_session = $this->request->getSession();
		$lo_session->delete('designPreviewIdentifier');

		throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
	}


	/**
	 * @return void
	 */
	protected function cancelPreview(): void {
		$lo_session = $this->request->getSession();

		$ls_identifier = $lo_session->read('designPreviewIdentifier');
		if ($ls_identifier) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_design = $this->Designs->findByIdentifier($ls_identifier)->first();
			if ($lo_design) {
				$this->Designs->delete($lo_design);
			}
		}

		$lo_session->delete('designPreviewIdentifier');

		throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
	}


	/**
	 * @param array $data
	 * @param bool $includeColumnSystem
	 * @return string
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	protected function generateCss(array $data, bool $includeColumnSystem): string {
		$ls_css = '';
		$la_realmFolders = Configure::read('App.paths.assets.Frontend');
		$la_variables = ScssCompiler::normalizeVariables($data['settings']);

		foreach (Configure::read('Design.previewScssFiles', []) as $ls_scssFile) {
			foreach ($la_realmFolders as $ls_basePath) {
				if (!str_starts_with($ls_scssFile, $ls_basePath)) {
					continue;
				}

				// compileScss expects SplFileInfo, not a string, so convert it
				$ls_scssFile = new SplFileInfo($ls_scssFile);

				$ls_css .= ScssCompiler::compileScss($ls_scssFile, $ls_basePath, $la_variables, true, $includeColumnSystem) . PHP_EOL;
			}
		}

		return $ls_css;
	}
}
