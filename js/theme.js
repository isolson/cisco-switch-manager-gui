/**
 * Theme switching functionality
 * Supports: system (auto), light, dark
 */
(function() {
	'use strict';

	// Apply theme immediately to prevent flash
	var savedTheme = localStorage.getItem('theme') || 'system';
	applyTheme(savedTheme);

	function applyTheme(theme) {
		var html = document.documentElement;

		if (theme === 'dark') {
			html.setAttribute('data-theme', 'dark');
		} else if (theme === 'light') {
			html.setAttribute('data-theme', 'light');
		} else {
			// System - remove attribute to let media query take over
			html.removeAttribute('data-theme');
		}
	}

	// Make applyTheme available globally for settings page
	window.applyTheme = applyTheme;

	// Re-apply on page load in case DOM wasn't ready
	document.addEventListener('DOMContentLoaded', function() {
		applyTheme(localStorage.getItem('theme') || 'system');
	});
})();
