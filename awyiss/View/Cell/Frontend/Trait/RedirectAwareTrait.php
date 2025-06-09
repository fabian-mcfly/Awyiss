<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Cake\Http\Exception\RedirectException;
use Error;
use Exception;


/**
 * Trait RedirectAwareTrait
 *
 * This trait is used to handle redirects in cells that may throw a RedirectException.
 * It catches the exception and performs the redirect, allowing the cell to be rendered
 * without needing to handle the redirect logic in each individual cell.
 */
trait RedirectAwareTrait {
	/**
	 * Catch the redirect exception and redirect the user
	 *
	 * @inheritDoc
	 */
	public function __toString(): string {
		try {
			return $this->render();
		}
		catch (RedirectException $ex) {
			// Redirects are handled by the middleware
			header('Location: ' . $ex->getMessage(), true, $ex->getCode());
			exit;
		}
		catch (Exception $ex) {
			trigger_error(
				sprintf('Could not render cell - %s [%s, line %d]', $ex->getMessage(), $ex->getFile(), $ex->getLine()),
				E_USER_WARNING
			);

			return '';
			/** @phpstan-ignore-next-line */
		}
		catch (Error $ex) {
			throw new Error(
				sprintf('Could not render cell - %s [%s, line %d]', $ex->getMessage(), $ex->getFile(), $ex->getLine()),
				0,
				$ex
			);
		}
	}
}
