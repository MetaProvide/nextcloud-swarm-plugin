<?php

// Define PHPUNIT_RUN or any test-specific constants if required.
if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

// Define the root directory for Nextcloud server
define('OC_SERVERROOT', __DIR__ . '/../dev-environment/nextcloud_source');

// Load Composer's autoloader.
// If your plugin does not depend on legacy OC classes, you may not need to load lib/base.php.
require_once __DIR__ . '/../vendor/autoload.php';

// (Optional) If your test really needs parts of the core bootstrapping,
// you could include lib/base.php. However, when using OCP and dependency injection,
// this is usually not needed.
require_once OC_SERVERROOT . '/lib/base.php';

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
\OC::$loader->addValidRoot(OC_SERVERROOT . '/tests');

// Fix for "Autoload path not allowed: .../files_external_ethswarm/tests/testcase.php"
\OC_App::loadApp('files_external_ethswarm');

\OC_Hook::clear();
