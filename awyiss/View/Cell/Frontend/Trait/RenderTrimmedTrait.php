<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


/**
 * Trait RenderTrimmedTrait
 *
 * This trait overrides the render method to trim whitespace from the output.
 */
trait RenderTrimmedTrait {
	/**
	 * Render the cell.
	 *
	 * @param string|null $template Custom template name to render. If not provided (null), the last
	 * value will be used. This value is automatically set by `CellTrait::cell()`.
	 * @return string The rendered cell.
	 * @throws \Cake\View\Exception\MissingCellTemplateException|\BadMethodCallException
	 * @see \Cake\View\Cell::render()
	 */
	public function render(?string $template = null): string {
		return trim(parent::render($template));
	}
}
