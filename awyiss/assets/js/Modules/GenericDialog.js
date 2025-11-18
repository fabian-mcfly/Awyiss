// noinspection JSUnusedGlobalSymbols

export default class GenericDialog {
	/**
	 * @type {HTMLDialogElement|null}
	 */
	lastDialog = null;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * Initialize the Generic dialog.
	 */
	constructor() {
		this.eventHandler.add('click', this.handleButtonClick.bind(this));
		this.eventHandler.add('close', this.handleClose.bind(this), window, true);
	}

	/**
	 * Handle the click event.
	 *
	 * @param {MouseEvent} event
	 */
	handleButtonClick(event) {
		const showDialogButton = event.target.closest('.ShowDialog');
		if (showDialogButton) {
			const dialogId = showDialogButton.dataset.dialog;
			if (dialogId) {
				this.lastDialog = document.getElementById(dialogId);
				this.lastDialog.showModal();
			}

			return;
		}

		if (event.target.closest('.Button-Close') && this.lastDialog && event.target.closest('dialog') === this.lastDialog) {
			this.lastDialog.close();
			this.lastDialog = null;
		}
	}

	/**
	 * Handle the close event on the dialog.
	 *
	 * @param {Event} event
	 */
	handleClose(event) {
		const dialog = event.target;
		if (dialog === this.lastDialog) {
			this.lastDialog = null;
		}
	}
}