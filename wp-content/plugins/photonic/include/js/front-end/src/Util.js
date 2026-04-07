// Utilities for Photonic
export const hasClass = (element, className) => {
	if (element.classList) {
		return element.classList.contains(className);
	}
	else {
		return new RegExp('(^| )' + className + '( |$)', 'gi').test(element.className);
	}
};

function ajax(method, url, args, callback) {
	const xhr = new XMLHttpRequest();
	xhr.open(method, url);
	xhr.onreadystatechange = function() {
		if (xhr.readyState === 4) {
			if (xhr.status === 200) {
				const data = xhr.responseText;
				callback(data);
			}
		}
	};
	let form = new FormData();
	for (const [key, value] of Object.entries(args)) {
		form.append(key, value);
	}
	xhr.send(form);
}

export const post = (url, args, callback) => {
	ajax('POST', url, args, callback);
};

export const get = (url, args, callback) => {
	ajax('GET', url, args, callback);
};

export const next = (elem, selector) => {
	let sibling = elem.nextElementSibling;

	if (!selector) return sibling;
	while (sibling) {
		if (sibling.matches(selector)) return sibling;
		sibling = sibling.nextElementSibling;
	}
};

export const getElement = value => {
	const parser = new DOMParser();
	const doc = parser.parseFromString(value, 'text/html');
	return doc.body;
};

export const getText = value => {
	// Not using innerHTML because of vulnerability to XSS
	/*
        const txt = document.createElement("div");
        txt.innerHTML = value;
        return txt.innerText;
    */

	if (value == null) {
		return '';
	}
	return value.replace(/<[^>]+>/g, '');
};

export const slideUpDown = (element, state) => {
	if (element != null && element.classList) {
		if (!element.classList.contains('photonic-can-slide')) {
			element.classList.add('photonic-can-slide');
		}
		if ('show' === state) {
			element.classList.remove('photonic-can-slide-hide');
			element.style.height = `${element.scrollHeight}px`;
		}
		else {
			element.classList.add('photonic-can-slide-hide');
			element.style.height = 0
		}
	}
};

export const slideUpTitle = (element, state) => {
	if (element && element.classList) {
		if ('show' === state) {
			let currentPadding = 0;
			if (element.offsetHeight) {
				currentPadding = parseInt(getComputedStyle(element).paddingTop.slice(0, -2)) * 2;
			}
			element.style.height = (element.scrollHeight + 6 - currentPadding) + 'px';
			element.classList.add('slideup-show');
		}
		else {
			element.style.height = '';
			element.classList.remove('slideup-show');
		}
	}
}

export const fadeIn = (el) => {
	if (!hasClass(el, 'fade-in')) {
		el.style.display = 'block';
		el.style.visibility = 'visible';
		el.classList.add('fade-in');
	}
}

export const fadeOut = (el, duration) => {
	let s = el.style,
		step = 25/(duration || 500);
	s.opacity = s.opacity || 1;
	(function fade() {
		s.opacity -= step;
		if (s.opacity < 0) {
			s.display = "none";
			el.classList.remove('fade-in');
		}
		else {
			setTimeout(fade, 25);
		}
	})();
}

// get the default display style of an element
const defaultDisplay = tag => {
	const iframe = document.createElement('iframe');
	iframe.setAttribute('frameborder', 0);
	iframe.setAttribute('width', 0);
	iframe.setAttribute('height', 0);
	document.documentElement.appendChild(iframe);

	const doc = (iframe.contentWindow || iframe.contentDocument).document;

	// IE support
	doc.write();
	doc.close();

	const testEl = doc.createElement(tag);
	doc.documentElement.appendChild(testEl);
	const display = (window.getComputedStyle ? getComputedStyle(testEl, null) : testEl.currentStyle).display;
	iframe.parentNode.removeChild(iframe);
	return display;
};

// actual show/hide function used by show() and hide() below
const showHide = (el, show) => {
	let value = el.getAttribute('data-olddisplay'),
		display = el.style.display,
		computedDisplay = (window.getComputedStyle ? getComputedStyle(el, null) : el.currentStyle).display;

	if (show) {
		if (!value && display === 'none') el.style.display = '';
		if (el.style.display === '' && (computedDisplay === 'none')) value = value || defaultDisplay(el.nodeName);
	}
	else {
		if (display && display !== 'none' || !(computedDisplay === 'none'))
			el.setAttribute('data-olddisplay', (computedDisplay === 'none') ? display : computedDisplay);
	}
	if (!show || el.style.display === 'none' || el.style.display === '')
		el.style.display = show ? value || '' : 'none';
};

// helper functions
export const show = (el) => showHide(el, true);
export const hide = (el) => showHide(el);

