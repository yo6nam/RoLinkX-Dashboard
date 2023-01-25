<?php
/*
*   RoLinkX Dashboard v2.94
*   Copyright (C) 2022 by Razvan Marin YO6NAM / www.xpander.ro
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

include __DIR__ .'/../includes/functions.php';
$cfgFile		= '/opt/rolink/conf/rolink.conf';
$restoreFile	= '/var/www/html/rolink/assets/rolink.conf';
$newFile		= '/tmp/rolink.conf.tmp';
$profilesPath	= dirname(__FILE__) .'/../profiles/';
$newProfile		= false;
$changes		= 0;
$msgOut			= null;
$oldVar = $newVar = $profiles = array();

// Read version installed
if (is_file('/opt/rolink/version')) {
	$localData		= file_get_contents('/opt/rolink/version');
	$localVersion	= explode('|', $localData);
} else {
	echo 'Please update RoLink/SVXLink first!';
	return;
}

// Populate profile from GET vars
$frmLoadProfile	= (isset($_GET['lpn'])) ? filter_input(INPUT_GET, 'lpn', FILTER_SANITIZE_STRING) : '';

// Retrieve POST vars (defaults if empty values to avoid locking the config file)
$frmProfile		= (isset($_POST['prn'])) ? filter_input(INPUT_POST, 'prn', FILTER_SANITIZE_STRING) : '';
$frmReflector	= (empty($_POST['ref'])) ? 'rolink.network' : preg_replace('/^(http(s)?:\/\/)?(www.)?|(\/)/i', '', filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING));
$frmPort		= (empty($_POST['prt'])) ? '1234' : filter_input(INPUT_POST, 'prt', FILTER_SANITIZE_NUMBER_INT);
$frmCallsign	= (empty($_POST['cal'])) ? 'YO1XYZ-P' : preg_replace('/[^\w-]/', '', filter_input(INPUT_POST, 'cal', FILTER_SANITIZE_STRING));
$frmAuthKey		= (empty($_POST['key'])) ? 'password' : trim(filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING));
$frmBeacon		= (empty($_POST['clb'])) ? 'YO1XYZ' : preg_replace('/[^\w-]/', '', filter_input(INPUT_POST, 'clb', FILTER_SANITIZE_STRING));
$frmVoice		= (empty($_POST['vop'])) ? 'en_US' : filter_input(INPUT_POST, 'vop', FILTER_SANITIZE_STRING);
$frmShortId		= (empty($_POST['sid'])) ? '0' : filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING);
$frmLongId		= (empty($_POST['lid'])) ? '0' : filter_input(INPUT_POST, 'lid', FILTER_SANITIZE_STRING);
$frmType		= (empty($_POST['tip'])) ? 'nod portabil' : filter_input(INPUT_POST, 'tip', FILTER_SANITIZE_STRING);
$frmBitrate		= (empty($_POST['cbr'])) ? '20000' : filter_input(INPUT_POST, 'cbr', FILTER_SANITIZE_STRING);
$frmRogerBeep	= (empty($_POST['rgr'])) ? '0' : filter_input(INPUT_POST, 'rgr', FILTER_SANITIZE_NUMBER_INT);
$frmRxGPIO		= (empty($_POST['rxp'])) ? 'gpio10' : filter_input(INPUT_POST, 'rxp', FILTER_SANITIZE_STRING);
$frmTxGPIO		= (empty($_POST['txp'])) ? 'gpio7' : filter_input(INPUT_POST, 'txp', FILTER_SANITIZE_STRING);
$frmMonitorTgs	= (empty($_POST['mtg'])) ? '226++' : filter_input(INPUT_POST, 'mtg', FILTER_SANITIZE_STRING);
$frmTgTimeOut	= (empty($_POST['tgt'])) ? '30' : filter_input(INPUT_POST, 'tgt', FILTER_SANITIZE_NUMBER_INT);
$frmACStatus	= (empty($_POST['acs'])) ? '0' : filter_input(INPUT_POST, 'acs', FILTER_SANITIZE_NUMBER_INT);
$frmDeEmphasis	= (empty($_POST['rxe'])) ? '0' : filter_input(INPUT_POST, 'rxe', FILTER_SANITIZE_NUMBER_INT);
$frmPreEmphasis = (empty($_POST['txe'])) ? '0' : filter_input(INPUT_POST, 'txe', FILTER_SANITIZE_NUMBER_INT);
$frmMasterGain	= (empty($_POST['mag'])) ? '0' : filter_input(INPUT_POST, 'mag', FILTER_SANITIZE_NUMBER_FLOAT);
$frmReconnectS	= (empty($_POST['res'])) ? '0' : filter_input(INPUT_POST, 'res', FILTER_SANITIZE_NUMBER_INT);
$frmTxTimeOut	= (empty($_POST['txt'])) ? '180' : filter_input(INPUT_POST, 'txt', FILTER_SANITIZE_NUMBER_INT);
$frmSqlDelay	= (empty($_POST['sqd'])) ? '500' : filter_input(INPUT_POST, 'sqd', FILTER_SANITIZE_NUMBER_INT);
$frmDelProfile	= (empty($_POST['prd'])) ? '' : filter_input(INPUT_POST, 'prd', FILTER_SANITIZE_STRING);

/* Process DTMF commands */
if (isset($_POST['dtmfCommand'])) {
	$dtmfCommand = (!empty($_POST['dtmfCommand'])) ? filter_input(INPUT_POST, 'dtmfCommand', FILTER_SANITIZE_STRING) : null;
	if (!is_link('/tmp/dtmf')) {
		echo "RoLink is not running!";
		return false;
	}
	if (!empty($dtmfCommand)) {
		exec('/usr/bin/sudo /usr/bin/chmod guo+rw /tmp/dtmf');
		exec("/usr/bin/echo '$dtmfCommand' >/tmp/dtmf", $reply);
		echo "<b>$dtmfCommand</b> executed!";
	}
	exit(0);
}

