<?php

namespace Site;

class SfyThemeSupport {
    public function __construct() {
        $this->addSupports();
        add_action( 'after_setup_theme', [ $this, 'loadTextDomain' ] );
        add_filter( 'upload_mimes', [ $this, 'mime_types' ] );
    }

    private function addSupports() {
        /*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
        add_theme_support( 'post-thumbnails' );

        add_theme_support( 'menus' );
        add_theme_support( 'widgets' );
        add_theme_support( 'custom-header' );
        /*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
        add_theme_support( 'title-tag' );
        /*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */

        add_theme_support( 'html5', [ 'comment-form',
                                      'comment-list',
                                      'gallery',
                                      'caption', ] );

        /*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
        add_theme_support(
            'post-formats',
            array(
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'audio',
            )
        );


    }

    public function loadTextDomain() {
        load_theme_textdomain( 'sfy', get_template_directory() . '/languages' );
    }


    public function mime_types( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';

        return $mimes;
    }
}
