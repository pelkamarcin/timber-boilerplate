<?php
/**
 * Functions (see frontend-hooks.php for hooks) related to everything displayed on frontend
 */


function themeprefix_excerpt_function( $post_content, $search_phrase ) {
  $pos     = 0;
  $cleaned = strip_tags( $post_content );
  if ( false !== strpos( $post_content, $search_phrase ) ) {
    $pos = strpos( $cleaned, $search_phrase );
  }
  $found = substr( $cleaned, $pos, 400 );
  echo ( 0 === $pos ? '' : '...' ) . $found . ' ...';

}
