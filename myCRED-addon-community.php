<?php
/**
 * Addon: Community
 * Addon URI: http://github.com/UsingBlockchain
 * Version: 0.1.0
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_COM',         __FILE__ );
define( 'myCRED_COM_VERSION', '0.1.0' );
define( 'myCRED_COM_DIR',          dirname(myCRED_COM) . '/' );
define( 'myCRED_COM_ASSETS_DIR',   myCRED_COM_DIR . 'assets/' );
define( 'myCRED_COM_INCLUDES_DIR', myCRED_COM_DIR . 'includes/' );
define( 'myCRED_COM_HOOKS_DIR', myCRED_COM_INCLUDES_DIR . 'hooks/' );
define( 'myCRED_COM_HANDLERS_DIR', myCRED_COM_INCLUDES_DIR . 'handlers/' );

require_once myCRED_COM_INCLUDES_DIR . 'mycred-community-functions.php';
require_once myCRED_COM_HOOKS_DIR . 'mycred-community-hook-social-share.php';

// include all handlers
require_once myCRED_COM_HANDLERS_DIR . 'mycred-community-handler-share.php';

/**
 * myCRED_Community_Module class
 * @since 0.1
 * @version 0.1
 */
if ( ! class_exists( 'myCRED_Community_Module' ) ) :
	class myCRED_Community_Module extends myCRED_Module {

		public $current_user_id = 0;
		public $priority        = 10;

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Community_Module', array(
				'module_name' => 'community',
				'screen_id'   => 'myCRED_page_community',
				'defaults'    => array(
					'template'  => '<p>%entry%</p><h1>%cred_f%</h1>',
				),
				'register'    => false,
				'add_to_core' => true
			) );

			if ( ! is_array( $this->community['type'] ) )
				$this->community['type'] = array( $this->community['type'] );

			add_action( 'mycred_help', array($this, 'help'), 10, 2);
		}

		/**
		 * Module Init
		 * @since 0.1
		 * @version 0.1
		 */
		public function module_init() {

			if ( ! is_user_logged_in() ) return;

			$this->current_user_id = get_current_user_id();
			$this->priority        = apply_filters( 'mycred_community_priority', 25, $this );

			// Setup Content Override
			add_action( 'template_redirect',    array( $this, 'template_redirect' ), 99990 );

			// Setup frontend assets
			add_action( 'mycred_front_enqueue', array( $this, 'register_assets' ), 20 );

		}

		/**
		 * Setup Content Filter
		 * We are using the template_redirect action to prevent this add-on having to run anywhere else but
		 * in the front-end of our website, since the the_content filter is used in soooo many places.
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function template_redirect() {

			// Handle share requests
			myCRED_Community_Handler_Share::maybe_share_content();

		}

		/**
		 * Register Assets
		 * @since 0.1
		 * @version 1.1
		 */
		public function register_assets() {

			global $post;

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
				'myCREDCommunitySocialShare',
				array(
					'ajaxurl'    => esc_url( ( isset( $post->ID ) ) ? mycred_get_permalink( $post->ID ) : home_url( '/' ) ),
					'token'      => wp_create_nonce( 'mycred-community-social-share' ),
				)
			);
			wp_enqueue_script( 'mycred-community' );

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

			$new_data['community']['use_css']  = ( isset( $data['community']['use_css'] ) ) ? 1: 0;
			$new_data['community']['template'] = wp_kses( $data['community']['template'], $this->core->allowed_html_tags() );

			return $new_data;

		}

		/**
		 * Contextual Help
		 * @since 0.1
		 * @version 1.0
		 */
		public function help( $screen_id, $screen ) {
			if ( $screen_id != 'mycred_page_myCRED_page_community' ) return;

			$screen->add_help_tab( array(
				'id'		=> 'mycred-community',
				'title'		=> __( 'Community', 'mycred-community' ),
				'content'	=> '
<p>' . $this->core->template_tags_general( __( 'This add-on lets you award %_plural% by inspecting social network actions for your content.', 'mycred-community' ) ) . '</p>'
			) );
		}
	}
endif;

/**
 * Load Community Module
 * @since 0.1
 * @version 0.1
 */
if ( ! function_exists( 'mycred_load_community_addon' ) ) :
	function mycred_load_community_addon( $modules, $point_types ) {

		$modules['solo']['community'] = new myCRED_Community_Module();
		$modules['solo']['community']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_community_addon', 120, 2 );
