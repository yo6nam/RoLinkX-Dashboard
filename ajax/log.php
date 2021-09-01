<?php
/*
*   RoLinkX Dashboard v0.2
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
* Log(s) page
*/
header("Cache-Control: no-cache");
$num		= (isset($_GET['n'])) ? filter_input(INPUT_GET, 'n', FILTER_SANITIZE_NUMBER_INT) : 0;
$log_file	= (!empty($_GET['t'])) ? filter_input(INPUT_GET, 't', FILTER_SANITIZE_NUMBER_INT) : NULL;
switch ($log_file) {
	case '1' :
		$logfile = '/var/log/syslog';
	break;
	case '2' :
		$logfile = '/tmp/svxlink.log';
	break;
	default :
		$logfile = '/var/log/syslog';
}
$wc = "/usr/bin/wc";
$sudo = "/usr/bin/sudo";
$tail = "/usr/bin/tail";
$cut = "/usr/bin/cut";
$tr = "/usr/bin/tr";

if (!$num) {
	$file_len = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");
	$logfile_lines = shell_exec("$sudo $tail -30 $logfile");
	$logfile_lines_arr = preg_split('/\n/', trim($logfile_lines));
	$ret_arr = array('count'=>$file_len,'loglines'=>$logfile_lines_arr);
	echo json_encode($ret_arr);
} else {
	$nextline	= $num+1;
	$safety_max = 115;
	sleep(2);
	$curr_len = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");

	if ($curr_len==$num) {
		clearstatcache();
		$ft = filectime($logfile);
		$cft = $ft;
		$safety = 0;
		while ($cft==$ft  && $safety < $safety_max) {
			clearstatcache();
			sleep(1);
			clearstatcache();
			$cft = filectime($logfile);
			$safety++;
	 	}

		if ($safety >= $safety_max){
			$ret_arr = array('count'=>-1);
			echo json_encode($ret_arr);
			exit;
		}
		get_last_log_lines_from_pos($nextline);
		exit;
	}
	get_last_log_lines_from_pos($nextline);
}

function get_last_log_lines_from_pos($pos) {
	global $sudo, $tail, $logfile, $wc, $cut, $tr;
	$logfile_lines = shell_exec("$sudo $tail -n +$pos $logfile");
	$logfile_lines_arr = preg_split('/\n/', trim($logfile_lines));
	$curr_len = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");
	$ret_arr = array('count'=>$curr_len,'loglines'=>$logfile_lines_arr);
	echo json_encode($ret_arr);
}