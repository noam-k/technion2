<?php


$calculation = 'x > 5 && x < 10';

$calculation = str_replace('x', '$res', $calculation);

$calculation = 'if ('.$calculation.') return 1; return 0;';

echo eval($calculation);
