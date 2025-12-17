import Survey from 'Frontend/Survey'

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		window.survey = new Survey()
	});
}
else {
	window.survey = new Survey();
}