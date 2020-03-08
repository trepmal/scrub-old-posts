<?php
/**
 * Scrub Posts
 */
class Scrub_Posts extends WP_CLI_Command {

	/**
	 * Scrub posts
	 *
	 * ## OPTIONS
	 *
	 * --date=<date>
	 * : Delete posts older than this date.
	 *
	 * [--post_type=<post_type>]
	 * : Post type. Default: post
	 *
	 * [--posts_per_page=<num>]
	 * : Process in batches of <num>. Default: 100
	 *
	 * [--dry-run]
	 * : Dry run. Only tell which images aren't found.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     wp scrub posts --date='-1 month'
	 *     wp scrub posts --date='2015-01-01'
	 */
	function posts( $args, $assoc_args ) {

		$dry_run = isset( $assoc_args['dry-run'] );
		unset( $assoc_args['dry-run'] );

		$date = date( 'Y-m-d', strtotime( $assoc_args['date'] ) );

		$defaults = array(
			'posts_per_page' => 100,
			'post_type'      => 'post',
		);
		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$post_type = $assoc_args['post_type'];
		$ppp       = min( 300, max( 1, intval( $assoc_args['posts_per_page'] ) ) );

		$gtotal = wp_count_posts( $post_type )->publish;
		$args = array(
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'posts_per_page'         => $ppp,
			'post_type'              => $post_type,
			'date_query'             => array(
				array(
					'before' => $date,
				),
			),
		);

		$scrub_query = new WP_Query( $args );
		$pages = $scrub_query->max_num_pages;
		$total = $scrub_query->found_posts;
		$args['no_found_rows'] = true;

		if ( $total > 0 ) {
			WP_CLI::confirm( sprintf( "Found %d posts (of %d) older than %s. Proceed?", $total, $gtotal, $date ) );
		} else {
			WP_CLI::line( 'No posts found' );
			return;
		}

		$notify = \WP_CLI\Utils\make_progress_bar( sprintf( 'Removing %d post(s)', $total ), $total );

		for( $i=1; $i<=$pages; $i++ ) {

			if ( $i > 1 ) {
				if ( $dry_run ) {
					$args['paged'] = $i;
				}
				$scrub_query = new WP_Query( $args );
			}


			foreach ( $scrub_query->posts as $post_id ) {
				if ( ! $dry_run ) {
					$result = wp_delete_post( $post_id, true );

					if ( is_wp_error( $result ) ) {
						$m = $result->get_error_message();
						WP_CLI::debug( WP_CLI::colorize( "%rError: [post id: $post_id] $m%n" ) );
					} elseif ( ! $result ) {
						WP_CLI::debug( WP_CLI::colorize( "%rError: [post id: $post_id] unknown%n" ) );
					} else {
						WP_CLI::debug( "[$post_id] deleted");
					}
				}
 				$notify->tick();
			}

		}

		$notify->finish();

	}

}