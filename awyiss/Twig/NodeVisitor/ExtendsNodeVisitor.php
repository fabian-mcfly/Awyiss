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
		$lo_sourceContext = $node->getSourceContext();
		$ls_currentTemplateName = $lo_sourceContext->getName(); // e.g., "@Frontend/some/template.twig"

		// Get the name of the parent template being extended
		$lo_parentTemplateNode = $node->getNode('parent');

		if (!$lo_parentTemplateNode instanceof ConstantExpression) {
			return $node;
		}

		$ls_targetTemplateName = $lo_parentTemplateNode->getAttribute('value');

		$ls_customerTemplatePath = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS;
		if (str_starts_with($ls_currentTemplateName, $ls_customerTemplatePath)) {
			$ls_currentTemplateName = substr_replace($ls_currentTemplateName, '@', 0, strlen($ls_customerTemplatePath));
		}

		if (
			$ls_currentTemplateName !== $ls_targetTemplateName ||
			(
				!str_starts_with($ls_targetTemplateName, '@Frontend/') &&
				!str_starts_with($ls_targetTemplateName, '@Backend/')
			)
		) {
			return $node;
		}

		$ls_newTemplateName = '@Awyiss/' . substr($ls_targetTemplateName, 1);

		// Replace the parent template node with the new template name
		$lo_newParentTemplateNode = new ConstantExpression($ls_newTemplateName, $lo_parentTemplateNode->getTemplateLine());
		$node->setNode('parent', $lo_newParentTemplateNode);

		return $node;
	}
}
