<?php

function writeStat($output, $log, $global)
{
    $return = ['codes' => []];
    foreach (array_keys($log) as $code) {
        sort($log[$code]);
        $count = count($log[$code]);
        $return['codes'][$code] = [
            '1.00' => $log[$code][$count - 1],
            '0.95' => $log[$code][ceil($count * 0.95) - 1],
            '0.99' => $log[$code][ceil($count * 0.99) - 1],
            'count' => $count
        ];
    }
    $return['timestamp'] = time();
    $return['global'] = $global;
    file_put_contents($output . ".tmp", json_encode($return));
    rename($output . ".tmp", $output);
}

if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4])) {
    print "Usage: aggregate.php CODE TIME WINDOW OUTPUT\n";
    print "    CODE - response code column number\n";
    print "    TIME - response time column number\n";
    print "    WINDOW - window size in seconds\n";
    print "    OUTPUT - filename for statistic\n";
    exit;
}
$codeNum = $argv[1];
$timeNum = $argv[2];
$window = $argv[3];
$output = $argv[4];

$stdin = fopen('php://stdin', 'r');
$start = time();
$log = [];
$global = [];
while(1) {
    while ($line = fgets(STDIN)) {
        $current = time();
        if (($current - $start) > $window) {
            writeStat($output, $log, $global);
            while($start < $current) {
                $start += $window;
            }
            $log = [];
        }
        $p = explode(' ', $line);
        if (!isset($log[$p[$codeNum]])) {
            $log[$p[$codeNum]] = [];
        }
        if (!isset($global[$p[$codeNum]])) {
            $global[$p[$codeNum]] = 0;
        }
        $log[$p[$codeNum]][] = (float)$p[$timeNum];
        $global[$p[$codeNum]]++;
    }
}
