<?php
if(!class_exists('Inbound_Assigned_Agents_Page')){
	
	class Inbound_Assigned_Agents_Page{
		
		function __construct(){
			self::load_hooks();			
		
		}
		
		function load_hooks(){
			/*enqueue admin scripts*/
			add_action('admin_enqueue_scripts', array(__CLASS__, 'agent_management_page_scripts'));
			
			/*create the agent action form before the usual taxonomy form is created*/
			add_action('inbound_assigned_lead_pre_add_form', array(__CLASS__, 'create_assigned_agent_form'));
		
			/*add the form for creating and editing lead groups*/
			add_action('inbound_assigned_lead_pre_add_form', array(__CLASS__, 'create_lead_group_action_form'));
			
			/* add custom columns */
			add_filter( 'manage_edit-inbound_assigned_lead_columns', array(__CLASS__, 'register_columns' )) ;
			
			/* process custom columns */
			add_action("manage_inbound_assigned_lead_custom_column", array(__CLASS__, 'render_columns'), 10, 3);
		
		}
	
		/**
		 * Enqueues scripts
		 */
		public static function agent_management_page_scripts(){
		global $post;

			if ((isset($post)&&'inbound-forms'=== $post->post_type)||( isset($_GET['post_type']) && $_GET['post_type']==='wp-lead')) {
				$current_screen = get_current_screen();
				if($current_screen->taxonomy == 'inbound_assigned_lead' && $current_screen->base == 'edit-tags'){
					
					/*enqueue css*/
					wp_enqueue_style('assigned-agents-page-styles', INBOUNDNOW_LEAD_ASSIGNMENTS_URLPATH . 'css/assigned-agents-page-style.css', false , true );
					
					/*enqueue sweet alert*/
					wp_enqueue_script('sweet-alert-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.min.js', false , true );
					wp_enqueue_style('sweet-alert-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/SweetAlert/sweetalert.css', false , true );
					
					/* if stand alone plugin */
					wp_enqueue_script('select2-js', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.min.js', false , true );
					wp_enqueue_style('select2-css', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/Select2/select2.css', false , true );

				}

			}
		
		}
	
		/**
		 * Creates the interface for creating and modifying agents
		 */
		public static function create_assigned_agent_form(){

			?>
			<form id="inbound-assign-agent-form" method="POST" action="javascript:void(0)">
				<div id="inbound-assign-agent-input-container">
					<div id="inbound-assign-agent-row-1" style="min-height: 180px;">
						<div id="inbound-assign-agent-actions">
							<h3><?php _e('Select an Agent action', 'inbound-pro'); ?></h3>
							<input type="radio" name="agent-action" value="create-agent"/><?php _e('Create Agent', 'inbound-pro'); ?> <br />
							<input type="radio" name="agent-action" value="edit-agent" />Edit Agent <br />
							<input type="radio" name="agent-action" value="delete-agent" /><?php _e('Delete Agent', 'inbound-pro'); ?> <br />
						</div>

						<div id="inbound-agent-avatar"></div>
					</div>
					<!--create agent-->
					<div id="inbound-agents-create-agent-container" class="agent-action-container">
						<select id="inbound-agents-possible-agent-dropdown[]" class="inbound-agent-dropdown">
						<option value="-1"><?php _e('Please select a user', 'inbound-pro'); ?></option>
						<?php	/*Note the options are url encoded*/
								foreach(Inbound_Assigned_Agents_Resources::$possible_agents as $key=>$value){
								echo '<option value="&id=' . $key . '&display_name=' . $value . '">' . $value . '</option>';
								}
						?>
						</select>
						<button class="inbound-assign-agent-form-submit-button" type="submit"><?php _e('Create Agent', 'inbound-pro');?></button>
					</div>
					<!--edit-agent-->
					<div id="inbound-agents-edit-agent-container" class="agent-action-container">
						<select id="inbound-agents-edit-assigned-agent-dropdown[]" class="inbound-agent-dropdown">
						<option value="-1"><?php _e('Please select an agent', 'inbound-pro'); ?></option>
						<?php	foreach(Inbound_Assigned_Agents_Resources::$assigned_agents as $key=>$value){
								echo '<option value="' . $key . '">' . $value . '</option>';
								}
						?>
						</select>
						<button type="button" id="inbound-agents-get-agent-data-button"><?php _e('Get Agent Data', 'inbound-pro'); ?></button>
						<div id="inbound-agents-agent-data-delete-mode-container">
							<label for="inbound-agents-agent-data-delete-mode" title="<?php _e('Activate delete mode to enable the deletion of unneeded data fields','inbound-pro'); ?>"><?php _e('Delete Data Mode', 'inbound-pro');?></label><br />
							<input type="radio" name="inbound-agents-agent-data-delete-mode" value="0" checked="checked"/><?php _e('Off', 'inbound-pro');?>
							<input type="radio" name="inbound-agents-agent-data-delete-mode" value="1"/><?php _e('On', 'inbound-pro');?>
						</div>
						<div id="inbound-agents-agent-data-interface-container">
							<div id="inbound-agents-agent-data-header"><?php _e('Agent Data', 'inbound-pro'); ?> </div>
							<div id="inbound-agents-agent-data-interface"></div>
						</div>
						<button class="inbound-assign-agent-form-submit-button" type="submit"><?php _e('Edit Agent Data', 'inbound-pro');?></button>
					</div>
					<!--delete agent-->
					<div id="inbound-agents-delete-agent-container" class="agent-action-container">
						<select id="inbound-agents-assigned-agent-dropdown[]" class="inbound-agent-dropdown">
						<option value="-1"><?php _e('Please select an agent', 'inbound-pro'); ?></option>
						<?php	foreach(Inbound_Assigned_Agents_Resources::$assigned_agents_by_UID as $key=>$value){
								echo '<option value="&id=' . $key . '&display_name=' . $value . '">' . $value . '</option>';
								}
						?>
						</select><br />
						<label><?php _e('Note: Agents delete faster if you remove all leads from them first', 'inbound-pro'); ?></label><br />
						<button class="inbound-assign-agent-form-submit-button" type="submit"><?php _e('Delete Agent', 'inbound-pro');?></button>
					</div>
				</div>
			</form>
			<script>
				jQuery(document).ready(function () {

					/*** AGENT FORM ACTION JS ***/	
					/*select2*/
					jQuery('.inbound-agent-dropdown').select2();
					
					//Only show one agent selector at a time
					jQuery('#inbound-assign-agent-actions').on('click', function(){
						var agentAction = jQuery('input[name=agent-action]:checked').val();
						//if the action is to create an agent, show the list of potential agents
						if(agentAction == 'create-agent'){
							jQuery('.agent-action-container').css({'display' : 'none'});
							jQuery('#inbound-agent-avatar').html('');
							jQuery('#inbound-agents-create-agent-container').css({'display' : 'inline-block'});
							jQuery('#inbound-agents-possible-agent-dropdown\\[\\]').val('-1').trigger('change' );
							
						}
						//if it's to edit an agent, show the edit agent screen
						else if(agentAction == 'edit-agent'){
							jQuery('.agent-action-container').css({'display' : 'none'});
							jQuery('#inbound-agent-avatar').html('');
							jQuery('#inbound-agents-edit-agent-container').css({'display' : 'inline-block'});
							jQuery('#inbound-agents-edit-assigned-agent-dropdown\\[\\]').val('-1').trigger('change' );
							//console.log(jQuery('#inbound-agents-assigned-agent-dropdown\\[\\]').val());

						//if it's to delete an agent, show the list of existing agents
						}else if(agentAction == 'delete-agent'){
							jQuery('.agent-action-container').css({'display' : 'none'});
							jQuery('#inbound-agent-avatar').html('');
							jQuery('#inbound-agents-delete-agent-container').css({'display' : 'inline-block'});
							jQuery('#inbound-agents-assigned-agent-dropdown\\[\\]').val('-1').trigger('change' );
						}

						});
					
					/**get the user avatar for the agent form**/
					jQuery('.inbound-agent-dropdown').on('change', function(){
						var selector = jQuery('.inbound-agent-dropdown:visible').select2('data')[0];
						/*if a user is selected*/
						if(selector.id != 'null' && selector.id != '-1' && selector.id != undefined){
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_profile_image',
									data : selector.id,
									
								},
								success : function(response){
									response = JSON.parse(response);
									jQuery('#inbound-agent-avatar').html('');
									jQuery('#inbound-agent-avatar').html('<img src="' + response +  '" height="140" width="140">');
									console.log(response);
								
								},
							});
						}else{
							jQuery('#inbound-agent-avatar').html('');
						}
					});
					
					/**get the agent extra data**/
					jQuery('#inbound-agents-get-agent-data-button').on('click', function(){
						var selector = jQuery('.inbound-agent-dropdown:visible').select2('data')[0];
						
						if(selector.id != -1 && selector.id != ''){
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_agent_extra_data',
									data : {
										agent_id : selector.id,
										fields: null,
										return_all_fields : 1,
										
										},
								},
								success : function(response){
									response = JSON.parse(response);

									jQuery('#inbound-agents-agent-data-interface').empty();
									
									var list = '';
									var agentDataList = '';
									jQuery.each(response, function(field_key){
										agentDataList += '<li class="agent-data-li" field_key="'+ field_key +'"><input type="checkbox" class="agent-data-delete-checkbox" title="Check this box to have the field deleted from the agent\'s data." value="'+ field_key +'" /><div class="agent-data-input-container"><p id="agent-data-input-label-text">' + Object.keys(response[field_key]) + '</p><input type="text" class="agent-data-li-input" value="' + response[field_key][Object.keys(response[field_key])] + '" /></div></li>';

									});						
									
									list += '<ul class="agent-data-ul">'+ agentDataList +'</ul>';
									list += '<li id="agent-data-add-field-li"><label for="agent-data-label-input">Field Label</label><br /><input type="text" id="agent-data-label-input"></li>';			
									list += '<div id="add-field-container"><button type="button" id="add-field-button" title="' + "<?php _e('Click this button to add a new field to the display. This only adds the field to the display, to save the field and it\'s data you must click \"Edit Agent Data\"', 'inbound-pro'); ?>" + '">Add New Field</button></div>';						
									
									jQuery('#inbound-agents-agent-data-header, #inbound-agents-agent-data-delete-mode-container').css({'display' : 'inline-block'});
									jQuery('#inbound-agents-agent-data-interface').append(list);
									
									jQuery('#add-field-button').on('click', function(){
										if(jQuery('#agent-data-label-input').val() != '' && jQuery('#agent-data-label-input').val() != undefined){
											var newField = '';
											var field_key = jQuery('#agent-data-label-input').val();
											field_key = field_key.replace(/[^A-Za-z ]/g, "").trim().toLowerCase();
											field_key = field_key.replace(/\ /g, "_")
											console.log(field_key);
											
											newField = '<li class="agent-data-li" field_key="'+ field_key +'"><input type="checkbox" class="agent-data-delete-checkbox" title="' + "<?php _e('Check this box to have the field deleted from the agent\'s data.', 'inbound-pro'); ?>" + '" value="'+ field_key +'" /><div class="agent-data-input-container"><p id="agent-data-input-label-text">' + jQuery('#agent-data-label-input').val() + '</p><input type="text" class="agent-data-li-input" value="" /></div></li>';
											jQuery('.agent-data-ul').append(newField);
											jQuery('#agent-data-label-input').val('');
											delete newField;
										}else{
											swal({
												title: "<?php _e('Error', 'inbound-pro'); ?>",
												text:  "<?php _e('Label field is empty, please enter a label', 'inbound-pro'); ?>",
												type:  'error',
											});
										}
									});
								},
							});
						}else{
							swal({
								title: "<?php _e('No agent selected', 'inbound-pro'); ?>",
								text:  "<?php _e('Please select an agent', 'inbound-pro'); ?>",
								type:  'error',
							});
						
						
						}
					});
					
					/**listener for the delete mode radio**/
					jQuery('#inbound-agents-agent-data-delete-mode-container').on('click', function(){
						if(jQuery('input[name=inbound-agents-agent-data-delete-mode]:checked').val() == '1'){
							jQuery('.agent-data-delete-checkbox').css({'display' : 'inline-block'});
						}else{
							jQuery('.agent-data-delete-checkbox').css({'display' : 'none'});
						}
					});

					/**perform agent actions**/
					jQuery('.inbound-assign-agent-form-submit-button').on('click', function(){
						var user_id;
						var selector = jQuery('.inbound-agent-dropdown:visible').select2('data')[0];
						user_id = [selector.id];
						
						/*if agent extra data is being edited*/
						if(jQuery('input[name=agent-action]:checked').val() == 'edit-agent'){
							if(selector.id != '-1'){
								var fieldData = {};
								var fieldsToDelete;
								
								swal({
									title:    "<?php _e('Please wait', 'inbound-pro'); ?>",
									text:     "<?php _e('Working...', 'inbound-pro'); ?>",
									imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
								});
								
								/*map the data object*/
								fieldData = jQuery(".agent-data-ul").find(".agent-data-li").map(function () {
									/*create the starting objects*/
									var obj = {};
									var obj2 = {};
									/*the label is the displayed text, value is the value of the input*/
									var label = jQuery(this).find('#agent-data-input-label-text').text().trim();
									var value = jQuery(this).find('input.agent-data-li-input').val();
									
									/*create the label/value object*/
									obj2[label] = value;
									
									/*and place it in the main object in the index of field_key*/
									obj[jQuery(this).attr('field_key')] = obj2;

									return obj;
								}).get();
								
								if(jQuery('input[name=inbound-agents-agent-data-delete-mode]:checked').val() == '1'){
									/*map the field object*/
									fieldsToDelete = jQuery(".agent-data-ul").find("input.agent-data-delete-checkbox:checked").map(function () {
										return this.value;
									}).get();
								}

								jQuery.ajax({
									type : 'POST',
									url : ajaxurl,
									data : {
										action : 'edit_agent_extra_data',
										data : {
											agent_id : selector.id,
											field_data : fieldData,
											fields_to_delete : (fieldsToDelete) ? fieldsToDelete : '',
										},
									},
									success : function(response){
										response = JSON.parse(response);
										console.log(response);
										
										if(response.success){
											swal({
												title: "<?php _e('Success!', 'inbound-pro'); ?>",
												text: response.success,
												type: 'success',
											}, function(){
											window.location.reload(true);
											});
										}else if(response.error){
											swal({
												title: "<?php _e('Error', 'inbound-pro'); ?>",
												text: response.error,
												type: 'error',
											});
										}else if(response.access_denied){
											swal({
												title: "<?php _e('Access Denied', 'inbound-pro'); ?>",
												text: response.access_denied,
												type: 'warning',
											});
											
										}else{
											swal({
												title: "<?php _e('Error', 'inbound-pro'); ?>",
												text: "<?php _e('No data returned', 'inbound-pro'); ?>",
												type: 'error',
											});
										
										}
										
									},
								});
							}else{
								swal({
									title: "<?php _e('No agent selected', 'inbound-pro'); ?>",
									text:  "<?php _e('Please select an agent', 'inbound-pro'); ?>",
									type: 'error',
								});
								
							}
						}else{
						/*if an agent is being created or deleted*/
							if(selector.id != '-1'){
								if(jQuery('input[name=agent-action]:checked').val() == 'delete-agent'){
									swal({
										title:    "<?php _e('Please wait', 'inbound-pro'); ?>",
										text:     "<?php _e('Deleting agent...', 'inbound-pro'); ?>",
										imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
									});
								}else{
									swal({
										title:    "<?php _e('Please wait', 'inbound-pro'); ?>",
										text:     "<?php _e('Working...', 'inbound-pro'); ?>",
										imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
									});
								}
								
								jQuery.ajax({
									type : 'POST',
									url : ajaxurl,
									data : {
										action : 'agent_term_actions',
										data : {
											execution : jQuery('input[name=agent-action]:checked').val(),
											user_data : user_id,
										},
									},
									success : function(response){
										response = JSON.parse(response);
										console.log(response);
										
										if(response.success){
											swal({
												title: "<?php _e('Success!', 'inbound-pro'); ?>",
												text: response.success,
												type: 'success',
											}, function(){
												window.location.reload(true);
											});
										}else if(response.not_success){
											swal({
												title: "<?php _e('Uh oh', 'inbound-pro'); ?>",
												text: response.not_success,
												type: 'warning',
											}, function(){
												window.location.reload(true);
											});
										
										}else if(response.error){
											swal({
												title: "<?php _e('Error', 'inbound-pro'); ?>",
												text: response.error,
												type: 'error',
											}, function(){
												window.location.reload(true);
											});
										}else if(response.access_denied){
											swal({
												title: "<?php _e('Access Denied', 'inbound-pro'); ?>",
												text: response.access_denied,
												type: 'warning',
											});
											
										}else{
											swal({
												title: "<?php _e('Error', 'inbound-pro'); ?>",
												text: "<?php _e('No data returned', 'inbound-pro'); ?>",
												type: 'error',
											});
										}
									},
								});
							}else{
								if(jQuery('input[name=agent-action]:checked').val() == 'delete-agent'){
									swal({
										title: "<?php _e('No agent selected', 'inbound-pro'); ?>",
										text:  "<?php _e('Please select an agent', 'inbound-pro'); ?>",
										type: 'error',
									});
								}else{
									swal({
										title: "<?php _e('No user selected', 'inbound-pro'); ?>",
										text:  "<?php _e('Please select a user', 'inbound-pro'); ?>",
										type: 'error',
									});									
								}
							}
						}	
					});
				});
			</script>
			<?php 
		}//end create_assigned_agent_form()
	
	
	
	
		/**
		 * Creates the interface for interacting with lead groups
		 */

		public static function create_lead_group_action_form(){
			$agents = Inbound_Assigned_Agents_Resources::$assigned_agents;;
			$limit_policy = Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_lead_group_limits');

			/**create the dropdown list of assigned agents. Values are "term id" => "display name"**/
			$assigned_agents_dropdown_options = '';
			foreach($agents as $key=>$value){
				$assigned_agents_dropdown_options .= '<option value="' . $key . '">' . $value . '</option>';
			}
			
			?>
			<form id="term-lead-group-form">
				<h3><?php _e('Select a Lead Group action:', 'inbound-pro'); ?></h3>
				<div id="inbound-agents-lead-group-actions">
					<input type="radio" name="lead-group-action" value="create-lead-group" title="<?php _e('Create lead managing groups for leads','inbound-pro');?>" /><?php _e('Create Lead Group', 'inbound-pro'); ?> <br />
					<input type="radio" name="lead-group-action" value="edit-lead-group-limits" title="<?php _e('Edit the form submission limits for agent groups. Leads generated by forms will not be added to an agent if the limit is reached or exceeded','inbound-pro');?>"  /><?php _e('Edit Lead Group Limits', 'inbound-pro'); ?> <br />
					<input type="radio" name="lead-group-action" value="clone-lead-group" title="<?php _e('Clone lead groups from agents to agents who don\'t have them. Optionally clones leads as well','inbound-pro');?>"  /><?php _e('Clone Lead Group', 'inbound-pro'); ?> <br />
					<input type="radio" name="lead-group-action" value="delete-lead-group" title="<?php _e('Delete lead groups from agents. Removes lead groups, but leads still have to be removed from the agent via Bulk Actions or Lead action in the agent\'s profile','inbound-pro');?>"  /><?php _e('Delete Lead Group', 'inbound-pro'); ?> <br />
				</div>
								
				<!--create lead group-->
				<div id="create-lead-group" style="display: none;">
					<div class="lead-group-input-container">
						<label id="term_lead_group_name_input-label" for="term_lead_group_name_input"><?php _e('Lead Group Name, for multiple groups separate with commas', 'inbound-pro'); ?></label><br />
						<input type="text" Id="term_lead_group_name_input" /><br />
						<label for="create-lead-group-agent-select"><?php _e('For:', 'inbound-pro'); ?></label><br />
						<select id="create-lead-group-agent-select" class="agent-selector" multiple="multiple">
							<?php echo $assigned_agents_dropdown_options; ?>
						</select><br />
					</div>
					<button type="button" id="term_lead_group_button" class="inbound-agents-group-action-button"><?php _e('Create lead groups', 'inbound-pro'); ?> </button><br />
				</div>
				
				<!--edit lead group limits-->
				<div id="edit-lead-group-limits" style="display: none">
					<div id="edit-lead-group-limits-container">
						<div id="edit-lead-group-limits-inputs">
							<?php
							if($limit_policy == '1'){
							?>
							<label for="edit-lead-group-limits-agent-multiselect"><?php _e('Agents to have limits set for', 'inbound-pro'); ?> </label>
							<select id="edit-lead-group-limits-agent-multiselect" class="agent-selector first-agent-selector" multiple="multiple" title="<?php _e('Setting limits on lead groups prevents new leads from being assigned to an agents group', 'inbound-pro'); ?>">
								<?php echo $assigned_agents_dropdown_options;?>
							</select><br>
							<label for="edit-lead-group-limits-select"><?php _e('Lead groups to set limits for', 'inbound-pro'); ?> </label>
							<select id="edit-lead-group-limits-select" class="lead-group-select" multiple="multiple">
								<option><?php _e('No groups found', 'inbound-pro'); ?> </option>
							</select><br>
							<?php 
							}else{
							 ?>
							<label for="edit-lead-group-limits-select"><?php _e('Lead groups to set limits for', 'inbound-pro'); ?> </label>
							<select id="edit-lead-group-limits-select" class="/*lead-group-select*/" multiple="multiple">
								<?php
								$options = array();
								foreach(Inbound_Assigned_Agents_Resources::$lead_group_limits as $groups){
									$options = array_merge($options, array_keys($groups));
								}
								foreach(array_unique($options) as $option){
									echo '<option value="' .$option. '">'. $option .'</option>';
								}
								
								?>
							</select><br>
							<?php } ?>
							<button type="button" id="get-lead-group-limits-button" class="inbound-agents-group-action-button"><?php _e('Get Lead Group limits', 'inbound-pro'); ?> </button><br>
							<button type="button" id="set-lead-group-limits-button" class="inbound-agents-group-action-button"><?php _e('Set Lead Group limits', 'inbound-pro'); ?> </button><br>
						</div>
						<div id="lead-group-limit-interface-container">
							<div id="lead-group-limit-header"><?php _e('Lead Group Limits', 'inbound-pro'); ?> </div>
							<div id="lead-group-limit-interface"><div id="lead-group-limit-interface-label"><?php _e('A limit of -1 means the limit is set to infinite.', 'inbound-pro'); ?></div></div>
							
						</div>
					</div>
				</div>
				<!--clone lead group-->
				<div id="clone-lead-group" style="display: none">
					<div class="lead-group-input-container">
						<p><?php _e('Clone an existing lead group to apply it to other agents', 'inbound-pro'); ?> </p>
						<label for="clone-from-agents"><?php _e('Agents to clone from', 'inbound-pro'); ?> </labeL><br>
						<select multiple="multiple" name="clone-from-agents" id="clone-from-agents-multiselect" class="agent-selector first-agent-selector" title="<?php _e('Select the groups to clone lead groups from. Cloning a lead group makes a duplicate group for another agent if they don\'t already have it.', 'inbound-pro'); ?>">
							<?php echo $assigned_agents_dropdown_options; ?>
						</select><br>
						<label for="clone-lead-group-select"><?php _e('Groups to clone', 'inbound-pro'); ?> </label>
						<select id="clone-lead-group-select" class="lead-group-select" multiple="multiple">
							<option><?php _e('No groups found', 'inbound-pro'); ?> </option>
						</select><br>
						<label for="clone-to-agents"><?php _e('Agents to clone to', 'inbound-pro'); ?> </labeL><br>
						<select multiple="multiple" name="clone-to-agents" id="clone-to-agents-multiselect" class="agent-selector second-agent-selector">
							<?php echo $assigned_agents_dropdown_options; ?>
						</select><br>
						<input type="checkbox" id="clone-lead-group-checkbox" name="clone-lead-group-checkbox" <?php if( Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_lead_cloning') ==  '1'){ echo 'checked="checked"'; }?>>
						<label for="clone-lead-group-checkbox"><?php _e('Clone leads when cloning group?', 'inbound-pro'); ?> </label><br />
					</div>
					<button type="button" id="clone_term_lead_group_button" class="inbound-agents-group-action-button"><?php _e('Clone selected lead group', 'inbound-pro'); ?> </button>
				</div>
				
				<!--delete lead group-->
				<div id="delete-lead-group" style="display: none">
					<div class="lead-group-input-container">
						<label for="delete-from-agents-multiselect"><?php _e('Select agents to delete lead groups from', 'inbound-pro'); ?> </label>
						<select multiple="multiple" id="delete-from-agents-multiselect" class="agent-selector first-agent-selector" title="<?php _e('Select the agents to delete lead groups from. Selecting multiple agents will populate the group selector with the groups that all the agents have in common.', 'inbound-pro'); ?>">
							<?php echo $assigned_agents_dropdown_options; ?>
						</select><br>
						<label for="lead-group-select"><?php _e('Select the groups to delete', 'inbound-pro'); ?> </label>
						<select id="lead-group-select" class="lead-group-select" multiple="multiple">
							<option><?php _e('No groups found', 'inbound-pro'); ?> </option>
						</select><br>
					</div>
					<button type="button" id="delete_term_lead_group_button" class="inbound-agents-group-action-button"><?php _e('Delete selected lead group', 'inbound-pro'); ?> </button>
				</div>
			</form>
			<script>
				jQuery(document).ready(function(){
				/***AGENT GROUP JS***/
				var wait;
				var limitPolicy = "<?php echo $limit_policy; ?>";

				/**spinner var**/	
				var opts = {
					 lines: 13, // The number of lines to draw
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
					 className: 'group-spinner', // The CSS class to assign to the spinner
					 top: '16px', // Top position relative to parent
					 left: 'calc(100% + 25px)', // Left position relative to parent
					 shadow: false, // Whether to render a shadow
					 hwaccel: false, // Whether to use hardware acceleration
					 position: 'relative', // Element positioning
				}
	
					/**call select2**/
					jQuery('.agent-selector,#clone-to-agents-multiselect,.lead-group-select,#edit-lead-group-limits-select').select2();
					
					/**move the searchbox**/
					jQuery('.alignleft.actions.bulkactions').after(jQuery('.search-form.wp-clearfix').detach());	
					
					/**set which group option to display**/
					var oldChecked;
					jQuery('#inbound-agents-lead-group-actions').on('click', function(){
						jQuery('#' + oldChecked).css({'display' : 'none'});
						jQuery('#' + jQuery('input[name=lead-group-action]:checked').val()).css({'display' : 'inline-block'});
						oldChecked = jQuery('input[name=lead-group-action]:checked').val();
					});
				
					/**create agent group**/
					jQuery('#term_lead_group_button').on('click', function(){
						console.log(jQuery('#create-lead-group-agent-select').val());
						var agentIds = jQuery('#create-lead-group-agent-select').val();
						var leadGroupNames = jQuery('#term_lead_group_name_input').val();
						if(leadGroupNames != '' && agentIds != '' && leadGroupNames != null && agentIds != null){
							swal({
								title: "<?php _e('Please Wait', 'inbound-pro'); ?>",
								text: "<?php _e('Working...', 'inbound-pro'); ?>",
								imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
							});
							
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'create_term_lead_group',
									data : {
										lead_group_names : jQuery('#term_lead_group_name_input').val(),
										agent_ids : jQuery('#create-lead-group-agent-select').val(),
									},
								},
								success : function(response){
									response = JSON.parse(response);
									console.log(response);
									
									if(response.access_denied){
										swal({
											title: "<?php _e('Access Denied', 'inbound-pro'); ?>",
											text: response.access_denied,
											type: 'warning',
										});
									}else{
										swal({
											title: "<?php _e('All Finished', 'inbound-pro'); ?>",
											text: response,
											type: 'success',
										}, function(){
											window.location.reload(true);
										});
									}
								},
							});
						}else{
							swal({
								title: "<?php _e('Error', 'inbound-pro'); ?>",
								text: "<?php _e('One or more of the Create Group fields is empty', 'inbound-pro'); ?>",
								type: 'error',
							});
						
						}
					});

					/**get lead group form submission limits**/
					jQuery('#get-lead-group-limits-button').on('click', function(){
						var agentCheck = 0;
						
						/*if the agent selector is visible, check it for a value*/
						if(jQuery('#s2id_edit-lead-group-limits-agent-multiselect').is(':visible')){
							if(jQuery('#edit-lead-group-limits-agent-multiselect').val() != null){
								agentCheck = 1
							}
						}else{
							agentCheck = 1;
						}
						
						if(jQuery('#edit-lead-group-limits-select').val() && agentCheck){
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_term_lead_limits',
									data : {
										lead_groups : jQuery('#edit-lead-group-limits-select').val(),
										agent_ids : jQuery('#edit-lead-group-limits-agent-multiselect').val(),
									},
								},
								success : function(response){
									var agentObject = {};
									var list = '';
									response = JSON.parse(response);

									/*if limits have been returned*/
									if(response){
										/*if the limit policy is to set limits for agents individually*/
										if(limitPolicy == 1){
											jQuery('#lead-group-limit-interface').empty();
											/*create the agent limit object*/
											jQuery.each(response, function(agentId, values){
												if( typeof agentObject[agentId] == 'undefined' ){ agentObject[agentId] = '';}
												jQuery.each(values, function(groupName, limits){
													var agentLimitListItem = '<li class="lead-group-limit-li set-by-agent" group="'+ groupName +'">' + groupName + ': <input type="text" class="lead-group-limit-li-input" value="' + limits + '" /></li>';
													agentObject[agentId] += agentLimitListItem;
													//console.log('The agent id is: ' + agentId + '. The group name is: ' + groupName + '. And the group limit is: ' + limits + '. ');
												});
											});
											
											jQuery.each(agentObject, function(agentId, limits){
												list +=  '<h4 class="edit-lead-group-limits-agent-header">' + jQuery('#edit-lead-group-limits-agent-multiselect option[value="'+agentId+'"]').html() + '</h4>' + '<ul class="agent-limit-list" id="' + agentId + '">' + limits + '</ul>';
												
											});
											jQuery('#lead-group-limit-interface').append(list);
										}else{
											jQuery('#lead-group-limit-interface').empty();
											
											jQuery.each(response, function(groupName, limits){
													agentObject[groupName] = '<li class="lead-group-limit-li set-by-group" group="'+ groupName +'">' + groupName + ' : <input type="text" class="lead-group-limit-li-input" value="' + limits + '" /></li>';
											});
											
											jQuery.each(agentObject, function(leadGroup, groupListItem){
												list += '<ul class="lead-group-limit-ul">'+ groupListItem +'</ul>';
											});
											jQuery('#lead-group-limit-interface').append(list);
										}
									}else{
										
										swal({
											title: "<?php _e('No limits found!', 'inbound-pro'); ?>",
											type: 'error',
										});
									}
								},
							});
						}else{
							swal({
								title: "<?php _e('Error', 'inbound-pro'); ?>",
								text: "<?php _e('One of the Lead Group input fields is empty', 'inbound-pro'); ?>",
								type: 'error',
							});
							
						}

					});
					
					/**set lead group form submission limits**/
					jQuery('#set-lead-group-limits-button').on('click', function(){
						var groupData = {},
							agentGroupData = {},
							agents = jQuery('.agent-limit-list'),
							inputs = jQuery('.lead-group-limit-li-input');

						/*if the policy is to get and set group limits for individual agents*/
						if(limitPolicy == 1){
							/*extract the inputed values from the set limits display*/
							jQuery.each(agents, function(index, agent){
								var obj = {};
								jQuery.each(jQuery(agent).children(), function(index, limitItem){
									obj[jQuery(limitItem).attr('group')] = jQuery(limitItem).find('input').val();
								
								});
								agentGroupData[jQuery(agent).attr('id')] = obj;
								delete obj;
							});
						}else{
							/*extract the inputed values from the set limits display*/
							jQuery.each(inputs, function(index, value){
								groupData[jQuery(value).closest('.lead-group-limit-li').attr('group')] = value.value;
							});
							agentGroupData[0] = groupData;
						
						}

						/*if there is limit data to process*/
						if(!jQuery.isEmptyObject(agentGroupData)){
							
							/*create the working popup*/
							swal({
								title: "<?php _e('Please Wait', 'inbound-pro'); ?>",
								text: "<?php _e('Working...', 'inbound-pro'); ?>",
								imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
							});
							
							/*send off the data to be processed into fields and limits*/
							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'edit_term_lead_limits',
									data : {
										agent_group_data : agentGroupData,
									},
								},
								success : function(response){
									response = JSON.parse(response);

									if(response.access_denied){
										swal({
											title: "<?php _e('Access Denied', 'inbound-pro'); ?>",
											text: response.access_denied,
											type: 'warning',
										});
									}else{
										swal({
											title: "<?php _e('Limits Updated!', 'inbound-pro'); ?>",
											type: 'success',
										}, function(){
											window.location.reload(true);
										});
									}
								},
								error(MLHttpRequest, textStatus, errorThrown) {
									swal({
										title: "<?php _e('ERROR', 'inbound-pro'); ?>",
										text: "<?php _e('An unkown error has occured', 'inbound-pro')?>",
										type: 'error',
									});
								},
							});
						}else{
							swal({
								title: "<?php _e('No limit data', 'inbound-pro'); ?>",
								text: "<?php _e('The limit data interface is empty', 'inbound-pro')?>",
								type: 'error',
							});
						}
					});	
						
					/**clone or delete lead groups**/
					jQuery('#clone_term_lead_group_button,#delete_term_lead_group_button').on('click', function(){
						if(jQuery('#clone-lead-group-checkbox:checked').val() == 'on'){
							cloneLeads = 1;
						}else{
							cloneLeads = 0;
						}
						
						var data = {
								execution   : jQuery('input[name=lead-group-action]:checked').val(),
								agent_ids   : jQuery('#' + jQuery('.first-agent-selector:visible').attr('id')).val(),
								agent_ids_2 : jQuery('.second-agent-selector').is(':visible') ? jQuery('#' + jQuery('.second-agent-selector:visible').attr('id')).val() : '',
								lead_groups : jQuery('#' + jQuery('.lead-group-select:visible').attr('id')).val(),
								clone_leads : cloneLeads,
						}
						
						if(data.agent_ids && data.lead_groups && data.agent_ids_2 != null){
							swal({
								title: "<?php _e('Please Wait','inbound-pro');?>",
								text: "<?php _e('Working...', 'inbound-pro');?>",
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
									console.log(response);
									if(response.success){
										swal({
											title: "<?php _e('Success!', 'inbound-pro'); ?>",
											text: response.success,
											type: 'success',
											
										}, function(){
											window.location.reload(true);
										});
									}else if(response.not_success){
										swal({
											title: "<?php _e('Hhm...', 'inbound-pro'); ?>",
											text: response.not_success,
											type: 'warning',
											
										});
									}else if(response.access_denied){
											swal({
												title: "<?php _e('Access Denied', 'inbound-pro'); ?>",
												text: response.access_denied,
												type: 'warning',
											});
									}else{
										swal({
											title: "<?php _e('Error', 'inbound-pro'); ?>",
											text: response.error,
											type: 'error',
										});
									}
								},	
							});
						
						}else{
							swal({
								title: "<?php _e('Error', 'inbound-pro'); ?>",
								text: "<?php _e('One of the input fields is empty', 'inbound-pro'); ?>",
								type: 'error',
							});
						}
					});
					
					/**refresh the lead group selector after a wait of 950ms**/
					jQuery('.first-agent-selector').on('change', function(){
						clearTimeout(wait);
						wait = setTimeout(refreshSelector, 950);
					});
					
					/**refresher**/
					function refreshSelector(){
						
						/*clear the selector*/
						jQuery('.lead-group-select').select2('data', null);
						jQuery('.lead-group-select').find('option').remove();

						/*remove the status icon*/
						jQuery('#agent-group-loading-status-icon').remove();
						
						if(jQuery('#' + jQuery('.first-agent-selector:visible').attr('id')).val() != null){						
							
							/*create a new spinner*/
							var target = jQuery('.lead-group-select.select2-container:visible');
							var spinner = new Spinner(opts).spin(target[0]);
							
							/*if the group action is to change lead group limits, add some css to change the spinner's location*/
							if(jQuery('input[name=lead-group-action]:checked').val() == 'edit-lead-group-limits'){ jQuery('.group-spinner').css({'position' : 'absolute', 'left': 'calc(100% - 12px)', 'top' : 'calc(100% + 20px)',}); }

							/*remove the status icon*/
							jQuery('#agent-group-loading-status-icon').remove();

							jQuery.ajax({
								type : 'POST',
								url : ajaxurl,
								data : {
									action : 'get_agent_term_lead_groups',
									data : jQuery('#' + jQuery('.first-agent-selector:visible').attr('id')).val(), 
									get_groups_in_common : 1,
									},
								success : function(agentLeadGroups){
									agentLeadGroups = JSON.parse(agentLeadGroups);
	
									/*on success remove the spinner*/
									jQuery('.group-spinner').remove();
									
									/*if groups have been returned, show the green checkmark*/
									if(agentLeadGroups != null){
										if(jQuery('input[name=lead-group-action]:checked').val() == 'edit-lead-group-limits'){
											jQuery('.lead-group-select.select2-container:visible').append('<div id="agent-group-loading-status-icon" style="position: absolute; left:calc(100% - 24px); top: calc(100% + 12px);" ><i id="agent-success-checkmark" style="color: green; font-size: 24px;" class="fa fa-check" aria-hidden="true"></i></div>');
										}else{
											jQuery('.lead-group-select.select2-container:visible').prepend('<div id="agent-group-loading-status-icon" style="position: absolute; left: calc(100% + 12px); top:5px;"><i id="agent-success-checkmark" style="color: green; font-size: 24px;" class="fa fa-check" aria-hidden="true"></i></div>');
										}
									}		
										
									var options = '';
									/*create the options out of the lead groups that the agents have in common*/
									if(agentLeadGroups != null){
										for(var i = 0; i < agentLeadGroups.length; i++){
											options += '<option value="' + agentLeadGroups[i] + '">' + agentLeadGroups[i] + '</option>';
										}
									}

									//add the options to the selector
									jQuery('#' + jQuery('.lead-group-select:visible').attr('id')).append(options);
								},
								error: function (MLHttpRequest, textStatus, errorThrown) {
									alert("Ajax not enabled");
											
									/*On failure remove the spinner and show the failure Xmark*/
									jQuery('.group-spinner').remove();
									jQuery('.lead-group-select:visible.select2-container:visible').prepend('<div id="agent-group-loading-status-icon" style="position: absolute; left: calc(100% + 12px); top:5px;"><i id="agent-failure-xmark" style="color: red; font-size: 24px;" class="fa fa-times" aria-hidden="true"></i></div>');							
								},
							});
						}	
					}
					
					/**remove from list fallback button**/
					jQuery('.delete-agent-term').on('click', function(clicked){
						swal({
							title: "<?php _e('Are you sure?', 'inbound-pro'); ?>",
							text: "<?php _e('This will delete the agent', 'inbound-pro'); ?>",
							type: 'warning',
							showCancelButton: true,
							confirmButtonColor: "#DD6B55",
							confirmButtonText: "<?php _e('Confirm', 'inbound-pro'); ?>",
							closeOnConfirm: false,
						},
						function(){
							deleteAgentTerm(jQuery(clicked.target).attr('value'));
						});
					});
					
					function deleteAgentTerm(data){
						swal({
							title: "<?php _e('Please Wait','inbound-pro');?>",
							text: "<?php _e('Working...', 'inbound-pro');?>",
							imageUrl: "<?php echo INBOUNDNOW_SHARED_URLPATH; ?>assets/includes/SweetAlert/loading_colorful.gif",
						});
						jQuery.ajax({
							type : 'POST',
							url : ajaxurl,
							data : {
								action : 'list_action_delete',
								data : data,
							},
							success : function(response){
								response = JSON.parse(response);
								console.log(response);
								if(response.error){
									swal({
										title: "<?php _e('Error', 'inbound-pro'); ?>",
										text: response.error,
										type: 'error',
									});
								}else if(response.access_denied){
									swal({
										title: "<?php _e('Access Denied', 'inboud-pro'); ?>",
										text: response.success,
										type: 'warning',
									});
								}else if(response.success){
									swal({
										title: "<?php _e('Success', 'inbound-pro'); ?>",
										text: response.success + ' ' + "<?php _e('Reload the page to refresh the agent action form', 'inbound-pro'); ?>",
										type: 'success',
									});
									jQuery('#tag-' + response.tag_id).css({'background' : 'red'});
									jQuery('#tag-' + response.tag_id).animate({'opacity' : '0'}, 750, 'linear', function(){jQuery('#tag-' + response.tag_id).remove();});
								
								}
							},
						});
					}
				});	
			</script>
		<?php	
		}		
	
	/**
     * Register custom columns for the wp-lead post type
     * @param $cols
     * @return array
     */
    public static function register_columns($cols) {

        $cols = array(
            "agent_name" => __('Name', 'inbound-pro'),
            "profile-picture" => __('Gravatar', 'inbound-pro' ),
            "title" => __('Job Title', 'inbound-pro' ),
            "added-as-agent" => __('Agent Since:', 'inbound-pro' ),
            "limits" => __('Lead Group Limits', 'inbound-pro' ),
            "lead-group-summary" => __('Lead Group Summary', 'inbound-pro' ),
            "posts" => __('Total Leads', 'inbound-pro'),
        );
        return $cols;
    }

    /**
     * Renders custom columns for wp-lead post type
     * @param $null //placeholder
     * @param $column
     * @param $agent_id
     * @return mixed
     */
    public static function render_columns($null, $column, $agent_id) {

        switch ($column) {
            case "profile-picture":
                $user_id = Inbound_Assigned_Agents_Resources::$user_id_by_term_id[$agent_id];
                $gravatar = Inbound_Assigned_Agents_Resources::get_profile_image($user_id);
                echo '<img class="lead-grav-img" width="50" height="50" src="' . $gravatar . '">';
                break;
            case "agent_name":
				$user_id = Inbound_Assigned_Agents_Resources::$user_id_by_term_id[$agent_id];
				$term = get_term($agent_id);
				$output =  '<div><a class="agent-list-table-name" href="' . get_edit_user_link( $user_id ) . '">' . $term->name .' </a>' .
								'<br />
								<div class="hidden" id="inline_' . $agent_id .'">
									<div class="name">' . $term->name . '</div>
									<div class="slug">' . $term->slug . '</div>
								</div>
								<div class="agent-list-row-actions">
									<span class="inline hide-if-no-js"><a href="#" class="editinline aria-button-if-js" aria-label="inline" role="button">Edit Profile Name</a> | </span>
									<span class="view-leads"><a href="' . admin_url('edit.php?page=lead_management&amp;post_type=wp-lead&amp;inbound_assigned_lead%5B%5D=' . $agent_id . '&amp;relation=AND&amp;orderby=date&amp;order=asc&amp;s=&amp;t=&amp;submit=Search+Leads') . '">View Leads |</a></span>
									<span class="delete"><a href="#delete_agent_term" value="&id=' . $agent_id .'&nonce=' . wp_create_nonce('delete-agent-term' . $agent_id) . '&taxonomy=inbound_assigned_lead" class="delete-agent-term" role="button">' . __('Delete', 'inbound-pro') . '</a></span>
								
								</div>
							</div>';
				echo $output;
				break;
            case "title":
					$job_title = Inbound_Assigned_Agents_Management::get_agent_extra_data($agent_id, array('job_title'));
					if(!empty($job_title['job_title']) && is_array($job_title['job_title'])){
						echo '<p class=agent-since>' . array_values($job_title['job_title'])[0] . '</p>';
					}
                break;
            case "added-as-agent":
					$agent_since = Inbound_Assigned_Agents_Management::get_agent_extra_data($agent_id, array('agent_since'));
					if(!empty($agent_since['agent_since'])){
						echo '<p class=agent-since>' . array_values($agent_since['agent_since'])[0] . '</p>';
					}
                break;
            case "limits":
				if(!empty(Inbound_Assigned_Agents_Resources::$lead_group_limits[$agent_id])){
					foreach(Inbound_Assigned_Agents_Resources::$lead_group_limits[$agent_id] as $group=>$limit){
						echo '<p>' . $group . ': ' .$limit . '</p>';
					}
				}else{
					echo '<p>' . __('No Limits Found', 'inbound-pro') . '</p>';
				}
                break;
            case "lead-group-summary":
				if(!empty(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent_id])){
					foreach(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent_id] as $key=>$value ){
						if(!empty($value)){
							echo '<p style="white-space: nowrap;">' . $key . ': ' . count($value) . '</p>';
						}else{
							echo '<p style="white-space: nowrap;">' . $key . ': 0</p>';
						}
					}
				}else{
					echo '<p>' . __('No groups found', 'inbound-pro') . '</p>';
				}
				
				/*if there are ungrouped leads, list the number of them*/
				if(!empty(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$agent_id])){
					echo '<p style="white-space: nowrap; font-weight: 600;">' . __('Ungrouped Leads: ', 'inbound-pro') . count(Inbound_Assigned_Agents_Resources::$ungrouped_leads[$agent_id]) . '</p>';
				}
                break;
        }
    }
	
	}

	new Inbound_Assigned_Agents_Page;


}

?>
