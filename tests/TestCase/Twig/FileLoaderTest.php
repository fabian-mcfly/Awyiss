<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Twig;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Twig\FileLoader;
use Cake\Core\Configure;
use Twig\Error\LoaderError;


/**
 * Test case for FileLoader
 *
 * @see \Awyiss\Twig\FileLoader
 */
class FileLoaderTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::getPaths()
	 */
	public function testGetPaths(): void {
		$loader = new FileLoader(['.twig']);
		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertArrayHasKey('customer', $paths);
		$this->assertArrayHasKey('awyiss', $paths);

		// Make sure the paths are sorted, customer first
		$this->assertSame(['customer', 'awyiss'], array_keys($paths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::getPaths()
	 */
	public function testGetPathsWithNamespace(): void {
		$loader = new FileLoader(['.twig']);
		$paths = $loader->getPaths('customer');

		$this->assertIsArray($paths);
		$this->assertCount(0, $paths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::setPaths()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testSetPathsWithString(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame(TMP, $paths[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::setPaths()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testSetPathsWithArray(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(['dummy' => TMP]);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame(TMP, $paths[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::setPaths()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testSetPathsWithNamespace(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP, 'dummy');

		// Default namespace should not be affected
		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(2, $paths);

		$this->assertFalse(in_array(TMP, $paths, true), 'TMP path should not be in default namespace paths');

		$paths = $loader->getPaths('dummy');

		$this->assertIsArray($paths);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertTrue(in_array(TMP, $paths, true), 'TMP path should be in dummy namespace paths');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPath(): void {
		$loader = new FileLoader(['.twig']);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(2, $paths, 'Default paths should be set');

		$loader->addPath(TMP);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame(TMP, $paths[0]);

		$this->assertSame(['customer', 'awyiss', 0], array_keys($paths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPathAvoidsDuplicates(): void {
		$loader = new FileLoader(['.twig']);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(2, $paths, 'Default paths should be set');

		$loader->addPath(TMP);
		$loader->addPath(TMP);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(3, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame(TMP, $paths[0]);

		$this->assertSame(['customer', 'awyiss', 0], array_keys($paths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPathWithPrepend(): void {
		$loader = new FileLoader(['.twig']);
		$loader->addPath(TMP, FileLoader::MAIN_NAMESPACE, true);

		$paths = $loader->getPaths();

		$this->assertSame([0, 'customer', 'awyiss'], array_keys($paths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPathWithPrependPrependsAlreadyExistingPaths(): void {
		$loader = new FileLoader(['.twig']);
		$loader->addPath(TMP);

		$paths = $loader->getPaths();

		$this->assertSame(['customer', 'awyiss', 0], array_keys($paths));

		// Add another path to check if it prepends correctly
		$loader->addPath(TMP, FileLoader::MAIN_NAMESPACE, true);

		$paths = $loader->getPaths();

		$this->assertSame([0, 'customer', 'awyiss'], array_keys($paths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPathWithNamespace(): void {
		$loader = new FileLoader(['.twig']);

		$paths = $loader->getPaths('dummy');

		$this->assertIsArray($paths);
		$this->assertCount(0, $paths, 'Dummy namespace should be empty initially');

		$loader->addPath(TMP, 'dummy');

		$paths = $loader->getPaths('dummy');

		$this->assertIsArray($paths);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame(TMP, $paths[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::addPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddPathAppendsDS(): void {
		$loader = new FileLoader(['.twig']);
		$loader->addPath(TMP . '///////\\\\////');

		$paths = $loader->getPaths();
		$this->assertSame(TMP, $paths[0], 'Path should be normalized to use DS');

		$loader = new FileLoader(['.twig']);
		$loader->addPath(rtrim(TMP, DS));

		$paths = $loader->getPaths();
		$this->assertSame(rtrim(TMP, DS) . DS, $paths[0], 'Path should be normalized to use DS');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::prependPath()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testPrependPath(): void {
		$loader = new FileLoader(['.twig']);
		$loader->prependPath(TMP);

		$paths = $loader->getPaths();

		$this->assertIsArray($paths);
		$this->assertCount(3, $paths);
		$this->assertArrayHasKey(0, $paths);

		$this->assertSame([0, 'customer', 'awyiss'], array_keys($paths), 'Prepended path should be first in the list');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::getNamespaces()
	 */
	public function testGetNamespaces(): void {
		$loader = new FileLoader(['.twig']);
		$namespaces = $loader->getNamespaces();

		$this->assertIsArray($namespaces);
		$this->assertCount(1, $namespaces);
		$this->assertArrayHasKey(0, $namespaces);

		$this->assertSame(FileLoader::MAIN_NAMESPACE, $namespaces[0], 'Main namespace should be defined');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplate(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		// Test finding a template in the main namespace
		$templateName = 'test_template.twig';
		file_put_contents(TMP . $templateName, 'Test content');

		$path = $loader->findTemplate($templateName);
		$this->assertSame(TMP . $templateName, $path, 'Template should be found in the main namespace');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithAbsolutePath(): void {
		$loader = new FileLoader(['.twig']);

		$templateName = TMP . 'test_template.twig';
		file_put_contents($templateName, 'Test content');

		// Test finding a template with an absolute path
		$path = $loader->findTemplate($templateName);
		$this->assertSame($templateName, $path, 'Template should be found without any additional checks when a absolute path was provided');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithRelativePath(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		$templateName = 'test_template.twig';
		if (!is_dir(TMP . 'subfolder')) {
			mkdir(TMP . 'subfolder', 0777, true);
		}
		file_put_contents(TMP . 'subfolder' . DS . $templateName, 'Test content');

		// Test finding a template with a relative path
		$path = $loader->findTemplate('subfolder' . DS . $templateName);
		$this->assertSame(TMP . 'subfolder' . DS . $templateName, $path, 'Template should be found in the main namespace with a relative path');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithNamespace(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP, 'customer');

		$templateName = 'customer_template.twig';
		file_put_contents(TMP . $templateName, 'Customer template content');

		// Test finding a template in a specific namespace
		$path = $loader->findTemplate('@customer/' . $templateName);
		$this->assertSame(TMP . $templateName, $path, 'Template should be found in the customer namespace');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithNamespacePrefersCustomerNamespace(): void {
		$loader = new FileLoader(['.twig']);

		$awyissTemplatesPath = Configure::read('App.paths.templates.awyiss');
		$loader->addPath($awyissTemplatesPath, Configure::read('App.namespace'));

		$frontendPaths = [$awyissTemplatesPath . 'Frontend' . DS];
		if (defined('CUSTOM_DIR')) {
			$customerTemplatesPath = Configure::read('App.paths.templates.customer');
			$loader->addPath($customerTemplatesPath, CUSTOM_NAMESPACE);

			array_unshift($frontendPaths, $customerTemplatesPath . 'Frontend' . DS);
		}

		$loader->setPaths($frontendPaths, 'Frontend');

		// `Frontend/content/section.twig` exists in the customer templates folder
		$filepath = $loader->findTemplate('@Frontend/content/section');
		$this->assertSame(
			ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS . 'content' . DS . 'section.twig',
			$filepath,
			'Template should be found in the Frontend namespace'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateChecksAllFolders(): void {
		$loader = new FileLoader(['.twig']);

		$awyissTemplatesPath = Configure::read('App.paths.templates.awyiss');
		$loader->addPath($awyissTemplatesPath, Configure::read('App.namespace'));

		$frontendPaths = [$awyissTemplatesPath . 'Frontend' . DS];
		if (defined('CUSTOM_DIR')) {
			$customerTemplatesPath = Configure::read('App.paths.templates.customer');
			$loader->addPath($customerTemplatesPath, CUSTOM_NAMESPACE);

			array_unshift($frontendPaths, $customerTemplatesPath . 'Frontend' . DS);
		}

		$loader->setPaths($frontendPaths, 'Frontend');

		// `Frontend/layout/default.twig` only exists in the awyiss templates folder
		$filepath = $loader->findTemplate('@Frontend/layout/default');
		$this->assertSame(
			ROOT . DS . 'awyiss' . DS . 'templates' . DS . 'Frontend' . DS . 'layout' . DS . 'default.twig',
			$filepath,
			'Template should be found in the Frontend namespace'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 */
	public function testFindTemplateWithUnknownNamespace(): void {
		$loader = new FileLoader(['.twig']);

		$this->expectException(LoaderError::class);
		$this->expectExceptionMessage('There are no registered paths for namespace "customer".');
		// Test finding a template in an unknown namespace
		$loader->findTemplate('@customer/customer_template.twig');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithInvalidNamespace(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP, 'customer');

		$this->expectException(LoaderError::class);
		$this->expectExceptionMessage('Malformed namespaced template name "@invalidcustomer_template" (expecting "@namespace/template_name").');
		// Test finding a template in an invalid namespace
		$loader->findTemplate('@invalidcustomer_template.twig');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWitInvalidTemplateName(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		$this->expectException(LoaderError::class);
		$this->expectExceptionMessage('A template name cannot contain NUL bytes.');
		// Attempt to find a template with an invalid name
		$loader->findTemplate("invalid\0name.twig");
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithNonExistentTemplate(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		$this->expectException(LoaderError::class);
		// Attempt to find a non-existent template
		$loader->findTemplate('non_existent_template.twig');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\FileLoader::findTemplate()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testFindTemplateWithPathBreakingOut(): void {
		$loader = new FileLoader(['.twig']);
		$loader->setPaths(TMP);

		$this->expectException(LoaderError::class);
		$this->expectExceptionMessage('Looks like you try to load a template outside configured directories (../subfolder/test_template).');
		$loader->findTemplate('..' . DS . 'subfolder' . DS . 'test_template.twig');
	}
}
