<?php

// Vercel entry point: every non-static request is routed here (see vercel.json)
// and handed to Symfony's normal front controller.
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/../public/index.php';
