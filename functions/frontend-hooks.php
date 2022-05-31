<?php
/**
 * Hooks responsible for everything displayed on frontend
 */

function themeprefix_add_namespaced_body_classes($classes)
{
    if (is_array($classes)) {
        foreach ($classes as $k => $v) {
            $classes[$k] = 'p-' . $v;
        }
    }

    return $classes;
}

add_filter('body_class', 'themeprefix_add_namespaced_body_classes');


function themeprefix_add_slug_to_body_class($classes)
{
    global $post;

    if (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    };

    return $classes;
}

add_filter('body_class', 'themeprefix_add_slug_to_body_class');


// Remove WP emoji code
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');


// Remove Gutenberg default styles
function themeprefix_remove_wp_block_library_css()
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
}

//add_action( 'wp_enqueue_scripts', 'themeprefix_remove_wp_block_library_css', 100 );

function themeprefix_portfolio_ajax()
{
    $args             = array(
        'posts_per_page' => -1,
        'post_type' => 'portfolio',
    );
    $context          = Timber::context();
    $context['posts'] = Timber::get_posts($args, 'CommonPost');

    Timber::render('partials/portfolios.twig', $context);
    die();
}

//add_action('wp_ajax_portfolio', 'themeprefix_portfolio_ajax');
//add_action('wp_ajax_nopriv_portfolio', 'themeprefix_portfolio_ajax');
