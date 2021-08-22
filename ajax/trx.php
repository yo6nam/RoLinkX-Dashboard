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
* SA818(S)(V/U) radio programming module
*/

$txPin		= 7; // Change this to fit your board
$pinPath	= '/sys/class/gpio/gpio'. $txPin .'/value';

/* Get POST vars */
$grp = (isset($_POST['grp'])) ? filter_input(INPUT_POST, 'grp', FILTER_SANITIZE_STRING) : '';
$dev = (isset($_POST['dev'])) ? filter_input(INPUT_POST, 'dev', FILTER_SANITIZE_STRING) : '';
$tpl = (isset($_POST['tpl'])) ? filter_input(INPUT_POST, 'tpl', FILTER_SANITIZE_STRING) : '';
$sql = (isset($_POST['sql'])) ? filter_input(INPUT_POST, 'sql', FILTER_SANITIZE_STRING) : '';
$vol = (isset($_POST['vol'])) ? filter_input(INPUT_POST, 'vol', FILTER_SANITIZE_STRING) : '';
$flt = (isset($_POST['flt'])) ? filter_input(INPUT_POST, 'flt', FILTER_SANITIZE_STRING) : '';

/* Include the serial class */
include __DIR__ . "/../includes/php_serial.class.php";

/* Stop SVXLink service before attempting anything */
shell_exec('/usr/bin/sudo /usr/bin/systemctl stop rolink.service');

/* If stuck in TX, exit */
shell_exec('/usr/bin/sudo /usr/bin/chmod guo+rw '. $pinPath);
shell_exec('/usr/bin/cat '. $pinPath .' | grep 1 && /usr/bin/echo 0 > '. $pinPath);

/* Build the AT commands */
if (!empty($dev) &&  !empty($grp) && !empty($tpl) && !empty($sql)) { // Frequency, Deviation, CTCSS, SQL
	$pgmGroup = 'AT+DMOSETGROUP='. $dev .','. $grp .','. $grp .','. $tpl .','. $sql .','. $tpl;
	$groupCmd = writeToSerial($pgmGroup, 2);
}
if (!empty($vol)) {
	$pgmVolume = 'AT+DMOSETVOLUME='. $vol;
	$volumeCmd = writeToSerial($pgmVolume, 1);
}
if (!empty($flt)) {
	$pgmFilter = 'AT+SETFILTER='. $flt;
	$filterCmd = writeToSerial($pgmFilter, 1);
}
function writeToSerial($command, $delay = 1) {
	if (empty($command)) return 'Empty command. Exiting...';
	shell_exec('/usr/bin/sudo /usr/bin/chmod guo+rw /dev/ttyS1');
	$serial = new phpSerial;
	$serial->deviceSet("/dev/ttyS1");
	$serial->deviceOpen('w+');
	stream_set_timeout($serial->_dHandle, 10);
	/* Connect to device */
	$serial->sendMessage("AT+DMOCONNECT\r\n", 1);
	$cstatus = trim($serial->readPort());
	if ($cstatus != "+DMOCONNECT:0") {
		return false;
	}
	/* Process command */
	$serial->sendMessage($command ."\r\n", $delay);
	$reply = $serial->readPort();
	$serial->deviceClose();
	return $reply;
}

/* Give feedback to user */
$moduleReply = '*** Response from SA818 module ***</br>';
$moduleReply .= (isset($groupCmd)) ? 'Channel : ' . str_replace("+DMOSETGROUP:0", "Success!", $groupCmd) . '</br>' : '';
$moduleReply .= (isset($volumeCmd)) ? 'Volume : ' . str_replace("+DMOSETVOLUME:0", "Success!", $volumeCmd) . '</br>' : '';
$moduleReply .= (isset($filterCmd)) ? 'Filter : ' . str_replace("+DMOSETFILTER:0", "Success!", $filterCmd) . '</br>' : '';
echo $moduleReply;

/* All done, start SVXLink service */
sleep(2);
shell_exec('/usr/bin/sudo /usr/bin/systemctl start rolink.service');
