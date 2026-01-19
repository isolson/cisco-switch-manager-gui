<?php

/**
 * Rate Limiting
 *
 * Provides protection against brute-force login attempts.
 */

require_once(__DIR__ . '/datastore.php');

// Configuration
define('MAX_LOGIN_ATTEMPTS', 5);        // Max failed attempts before lockout
define('LOCKOUT_DURATION', 900);        // Lockout duration in seconds (15 minutes)
define('ATTEMPT_WINDOW', 900);          // Window to count attempts (15 minutes)

/**
 * Get the rate limit data file path
 *
 * @return string File path
 */
function getRateLimitFile() {
	return getDataPath('.ratelimit.json');
}

/**
 * Get rate limit data
 *
 * @return array Rate limit data
 */
function getRateLimitData() {
	$file = getRateLimitFile();

	if (!file_exists($file)) {
		return [];
	}

	$content = @file_get_contents($file);
	if ($content === false) {
		return [];
	}

	$data = json_decode($content, true);
	return is_array($data) ? $data : [];
}

/**
 * Save rate limit data
 *
 * @param array $data Rate limit data
 * @return bool Success
 */
function saveRateLimitData($data) {
	$file = getRateLimitFile();
	$dir = dirname($file);

	if (!is_dir($dir)) {
		@mkdir($dir, 0700, true);
	}

	return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Get client identifier (IP address)
 *
 * @return string Client identifier
 */
function getClientIdentifier() {
	// Use X-Forwarded-For if behind a proxy, but validate it
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

	// Only trust X-Forwarded-For if REMOTE_ADDR is a known proxy
	// This prevents IP spoofing attacks
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$trustedProxies = ['127.0.0.1', '::1', '172.17.0.1']; // Docker bridge
		if (in_array($ip, $trustedProxies)) {
			$forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip = trim($forwarded[0]);
		}
	}

	return $ip;
}

/**
 * Check if client is currently locked out
 *
 * @param string|null $identifier Optional client identifier
 * @return array ['locked' => bool, 'remaining' => int seconds]
 */
function isLockedOut($identifier = null) {
	if ($identifier === null) {
		$identifier = getClientIdentifier();
	}

	$data = getRateLimitData();

	if (!isset($data[$identifier])) {
		return ['locked' => false, 'remaining' => 0];
	}

	$record = $data[$identifier];

	// Check if locked out
	if (isset($record['lockout_until']) && $record['lockout_until'] > time()) {
		return [
			'locked' => true,
			'remaining' => $record['lockout_until'] - time()
		];
	}

	return ['locked' => false, 'remaining' => 0];
}

/**
 * Record a failed login attempt
 *
 * @param string|null $identifier Optional client identifier
 * @return array ['locked' => bool, 'attempts' => int, 'remaining' => int]
 */
function recordFailedAttempt($identifier = null) {
	if ($identifier === null) {
		$identifier = getClientIdentifier();
	}

	$data = getRateLimitData();
	$now = time();

	// Initialize or get existing record
	if (!isset($data[$identifier])) {
		$data[$identifier] = [
			'attempts' => [],
			'lockout_until' => null
		];
	}

	$record = &$data[$identifier];

	// Clean old attempts outside the window
	$record['attempts'] = array_filter($record['attempts'], function($timestamp) use ($now) {
		return ($now - $timestamp) < ATTEMPT_WINDOW;
	});

	// Add new attempt
	$record['attempts'][] = $now;

	// Check if we need to lock out
	$attemptCount = count($record['attempts']);
	$locked = false;
	$remaining = 0;

	if ($attemptCount >= MAX_LOGIN_ATTEMPTS) {
		$record['lockout_until'] = $now + LOCKOUT_DURATION;
		$locked = true;
		$remaining = LOCKOUT_DURATION;
	}

	saveRateLimitData($data);

	return [
		'locked' => $locked,
		'attempts' => $attemptCount,
		'remaining' => $remaining
	];
}

/**
 * Clear failed attempts for a client (call on successful login)
 *
 * @param string|null $identifier Optional client identifier
 */
function clearFailedAttempts($identifier = null) {
	if ($identifier === null) {
		$identifier = getClientIdentifier();
	}

	$data = getRateLimitData();

	if (isset($data[$identifier])) {
		unset($data[$identifier]);
		saveRateLimitData($data);
	}
}

/**
 * Clean up old rate limit records (maintenance)
 */
function cleanupRateLimitData() {
	$data = getRateLimitData();
	$now = time();
	$changed = false;

	foreach ($data as $identifier => $record) {
		// Remove if no lockout and no recent attempts
		$hasRecentAttempts = false;
		if (isset($record['attempts'])) {
			foreach ($record['attempts'] as $timestamp) {
				if (($now - $timestamp) < ATTEMPT_WINDOW) {
					$hasRecentAttempts = true;
					break;
				}
			}
		}

		$hasActiveLockout = isset($record['lockout_until']) && $record['lockout_until'] > $now;

		if (!$hasRecentAttempts && !$hasActiveLockout) {
			unset($data[$identifier]);
			$changed = true;
		}
	}

	if ($changed) {
		saveRateLimitData($data);
	}
}

/**
 * Get remaining login attempts before lockout
 *
 * @param string|null $identifier Optional client identifier
 * @return int Remaining attempts
 */
function getRemainingAttempts($identifier = null) {
	if ($identifier === null) {
		$identifier = getClientIdentifier();
	}

	$data = getRateLimitData();
	$now = time();

	if (!isset($data[$identifier]) || !isset($data[$identifier]['attempts'])) {
		return MAX_LOGIN_ATTEMPTS;
	}

	// Count recent attempts
	$recentAttempts = count(array_filter($data[$identifier]['attempts'], function($timestamp) use ($now) {
		return ($now - $timestamp) < ATTEMPT_WINDOW;
	}));

	return max(0, MAX_LOGIN_ATTEMPTS - $recentAttempts);
}
