<?php
/*
*   RoLinkX Dashboard v2.0
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
* SA818(S)(V/U) radio programming module
*/

include __DIR__ . "/../includes/functions.php";
include __DIR__ . "/../includes/php_serial.class.php";

/* Get POST vars */
$grp = (isset($_POST['grp'])) ? filter_input(INPUT_POST, 'grp', FILTER_SANITIZE_STRING) : '';
$dev = (isset($_POST['dev'])) ? filter_input(INPUT_POST, 'dev', FILTER_SANITIZE_STRING) : '';
$tpl = (isset($_POST['tpl'])) ? filter_input(INPUT_POST, 'tpl', FILTER_SANITIZE_STRING) : '';
$sql = (isset($_POST['sql'])) ? filter_input(INPUT_POST, 'sql', FILTER_SANITIZE_STRING) : '';
$vol = (isset($_POST['vol'])) ? filter_input(INPUT_POST, 'vol', FILTER_SANITIZE_STRING) : '';
$flt = (isset($_POST['flt'])) ? filter_input(INPUT_POST, 'flt', FILTER_SANITIZE_STRING) : '';

$ctcssVars = array(
		"1" => "67.0", "2" => "71.9", "3" => "74.4", "4" => "77.0", "5" => "79.7",
		"6" => "82.5", "7" => "85.4", "8" => "88.5", "9" => "91.5", "10" => "94.8",
		"11" => "97.4", "12" => "100.0", "13" => "103.5", "14" => "107.2",
		"15" => "110.9", "16" => "114.8", "17" => "118.8", "18" => "123",
		"19" => "127.3", "20" => "131.8", "21" => "136.5", "22" => "141.3",
		"23" => "146.2", "24" => "151.4", "25" => "156.7", "26" => "162.2",
		"27" => "167.9", "28" => "173.8", "29" => "179.9", "30" => "186.2",
		"31" => "192.8", "32" => "203.5", "33" => "210.7", "34" => "218.1",
		"35" => "225.7", "36" => "233.6", "37" => "241.8", "38" => "250.3"
		);

if (empty($grp) && empty($vol) && empty($flt)) {
	sleep(2);
	echo 'Not enough data to write!<br/>Check your parameters';
	exit(1);
}

/* Update configuration info sent to reflector */
if (!empty($grp)) {
	$cfgRefFile = '/opt/rolink/conf/rolink.json';
	$tmpRefFile = '/tmp/rolink.json.tmp';
	if (!is_file($cfgRefFile)) {
		sleep(2);
		echo 'RoLink not installed!';
		exit(1);
	}
	$nfoParam = json_decode(file_get_contents($cfgRefFile), true);
	$nfoParam['pl_in'] = $ctcssVars[floatval($tpl)];
	$nfoParam['pl_out']	= $ctcssVars[floatval($tpl)];
	$nfoParam['rx_frq'] = sprintf("%0.3f", $grp);
	$nfoParam['tx_frq'] = sprintf("%0.3f", $grp);
	$nfoParam['isx'] = 2;
	toggleFS(true);
	$nfoParams = json_encode($nfoParam, JSON_PRETTY_PRINT);
	file_put_contents($tmpRefFile, $nfoParams);
	shell_exec("/usr/bin/sudo /usr/bin/cp $tmpRefFile $cfgRefFile");
}

/* Stop SVXLink service before attempting anything */
serviceControl('rolink.service','stop');

/* If stuck in TX, force exit */
unstick();

/* Build the AT commands */
if (!empty($dev) &&  !empty($grp) && !empty($tpl) && !empty($sql)) { // Frequency, Deviation, CTCSS, SQL
	$pgmGroup = 'AT+DMOSETGROUP='. $dev .','. $grp .','. $grp .','. $tpl .','. $sql .','. $tpl;
	$groupCmd = writeToSerial($pgmGroup, $config['cfgTty'], 2);
}
if (!empty($vol)) { // Audio input preamp
	$pgmVolume = 'AT+DMOSETVOLUME='. $vol;
	$volumeCmd = writeToSerial($pgmVolume, $config['cfgTty'], 1);
}
if (!empty($flt)) { // Filters
	$pgmFilter = 'AT+SETFILTER='. $flt;
	$filterCmd = writeToSerial($pgmFilter, $config['cfgTty'], 1);
}
function writeToSerial($command, $tty = 1, $delay = 1) {
	if (empty($command)) return 'Empty command. Exiting...';
	shell_exec('/usr/bin/sudo /usr/bin/chmod guo+rw /dev/ttyS' . $tty);
	$serial = new phpSerial;
	$serial->deviceSet("/dev/ttyS" . $tty);
	$serial->deviceOpen('w+');
	stream_set_timeout($serial->_dHandle, 6);
	/* Connect to device */
	$serial->sendMessage("AT+DMOCONNECT\r\n", 1);
	$cstatus = trim($serial->readPort());
	if ($cstatus != "+DMOCONNECT:0") {
		$serial->deviceClose();
		return 'Could not connect!';
	}
	/* Process command */
	$serial->sendMessage($command ."\r\n", $delay);
	$reply = $serial->readPort();
	$serial->deviceClose();
	return $reply;
}

/* Send feedback to user */
$moduleReply = '<b>Response from SA818</b></br>';
$moduleReply .= (isset($groupCmd)) ? 'Channel : ' . str_replace("+DMOSETGROUP:0", "Success!", $groupCmd) . '</br>' : '';
$moduleReply .= (isset($volumeCmd)) ? 'Volume : ' . str_replace("+DMOSETVOLUME:0", "Success!", $volumeCmd) . '</br>' : '';
$moduleReply .= (isset($filterCmd)) ? 'Filter : ' . str_replace("+DMOSETFILTER:0", "Success!", $filterCmd) . '</br>' : '';
echo $moduleReply;

/* All done, start SVXLink service */
toggleFS(false);
serviceControl('rolink.service','start');
