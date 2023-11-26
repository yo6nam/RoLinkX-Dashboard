<?php
ignore_user_abort(true);
set_time_limit(0);
header("Cache-Control: no-cache");
header("Content-Type: text/event-stream; charset=utf-8");
header("Content-Encoding: none");
header("Access-Control-Allow-Origin: *");
ob_implicit_flush(true);
ob_end_flush();
$heartbeat = $id = 0;
$prevData = '';
$stream = '/tmp/rolinkXstream';
$gpioPaths = [
    "rx" => "/sys/class/gpio/gpio10/value",
    "tx" => "/sys/class/gpio/gpio7/value",
    "fan" => "/sys/class/gpio/gpio6/value",
];
while (true) {
    $data = [];
    // Add Talker status to array
    if (file_exists($stream)) {
        $event = file_get_contents($stream);
        $evData = json_decode($event, true);
        if ($evData !== null) {
            $data['ta'] = $evData['t'];
            $data['tn'] = $evData['c'];
            if (!empty($evData['s'])) $data['s'] = $evData['s'];
        }
    }
    // Add GPIO status to array
    foreach ($gpioPaths as $key => $path) {
        if (file_exists($path)) {
            $value = trim(file_get_contents($path));
            $data[$key] = $value === "0" || $value === "1" ? $value : "0";
        }
    }
    $jsonData = json_encode($data);
    if ($jsonData != $prevData) {
    	$id++;
        echo "id: $id\retry: 1000\ndata: $jsonData\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        $prevData = $jsonData;
    }
    if ($heartbeat >= 50) {
    	echo ": heartbeat\n\n";
    	if (ob_get_level() > 0) {
        	ob_flush();
    	}
    	$heartbeat = 0;
	}
	$heartbeat++;
    usleep(200000);
    if (connection_aborted()) exit();
}
