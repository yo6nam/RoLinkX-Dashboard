<?php
/*
*   RoLinkX Dashboard v2.95
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
* Note : Some code borrowed from https://github.com/RaspAP/raspap-webgui
*/

if (isset($_GET['scan'])) echo scanWifi(1);

$pinsArray = array(2, 3, 6, 7, 10, 18, 19);

/* Wi-Fi form */
function getSSIDs() {
	$storedSSID = null;
	$storedPwds = null;
	preg_match_all('/ssid="(.*)"/', file_get_contents('/etc/wpa_supplicant/wpa_supplicant.conf'), $resultSSID);
	if (empty($resultSSID)) return false;

	foreach ($resultSSID[1] as $key => $ap) {
		if ($key <= 3) {
			  $storedSSID[] = $ap;
		  }
	}

	preg_match_all('/psk="(\S+)"/', file_get_contents('/etc/wpa_supplicant/wpa_supplicant.conf'), $resultPWDS);
	if (empty($resultPWDS)) return false;

	foreach ($resultPWDS[1] as $key => $pw) {
		if ($key <= 3) {
			  $storedPwds[] = $pw;
		  }
	}
	return array($storedSSID, $storedPwds);
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
		$active = (isset($con[0])) ? $con[0] : null;
		$a = (isset($ssidList[0][$i]) && $active === $ssidList[0][$i]) ? true : false;
		$n = (empty($ssidList[0][$i])) ? 'empty' : $ssidList[0][$i] .' (saved)';
		$p = (empty($ssidList[1][$i])) ? 'empty' : preg_replace('/(?!^.?).(?!.{0}$)/', '*',  $ssidList[1][$i]);
		$c = ($i + 1);
		$b = ($a) ? ' bg-success text-white' : null;
		$s = ($a) ? ' (connected)' : null;
		$wifiForm .= '<h4 class="d-flex justify-content-center badge badge-light fs-6'. $b .'"><i class="icon-wifi">&nbsp;</i>Network '. $c . $s .'</h4><div class="input-group input-group-sm mb-2">
		  <span class="input-group-text" style="width: 7rem;">Name (SSID)</span>
		  <input id="wlan_network_'. $c .'" type="text" class="form-control" placeholder="'. $n .'" aria-label="Network Name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-4">
		  <span class="input-group-text" style="width: 7rem;">Key (Password)</span>
		  <input id="wlan_authkey_'. $c .'" type="text" class="form-control" placeholder="'. $p .'" aria-label="Network key" aria-describedby="inputGroup-sizing-sm">
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
	$cfgFile = '/opt/rolink/conf/rolink.conf';
	if (!is_file($cfgFile)) return '<div class="alert alert-danger text-center" role="alert">RoLink not installed!</div>';

	global $pinsArray;
	$svxPinsArray = array();

	/* Convert pins to both states (normal/inverted) */
	foreach ($pinsArray as $pin) {
		$svxPinsArray[] = 'gpio'. $pin;
		$svxPinsArray[] = '!gpio'. $pin;
	}
	$profileOption = null;
	$voicesPath = '/opt/rolink/share/sounds';

	/* Get current variables */
	$cfgFileData = file_get_contents('/opt/rolink/conf/rolink.conf');
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
	/* Monitor TGs*/
	preg_match('/(MONITOR_TGS=)(\S+)/', $cfgFileData, $varMonitorTgs);
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
	preg_match('/(MASTER_GAIN=)(-?\d+)\n/', $cfgFileData, $varMasterGain);
	$masterGainValue	= (isset($varMasterGain[2])) ? $varMasterGain[2] : '';
	/* Reconnect seconds */
	preg_match('/(RECONNECT_SECONDS=)(\d+)/', $cfgFileData, $varReconnectSeconds);
	$reconnectSecondsValue	= (isset($varReconnectSeconds[2])) ? 'value='. $varReconnectSeconds[2] : '';

	/* Profiles section */
	$profilesPath	= dirname(__FILE__) .'/../profiles/';
	$proFiles		= array_slice(scandir($profilesPath), 2);
	$skip			= array('sa818pgm.log', 'index.html');

	/* Configuration info sent to reflector ('tip' only) */
	$cfgRefFile = file_get_contents('/opt/rolink/conf/rolink.json');
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
		  <input id="svx_cal" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Call sign" aria-describedby="inputGroup-sizing-sm" '. $callSignValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Auth Key</span>
		  <input id="svx_key" type="text" class="form-control" placeholder="nod_portabil" aria-label="Adresa server" aria-describedby="inputGroup-sizing-sm" '. $authKeyValue .'>
		</div>
		<div class="input-group input-group-sm mb-1">
		  <span class="input-group-text" style="width: 8rem;">Callsign (beacon)</span>
		  <input id="svx_clb" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Call sign" aria-describedby="inputGroup-sizing-sm" '. $beaconValue .'>
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
		  <span class="input-group-text" style="width: 8rem;">Squelch delay</span>
		  <input id="svx_sqd" type="text" class="form-control" placeholder="500" aria-label="Squelch delay" aria-describedby="inputGroup-sizing-sm" '. $sqlDelayValue .'>
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
		for($gain=6; $gain>=-6; $gain-=1){
			$svxForm .= '<option value="'. $gain .'"'. ($gain == $masterGainValue ? ' selected' : '') .'>'. (($gain > 0) ? '+'. $gain : $gain) .' dB</option>'. PHP_EOL;
		}
		$svxForm .= '</select>
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
		</div>';
		$svxForm .= '
		<div class="d-flex justify-content-center mt-4">
			<button id="savesvxcfg" type="submit" class="btn btn-danger btn-lg m-2">Save</button>
			<button id="restore" type="submit" class="btn btn-info btn-lg m-2">Restore defaults</button>
			</div>'. PHP_EOL;
	return $svxForm;
}

