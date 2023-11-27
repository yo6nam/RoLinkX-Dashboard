<?php
/*
*   RoLinkX Dashboard v3.62
*   Copyright (C) 2023 by Razvan Marin YO6NAM / www.xpander.ro
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
* Forms module
* Note : Some code borrowed from
* https://github.com/RaspAP/raspap-webgui
* https://gist.github.com/magicbug/bf27fc2c9908eb114b4a
*/

if (isset($_GET['scan'])) echo scanWifi(1);
if (isset($_GET['gpsStatus'])) echo aprsForm(1);

/* Wi-Fi form */
function getSSIDs() {
	$storedSSID = null;
	$storedPwds = null;
	$wpaBuffer = file_get_contents('/etc/wpa_supplicant/wpa_supplicant.conf');
    // Match both plain text passwords and hashed passphrases
    preg_match_all('/ssid="(.*)"/', $wpaBuffer, $resultSSID);
    preg_match_all('/psk=(".*?"|\S+)/', $wpaBuffer, $resultPWDS);
    if (empty($resultSSID) || empty($resultPWDS)) return false;
    foreach ($resultSSID[1] as $key => $ap) {
        if ($key <= 3) {
            $storedSSID[] = $ap;
        }
    }
    foreach ($resultPWDS[1] as $key => $pw) {
        if ($key <= 3) {
            $storedPwds[] = trim($pw, '"');
        }
    }
    return [$storedSSID, $storedPwds];
}

function scanWifi($ext = 0) {
	$apList = null;
	exec('/usr/bin/sudo wpa_cli -i wlan0 scan');
	exec('/usr/bin/sudo wpa_cli -i wlan0 scan_results', $reply);
	if (empty($reply)) return;
	array_shift($reply);

	foreach ($reply as $network) {
		$arrNetwork = preg_split("/[\t]+/", $network);
		if (!isset($arrNetwork[4])) continue;
		$ssid = trim($arrNetwork[4]);
		if (empty($ssid) || preg_match('[\x00-\x1f\x7f\'\`\´\"]', $ssid)) {
			continue;
		}
		$networks[$ssid]['ssid'] = $ssid;
		$networks[$ssid] = array(
			'rssi' => $arrNetwork[2],
			'protocol' => authType($arrNetwork[3]),
			'channel' => freqToChan($arrNetwork[1])
		);
	}

	if (!empty($networks)) {
		$cnt = 1;
		if ($ext != 1) {
			$apList = '<div class="accordion mb-3" id="wifiNetworks">
	<div class="accordion-item">
	 <h3 class="accordion-header" id="heading">
		<button class="bg-info text-white accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#availableNetworks" aria-expanded="false" aria-controls="availableNetworks">Available Networks (click to open)</button>
	 </h3>
	 <div id="availableNetworks" class="accordion-collapse collapse" aria-labelledby="heading" data-bs-parent="#wifiNetworks">
		<div id="updateList" class="accordion-body">';
		 }
			$apList .= '<table class="table table-sm"><thead><tr>
			<th scope="col">#</th>
			<th scope="col">SSID</th>
			<th scope="col">RSSI</th>
			<th scope="col">Auth</th>
			<th scope="col">Ch.</th>
			</tr></thead>
			<tbody>';

		foreach ($networks as $name => $data) {
			if ($data['rssi'] >= -80) {
				$lvlQuality = 'class="alert-success"';
			} elseif ($data['rssi'] >= -90) {
				$lvlQuality = 'class="alert-warning"';
			} else {
				$lvlQuality = 'class="alert-light"';
			}

			$apList .= '<tr '. $lvlQuality .'><th scope="row">'. $cnt .'</th>
						<td>'. $name .'</td>
						<td>'. $data['rssi'] .'</td>
						<td>'. $data['protocol'] .'</td>
						<td>'. $data['channel'] .'</td>
						</tr>';
			++$cnt;
		}
		$apList .= '</tbody></table>';
		if ($ext != 1) {
			$apList .= '</div></div></div></div>';
		}
	}
	return $apList;
}

function authType($type) {
	 $options = array();
	 preg_match_all('/\[([^\]]+)\]/s', $type, $matches);

	 foreach ($matches[1] as $match) {
		  if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
				$protocol = $protocol_match[1];
				$matchArr = explode('-', $match);
				$options[] = htmlspecialchars($protocol, ENT_QUOTES);
		  }
	 }

	 if (count($options) === 0) {
		  return 'Open';
	 } else {
		  return implode(' / ', $options);
	 }
}

function freqToChan($freq) {
	 if ($freq >= 2412 && $freq <= 2484) {
		  $channel = ($freq - 2407)/5;
	 } elseif ($freq >= 4915 && $freq <= 4980) {
		  $channel = ($freq - 4910)/5 + 182;
	 } elseif ($freq >= 5035 && $freq <= 5865) {
		  $channel = ($freq - 5030)/5 + 6;
	 } else {
		  $channel = -1;
	 }
	 if ($channel >= 1 && $channel <= 196) {
		  return $channel;
	 } else {
		  return 'Invalid Channel';
	 }
}

function wifiForm() {
	$ssidList	= getSSIDs();
	$apsList	= scanWifi();
	exec('/sbin/iwgetid --raw', $con);
	$wifiForm = '<h4 class="mt-2 alert alert-info fw-bold">Wi-Fi configuration</h4>';
	$wifiForm .= '<div id="wifiScanner">'. $apsList .'</div>';
	$wifiForm .= '<div class="card">
		<div class="card-header">Add / Edit networks</div>
		<div class="card-body">'. PHP_EOL;
	for ($i = 0; $i < 4; $i++) {
		$connected = (isset($con[0])) ? $con[0] : null;
		$active = (isset($ssidList[0][$i]) && $connected === $ssidList[0][$i]) ? true : false;
		$networkName = (empty($ssidList[0][$i])) ? 'empty' : $ssidList[0][$i] .' (saved)';
		$networkKey = (empty($ssidList[1][$i])) ? 'empty' : '********';
		$count = ($i + 1);
		$background = ($active) ? ' bg-success text-white' : null;
		$status = ($active) ? ' (connected)' : null;
		$wifiForm .= '<h4 class="d-flex justify-content-center badge badge-light fs-6'. $background .'"><i class="icon-wifi">&nbsp;</i>Network '. $count . $status .'</h4><div class="input-group input-group-sm mb-2">
		  <span class="input-group-text" style="width: 7rem;">Name (SSID)</span>
		  <input id="wlan_network_'. $count .'" type="text" class="form-control" placeholder="'. $networkName .'" aria-label="Network Name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-4">
		  <span class="input-group-text" style="width: 7rem;">Key (Password)</span>
		  <input id="wlan_authkey_'. $count .'" type="text" class="form-control" placeholder="'. $networkKey .'" aria-label="Network key" aria-describedby="inputGroup-sizing-sm">
		</div>'. PHP_EOL;
	}
	$wifiForm .= '<div class="row justify-content-center m-1">
			<div class="col-auto alert alert-info m-2 p-1" role="alert">To delete a network use the - (dash) character as SSID</div>
			<div class="col-auto alert alert-warning m-2 p-1" role="alert">Note : Open networks (no key) are not supported</div>
		</div>
		<div class="d-flex justify-content-center mt-2">
			<button id="savewifi" class="m-2 btn btn-danger btn-lg">Save</button>
			<button id="rewifi" class="m-2 btn btn-info btn-lg">Restart Wi-Fi</button>
		</div>
		</div>
	</div>'. PHP_EOL;
	$wifiForm .= '<script>
	var auto_refresh = setInterval( function () {
		$("#updateList").load("includes/forms.php?scan");
	}, 6000);
	</script>'. PHP_EOL;
	return $wifiForm;
}