/* Process restore command */
if (isset($_POST['restore'])) {
	if (!is_file($restoreFile)) {
		echo "Restore data not available!";
		exit(1);
	}
	toggleFS(true);
	file_put_contents('/tmp/rolink.conf.tmp', file_get_contents($restoreFile));
	exec("/usr/bin/sudo /usr/bin/cp /tmp/rolink.conf.tmp /opt/rolink/conf/rolink.conf");
	toggleFS(false);
	serviceControl('rolink.service', 'restart');
	echo "RoLink configuration restored to defaults";
	exit(0);
}

// Get current variables
$oldCfg = file_get_contents($cfgFile);
preg_match('/(CALLSIGN=")(\S+)"/', $oldCfg, $varCallSign);
preg_match('/(HOST=)(\S+)/', $oldCfg, $varReflector);
preg_match('/(^PORT=)(\d+)/m', $oldCfg, $varPort);
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
preg_match('/(SQL_DELAY=)(\d+)/', $oldCfg, $varSqlDelay);
preg_match('/(TIMEOUT=)(\d+)\nTX/', $oldCfg, $varTxTimeout);
// Since 1.7.99.62
preg_match('/(HOSTS=)(\S+)/', $oldCfg, $varRefHosts);
preg_match('/(HOST_PORT=)(\d+)/', $oldCfg, $varPorts);
// Since 1.7.99.68-r2
preg_match('/(ANNOUNCE_CONNECTION_STATUS=)(\d+)/', $oldCfg, $announceConnectionStatus);
// Power Hotspot
preg_match('/(DEEMPHASIS=)(\d+)\n/', $oldCfg, $varDeEmphasis);
preg_match('/(PREEMPHASIS=)(\d+)\n/', $oldCfg, $varPreEmphasis);
preg_match('/(MASTER_GAIN=)(-?\d+)\n/', $oldCfg, $varMasterGain);
preg_match('/(RECONNECT_SECONDS=)(\d+)\n/', $oldCfg, $varReconnectSeconds);

// Safe category values
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
// Since 1.7.99.62
$refHostsValue		= (isset($varRefHosts[2])) ? $varRefHosts[2] : '';
$portsValue			= (isset($varPorts[2])) ? $varPorts[2] : '';

