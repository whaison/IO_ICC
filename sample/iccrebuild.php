<?php

require 'IO/ICC/Editor.php';

if ($argc != 2) {
    echo "Usage: php iccrebuild.php <icc_file>\n";
    echo "ex) php iccrebuild.php test.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC_Editor();

$icc->parse($iccdata);

$icc->rebuild();

echo $icc->build();

exit(0);
