<?php
/*
 *   RoLinkX Dashboard v3.5
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
 * Log(s) page
 */

header("Cache-Control: no-cache");
$num     = (isset($_GET['n'])) ? filter_input(INPUT_GET, 'n', FILTER_SANITIZE_NUMBER_INT) : 0;
$logFile = (!empty($_GET['t'])) ? filter_input(INPUT_GET, 't', FILTER_SANITIZE_NUMBER_INT) : null;
$maxWait = 115;
$wc      = "/usr/bin/wc";
$sudo    = "/usr/bin/sudo";
$tail    = "/usr/bin/tail";
$cut     = "/usr/bin/cut";
$tr      = "/usr/bin/tr";

switch ($logFile) {
    case '1':
        $logfile = '/var/log/syslog';
        break;
    case '2':
        $logfile = '/tmp/svxlink.log';
        if (!is_file($logfile)) {
            echo json_encode(['count' => '0', 'loglines' => ['The log file for RoLink (SVXLink) is missing. This might be due to a crash of the application. Check syslog.']]);
            sleep(3);
            exit(1);
        }
        break;
    case '3':
        $logfile = '/var/log/direwolf.log';
        if (!is_file($logfile)) {
            echo json_encode(['count' => '0', 'loglines' => ['The log file for Direwolf is missing. This might be due to a crash of the application. Check syslog.']]);
            sleep(3);
            exit(1);
        }
        break;
    default:
        $logfile = '/var/log/syslog';
}

if (!$num) {
    $fileLen    = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");
    $logLines   = shell_exec("$sudo $tail -100 $logfile");
    $logLineArr = preg_split('/\n/', trim($logLines));
    $dataArr    = array('count' => $fileLen, 'loglines' => $logLineArr);
    echo json_encode($dataArr);
} else {
    $nextline = $num + 1;
    sleep(2);
    $curr_len = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");
    if ($curr_len == $num) {
        clearstatcache($logfile);
        $logTimeStamp = filectime($logfile);
        $newTimeStamp = $logTimeStamp;
        $timeout      = 0;
        while ($newTimeStamp == $logTimeStamp && $timeout < $maxWait) {
            clearstatcache($logfile);
            sleep(1);
            clearstatcache($logfile);
            $newTimeStamp = filectime($logfile);
            $timeout++;
        }
        if ($timeout >= $maxWait) {
            $dataArr = array('count' => -1);
            echo json_encode($dataArr);
            exit;
        }
        getLastLog($nextline);
        exit;
    }
    getLastLog($nextline);
}

function getLastLog($pos)
{
    global $sudo, $tail, $logfile, $wc, $cut, $tr;
    $logLines   = shell_exec("$sudo $tail -n +$pos $logfile");
    $logLineArr = preg_split('/\n/', trim($logLines));
    $curr_len   = shell_exec("$sudo $wc -l $logfile | $cut -d \" \" -f 1 | $tr -d '\n' 2>/dev/null");
    $dataArr    = array('count' => $curr_len, 'loglines' => $logLineArr);
    echo json_encode($dataArr);
}
