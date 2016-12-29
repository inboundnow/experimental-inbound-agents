<?php
if(!class_exists('Inbound_Assigned_Agents_Taxonomy')){

	class Inbound_Assigned_Agents_Taxonomy{
	
		function __construct(){
			self::load_hooks();
		
		}
	
		public static function load_hooks(){
			add_action('init', array(__CLASS__, 'add_lead_assignment_taxononmy'));
		}
	
		/**
		 * Create agent taxonomy
		 */
		public static function add_lead_assignment_taxononmy(){
			
			$labels = array(
				'name'						=> __( 'Assigned Agents', 'inbound-pro' ),
				'singular_name'				=> __( 'Assigned Agent', 'inbound-pro' ),
				'search_items'				=> __( 'Search Agents', 'inbound-pro' ),
				'popular_items'				=> __( 'Top Agents', 'inbound-pro' ),
				'all_items'					=> __( 'All Agents', 'inbound-pro' ),
				'parent_item'				=> null,
				'parent_item_colon'			=> null,
				'edit_item'					=> __( 'Edit Agent', 'inbound-pro' ),
				'update_item'				=> __( 'Update Agent', 'inbound-pro' ),
				'add_new_item'				=> __( 'Add New Agent', 'inbound-pro' ),
				'new_item_name'				=> __( 'New Agent', 'inbound-pro' ),
				'separate_items_with_commas' => __( 'Separate Agents with commas', 'inbound-pro' ),
				'add_or_remove_items'		=> __( 'Add or remove Agents', 'inbound-pro' ),
				'choose_from_most_used'		=> __( 'Choose from the most used Agent', 'inbound-pro' ),
				'not_found'					=> __( 'No Agents found.', 'inbound-pro' ),
				'menu_name'					=> __( 'Manage Agents', 'inbound-pro' ),
			);

			$args = array(
				'hierarchical'			=> true,
				'labels'				=> $labels,
				'singular_label'		=> __( 'Agent Management', 'inbound-pro' ),
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'show_in_nav_menus'		=> true,
				'show_admin_column'		=> true,
				'query_var'				=> true,
				'rewrite'				=> false,
		//		'update_callback'       => array(__CLASS__, 'agents_update_function'), //TODO: FIND OUT IF I NEED THIS
			);
						
			register_taxonomy('inbound_assigned_lead', 'wp-lead', $args);
			register_taxonomy_for_object_type('inbound_assigned_lead', 'wp-lead');


	
		}
	
		/*Update function.*/
/*		public static function agents_update_function($terms, $taxonomy){
			wp_update_term_count($terms,$taxonomy);
		
		}*/

	}//end Inbound_Assigned_Agents_Taxonomy








	new Inbound_Assigned_Agents_Taxonomy;

}



























?>
