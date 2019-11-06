<?php
if ( ! defined( 'WP_CLI' ) ) return;
/**
 * Scrub Posts - Remove content older than a given date.
 * @url https://github.com/trepmal/scrub-old-posts
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
	 * : Proccess in batches of <num>. Default: 10
	 *
	 * [--limit=<num>]
	 * : Limited how many post to process. Default: Unlimited
	 *
	 * [--dry-run]
	 * : Dry run. Only tell which posts aren't found.
	 *
	 * [--force]
	 * : Remove all posts are found without confirmation.
	 *
	 * [--log]
	 * : Print log messages.
	 *
	 * ## EXAMPLES
	 *
	 *     wp scrub posts --date='-1 month'
	 *     wp scrub posts --date='2015-01-01'
	 */
	function posts( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );
		$force = isset( $assoc_args['force'] );
		$show_log = isset( $assoc_args['log'] );
		$date = date( 'Y-m-d', strtotime( $assoc_args['date'] ) );
		$ppp = intval( $assoc_args['posts_per_page'] );
		$limit = intval( $assoc_args['limit'] );
		if ( $ppp === 0 ) {
			$ppp = 10;
		}
		if ( $limit > 0 ) {
			$ppp = $limit;
		}
		$post_type = $assoc_args['post_type'];
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}
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
			if ( ! $force && ! $dry_run ) {
				WP_CLI::confirm( sprintf( "Found %d posts (of %d) older than %s. Proceed%s?", $total, $gtotal, $date, ($limit > 0 ? " ({$limit} posts)" : 0) ) );
			}
		} else {
			WP_CLI::line( 'No posts found.' );
			return;
		}
		if ( !$show_log ) {
			$notify = \WP_CLI\Utils\make_progress_bar( sprintf( 'Removing %d post(s)...', $limit ? $limit : $total ), $limit ? $limit : $total );
		}
		if ( $limit > 0 ) {
			$pages = 1;
		}
		for( $i=1; $i<=$pages; $i++ ) {
			if ( $i > 1 ) {
				if ( $dry_run ) {
					$args['paged'] = $i;
				}
				$scrub_query = new WP_Query( $args );
			}
			foreach ( $scrub_query->posts as $post_id ) {
				if ( ! $dry_run ) {
					$this->delete_unattached_attachments( $post_id, $show_log );
					wp_delete_post( $post_id, true );
					if ( $show_log ) {
						WP_CLI::log( sprintf( 'Post %d is removed.', $post_id ) );
					}
				}
				if ( !$show_log ) {
	 				$notify->tick();
	 			}
			}
		}
		if ( !$show_log ) {
			$notify->finish();
		}
	}

	private function delete_unattached_attachments($postID = 0, $show_log = false) {
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'fields' => 'ids',
			'post_parent' => $postID,
		));
		if ($attachments) {
			foreach ($attachments as $idx => $attachmentID){
				// $attachment_path = get_attached_file( $attachmentID );
				// //Delete attachment from database only, not file
				$delete_attachment = wp_delete_attachment($attachmentID, true);
				//Delete attachment file from disk
				// $attachment_path = str_replace('/public_html/wp-content/uploads/', '/shared/uploads/', $attachment_path);
				// $delete_file = unlink( $attachment_path );
				if ( $show_log ) {
					WP_CLI::log( sprintf(' Attachment "%s" is removed.', $attachmentID) );
				}
			}
		}
	}
}
WP_CLI::add_command( 'scrub', 'Scrub_Posts' );