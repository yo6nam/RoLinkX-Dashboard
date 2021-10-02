<?php
/*
*   RoLinkX Dashboard v0.8
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
* Wi-Fi controll module
*/

$wpaFile = '/etc/wpa_supplicant/wpa_supplicant.conf';
$wpaTemp = '/tmp/wpa_supplicant.tmp';
$weHaveData = false;

/* Get POST vars */
$wnA = (isset($_POST['wn1'])) ? filter_input(INPUT_POST, 'wn1', FILTER_SANITIZE_STRING) : '';
$wkA = (isset($_POST['wk1'])) ? filter_input(INPUT_POST, 'wk1', FILTER_SANITIZE_STRING) : '';
$wnB = (isset($_POST['wn2'])) ? filter_input(INPUT_POST, 'wn2', FILTER_SANITIZE_STRING) : '';
$wkB = (isset($_POST['wk2'])) ? filter_input(INPUT_POST, 'wk2', FILTER_SANITIZE_STRING) : '';
$wnC = (isset($_POST['wn3'])) ? filter_input(INPUT_POST, 'wn3', FILTER_SANITIZE_STRING) : '';
$wkC = (isset($_POST['wk3'])) ? filter_input(INPUT_POST, 'wk3', FILTER_SANITIZE_STRING) : '';

function getSSIDs() {
	global $wpaFile;
	$storedSSID = null;
	$storedPwds = null;
	$wpaBuffer  = file_get_contents($wpaFile);
	preg_match_all('/ssid="(\S+)"/', $wpaBuffer, $resultSSID);
	if (empty($resultSSID)) return false;
	foreach ($resultSSID[1] as $key => $ap) {
		if ($key <= 2) {
  			$storedSSID[] = $ap;
  		}
	}

	preg_match_all('/psk="(\S+)"/', $wpaBuffer, $resultPWDS);
	if (empty($resultPWDS)) return false;
	foreach ($resultPWDS[1] as $key => $pw) {
		if ($key <= 2) {
  			$storedPwds[] = $pw;
  		}
	}
	return array($storedSSID, $storedPwds);
}

$ssidList	= getSSIDs();
$networkA	= (empty($ssidList[0][0])) ? '' : $ssidList[0][0];
$networkB	= (empty($ssidList[0][1])) ? '' : $ssidList[0][1];
$networkC	= (empty($ssidList[0][2])) ? '' : $ssidList[0][2];
$authKeyA	= (empty($ssidList[1][0])) ? '' : $ssidList[1][0];
$authKeyB	= (empty($ssidList[1][1])) ? '' : $ssidList[1][1];
$authKeyC	= (empty($ssidList[1][2])) ? '' : $ssidList[1][2];

/* Check for data */
if ($wnA || $wnB || $wnC || $wkA || $wkB || $wkC) $weHaveData = true;

/* Networks */
if ($wnA && $wnA != $networkA) $networkA = $wnA;
if ($wnB && $wnB != $networkB) $networkB = $wnB;
if ($wnC && $wnC != $networkC) $networkC = $wnC;

if ($wkA && $wkA != $authKeyA) $authKeyA = $wkA;
if ($wkB && $wkB != $authKeyB) $authKeyB = $wkB;
if ($wkC && $wkC != $authKeyC) $authKeyC = $wkC;

/* Delete networks if supplied with '-' character */
if ($wnA == '-') $networkA = ''; $weHaveData = true;
if ($wnB == '-') $networkB = '';
if ($wnC == '-') $networkC = '';

/* Update the wpa_supplicant.conf file with new data */
$wpaData = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
ap_scan=1
fast_reauth=1
country=RO' . PHP_EOL;
if (!empty($networkA)) {
	$wpaData .= 'network={
        ssid='. json_encode($networkA) .'
        psk='. json_encode($authKeyA) .'
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if (!empty($networkB)) {
	$wpaData .= 'network={
        ssid='. json_encode($networkB) .'
        psk='. json_encode($authKeyB) .'
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if (!empty($networkC)) {
	$wpaData .= 'network={
        ssid='. json_encode($networkC) .'
        psk='. json_encode($authKeyC) .'
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if ($weHaveData) {
	file_put_contents($wpaTemp, $wpaData);
	shell_exec("sudo /usr/bin/cp $wpaTemp $wpaFile");
	echo 'New data stored. Restart Wi-Fi now.';
} else {
	echo 'No new data, so nothing changed';
}