// Advanced category values
$rxGPIOValue		= (isset($varRxGPIO[2])) ? $varRxGPIO[2] : '';
$txGPIOValue		= (isset($varTxGPIO[2])) ? $varTxGPIO[2] : '';
$monitorTgsValue	= (isset($varMonitorTgs[2])) ? $varMonitorTgs[2] : '';
$tgSelectTOValue	= (isset($varTgSelTimeOut[2])) ? $varTgSelTimeOut[2] : '';
$txTimeOutValue		= (isset($varTxTimeout[2])) ? $varTxTimeout[2] : '';
$sqlDelayValue		= (isset($varSqlDelay[2])) ? $varSqlDelay[2] : '';
$acsValue			= (isset($announceConnectionStatus[2])) ? $announceConnectionStatus[2] : null;
$preEmphasisValue	= (isset($varPreEmphasis[2])) ? $varPreEmphasis[2] : 0;
$deEmphasisValue	= (isset($varDeEmphasis[2])) ? $varDeEmphasis[2] : 0;
$masterGainValue	= (isset($varMasterGain[2])) ? $varMasterGain[2] : null;
$reconnectSValue	= (isset($varReconnectSeconds[2])) ? $varReconnectSeconds[2] : null;

/* Profile defaults */
$profiles['reflector']	= $reflectorValue;
$profiles['port']		= $portValue;
$profiles['callsign']	= $callSignValue;
$profiles['key']		= $authKeyValue;
$profiles['beacon']		= $beaconValue;
$profiles['bitrate']	= $codecBitrateValue;
$profiles['type']		= 'nod portabil';
$profiles['shortIdent'] = $shortIdentValue;
$profiles['longIdent']	= $longIdentValue;
$profiles['rogerBeep']	= $rogerBeepValue;
$profiles['connectionStatus'] = $acsValue;

/* Convert config of new installs */
if (preg_match('/svx\.ro/', $oldCfg)) {
	$sCfg = $rCfg = array();
	$sCfg[1] = '/#H/im';
	$sCfg[2] = '/HOST=/im';
	$sCfg[3] = '/^PORT=/im';
	$rCfg[1] = 'H';
	$rCfg[2] = '#HOST=';
	$rCfg[3] = '#PORT=';
	$oldCfg = preg_replace($sCfg, $rCfg, $oldCfg);
}

/* Temporary fix(es) */
$oldCfg = preg_replace('/(\#+)(\w)/', '#${2}', $oldCfg);
if (!isset($acsValue)) {
	$oldCfg = preg_replace('/(ANNOUNCE_REMOTE_MIN_INTERVAL=)(\d+)/', '${1}${2}'."\nANNOUNCE_CONNECTION_STATUS=0", $oldCfg);
}
if (!isset($masterGainValue)) {
	$oldCfg = preg_replace('/(PREEMPHASIS=)(\d+)/', '${1}${2}'."\nMASTER_GAIN=0", $oldCfg);
}
if (!isset($reconnectSValue)) {
	$oldCfg = preg_replace('/(MUTE_FIRST_TX_REM=)(\d+)/', '${1}${2}'."\nRECONNECT_SECONDS=0", $oldCfg);
}

/* Process new values */
$oldVar[0]	= '/(CALLSIGN=)(\w\S+)/';
$newVar[0]	= '${1}'. $frmBeacon;
if ($beaconValue != $frmBeacon) {
	++$changes;
	$profiles['beacon'] = $frmBeacon;
}

$oldVar[1]	= '/(HOST=)(\S+)/';
$newVar[1]	= '${1}'. $frmReflector;

if ($localVersion[1] > '1.7.99.62' && empty($varRefHosts)) {
	// Upgrade config file to new version
	$newVar[1]	= '#${1}'. $frmReflector . PHP_EOL .'HOSTS='. $frmReflector .':'. $frmPort;
}
if ($reflectorValue != $frmReflector) {
	++$changes;
	$profiles['reflector'] = $frmReflector;
}

$oldVar[2]	= '/(PORT=)(\d+)/';
$newVar[2]	= '${1}'. $frmPort;
if ($localVersion[1] > '1.7.99.62' && empty($varPorts)) {
	// Upgrade config file to new version
	$newVar[2]	= '#${1}'. $frmPort . PHP_EOL .'HOST_PORT='. $frmPort;
}
if ($portValue != $frmPort) {
	if ($portsValue != $frmPort) ++$changes;
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
	$profiles['shortIdent'] = $frmShortId;
}

