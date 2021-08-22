<?php
/*
*   RoLinkX Dashboard v0.1a
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

$cfgFile = '/opt/rolink/conf/rolink.conf';
$newFile = '/tmp/rolink.conf.tmp';
$oldVar = $newVar = array();
$changes = 0;

// Get POST vars
$frmReflector	= (isset($_POST['ref'])) ? filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING) : '';
$frmPort		= (isset($_POST['prt'])) ? filter_input(INPUT_POST, 'prt', FILTER_SANITIZE_STRING) : '';
$frmCallsign	= (isset($_POST['cal'])) ? filter_input(INPUT_POST, 'cal', FILTER_SANITIZE_STRING) : '';
$frmAuthKey		= (isset($_POST['key'])) ? filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING) : '';
$frmBeacon		= (isset($_POST['clb'])) ? filter_input(INPUT_POST, 'clb', FILTER_SANITIZE_STRING) : '';
$frmShortId		= (isset($_POST['sid'])) ? filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING) : '';
$frmLongId		= (isset($_POST['lid'])) ? filter_input(INPUT_POST, 'lid', FILTER_SANITIZE_STRING) : '';

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

$reflectorValue		= (isset($varReflector[2])) ? $varReflector[2] : '';
$portValue			= (isset($varPort[2])) ? $varPort[2] : '';
$callSignValue		= (isset($varCallSign[2])) ? $varCallSign[2] : '';
$authKeyValue		= (isset($varAuthKey[2])) ?  $varAuthKey[2] : '';
$beaconValue		= (isset($varBeacon[2])) ?  $varBeacon[2] : '';
$shortIdentValue	= (isset($varShortIdent[2])) ?  $varShortIdent[2] : '';
$longIdentValue		= (isset($varLongIdent[2])) ? $varLongIdent[2] : '';

$oldVar[0]	= '/(CALLSIGN=)(\w\S+)/';
$newVar[0]	= '${1}' . $frmBeacon;
$changes	= ($beaconValue != $frmBeacon) ? ++$changes : $changes;

$oldVar[1]	= '/(HOST=)(\S+)/';
$newVar[1]	= '${1}' . $frmReflector;
$changes	= ($reflectorValue != $frmReflector) ? ++$changes : $changes;

$oldVar[2]	= '/(PORT=)(\d+)/';
$newVar[2]	= '${1}' . $frmPort;
$changes	= ($portValue != $frmPort) ? ++$changes : $changes;

$oldVar[3]	= '/(CALLSIGN=")(\S+)"/';
$newVar[3]	= '${1}'. $frmCallsign .'"';
$changes	= ($callSignValue != $frmCallsign) ? ++$changes : $changes;

$oldVar[4]	= '/(AUTH_KEY=)"(\S+)"/';
$newVar[4]	= '${1}"'. $frmAuthKey .'"';
$changes	= ($authKeyValue != $frmAuthKey) ? ++$changes : $changes;

$oldVar[5]	= '/(SHORT_IDENT_INTERVAL=)(\d+)/';
$newVar[5]	= '${1}'. $frmShortId;
$changes	= ($shortIdentValue != $frmShortId) ? ++$changes : $changes;

$oldVar[6]	= '/(LONG_IDENT_INTERVAL=)(\d+)/';
$newVar[6]	= '${1}'. $frmLongId;
$changes	= ($longIdentValue != $frmLongId) ? ++$changes : $changes;

// Compare current stored values vs new values from form
if ($changes > 0) {

	// Stop SVXLink service before attempting anything
	shell_exec('/usr/bin/sudo /usr/bin/systemctl stop rolink.service');
	$newCfg = preg_replace($oldVar, $newVar, $oldCfg);
	sleep(1);
	file_put_contents($newFile, $newCfg);
	shell_exec("sudo /usr/bin/cp $newFile /opt/rolink/conf/rolink.conf");
	echo 'Configuration updated ('. $changes .' change(s) applied)<br/>Restarting RoLink service...';

	// All done, start SVXLink service
	shell_exec('/usr/bin/sudo /usr/bin/systemctl start rolink.service');
} else {
	echo 'No new data to process.<br/>Keeping original configuration.';
}
