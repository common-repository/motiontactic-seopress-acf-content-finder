<?php
/*
Plugin Name: Custom Field Content Finder for SEOPress and ACF
description: Add post ACF content to SEOPress
Version: 1.0
Author: Motion Tactic
Author URI: https://motiontactic.com
License: GPL2
*/

class CFCFSeoPressACF
{
	public $straight_value_types = [ 'text', 'wysiwyg', 'textarea', 'email' ];
	public $nested_types = [ 'group', 'repeater', 'flexible_content' ];

	public function __construct()
	{
		$this->register_actions();
	}

	public function register_actions()
	{
		add_filter( 'seopress_content_analysis_content', [ $this, 'add_acf_to_seopress_content' ], 10, 2 );
		add_action( 'admin_menu', [ $this, 'setup_admin_menu' ] );
	}

	public function add_acf_to_seopress_content( $content, $id )
	{
		$content = $content . $this->pull_acf_content( $id );
		return $content;
	}

	public function pull_acf_content( $id )
	{
		$fields = get_field_objects( $id );
		if ( !$fields ) return '';

		$content = '';
		foreach ( $fields as $field ) {
			$content .= $this->pull_content( $field, $id );
		}
		return $content;
	}

	public function pull_content( $field, $id )
	{

		if ( in_array( $field[ 'type' ], $this->straight_value_types ) ) return $field[ 'value' ] . ' ';

		if ( in_array( $field[ 'type' ], $this->nested_types ) ) {
			if ( have_rows( $field[ 'name' ], $id ) ) {
				$content = '';
				while ( have_rows( $field[ 'name' ], $id ) ) {
					$row = the_row();
					foreach ( $row as $row_field_key => $row_field ) {
						$sub_field_object = get_sub_field_object( $row_field_key );
						if ( !$sub_field_object ) continue;
						$content .= $this->pull_content( $sub_field_object, $id );
					}
				}
				return $content;
			}
		}

		return '';
	}

	public function setup_admin_menu()
	{
		add_management_page( 'SEOPress ACF Content', 'SEOPress ACF Content', 'manage_options', 'seopress-acf-content', [ $this, 'create_admin_page' ] );
	}

	public function create_admin_page()
	{
		$request_id = sanitize_text_field( $_REQUEST[ 'page' ] );
		$request_page = sanitize_title( $_REQUEST[ 'post_id' ] );
		$nonce_verify = isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'cfcf_acf_test' );
		?>
		<h1>ACF Content</h1>
		<h3>Search by post id to see the content that the plugin will find in the pages ACF fields</h3>
		<form action="?" method="get">
			<label for="seopress-acf-test">Post ID</label>
			<input type="text" name="post_id" id="seopress-acf-test" value="<?php echo $request_id; ?>">
			<input type="hidden" name="page" value="<?php echo $request_page; ?>">
			<?php wp_nonce_field('cfcf_acf_test', 'nonce'); ?>
			<input type="submit" value="Get ACF Content">
		</form>
		<?php if ( $request_id && $nonce_verify) :
		$post_id = (int)$request_id;
		?>
		<h2>Here is what the plugin found on the <?php echo get_the_title( $post_id ); ?> <?php echo get_post_type( $post_id ); ?></h2>
		<div class="output" style="margin: 50px 75px 50px 50px; padding: 50px; background-color:#ffffff;">
			<?php echo $this->pull_acf_content( $post_id ); ?>
		</div>
	<?php
	endif;
	}
}


$SeoPressACF = new CFCFSeoPressACF();
