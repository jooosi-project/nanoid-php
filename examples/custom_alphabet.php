<?php
require __DIR__ . '/../vendor/autoload.php';

use function Hidehalo\Nanoid\{customAlphabet, customRandom, random};

$hexId = customAlphabet('0123456789abcdef', 12);
$customRandomId = customRandom('abcdef', 10, random(...));

printf("12-character hexadecimal ID: %s\n", $hexId());
printf("6-character hexadecimal ID: %s\n", $hexId(6));
printf("Custom random-source ID: %s\n", $customRandomId());
