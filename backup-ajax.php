<?php
/**
 * AJAX endpoint for backup operations
 */

require_once('php/session.php');
require_once('php/functions.php');
require_once('config.php');
require_once('php/backupoperations.php');

header('Content-Type: application/json');

// Check if feature is enabled
if (!defined('ENABLE_CONFIG_BACKUP') || !ENABLE_CONFIG_BACKUP) {
	echo json_encode(['error' => 'Config backup feature is disabled']);
	exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
	case 'backup_all':
		$results = backupAllSwitches();

		// Auto-sync to GitHub if configured
		$settings = getBackupSettings();
		$syncResult = null;
		if ($settings['github_configured'] && $settings['auto_sync']) {
			$syncResult = syncToGitHub("Backup all switches - " . date('Y-m-d H:i:s'));
		}

		echo json_encode([
			'success' => true,
			'results' => $results,
			'sync_result' => $syncResult
		]);
		break;

	case 'backup_switch':
		$switchAddr = $_POST['switch_addr'] ?? '';
		$switch = getSwitchByAddr($switchAddr);

		if (!$switch) {
			echo json_encode(['success' => false, 'error' => 'Switch not found']);
			exit;
		}

		$creds = getCredentialsForSwitch($switch, true);
		if (!$creds) {
			echo json_encode(['success' => false, 'error' => 'No credentials available']);
			exit;
		}

		$result = getRunningConfig($switchAddr, $creds['username'], $creds['password']);
		if (!$result['success']) {
			echo json_encode(['success' => false, 'error' => $result['error']]);
			exit;
		}

		$saveResult = saveConfigBackup($switchAddr, $switch['name'], $result['config']);

		// Auto-sync if enabled
		$settings = getBackupSettings();
		$syncResult = null;
		if ($saveResult['success'] && $settings['github_configured'] && $settings['auto_sync']) {
			$syncResult = syncToGitHub("Backup " . $switch['name']);
		}

		echo json_encode([
			'success' => $saveResult['success'],
			'error' => $saveResult['error'],
			'path' => $saveResult['path'],
			'sync_result' => $syncResult
		]);
		break;

	case 'sync_github':
		$syncResult = syncToGitHub();
		echo json_encode($syncResult);
		break;

	case 'get_status':
		$switchAddr = $_GET['switch_addr'] ?? '';
		$latestBackup = getLatestBackup($switchAddr);
		echo json_encode([
			'has_backup' => $latestBackup !== null,
			'backup' => $latestBackup
		]);
		break;

	default:
		echo json_encode(['error' => 'Invalid action']);
}
