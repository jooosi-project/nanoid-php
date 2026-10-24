<?php
require __DIR__ . '/../vendor/autoload.php';

use function Hidehalo\Nanoid\nanoid;

$id = nanoid();
$shortId = nanoid(10);

printf("Secure URL-friendly ID: %s\n", $id);
printf("Secure 10-character ID: %s\n", $shortId);