//JavaScript HTML Sanitizer v2.0.3, (c) Alexander Yumashev, Jitbit Software.
//homepage https://github.com/jitbit/HtmlSanitizer
//License: MIT https://github.com/jitbit/HtmlSanitizer/blob/master/LICENSE
export const HTMLSanitizer = new (function () {
	const _tagWhitelist = {
		'A': true, 'ABBR': true, 'B': true, 'BLOCKQUOTE': true, 'BODY': true, 'BR': true, 'CENTER': true, 'CODE': true, 'DD': true, 'DIV': true, 'DL': true, 'DT': true, 'EM': true, 'FONT': true,
		'H1': true, 'H2': true, 'H3': true, 'H4': true, 'H5': true, 'H6': true, 'HR': true, 'I': true, 'IMG': true, 'LABEL': true, 'LI': true, 'OL': true, 'P': true, 'PRE': true,
		'SMALL': true, 'SOURCE': true, 'SPAN': true, 'STRONG': true, 'SUB': true, 'SUP': true, 'TABLE': true, 'TBODY': true, 'TR': true, 'TD': true, 'TH': true, 'THEAD': true, 'UL': true, 'U': true, 'VIDEO': true
	};

	const _contentTagWhiteList = { 'FORM': true, 'GOOGLE-SHEETS-HTML-ORIGIN': true }; //tags that will be converted to DIVs
	const _attributeWhitelist = { 'align': true, 'color': true, 'controls': true, 'height': true, 'href': true, 'id': true, 'src': true, 'style': true, 'target': true, 'title': true, 'type': true, 'width': true };
	const _cssWhitelist = { 'background-color': true, 'color': true, 'font-size': true, 'font-weight': true, 'text-align': true, 'text-decoration': true, 'width': true };
	const _schemaWhiteList = [ 'http:', 'https:', 'data:', 'm-files:', 'file:', 'ftp:', 'mailto:', 'pw:' ]; //which "protocols" are allowed in "href", "src" etc
	const _uriAttributes = { 'href': true, 'action': true };
	const _parser = new DOMParser();

	this.SanitizeHTML = (input, extraSelector) => {
		if (input == null) return null;

		input = input.trim();
		if (input === "") return ""; //to save performance

		//firefox "bogus node" workaround for wysiwyg's
		if (input === "<br>") return "";

		if (input.indexOf("<body") === -1) input = "<body>" + input + "</body>"; //add "body" otherwise some tags are skipped, like <style>

		let doc = _parser.parseFromString(input, "text/html");

		//DOM clobbering check (damn you firefox)
		if (doc.body.tagName !== 'BODY')
			doc.body.remove();
		if (typeof doc.createElement !== 'function')
			doc.createElement.remove();

		function makeSanitizedCopy(node) {
			let newNode;
			if (node.nodeType === Node.TEXT_NODE) {
				newNode = node.cloneNode(true);
			} else if (node.nodeType === Node.ELEMENT_NODE && (_tagWhitelist[node.tagName] || _contentTagWhiteList[node.tagName] || (extraSelector && node.matches(extraSelector)))) { //is tag allowed?

				if (_contentTagWhiteList[node.tagName])
					newNode = doc.createElement('DIV'); //convert to DIV
				else
					newNode = doc.createElement(node.tagName);

				for (let i = 0; i < node.attributes.length; i++) {
					let attr = node.attributes[i];
					if (_attributeWhitelist[attr.name]) {
						if (attr.name === "style") {
							for (let s = 0; s < node.style.length; s++) {
								let styleName = node.style[s];
								if (_cssWhitelist[styleName])
									newNode.style.setProperty(styleName, node.style.getPropertyValue(styleName));
							}
						}
						else {
							if (_uriAttributes[attr.name]) { //if this is a "uri" attribute, that can have "javascript:" or something
								if (attr.value.indexOf(":") > -1 && !startsWithAny(attr.value, _schemaWhiteList))
									continue;
							}
							newNode.setAttribute(attr.name, attr.value);
						}
					}
				}
				for (let i = 0; i < node.childNodes.length; i++) {
					let subCopy = makeSanitizedCopy(node.childNodes[i]);
					newNode.appendChild(subCopy, false);
				}

				//remove useless empty spans (lots of those when pasting from MS Outlook)
				if ((newNode.tagName === "SPAN" || newNode.tagName === "B" || newNode.tagName === "I" || newNode.tagName === "U")
					&& newNode.innerHTML.trim() === "") {
					return doc.createDocumentFragment();
				}

			} else {
				newNode = doc.createDocumentFragment();
			}
			return newNode;
		}

		let resultElement = makeSanitizedCopy(doc.body);

		return resultElement.innerHTML
			.replace(/div><div/g, "div>\n<div"); //replace is just for cleaner code
	}

	function startsWithAny(str, substrings) {
		for (let i = 0; i < substrings.length; i++) {
			if (str.indexOf(substrings[i]) === 0) {
				return true;
			}
		}
		return false;
	}

	this.AllowedTags = _tagWhitelist;
	this.AllowedAttributes = _attributeWhitelist;
	this.AllowedCssStyles = _cssWhitelist;
	this.AllowedSchemas = _schemaWhiteList;
});
