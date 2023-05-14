<?php

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 *
 * For more information on $sites, read the documentation included in the
 * example.sites.php that comes with the Drupal Core.
 */

use Drall\Service\SiteDetector;

$sites = SiteDetector::detectFromDirectory(__DIR__);
