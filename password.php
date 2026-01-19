<?php

require_once('php/session.php');

require_once('php/functions.php');
require_once('config.php');
require_once('lang.php');
require_once('php/csrf.php');

if(!ENABLE_PASSWORD_CHANGE) die('This feature is disabled.');

?>

<!DOCTYPE html>
<html>
<head>
	<title><?php translate('Change Password'); ?> - <?php translate('Cisco Switch Manager GUI'); ?></title>
	<?php require('head.inc.php'); ?>
</head>
<body>
	<script>
	function beginFadeOutAnimation() {
		document.getElementById('imgSwitch').style.opacity = 0;
		document.getElementById('imgLoading').style.opacity = 1;
	}
	function endFadeOutAnimation() {
		document.getElementById('imgSwitch').style.opacity = 0;
		document.getElementById('imgLoading').style.opacity = 1;
	}
	</script>
	<style>
	/* password-change-only style definitions */
	.changeok {
		font-weight: bold;
		color: green;
	}
	.changefail {
		font-weight: bold;
		color: red;
	}
	</style>

	<div id='container'>
		<h1 id='title'><div id='logo'></div></h1>

		<div id='splash' class='login'>
			<div id='imgContainer'>
				<img id='imgLoading' src='img/loading.svg'></img>
				<img id='imgSwitch' src='img/switch.png'></img>
			</div>
			<div id='subtitle'>
				<p>
					<?php translate('With this form you can change your password on all switches. After the procedure is done, you have to log in again.'); ?>
				</p>
				<p>
					<?php translate('This procedure can take some minutes, because the webserver has to establish an SSH connection to all switches. Please do not close this page until finished.'); ?>
				</p>
			</div>

<?php
$info = "";
$showform = true;

// Dangerous characters that could be used for command injection
$dangerousChars = ['$', ' ', '"', "'", '>', '<', '|', "\n", "\r", '`', ';', '&', '(', ')', '{', '}', '[', ']', '\\'];

if(isset($_POST['newpw']) && isset($_POST['newpw2'])) {
	// Validate CSRF token first
	if (!checkCSRFToken()) {
		$info = "<div class='infobox error'>".translate("Security token expired. Please try again.",false)."</div>";
	} elseif($_POST['newpw'] == $_POST['newpw2']) {
		if($_POST['newpw'] != "") {
			// Check for dangerous characters
			$hasDangerousChars = false;
			foreach ($dangerousChars as $char) {
				if (strpos($_POST['newpw'], $char) !== false || strpos($_POST['username'], $char) !== false) {
					$hasDangerousChars = true;
					break;
				}
			}

			if(!$hasDangerousChars) {
				// Sanitize inputs
				$safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['username']);
				$safePassword = $_POST['newpw']; // Already validated above

				echo "<script>beginFadeOutAnimation();</script>";

				foreach(getAllSwitches() as $currentswitch) {
					echo "<span>" . htmlspecialchars($currentswitch['name']) . "...</span>&nbsp;"; flush(); ob_flush();

					$connection = @ssh2_connect($currentswitch['addr'], 22);

					if($connection !== false) {
						if(@ssh2_auth_password($connection, $_SESSION['username'], $_SESSION['password']) !== false) {
							$stdio_stream = @ssh2_shell($connection);
							if($stdio_stream !== false) {

								fwrite($stdio_stream, "conf t" . "\n" .
								                      "username " . $safeUsername . " password 0 " . $safePassword . "\n" .
								                      "end" . "\n" .
								                      "wr mem" . "\n" .
								                      "exit" . "\n");
								// Read output but don't store it (security)
								stream_get_contents($stdio_stream);
								echo "<span class='changeok'>OK</span><br>"; flush(); ob_flush();

							} else {
								echo "<span class='changefail'>FAIL! (cmd execution error)</span><br>"; flush(); ob_flush();
							}
						} else {
							echo "<span class='changefail'>FAIL! (auth error)</span><br>"; flush(); ob_flush();
						}
					} else {
						echo "<span class='changefail'>FAIL! (connection error)</span><br>"; flush(); ob_flush();
					}

				}
				session_destroy();
				$info .= "<div class='infobox ok'>".translate("Password changed on the displayed switches",false)."</div>";
				$info .= "<div class='infobox info'>".translate("You need to login again",false)."</div>";
				$info .= "<a href='login.php' class='slubbutton'>".translate("Re-Login Now",false)."</a>";
				$showform = false;
				echo "<script>endFadeOutAnimation();</script>";
			} else {
				$info = "<div class='infobox warn'>".translate("The following special chars are not allowed!",false)."<br><span style='font-family: monospace;'>\" | > $ &lt; ` ; &amp; ' ( ) { } [ ] \\</span></div>";
			}
		} else {
			$info = "<div class='infobox warn'>".translate("The password cannot be empty",false)."</div>";
		}
	} else {
		$info = "<div class='infobox warn'>".translate("The passwords do not match!",false)."</div>";
	}
}
?>

				<?php echo $info; ?>

				<?php if($showform == true) { ?>
					<form method='POST' name='passwordform' onsubmit='beginFadeOutAnimation();'>
						<?php csrfField(); ?>
						<div class='form-row'>
							<input type='text' name='username' id='username' value='<?php echo htmlspecialchars($_SESSION['username']); ?>' readonly></input>
							<span class='tip'><label for='description'><?php translate('Username'); ?></label></span>
							<br>
						</div>
						<div class='form-row'>
							<input type='password' name='newpw' id='newpw' autofocus='true'></input>
							<span class='tip'><label for='description'><?php translate('New Password'); ?></label></span>
							<br>
						</div>
						<div class='form-row'>
							<input type='password' name='newpw2' id='newpw2' autofocus='true'></input>
							<span class='tip'><label for='description'><?php translate('Confirm New Password'); ?></label></span>
							<br>
						</div>
						<div class='form-row'>
							<button class='slubbutton'><?php translate('Change Password'); ?></button>
						</div>
					</form>

					<a href='index.php' onclick='beginFadeOutAnimation();'>&gt;<?php translate('Back'); ?></a>
				<?php } ?>

		</div>

		<?php require('foot.inc.php'); ?>

	</div>

<?php require('menu.inc.php'); ?>

</body>
</html>
