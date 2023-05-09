<?php
declare(strict_types=1);

include __DIR__ . '/vendor/autoload.php';

//Let's open that box...
\NWO\Pandora\Box::Open(
    "Token",
    ["Channel IDs to listen on"],
    ["User IDs of admins"],
    //Client file.
    __FILE__,
    //External Command directory.
    __DIR__ . DIRECTORY_SEPARATOR . "Commands"
);