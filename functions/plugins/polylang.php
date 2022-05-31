<?php

// Make sure Polylang copies the content when creating a translation
function themeprefix_polylang_editor_content($content)
{
    // Polylang sets the 'from_post' parameter
    if (isset($_GET['from_post'])) {
        $my_post = get_post($_GET['from_post']);
        if ($my_post) {
            return $my_post->post_content;
        }
    }

    return $content;
}

add_filter('default_content', 'themeprefix_polylang_editor_content');


// Make sure Polylang copies the title when creating a translation
function themeprefix_polylang_editor_title($title)
{
    // Polylang sets the 'from_post' parameter
    if (isset($_GET['from_post'])) {
        $my_post = get_post($_GET['from_post']);
        if ($my_post) {
            return $my_post->post_title;
        }
    }

    return $title;
}

add_filter('default_title', 'themeprefix_polylang_editor_title');
