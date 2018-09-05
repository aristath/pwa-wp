<?php
/**
 * WP_Service_Worker_Custom_Header_Integration class.
 *
 * @package PWA
 */

/**
 * Class representing the Custom Header service worker integration.
 *
 * @since 0.2
 */
class WP_Service_Worker_Custom_Header_Integration extends WP_Service_Worker_Base_Integration {

	/**
	 * Registers the integration functionality.
	 *
	 * @since 0.2
	 *
	 * @param WP_Service_Worker_Registry $registry Instance to register service worker behavior with.
	 */
	public function register( WP_Service_Worker_Registry $registry ) {
		if ( ! current_theme_supports( 'custom-header' ) || ! get_custom_header() ) {
			return;
		}

		if ( is_random_header_image() ) {
			// What follows comes fom _get_random_header_data().
			global $_wp_default_headers;
			$header_image_mod = get_theme_mod( 'header_image', '' );

			$headers = array();
			if ( 'random-uploaded-image' === $header_image_mod ) {
				$headers = get_uploaded_header_images();
			} elseif ( ! empty( $_wp_default_headers ) ) {
				if ( 'random-default-image' === $header_image_mod ) {
					$headers = $_wp_default_headers;
				} else {
					if ( current_theme_supports( 'custom-header', 'random-default' ) ) {
						$headers = $_wp_default_headers;
					}
				}
			}

			foreach ( $headers as $header ) {
				$url      = sprintf( $header['url'], get_template_directory_uri(), get_stylesheet_directory_uri() );
				$file     = wp_service_workers()->get_validated_file_path( get_header_image() );
				$revision = null;
				if ( is_string( $file ) ) {
					$revision = md5( file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				}
				$registry->register_precached_route( $url, $revision );
			}
			return;
		}

		$header     = get_custom_header();
		$attachment = ! empty( $header->attachment_id ) ? get_post( get_custom_header()->attachment_id ) : null;

		if ( $attachment ) {
			foreach ( $this->get_attachment_image_urls( $attachment->ID, array( $header->width, $header->height ) ) as $image_url ) {
				$registry->register_precached_route( $image_url, $attachment->post_modified );
			}
		} elseif ( get_header_image() ) {
			$url      = get_header_image();
			$file     = wp_service_workers()->get_validated_file_path( get_header_image() );
			$revision = null;
			if ( is_string( $file ) ) {
				$revision = md5( file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
			$registry->register_precached_route( $url, $revision );
		}
	}
}
