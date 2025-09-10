<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Twig\NodeVisitor;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor;
use Cake\Core\Configure;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Source;


/**
 * Test case for ExtendsNodeVisitor
 *
 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor
 */
class ExtendsNodeVisitorTest extends TestCase {
	/**
	 * @var \Twig\Environment|\PHPUnit\Framework\MockObject\MockObject
	 * @noinspection PhpDocFieldTypeMismatchInspection
	 */
	protected Environment $environment;
	/**
	 * @var \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor
	 */
	protected ExtendsNodeVisitor $visitor;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->visitor = new ExtendsNodeVisitor();
		$this->environment = $this->createMock(Environment::class);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeWithModuleNodeWithoutParentReturnsUnchanged(): void {
		$source = new Source('', '@Frontend/test.twig');
		$node = new ModuleNode(new Node(), null, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeWithNonConstantParentReturnsUnchanged(): void {
		$source = new Source('', '@Frontend/test.twig');
		$parentNode = $this->createMock(AbstractExpression::class);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);
		$this->assertSame($parentNode, $node->getNode('parent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeWithCustomerTemplatePathReturnsUnchanged(): void {
		$customerTemplatePath = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'test.twig';
		$source = new Source('', $customerTemplatePath);
		$parentNode = new ConstantExpression('@Frontend/base.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);
		$this->assertSame($parentNode, $node->getNode('parent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeReplacesParentWhenFrontendExtendsItself(): void {
		$source = new Source('', '@Frontend/layout/base.twig');
		$parentNode = new ConstantExpression('@Frontend/layout/base.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertInstanceOf(ModuleNode::class, $result);

		$newParentNode = $result->getNode('parent');
		$this->assertInstanceOf(ConstantExpression::class, $newParentNode);
		$this->assertNotSame($parentNode, $newParentNode);
		$this->assertEquals('@Awyiss/Frontend/layout/base.twig', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeReplacesParentWhenBackendExtendsItself(): void {
		$source = new Source('', '@Backend/admin/base.twig');
		$parentNode = new ConstantExpression('@Backend/admin/base.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertInstanceOf(ModuleNode::class, $result);

		$newParentNode = $result->getNode('parent');
		$this->assertInstanceOf(ConstantExpression::class, $newParentNode);
		$this->assertNotSame($parentNode, $newParentNode);
		$this->assertEquals('@Awyiss/Backend/admin/base.twig', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeDoesNotReplaceWhenExtendingDifferentTemplate(): void {
		$source = new Source('', '@Frontend/page/detail.twig');
		$parentNode = new ConstantExpression('@Frontend/layout/base.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);

		$newParentNode = $result->getNode('parent');
		$this->assertSame($parentNode, $newParentNode);
		$this->assertEquals('@Frontend/layout/base.twig', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeDoesNotReplaceWhenExtendingAwyissTemplate(): void {
		$source = new Source('', '@Frontend/layout/base.twig');
		$parentNode = new ConstantExpression('@Awyiss/layout/master.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);

		$newParentNode = $result->getNode('parent');
		$this->assertSame($parentNode, $newParentNode);
		$this->assertEquals('@Awyiss/layout/master.twig', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodePreservesTemplateLineNumber(): void {
		$lineNumber = 5;
		$source = new Source('', '@Frontend/test.twig');
		$parentNode = new ConstantExpression('@Frontend/test.twig', $lineNumber);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$newParentNode = $result->getNode('parent');
		$this->assertEquals($lineNumber, $newParentNode->getTemplateLine());
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeHandlesNestedDirectories(): void {
		$source = new Source('', '@Frontend/admin/user/edit.twig');
		$parentNode = new ConstantExpression('@Frontend/admin/user/edit.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$newParentNode = $result->getNode('parent');
		$this->assertNotSame($parentNode, $newParentNode);
		$this->assertEquals('@Awyiss/Frontend/admin/user/edit.twig', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeHandlesTemplateWithoutExtension(): void {
		$source = new Source('', '@Backend/base');
		$parentNode = new ConstantExpression('@Backend/base', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$newParentNode = $result->getNode('parent');
		$this->assertNotSame($parentNode, $newParentNode);
		$this->assertEquals('@Awyiss/Backend/base', $newParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function _testEnterNodeWithDifferentNamespaces(): void {
		$source = new Source('', '@CustomNamespace/test.twig');
		$parentNode = new ConstantExpression('@CustomNamespace/test.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);

		$originalParentNode = $result->getNode('parent');
		$this->assertSame($parentNode, $originalParentNode);
		$this->assertEquals('@CustomNamespace/test.twig', $originalParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor::enterNode()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnterNodeCaseInsensitiveNamespaceCheck(): void {
		// Test that namespace comparison is case-sensitive (should not match)
		$source = new Source('', '@frontend/test.twig');
		$parentNode = new ConstantExpression('@frontend/test.twig', 1);
		$node = new ModuleNode(new Node(), $parentNode, new Node(), new Node(), new Node(), [], $source);

		$result = $this->visitor->enterNode($node, $this->environment);

		$this->assertSame($node, $result);

		$originalParentNode = $result->getNode('parent');
		$this->assertSame($parentNode, $originalParentNode);
		$this->assertEquals('@frontend/test.twig', $originalParentNode->getAttribute('value'));
	}


	/**
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExistingTemplate(): void {
		// Create a filesystem loader pointing to your actual template directories
		$loader = new FilesystemLoader();

		$awyissTemplatesPath = Configure::read('App.paths.templates.awyiss');
		$loader->addPath($awyissTemplatesPath, Configure::read('App.namespace'));

		$frontendPaths = [$awyissTemplatesPath . 'Frontend' . DS];
		if (defined('CUSTOM_DIR')) {
			$customerTemplatesPath = Configure::read('App.paths.templates.customer');
			$loader->addPath($customerTemplatesPath, CUSTOM_NAMESPACE);

			array_unshift($frontendPaths, $customerTemplatesPath . 'Frontend' . DS);
		}

		$loader->setPaths($frontendPaths, 'Frontend');

		// Add the node visitor to test
		$env = new Environment($loader, ['cache' => false, 'autoescape' => false]);
		$env->addNodeVisitor($this->visitor);

		// Parse an actual template file that exists
		$templateName = '@Frontend/element/content_row.twig';

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$output = $env->render($templateName, []);

		$this->assertStringContainsString('<div class="ContentRow">', $output);
	}
}
