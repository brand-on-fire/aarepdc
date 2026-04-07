/**
 * Photonic Masonry Layout
 * The Masonry layout is primarily controlled using CSS columns. The JS component is to facilitate responsive behaviour and breakpoints.
 *
 * License: GPL v3.0
 */
import {Core} from "../Core";
import * as Util from "../Util";

export const MasonryHorizontal = (resized, jsLoaded, selector) => {
	if (console !== undefined && Photonic_JS.debug_on !== '0' && Photonic_JS.debug_on !== '') console.time('Horizontal Masonry');

	let selection = document.querySelectorAll(selector);
	if (selector == null || selection.length === 0) {
		selection = document.querySelectorAll('.photonic-masonry-horizontal-layout');
	}

	if (!resized && selection.length > 0) {
		Core.showSpinner();
	}

	let minWidth = (isNaN(Photonic_JS.masonry_min_width) || parseInt(Photonic_JS.masonry_min_width) <= 0) ? 200 : Photonic_JS.masonry_min_width;
	minWidth = parseInt(minWidth);

	selection.forEach((grid) => {
		let columns = grid.getAttribute('data-photonic-gallery-columns');
		columns = (isNaN(parseInt(columns)) || parseInt(columns) <= 0) ? 3 : parseInt(columns);

		const buildLayout = (grid, fadeIn) => {
			const figures = grid.querySelectorAll('figure');
			const inputFigures = [], outputFigures = [];
			figures.forEach(figure => {
				inputFigures[figure.getAttribute('data-photonic-idx')] = figure;
			});

			const viewportWidth = Math.floor(grid.getBoundingClientRect().width),
				idealColumns = (viewportWidth / columns) > minWidth ? columns : Math.floor(viewportWidth / minWidth);

			if (idealColumns !== undefined && idealColumns !== null) {
				grid.style.columnCount = idealColumns.toString();

				let col = 0;
				while (col < idealColumns) {
					for (let idx = 0; idx < inputFigures.length; idx += idealColumns) {
						let _val = inputFigures[idx + col];
						if (_val !== undefined) {
							outputFigures.push(_val);
						}
					}
					col++;
				}

				if (outputFigures.length === inputFigures.length) {
					const fragment = document.createDocumentFragment();
					outputFigures.forEach(figure => {
						grid.appendChild(figure);
					});
				}
			}

			if (fadeIn) {
				Array.from(grid.getElementsByTagName('img')).forEach(img => {
					Util.fadeIn(img);
				});
			}

			Core.showSlideupTitle();
			if (!resized && !jsLoaded) {
				Core.hideLoading();
			}
		};

		if (grid.classList.contains('sizes-present')) {
			Core.watchForImages(grid);
			buildLayout(grid, !resized);
		}
		else {
			Core.waitForImages(grid).then(() => {
				buildLayout(grid, true);
			});
		}
	});
	if (console !== undefined && Photonic_JS.debug_on !== '0' && Photonic_JS.debug_on !== '') console.timeEnd('Horizontal Masonry');
};
