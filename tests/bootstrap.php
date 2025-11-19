<?php

/**
 * PHPUnit bootstrap file for WordPress Reviews library tests.
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load Brain\Monkey
require_once dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php';

// Load the class being tested
require_once dirname(__DIR__) . '/ReviewsController.php';
