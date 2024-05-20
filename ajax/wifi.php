<?php
/*
 *   RoLinkX Dashboard v3.7
 *   Copyright (C) 2024 by Razvan Marin YO6NAM / www.xpander.ro
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
$maxNetworks = 5;
$weHaveData = false;

/* Get POST vars */
for ($i = 1; $i <= $maxNetworks; $i++) {
    ${"wn$i"} = isset($_POST["wn$i"]) ? filter_input(INPUT_POST, "wn$i", FILTER_SANITIZE_ADD_SLASHES) : '';
    ${"wk$i"} = isset($_POST["wk$i"]) ? filter_input(INPUT_POST, "wk$i", FILTER_SANITIZE_ADD_SLASHES) : '';
}

function wpa_passphrase($ssid, $passphrase)
{
    $bin = hash_pbkdf2('sha1', $passphrase, $ssid, 4096, 32, true);
    return bin2hex($bin);
}

function getSSIDs($wpaFile, $maxNetworks)
{
    $storedSSID = [];
    $storedPwds = [];
    $wpaBuffer = file_get_contents($wpaFile);

    // Match both plain text passwords and hashed passphrases
    preg_match_all('/ssid="(.*)"/', $wpaBuffer, $resultSSID);
    preg_match_all('/psk=(".*?"|\S+)/', $wpaBuffer, $resultPWDS);

    if (empty($resultSSID[1]) || empty($resultPWDS[1])) {
        return false;
    }

    // Store up to 4 SSIDs and passwords
    for ($i = 0; $i < $maxNetworks; $i++) {
        if (isset($resultSSID[1][$i])) {
            $storedSSID[] = $resultSSID[1][$i];
        }
        if (isset($resultPWDS[1][$i])) {
            $storedPwds[] = trim($resultPWDS[1][$i], '"');
        }
    }

    return [$storedSSID, $storedPwds];
}

/* Check for user input data */
for ($i = 1; $i <= 4; $i++) {
    if (${"wn$i"} || ${"wk$i"}) {
        $weHaveData = true;
        break;
    }
}

$ssidList = getSSIDs($wpaFile, $maxNetworks);

$storedNetwork = [];
$storedAuthKey = [];

for ($i = 0; $i < $maxNetworks; $i++) {
    $storedNetwork[$i] = $ssidList ? ($ssidList[0][$i] ?? '') : '';
    $storedAuthKey[$i] = $ssidList ? ($ssidList[1][$i] ?? '') : '';
}

/* Networks and Validation */
for ($i = 1; $i <= $maxNetworks; $i++) {
    ${"network$i"} = $storedNetwork[$i - 1];
    ${"authKey$i"} = $storedAuthKey[$i - 1];

    if (${"wn$i"} == '-') {
         ${"network$i"} = '';
    } elseif (${"wn$i"} && strlen(${"wk$i"}) < 8) {
		echo 'Network <b>'. ${"wn$i"} .'</b> : Invalid network security key lenght!';
		exit(1);
    } elseif (${"wn$i"} && ${"wn$i"} != $storedNetwork[$i - 1]) {
        ${"network$i"} = ${"wn$i"};
    }

    if (${"wk$i"} && ${"wk$i"} != $storedAuthKey[$i - 1]) {
        ${"authKey$i"} = ${"wk$i"};
    }
}

/* Update the wpa_supplicant.conf file with new data */
$wpaData = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
ap_scan=1
fast_reauth=1
country=RO' . PHP_EOL;
for ($i = 1; $i <= $maxNetworks; $i++) {
    if (!empty(${"network$i"})) {
        $psk = (strlen(${"authKey$i"}) < 32) ? wpa_passphrase(${"network$i"}, ${"authKey$i"}) : ${"authKey$i"};
        $wpaData .= 'network={
	ssid=' . json_encode(${"network$i"}) . '
	psk=' . $psk . '
	key_mgmt=WPA-PSK
	scan_ssid=1
}' . PHP_EOL;
    }
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
