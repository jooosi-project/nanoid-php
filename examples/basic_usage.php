<?php
require __DIR__.'/../vendor/autoload.php';

use function Hidehalo\Nanoid\nanoid;

printf("Secure Nano ID: %s\n", nanoid());
printf("10-character ID: %s\n", nanoid(10));
