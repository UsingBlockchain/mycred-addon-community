<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;
if ( ! defined( 'myCRED_COM_VERSION' ) ) exit;

/**
 * myCRED_Community_Handler_Share class
 * @since 0.1
 * @version 0.1
 */
if ( ! class_exists( 'myCRED_Community_Handler_Share' ) ) :
	class myCRED_Community_Handler_Share {
		/**
		 * Maybe Share Content
		 * Check if a share request has been made via an AJAX submission.
		 * @since 0.1
		 * @version 0.1
		 */
		public static function maybe_share_content() {

			if ( is_user_logged_in() && ! mycred_is_admin() ) {

				if ( isset( $_POST['action'] ) && $_POST['action'] == 'mycred-community-social-share' && isset( $_POST['postid'] ) && isset( $_POST['token'] ) && wp_verify_nonce( $_POST['token'], 'mycred-community-social-share' ) ) {

					$post_id    = absint( $_POST['postid'] );
					$platform   = sanitize_key( $_POST['platform'] );

					if ( mycred_force_singular_session( $this->current_user_id, 'mycred-last-content-share' ) )
						wp_send_json( 'ERROR' );

					// If the content is for share and we have not shared it
					if ( mycred_post_is_shareable( $post_id ) && ! mycred_user_shared_content( $this->current_user_id, $post_id ) ) {

						$content  = '';
						$post     = mycred_get_post( $post_id );
						$share = mycred_community_new_social_share( $post, $this->current_user_id, $platform );

						// Successfull share
						if ( $share === true ) {

							$content = 'SUCCESS';

						}

						// Something went wrong
						else {

							$content = $share;

						}

						// Let others play
						$content = apply_filters( 'mycred_community_social_share_ajax', $content, $share );

						if ( $share !== true )
							wp_send_json_error( $content );
						else
							wp_send_json_success( $content );

					}

					wp_send_json( 'ERROR' );

				}

			}

		}

	}
    
endif;
