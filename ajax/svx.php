<?php
/*
*   RoLinkX Dashboard v0.9g
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
* SVXLink configuration module
*/

$cfgFile		= '/opt/rolink/conf/rolink.conf';
$newFile		= '/tmp/rolink.conf.tmp';
$profilesPath	= dirname(__FILE__) . '/../profiles/';
$newProfile		= false;
$changes		= 0;
$msgOut			= null;
$oldVar = $newVar = $profiles = array();

// Get File system status
exec('/usr/bin/cat /proc/mounts | grep -Po \'(?<=(ext4\s)).*(?=,noatime)\'', $fileSystemStatus);

// Retrieve GET vars
$frmLoadProfile	= (isset($_GET['lpn'])) ? filter_input(INPUT_GET, 'lpn', FILTER_SANITIZE_STRING) : '';

// Retrieve POST vars (defaults if empty values to avoid locking the config file)
$frmProfile		= (isset($_POST['prn'])) ? filter_input(INPUT_POST, 'prn', FILTER_SANITIZE_STRING) : '';
$frmReflector	= (empty($_POST['ref'])) ? 'svx.439100.ro' : filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING);
$frmPort		= (empty($_POST['prt'])) ? '1234' : filter_input(INPUT_POST, 'prt', FILTER_SANITIZE_NUMBER_INT);
$frmCallsign	= (empty($_POST['cal'])) ? 'YO1XYZ-P' : filter_input(INPUT_POST, 'cal', FILTER_SANITIZE_STRING);
$frmAuthKey		= (empty($_POST['key'])) ? 'password' : filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);
$frmBeacon		= (empty($_POST['clb'])) ? 'YO1XYZ' : filter_input(INPUT_POST, 'clb', FILTER_SANITIZE_STRING);
$frmVoice		= (empty($_POST['vop'])) ? 'en_US' : filter_input(INPUT_POST, 'vop', FILTER_SANITIZE_STRING);
$frmShortId		= (empty($_POST['sid'])) ? '0' : filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING);
$frmLongId		= (empty($_POST['lid'])) ? '0' : filter_input(INPUT_POST, 'lid', FILTER_SANITIZE_STRING);
$frmBitrate		= (empty($_POST['cbr'])) ? '20000' : filter_input(INPUT_POST, 'cbr', FILTER_SANITIZE_STRING);
$frmRogerBeep	= (empty($_POST['rgr'])) ? '0' : filter_input(INPUT_POST, 'rgr', FILTER_SANITIZE_NUMBER_INT);
$frmRxGPIO		= (empty($_POST['rxp'])) ? 'gpio10' : filter_input(INPUT_POST, 'rxp', FILTER_SANITIZE_STRING);
$frmTxGPIO		= (empty($_POST['txp'])) ? 'gpio7' : filter_input(INPUT_POST, 'txp', FILTER_SANITIZE_STRING);
$frmMonitorTgs	= (empty($_POST['mtg'])) ? '226++' : filter_input(INPUT_POST, 'mtg', FILTER_SANITIZE_STRING);
$frmTgTimeOut	= (empty($_POST['tgt'])) ? '30' : filter_input(INPUT_POST, 'tgt', FILTER_SANITIZE_NUMBER_INT);

$frmDelProfile	= (empty($_POST['prd'])) ? '' : filter_input(INPUT_POST, 'prd', FILTER_SANITIZE_STRING);

/* Process DTMF commands */
if (isset($_POST['dtmfCommand'])) {
	$dtmfCommand = (!empty($_POST['dtmfCommand'])) ? filter_input(INPUT_POST, 'dtmfCommand', FILTER_SANITIZE_STRING) : null;
	if (!is_link('/tmp/dtmf')) {
		echo "RoLink is not running!";
		return false;
	}
	if (!empty($dtmfCommand)) {
		shell_exec('/usr/bin/sudo /usr/bin/chmod guo+rw /tmp/dtmf');
		exec("/usr/bin/echo $dtmfCommand >/tmp/dtmf", $reply);
		echo "<b>$dtmfCommand</b> executed!";
	}
	exit(0);
}

// Switch back to Read-Only FS
function toggleFS() {
	exec('/usr/bin/cat /proc/mounts | grep -Po \'(?<=(ext4\s)).*(?=,noatime)\'', $fileSystemStatus);
	if ($fileSystemStatus[0] == 'rw') {
		exec("/usr/bin/sudo /usr/bin/mount -o remount,ro /");
		sleep(1);
	}
}

// Add file contents to buffer
$oldCfg = file_get_contents($cfgFile);

