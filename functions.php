<?php
/**
 * Theme functions
 * See descriptions in each `require`d file
 */
require_once __DIR__ . '/vendor/autoload.php';
Timber\Timber::init();

$functions_path = get_template_directory() . '/functions/';

// Development functions
$dev_functions_file_path = $functions_path . 'dev-functions.php';
if ( file_exists( $dev_functions_file_path ) ) {
    require_once( $dev_functions_file_path );
}

// Obtain and setup shared variables
require_once( $functions_path . 'common-variables.php' );

// Timber setup
require_once( $functions_path . 'timber/timber-setup.php' );
require_once( $functions_path . 'timber/extend-twig.php' );
require_once( $functions_path . 'timber/timber-extend-site.php' );

// Client side PHP data (script localization, AJAX, etc.)
require_once( $functions_path . 'script-data/localized-strings.php' );
require_once( $functions_path . 'script-data/script-data.php' );

// Basic theme setup
require_once( $functions_path . 'theme-setup/theme-support.php' );
require_once( $functions_path . 'theme-setup/register-post-types.php' );
require_once( $functions_path . 'theme-setup/register-taxonomies.php' );
require_once( $functions_path . 'theme-setup/register-sidebars.php' );
require_once( $functions_path . 'theme-setup/register-scripts.php' );
require_once( $functions_path . 'theme-setup/register-menus.php' );
require_once( $functions_path . 'theme-setup/register-thumbnail-sizes.php' );

// Custom theme functions and hooks
require_once( $functions_path . 'frontend-hooks.php' );
require_once( $functions_path . 'frontend-functions.php' );
require_once( $functions_path . 'admin-hooks.php' );
require_once( $functions_path . 'blocks.php' );
require_once( $functions_path . 'shortcodes.php' );

// Plugins dependent functions and hooks
require_once( $functions_path . 'plugins/custom-fields.php' );
require_once( $functions_path . 'plugins/acf.php' );
require_once( $functions_path . 'plugins/polylang.php' );
require_once( $functions_path . 'plugins/contact-form-7.php' );
require_once( $functions_path . 'plugins/plugin.php' );

// Alter main query for specific templates (aka pre_get_posts)
require_once( $functions_path . 'alter-the-loop/alter-archive-post-type.php' );
