this.Element && function(ElementPrototype) {
	ElementPrototype.closest = ElementPrototype.closest ||
		function(selector) {
			var el = this;
			while (el.matches && !el.matches(selector)) el = el.parentNode;
			return el.matches ? el : null;
		}
}(Element.prototype);

function editModuleOnClickHandler(event) {
	event.preventDefault();
	var element = event.target;

	if (element.tagName !== 'A') {
		element = element.closest('A.typo3-adminPanel-btn-openBackend');
	}

	var vHWin = window.open(element.getAttribute('data-backendScript'), element.getAttribute('data-t3BeSitenameMd5'));
	vHWin.focus();
	return false;
}

function initializeEditModule() {
	var editModuleBtnsOpenBackend = document.querySelectorAll('.typo3-adminPanel-btn-openBackend');
	for (var i = 0, len = editModuleBtnsOpenBackend.length; i < len; i++ ) {
		editModuleBtnsOpenBackend[i].addEventListener('click', editModuleOnClickHandler);
	}
}


window.addEventListener('load', initializeEditModule, false);
