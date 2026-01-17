<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
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

		$design = $this->ensureIdentifier();

		$designerConfig = Configure::read('Design');

		/** @var class-string<\Awyiss\Utility\Design\ScssVariableProvider> $scssVariableProviderClass */
		$scssVariableProviderClass = App::className('ScssVariableProvider', 'Utility/Design');
		$scssVariableProvider = new $scssVariableProviderClass($designerConfig);
		$internalVariables = $scssVariableProvider->getNormalizedInternalVariables();

		/** @var class-string<\Awyiss\Utility\Design\WebfontProvider> $webfontProviderClass */
		$webfontProviderClass = App::className('WebfontProvider', 'Utility/Design');
		$webfontProvider = new $webfontProviderClass();
		$webfonts = $webfontProvider->getWebfonts();

		$preview = null;
		if ($this->request->is(['patch', 'post', 'put'])) {
			if ($this->request->getData('cancel_preview') !== null) {
				$this->cancelPreview();
			}
			elseif ($this->request->getData('preview') !== null) {
				$preview = $this->savePreviewData($internalVariables, $webfonts);
			}
			elseif ($this->request->getData('reset') !== null) {
				$this->reset();
			}
			else {
				$this->save($design, $internalVariables, $webfonts);
			}
		}

		// If the design is a preview, save its identifier in the session
		$session = $this->request->getSession();
		if (!$design->inUse) {
			$session->write('designPreviewIdentifier', $design->identifier);
		}
		else {
			$session->delete('designPreviewIdentifier');
		}

		/**
		 * Nest the variables that are associated with others
		 * The associated variables will be removed from the main array.
		 */
		$variables = $this->nestVariables($internalVariables);

		// Group the variables by their 'category' attribute. If a variable does not have a group, it will be placed in the 'variables' group.
		$variables = array_reduce(array_keys($variables), function (array $carry, string $key) use ($variables): array {
			$item = $variables[ $key ];
			$group = $item['category'] ?? 'variables';
			$carry[ $group ][ $key ] = $item;

			return $carry;
		}, []);

		$fontWeights = [];
		for ($i = 100; $i <= 900; $i += 100) {
			$fontWeights[ $i ] = $i;
		}

		$fontStacks = $scssVariableProvider->getConfig('fontStacks');
		foreach ($fontStacks as $category => $fonts) {
			$fontStacks[ $category ] = array_combine($fonts, $fonts);
		}

		$webfonts = new Collection($webfonts)->groupBy('category')->toArray();
		foreach ($webfonts as $category => $fonts) {
			$fonts = new Collection($fonts);
			$webfonts[ $category ] = $fonts->indexBy('id')->toArray();
		}

		$query = $this->getOverviewQuery();
		$designs = $this->paginate($query);

		$this->set([
			'design' => $design,
			'designs' => $designs,
			'fontStacks' => $fontStacks,
			'fontWeights' => $fontWeights,
			'preview' => $preview ,
			'previewIdentifier' => $preview?->identifier,
			'units' => $scssVariableProvider->getConfig('units'),
			'variables' => $variables,
			'webfonts' => $webfonts,
		]);
	}


	/**
	 * @param Design $design
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @return void
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 * @throws \Cake\Http\Exception\ForbiddenException
	 * @throws \Exception
	 */
	protected function save(Design $design, array $internalVariables, array $webfonts): void {
		$requestData = $this->request->getData();
		$use = !empty($requestData['use']);

		if ($use) {
			$this->Authorization->ensure('use');
		}
		else {
			$this->Authorization->ensure('save');

			$design->unset('id');
			$design->setNew(true);
		}

		$requestData = $this->normalizeRequestData($requestData, $internalVariables, $webfonts);

		if ($use) {
			$design->inUse = true;
		}
		else {
			$requestData['identifier'] = Security::randomString(12);

			$design->title = null;
			$design->description = null;
			$design->inUse = false;
		}

		$design->isPreview = false;

		$this->Designs->patchEntity($design, $requestData, [
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$design->title) {
			/** @var \Awyiss\Model\Entity\User $identity */
			$identity = $this->request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');

			$design->title = $identity->username . ', ' . new DateTime('now')->format('Y-m-d H:i');

			if ($use) {
				$design->title = 'Standard (' . $design->title . ')';
			}
		}

		$design->css = $this->generateCss($requestData);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Designs->save($design, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__('save_succeeded'));
				}

				$session = $this->request->getSession();
				$session->delete('designPreviewIdentifier');

				throw new RedirectException(Router::url(['action' => 'overview', 'identifier' => $use ? $design->identifier : null], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('save_failed'));
				foreach ($design->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		foreach ($variables as &$options) {
			if (empty($options['associatedVariables'])) {
				continue;
			}

			foreach ($options['associatedVariables'] as $associatedVariable) {
				if (isset($variables[ $associatedVariable ])) {
					$options['associatedVariables'][ $associatedVariable ] = $variables[ $associatedVariable ];
					unset($variables[ $associatedVariable ]);
				}
			}

			$options['associatedVariables'] = array_filter($options['associatedVariables'], function ($options, $key) use ($variables) {
				return !is_numeric($key) && is_array($options);
			}, ARRAY_FILTER_USE_BOTH);
		}

		return $variables;
	}


	/**
	 * @param array $requestData
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @return array
	 */
	protected function normalizeRequestData(array $requestData, array $internalVariables, array $webfonts): array {
		$data = ['settings' => []];

		// Create a map of the internal variables and their underscored names
		$underscoredNames = array_map(fn ($key) => Inflector::underscore($key), array_keys($internalVariables));
		$variableMap = array_combine($underscoredNames, array_keys($internalVariables));
		$internalVariables = new Collection($internalVariables);

		foreach ($requestData as $key => $value) {
			if (in_array($key, ['custom', 'font_variants', 'save_as_copy', 'reload_form', 'preview', 'save', 'use'])) {
				continue;
			}

			if (in_array($key, ['title', 'description', '_translations'])) {
				$data[ $key ] = $value;
				continue;
			}

			if ($value === 'custom') {
				$value = $requestData['custom'][ $key ] ?? '';
			}

			$variableOptions = $internalVariables->filter(function ($variableOptions, $variableKey) use ($key) {
				return Inflector::underscore($variableKey) === $key;
			})->first();

			if ($variableOptions && $variableOptions['type'] === ScssVariableType::FontName) {
				if (isset($webfonts[ $value ])) {
					$value = [
						'font' => $webfonts[ $value ],
						'variants' => $requestData['font_variants'][ $key ] ?? [],
					];
				}
				else {
					$value = [
						'font' => [
							'name' => $value,
						],
						'variants' => $requestData['font_variants'][ $key ] ?? [],
					];
				}
			}

			// Don't save empty font stacks
			if (
				$variableOptions &&
				$variableOptions['type'] === ScssVariableType::FontStack &&
				empty($value)
			) {
				continue;
			}

			$key = $variableMap[ $key ] ?? $key;

			if (str_ends_with($key, '_unit')) {
				$key = substr($key, 0, -5);
				$key = $variableMap[ $key ] ?? $key;
				$key .= 'Unit';
			}

			$data['settings'][ $key ] = $value;

			if (!empty($value) && isset($variableOptions['forcedUnit'])) {
				$data['settings'][ $key . 'Unit' ] = $variableOptions['forcedUnit'];
			}
		}

		return $data;
	}


	/**
	 * @return \Awyiss\Model\Entity\Design
	 * @throws \Exception
	 */
	protected function ensureIdentifier(): Design {
		$this->loadDesigns();

		if (!$this->request->getParam('identifier') && !$this->request->getParam('preview')) {
			/** @var \Awyiss\Model\Entity\User $identity */
			$identity = $this->request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');

			// Check if the user has a preview design
			$identifier = $this->designs->firstMatch([
				'isPreview' => true,
				'createdBy' => $identity->id,
			])?->get('identifier');

			if ($identifier) {
				// If the user has a preview design, redirect to the overview page
				throw new RedirectException(Router::url([
					'action' => 'overview',
					'preview' => $identifier,
				], true), 302);
			}

			// If the user does not have a preview design, redirect to the first design in use
			$identifier = $this->designs->firstMatch(['inUse' => true])->get('identifier');

			throw new RedirectException(Router::url([
				'action' => 'overview',
				'identifier' => $identifier,
			], true), 302);
		}

		if ($this->request->getParam('preview')) {
			$design = $this->designs->firstMatch([
				'identifier' => $this->request->getParam('preview'),
				'isPreview' => true,
			]);

			if ($design) {
				return $design;
			}
		}

		$identifier = $this->request->getParam('identifier');

		// Get the design by its identifier
		/** @var \Awyiss\Model\Entity\Design $design */
		$design = $this->designs->firstMatch([
			'identifier' => $identifier,
		]);

		if (!$design) {
			$design = $this->designs->firstMatch(['inUse' => true]);
		}

		if (!$design) {
			throw new Exception('Design not found');
		}

		if (!$design->inUse) {
			// If the design is not in use, make sure the user has the permission to load designs
			$this->Authorization->ensure('load');
		}

		if ($identifier !== $design->identifier) {
			throw new RedirectException(Router::url([
				'action' => 'overview',
				'identifier' => $design->identifier,
			], true), 302);
		}

		return $design;
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function loadDesigns(): void {
		if (!isset($this->designs)) {
			/** @uses \Awyiss\Model\Table::findTranslations() */
			$this->designs = $this->Designs->find('translations')
			->contain([
				'CreatedByUser',
			])
			->orderByDesc('Designs.id')
			->all()->compile();
		}

		if (!$this->designs->count()) {
			$design = $this->Designs->newDefaultEntity([
				'identifier' => Security::randomString(12),
				'title' => 'Standard',
				'inUse' => true,
			]);

			if (!$this->Designs->save($design)) {
				throw new Exception('Could not create a default design');
			}

			$this->designs = $this->designs->append([$design])->compile();
		}
	}


	/**
	 * @param array $internalVariables
	 * @param array $webfonts
	 * @return \Awyiss\Model\Entity\Design|null
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	protected function savePreviewData(array $internalVariables, array $webfonts): ?Design {
		$previewData = $this->request->getData();
		$previewData = $this->normalizeRequestData($previewData, $internalVariables, $webfonts);

		/** @var \Awyiss\Model\Entity\Design|null $preview */
		$preview = null;
		if ($this->request->getData('preview')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$preview = $this->Designs->findByIdentifier($this->request->getData('preview'))->first();
		}

		if (!$preview?->isPreview) {
			$preview = $this->Designs->newDefaultEntity([
				'identifier' => Security::randomString(12),
				'inUse' => false,
				'isPreview' => true,
			]);
		}

		unset($previewData['_translations']);

		$this->Designs->patchEntity($preview, $previewData, ['validate' => false]);

		/** @var \Awyiss\Model\Entity\User $identity */
		$identity = $this->request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');
		$now = new DateTime('now');
		$preview->title = sprintf('Preview (%s, %s)', $identity->username, $now->format('Y-m-d H:i'));

		$preview->css = $this->generateCss($previewData);

		if ($this->Designs->save($preview)) {
			throw new RedirectException(Router::url(['action' => 'overview', 'preview' => $preview->identifier, '#' => 'Preview'], true), 302);
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

		/** @var \Awyiss\Model\Entity\Design $design */
		$design = $this->Designs->findById($id)->first();
		if (!$design) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Designs->delete($design)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($design->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @return void
	 */
	protected function reset(): void {
		$session = $this->request->getSession();
		$session->delete('designPreviewIdentifier');

		throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
	}


	/**
	 * @return void
	 */
	protected function cancelPreview(): void {
		$session = $this->request->getSession();

		$identifier = $session->read('designPreviewIdentifier');
		if ($identifier) {
			/**
			 * @var \Awyiss\Model\Entity\Design|null $design
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$design = $this->Designs->findByIdentifier($identifier)->first();
			if ($design) {
				$this->Designs->delete($design);
			}
		}

		$session->delete('designPreviewIdentifier');

		throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
	}


	/**
	 * @param array $data
	 * @return string
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	protected function generateCss(array $data): string {
		$css = '';
		$realmFolders = Configure::read('App.paths.assets.Frontend');

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $className */
		$className = App::className('ScssCompiler', 'Utility/Design');

		/** @var class-string<\Awyiss\Utility\Design\ScssVariableProvider> $scssVariableProviderClass */
		$scssVariableProviderClass = App::className('ScssVariableProvider', 'Utility/Design');
		$scssVariableProvider = new $scssVariableProviderClass(Configure::read('Design'));

		foreach (Configure::read('Design.previewScssFiles', []) as $scssFile) {
			foreach ($realmFolders as $basePath) {
				if (!str_starts_with($scssFile, $basePath)) {
					continue;
				}

				$scssVariableProvider->setScssFiles([$scssFile]);

				$internalVariables = $scssVariableProvider->getInternalVariables();
				$includeColumnSystem = isset($internalVariables['includeColumnSystem']) && $internalVariables['includeColumnSystem']->getValue() === true;

				// compileScss expects SplFileInfo, not a string, so convert it
				$scssFile = new SplFileInfo($scssFile);

				$css .= $className::compileScss($scssFile, $basePath, $data['settings'], true, $includeColumnSystem) . PHP_EOL;
			}
		}

		return $css;
	}
}
