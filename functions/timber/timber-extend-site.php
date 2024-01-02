<?php

use Timber\Site;

/**
 * Setup variables that should be available via $context in all the templates
 */
class CurrentTheme extends Site {
    public function __construct() {

        add_filter( 'timber_context', [ $this, 'themeprefix_add_to_context_global' ] );
        add_filter( 'timber_context', [ $this, 'themeprefix_add_to_context_header' ] );
        add_filter( 'timber_context', [ $this, 'themeprefix_add_to_context_footer' ] );

        parent::__construct();
    }

    public function themeprefix_add_to_context_global( $context ) {
        global $common_config;

        $context['common_config'] = $common_config;
        $context['site']          = $this;
        if ( function_exists( 'acf_add_options_page' ) ) {
            $context['lang'] = pll_current_language( 'slug' );
        }

        return $context;
    }

    public function themeprefix_add_to_context_header( $context ) {
        // add menus
        $context['mainmenu'] = Timber::get_menu( 'mainmenu' );

        return $context;
    }

    public function themeprefix_add_to_context_footer( $context ) {
        require_once( get_template_directory() . '/footer.php' );

        return $context;
    }

}

new CurrentTheme();