// Get current variables
preg_match('/(CALLSIGN=")(\S+)"/', $oldCfg, $varCallSign);
preg_match('/(HOST=)(\S+)/', $oldCfg, $varReflector);
preg_match('/(PORT=)(\d+)/', $oldCfg, $varPort);
preg_match('/(AUTH_KEY=)"(\S+)"/', $oldCfg, $varAuthKey);
preg_match('/(CALLSIGN=)(\w\S+)/', $oldCfg, $varBeacon);
preg_match('/(DEFAULT_LANG=)(\S+)/', $oldCfg, $varVoicePack);
preg_match('/(SHORT_IDENT_INTERVAL=)(\d+)/', $oldCfg, $varShortIdent);
preg_match('/(LONG_IDENT_INTERVAL=)(\d+)/', $oldCfg, $varLongIdent);
preg_match('/(OPUS_ENC_BITRATE=)(\d+)/', $oldCfg, $varCodecBitRate);
preg_match('/(RGR_SOUND_ALWAYS=)(\d+)/', $oldCfg, $varRogerBeep);
preg_match('/(GPIO_SQL_PIN=)(\S+)/', $oldCfg, $varRxGPIO);
preg_match('/(PTT_PIN=)(\S+)/', $oldCfg, $varTxGPIO);
preg_match('/(MONITOR_TGS=)(\S+)/', $oldCfg, $varMonitorTgs);
preg_match('/(TG_SELECT_TIMEOUT=)(\d+)/', $oldCfg, $varTgSelTimeOut);

$reflectorValue		= (isset($varReflector[2])) ? $varReflector[2] : '';
$portValue			= (isset($varPort[2])) ? $varPort[2] : '';
$callSignValue		= (isset($varCallSign[2])) ? $varCallSign[2] : '';
$authKeyValue		= (isset($varAuthKey[2])) ?  $varAuthKey[2] : '';
$beaconValue		= (isset($varBeacon[2])) ?  $varBeacon[2] : '';
$voicePackValue		= (isset($varVoicePack[2])) ?  $varVoicePack[2] : '';
$shortIdentValue	= (isset($varShortIdent[2])) ?  $varShortIdent[2] : '';
$longIdentValue		= (isset($varLongIdent[2])) ? $varLongIdent[2] : '';
$codecBitrateValue	= (isset($varCodecBitRate[2])) ? $varCodecBitRate[2] : '';

$rogerBeepValue		= (isset($varRogerBeep[2])) ? $varRogerBeep[2] : '';
$rxGPIOValue		= (isset($varRxGPIO[2])) ? $varRxGPIO[2] : '';
$txGPIOValue		= (isset($varTxGPIO[2])) ? $varTxGPIO[2] : '';
$monitorTgsValue	= (isset($varMonitorTgs[2])) ? $varMonitorTgs[2] : '';
$tgSelectTOValue	= (isset($varTgSelTimeOut[2])) ? $varTgSelTimeOut[2] : '';

/* Profile defaults */
$profiles['reflector']	= $reflectorValue;
$profiles['port']		= $portValue;
$profiles['callsign']	= $callSignValue;
$profiles['key']		= $authKeyValue;
$profiles['beacon']		= $beaconValue;
$profiles['bitrate']	= $codecBitrateValue;

/* Process new values, if inserted */
$oldVar[0]	= '/(CALLSIGN=)(\w\S+)/';
$newVar[0]	= '${1}' . $frmBeacon;
if ($beaconValue != $frmBeacon) {
	++$changes;
	$profiles['beacon'] = $frmBeacon;
}

$oldVar[1]	= '/(HOST=)(\S+)/';
$newVar[1]	= '${1}' . $frmReflector;
if ($reflectorValue != $frmReflector) {
	++$changes;
	$profiles['reflector'] = $frmReflector;
}

$oldVar[2]	= '/(PORT=)(\d+)/';
$newVar[2]	= '${1}' . $frmPort;
if ($portValue != $frmPort) {
	++$changes;
	$profiles['port'] = $frmPort;
}

$oldVar[3]	= '/(CALLSIGN=")(\S+)"/';
$newVar[3]	= '${1}'. $frmCallsign .'"';
if ($callSignValue != $frmCallsign) {
	++$changes;
	$profiles['callsign'] = $frmCallsign;
}

$oldVar[4]	= '/(AUTH_KEY=)"(\S+)"/';
$newVar[4]	= '${1}"'. $frmAuthKey .'"';
if ($authKeyValue != $frmAuthKey) {
	++$changes;
	$profiles['key'] = $frmAuthKey;
}

