/**
 * JS file for the Awyiss backend
 * If you want to overwrite the backend JS, just rename this file to main.js
 *
 * This will replace the default main.js.
 *
 * Modules can just be overwritten in the same way:
 * Put a file with the same name in the same directory, and it will be used instead of the default one.
 *
 * Adding custom script without overwriting the default one is possible by extending
 * the Backend/element/scripts.twig template, adding a statement like
 * 		{% do helper_Asset_add('custom.js', {type: 'module'}) %}
 *
 * If you want to use the default main.js and add some custom script, uncomment the following lines:
 *
 * window.mainJsIsImported = true;
 *
 * import { initMain } from '../../../awyiss/assets/js/main.js';
 *
 * console.log('Custom Main.js loaded');
 *
 * // If document is still loading, wait for it to complete
 * if (document.readyState === 'loading') {
 * 	document.addEventListener('DOMContentLoaded', initMainOnReady);
 * }
 * else {
 * 	// DOMContentLoaded has already fired
 * 	initMainOnReady();
 * }
 *
 * window.onload = function() {
 * 	initMainOnLoad();
 * }
 */