/* SVXLink form */
function svxForm() {
	$env = checkEnvironment();
	if ($env) return $env;
	global $cfgFile, $config, $pinsArray, $cfgRefFile;
	$svxPinsArray = array();

	/* Convert pins to both states (normal/inverted) */
	foreach ($pinsArray as $pin) {
		$svxPinsArray[] = 'gpio'. $pin;
		$svxPinsArray[] = '!gpio'. $pin;
	}
	$profileOption = null;
	$voicesPath = '/opt/rolink/share/sounds';

	/* Get current variables */
	$cfgFileData = file_get_contents($cfgFile);
	/* Host / Reflector */
	preg_match('/(HOST=)(\S+)/', $cfgFileData, $varReflector);
	$reflectorValue = (isset($varReflector[2])) ? 'value='. $varReflector[2] : '';
	/* Port */
	preg_match('/(PORT=)(\d+)/', $cfgFileData, $varPort);
	$portValue = (isset($varPort[2])) ? 'value='. $varPort[2] : '';
	/* Callsign for authentification */
	preg_match('/(CALLSIGN=")(\S+)"/', $cfgFileData, $varCallSign);
	$callSignValue = (isset($varCallSign[2])) ? 'value='. $varCallSign[2] : '';
	/* Key for authentification */
	preg_match('/(AUTH_KEY=)"(\S+)"/', $cfgFileData, $varAuthKey);
	$authKeyValue = (isset($varAuthKey[2])) ? 'value='. $varAuthKey[2] : '';
	/* Callsign for beacons */
	preg_match('/(CALLSIGN=)(\w\S+)/', $cfgFileData, $varBeacon);
	$beaconValue = (isset($varBeacon[2])) ? 'value='. $varBeacon[2] : '';
	/* RX GPIO */
	preg_match('/(GPIO_SQL_PIN=)(\S+)/', $cfgFileData, $varRxGPIO);
	$rxGPIOValue = (isset($varRxGPIO[2])) ? $varRxGPIO[2] : '';
	/* TX GPIO */
	preg_match('/(PTT_PIN=)(\S+)/', $cfgFileData, $varTxGPIO);
	$txGPIOValue = (isset($varTxGPIO[2])) ? $varTxGPIO[2] : '';
	/* Roger beep */
	preg_match('/(RGR_SOUND_ALWAYS=)(\d+)/', $cfgFileData, $varRogerBeep);
	$rogerBeepValue = (isset($varRogerBeep[2])) ? $varRogerBeep[2] : '';
	/* Squelch delay */
	preg_match('/(SQL_DELAY=)(\d+)/', $cfgFileData, $varSquelchDelay);
	$sqlDelayValue = (isset($varSquelchDelay[2])) ? 'value='. $varSquelchDelay[2] : '';
	/* Default TG */
	preg_match('/(DEFAULT_TG=)(\d+)/', $cfgFileData, $varDefaultTg);
	$defaultTgValue = (isset($varDefaultTg[2])) ? 'value='. $varDefaultTg[2] : '';
	/* Monitor TGs*/
	preg_match('/(MONITOR_TGS=)(.+)/', $cfgFileData, $varMonitorTgs);
	$monitorTgsValue = (isset($varMonitorTgs[2])) ? 'value='. $varMonitorTgs[2] : '';
	/* TG Select Timeout */
	preg_match('/(TG_SELECT_TIMEOUT=)(\d+)/', $cfgFileData, $varTgSelTimeOut);
	$tgSelTimeOutValue	= (isset($varTgSelTimeOut[2])) ? 'value='. $varTgSelTimeOut[2] : '';
	/* Announce connection status interval */
	preg_match('/(ANNOUNCE_CONNECTION_STATUS=)(\d+)/', $cfgFileData, $varAnnounceConnectionStatus);
	$announceConnectionStatusValue	= (isset($varAnnounceConnectionStatus[2])) ? 'value='. $varAnnounceConnectionStatus[2] : '';
	/* Opus codec bitrate */
	preg_match('/(OPUS_ENC_BITRATE=)(\d+)/', $cfgFileData, $varCodecBitRate);
	$bitrateValue = (isset($varCodecBitRate[2])) ? 'value='. $varCodecBitRate[2] : '';
	/* Voice Language */
	preg_match('/(DEFAULT_LANG=)(\S+)/', $cfgFileData, $varVoicePack);
	/* Short / Long Intervals */
	preg_match('/(SHORT_IDENT_INTERVAL=)(\d+)/', $cfgFileData, $varShortIdent);
	preg_match('/(LONG_IDENT_INTERVAL=)(\d+)/', $cfgFileData, $varLongIdent);
	/* TimeOut Timer (TX) */
	preg_match('/(TIMEOUT=)(\d+)\nTX/', $cfgFileData, $varTxTimeout);
	$txTimeOutValue	= (isset($varTxTimeout[2])) ? 'value='. $varTxTimeout[2] : '';
	/* DeEmphasis (RX) */
	preg_match('/(DEEMPHASIS=)(\d+)\n/', $cfgFileData, $varDeEmphasis);
	$deEmphasisValue	= (isset($varDeEmphasis[2])) ? $varDeEmphasis[2] : '';
	/* PreEmphasis (TX) */
	preg_match('/(PREEMPHASIS=)(\d+)\n/', $cfgFileData, $varPreEmphasis);
	$preEmphasisValue	= (isset($varPreEmphasis[2])) ? $varPreEmphasis[2] : '';
	/* MasterGain (TX) */
	preg_match('/(MASTER_GAIN=)(-?\d+(\.\d{1,2})?)\n/', $cfgFileData, $varMasterGain);
	$masterGainValue	= (isset($varMasterGain[2])) ? $varMasterGain[2] : '';
	/* Reconnect seconds */
	preg_match('/(RECONNECT_SECONDS=)(\d+)/', $cfgFileData, $varReconnectSeconds);
	$reconnectSecondsValue	= (isset($varReconnectSeconds[2])) ? 'value='. $varReconnectSeconds[2] : '';
	/* Limiter */
	preg_match('/(LIMITER_THRESH=)(-?\d+)\n/', $cfgFileData, $varLimiter);
	$limiterValue	= (isset($varLimiter[2])) ? $varLimiter[2] : '';
	/* Fan control */
	preg_match('/(FAN_START=)(\d+)/', $cfgFileData, $varFanStart);
	$fanStartValue	= (isset($varFanStart[2])) ? 'value='. $varFanStart[2] : '';
	/* Modules */
	preg_match('/(#?)(MODULES=)(\S+)/', $cfgFileData, $varModules);
	$modulesValue	= (isset($varModules[1])) ? $varModules[1] : '';
	/* Tx Delay */
	preg_match('/(TX_DELAY=)(\d+)/', $cfgFileData, $varTxDelay);
	$txDelayValue	= (isset($varTxDelay[2])) ? 'value='. $varTxDelay[2] : '';

	/* Profiles section */
	$profilesPath	= dirname(__FILE__) .'/../profiles/';
	$proFiles		= array_slice(scandir($profilesPath), 2);
	$skip			= array('sa818pgm.log', 'index.html');

	/* Configuration info sent to reflector ('tip' only) */
	$cfgRefFile = file_get_contents($cfgRefFile);
	$cfgRefData = json_decode($cfgRefFile, true);

	if (!empty($proFiles)) {
		$profileOption	= '<div class="input-group input-group-sm mb-3">
			  <label class="input-group-text bg-info text-white" for="svx_spn" style="width: 8rem;">Select profile</label>
			  <select id="svx_spn" class="form-select">
				<option value="" selected disabled>Select a profile</option>' . PHP_EOL;
		foreach ($proFiles as $profile) {
			if (in_array($profile, $skip)) continue;
			$profileOption .= '<option value="'. $profile .'">'. basename($profile, '.json') .'</option>' . PHP_EOL;
		}
		$profileOption .= '</select>
		<button id="delsvxprofile" class="btn btn-outline-danger" type="button">Delete</button>
		</div>
		<div class="separator">General</div>';
	}

	$svxForm = '<h4 class="mt-2 alert alert-warning fw-bold">SVXLink configuration</h4>';
	$svxForm .= $profileOption;
	$svxForm .= '<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text bg-info text-white" style="width: 8rem;">Create new profile</span>
		  <input id="svx_prn" type="text" class="form-control" placeholder="Name your profile" aria-label="Profile name" aria-describedby="inputGroup-sizing-sm">
		</div>';
	$svxForm .= '<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Reflector (IP/DNS)</span>
		  <input id="svx_ref" type="text" class="form-control" placeholder="rolink.network" aria-label="Server address" aria-describedby="inputGroup-sizing-sm" '. $reflectorValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Port</span>
		  <input id="svx_prt" type="text" class="form-control" placeholder="5301" aria-label="Port" aria-describedby="inputGroup-sizing-sm" '. $portValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Callsign</span>
		  <input id="svx_cal" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Callsign" aria-describedby="inputGroup-sizing-sm" '. $callSignValue .'>
		</div>
		<div id="auth_key" class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Auth Key</span>
		  <input id="svx_key" type="password" class="form-control" placeholder="nod_portabil" aria-label="Auth Key" aria-describedby="inputGroup-sizing-sm" '. $authKeyValue .'>
		  <button id="show_hide" class="input-group-text" role="button"><i class="icon-visibility" aria-hidden="true"></i></button>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Callsign (beacon)</span>
		  <input id="svx_clb" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Callsign" aria-describedby="inputGroup-sizing-sm" '. $beaconValue .'>
		</div>';
	$svxForm .= '<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Roger Beep</span>
		  <select id="svx_rgr" class="form-select">
			<option value="0"'. (($rogerBeepValue == 0) ? ' selected' : '') .'>No</option>
			<option value="1"'. (($rogerBeepValue == 1) ? ' selected' : '') .'>Yes</option>
		  </select>
		</div>';
		/* Voice language detection/selection */
		$svxForm .= '<div class="input-group input-group-sm mb-1">
		<span class="input-group-text" style="width: 8rem;">Voice pack</span>'. PHP_EOL;
		if (is_dir($voicesPath)) {
			$svxForm .= '<select id="svx_vop" class="form-select">'. PHP_EOL;
			foreach(glob($voicesPath .'/*' , GLOB_ONLYDIR) as $voiceDir) {
    			$availableVoicePacks = str_replace($voicesPath .'/', '', $voiceDir);
    			$vsel = ($availableVoicePacks == $varVoicePack[2]) ? ' selected' : null;
    			$svxForm .= '<option value="'. $availableVoicePacks .'"'. $vsel .'>'. $availableVoicePacks .'</option>'. PHP_EOL;
			}
			$svxForm .= '</select>'. PHP_EOL;
			$svxForm .= '<button type="button" id="getVoices" class="btn btn-light btn-lg btn-block">&#128260;</button>'. PHP_EOL;
		} else {
			$svxForm .= '<button type="button" id="getVoices" class="btn btn-primary btn-lg btn-block">Download &amp; install voices</button>'. PHP_EOL;
		}
		$svxForm .= '</div>'. PHP_EOL;
		$svxForm .= '
		<div class="input-group input-group-sm mb-1">
		  <label class="input-group-text" for="svx_sid" style="width: 8rem;">Short Ident</label>
		  <select id="svx_sid" class="form-select">
			 <option value="0">Disabled</option>'. PHP_EOL;
		/* Generate 5 minutes intervals up to 60 & identify stored value on file */
		for ($sid=5; $sid<=120; $sid+=5) {
			$sel = ($sid == $varShortIdent[2]) ? ' selected' : null;
			$svxForm .= '<option value="'. $sid .'"'. $sel .'>'. $sid .' minutes</option>'. PHP_EOL;
		}
	$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <label class="input-group-text" for="svx_lid" style="width: 8rem;">Long Ident</label>
		  <select id="svx_lid" class="form-select">
			 <option value="0">Disabled</option>'. PHP_EOL;
		/* Generate 5 minutes intervals up to 60 & identify stored value on file */
		for ($lid=5; $lid<=300; $lid+=5) {
			$sel = ($lid == $varLongIdent[2]) ? ' selected' : null;
			$svxForm .= '<option value="'. $lid .'"'. $sel .'>'. $lid .' minutes</option>'. PHP_EOL;
		}
		$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Type</span>
		  <input id="svx_tip" type="text" class="form-control" placeholder="nod portabil" aria-label="Description" aria-describedby="inputGroup-sizing-sm" value="'. $cfgRefData['tip'] .'">
		</div>
		<div class="separator">Advanced</div>';
		$svxForm .= '<div class="input-group input-group-sm mb-1">
			<label class="input-group-text" for="svx_rxp" style="width: 8rem;">RX GPIO pin</label>
			<select id="svx_rxp" class="form-select">'. PHP_EOL;
		foreach ($svxPinsArray as $rxpin) {
			$inverted = (strpos($rxpin, '!') !== false) ? ' (inverted)' : null;
			$defaultRxPin = ($rxpin == 'gpio10') ? ' (default)' : null;
			$svxForm .= '<option value="'. $rxpin .'"'. ($rxpin == $rxGPIOValue ? ' selected' : '') .'>'. (int) filter_var($rxpin, FILTER_SANITIZE_NUMBER_INT) . $defaultRxPin . $inverted .'</option>'. PHP_EOL;
		}
		$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<label class="input-group-text" for="svx_txp" style="width: 8rem;">TX GPIO pin</label>
			<select id="svx_txp" class="form-select">'. PHP_EOL;
		foreach ($svxPinsArray as $txpin) {
			$inverted = (strpos($txpin, '!') !== false) ? ' (inverted)' : null;
			$defaultTxPin = ($txpin == 'gpio7') ? ' (default)' : null;
			$svxForm .= '<option value="'. $txpin .'"'. ($txpin == $txGPIOValue ? ' selected' : '') .'>'. (int) filter_var($txpin, FILTER_SANITIZE_NUMBER_INT) . $defaultTxPin . $inverted .'</option>'. PHP_EOL;
		}
		$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Modules</span>
		  <select id="svx_mod" class="form-select">
			<option value="0"'. (($modulesValue == '#') ? ' selected' : '') .'>No</option>
			<option value="1"'. ((empty($modulesValue)) ? ' selected' : '') .'>Yes</option>
		  </select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Squelch delay</span>
		  <input id="svx_sqd" type="text" class="form-control" placeholder="500" aria-label="Squelch delay" aria-describedby="inputGroup-sizing-sm" '. $sqlDelayValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">TX delay</span>
		  <input id="svx_txd" type="text" class="form-control" placeholder="875" aria-label="TX delay" aria-describedby="inputGroup-sizing-sm" '. $txDelayValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Default TG</span>
		  <input id="svx_dtg" type="text" class="form-control" placeholder="226" aria-label="Default TG" aria-describedby="inputGroup-sizing-sm" '. $defaultTgValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Monitor TGs</span>
		  <input id="svx_mtg" type="text" class="form-control" placeholder="226++" aria-label="Monitor TGs" aria-describedby="inputGroup-sizing-sm" '. $monitorTgsValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">TG Sel Timeout</span>
		  <input id="svx_tgt" type="text" class="form-control" placeholder="30" aria-label="TG Timeout" aria-describedby="inputGroup-sizing-sm" '. $tgSelTimeOutValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Reconnect seconds</span>
		  <input id="svx_res" type="text" class="form-control" placeholder="0" aria-label="Reconnect seconds" aria-describedby="inputGroup-sizing-sm" '. $reconnectSecondsValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Connection Status</span>
		  <input id="svx_acs" type="text" class="form-control" placeholder="0" aria-label="Connection Status" aria-describedby="inputGroup-sizing-sm" '. $announceConnectionStatusValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">TX Timeout</span>
		  <input id="svx_txt" type="text" class="form-control" placeholder="180" aria-label="TX Timeout" aria-describedby="inputGroup-sizing-sm" '. $txTimeOutValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">De-Emphasis (RX)</span>
		  <select id="svx_rxe" class="form-select">
			<option value="0"'. (($deEmphasisValue == 0) ? ' selected' : '') .'>No</option>
			<option value="1"'. (($deEmphasisValue == 1) ? ' selected' : '') .'>Yes</option>
		  </select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Pre-Emphasis (TX)</span>
		  <select id="svx_txe" class="form-select">
			<option value="0"'. (($preEmphasisValue == 0) ? ' selected' : '') .'>No</option>
			<option value="1"'. (($preEmphasisValue == 1) ? ' selected' : '') .'>Yes</option>
		  </select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<label class="input-group-text" for="svx_mag" style="width: 8rem;">Master Gain (TX)</label>
			<select id="svx_mag" class="form-select">'. PHP_EOL;
		for($gain=6; $gain>=-6; $gain-=.25){
			$svxForm .= '<option value="'. $gain .'"'. ($gain == $masterGainValue ? ' selected' : '') .'>'. (($gain > 0) ? '+'. $gain : $gain) .' dB</option>'. PHP_EOL;
		}
		$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Audio Compressor</span>
		  <select id="svx_lim" class="form-select">
			<option value="-6"'. (($limiterValue != 0) ? ' selected' : '') .'>Normal</option>
			<option value="0"'. (($limiterValue == 0) ? ' selected' : '') .'>Enhanced</option>
		  </select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <label class="input-group-text" for="svx_cbr" style="width: 8rem;">Codec Bitrate</label>
		  <select id="svx_cbr" class="form-select">'. PHP_EOL;
		if (isset($varCodecBitRate[2])) {
			/* Generate codec bitrates */
			for ($cbr=8000; $cbr<=32000; $cbr+=2000) {
				$sel = ($cbr == $varCodecBitRate[2]) ? ' selected' : null;
				$cbrSuffix = ($cbr == 20000) ? '(default)' : null;
				$svxForm .= '<option value="'. $cbr .'"'. $sel .'>'. $cbr / 1000 .' kb/s '. $cbrSuffix .'</option>'. PHP_EOL;
			}
		} else {
			$svxForm .= '<option value="" disabled selected>Unavailable</option>'. PHP_EOL;
		}
	$svxForm .= '
		  </select>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Fan Start</span>
		  <input id="svx_fan" type="text" class="form-control" placeholder="180" aria-label="Fan Start after" aria-describedby="inputGroup-sizing-sm" '. $fanStartValue .'>
		</div>
		<input type="hidden" id="autoConnect" name="autoConnect" value="'. $config['cfgAutoConnect'] .'" />';
		$svxForm .= '
		<div class="d-flex justify-content-center mt-4">
			<button id="savesvxcfg" type="submit" class="btn btn-danger btn-lg m-2">Save</button>
			<button id="restore" type="submit" class="btn btn-info btn-lg m-2">Restore defaults</button>
			</div>'. PHP_EOL;
	return $svxForm;
}

