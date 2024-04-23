<?php
/*
 *   RoLinkX Dashboard v3.55
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
 * Wi-Fi management module
 */

include __DIR__ . '/../includes/functions.php';
$wpaFile    = '/etc/wpa_supplicant/wpa_supplicant.conf';
$wpaTemp    = '/tmp/wpa_supplicant.tmp';
$weHaveData = $fail = false;

/* Get POST vars */
$wnA = (isset($_POST['wn1'])) ? filter_input(INPUT_POST, 'wn1', FILTER_SANITIZE_ADD_SLASHES) : '';
$wkA = (isset($_POST['wk1'])) ? filter_input(INPUT_POST, 'wk1', FILTER_SANITIZE_ADD_SLASHES) : '';
$wnB = (isset($_POST['wn2'])) ? filter_input(INPUT_POST, 'wn2', FILTER_SANITIZE_ADD_SLASHES) : '';
$wkB = (isset($_POST['wk2'])) ? filter_input(INPUT_POST, 'wk2', FILTER_SANITIZE_ADD_SLASHES) : '';
$wnC = (isset($_POST['wn3'])) ? filter_input(INPUT_POST, 'wn3', FILTER_SANITIZE_ADD_SLASHES) : '';
$wkC = (isset($_POST['wk3'])) ? filter_input(INPUT_POST, 'wk3', FILTER_SANITIZE_ADD_SLASHES) : '';
$wnD = (isset($_POST['wn4'])) ? filter_input(INPUT_POST, 'wn4', FILTER_SANITIZE_ADD_SLASHES) : '';
$wkD = (isset($_POST['wk4'])) ? filter_input(INPUT_POST, 'wk4', FILTER_SANITIZE_ADD_SLASHES) : '';

function getSSIDs()
{
    global $wpaFile;
    $storedSSID = null;
    $storedPwds = null;
    $wpaBuffer  = file_get_contents($wpaFile);
    // Match both plain text passwords and hashed passphrases
    preg_match_all('/ssid="(.*)"/', $wpaBuffer, $resultSSID);
    preg_match_all('/psk=(".*?"|\S+)/', $wpaBuffer, $resultPWDS);
    if (empty($resultSSID) || empty($resultPWDS)) {
        return false;
    }

    foreach ($resultSSID[1] as $key => $ap) {
        if ($key <= 3) {
            $storedSSID[] = $ap;
        }
    }
    foreach ($resultPWDS[1] as $key => $pw) {
        if ($key <= 3) {
            // Remove double quotes if present
            $storedPwds[] = trim($pw, '"');
        }
    }
    return [$storedSSID, $storedPwds];
}

function wpa_passphrase($ssid, $passphrase)
{
    $bin = hash_pbkdf2('sha1', $passphrase, $ssid, 4096, 32, true);
    return bin2hex($bin);
}

$ssidList = getSSIDs();
$networkA = (empty($ssidList[0][0])) ? '' : $ssidList[0][0];
$networkB = (empty($ssidList[0][1])) ? '' : $ssidList[0][1];
$networkC = (empty($ssidList[0][2])) ? '' : $ssidList[0][2];
$networkD = (empty($ssidList[0][3])) ? '' : $ssidList[0][3];
$authKeyA = (empty($ssidList[1][0])) ? '' : $ssidList[1][0];
$authKeyB = (empty($ssidList[1][1])) ? '' : $ssidList[1][1];
$authKeyC = (empty($ssidList[1][2])) ? '' : $ssidList[1][2];
$authKeyD = (empty($ssidList[1][3])) ? '' : $ssidList[1][3];

/* Check for user input data */
if ($wnA || $wnB || $wnC || $wnD || $wkA || $wkB || $wkC || $wkD) {
    $weHaveData = true;
}

/* Networks */
if ($wnA && $wnA != $networkA) {
    $networkA = $wnA;
}

if ($wnB && $wnB != $networkB) {
    $networkB = $wnB;
}

if ($wnC && $wnC != $networkC) {
    $networkC = $wnC;
}

if ($wnD && $wnD != $networkD) {
    $networkD = $wnD;
}

if ($wkA && $wkA != $authKeyA) {
    $authKeyA = $wkA;
}

if ($wkB && $wkB != $authKeyB) {
    $authKeyB = $wkB;
}

if ($wkC && $wkC != $authKeyC) {
    $authKeyC = $wkC;
}

if ($wkD && $wkD != $authKeyD) {
    $authKeyD = $wkD;
}

/* Delete networks if supplied with '-' character or validate key lenght */
if ($wnA == '-') {
    $networkA = '';
} elseif ($wnA && strlen($wkA) < 8) {
    $fail = true;
}
if ($wnB == '-') {
    $networkB = '';
} elseif ($wnB && strlen($wkB) < 8) {
    $fail = true;
}
if ($wnC == '-') {
    $networkC = '';
} elseif ($wnC && strlen($wkC) < 8) {
    $fail = true;
}
if ($wnD == '-') {
    $networkD = '';
} elseif ($wnD && strlen($wkD) < 8) {
    $fail = true;
}

if ($fail) {
    echo $wkC . 'Invalid network security key lenght!';
    exit(1);
}

/* Update the wpa_supplicant.conf file with new data */
$wpaData = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
ap_scan=1
fast_reauth=1
country=RO' . PHP_EOL;
if (!empty($networkA)) {
    $wpaData .= 'network={
        ssid=' . json_encode($networkA) . '
        psk=' . ((strlen($authKeyA) < 32) ? wpa_passphrase($networkA, $authKeyA) : $authKeyA) . '
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if (!empty($networkB)) {
    $wpaData .= 'network={
        ssid=' . json_encode($networkB) . '
        psk=' . ((strlen($authKeyB) < 32) ? wpa_passphrase($networkB, $authKeyB) : $authKeyB) . '
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if (!empty($networkC)) {
    $wpaData .= 'network={
        ssid=' . json_encode($networkC) . '
        psk=' . ((strlen($authKeyC) < 32) ? wpa_passphrase($networkC, $authKeyC) : $authKeyC) . '
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}

if (!empty($networkD)) {
    $wpaData .= 'network={
        ssid=' . json_encode($networkD) . '
        psk=' . ((strlen($authKeyD) < 32) ? wpa_passphrase($networkD, $authKeyD) : $authKeyD) . '
        key_mgmt=WPA-PSK
        scan_ssid=1
}' . PHP_EOL;
}
if ($weHaveData) {
    toggleFS(true);
    file_put_contents($wpaTemp, $wpaData);
    exec("/usr/bin/sudo /usr/bin/cp $wpaTemp $wpaFile");
    toggleFS(false);
    echo 'New data stored.<br/>Reboot if you want to connect!';
} else {
    echo 'No new data, so nothing changed';
}
