<?php

/**
 * CSRF Token Protection
 *
 * Provides CSRF token generation and validation for form submissions.
 */

/**
 * Generate or retrieve the current CSRF token
 *
 * @return string CSRF token
 */
function getCSRFToken() {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		return '';
	}

	if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
		regenerateCSRFToken();
	}

	// Regenerate token if older than 1 hour
	if (time() - $_SESSION['csrf_token_time'] > 3600) {
		regenerateCSRFToken();
	}

	return $_SESSION['csrf_token'];
}

/**
 * Generate a new CSRF token
 */
function regenerateCSRFToken() {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	$_SESSION['csrf_token_time'] = time();
}

/**
 * Validate a submitted CSRF token
 *
 * @param string $token Token from form submission
 * @return bool True if valid
 */
function validateCSRFToken($token) {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		return false;
	}

	if (!isset($_SESSION['csrf_token'])) {
		return false;
	}

	if (empty($token)) {
		return false;
	}

	// Use timing-safe comparison
	return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF token input field
 */
function csrfField() {
	$token = getCSRFToken();
	echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request and die if invalid
 *
 * @param string $errorMessage Optional custom error message
 */
function requireCSRFToken($errorMessage = 'Invalid security token. Please try again.') {
	$token = $_POST['csrf_token'] ?? '';

	if (!validateCSRFToken($token)) {
		http_response_code(403);
		die($errorMessage);
	}
}

/**
 * Check if a POST request has a valid CSRF token
 *
 * @return bool True if valid or not a POST request
 */
function checkCSRFToken() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return true;
	}

	$token = $_POST['csrf_token'] ?? '';
	return validateCSRFToken($token);
}
