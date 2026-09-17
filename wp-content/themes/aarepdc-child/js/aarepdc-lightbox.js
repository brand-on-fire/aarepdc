/**
 * AAREP DC: standard lightbox for WordPress core galleries.
 *
 * Opens gallery links (which point at image files, see inc/gallery_lightbox.php) in an
 * accessible overlay: previous/next, keyboard (Esc, arrows, Tab trapped), swipe, counter,
 * focus returned to the thumbnail on close. No dependencies.
 */
(function () {
	'use strict';

	var IMAGE_RE = /\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i;
	var dialog, imgEl, captionEl, counterEl, prevBtn, nextBtn, closeBtn;
	var items = [];
	var index = 0;
	var opener = null;
	var touchX = null;

	function el(tag, cls, attrs) {
		var node = document.createElement(tag);
		if (cls) { node.className = cls; }
		if (attrs) {
			Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
		}
		return node;
	}

	function build() {
		dialog = el('div', 'aarepdc-lb', { role: 'dialog', 'aria-modal': 'true', 'aria-label': 'Photo viewer', hidden: '' });

		var backdrop = el('div', 'aarepdc-lb__backdrop');
		var stage = el('figure', 'aarepdc-lb__stage');
		imgEl = el('img', 'aarepdc-lb__img', { alt: '' });
		captionEl = el('figcaption', 'aarepdc-lb__caption');
		counterEl = el('p', 'aarepdc-lb__counter', { 'aria-live': 'polite' });

		closeBtn = el('button', 'aarepdc-lb__btn aarepdc-lb__close', { type: 'button', 'aria-label': 'Close photo viewer' });
		closeBtn.innerHTML = '<span aria-hidden="true">&times;</span>';
		prevBtn = el('button', 'aarepdc-lb__btn aarepdc-lb__prev', { type: 'button', 'aria-label': 'Previous photo' });
		prevBtn.innerHTML = '<span aria-hidden="true">&#8249;</span>';
		nextBtn = el('button', 'aarepdc-lb__btn aarepdc-lb__next', { type: 'button', 'aria-label': 'Next photo' });
		nextBtn.innerHTML = '<span aria-hidden="true">&#8250;</span>';

		stage.appendChild(imgEl);
		stage.appendChild(captionEl);
		dialog.appendChild(backdrop);
		dialog.appendChild(stage);
		dialog.appendChild(counterEl);
		dialog.appendChild(prevBtn);
		dialog.appendChild(nextBtn);
		dialog.appendChild(closeBtn);
		document.body.appendChild(dialog);

		backdrop.addEventListener('click', close);
		stage.addEventListener('click', function (e) { if (e.target === stage) { close(); } });
		closeBtn.addEventListener('click', close);
		prevBtn.addEventListener('click', function () { show(index - 1); });
		nextBtn.addEventListener('click', function () { show(index + 1); });

		dialog.addEventListener('touchstart', function (e) {
			touchX = e.touches.length === 1 ? e.touches[0].clientX : null;
		}, { passive: true });
		dialog.addEventListener('touchend', function (e) {
			if (touchX === null || !e.changedTouches.length) { return; }
			var dx = e.changedTouches[0].clientX - touchX;
			touchX = null;
			if (Math.abs(dx) > 50) { show(index + (dx < 0 ? 1 : -1)); }
		});
	}

	function captionFor(link) {
		var fig = link.closest('.gallery-item');
		var cap = fig ? fig.querySelector('.gallery-caption, figcaption') : null;
		if (cap && cap.textContent.trim()) { return cap.textContent.trim(); }
		var img = link.querySelector('img');
		return img && img.getAttribute('alt') ? img.getAttribute('alt').trim() : '';
	}

	function preload(i) {
		if (items.length < 2) { return; }
		var n = (i + items.length) % items.length;
		var p = new Image();
		p.src = items[n].href;
	}

	function show(i) {
		if (!items.length) { return; }
		index = (i + items.length) % items.length;
		var link = items[index];
		var caption = captionFor(link);

		imgEl.classList.remove('is-loaded');
		imgEl.onload = function () { imgEl.classList.add('is-loaded'); };
		imgEl.src = link.href;
		imgEl.alt = caption || ('Event photo ' + (index + 1) + ' of ' + items.length);
		captionEl.textContent = caption;
		captionEl.hidden = !caption;
		counterEl.textContent = (index + 1) + ' / ' + items.length;

		var single = items.length < 2;
		prevBtn.hidden = single;
		nextBtn.hidden = single;

		preload(index + 1);
		preload(index - 1);
	}

	function focusables() {
		return [closeBtn, prevBtn, nextBtn].filter(function (b) { return !b.hidden; });
	}

	function onKey(e) {
		if (dialog.hidden) { return; }
		if (e.key === 'Escape') { e.preventDefault(); close(); }
		else if (e.key === 'ArrowRight') { e.preventDefault(); show(index + 1); }
		else if (e.key === 'ArrowLeft') { e.preventDefault(); show(index - 1); }
		else if (e.key === 'Tab') {
			var f = focusables();
			var at = f.indexOf(document.activeElement);
			e.preventDefault();
			var next = e.shiftKey ? (at <= 0 ? f.length - 1 : at - 1) : (at === f.length - 1 ? 0 : at + 1);
			f[next].focus();
		}
	}

	function open(group, i, trigger) {
		if (!dialog) { build(); }
		items = group;
		opener = trigger;
		show(i);
		dialog.hidden = false;
		document.documentElement.classList.add('aarepdc-lb-open');
		document.addEventListener('keydown', onKey);
		closeBtn.focus();
	}

	function close() {
		if (!dialog || dialog.hidden) { return; }
		dialog.hidden = true;
		imgEl.removeAttribute('src');
		document.documentElement.classList.remove('aarepdc-lb-open');
		document.removeEventListener('keydown', onKey);
		if (opener && typeof opener.focus === 'function') { opener.focus(); }
		opener = null;
	}

	function init() {
		var galleries = document.querySelectorAll('.gallery');
		Array.prototype.forEach.call(galleries, function (gallery) {
			var links = Array.prototype.filter.call(
				gallery.querySelectorAll('.gallery-item a[href]'),
				function (a) { return IMAGE_RE.test(a.getAttribute('href')); }
			);
			links.forEach(function (link, i) {
				if (!link.getAttribute('aria-label')) {
					var cap = captionFor(link);
					link.setAttribute('aria-label', (cap ? cap + ', ' : '') + 'view photo ' + (i + 1) + ' of ' + links.length);
				}
				link.setAttribute('aria-haspopup', 'dialog');
				link.addEventListener('click', function (e) {
					if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
					e.preventDefault();
					e.stopPropagation();
					open(links, i, link);
				});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
