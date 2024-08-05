//noinspection JSUnusedGlobalSymbols

import History from 'Audit/History';
import Info from 'Audit/Info';

/**
 * Class to handle the loading of audit information for a given element.
 */
export default class Audit {
	/**
	 * The audit history
	 * @type {History}
	 */
	auditHistory;
	/**
	 * The audit information
	 * @type {Info}
	 */
	auditInfo;

	constructor() {
		this.auditHistory = new History();
		this.auditInfo = new Info();
	}
}
