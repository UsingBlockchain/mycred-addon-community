<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;
if ( ! defined( 'myCRED_COM_VERSION' ) ) exit;

/**
 * Community Settings
 * Returns the Community add-on settings.
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_community_settings' ) ) :
	function mycred_community_settings() {

		$mycred   = mycred();
		if ( isset( $mycred->community ) )
			$settings = $mycred->community;

		else {

			global $mycred_modules;

			$settings = $mycred_modules['solo']['content']->community;

		}

		return $settings;

	}
endif;
/**
 * Post Type for Share
 * Returns either true (post type is for share) or false (post type not for share).
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_post_type_for_share' ) ) :
	function mycred_post_type_for_share( $post_type = NULL ) {

		$settings = mycred_community_settings();
		$for_share = false;

		if ( array_key_exists( 'post_types', $settings ) && ! empty( $settings['post_types'] ) ) {

			$post_types = explode( ',', $settings['post_types'] );
			if ( in_array( $post_type, $post_types ) )
				$for_share = true;

		}

		return apply_filters( 'mycred_post_type_for_share', $for_share, $post_type );

	}
endif;

/**
 * Post is for Share
 * Returns true (post is for share) or false (post is not for share).
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_post_is_for_share' ) ) :
	function mycred_post_is_for_share( $post = NULL ) {

		if ( ! is_object( $post ) )
			$post = mycred_get_post( $post );

		// Invalid post - not for sale
		if ( ! isset( $post->ID ) ) return false;

		$settings    = mycred_community_settings();
		$point_types = $settings['type'];
		$for_share   = false;

		// We start with checking the post type.
		if ( mycred_post_type_for_share( $post->post_type ) && array_key_exists( $post->post_type, $settings['filters'] ) ) {

			$filter = $settings['filters'][ $post->post_type ]['by'];
			$list   = explode( ',', $settings['filters'][ $post->post_type ]['list'] );

			// Manual filter - check saved settings
			if ( $filter === 'manual' ) {

				// Loop through each point type we allow and check the settings to see if anyone is enabled
				foreach ( $point_types as $type_id ) {

					$suffix = '_' . $type_id;
					if ( $type_id == MYCRED_DEFAULT_TYPE_KEY )
						$suffix = '';

					$sale_setup = (array) mycred_get_post_meta( $post->ID, 'myCRED_community_social_share' . $suffix, true );
					if ( array_key_exists( 'status', $sale_setup ) && $sale_setup['status'] === 'enabled' )
						$for_share = true;

				}

			}

			// All posts for share
			elseif ( $filter === 'all' ) {

				$for_share = true;

			}

			// Posts are set for share but some are excluded
			elseif ( $filter === 'exclude' ) {

				// If post is not excluded, it is for share
				if ( ! in_array( $post->ID, $list ) )
					$for_share = true;

			}

			// Posts are not for share but some are
			elseif ( $filter === 'include' ) {

				// If post is included, it is for share
				if ( in_array( $post->ID, $list ) )
					$for_share = true;

			}

			// Taxonomy check
			else {

				$check    = 'include';
				$taxonomy = $filter;

				if ( substr( $taxonomy, 0, 1 ) === '-' ) {
					$check    = 'exclude';
					$taxonomy = ltrim( $taxonomy );
				}

				// Get post terms
				$terms    = wp_get_post_terms( $post->ID, $taxonomy );

				// Taxonomy exclude check
				if ( $check === 'exclude' ) {

					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {

							if ( in_array( $term->slug, $list ) ) continue;
							$for_share = true;

						}
					}

					// No terms - not excluded
					else {
						$for_share = true;
					}

				}

				// Taxonomy include check
				else {

					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {

							if ( ! in_array( $term->slug, $list ) ) continue;
							$for_share = true;

						}
					}

				}

			}

		}

		return apply_filters( 'mycred_post_is_for_share', $for_share, $post, $settings );

	}
endif;

/**
 * Get Expiration Length
 * @since 1.7.9.1
 * @version 1.0
 */
