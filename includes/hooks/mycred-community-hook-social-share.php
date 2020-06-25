<?php
if ( ! defined( 'myCRED_COM_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 0.1
 * @version 0.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_community_hooks', 15 );
function mycred_register_community_hooks( $installed ) {

	$installed['community'] = array(
		'title'         => __( '%plural% for social shares', 'mycred-community' ),
		'description'   => __( 'Award c\'s for sharing content on social networks.', 'mycred-community' ),
		'documentation' => '#',
		'callback'      => array( 'myCRED_Community_Hook_Social_Share' )
	);

	return $installed;

}

/**
 * Community Social Share Hook
 * @since 0.1
 * @version 0.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_community_social_share_hook', 15 );
function mycred_load_community_social_share_hook() {

	// If the hook has been replaced, exit now
	if ( class_exists( 'myCRED_Community_Hook_Social_Share' ) ) return;

/**
 * Hook for sharing content on social networks
 * @since 0.1
 * @version 0.1
 */
	class myCRED_Community_Hook_Social_Share extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			$defaults = array(
				'post'    => array(
					'creds'  => 1,
					'log'    => '%plural% for sharing a Post on social networks',
					'limit'  => '0/x'
				),
				'page'    => array(
					'creds'  => 1,
					'log'    => '%plural% for sharing a Page on social networks',
					'limit'  => '0/x'
				)
			);

			if ( isset( $hook_prefs['social_sharing'] ) )
				$defaults = $hook_prefs['social_sharing'];

			parent::__construct( array(
				'id'       => 'social_sharing',
				'defaults' => $defaults
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 0.1
		 * @version 0.1
		 */
		public function run() {

			add_action( 'updated_post_meta', array( $this, 'social_sharing' ), 10, 4 );
			add_action( 'updated_page_meta', array( $this, 'social_sharing' ), 10, 4 );

			// Get all not built-in post types (excludes posts, pages, media)
			$post_type_args = array(
				'public'   => true,
				'_builtin' => false
			);
			$post_types     = get_post_types( $post_type_args, 'objects', 'and' ); 

			foreach ( $post_types as $post_type ) {
				add_action( 'updated_' . $post_type->name . '_meta', array( $this, 'social_sharing' ), 10, 4 );
			}

		}

		/**
		 * Publish Content Hook
		 * @since 0.1
		 * @version 0.1
		 */
		public function social_sharing( $meta_id, $object_id, $meta_key, $_meta_value ) {

			if ( $meta_key !== '_mycred_community_post_social_shares_list' ) return;

			$post_id = $object_id;
			$post = get_post($post_id);
			$post_status = get_post_status($post);

			$user_id   = $post->post_author;
			$post_type = $post->post_type;

			// Check for exclusions
			if ( $this->core->exclude_user( $user_id ) === true ) return;

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $post_type ]['creds'] ) || empty( $this->prefs[ $post_type ]['creds'] ) || $this->prefs[ $post_type ]['creds'] == 0 ) return;

			// We want to fire when content gets shared
			$old_list 	= get_post_meta($post_id, '_mycred_community_post_social_shares_list');
			$references = apply_filters( 'mycred_community_social_share_hook_ref', 'social_sharing', $post, $meta_id, $object_id, $meta_key, $_meta_value, $this );

			if ( in_array($post_status, ['publish']) && $old_list !== $_meta_value &&  array_key_exists( $post_type, $this->prefs ) ) {
				// Prep
				$entry = $this->prefs[ $post_type ]['log'];
				$data  = array( 'ref_type' => 'post' );

				// Make sure this is unique
				if ( $this->core->has_entry( $references, $post_id, $user_id, $data, $this->mycred_type ) ) return;

				// Check limit
				if ( ! $this->over_hook_limit( $post_type, $references, $user_id ) )
					$this->core->add_creds(
						$references,
						$user_id,
						$this->prefs[ $post_type ]['creds'],
						$entry,
						$post_id,
						$data,
						$this->mycred_type
					);
			}

		}

		/**
		 * Preference for Publish Content Hook
		 * @since 0.1
		 * @version 1.1
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>
<div class="hook-instance">
	<h3><?php _e( 'Sharing Posts', 'mycred-community' ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'post' => 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'post' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'post' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['post']['creds'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'post' => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
				<?php echo $this->hook_limit_setting( $this->field_name( array( 'post' => 'limit' ) ), $this->field_id( array( 'post' => 'limit' ) ), $prefs['post']['limit'] ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'post' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'post' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'post' => 'log' ) ); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['post']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<div class="hook-instance">
	<h3><?php _e( 'Sharing Pages', 'mycred-community' ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'page' => 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'page' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'page' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['page']['creds'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'page' => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
				<?php echo $this->hook_limit_setting( $this->field_name( array( 'page' => 'limit' ) ), $this->field_id( array( 'page' => 'limit' ) ), $prefs['page']['limit'] ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'page' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'page' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'page' => 'log' ) ); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['page']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php

			// Get all not built-in post types (excludes posts, pages, media)
			$post_type_args = array(
				'public'   => true,
				'_builtin' => false
			);
			$post_types     = get_post_types( $post_type_args, 'objects', 'and' ); 

			foreach ( $post_types as $post_type ) {

				// Start by checking if this post type should be excluded
				if ( ! $this->include_post_type( $post_type->name ) ) continue;

				// Points to award/deduct
				if ( isset( $prefs[ $post_type->name ]['creds'] ) )
					$_creds = $prefs[ $post_type->name ]['creds'];
				else
					$_creds = 0;

				// Log template
				if ( isset( $prefs[ $post_type->name ]['log'] ) )
					$_log = $prefs[ $post_type->name ]['log'];
				else
					$_log = '%plural% for sharing ' . $post_type->labels->name . ' on social networks';

				if ( isset( $prefs[ $post_type->name ]['limit'] ) )
					$_limit = $prefs[ $post_type->name ]['limit'];
				else
					$_limit = '0/x';

?>
<div class="hook-instance">
	<h3><?php printf( __( 'Sharing %s', 'mycred-community' ), $post_type->labels->name ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type->name => 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type->name => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $post_type->name => 'creds' ) ); ?>" value="<?php echo $this->core->number( $_creds ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type->name => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
				<?php echo $this->hook_limit_setting( $this->field_name( array( $post_type->name => 'limit' ) ), $this->field_id( array( $post_type->name => 'limit' ) ), $_limit ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type->name => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type->name => 'log' ) ); ?>" id="<?php echo $this->field_id( array( $post_type->name => 'log' ) ); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $_log ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php

			}

		}

		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 0.1
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['post']['limit'] ) && isset( $data['post']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['post']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['post']['limit'] = $limit . '/' . $data['post']['limit_by'];
				unset( $data['post']['limit_by'] );
			}

			if ( isset( $data['page']['limit'] ) && isset( $data['page']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['page']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['page']['limit'] = $limit . '/' . $data['page']['limit_by'];
				unset( $data['page']['limit_by'] );
			}

			// Get all not built-in post types (excludes posts, pages, media)
			$post_type_args = array(
				'public'   => true,
				'_builtin' => false
			);
			$post_types = get_post_types( $post_type_args, 'objects', 'and' ); 

			foreach ( $post_types as $post_type ) {

				// Start by checking if this post type should be excluded
				if ( ! $this->include_post_type( $post_type->name ) ) continue;

				if ( isset( $data[ $post_type->name ]['limit'] ) && isset( $data[ $post_type->name ]['limit_by'] ) ) {
					$limit = sanitize_text_field( $data[ $post_type->name ]['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data[ $post_type->name ]['limit'] = $limit . '/' . $data[ $post_type->name ]['limit_by'];
					unset( $data[ $post_type->name ]['limit_by'] );
				}

			}

			return $data;

		}

	}

}
