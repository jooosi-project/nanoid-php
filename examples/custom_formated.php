<?php
require __DIR__.'/../vendor/autoload.php';

use function Hidehalo\Nanoid\{customAlphabet, customRandom, random};

$alphabet = '0123456789';
$generate = customAlphabet($alphabet, 10);
$customRandomId = customRandom($alphabet, 10, random(...));

printf("Custom alphabet ID: %s\n", $generate());
printf("Custom random ID: %s\n", $customRandomId());
