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
	 * : Post type.
	 * ---
	 * default: post
	 * ---
	 *
	 * [--posts_per_page=<num>]
	 * : Process in batches of <num>.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--dry-run]
	 * : Dry run.
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

		$dry_run   = isset( $assoc_args['dry-run'] );
		$date      = strtotime( $assoc_args['date'] );
		$post_type = $assoc_args['post_type'];
		$ppp       = min( 500, max( 1, intval( $assoc_args['posts_per_page'] ) ) );

		if ( ! get_post_type_object( $post_type ) ) {
			WP_CLI::error( 'Invalid post type.' );
		}
		if ( ! $date ) {
			WP_CLI::error( 'Invalid date.' );
		}

		// Only dealing with 'publish' right now
		$gtotal = wp_count_posts( $post_type )->publish;
		$args = array(
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'posts_per_page'         => $ppp,
			'post_type'              => $post_type,
			'date_query'             => array(
				array(
					'before' => date( 'Y-m-d', $date ),
				),
			),
		);

		$scrub_query = new WP_Query( $args );
		$pages = $scrub_query->max_num_pages;
		$total = $scrub_query->found_posts;
		$args['no_found_rows'] = true;

		if ( $total > 0 ) {
			WP_CLI::confirm( sprintf(
				"%sFound %d posts (of %d) older than %s. Proceed?",
				$dry_run ? '[Dry run] ' : '',
				$total,
				$gtotal,
				date( 'Y-m-d', $date )
			) );
		} else {
			WP_CLI::line( 'No posts found.' );
			return;
		}

		$notify = \WP_CLI\Utils\make_progress_bar( sprintf(
			'%s %d post(s)',
			$dry_run ? 'Would remove' : 'Removing',
			$total
		), $total );

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
						WP_CLI::debug( WP_CLI::colorize( sprintf( '%rError: [post id: %d] %d%n', $post_id, $m ) ) );
					} elseif ( ! $result ) {
						WP_CLI::debug( WP_CLI::colorize( sprintf( '%rError: [post id: %d] unknown%n', $post_id ) ) );
					} else {
						WP_CLI::debug( sprintf( '[%d] deleted', $post_id ) );
					}
				}
 				$notify->tick();
			}

		}

		$notify->finish();

	}

}