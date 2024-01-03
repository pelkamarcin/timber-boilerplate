<?php
/**
 * Theme functions
 * See descriptions in each `require`d file
 */


/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 */

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/sfy-site.php';

if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 5);
}


Timber\Timber::init();

// Sets the directories (inside your theme) to find .twig files.
Timber::$dirname = [ 'templates', 'views' ];

new Site\SfySite();
