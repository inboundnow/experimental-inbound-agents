<?php

if(!class_exists('Inbound_Assigned_Agents_Agent_Profile')){
	
	class Inbound_Assigned_Agents_Agent_Profile{
		
		function __construct(){
			self::add_hooks();
		
		}
		
		public static function add_hooks(){
			
			/*user profile lead widget*/
			add_filter( 'show_user_profile', array( __CLASS__, 'assigned_leads_widget' ), 10 );  //This hook only triggers when a user is viewing their _own_ profile page.
            add_filter( 'edit_user_profile', array( __CLASS__, 'assigned_leads_widget' ), 10 );  //This hook only triggers when a user is viewing _another users_ profile page (not their own).
		}
		
		
		/**For displaying the user's assigned leads in his profile**/
		public static function assigned_leads_widget( $user ){
			global $user_id;

			if ( !current_user_can('editor') && !current_user_can('administrator') && $user_id != wp_get_current_user()->ID) {
                return;
            }


			$agent_term_id = get_user_meta($user->ID, 'inbound_assigned_lead_term', true);
			
			/*if has lead term*/
			if($agent_term_id){		
			
			/*get the agent's lead groups*/
			$agent_lead_groups = Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent_term_id];

			/*get the agent's term object*/
			$agent_term = get_term($agent_term_id, 'inbound_assigned_lead');
			
			/*get all the agent's leads for the "Select from all leads" option*/
			$leads = get_posts(array(
				'post_type' => 'wp-lead',
				'numberposts' => -1,
				'tax_query' => array(
					array(
					'taxonomy' => 'inbound_assigned_lead',
					'field' => 'id',
					'terms' => $agent_term,
					),
				),
			));

			/*build the "Select from all leads" options*/
			$lead_options = '';
			foreach($leads as $lead){
				$lead_options .= '<option value="' . $lead->ID . '">' . $lead->ID . '</option>';
			}
			
			/*enqueue the profile css*/
			wp_enqueue_style('assigned-agents-profile-styles', INBOUNDNOW_LEAD_ASSIGNMENTS_URLPATH . 'css/assigned-agents-profile-style.css' , false , true );
			
			/*enqueue sweet alert*/
			wp_enqueue_script('sweet-alert-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.min.js', false , true );
			wp_enqueue_style('sweet-alert-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.css', false , true );
	
			/* if stand alone plugin */
            if (!defined('INBOUND_PRO_CURRENT_VERSION')) {
				wp_enqueue_script('select2-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.min.js', false , true );
				wp_enqueue_style('select2-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.css', false , true );
            }
	
	
			?>
			<div id="inbound-agents-interface-header">Inbound Agent Lead Interface</div>
			<div id="inbound-agent-profile-container">
				<div id="inbound-agent-profile" class="inbound-agent-profile-display-container">
					<h2 id="inbound-agent-profile-header" ><?php printf( __('%s\'s Assigned Leads: ', 'inbound-pro'), $user->display_name );?></h2>
					<div id="inbound-agent-profile-lead-count"  title="<?php _e('This is the total number of leads that the agent has assigned','inboundpro'); ?>"><?php echo  __('Total Leads: ', 'inbound-pro') . $agent_term->count; ?></div>
					<div id="inbound-agent-profile-groups-header" title="<?php _e('These are the groups that leads are stored in. The same lead may be in multiple groups','inboundpro'); ?>"><?php _e('In these groups: ', 'inbound-pro'); ?></div>
					<?php
					if(!empty($agent_lead_groups)){
						foreach($agent_lead_groups as $key=>$value ){	
							echo '<p style="white-space: nowrap; font-weight: 600;">' . $key . ': ' . count($value) . '</p>';
						}
					}else{
						echo '<p>' . __('No groups found', 'inbound-pro') . '</p>';
					}
					/*if there are ungrouped leads, list the number of them*/
					if(!empty(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$agent_term_id])){
						echo '<p style="white-space: nowrap; font-weight: 600;">' . __('Ungrouped Leads: ', 'inbound-pro') . count(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$agent_term_id]) . '</p>';
					}
					?>
				</div>
				<div id="agent-profile-lead-actions">
					<div id="agent-lead-action-container" class="inbound-agent-profile-display-container">
						<div id="agent-lead-action-controls">
							<div id="lead-action-header">Lead Actions:</div>
							<div id="lead-select-display-container">
								<label for="lead-select-display-option" class="agent-action-label"><?php _e('Choose how to select leads', 'inbound-pro');?></label><br />
								<input type="radio" name="lead-select-display-option" value="groups" checked="checked" title="<?php _e('Select leads from inside groups','inbound-pro'); ?>"><?php _e('Select leads from groups', 'inbound-pro');?><br />
								<input type="radio" name="lead-select-display-option" value="leads" title="<?php _e('Select from all leads that this agent has','inbound-pro'); ?>" ><?php _e('Select from all leads', 'inbound-pro');?><br />
							</div>
							<select id="agents-lead-group-selector">
								<option value="-1"><?php _e('Please select a lead group', 'inbound-pro');?></option>
								<?php foreach($agent_lead_groups as $group=>$leads){ echo '<option value="'. $group .'">' . $group . '</option>'; }?>
								<?php if($lead_options != '') { ?>
								<option value="Ungrouped Leads"><?php _e('Ungrouped Leads', 'inbound-pro');?></option>
								<?php }; ?>
							</select><br />
							<select id="agents-lead-selector" multiple="multiple">
							</select>
							<div id="agents-lead-action-selector-container">
								<label for="transfer-to-group" class="agent-action-label"><?php _e('Choose a lead action', 'inbound-pro');?></label><br />
								<input type="radio" name="lead-action" class="lead-action-radio-button" value="transfer-to-group" id="transfer-to-group" title="<?php _e('Transfer leads from one of this agent\'s groups into one or more of this agents groups. Optionally, leads can be moved out of all of this agent\'s groups and into the recipient group/s', 'inbound-pro'); ?>" /><?php _e('Transefer selected leads to another group', 'inbound-pro');?><br />
								<input type="radio" name="lead-action" class="lead-action-radio-button" value="transfer-to-agent" id="transfer-to-agent" title="<?php _e('Transfer leads from this agent to another agent. This removes the lead from this agent, and gives it to the recipient agent. ', 'inbound-pro'); ?>" /><?php _e('Transfer selected leads to another agent', 'inbound-pro');?><br />
								<input type="radio" name="lead-action" class="lead-action-radio-button" value="remove-leads" id="remove-leads" title="<?php _e('Remove leads from selected agent groups. Optionally, remove lead from this agent entirly', 'inbound-pro'); ?>" /><?php _e('Remove selected Leads', 'inbound-pro');?><br />
							</div>
							<div id="lead-action-inputs">
								<!--for sending lead to another group in the same agent-->
								<div id="agents-lead-group-selector-2-container" class="used-in-transfer-to-group">
									<label for="agents-lead-group-selector-2"><?php _e('Recipient Groups' ,'inbound-pro'); ?></label>
									<select id="agents-lead-group-selector-2" multiple="multiple"  title="<?php _e('Select the groups to transfer leads into','inbound-pro'); ?>" >
										<?php foreach($agent_lead_groups as $group=>$leads){ echo '<option value="'. $group .'">' . $group . '</option>'; }?>
									</select>
								</div>
								<br />
								<!--for selecting an agent to send leads to-->
								<div id="to-agent-selector-container" class="used-in-transfer-to-agent">
									<label for="to-agent-selector"><?php _e('Recipient Agents','inbound-pro');?></label>
									<select id="to-agent-selector" multiple="multiple"  title="<?php _e('Select agents to transfer leads to','inbound-pro'); ?>" >
										<?php foreach(Inbound_Assigned_Agents_Resources::$assigned_agents as $agent_id=>$display_name){
												/*skip the current user's agent id*/
												if($agent_id == $agent_term_id){ continue; }
												echo '<option value="' . $agent_id. '">' . $display_name . '</option>';
											} ?>
									</select>
								</div>
								<!--for selecting other agent's groups to send leads to-->
								<div id="to-agent-group-selector-container" class="used-in-transfer-to-agent">
									<label for="to-agent-group-selector"><?php _e('Recipient Agent Groups','inbound-pro'); ?></label>
									<select id="to-agent-group-selector" multiple="multiple"  title="<?php _e('Select groups to transfer leads into','inbound-pro'); ?>" >
									</select>
								</div>
								<br />
								<div id="move-leads-from-all-groups-container" class="used-in-transfer-to-group">
									<input type="checkbox" id="move-leads-from-all-groups" title="<?php _e('Check this to move this lead out of any other groups that it\'s in for this agent, and into the selected group/s.', 'inbound-pro'); ?>" /><?php _e('Move leads from other groups to the selected?', 'inbound-pro'); ?><br />
								</div>
								<div id="remove-lead-from-agent-container" class="used-in-remove-leads">
								<input type="checkbox" id="remove-lead-from-agent" title="<?php _e('Check this to remove the lead from this agent and from all of this agent\'s groups', 'inbound-pro'); ?>" /><?php _e('Remove leads from agent entirely?', 'inbound-pro'); ?><br />
								</div>
							</div>
							
						</div>
						<button type="button" id="agent-profile-lead-action-button"><?php _e('Do action', 'inbound-pro'); ?></button>
					</div>
					
					<div id="agent-lead-display-container" class="inbound-agent-profile-display-container">
						<div id="lead-display-header"><?php _e('Selected Leads:', 'inbound-pro'); ?></div>
						<table id="agent-lead-display-table">
							<thead>
								<tr><th scope="col" id="lead-id"><?php _e('Lead Id:', 'inbound-pro'); ?></th><th scope="col" id="first-name"><?php _e('First Name:', 'inbound-pro'); ?></th><th scope="col" id="last-name"><?php _e('Last Name', 'inbound-pro'); ?></th><th scope="col" id="email"><?php _e('Email:', 'inbound-pro'); ?></th></tr>
							</thead>
							<tbody id="lead-info-table">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<script>
				jQuery(document).ready(function(){
					var groups = <?php echo json_encode($agent_lead_groups); ?>;
					var leads = <?php echo json_encode($lead_options); ?>;
					var ungroupedLeads = JSON.parse("<?php echo json_encode(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$agent_term_id]); ?>");
					var oldChecked;

					/**create opts for the lead display spinner**/
					var spinnerOpts = {
						 lines: 18, // The number of lines to draw
						 length: 14, // The length of each line
						 width: 7, // The line thickness
						 radius: 24, // The radius of the inner circle
						 scale: 1.50, // Scales overall size of the spinner
						 corners: 1, // Corner roundness (0..1)
						 color: '#000', // #rgb or #rrggbb or array of colors
						 opacity: 0.25, // Opacity of the lines
						 rotate: 0, // The rotation offset
						 direction: 1, // 1: clockwise, -1: counterclockwise
						 speed: 1, // Rounds per second
						 trail: 60, // Afterglow percentage
						 fps: 20, // Frames per second when using setTimeout() as a fallback for CSS
						 zIndex: 2e9, // The z-index (defaults to 2000000000)
						 className: 'agent-lead-display-spinner', // The CSS class to assign to the spinner
						 top: '50%', // Top position relative to parent
						 left: '50%', // Left position relative to parent
						 shadow: false, // Whether to render a shadow
						 hwaccel: false, // Whether to use hardware acceleration
						 position: 'absolute', // Element positioning
					}
					
					/**create opts for the agent group selector spinner**/
					var spinnerOpts2 = {
						 lines: 18, // The number of lines to draw
						 length: 14, // The length of each line
						 width: 7, // The line thickness
						 radius: 24, // The radius of the inner circle
						 scale: 0.25, // Scales overall size of the spinner
						 corners: 1, // Corner roundness (0..1)
						 color: '#000', // #rgb or #rrggbb or array of colors
						 opacity: 0.25, // Opacity of the lines
						 rotate: 0, // The rotation offset
						 direction: 1, // 1: clockwise, -1: counterclockwise
						 speed: 1, // Rounds per second
						 trail: 60, // Afterglow percentage
						 fps: 20, // Frames per second when using setTimeout() as a fallback for CSS
						 zIndex: 2e9, // The z-index (defaults to 2000000000)
						 className: 'lead-action-to-agent-spinner', // The CSS class to assign to the spinner
						 top: '150%', // Top position relative to parent
						 left: '95%', // Left position relative to parent
						 shadow: false, // Whether to render a shadow
						 hwaccel: false, // Whether to use hardware acceleration
						 position: 'absolute', // Element positioning
					}
					
					/**call select2**/
					jQuery('#agents-lead-group-selector,#agents-lead-selector').select2();
					jQuery('#agents-lead-group-selector-2').select2({containerCssClass : 'used-in-transfer-to-group'});
					jQuery('#to-agent-selector,#to-agent-group-selector').select2({containerCssClass : 'used-in-transfer-to-agent'});
					

					
					/**how to select the leads**/
					jQuery('#lead-select-display-container').on('change', function(){
						/**by groups**/
						if(jQuery('input[name=lead-select-display-option]:checked').val() == 'groups'){
							/*show the group selector*/
							jQuery('#s2id_agents-lead-group-selector').css({'display' : 'inline-block'});
							/*make the move from groups checkbox potentially visible*/
							jQuery('#move-leads-from-all-groups-container').css({'visibility' : 'visible'});
							/*empty the lead selector,*/
							jQuery('#agents-lead-selector').empty();
							jQuery('#agents-lead-selector').select2('data', null);
							/*empty the display window*/
							jQuery('#lead-info-table').empty();
						}else{
						/**all agent leads**/
							/*hide the group selector*/
							jQuery('#s2id_agents-lead-group-selector').css({'display' : 'none'});
							/*make the move from groups checkbox invisible*/
							jQuery('#move-leads-from-all-groups-container').css({'visibility' : 'hidden'});						
							/*unset the lead group selector*/
							jQuery('#agents-lead-group-selector').val('-1').trigger('change');
							/*empty the lead selector*/
							jQuery('#agents-lead-selector').empty();
							jQuery('#agents-lead-selector').select2('data', null);
							/*add all the agent's leads as options*/
							jQuery('#agents-lead-selector').append(leads);
							/*empty the display window*/
							jQuery('#lead-info-table').empty();
						}
					});
					
					/**how to display the action inputs**/
					jQuery('#agents-lead-action-selector-container input').on('change', function(){
						/*set which set of inputs to display*/
						var checked = jQuery('#agents-lead-action-selector-container input:checked').val();
						if(oldChecked){jQuery('.used-in-' + oldChecked).css({'display' : 'none'});}
						jQuery('.used-in-' + checked).css({'display' : 'inline-block'});
						oldChecked = checked;
						
						/*show the submit button*/
						jQuery('#agent-profile-lead-action-button').css({'display' : 'inline-block'});
						
						/*change the button text to reflect the lead action*/
						if(checked == 'transfer-to-group' || checked == 'transfer-to-agent'){
							jQuery('#agent-profile-lead-action-button').html("<?php echo __('Transfer Leads', 'inbound-pro'); ?>");
							console.log(checked);
						}else if(checked == 'remove-leads'){
							jQuery('#agent-profile-lead-action-button').html("<?php echo __('Remove Leads', 'inbound-pro'); ?>");
						}

					});

					
					/**change the lead list to reflect the group selected**/
					jQuery('#agents-lead-group-selector').on('change', function(){
						/*clear the lead selector*/
						jQuery('#agents-lead-selector').empty();
						jQuery('#agents-lead-selector').select2('data', null);
						
						if(jQuery('#agents-lead-group-selector').val() == 'Ungrouped Leads'){
							var options = '';
							for(var b = 0; b <  ungroupedLeads.length; b++){
								options += '<option value="' + ungroupedLeads[b] + '">' + ungroupedLeads[b] + '</option>';
							}
							jQuery('#agents-lead-selector').append(options);

						}else if(jQuery('#agents-lead-group-selector').val() != '-1'){
							var selection = jQuery('#agents-lead-group-selector').val();
							var options = '';
							
							/**if the leads are held in an object, reformat it into an array**/
							if(typeof groups[selection] == 'object'){
								var formattedSelection = [];
								for(var h in groups[selection]){
									formattedSelection.push(groups[selection][h]);
								}
								groups[selection] = formattedSelection;
							}
						
							
							for(var i = 0; i < groups[selection].length; i++){
								options += '<option value="' + groups[selection][i] + '">' + groups[selection][i] + '</option>';
							}
							
							if(options != ''){
								jQuery('#agents-lead-selector').append(options);
							}

						}
					});
					
					/**when the lead selector changes, wait 1000ms then refresh the lead display**/	
					var wait;
					jQuery('#agents-lead-selector').on('change', function(){
						clearTimeout(wait);
						wait = setTimeout(getAgentLeads, 1000);
					});		
					
					
					/**refreshes the lead display window**/
					function getAgentLeads(){
						jQuery('#lead-info-table').empty();
						
						if(jQuery('#agents-lead-selector').val()){
							/*create a new spinner*/
							var target = jQuery('#agent-lead-display-container');
							var spinner = new Spinner(spinnerOpts).spin(target[0]);
							
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_agent_lead_info',
									data : {
										agent_id : <?php echo $agent_term_id; ?>,
										lead_ids : jQuery('#agents-lead-selector').val(),
									},
								},
								success : function(response){
									leadData = JSON.parse(response);
									/*remove the spinner*/
									jQuery('.agent-lead-display-spinner').remove();
									
									var html = '';
									var count = 0;
									/**create a new row for each lead**/
									for(var i in leadData){
										console.log(i);
										html += '<tr>\
													<td id="lead-id-cell-' + count + '" class="data-cell" data-colname="lead-id">'+ i +'</td>\
													<td id="first-name-cell-' + count + '" class="data-cell" data-colname="first-name">'+ leadData[i].first_name +'</td>\
													<td id="last-name-cell-' + count + '" class="data-cell" data-colname="last-name">'+ leadData[i].last_name +'</td>\
													<td id="email-cell-' + count + '" class="data-cell email-cell" data-colname="email">'+ leadData[i].email +'</td>\
												</tr>';
										count++;
									}
									jQuery('#lead-info-table').append(html);
								},
							});
						}
					}
					

					/**when the "to" agent selector changes, wait 750ms then refresh the "to" agent group selector**/
					var wait2;
					jQuery('#to-agent-selector').on('change', function(){
						clearTimeout(wait2);
						wait2 = setTimeout(recipientAgentGroups, 750);
					});
					
					/**refreshes the recipient agent's group list**/
					function recipientAgentGroups(){
						var val = jQuery('#to-agent-selector').val();
						
						/*clear the selector*/
						jQuery('#to-agent-group-selector').empty();
						jQuery('#to-agent-group-selector').select2('data', null)

						if(val){
							
							/*create a new spinner*/
							var target = jQuery('#s2id_to-agent-group-selector');
							var spinner = new Spinner(spinnerOpts2).spin(target[0]);
							
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_agent_term_lead_groups',
									data : val,
									get_groups_in_common : '1',
								},
								success : function(response){
									var groups = JSON.parse(response);
									
									/*remove the spinner*/
									jQuery('.lead-action-to-agent-spinner').remove();
									
									var options = '';
									for(var i = 0; i < groups.length; i++){
										options += '<option value="' + groups[i] + '">' + groups[i] + '</option>';
									}

									jQuery('#to-agent-group-selector').append(options);
								},
							});
						}
					}

					/**do lead actions on button click**/
					jQuery('#agent-profile-lead-action-button').on('click', function(){
						doLeadAction(jQuery('input[name=lead-action]:checked').val());

					});
					
					/**do lead actions**/
					function doLeadAction(action){
						var data = {};
						var agentLeadGroupSelector  = (jQuery('#s2id_agents-lead-group-selector').is(':visible')) ? jQuery('#agents-lead-group-selector').val() : true;  //pass true as a placeholder. If selecting from all leads, this value will be overwritten
						var agentLeadGroupSelector2 = jQuery('#agents-lead-group-selector-2').val();
						var leadIds = jQuery('#agents-lead-selector').val();
						

						if(action == 'transfer-to-group'){
							//move to group in same agent

							/**set whether or not to move leads out of all other groups and into the selected groups**/
							var transferFromOtherGroups;
							/*if selecting leads from the all leads dropdown, then move leads from all other groups*/
							if(!jQuery('#s2id_agents-lead-group-selector').is(':visible')){
								transferFromOtherGroups = 1;
							}else if(jQuery('#move-leads-from-all-groups').is(':checked')){
								transferFromOtherGroups = 1;
							}else{
								transferFromOtherGroups = 0;
							}

							if(agentLeadGroupSelector != '-1' && agentLeadGroupSelector != '' && agentLeadGroupSelector2 != null && leadIds != null){
								data = {
									execution : 'transfer-leads',
									transfer_from_other_groups : transferFromOtherGroups,
									agent_ids : ["<?php echo $agent_term_id; ?>"],
									agent_ids_2 : ["<?php echo $agent_term_id; ?>"],
									lead_groups : [agentLeadGroupSelector],
									lead_groups_2 : jQuery('#agents-lead-group-selector-2').val(),
									lead_ids : jQuery('#agents-lead-selector').val(),
								};
							}else{
								swal({
									title: "<?php _e('Error', 'inbound-pro'); ?>",
									text: "<?php _e('One of the inputs is empty', 'inbound-pro');?>",
									type: 'error',
								});
								return;
							}
							
						}else if(action == 'transfer-to-agent'){
							//remove from this agent and add to another
							if(agentLeadGroupSelector != '-1' && agentLeadGroupSelector != '' && jQuery('#to-agent-group-selector').val() != null && leadIds != null){	
								data = {
									execution : 'transfer-leads',
									remove_from_agent : 1,
									agent_ids : ["<?php echo $agent_term_id; ?>"],
									agent_ids_2 : jQuery('#to-agent-selector').val(),
									lead_groups : [agentLeadGroupSelector],
									lead_groups_2 : jQuery('#to-agent-group-selector').val(),
									lead_ids : leadIds,
								
								};
							}else{
								swal({
									title: "<?php _e('Error', 'inbound-pro'); ?>",
									text: "<?php _e('One of the inputs is empty', 'inbound-pro');?>",
									type: 'error',									
								});
								return;
								
							}
							
						}else if(action == 'remove-leads'){
							//remove the leads
							
							if(agentLeadGroupSelector != '-1' && agentLeadGroupSelector != '' && leadIds != null){	
								data = {
									execution : 'remove-leads',
									remove_from_agent : (jQuery('#remove-lead-from-agent').is(':checked')) ? 1 : 0,
									agent_ids : ["<?php echo $agent_term_id; ?>"],
									lead_groups : [agentLeadGroupSelector],
									lead_ids : leadIds,
								};
							}else{
								swal({
									title: "<?php _e('Error', 'inbound-pro'); ?>",
									text: "<?php _e('One of the inputs is empty', 'inbound-pro');?>",
									type: 'error',									
								});
								return;
							}
						}else{
							//error
							swal({
								title: "<?php _e('Error', 'inbound-pro'); ?>",
								text: "<?php _e('Unknown action', 'inbound-pro');?>",
								type: 'error',									
							});
							return;
						}
						console.log(data);

						swal({
							title:    "<?php _e('Please wait', 'inbound-pro'); ?>",
							text:     "<?php _e('Working...', 'inbound-pro'); ?>",
							imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
						});
					
						jQuery.ajax({
							type : 'POST',
							url : ajaxurl,
							data : {
								action : 'edit_term_lead_group_data',
								data : data,
								
							},
							success : function(response){
								response = JSON.parse(response);
								
								if(response.success){
									swal({
										title: "<?php _e('Success!','inbound-pro'); ?>",
										text: response.success,
										type: 'success', 
										
									}, function(){
											window.location.reload(true);
										});
									
								}else if(response.error){
									swal({
										title: "<?php _e('Error'); ?>",
										text: response.error,
										type: 'error',
									});
								}
							},
						});
					}
				});
			</script>
	<?php	}
		}
	}

	new Inbound_Assigned_Agents_Agent_Profile;

}




?>
