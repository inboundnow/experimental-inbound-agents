<?php

if(!class_exists('Inbound_Assigned_Agents_Bulk_Actions')){
	
	class Inbound_Assigned_Agents_Bulk_Actions{
	
		function __construct(){
			self::load_hooks();
		
		}
	
		public static function load_hooks(){
			/**Lead page bulk actions**/
			
			/*register agent columns for the bulk actions page*/
			add_filter('inbound_bulk_lead_action_list_item', array(__CLASS__, 'register_bulk_action_list_items'));
			/*add the new lead list table headers*/
			add_action('inbound_bulk_lead_action_list_header', array(__CLASS__, 'add_lead_action_list_header'));
			/*add the "add to agent"  bulk action to the leads screen*/
			add_action('admin_footer-edit.php', array(__CLASS__, 'register_bulk_action_agent_assign'));
		
			/**Inboundnow bulk actions**/
			
			/*create the lead action triggers //the buttons*/
			add_action('inbound_bulk_lead_action_triggers', array(__CLASS__, 'inbound_agents_lead_action_triggers'));
			/*create lead action controls //the drop-up options*/
			add_action('inbound_bulk_lead_action_controls', array(__CLASS__, 'inbound_agents_lead_action_controls'));
			/*create the added scripts*/
			add_action('inbound_bulk_lead_action_inline_scripts', array(__CLASS__, 'inbound_agents_lead_action_controls'));
			/*enqueue scripts*/
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_inbound_agents_bulk_action_scripts'));
		}
	
	
		/**
		 * Create the "add to agent" bulk action and process the action requests
		 */
		public static function register_bulk_action_agent_assign(){
        global $post_type;

        if ($post_type != 'wp-lead') {
            return;
        }

        $lists = Inbound_Assigned_Agents_Resources::$assigned_agents;

        $html = "<select class='inbound_agent_select' name='action_agent_select_id'>";
        foreach ($lists as $id => $label) {
            $html .= "<option value='" . $id . "'>" . $label . "</option>";
        }
        $html .= "</select>";


        ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
				var checked = [];

                jQuery('<option>').val('add-to-agent').text('<?php _e('Add to Agent', 'inbound-pro') ?>').appendTo("select[name='action']");
                jQuery('<option>').val('add-to-agent').text('<?php _e('Add to Agent', 'inbound-pro') ?>').appendTo("select[name='action2']");

                jQuery(document).on('change', 'select[name=action],select[name=action2]', function () {
                    var this_id = jQuery(this).val();
					
					/*set the action selectors to the same value*/
					jQuery('select[name=action],select[name=action2]').val(jQuery(this).val()).prop(':checked', true);
                    
                    /*append the add to agent selector html*/
					if (this_id.indexOf("add-to-agent") >= 0) {
                        var html = "<?php echo $html; ?>";
						jQuery("select[name='action']").after(html);
                        jQuery("select[name='action2']").after(html);
                    }
                    else {                        
                        jQuery('#posts-filter').prop('target', 'self');
                        jQuery('.inbound_agent_select').remove();
                    }
                });

				/**add to leads to agent**/
				jQuery('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function (){
					if(jQuery('#bulk-action-selector-top').val() == 'add-to-agent' || jQuery('#bulk-action-selector-bottom').val() == 'add-to-agent'){
						console.log(jQuery('#bulk-action-selector-top').val());
						/*clear any previous event listeners*/
						jQuery(document).off('click','#doaction, #doaction2');
						
						jQuery(document).on('click', '#doaction, #doaction2', function(e){
							e.preventDefault();
							if(jQuery('select[name=action]').val() == 'add-to-agent' || jQuery('select[name=action2]').val() == 'add-to-agent'){

								/*get the lead ids*/
								leadIds = jQuery("#the-list").find("input[type=checkbox]:checked").map(function () {
									return this.value;
								}).get();
								
								/*get the agent id*/
								var agent_id = jQuery(this).siblings('.inbound_agent_select').val();

								jQuery.ajax({
									type: 'POST',
									url: ajaxurl,
									data : {
										action : 'edit_term_lead_group_data',
										data : {'execution' : 'add-leads',
												'agent_ids' : agent_id = [agent_id], 
												'lead_ids' : leadIds,
												},
										
									},
									success : function(response){
										response = JSON.parse(response);
									//	console.log(response);
										location.reload(true);
									},
								});
							}
						});
					}
				});
            });
        </script>
        <?php
		}

