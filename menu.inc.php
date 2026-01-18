<?php require_once('lang.php'); ?>

	<div id='topmenu' style='text-align: center;'>
		<?php
		// Get username from web user session or legacy switch session
		$htmlUsername = '';
		if(isset($_SESSION['web_user']) && isset($_SESSION['web_user']['username'])) {
			$htmlUsername = htmlspecialchars($_SESSION['web_user']['username'], ENT_QUOTES, 'UTF-8');
		} elseif(isset($_SESSION['username'])) {
			$htmlUsername = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
		}
		$isWebUserAuthenticated = isset($_SESSION['web_user_authenticated']) && $_SESSION['web_user_authenticated'] === true;
		?>
		<?php if(isset($MAINCTRLS) == false || (isset($MAINCTRLS) == true && $MAINCTRLS == true)) { ?>
		<div style='float: left;'>
			<a href='index.php' class='slubbutton secondary' id='mainmenubtn' title='<?php translate('Switch Monitoring'); ?>'><?php translate('Monitoring'); ?></a>
			<?php if(defined('ENABLE_CONFIG_BACKUP') && ENABLE_CONFIG_BACKUP) { ?>
				<a href='backup.php' class='slubbutton secondary' id='backupbtn' title='<?php translate('Backup switch configurations'); ?>'><?php translate('Backups'); ?></a>
			<?php } ?>
			<?php if($isWebUserAuthenticated) { ?>
				<a href='settings.php?tab=switches' class='slubbutton secondary' id='switchesbtn' title='<?php translate('Manage switches'); ?>'><?php translate('Switches'); ?></a>
			<?php } ?>
		</div>
		<?php } ?>
		<?php if(!empty($ZOOM)) { ?>
		<script>
			function zoom(zoom) { document.body.style.zoom = zoom; }
		</script>
		<style>
			/* style definitions for maps */
			#topmenu {
				opacity: 0.95;
				background-color: rgba(255,255,255,0.75);
				backdrop-filter: blur(8px) brightness(90%) contrast(120%); /* bleeding edge feature of chrome and safari :> */
				border-bottom: 1px solid lightgray;
			}
			div#zoomlinks > a {
				min-width: 50px;
			}
		</style>
		<div style='text-align: center; display: inline-block;' id='zoomlinks'>
			<a href='#' class='slubbutton secondary notypo' onclick='zoom("50%");'>50%</a>&nbsp;
			<a href='#' class='slubbutton secondary notypo' onclick='zoom("80%");'>80%</a>&nbsp;
			<a href='#' class='slubbutton secondary notypo' onclick='zoom("100%");'>100%</a>&nbsp;
			<a href='#' class='slubbutton secondary notypo' onclick='zoom("120%");'>120%</a>&nbsp;
		</div>
		<?php } ?>
		<div style='float: right;'>
			<?php if($isWebUserAuthenticated) { ?>
				<a href='settings.php' class='slubbutton secondary' id='settingsbtn' title='<?php translate('Manage users and system settings'); ?>'><?php translate('Settings'); ?></a>
			<?php } ?>
			<?php if(defined('ENABLE_PASSWORD_CHANGE') && ENABLE_PASSWORD_CHANGE) { ?>
				<a href='password.php' class='slubbutton secondary' id='pwchangebtn' title='<?php translate('Change switch password'); ?>'><?php translate('Password'); ?></a>
			<?php } ?>
			<?php if($htmlUsername != '') { ?>
				<a href='login.php?logout=1' class='slubbutton destructive' id='logoutbtn'><?php echo str_replace('%USER%', $htmlUsername, translate('Log Out %USER%',false)); ?></a>
			<?php } ?>
		</div>
	</div>
