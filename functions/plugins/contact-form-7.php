<?php

define( 'WPCF7_AUTOP', false ); // phpcs:ignore
add_filter( 'wpcf7_autop_or_not', '__return_false' );