/***************************************************INBOUND BULK ACTIONS**************************************************************/	
		
		public static function add_lead_action_list_header(){
			
			$headers  = '<th scope="col">' . __('Agents Assigned', 'inbound-pro' ) . '</th>';
			$headers .= '<th scope="col">' . __('In Agent Groups', 'inbound-pro' ) . '</th>';
		
			echo $headers;
		}
	
		
		public static function register_bulk_action_list_items($post){
			$groups_by_id = Inbound_Assigned_Agents_Resources::$agent_term_lead_groups;
			
			/* show lists */
			$html = '';
			 
			/* show agent terms */
            $html .= '<td>';
            $lead_group_html = '<td>';
            $terms = wp_get_post_terms($post->ID, 'inbound_assigned_lead', 'id');
            foreach ($terms as $term) {
				$html .= '<span class="list-pill">' . $term->name . ' <i title="' . $term->name . '" data-lead-id="' . $post->ID . '" data-list-id="' . $term->term_id . '"></i></span> ';
            }
			$html .= '</td>';
			
			
			/* Show Agent groups*/
			$html .= '<td>';
			/*loop through the terms*/
            foreach ($terms as $term) {
				/*make sure the the agent term exists in the listing*/
				if(isset($groups_by_id[$term->term_id])){
					$in_group = false;
					$html .= '<span title="' . $term->name . '">' . $term->name .  ': </span>';
					/*loop through the agent's groups to see if the lead is in one of them*/
					foreach($groups_by_id[$term->term_id] as $lead_group=>$value){
						/*ignore anything that's not an array*/
						if(gettype($value) == 'array'){
							if(in_array($post->ID, $value)){
							$in_group = true;
							$html .= '<span title="' . $term->name . '">' . $lead_group .  ', </span>';
										
							}
						}
					}
					/*if the lead isn't in a group, in_group will be false and "N/A" will be outputted*/
					if(!$in_group){ $html .= '<span title="' . $term->name . '">N/A</span>'; }
				}else if(!empty(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$term->term_id]) && in_array($post->ID, Inbound_Assigned_Agents_Resources::$ungrouped_leads[$term->term_id])){
					/**if the lead is an unlisted lead, output N/A**/
					$html .= '<span title="' . $term->name . '">' . $term->name .  ': </span>';
					$html .= '<span title="' . $term->name . '">N/A</span>'; 
				}
				/*newline on each new agent*/
				$html .= '<br>';
            }
			
			$html .= '</td>';

			echo $html;
		
		}
	
	
	
		public static function inbound_agents_lead_action_triggers(){
			?>

			<div class="action" id="agent-lead-bulk-actions">
				<label for="inbound-bulk-action-agent-selector"><?php _e('Agent Actions:', 'inbound-pro' ); ?></label>
				<input type="button" name="add-leads" class="manage-tag-add button-primary button" value="<?php _e('Add', 'inbound-pro' ) ?>" title="<?php _e('Add leads to the selected agent/s', 'inbound-pro' ); ?>"/>
				<input type="button" name="remove-leads" class="manage-remove button-primary button" value="<?php _e('Remove', 'inbound-pro' ) ?>" title="<?php _e('Remove leads from the selected agent/s.', 'inbound-pro' ); ?>"/>
				<input type="button" name="transfer-leads" class="manage-tag-replace button-primary button" value="<?php _e('Transfer Leads', 'inbound-pro' ); ?>" title="<?php _e('Transfer leads from the selected agent/s to other agent/s', 'inbound-pro' ); ?>"/>
			</div>
			<!--agent option popup-->
			<div id="inbound-agent-option-popup-container">
				<div id="inbound-agent-option-popup-controls">
					<div class="inbound-agent-bulk-action-header used-in-add-leads"><?php _e('Add leads to agents', 'inbound-pro'); ?></div>
					<div class="inbound-agent-bulk-action-header used-in-remove-leads"><?php _e('Remove leads from agents', 'inbound-pro'); ?></div>
					<div class="inbound-agent-bulk-action-header used-in-transfer-leads"><?php _e('Transfer leads', 'inbound-pro'); ?></div>
					
					<!--agent/group select 1-->
					<div id="inbound-agent-selector-container">
						<label for="inbound-bulk-action-agent-selector" ><?php _e('Select Agents:', 'inbound-pro'); ?></label><br />
						<select id="inbound-bulk-action-agent-selector" name="agent-select" class="agent-selector" title="<?php _e('Select the agents who\'s leads are to be moved', 'inbound-pro' ); ?>" multiple="multiple" style="width: 100%;">
							<?php
							foreach(Inbound_Assigned_Agents_Resources::$assigned_agents as $key=>$value){
								echo '<option value="' . $key . '">' . $value . '</option>';
							} ?>
						</select><br />
					</div>
					<div id="inbound-agent-lead-group-selector-container">
						<label for="inbound-bulk-action-lead-group-selector" class="used-in-add-leads"><?php _e('Select the groups to add leads to:', 'inbound-pro'); ?></label>
						<label for="inbound-bulk-action-lead-group-selector" class="used-in-remove-leads"><?php _e('Select the groups to remove leads from:', 'inbound-pro'); ?></label>
						<label for="inbound-bulk-action-lead-group-selector" class="used-in-transfer-leads"><?php _e('Select the groups to move leads from:', 'inbound-pro'); ?></label><br />
						<select id="inbound-bulk-action-lead-group-selector"  name="agent-group-select" class="lead-group-selector" title="<?php _e('Select the groups to move leads out of', 'inbound-pro' ); ?>" multiple="multiple" style="width: 100%;">
						</select><br />
					</div>
					<!--end agent select 1-->
					
					<!--agent/group select 2-->
					<div id="inbound-agent-selector-container-2" class="transfer-box-checked">
						<label for="inbound-bulk-action-agent-selector-2"><?php _e('Select the agents to move leads to', 'inbound-pro'); ?></label><br />
						<select id="inbound-bulk-action-agent-selector-2" name="agent-select-2" class="agent-selector" title="<?php _e('Select the agents who will be receiving leads. Inputting multiple agents add the leads to all selected agents', 'inbound-pro' ); ?>" multiple="multiple" style="width: 100%;">
							<?php
							foreach(Inbound_Assigned_Agents_Resources::$assigned_agents as $key=>$value){
								echo '<option value="' . $key . '">' . $value . '</option>';
							} ?>
						</select><br />
					</div>
					<div id="inbound-agent-lead-group-selector-container-2" class="used-in-transfer-leads lead-group-selector">
						<label for="inbound-bulk-action-lead-group-selector-2" ><?php _e('Select the groups to move leads to:', 'inbound-pro'); ?></label><br />
						<select id="inbound-bulk-action-lead-group-selector-2" name="agent-group-select-2" class="lead-group-selector" title="<?php _e('Select the groups that the leads will be put in', 'inbound-pro' ); ?>" multiple="multiple" style="width: 100%;">
						</select><br />
					</div>
					<!--end agent/group select 2-->
					
					
					<div id="inbound-agents-lead-group-radio-container" class="used-in-add-leads used-in-transfer-leads delete-transfer-box-checked">
						<label for="inbound-agents-lead-group-radio"><?php _e('Select from these lead groups:', 'inbound-pro'); ?></label><br />
						<input type="radio" name="inbound-agents-lead-group-radio" class="inbound-agents-lead-groups-radio" value="1" title="<?php _e('Select only the lead groups the agents have in common.', 'inbound-pro' ); ?>" checked="checked"/><?php _e('Groups in common', 'inbound-pro'); ?><br />
						<input type="radio" name="inbound-agents-lead-group-radio" class="inbound-agents-lead-groups-radio" value="" title="<?php _e('Select from all created agent lead groups. If an agent doesn\'t have the selected lead group, the group will be created for the agent and the leads will be automatically added to it.', 'inbound-pro' ); ?>" /><?php _e('All groups', 'inbound-pro'); ?><br />
					</div>
					
					
					<div id="inbound-agents-remove-leads-radio-container" class="used-in-remove-leads">
						<label><?php _e('Remove leads', 'inbound-pro'); ?></label><br />
						<input type="radio" name="inbound-agents-remove-leads-radio" class="inbound-agents-remove-leads-radio" value="0" title="<?php _e('Select to remove leads from an agents\' lead group', 'inbound-pro' ); ?>" checked="checked"/><?php _e('Remove from group', 'inbound-pro'); ?><br />
						<input type="radio" name="inbound-agents-remove-leads-radio" class="inbound-agents-remove-leads-radio" value="1" title="<?php _e('Select to remove leads from an agent entirely', 'inbound-pro' ); ?>"/><?php _e('Remove from agent', 'inbound-pro'); ?><br />
					</div>
					
				
					<div id="to-another-agent-checkbox-container" class="used-in-transfer-leads">
						<input type="checkbox" name="to-another-agent-checkbox" id="to-another-agent-checkbox" title="<?php _e('Check this to move leads from the selected agents to other agents', 'inbound-pro' ); ?>"/><?php _e('Transfer to other agents?', 'inbound-pro'); ?><br />
					</div>
					
					<div id="inbound-agents-remove-leads-checkbox-container" class="transfer-box-checked">
						<input type="checkbox" name="inbound-agents-remove-leads-checkbox" title="<?php _e('Remove the selected leads from the agents in the first dropdown', 'inbound-pro' ); ?>"/><?php _e('Remove leads from first agents?', 'inbound-pro'); ?><br />
					</div>
				</div>
				<div id="inbound-agents-submit-button-container">
					<button type="button" name="assigned-agents-bulk-action-submit-button" id="assigned-agents-bulk-action-submit-button"><?php _e('Do Lead Action', 'inbound-pro'); ?></button>
				</div>
				
			</div>
			<!--end agent option popup-->
			<?php
		}
	
	
	
		public static function inbound_agents_lead_action_controls(){
			?>
			 <option value="agent-lead-bulk-actions" class="action-symbol lead-agent-action-symbol db-drop-label"><?php _e('Do agent lead action', 'inbound-pro' ); ?></option>
			<?php
		}
	
		public static function enqueue_inbound_agents_bulk_action_scripts(){
			global $post;
			
			//Enqueue the javascript for the inbound bulk actions agent actions
			if ((isset($post) && 'wp-lead'=== $post->post_type) || (isset($_GET['post_type']) && $_GET['post_type']==='wp-lead')) {
				$current_screen = get_current_screen();
				
				if($current_screen->base == 'wp-lead_page_lead_management'){
					/*inbound bulk action .js*/
					wp_enqueue_script('assigned-agents-inbound-bulk-actions-js', INBOUNDNOW_LEAD_ASSIGNMENTS_URLPATH . 'js/assigned-agents-inbound-bulk-actions.js' , false , true );
					wp_localize_script( 'assigned-agents-inbound-bulk-actions-js', 'assigned_agents_inbound_bulk_action_vars', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'inbound_shortcode_nonce' => wp_create_nonce('inbound-shortcode-nonce'), 'wp-lead' => 'on' ) );
					
					/*css*/
					wp_enqueue_style('assigned-agents-bulk-action-styles', INBOUNDNOW_LEAD_ASSIGNMENTS_URLPATH . 'css/assigned-agents-bulk-action-style.css' , false , true );
				
					/*enqueue sweet alert*/
					wp_enqueue_script('sweet-alert-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.min.js', false , true );
					wp_enqueue_style('sweet-alert-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.css', false , true );
					
					/* if stand alone plugin */
					if (!defined('INBOUND_PRO_CURRENT_VERSION')) {
						wp_enqueue_script('select2-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.min.js', false , true );
						wp_enqueue_style('select2-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.css', false , true );
					}
				}
			}
		}
	}


	new Inbound_Assigned_Agents_Bulk_Actions;

}












?>