$oldVar[6]	= '/(LONG_IDENT_INTERVAL=)(\d+)/';
$newVar[6]	= '${1}'. $frmLongId;
if ($longIdentValue != $frmLongId) {
	++$changes;
	$profiles['longIdent'] = $frmLongId;
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
	$profiles['rogerBeep'] = $frmRogerBeep;
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

$oldVar[14]	= '/(SQL_DELAY=)(\d+)/';
$newVar[14]	= '${1}'. $frmSqlDelay;
if ($sqlDelayValue != $frmSqlDelay) {
	++$changes;
}

$oldVar[15]	= '/(TIMEOUT=)(\d+)\nTX/';
$newVar[15]	= '${1}'. $frmTxTimeOut . PHP_EOL .'TX';
if ($txTimeOutValue != $frmTxTimeOut) {
	++$changes;
}

$oldVar[16]	= '/(HOSTS=)(\S+)/';
$newVar[16]	= '${1}'. $frmReflector .':'. $frmPort;

$oldVar[17]	= '/(HOST_PORT=)(\d+)/';
$newVar[17]	= '${1}'. $frmPort;

$oldVar[18]	= '/(ANNOUNCE_CONNECTION_STATUS=)(\d+)/';
$newVar[18]	= '${1}'. $frmACStatus;
if ($acsValue != (int)$frmACStatus) {
	++$changes;
	$profiles['connectionStatus'] = $frmACStatus;
}

$oldVar[19]	= '/(DEEMPHASIS=)(\d+)/';
$newVar[19]	= '${1}'. $frmDeEmphasis;
if ($deEmphasisValue != (int)$frmDeEmphasis) {
	++$changes;
}

$oldVar[20]	= '/(PREEMPHASIS=)(\d+)/';
$newVar[20]	= '${1}'. $frmPreEmphasis;
if ($preEmphasisValue != (int)$frmPreEmphasis) {
	++$changes;
}

$oldVar[21]	= '/(MASTER_GAIN=)(-?\d+)/';
$newVar[21]	= '${1}'. $frmMasterGain;
if ($masterGainValue != (int)$frmMasterGain) {
	++$changes;
}

$oldVar[22]	= '/(RECONNECT_SECONDS=)(\d+)/';
$newVar[22]	= '${1}'. $frmReconnectS;
if ($reconnectSValue != (int)$frmReconnectS) {
	++$changes;
}

/* Configuration info sent to reflector ('type' only) */
if ($cfgRefData['tip'] != $frmType) {
	++$changes;
	$profiles['type'] = $frmType;
}

/* Create profile */
if (!empty($frmProfile)) {
	$profile = json_encode($profiles, JSON_PRETTY_PRINT);
	$proFileName = preg_replace('/[^a-zA-Z0-9\-\.]/', '_', $frmProfile) .'.json';
	toggleFS(true);
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
	toggleFS(true);
	unlink($profilesPath . $frmDelProfile);
 	echo 'Profile "'. basename($frmDelProfile, '.json') .'" has been deleted';
 	toggleFS(false);
 	exit(0);
}

// Compare current stored values vs new values from form
if ($changes > 0) {
	// Stop SVXLink service before attempting anything
	serviceControl('rolink.service', 'stop');
	$newCfg = preg_replace($oldVar, $newVar, $oldCfg);
	toggleFS(true);
	file_put_contents($newFile, $newCfg);
	exec("/usr/bin/sudo /usr/bin/cp $newFile /opt/rolink/conf/rolink.conf");
	// Update json file if decription/type changed
	if ($cfgRefData['tip'] != $frmType) {
		$cfgRefData['tip'] = $frmType;
		$nfoParams = json_encode($cfgRefData, JSON_PRETTY_PRINT);
		file_put_contents($tmpRefFile, $nfoParams);
		exec("/usr/bin/sudo /usr/bin/cp $tmpRefFile $cfgRefFile");
	}
	// Update json file with signature of using RoLinkX Dashboard
	if (!isset($cfgRefData['isx']) || $cfgRefData['isx'] == 1) {
		$cfgRefData['isx'] = 2;
		$nfoParams = json_encode($cfgRefData, JSON_PRETTY_PRINT);
		file_put_contents($tmpRefFile, $nfoParams);
		exec("/usr/bin/sudo /usr/bin/cp $tmpRefFile $cfgRefFile");
	}
	$msgOut .= 'Configuration updated ('. $changes .' change(s) applied)<br/>Restarting RoLink service...';
	$msgOut .= ($newProfile) ? '<br/>Profile saved as '. basename($proFileName, '.json') : '';

	// All done, start SVXLink service
	serviceControl('rolink.service', 'start');
} else {
	$msgOut .= 'No new data to process.<br/>Keeping original configuration.';
	$msgOut .= ($newProfile) ? '<br/>Profile saved as '. basename($proFileName, '.json') : '';
}
toggleFS(false);
echo $msgOut;
