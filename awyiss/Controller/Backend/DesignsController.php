<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
use Awyiss\Utility\Design\ScssCompiler;
use Awyiss\Utility\Design\ScssVariableProvider;
use Awyiss\Utility\Design\ScssVariableType;
use Awyiss\Utility\Design\WebfontProvider;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Http\Exception\RedirectException;
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
	protected Collection $designs;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return null;
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

		$lo_webfontProvider = new WebfontProvider();
		$la_webfonts = $lo_webfontProvider->getWebfonts();

		$lo_preview = null;
		if ($this->request->is(['patch', 'post', 'put'])) {
			if ($this->request->getData('cancel') !== null) {
				$this->cancelPreview();
			}

			if ($this->request->getData('preview') === null) {
				$this->save($lo_design, $la_internalVariables, $la_webfonts);
			}
			else {
				$lo_preview = $this->savePreviewData($la_internalVariables, $la_webfonts, isset($lo_scssVariableProvider->getInternalVariables()['includeColumnSystem']));
			}
		}

		// If the design is a preview, save its identifier in the session
		if ($lo_design->isPreview) {
			$lo_session = $this->request->getSession();
			$lo_session->write('designPreviewIdentifier', $lo_design->identifier);
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
		$la_webfonts = $lo_webfonts->filter(fn ($font) => $font['popularity'] < 1000)->groupBy('category')->toArray();
		foreach ($la_webfonts as $ls_category => $la_fonts) {
			$lo_fonts = new Collection($la_fonts);
			$la_webfonts[ $ls_category ] = $lo_fonts->indexBy('id')->toArray();
		}

		$this->set([
			'design' => $lo_design,
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
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 * @throws \Exception
	 */
	protected function save(Design $design, array $internalVariables, array $webfonts): void {
		$this->Authorization->ensure('save');

		$la_requestData = $this->request->getData();
		$la_requestData = $this->normalizeRequestData($la_requestData, $internalVariables, $webfonts);

		$this->Designs->patchEntity($design, $la_requestData, [
			'validate' => !$this->request->getData('reload_form'),
		]);

		$design->inUse = true;
		$design->isPreview = false;

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Designs->save($design, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__('save_succeeded'));
				}

				$lo_session = $this->request->getSession();
				$lo_session->delete('designPreviewIdentifier');

				throw new RedirectException(Router::url(['action' => 'overview', 'identifier' => $design->identifier], true), 302);
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
			if (in_array($ls_key, ['custom', 'font_variants', 'save_as_copy', 'reload_form', 'preview', 'save'])) {
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
			'inUse' => true,
		]);

		if (!$lo_design) {
			$lo_design = $this->designs->firstMatch(['inUse' => true]);
		}

		if (!$lo_design) {
			throw new Exception('Design not found');
		}

		if ($ls_identifier !== $lo_design->identifier) {
			throw new RedirectException(Router::url([
				'action' => 'overview',
				'identifier' => $lo_design->identifier,
			], true), 302);
		}

		if (!$lo_design->inUse) {
			// If the design is not in use, make sure the user has the permission to load designs
			$this->Authorization->ensure('load');
		}

		return $lo_design;
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function loadDesigns(): void {
		if (!isset($this->designs)) {
			$this->designs = $this->Designs->find()->all()->compile();
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

		if (!$lo_preview) {
			/** @var \Awyiss\Model\Entity\User $lo_identity */
			$lo_identity = $this->request->getAttribute('identity');

			$lo_now = new DateTime('now');

			$lo_preview = $this->Designs->newDefaultEntity([
				'identifier' => Security::randomString(12),
				'title' => sprintf('Preview (%s, %s)', $lo_identity->username, $lo_now->format('Y-m-d H:i')),
				'inUse' => false,
				'isPreview' => true,
			]);
		}

		$ls_css = '';
		$la_realmFolders = Configure::read('App.paths.assets.Frontend');
		$la_variables = $this->normaleizeVariables($la_previewData['settings']);

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

		$this->Designs->patchEntity($lo_preview, $la_previewData, ['validate' => false]);

		$lo_preview->css = $ls_css;

		if ($this->Designs->save($lo_preview)) {
			throw new RedirectException(Router::url(['action' => 'overview', 'preview' => $lo_preview->identifier, '#' => 'Preview'], true), 302);
		}

		return null;
	}


	/**
	 * @param array $vars
	 * @return array
	 */
	protected function normaleizeVariables(array $vars): array {
		$la_variables = $vars;
		$la_variables = ScssCompiler::normalizeVariables($la_variables);

		foreach ($la_variables as $ls_key => $lx_value) {
			if (is_array($lx_value)) {
				if (isset($lx_value['font'])) {
					$lx_value = 'inspect(' . $lx_value['font']['name'] . ')';
				}
				else {
					$lx_value = implode(' ', $lx_value);
				}
			}

			$la_variables[ $ls_key ] = $lx_value;
		}

		return $la_variables;
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
}