if ( ! function_exists( 'mycred_community_social_share_get_expiration_length' ) ) :
	function mycred_community_social_share_get_expiration_length( $post_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$length      = 0;
		if ( $post_id === NULL ) return $length;

		$settings    = mycred_community_settings();
		$post        = mycred_get_post( $post_id );
		$point_types = $settings['type'];
		$has_expired = false;

		// Invalid post
		if ( ! isset( $post->ID ) ) return $length;

		$filter = $settings['filters'][ $post->post_type ]['by'];

		// Manual mode - expiration settings are found in the post setting
		if ( $filter === 'manual' ) {

			$suffix = '_' . $type;
			if ( $type == MYCRED_DEFAULT_TYPE_KEY )
				$suffix = '';

			$sale_setup = (array) mycred_get_post_meta( $post->ID, 'myCRED_community_social_share' . $suffix, true );
			if ( ! empty( $sale_setup ) && array_key_exists( 'expire', $sale_setup ) && $sale_setup['expire'] > 0 )
				$length = $sale_setup['expire'];

		}

		// Else we need to check the point type setup in our add-on settings.
		else {

			$point_type_setup = (array) mycred_get_option( 'mycred_community_share_this_' . $type );
			if ( ! empty( $point_type_setup ) && array_key_exists( 'expire', $point_type_setup ) && $point_type_setup['expire'] > 0 )
				$length = $point_type_setup['expire'];

		}

		return apply_filters( 'mycred_community_social_share_expiration', $length, $post );

	}
endif;

/**
 * User Has Paid
 * Checks if a user has paid for the given post. Will also take into account
 * if a purchase has expired (if used).
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_user_shared_content' ) ) :
	function mycred_user_shared_content( $user_id = NULL, $post_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		global $wpdb, $mycred_log_table;

		$has_shared = false;
		$user_id  = absint( $user_id );
		$post_id  = absint( $post_id );
		$account  = mycred_get_account( $user_id );
		$expires  = mycred_community_social_share_get_expiration_length( $post_id, $point_type );

		// No expirations
		if ( $expires == 0 ) {

			// The history object should have a record of our payment for a quick check without the need to run the below db query
			if ( ! empty( $account->point_types ) && in_array( $point_type, $account->point_types ) && isset( $account->balance[ $point_type ]->history ) ) {

				$data = $account->balance[ $point_type ]->history->get( 'data' );
				if ( array_key_exists( 'share_content', $data ) && ! empty( $data['share_content']->reference_ids ) && in_array( $post_id, $data['share_content']->reference_ids ) )
					$has_shared = true;

			}

		}

		$last_payment = '';

		if ( ! $has_shared ) {

			$last_share = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$mycred_log_table} WHERE user_id = %d AND ref = 'share_content' AND ref_id = %d ORDER BY time DESC LIMIT 1;", $user_id, $post_id ) );

			// Found a payment
			if ( $last_share !== NULL ) {

				$has_shared = true;

			}

		}

		// All else there are no purchases
		return apply_filters( 'mycred_user_has_shared_content', $has_shared, $user_id, $post_id, $last_share );

	}
endif;
/**
 * New Social Share
 * Handles the social share of a particular post by a given user.
 * @returns true (bool) on success or an error message (string)
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_community_new_social_share' ) ) :
	function mycred_community_new_social_share( $post = NULL, $user_id = NULL, $platform = NULL ) {

		if ( ! is_object( $post ) )
			$post = mycred_get_post( $post );

		if ( ! isset( $post->ID ) ) return false;

		$mycred   = mycred( MYCRED_DEFAULT_TYPE_KEY );
		$settings = mycred_community_settings();
		$result   = apply_filters( 'mycred_community_before_social_share', false, $post->ID, $user_id, $platform );

		// We handle shares
		if ( $result === false ) {

			// Disabled point type or user is excluded.
			if ( $mycred->exclude_user( $user_id ) )
				$result = sprintf( _x( 'You are not elligible for Social Network Rewards of %s', 'Point type name', 'mycred-community' ), $mycred->plural() );

			else {

				// Need a unique transaction id
				$share_id = 'SHAREID-' . $user_id . ':' . $platform . '-' . current_time( 'timestamp' ) . '-' . $post->ID;

				$shares_list = get_post_meta($post->ID, '_mycred_community_post_social_shares_list');
				$shares_log = get_post_meta($post->ID, '_mycred_community_post_social_shares_log');
				
				// Add user to list of sharers
				$shares_list .= ',' . $user_id . ':' . $platform;
				$shares_log  .= ',' . $share_id; 

				update_post_meta($post->ID, '_mycred_community_post_social_shares_list', $shares_list);
				update_post_meta($post->ID, '_mycred_community_post_social_shares_log', $share_log);

				$result = true;

			}

		}

		return apply_filters( 'mycred_community_after_social_share', $result, $post, $user_id, $platform );

	}
endif;
