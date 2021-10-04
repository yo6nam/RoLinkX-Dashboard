<?php
/*
*   RoLinkX Dashboard v0.8a
*   Copyright (C) 2021 by Razvan Marin YO6NAM / www.xpander.ro
*
*   This program is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 2 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software
*   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

/*
* System reporting / actions module
*/

$halt				= (isset($_POST['halt'])) ? filter_input(INPUT_POST, 'halt', FILTER_SANITIZE_NUMBER_INT) : '';
$reboot				= (isset($_POST['reboot'])) ? filter_input(INPUT_POST, 'reboot', FILTER_SANITIZE_NUMBER_INT) : '';
$rewifi				= (isset($_POST['rewifi'])) ? filter_input(INPUT_POST, 'rewifi', FILTER_SANITIZE_NUMBER_INT) : '';
$resvx				= (isset($_POST['resvx'])) ? filter_input(INPUT_POST, 'resvx', FILTER_SANITIZE_NUMBER_INT) : '';
$endsvx				= (isset($_POST['endsvx'])) ? filter_input(INPUT_POST, 'endsvx', FILTER_SANITIZE_NUMBER_INT) : '';
$switchHostName		= (isset($_POST['switchHostName'])) ? filter_input(INPUT_POST, 'switchHostName', FILTER_SANITIZE_NUMBER_INT) : '';

/* Configuration */
if (isset($_POST)) {
	$changed = false;
	$config = include '../config.php';
	foreach ($config as $cfgItem => $cfgItemValue) {
		if (isset($_POST[$cfgItem])) {
			$oldValue = $config[$cfgItem];
			$newValue = $_POST[$cfgItem];
			if ($oldValue != $newValue) {
				$config[$cfgItem] = $newValue;
				$changed = true;
			}
		}
	}
	if ($changed) {
		file_put_contents('../config.php', '<?php'. PHP_EOL .'return '. var_export($config, true) . ';' . PHP_EOL);
		echo 'Configuration saved!';
		exit(0);
	}
}

/* Stop SVXLink */
if ($endsvx == 1) echo stopSVXLink();
function stopSVXLink() {
	exec("/usr/bin/sudo /usr/bin/systemctl stop rolink.service");
	return true;
}

/* Restart SVXLink */
if ($resvx == 1) echo restartSVXLink();
function restartSVXLink() {
	exec("/usr/bin/sudo /usr/bin/systemctl restart rolink.service");
	return true;
}

/* Restart Wi-Fi */
if ($rewifi == 1) echo wifiRestart();
function wifiRestart() {
	exec("/usr/bin/sudo /sbin/wpa_cli -i wlan0 reconfigure");
	/* Reserved for future version
	exec("/usr/bin/sudo /usr/bin/autohotspotN reload", $reply);
	$result = (empty($reply)) ? 'Command failed' : json_encode($reply);
	return $result;
	*/
	return true;
}

/* Power Off System */
if ($halt == 1) sysHalt();
function sysHalt() {
	/* If stuck in TX, force exit */
	$config = include '../config.php';
	$pinPath = '/sys/class/gpio/gpio'. $config['cfgPttPin'] .'/value';
	shell_exec('/usr/bin/sudo /usr/bin/chmod guo+rw '. $pinPath .'; /usr/bin/echo 0 > '. $pinPath .';/usr/bin/sudo /usr/sbin/halt -p');
	exit(0);
}

/* Reboot System */
if ($reboot == 1) sysReboot();
function sysReboot() {
	exec("/usr/bin/sudo /usr/sbin/reboot");
	exit(0);
}

/* Switch Host Name */
if ($switchHostName == 1) echo switchHostName();
function switchHostName() {
	$hostName = gethostname();
	preg_match('/CALLSIGN=(\S+)/', file_get_contents('/opt/rolink/conf/rolink.conf'), $callSign);
	$newHostName = preg_replace('/[^a-zA-Z0-9\-\._]/', '', trim($callSign[1]));
	if ($newHostName != 'N0CALL' && $hostName != $newHostName) {
		exec("/usr/bin/sudo /usr/bin/hostnamectl set-hostname $newHostName");
		return 'Hostname has been changed from <br/><b>' . $hostName . '</b> to <b>' . $newHostName . '</b><br/>You need to reboot to apply changes.';
	} else {
		return 'Nothing changed.<br/>New and old hostnames are the same.';
	}
	return false;
}