/* SA818 radio */
function sa818Form() {
	$cfgFile = '/opt/rolink/conf/rolink.conf';
	if (!is_file($cfgFile)) return '<div class="alert alert-danger text-center" role="alert">RoLink not installed!</div>';
	$config = include 'config.php';
	$historyFile = dirname(__FILE__) .'/../profiles/sa818pgm.log';
	$ctcssVars = array(
		"0" => "None", "1" => "67.0", "2" => "71.9", "3" => "74.4", "4" => "77.0", "5" => "79.7",
		"6" => "82.5", "7" => "85.4", "8" => "88.5", "9" => "91.5", "10" => "94.8",
		"11" => "97.4", "12" => "100.0", "13" => "103.5", "14" => "107.2",
		"15" => "110.9", "16" => "114.8", "17" => "118.8", "18" => "123",
		"19" => "127.3", "20" => "131.8", "21" => "136.5", "22" => "141.3",
		"23" => "146.2", "24" => "151.4", "25" => "156.7", "26" => "162.2",
		"27" => "167.9", "28" => "173.8", "29" => "179.9", "30" => "186.2",
		"31" => "192.8", "32" => "203.5", "33" => "210.7", "34" => "218.1",
		"35" => "225.7", "36" => "233.6", "37" => "241.8", "38" => "250.3"
		);
	$sa818Form = '<h4 class="mt-2 alert alert-danger fw-bold">SA818 programmer</h4>
		<div class="form-floating mb-1">
			<select id="sa_grp" class="form-select" aria-label="Frecvenţă (MHz)">
				<option selected disabled>Select a value</option>';
					for ($f=144.000; $f<=148.000; $f+=0.0125) {
						if (sprintf("%0.3f", $f) == '144.800') continue;
						$freqFmt = str_replace('000', '00', sprintf("%0.4f", $f));
						$freqFmt = (strlen($freqFmt) == 8) ? str_replace(',0','', preg_replace('/\d$/', ',$0', $freqFmt)) : $freqFmt;
						$sa818Form .= '<option value="'. sprintf("%0.4f", $f) .'">'. $freqFmt .'</option>'. PHP_EOL;
					}
					for ($f=420.000; $f<=450.000; $f+=0.025) {
						$sa818Form .= '<option value="'. sprintf("%0.4f", $f) .'">'. sprintf("%0.3f",$f) .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_grp">Frequency (MHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_dev" class="form-select" aria-label="Deviation (kHz)">
				<option selected disabled>Select a value</option>
				<option value="0">12.5</option>
				<option value="1" selected>25</option>
			</select>
			<label for="sa_dev">Deviation (kHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_tpl" class="form-select" aria-label="CTCSS (Hz)">
				<option selected disabled>Select a value</option>';
					/* Build CTCSS selects */
					foreach ($ctcssVars as $key => $val) {
						$selected = ($key == 13) ? ' selected' : '';
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
						$selected = ($sq == 4) ? ' selected' : '';
						$sa818Form .= '<option value="'. $sq .'"'. $selected .'>'. $sq .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_sql">Squelch</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_vol" class="form-select" aria-label="Volume">
				<option value="" selected>No change</option>';
					/* Generate volume values */
					for ($vol=1; $vol<=8; $vol+=1) {
						$sa818Form .= '<option value="'. $vol .'">'. $vol .'</option>'. PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_vol">Volume</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_flt" class="form-select" aria-label="Filter">
				<option value="" selected>No change</option>
				<option value="0,0,0">Disable All</option>
				<option value="1,0,0">Enable Pre/De-Emphasis</option>
				<option value="0,1,0">Enable High Pass</option>
				<option value="0,0,1">Enable Low Pass</option>
				<option value="0,1,1">Enable Low Pass & High Pass</option>
				<option value="1,1,0">Enable Pre/De-Emphasis & High Pass</option>
				<option value="1,0,1">Enable Pre/De-Emphasis & Low Pass</option>
				<option value="1,1,1">Enable All</option>
			</select>
			<label for="sa_flt">Filter</label>
		</div>';
		$sa818Form .= '<div class="col alert alert-info mt-3 p-1 mx-auto text-center" role="alert">Note : Using <b>ttyS'. $config['cfgTty'] .'</b> and <b>GPIO'. $config['cfgPttPin'] .'</b> for PTT. You can change these using the config page.</div>'. PHP_EOL;
		$sa818Form .= '<div class="d-flex justify-content-center my-3">
			<button id="programm" type="button" class="btn btn-danger btn-lg">Send data</button>
		</div>'. PHP_EOL;
	// Display last programmed data
	if (is_file($historyFile)) {
		$last = json_decode(file_get_contents($historyFile), true);
		$sa818Form .= '<div class="d-flex justify-content-center"><small class="d-inline-flex px-2 py-2 text-muted border rounded-3">';
		$sa818Form .= 'Last programmed : ' . date('d-M-Y H:i:s', $last['date']) ."<br/>";
		$sa818Form .= 'Frequency : ' . $last['frequency'] ."<br/>";
		$sa818Form .= 'Deviation : ' . $last['deviation'] ."<br/>";
		$sa818Form .= 'CTCSS : ' . ((preg_match('/None/',$last['ctcss'])) ? 'Open / None' : $last['ctcss']) ."<br/>";
		$sa818Form .= 'Squelch : ' . $last['squelch'];
		$sa818Form .= '</small></div>';
	}
	return $sa818Form;
}

/* Logs */
function logsForm() {
	$cfgFile = '/opt/rolink/conf/rolink.conf';
	if (!is_file($cfgFile)) return '<div class="alert alert-danger text-center" role="alert">RoLink not installed!</div>';
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

/* Config */
function cfgForm() {
	$cfgFile = '/opt/rolink/conf/rolink.conf';
	if (!is_file($cfgFile)) return '<div class="alert alert-danger text-center" role="alert">RoLink not installed!</div>';
	global $pinsArray;
	include __DIR__ .'/../includes/functions.php';
	$ttysArray = array(1, 2, 3);
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
			$configData .= '<option value="'. $tty .'"'. ($tty == $config['cfgTty'] ? ' selected' : '') .'>'. $tty .'</option>'. PHP_EOL;
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
		if (is_file('/opt/rolink/version')) {
			$localData		= file_get_contents('/opt/rolink/version');
			$localVersion	= explode('|', $localData);
			$isOnline		= checkdnsrr('google.com');
			// Check if RoLink version is capable of updates and if we're connected to the internet
			if ($localVersion[0] > '20211204' && $isOnline) {
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
