// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import HistoryTables from 'Audit/HistoryTables';

export default class AuditController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (document.body.classList.contains('HistoryAction')) {
			const historyTables = new HistoryTables();
			// Enable the url-change feature
			historyTables.enableUrlChange();
		}
	}
}

/**
 * Expose the class globally
 * @global
 * @type {AuditController}
 */
window.AuditController = AuditController;
