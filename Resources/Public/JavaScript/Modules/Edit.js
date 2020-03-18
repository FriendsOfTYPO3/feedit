this.Element && function(ElementPrototype) {
	ElementPrototype.closest = ElementPrototype.closest ||
		function(selector) {
			var el = this;
			while (el.matches && !el.matches(selector)) el = el.parentNode;
			return el.matches ? el : null;
		}
}(Element.prototype);

function openBackendHandler(event) {
	event.preventDefault();
	var element = event.target;

	if (element.tagName !== 'A') {
		element = element.closest('a.typo3-feedit-btn-openBackend');
	}

	var vHWin = window.open(element.getAttribute('data-backendScript'), element.getAttribute('data-t3BeSitenameMd5'));
	vHWin.focus();
	return false;
}

function submitFormHandler(event) {
	event.preventDefault();
	var element = event.target;

	if (element.tagName !== 'A') {
		element = element.closest('a.typo3-feedit-btn-submitForm');
	}

	var execute = true;
	var form = document[element.getAttribute('data-feedit-formname')];
	var confirmText = element.getAttribute('data-feedit-confirm');

	if (confirmText) {
		execute = confirm(confirmText);
	}

	if (execute) {
		form.querySelector('.typo3-feedit-cmd').value = element.getAttribute('data-feedit-cmd');
		form.submit();
	}

	return false;
}

function initializeEditModule() {
	var editModuleBtnsOpenBackend = document.querySelectorAll('.typo3-feedit-btn-openBackend');
	for (var i = 0, len = editModuleBtnsOpenBackend.length; i < len; i++ ) {
		editModuleBtnsOpenBackend[i].addEventListener('click', openBackendHandler);
	}

	var editModuleBtnsSubmitForm = document.querySelectorAll('.typo3-feedit-btn-submitForm');
	for (var i = 0, len = editModuleBtnsSubmitForm.length; i < len; i++ ) {
		editModuleBtnsSubmitForm[i].addEventListener('click', submitFormHandler);
	}
}

window.addEventListener('load', initializeEditModule, false);
