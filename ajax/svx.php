<?php
/*
*   RoLinkX Dashboard v0.3
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
$msgOut			= NULL;
$oldVar = $newVar = $profiles = array();

// Retrieve GET vars
$frmLoadProfile	= (isset($_GET['lpn'])) ? filter_input(INPUT_GET, 'lpn', FILTER_SANITIZE_STRING) : '';

// Retrieve POST vars (defaults if empty values to avoid locking the config file)
$frmProfile		= (isset($_POST['prn'])) ? filter_input(INPUT_POST, 'prn', FILTER_SANITIZE_STRING) : '';
$frmReflector	= (empty($_POST['ref'])) ? 'svx.439100.ro' : filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING);
$frmPort		= (empty($_POST['prt'])) ? '1234' : filter_input(INPUT_POST, 'prt', FILTER_SANITIZE_NUMBER_INT);
$frmCallsign	= (empty($_POST['cal'])) ? 'YO1XYZ-P' : filter_input(INPUT_POST, 'cal', FILTER_SANITIZE_STRING);
$frmAuthKey		= (empty($_POST['key'])) ? 'password' : filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);
$frmBeacon		= (empty($_POST['clb'])) ? 'YO1XYZ' : filter_input(INPUT_POST, 'clb', FILTER_SANITIZE_STRING);
$frmShortId		= (empty($_POST['sid'])) ? '0' : filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING);
$frmLongId		= (empty($_POST['lid'])) ? '0' : filter_input(INPUT_POST, 'lid', FILTER_SANITIZE_STRING);
$frmBitrate		= (empty($_POST['cbr'])) ? '20000' : filter_input(INPUT_POST, 'cbr', FILTER_SANITIZE_STRING);
$frmDelProfile	= (empty($_POST['prd'])) ? '' : filter_input(INPUT_POST, 'prd', FILTER_SANITIZE_STRING);

// Add file contents to buffer
$oldCfg = file_get_contents($cfgFile);

// Get current variables
preg_match('/(CALLSIGN=")(\S+)"/', $oldCfg, $varCallSign);
preg_match('/(HOST=)(\S+)/', $oldCfg, $varReflector);
preg_match('/(PORT=)(\d+)/', $oldCfg, $varPort);
preg_match('/(AUTH_KEY=)"(\S+)"/', $oldCfg, $varAuthKey);
preg_match('/(CALLSIGN=)(\w\S+)/', $oldCfg, $varBeacon);
preg_match('/(SHORT_IDENT_INTERVAL=)(\d+)/', $oldCfg, $varShortIdent);
preg_match('/(LONG_IDENT_INTERVAL=)(\d+)/', $oldCfg, $varLongIdent);
preg_match('/(OPUS_ENC_BITRATE=)(\d+)/', $oldCfg, $varCodecBitRate);

$reflectorValue		= (isset($varReflector[2])) ? $varReflector[2] : '';
$portValue			= (isset($varPort[2])) ? $varPort[2] : '';
$callSignValue		= (isset($varCallSign[2])) ? $varCallSign[2] : '';
$authKeyValue		= (isset($varAuthKey[2])) ?  $varAuthKey[2] : '';
$beaconValue		= (isset($varBeacon[2])) ?  $varBeacon[2] : '';
$shortIdentValue	= (isset($varShortIdent[2])) ?  $varShortIdent[2] : '';
$longIdentValue		= (isset($varLongIdent[2])) ? $varLongIdent[2] : '';
$codecBitrateValue	= (isset($varCodecBitRate[2])) ? $varCodecBitRate[2] : '';

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

/* Create profile */
if (!empty($frmProfile)) {
	$profile = json_encode($profiles, JSON_PRETTY_PRINT);
	$proFileName = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $frmProfile) . '.json';
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
	unlink($profilesPath . $frmDelProfile);
 	echo 'Profile "'. basename($frmDelProfile, '.json') .'" has been deleted';
 	exit(0);
}

// Compare current stored values vs new values from form
if ($changes > 0) {
	// Stop SVXLink service before attempting anything
	shell_exec('/usr/bin/sudo /usr/bin/systemctl stop rolink.service');
	$newCfg = preg_replace($oldVar, $newVar, $oldCfg);
	sleep(1);
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

echo $msgOut;