/* SA818 radio */
function sa818Form() {
	$env = checkEnvironment();
	if ($env) return $env;
	global $cfgFile, $config;
	$historyFile = dirname(__FILE__) .'/../profiles/sa818pgm.log';
	// Last programmed details
	$lastPgmData = array(
		"date" => null,
		"frequency" => null,
		"deviation" => null,
		"ctcss" => null,
		"squelch" => null,
		"volume" => null,
		"filter" => null
		);
	if (is_file($historyFile)) {
		$lastPgmData = json_decode(file_get_contents($historyFile), true);
	}
	$ctcssVars = [
		"0" => "None", "1" => "67.0", "2" => "71.9", "3" => "74.4", "4" => "77.0", "5" => "79.7",
		"6" => "82.5", "7" => "85.4", "8" => "88.5", "9" => "91.5", "10" => "94.8",
		"11" => "97.4", "12" => "100.0", "13" => "103.5", "14" => "107.2",
		"15" => "110.9", "16" => "114.8", "17" => "118.8", "18" => "123",
		"19" => "127.3", "20" => "131.8", "21" => "136.5", "22" => "141.3",
		"23" => "146.2", "24" => "151.4", "25" => "156.7", "26" => "162.2",
		"27" => "167.9", "28" => "173.8", "29" => "179.9", "30" => "186.2",
		"31" => "192.8", "32" => "203.5", "33" => "210.7", "34" => "218.1",
		"35" => "225.7", "36" => "233.6", "37" => "241.8", "38" => "250.3"
		];
	$filterOptions = [
		'' => 'No change',
		'0,0,0' => 'Disable All',
		'1,0,0' => 'Enable Pre/De-Emphasis',
		'0,1,0' => 'Enable High Pass',
		'0,0,1' => 'Enable Low Pass',
		'0,1,1' => 'Enable Low Pass & High Pass',
		'1,1,0' => 'Enable Pre/De-Emphasis & High Pass',
		'1,0,1' => 'Enable Pre/De-Emphasis & Low Pass',
		'1,1,1' => 'Enable All',
	];
	$sa818Form = '<h4 class="mt-2 alert alert-danger fw-bold">SA818 programmer</h4>
	<div class="card mb-2">
		<h4 class="card-header fs-5">Channel</h4>
		<div class="card-body">
			<div class="form-floating mb-1">
				<select id="sa_grp" class="form-select" aria-label="Frecvenţă (MHz)">
				<option selected disabled>Select a value</option>';
					for ($f=144.000; $f<=148.000; $f+=0.0125) {
						$freqFmt = str_replace('000', '00', sprintf("%0.4f", $f));
						$freqFmt = (strlen($freqFmt) == 8) ? str_replace(',0','', preg_replace('/\d$/', ',$0', $freqFmt)) : $freqFmt;
						$sa818Form .= '<option '. (($lastPgmData['frequency'] == sprintf("%0.4f", $f)) ? 'selected' : null) .' value="'. sprintf("%0.4f", $f) .'">'. $freqFmt .'</option>'. PHP_EOL;
					}
					for ($f=420.000; $f<=450.000; $f+=0.025) {
						$sa818Form .= '<option '. (($lastPgmData['frequency'] == sprintf("%0.4f", $f)) ? 'selected' : null) .' value="'. sprintf("%0.4f", $f) .'">'. sprintf("%0.3f",$f) .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_grp">Frequency (MHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_dev" class="form-select" aria-label="Deviation (kHz)">
				<option selected disabled>Select a value</option>
				<option '. ((isset($lastPgmData['deviation']) && $lastPgmData['deviation'] == 0) ? 'selected' : null) .' value="0">12.5</option>
				<option '. (($lastPgmData['deviation'] == 1) ? 'selected' : null) .' value="1">25</option>
			</select>
			<label for="sa_dev">Deviation (kHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_tpl" class="form-select" aria-label="CTCSS (Hz)">
				<option selected disabled>Select a value</option>';
					/* Build CTCSS selects */
					foreach ($ctcssVars as $key => $val) {
						$selected = ($lastPgmData['ctcss'] == sprintf("%04d", $key)) ? 'selected' : null;
						$sa818Form .= '<option value="'. sprintf("%04d", $key) .'"'. $selected .'>'. $val .'</option>'. PHP_EOL;
					}
			$sa818Form .= '</select>
			<label for="sa_tpl">CTCSS (Hz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_sql" class="form-select" aria-label="Squelch">
				<option selected disabled>Select a value</option>';
					/* Generate squelch values */
					for ($sq=1; $sq<=8; $sq+=1) {
						$selected = ($lastPgmData['squelch'] == $sq) ? ' selected' : '';
						$sa818Form .= '<option value="'. $sq .'"'. $selected .'>'. $sq .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_sql">Squelch</label>
		</div>
		</div>
		</div>
		<div class="card mb-2">
		<h4 class="card-header fs-5">Volume</h4>
		<div class="card-body">
		<div class="form-floating">
			<select id="sa_vol" class="form-select" aria-label="Volume">
				<option value="" selected>No change</option>';
					/* Generate volume values */
					for ($vol=1; $vol<=8; $vol+=1) {
						$sa818Form .= '<option '. (isset($lastPgmData['volume']) && ($lastPgmData['volume'] == $vol) ? 'selected' : null) .' value="'. $vol .'">'. $vol .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_vol">Volume</label>
		</div>
		</div>
		</div>
		<div class="card mb-2">
		<h4 class="card-header fs-5">Filter</h4>
		<div class="card-body">
		<div class="form-floating">
		<select id="sa_flt" class="form-select" aria-label="Filter">'. PHP_EOL;
		foreach ($filterOptions as $value => $label) {
	        $sa818Form .= '<option value="'. $value .'"'. ((isset($lastPgmData["filter"]) && ($lastPgmData["filter"] == $value)) ? " selected" : "") .'>'. $label .'</option>'. PHP_EOL;
		}
		$sa818Form .= '</select>
			<label for="sa_flt">Filter</label>
		</div>
		</div>
		</div>';
		$sa818Form .= '<div class="col alert alert-info mt-3 p-1 mx-auto text-center" role="alert">Note : Using <b>ttyS'. $config['cfgTty'] .'</b> and <b>GPIO'. $config['cfgPttPin'] .'</b> for PTT. You can change these using the config page.</div>'. PHP_EOL;
		$sa818Form .= '<div class="d-flex justify-content-center my-3">
			<button id="programm" type="button" class="btn btn-danger btn-lg">Send data</button>
		</div>'. PHP_EOL;
		$sa818Form .= '<div class="d-flex justify-content-center"><small class="d-inline-flex px-1 py-1 text-muted border rounded-3">';
		$sa818Form .= 'Last programmed : '. ((isset($lastPgmData['date'])) ? date('d-M-Y H:i:s', $lastPgmData['date']) : 'Unknown');
		$sa818Form .= '</small></div>';
	return $sa818Form;
}

/* APRS */
function aprsForm($ajax = false) {
	$cfgFiles = array(
		'/opt/rolink/conf/rolink.conf' => 'RoLink',
		'/etc/direwolf.conf' => 'DireWolf'
		);
	foreach ($cfgFiles as $path => $name) {
		if (!is_file($path)) return '<div class="alert alert-danger text-center" role="alert">'. $name .' not installed!</div>';
	}
	if ($ajax) include_once __DIR__ .'/functions.php';
	$callsign = $aprsfiLink = $comment = $server = $symbol = '';
	$report = 0;
	if (preg_match('/IGLOGIN (\S+)/', file_get_contents('/etc/direwolf.conf'), $matches)) {
		$callsign = $matches[1];
		$aprsfiLink = (empty($callsign) && $callsign == 'N0CALL-15') ? null : '<span data-bs-toggle="tooltip" title="View '. $callsign .' on aprs.fi" class="input-group-text">
			<a class="mx-2" href="https://aprs.fi/#!call='. $callsign .'" target="_blank"><i class="icon-exit_to_app"></i></a>
		</span>';
	}
	$aprsForm = '<h4 class="mt-2 alert alert-primary fw-bold">APRS</h4>';
	$data = json_decode(gpsd(), true);
	if ($data['class'] == 'ERROR') {
		$aprsForm .= '<div class="alert alert-danger text-center" role="alert">'. $data['message'] .'</div>';
		return $aprsForm;
	};

	$svcDirewolf	= trim(shell_exec("systemctl is-active direwolf"));
	$svcGPSD		= trim(shell_exec("systemctl is-active gpsd"));

	$aprsForm .= '<div class="accordion mb-3" id="gpsdata">
   <div class="accordion-item">
      <h3 class="accordion-header" id="heading">
         <button class="bg-'. (($svcDirewolf == 'active' && $svcGPSD == 'active') ? 'success' : 'danger') .' text-white accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#position" aria-expanded="true" aria-controls="position">Status</button>
      </h3>
      <div id="position" class="accordion-collapse collapse show" aria-labelledby="heading" data-bs-parent="#gpsdata">
	<div id="dynamicData" class="accordion-body">';

	$dynamicData = '<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">Direwolf</span>
			<input type="text" class="form-control '.(($svcDirewolf == 'active') ? 'text-success' : 'text-danger').'" value="'. $svcDirewolf .'" readonly>
			'. (($svcDirewolf == 'active') ? $aprsfiLink : null) .'
		</div>
	<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">GPSD</span>
			<input type="text" class="form-control '.(($svcGPSD == 'active') ? 'text-success' : 'text-danger').'" value="'. $svcGPSD .'" readonly>
	</div>';

	if ($svcGPSD == 'active' && isset($data['tpv'][0])) {
		$fixDescriptions = [0 => "unknown", 1 => "no fix", 2 => "2D", 3 => "3D"];
    	$gpsData = $data['tpv'][0];
    	if ($gpsData['mode'] == 0) {
    		$aprsForm .= '<meta http-equiv="refresh" content="3"><div class="alert alert-warning text-center" role="alert">Status unknown. Reloading...</div>';
    		return $aprsForm;
    	}

    	$fixMode		= $fixDescriptions[$gpsData['mode']];
    	$coordinates	= number_format($gpsData['lat'], 5) .', '. number_format($gpsData['lon'], 5);
    	$altitude		= (($gpsData['mode'] == 3) ? round($gpsData['alt']) . ' m': 'N/A');
    	$speed			= (($gpsData['mode'] == 3) ? round($gpsData['speed'] * 3.6) . ' km/h' : 'N/A');

		// Convert reported time to selected timezone (Config page)
		$utcTime = new DateTime($gpsData['time'], new DateTimeZone("UTC"));
		$timezone = trim(file_get_contents('/etc/timezone'));
		$eetTimeZone = new DateTimeZone($timezone);
		$utcTime->setTimezone($eetTimeZone);
		$time = $utcTime->format("H:i:s d/m/Y");

		// Maidenhead Locator
		$longitude = $gpsData['lon'] + 180;
		$latitude = $gpsData['lat'] + 90;
		$letterA = ord('A');
		$numberZero = ord('0');
		$locator = chr($letterA + intval($longitude / 20));
		$locator .= chr($letterA + intval($latitude / 10));
		$locator .= chr($numberZero + intval(($longitude % 20) / 2));
		$locator .= chr($numberZero + intval($latitude % 10));
		$locator .= chr($letterA + intval(($longitude - intval($longitude / 2) * 2) / (2 / 24)));
		$locator .= chr($letterA + intval(($latitude - intval($latitude / 1) * 1 ) / (1 / 24)));

    	$dynamicData .= '<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">Fix mode</span>
			<input type="text" class="form-control" value="'. $fixMode .'" readonly>
		</div>';
    	$dynamicData .= ($gpsData['mode'] < 2) ? null : '<div class="input-group input-group-sm mb-1">
  			<div class="input-group-prepend input-group-sm">
    			<span class="input-group-text" style="width: 6.5rem;">Lat / Lon</span>
  			</div>
  			<input type="text" class="form-control" value="'. $coordinates .'" readonly>
		</div>
		<div class="input-group input-group-sm mb-1">
  			<div class="input-group-prepend input-group-sm">
    			<span class="input-group-text" style="width: 6.5rem;">Grid Square</span>
  			</div>
  			<input type="text" class="form-control" value="'. $locator .'" readonly>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">Altitude</span>
			<input type="text" class="form-control" value="'. $altitude .'" readonly>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">Speed</span>
			<input type="text" class="form-control" value="'. $speed .'" readonly>
		</div>';
    	$dynamicData .= '<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 6.5rem;">Time</span>
			<input type="text" class="form-control" value="'. $time .'" readonly>
		</div>';
    	$dynamicData .= ($gpsData['mode'] < 2) ? null : '<div class="col-auto fill">
			<div class="map" id="map"></div>
		</div>
		<script>
			var LonLat = ol.proj.fromLonLat(['. $gpsData['lon'] .','. $gpsData['lat'] .'])
			var stroke = new ol.style.Stroke({color: "red", width: 2});
			var feature = new ol.Feature(new ol.geom.Point(LonLat))
			var x = new ol.style.Style({
				image: new ol.style.Icon({
				anchor: [0.5, 1],
				crossOrigin: "anonymous",
				src: "assets/img/pin.png",
				})
			})
			feature.setStyle(x)
			var source = new ol.source.Vector({
			    features: [feature]
			});
			var vectorLayer = new ol.layer.Vector({
			  source: source
			});
			var map = new ol.Map({
			  target: "map",
			  layers: [
			    new ol.layer.Tile({
			      source: new ol.source.OSM()
			    }),
			    vectorLayer
			  ],
			  view: new ol.View({
			    center: LonLat,
			    zoom: 10
			  })
			});
		</script>' . PHP_EOL;
	}
	/* Return updates only */
	if ($ajax) return $dynamicData;
	/* Read config*/
	$aprsConfig = file('/etc/direwolf.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($aprsConfig as $line) {
		if (preg_match('/IGSERVER (\S+)/', $line, $matches)) {
			$server = $matches[1];
		} elseif (preg_match('/TBEACON.*symbol="([^"]+)".*comment="([^"]+)"(?:.*commentcmd="([^"]*)")?/', $line, $matches)) {
			$symbol = $matches[1];
			$comment = $matches[2];
			$temp = (isset($matches[3])) ? (preg_match('/tempc/', $matches[3]) ? 2 : 1) : 0;
		} elseif (preg_match('/KISSCOPY (\S+)/', $line, $matches)) {
			$report = $matches[1];
		}
	}
	$aprsForm .= $dynamicData;
	$aprsForm .= '</div>
      </div>
   </div>
</div>
<div class="card mb-2">
	<h4 class="card-header fs-5">Configuration</h4>
	<div class="card-body">
		<div class="input-group input-group-sm mb-1">
			<span data-bs-toggle="tooltip" title="Manage the Direwolf service which handles sending GPS data to APRS-IS" class="input-group-text" style="width: 8rem;">Direwolf</span>
			<select id="aprs_service" class="form-select">
				<option value="0"'. (($svcDirewolf == 'inactive') ? ' selected' : null).'>Disabled</option>
				<option value="1"'. (($svcDirewolf == 'active') ? ' selected' : null).'>Enabled</option>
			</select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span data-bs-toggle="tooltip" title="Use a valid callsign with a proper suffix. The password will be generated automatically" class="input-group-text" style="width: 8rem;">Callsign</span>
			<input id="aprs_callsign" type="text" class="form-control" placeholder="YO1XYZ-15" aria-label="Callsign" aria-describedby="inputGroup-sizing-sm" value="'. $callsign .'">
		</div>
		<div class="input-group input-group-sm mb-1">
			<span data-bs-toggle="tooltip" title="A short comment about the device or status" class="input-group-text" style="width: 8rem;">Comment</span>
			<input id="aprs_comment" type="text" class="form-control" placeholder="Nod rolink" aria-label="Comment" aria-describedby="inputGroup-sizing-sm" value="'. $comment .'">
		</div>
		<div class="input-group input-group-sm mb-1">
			<span data-bs-toggle="tooltip" title="Choose whether to include the CPU temperature reading at the end of your comment. Selecting <b>Yes (compensated)</b> will add +38°C to the result, which is required for H2+ SoC-based Orange Pi Zero." class="input-group-text" style="width: 8rem;">CPU Temp</span>
			<select id="aprs_temp" class="form-select">
				<option value="0"'. (($temp == 0) ? ' selected' : null) .'>No</option>
				<option value="1"'. (($temp == 1) ? ' selected' : null) .'>Yes</option>
				<option value="2"'. (($temp == 2) ? ' selected' : null) .'>Yes (compensated)</option>
			</select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 8rem;">Symbol</span>
			<select id="aprs_symbol" class="form-select">';
	$symbols = array(
	    'rolink' => 'RoLink',
	    '/[' => 'Person',
	    '\b' => 'Bike',
	    '/<' => 'Motorcycle',
	    '/>' => 'Car',
	    '/k' => 'Truck',
	    '\k' => 'SUV',
	    '\j' => 'Jeep',
	    '/-' => 'House',
	);
	foreach ($symbols as $sym => $name) {
	    $selected = ($symbol == $sym) ? 'selected' : '';
	    $aprsForm .= "<option value=\"$sym\" $selected>$name</option>" . PHP_EOL;
	}
	$aprsForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span class="input-group-text" style="width: 8rem;">Server</span>
			<select id="aprs_server" class="form-select">';
	$servers = array(
	    'Worldwide' => 'rotate.aprs2.net',
	    'Europe / Africa' => 'euro.aprs2.net',
	    'North America' => 'noam.aprs2.net',
	    'South America' => 'soam.aprs2.net',
	    'Asia' => 'asia.aprs2.net',
	    'Oceania' => 'aunz.aprs2.net',
	    'Romania' => 'aprs.439100.ro',
	);
	foreach ($servers as $label => $value) {
	    $selected = ($server == $value) ? 'selected' : '';
	    $aprsForm .= "<option value=\"$value\" $selected>$label</option>" . PHP_EOL;
	}
	$aprsForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-1">
			<span data-bs-toggle="tooltip" title="Specify if you want to notify the server (reflector) about your usage of GPS service." class="input-group-text" style="width: 8rem;">Report position</span>
			<select id="aprs_report" class="form-select">
				<option value="0"'. ((!$report) ? ' selected' : null) .'>No</option>
				<option value="1"'. (($report) ? ' selected' : null) .'>Yes</option>
			</select>
		</div>
		<div class="d-flex justify-content-center mx-2">
			<button id="saveaprscfg" type="submit" class="btn btn-danger btn-lg m-2">Save</button>
		</div>
	</div>
</div>';
	$aprsForm .= '<script>
	var auto_refresh = setInterval( function () {
		$("#dynamicData").load("includes/forms.php?gpsStatus");
	}, 30000);
	</script>'. PHP_EOL;
	return $aprsForm;
}

/* Logs */
function logsForm() {
	$env = checkEnvironment();
	if ($env) return $env;
	$logData = '<h4 class="mt-2 alert alert-dark fw-bold">Logs</h4>';
	$logData .= '<div class="container">
	<div class="row justify-content-center">
		<div class="col-lg-12">
			<div class="card bg-light shadow border-0">
				<div class="card-header bg-white">
					<img id="new_log_line" src="assets/img/new.svg" alt="received" style="display:none;">
					<div id="log_selector">
						<select id="log">
							<option value="" disabled>-Log file-</option>
							<option value="1" selected>Syslog</option>
							<option value="2">RoLink</option>
							<option value="3">Direwolf</option>
						</select>
					</div>
				</div>
				<div class="card-body px-lg-3 py-lg-2 scrolog">
					<div class="small" id="log_data" style="height:65vh;overflow:auto"></div>
				</div>
			</div>
		</div>
	</div>
</div>';
	return $logData;
}

/* Terminal */
function ttyForm() {
	$env = checkEnvironment();
	if ($env) return $env;
	$ttydService = '/lib/systemd/system/ttyd.service';
	if (!is_file($ttydService)) return '<div class="alert alert-danger text-center" role="alert">ttyd package not installed</div>';
	$host = parse_url($_SERVER['HTTP_HOST']);
	$host = (empty($host['host']) ? $_SERVER['HTTP_HOST'] : $host['host']);
	$ttyFrame = '<h4 class="mt-2 alert alert-primary fw-bold">Terminal</h4>';
	$ttyFrame .= '<div class="row">
	    <div class="col-lg-12">
	        <div class="card bg-light shadow border-0">
	            <div class="card-body px-lg-3 py-lg-2">
	                <iframe style="height:65vh;overflow:auto" class="col-lg-12 col-md-12 col-sm-12 embed-responsive-item" src="//' . $host . ':8080"></iframe>
	            </div>
	        </div>
	    </div>
	</div>';
	return $ttyFrame;
}

/* Config */
function cfgForm() {
	$env = checkEnvironment();
	if ($env) return $env;
	global $pinsArray, $config;
	$ttysArray = array(1, 2, 3);
	$ttyPortDetected = $sa818Firmware = null;
	$version = version();
	if ($version) {
		if ($version['date'] >= 20230126){
			$sysReply = shell_exec('/usr/bin/sudo /opt/rolink/scripts/init sa_detect');
			if (!empty($sysReply)) {
				$sysData = explode('|', $sysReply);
				$ttyPortDetected = $sysData[0];
				$sa818Firmware = str_replace("+VERSION:", "", trim($sysData[1]));
			}
		};
	}

	$statusPageItems = array(
		'cfgHostname' => 'Hostname',
		'cfgUptime' => 'Uptime',
		'cfgCpuStats' => 'CPU Stats',
		'cfgNetworking' => 'Networking',
		'cfgSsid' => 'Wi-Fi Info',
		'cfgPublicIp' => 'External IP',
		'cfgSvxStatus' => 'SVXLink Status',
		'cfgRefNodes' => 'Connected nodes',
		'cfgCallsign' => 'Callsign',
		'cfgDTMF' => 'DTMF Sender',
		'cfgKernel' => 'Kernel version',
		'cfgDetectSa' => 'Detect SA818',
		'cfgFreeSpace' => 'Free Space',
		'cfgTempOffset' => 'CPU Temp Offset'
	);

	// Get mixer's current values
	exec('/usr/bin/sudo /usr/bin/amixer get \'Line Out\' | grep -Po \'(?<=(\[)).*(?=\%\])\' | head -1', $mixerGetLineOut);
	exec('/usr/bin/sudo /usr/bin/amixer get \'DAC\' | grep -Po \'(?<=(\[)).*(?=\%\])\' | head -1', $mixerGetDAC);
	exec('/usr/bin/sudo /usr/bin/amixer get \'Mic1 Boost\' | grep -Po \'(?<=(\[)).*(?=\%\])\' | head -1', $mixerGetMic1Boost);
	exec('/usr/bin/sudo /usr/bin/amixer get \'ADC Gain\' | grep -Po \'(?<=(\[)).*(?=\%\])\' | head -1', $mixerGetADCGain);

	$configData = '<h4 class="mt-2 alert alert-warning fw-bold">Configuration</h4>
	<div class="card m-1">
		<h4 class="m-2">Serial & GPIO</h4>
		<div class="form-floating m-2">
			<select id="cfgPttPin" class="form-select" aria-label="GPIO Pin (PTT)">'. PHP_EOL;
		foreach ($pinsArray as $pin) {
			$configData .= '<option value="'. $pin .'"'. ($pin == $config['cfgPttPin'] ? ' selected' : '') .'>'. $pin .'</option>'. PHP_EOL;
		}
		$configData .= '</select>
		<label for="cfgPttPin">GPIO Pin (PTT)</label>
		</div>'. PHP_EOL;
		$configData .= '<div class="form-floating m-2">
				<select id="cfgTty" class="form-select" aria-label="Serial Port (ttyS)">'. PHP_EOL;
		foreach ($ttysArray as $tty) {
			$ttyDetails = null;
			if ((int)$tty == (int)$ttyPortDetected) {
				$ttyDetails = ' (found '. $sa818Firmware .')';
			}
			$configData .= '<option value="'. $tty .'"'. ($tty == $config['cfgTty'] ? ' selected' : '') .'>'. $tty . $ttyDetails .'</option>'. PHP_EOL;
		}
		$configData .= '</select>
		<label for="cfgTty">Serial Port (ttyS)</label>
	</div>
	<h4 class="m-2">System</h4>
	<div class="form-floating m-2">
		<select id="timezone" class="form-select" aria-label="Time Zone">'. PHP_EOL;
		$tz = file('./assets/timezones.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($tz as $timezone) {
			$configData .= '<option value="'. $timezone .'"'. ($timezone == trim(file_get_contents('/etc/timezone')) ? ' selected' : '') .'>'. $timezone .'</option>'. PHP_EOL;
		}
		$configData .= '</select>
		<label for="timezone">Time Zone</label>
	</div>
	<div class="form-floating m-2">
		<select id="cfgAutoConnect" name="cfgAutoConnect" class="form-select" aria-label="Auto connect">'. PHP_EOL;
		$configData .= '<option '. (($config['cfgAutoConnect'] == 'true') ? 'selected' : null) .' value="true">Yes</option>
						<option '. (($config['cfgAutoConnect'] == 'false') ? 'selected' : null) .' value="false">No</option>';
		$configData .= '</select>
		<label for="cfgAutoConnect">Auto connect on profile change</label>
	</div>
	<div class="form-floating m-2">
		<input id="accessPassword" type="text" class="form-control" aria-label="Password"';
		$password = $label = null;
		if (is_file(__DIR__ . '/../assets/pwd')){
			$password = file_get_contents(__DIR__ . '/../assets/pwd');
		}
		if (empty($password)) {
			$configData .= ' placeholder=""';
			$label = ' (not set)';
		} else {
			$configData .= ' value="'. $password .'"';
		}
		$configData .= '>
		<label for="accessPassword">Dashboard password'. $label .'</label>
	</div>
	<h4 class="m-2">Status page content</h4>
	<div class="row form-floating m-2">'. PHP_EOL;
	foreach ($statusPageItems as $cfgName => $cfgTitle) {
		$configData .= '<div class="form-check col col-lg-2 m-3">
			<input class="form-check-input" type="checkbox" id="'. $cfgName .'"'. ($config[$cfgName] == 'true' ? ' checked' : '') .'>
			<label class="form-check-label" for="'. $cfgName .'">'. $cfgTitle .'</label>
		</div>'. PHP_EOL;
	}
	$configData .= '</div>
<h4 class="m-2">Audio control</h4>
<div class="row m-3">
	<p class="lead">Output</p>
	<div class="col-sm-3">
		<div class="d-flex flex-column">
			<label for="vac_out">Volume Out<span class="mx-2" id="vac_outcv">('. $mixerGetLineOut[0] .'%)</span></label>
			<input type="range" min="6" max="100" step="3" class="form-control-range" id="vac_out" value="'. $mixerGetLineOut[0] .'">
		</div>
	</div>
	<div class="col-sm-3">
    	<div class="d-flex flex-column">
			<label for="vac_dac">DAC<span class="mx-2" id="vac_daccv">('. $mixerGetDAC[0] .'%)</span></label>
			<input type="range" min="0" max="100" step="2" class="form-control-range" id="vac_dac" value="'. $mixerGetDAC[0] .'">
		</div>
	</div>
</div>
<div class="row m-3">
	<p class="lead">Input</p>
	<div class="col-sm-3">
    	<div class="d-flex flex-column">
    		<label for="vac_mb">Mic1 Boost<span class="mx-2" id="vac_mbcv">('. $mixerGetMic1Boost[0] .'%)</span></label>
    		<input type="range" min="0" max="100" step="10" class="form-control-range" id="vac_mb" value="'. $mixerGetMic1Boost[0] .'" disabled>
    	</div>
	</div>
  	<div class="col-sm-3">
    	<div class="d-flex flex-column">
    		<label for="vac_adc">ADC Gain<span class="mx-2" id="vac_adccv">('. $mixerGetADCGain[0] .'%)</span></label>
    		<input type="range" min="0" max="100" step="10" class="form-control-range" id="vac_adc" value="'. $mixerGetADCGain[0] .'">
    	</div>
	</div>
</div>
		<div class="alert alert-info m-2 p-1" role="alert">Note : Adjusting the sliders has immediate effect!</div>
</div>
	<div class="d-flex justify-content-center mt-4">
		<button id="cfgSave" type="button" class="btn btn-danger btn-lg mx-2">Save</button>';
		if ($version) {
			$isOnline = checkdnsrr('google.com');
			// Check if RoLink version is capable of updates and if we're connected to the internet
			if ($version['date'] > 20211204 && $isOnline) {
				$configData .= '<button id="updateDash" type="button" class="btn btn-primary btn-lg mx-2">Dashboard update</button>';
				$configData .= '<button id="updateRoLink" type="button" class="btn btn-warning btn-lg mx-2">RoLink update</button>';
			}
			$configData .= ($isOnline) ? null : '<button type="button" class="btn btn-dark btn-lg mx-2">Internet not available</button>';
		}
		// Show "Make Read-only" button
		if (!preg_match('/ro,ro/', file_get_contents('/etc/fstab'))) {
			$configData .= '</div><div class="d-flex justify-content-center m-2"><button id="makeRO" type="button" class="btn btn-dark btn-lg">Make FS Read-Only</button>';
		}
	$configData .= '</div>'. PHP_EOL;
	return $configData;
}
