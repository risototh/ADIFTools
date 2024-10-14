#!/usr/bin/env php
<?php
// https://www.ok2kkw.com/ediformat.htm
require_once 'Utils.php';
require_once 'ADIFParser.php';
require_once 'HamUtils.php';
error_reporting(E_ALL & ~E_WARNING);

$configuration = getConfiguration();

if (!file_exists($configuration['cli']['f'])) {
    die('No ADIF file. Use -f option.');
}

$adif = new ADIFParser(file_get_contents($configuration['cli']['f']));

set_time_limit(1);

echo "\n";

switch ($configuration['cli']['t']) {
    case 'cbpmr':
        echo HamUtils::exportCBPRM($adif, $configuration);
        break;
    case 'sota':
        echo HamUtils::exportSOTA($adif, $configuration);
        break;
    case 'vkvpa':
        echo HamUtils::exportVKVPA($adif, $configuration);
        break;
    case 'pabody':
        echo HamUtils::exportPABody($adif, $configuration);
        break;
    case 'subreg':
        echo HamUtils::exportSubreg($adif, $configuration);
        break;


    default:
        echo "Unknown configuration type '" . $configuration['cli']['t'] . "'. Allowed types: cbpmr, sota, vkvpa, pabody, subreg\n";
}

echo "\n\n";
