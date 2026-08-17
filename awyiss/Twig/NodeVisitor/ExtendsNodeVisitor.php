<?php declare(strict_types=1);


namespace Awyiss\Twig\NodeVisitor;


use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;


/**
 * A NodeVisitor that rewrites the parent template name in an extends tag
 * to use the Awyiss namespace in case a template tries to extend itself
 * in the @Frontend or @Backend namespace.
 */
class ExtendsNodeVisitor implements NodeVisitorInterface {
	/**
	 * @inheritDoc
	 */
	public function enterNode(Node $node, Environment $env): Node {
		// Handle ModuleNode (the root node of a compiled template)
		if ($node instanceof ModuleNode && $node->hasNode('parent')) {
			return $this->processExtendsNode($node);
		}

		return $node;
	}


	/**
	 * @inheritDoc
	 */
	public function leaveNode(Node $node, Environment $env): ?Node {
		return $node;
	}


	/**
	 * @inheritDoc
	 */
	public function getPriority(): int {
		return 0; // Process early
	}


	/**
	 * @param \Twig\Node\ModuleNode $node
	 * @return \Twig\Node\Node
	 */
	protected function processExtendsNode(ModuleNode $node): Node {
		// Get the current template name and path
		$sourceContext = $node->getSourceContext();
		$currentTemplateName = $sourceContext->getName(); // e.g., "@Frontend/some/template.twig"

		// Get the name of the parent template being extended
		$parentTemplateNode = $node->getNode('parent');

		if (!$parentTemplateNode instanceof ConstantExpression) {
			return $node;
		}

		$targetTemplateName = $parentTemplateNode->getAttribute('value');

		$customerTemplatePath = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS;
		if (str_starts_with($currentTemplateName, $customerTemplatePath)) {
			$currentTemplateName = substr_replace($currentTemplateName, '@', 0, strlen($customerTemplatePath));
		}

		if (
			$currentTemplateName !== $targetTemplateName
			|| (
				!str_starts_with($targetTemplateName, '@Frontend/')
				&& !str_starts_with($targetTemplateName, '@Backend/')
			)
		) {
			return $node;
		}

		$newTemplateName = '@Awyiss/' . substr($targetTemplateName, 1);

		// Replace the parent template node with the new template name
		$newParentTemplateNode = new ConstantExpression($newTemplateName, $parentTemplateNode->getTemplateLine());
		$node->setNode('parent', $newParentTemplateNode);

		return $node;
	}
}
