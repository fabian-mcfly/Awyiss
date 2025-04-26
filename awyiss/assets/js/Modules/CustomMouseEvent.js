// noinspection JSUnusedGlobalSymbols

/**
 * CustomMouseEvent class that extends the MouseEvent class and
 * adds a customData property to store additional data.
 */
export default class CustomMouseEvent extends MouseEvent {
	constructor(typeArg, customData, mouseEventInit) {
		super(typeArg, mouseEventInit);

		Object.keys(customData).forEach(key => {
			if (!this.hasOwnProperty(key)) {
				this[key] = customData[key];
			}
		});
	}
}