<?php
/**
 * Plugin Name: myCred Community
 * Plugin URI: https://github.com/UsingBlockchain/mycred-addon-community
 * Description: A community add-on for myCRED: the adaptive and powerful points management system for WordPress.
 * Version: 0.1.0
 * Tags: badges, gamification, loyalty, points, rewards, social, networking, surveys, polls
 * Author: Using Blockchain Ltd
 * Author URI: https://ubc.digital
 * Author Email: info@ubc.digital
 * Requires at least: WP 4.8
 * Tested up to: WP 5.4.2
 * Text Domain: mycred-community
 * Domain Path: /lang
 * License: 3-Clause BSD
 * License URI: https://opensource.org/licenses/BSD-3-Clause
 */
/**
 * Addon: Community
 * Addon URI: https://github.com/UsingBlockchain/mycred-addon-community
 * Version: 0.1
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_COM',         __FILE__ );
define( 'myCRED_COM_VERSION', '0.1' );

/**
 * myCRED_Community_Module class
 * @since 0.1
 * @version 0.1
 */
if ( ! class_exists( 'myCRED_Community_Module' ) ) :
	class myCRED_Community_Module extends myCRED_Module {

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Community_Module', array(
				'module_name' => 'community',
				'defaults'    => array(
					'template'  => '<p>%entry%</p><h1>%cred_f%</h1>',
					'use_css'   => 1,
				),
				'register'    => false,
				'add_to_core' => true
			) );
			
			add_filter( 'mycred_add_finished', array( $this, 'mycred_finished' ), 40, 3 );

		}

		/**
		 * Module Init
		 * @since 0.1
		 * @version 0.1
		 */
		public function module_init() {

			if ( ! is_user_logged_in() ) return;

			add_action( 'mycred_front_enqueue', array( $this, 'register_assets' ), 20 );
			add_action( 'wp_footer',            array( $this, 'setup_social_shares' ), 1 );

		}

		/**
		 * Register Assets
		 * @since 0.1
		 * @version 1.1
		 */
		public function register_assets() {

			// Register script
			wp_register_script(
				'mycred-community',
				plugins_url( 'assets/js/social-shares.js', myCRED_COM ),
				array( 'jquery' ),
				myCRED_COM_VERSION . '.2',
				true
			);

			// Localize
			wp_localize_script(
				'mycred-community',
				'myCRED_Community',
				array(
					'ajaxurl'  => admin_url( 'admin-ajax.php' )
				)
			);
			wp_enqueue_script( 'mycred-community' );

			// If not disabled, enqueue the stylesheet
			if ( $this->community['use_css'] == 1 ) {

				wp_register_style(
					'mycred-community',
					plugins_url( 'assets/css/social-shares.css', myCRED_COM ),
					false,
					myCRED_COM_VERSION . '.2',
					'all',
					true
				);

				wp_enqueue_style( 'mycred-community' );

			}

		}

		/**
		 * myCRED Finished
		 * @since 1.6
		 * @version 1.0
		 */
		public function mycred_finished( $reply, $request, $mycred ) {

			if ( $reply === false || $this->community['template'] == '' ) return $reply;

			// Parse template
			$template = str_replace( '%entry%', $request['entry'], $this->community['template'] );
			$template = str_replace( '%amount%', $request['amount'], $template );

			// Attempt to parse the template tags now that we have the entire request.
			// This way we just need to display it and we are done.
			$template = $mycred->template_tags_amount( $template, $request['amount'] );
			$template = $mycred->parse_template_tags( $template, $this->request_to_entry( $request ) );

			// Let others play
			$template = apply_filters( 'mycred_community_social_share', $template, $request, $mycred );

			// If template is not empty, add it now.
			if ( strlen( $template ) > 0 )
				mycred_add_social_share( array( 'user_id' => $request['user_id'], 'share' => $template ) );

			return $reply;

		}

		/**
		 * Setup social shares
		 * @since 0.1
		 * @version 0.1
		 */
		public function setup_social_shares() {

			$user_id = get_current_user_id();
			$data    = get_transient( 'mycred_community_social_share_' . $user_id );

			if ( $data === false || ! is_array( $data ) ) return;

			foreach ( $data as $share )

        add_filter( 'mycred_community_social_share', function ($query) use ($share){ $query[]= $share ; return $query; }  );


      delete_transient( 'mycred_community_social_share_' . $user_id );

		}

		/**
		 * Settings Page
		 * @since 0.1
		 * @version 1.2
		 */
		public function after_general_settings( $mycred = NULL ) {

			$prefs = $this->community;

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><?php _e( 'Community', 'mycred-community' ); ?></h4>
<div class="body" style="display:none;">

	<h3><?php _e( 'Setup', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( 'template' ); ?>"><?php _e( 'Template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( 'template' ); ?>" id="<?php echo $this->field_id( 'template' ); ?>" value="<?php echo esc_attr( $prefs['template'] ); ?>" class="form-control" />
				<p><span class="description"><?php _e( 'Use %entry% to show the log entry in the notice and %amount% for the amount.', 'mycred' ); ?></span> <a href="javascript:void(0);" id="restore-defaults-community"><?php _e( 'Restore to default', 'mycred' ); ?></a></p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<div class="checkbox">
					<label for="<?php echo $this->field_id( 'use_css' ); ?>"><input type="checkbox" name="<?php echo $this->field_name( 'use_css' ); ?>" id="<?php echo $this->field_id( 'use_css' ); ?>" <?php checked( $prefs['use_css'], 1 ); ?> value="1" /> <?php _e( 'Use the included CSS Styling for notifications.', 'mycred' ); ?></label>
				</div>
			</div>
		</div>
	</div>

</div>
<script type="text/javascript">
jQuery(function($) {

	$( '#restore-defaults-community' ).click(function(){
		$( '#<?php echo $this->field_id( 'template' ); ?>' ).val( '<?php echo $this->default_prefs['template']; ?>' );
	});

});
</script>
<?php

		}

		/**
		 * Sanitize & Save Settings
		 * @since 0.1
		 * @version 1.1
		 */
		public function sanitize_extra_settings( $new_data, $data, $general ) {

			$new_data['notifications']['use_css']  = ( isset( $data['notifications']['use_css'] ) ) ? 1: 0;
			$new_data['notifications']['template'] = wp_kses( $data['notifications']['template'], $this->core->allowed_html_tags() );

			return $new_data;

		}
	}
endif;

/**
 * Load Notifications Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_community_addon' ) ) :
	function mycred_load_community_addon( $modules, $point_types ) {

		$modules['solo']['community'] = new myCRED_Community_Module();
		$modules['solo']['community']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_community_addon', 120, 2 );

/**
 * Add Notice
 * @since 0.1
 * @version 1.0
 */
if ( ! function_exists( 'mycred_add_social_share' ) ) :
	function mycred_add_social_share( $community = array() ) {

		// Minimum requirements
		if ( ! isset( $community['user_id'] ) || ! isset( $community['message'] ) ) return false;

			// Get transient
		$data = get_transient( 'mycred_community_social_share_' . $community['user_id'] );

		// If none exists create a new array
		if ( $data === false || ! is_array( $data ) )
			$shares = array();
		else
			$shares = $data;

		// Add new notice
		$shares[] = addslashes( $community['share'] );

		// Save as a transient
		set_transient( 'myCRED_Community_' . $community['user_id'], $shares, 86400*42 );

	}
endif;
