<?php

if ( ! defined( 'WP_CLI' ) ) return;

require_once __DIR__ . '/inc/class-scrub-posts.php';

WP_CLI::add_command( 'scrub', 'Scrub_Posts' );
