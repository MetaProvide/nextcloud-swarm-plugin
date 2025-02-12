<?php

// Define PHPUNIT_RUN or any test-specific constants if required.
define('PHPUNIT_RUN', 1);

// Adjust the server root relative to the test directory.
define('OC_SERVERROOT', __DIR__ . '/../');

// Load Composer's autoloader.
// If your plugin does not depend on legacy OC classes, you may not need to load lib/base.php.
require_once OC_SERVERROOT . '/vendor/autoload.php';

// (Optional) If your test really needs parts of the core bootstrapping,
// you could include lib/base.php. However, when using OCP and dependency injection,
// this is usually not needed.
// require_once OC_SERVERROOT . '/lib/base.php';