$oldVar[5]	= '/(SHORT_IDENT_INTERVAL=)(\d+)/';
$newVar[5]	= '${1}'. $frmShortId;
if ($shortIdentValue != $frmShortId) {
	++$changes;
}

$oldVar[6]	= '/(LONG_IDENT_INTERVAL=)(\d+)/';
$newVar[6]	= '${1}'. $frmLongId;
if ($longIdentValue != $frmLongId) {
	++$changes;
}

$oldVar[7]	= '/(OPUS_ENC_BITRATE=)(\d+)/';
$newVar[7]	= '${1}'. $frmBitrate;
if ($codecBitrateValue != $frmBitrate) {
	++$changes;
	$profiles['bitrate'] = $frmBitrate;
}

$oldVar[8]	= '/(DEFAULT_LANG=)(\S+)/';
$newVar[8]	= '${1}'. $frmVoice;
if ($voicePackValue != $frmVoice) {
	++$changes;
}

$oldVar[9]	= '/(RGR_SOUND_ALWAYS=)(\d+)/';
$newVar[9]	= '${1}'. $frmRogerBeep;
if ($rogerBeepValue != $frmRogerBeep) {
	++$changes;
}
$oldVar[10]	= '/(GPIO_SQL_PIN=)(\S+)/';
$newVar[10]	= '${1}'. $frmRxGPIO;
if ($rxGPIOValue != $frmRxGPIO) {
	++$changes;
}
$oldVar[11]	= '/(PTT_PIN=)(\S+)/';
$newVar[11]	= '${1}'. $frmTxGPIO;
if ($txGPIOValue != $frmTxGPIO) {
	++$changes;
}
$oldVar[12]	= '/(MONITOR_TGS=)(\S+)/';
$newVar[12]	= '${1}'. $frmMonitorTgs;
if ($monitorTgsValue != $frmMonitorTgs) {
	++$changes;
}
$oldVar[13]	= '/(TG_SELECT_TIMEOUT=)(\d+)/';
$newVar[13]	= '${1}'. $frmTgTimeOut;
if ($tgSelectTOValue != $frmTgTimeOut) {
	++$changes;
}

/* Create profile */
if (!empty($frmProfile)) {
	$profile = json_encode($profiles, JSON_PRETTY_PRINT);
	$proFileName = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $frmProfile) . '.json';
	// Change FS State
	if ($fileSystemStatus[0] == 'ro') {
		exec("/usr/bin/sudo /usr/bin/mount -o remount,rw /");
		sleep(1);
	}
	file_put_contents($profilesPath . $proFileName, $profile);
	$newProfile = true;
}

/* Load profile */
if (!empty($frmLoadProfile)) {
	$selectedProfile = $profilesPath . $frmLoadProfile;
	if (is_file($selectedProfile)) {
		echo file_get_contents($selectedProfile);
	}
	exit(0);
}

/* Delete profile */
if (!empty($frmDelProfile)) {
	if ($fileSystemStatus[0] == 'ro') {
		exec("/usr/bin/sudo /usr/bin/mount -o remount,rw /");
		sleep(1);
	}
	unlink($profilesPath . $frmDelProfile);
 	echo 'Profile "'. basename($frmDelProfile, '.json') .'" has been deleted';
 	toggleFS();
 	exit(0);
}

// Compare current stored values vs new values from form
if ($changes > 0) {
	// Stop SVXLink service before attempting anything
	shell_exec('/usr/bin/sudo /usr/bin/systemctl stop rolink.service');
	$newCfg = preg_replace($oldVar, $newVar, $oldCfg);
	if ($fileSystemStatus[0] == 'ro') {
		exec("/usr/bin/sudo /usr/bin/mount -o remount,rw /");
		sleep(1);
	}
	file_put_contents($newFile, $newCfg);
	shell_exec("sudo /usr/bin/cp $newFile /opt/rolink/conf/rolink.conf");
	$msgOut .= 'Configuration updated ('. $changes .' change(s) applied)<br/>Restarting RoLink service...';
	$msgOut .= ($newProfile) ? '<br/>Profile saved as ' . basename($proFileName, '.json') : '';

	// All done, start SVXLink service
	shell_exec('/usr/bin/sudo /usr/bin/systemctl start rolink.service');
} else {
	$msgOut .= 'No new data to process.<br/>Keeping original configuration.';
	$msgOut .= ($newProfile) ? '<br/>Profile saved as ' . basename($proFileName, '.json') : '';
}
toggleFS();
echo $msgOut;
