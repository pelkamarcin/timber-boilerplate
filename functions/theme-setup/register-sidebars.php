<?php
/**
 * Sidebar registration
 */

function themeprefix_widgets_init() {
    //    register_sidebar(array(
    //                         'name' => __('Post footer', 'themeprefix'),
    //                         'id' => 'post_footer',
    //                         'description' => __('Content below every post', 'themeprefix'),
    //                         'before_widget' => '<div id="%1$s" class="c-widget %2$s">',
    //                         'after_widget' => '</div>',
    //                         'before_title' => '<h2 class="c-widget__title">',
    //                         'after_title' => '</h2>',
    //                     ));
}

add_action( 'widgets_init', 'themeprefix_widgets_init' );
