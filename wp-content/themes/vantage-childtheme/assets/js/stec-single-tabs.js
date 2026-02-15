(function () {
	"use strict";

	var ROOT_SELECTOR = ".single-stec_event .stec-single-page";
	var TABS_SELECTOR = ".stec-content-tabs";
	var TAB_SELECTOR = ".stec-content-tab";
	var PRODUCTS_HIDDEN_CLASS = "awz-stec-hidden-products-tab";
	var TARGET_LABEL = "Termine";

	function normalizeLabel(value) {
		return String(value || "")
			.toLowerCase()
			.replace(/\s+/g, " ")
			.trim();
	}

	function getLabelElement(tab) {
		return tab.querySelector("span, stec-span");
	}

	function getCurrentLabel(tab) {
		var labelElement = getLabelElement(tab);
		if (labelElement) {
			return normalizeLabel(labelElement.textContent);
		}

		return normalizeLabel(tab.textContent);
	}

	function setLabel(tab, label) {
		var labelElement = getLabelElement(tab);
		if (labelElement && normalizeLabel(labelElement.textContent) !== normalizeLabel(label)) {
			labelElement.textContent = label;
		}
	}

	function getTabKind(tab) {
		if (tab.dataset.awzTabKind) {
			return tab.dataset.awzTabKind;
		}

		var originalLabel = tab.dataset.awzOriginalLabel;
		if (!originalLabel) {
			originalLabel = getCurrentLabel(tab);
			tab.dataset.awzOriginalLabel = originalLabel;
		}

		if (originalLabel === "tickets") {
			tab.dataset.awzTabKind = "tickets";
			return "tickets";
		}

		if (originalLabel === "products" || originalLabel === "produkte") {
			tab.dataset.awzTabKind = "products";
			return "products";
		}

		return "";
	}

	function updateTabs(tabsContainer) {
		var tabs = Array.prototype.slice
			.call(tabsContainer.children || [])
			.filter(function (child) {
				return child.classList && child.classList.contains(TAB_SELECTOR.replace(".", ""));
			});

		if (!tabs.length) {
			return;
		}

		var ticketsTab = null;
		var productsTab = null;

		tabs.forEach(function (tab) {
			var kind = getTabKind(tab);

			if (!ticketsTab && kind === "tickets") {
				ticketsTab = tab;
			}

			if (!productsTab && kind === "products") {
				productsTab = tab;
			}
		});

		if (ticketsTab) {
			setLabel(ticketsTab, TARGET_LABEL);
		}

		if (!productsTab) {
			return;
		}

		if (!ticketsTab) {
			setLabel(productsTab, TARGET_LABEL);
			if (productsTab.classList.contains(PRODUCTS_HIDDEN_CLASS)) {
				productsTab.classList.remove(PRODUCTS_HIDDEN_CLASS);
			}
			return;
		}

		if (!productsTab.classList.contains(PRODUCTS_HIDDEN_CLASS)) {
			productsTab.classList.add(PRODUCTS_HIDDEN_CLASS);
		}

		if (productsTab.classList.contains("active")) {
			productsTab.classList.remove("active");

			if (!ticketsTab.classList.contains("active")) {
				ticketsTab.dispatchEvent(new MouseEvent("click", { bubbles: true }));
			}
		}
	}

	function updateAllTabs() {
		var containers = document.querySelectorAll(ROOT_SELECTOR + " " + TABS_SELECTOR);
		containers.forEach(updateTabs);
	}

	function bindEventTabCallback() {
		var previousHook = window.stecOnEventTabContentRender;

		window.stecOnEventTabContentRender = function (payload) {
			if (typeof previousHook === "function") {
				previousHook(payload);
			}

			window.requestAnimationFrame(updateAllTabs);
		};
	}

	function observeSinglePageRoots() {
		var roots = document.querySelectorAll(ROOT_SELECTOR);
		roots.forEach(function (root) {
			var observer = new MutationObserver(function () {
				updateAllTabs();
			});

			observer.observe(root, {
				subtree: true,
				childList: true,
			});
		});
	}

	function bootstrap() {
		bindEventTabCallback();
		updateAllTabs();
		observeSinglePageRoots();
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", bootstrap);
	} else {
		bootstrap();
	}
})();
