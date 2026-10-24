<?php
require __DIR__ . '/../vendor/autoload.php';

use function Hidehalo\Nanoid\NonSecure\{customAlphabet, nanoid};

// Non-secure IDs use mt_rand() and are not suitable for secrets or tokens.
$id = nanoid();
$shortId = nanoid(10);
$numericId = customAlphabet('0123456789', 8);

printf("Non-secure URL-friendly ID: %s\n", $id);
printf("Non-secure 10-character ID: %s\n", $shortId);
printf("Non-secure numeric ID: %s\n", $numericId());
