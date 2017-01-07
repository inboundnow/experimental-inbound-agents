<?php

if(!class_exists('Inbound_Assigned_Agents_Inbound_Forms_Submissions')){

	class Inbound_Assigned_Agents_Inbound_Forms_Submissions{
	
	
		function __construct(){
			self::load_hooks();
		}
	
		public static function load_hooks(){
			
			/*after the lead has been created, update the concerned agents*/
			add_filter('inbound_store_lead_post', array(__CLASS__, 'update_agents_with_new_submission'));

			/*filter the data used for making the body of the email*/
			add_filter('inbound-email-post-params', array(__CLASS__, 'filter_email_post_params'));
		}
	
		public static function update_agents_with_new_submission($lead){
			
			$settings = Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_rotate_agent_lead_assignment');

			$form_values = get_post_meta($lead['form_id'], 'inbound_form_values');
			parse_str($form_values[0], $form_values);

			if($form_values['inbound_shortcode_inbound_assign_agent_enable'] == 'on'){

				/*if rotating lead assignment has been selected in the extension settings*/
				if($settings == 1){

					/*get the rotating assignment counter from the form meta*/
					$rotation_counter = get_post_meta($lead['form_id'], 'inbound_assign_agents_rotation_counter', true);
					
					/*get the number of agents assigned to the form*/
					$agent_count = count($form_values['inbound_shortcode_inbound_assign_agent']);
					
					/*get the agent to assign to with the remainder of dividing the counter by the agent count*/
					$pointer = $rotation_counter % $agent_count;
					
					/*get the agent id*/
					$agent_id = $form_values['inbound_shortcode_inbound_assign_agent'][$pointer];

					/*assign to lead groups if set to*/
					if($form_values['inbound_shortcode_inbound_assign_to_agent_lead_group_enable'] == 'on'){
						
						/*shorten the group variable*/
						$groups = $form_values['inbound_shortcode_inbound_assign_to_agent_lead_group'];
					
	
						for($i = 0; $i < $agent_count; $i++){
							/*check_if_under_limits returns false if the group is not under the limit*/
							$checked_groups = Inbound_Assigned_Agents_Management::check_if_under_limits($agent_id, $groups);
							if($checked_groups !== false){
								break;
							}else{
								//move the pointer one to try again
								$pointer++;
								$agent_id = $form_values['inbound_shortcode_inbound_assign_agent'][$pointer];
							}
						}
							/*if there is an agent who's leads are under the limit*/
							if($checked_groups !== false){			
								$data = array(
											'execution' => 'add-leads',
											'agent_ids' => array( $agent_id ),
											'lead_groups' => $checked_groups,
											'lead_ids' => array($lead['id']),
								);
								
								/*assign the lead to the agent and put the lead in the group/s*/	
								Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
								/*notify the assigned agent*/
								self::send_agent_notification($agent_id, $lead);
							}
						
						}else{
							/*if the user opted not to put leads into groups, just add the lead to the agent*/
								$data = array(
											'execution' => 'add-leads',
											'agent_ids' => array( $agent_id ),
											'lead_ids' => array($lead['id']),
								);
											
								Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
								/*notify the assigned agent*/
								self::send_agent_notification($agent_id, $lead);
						}

					/*and update the counter for next time*/
					$update_meta = update_post_meta($lead['form_id'], 'inbound_assign_agents_rotation_counter', (int)$rotation_counter + 1);
					
				}else{
					/**if leads are to be assigned to all form agents:**/
					$agents_to_send_notifications = array();

					/*loop through the assigned agents*/
					foreach($form_values['inbound_shortcode_inbound_assign_agent'] as $agent_id){
						
						/*assign to lead groups if set to*/
						if($form_values['inbound_shortcode_inbound_assign_to_agent_lead_group_enable'] == 'on'){
							/*shorten the group variable*/
							$groups = $form_values['inbound_shortcode_inbound_assign_to_agent_lead_group'];
							
							$checked_groups = Inbound_Assigned_Agents_Management::check_if_under_limits($agent_id, $groups);
							
							if($checked_groups !== false){
								$data = array(
											'execution' => 'add-leads',
											'agent_ids' => array( $agent_id ),
											'lead_groups' => $checked_groups,
											'lead_ids' => array($lead['id']),
								);
							
								Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
								
								/*add agents to notify*/
								$agents_to_send_notifications[] = $agent_id;
							}
						}else{
						/**if not set to put in groups, just assign the leads to agents**/
								$data = array(
											'execution' => 'add-leads',
											'agent_ids' => array( $agent_id ),
											'lead_ids' => array($lead['id']),
								);
											
						/*add lead to the agent term for default taxonomy functionality*/
						Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
						
						/*add agents to notify*/
						$agents_to_send_notifications[] = $agent_id;
						
						}
					}
					
					/*send agent notfications*/
					if(!empty($agents_to_send_notifications)){
						self::send_agent_notification($agents_to_send_notifications, $lead);
					}
				}
			}
		}
		
		/**
         *  Sends Notification of New Lead Conversion to Agents assigned to the converting form
         */
        public static function send_agent_notification($agent_id, $lead) {
			$to_address = array();

			/* exit if there's no agents */
		    if (empty($agent_id)) {
			    return;
		    }

			/*get the form meta*/
			$form_meta_data = get_post_meta($lead['form_id']);
			
			/*set the post id*/
			$form_meta_data['post_id'] = $lead['form_id'];
	
			/* Rebuild Form Meta Data to Load Single Values	*/
			foreach ($form_meta_data as $key => $value) {
				if (isset($value[0])) {
					$form_meta_data[$key] = $value[0];
				}
			}
			
			/*exit if agents arent supposed to be notified*/
			if($form_meta_data['inbound_agents_notify_agents'] != '1'){
				return;
			}

			/**setup the lead data for the templating engine**/
			$lead_data;
			$lead_data['wpleads_email_address'] = $lead['email'];
			/*process the mapped fields*/
			parse_str($lead['mapped_params'], $mapped);
			foreach($mapped as $key=>$value){
				if(!isset($lead_data[$key])){
					$lead_data[$key] = $value;
				}
			}			
			/*process the raw params*/
			parse_str($lead['raw_params'], $raw_params);
			foreach($raw_params as $key=>$value){
				if(!isset($lead_data[$key])){
					$lead_data[$key] = $value;
				}
			}
			/*unset unneeded fields*/
			unset($lead['mapped_params'], $lead['raw_params'], $lead['url_params']);
			/*add remaining fields to lead_data*/
			foreach($lead as $key=>$value){
				if(!isset($lead_data[$key])){
					$lead_data[$key] = $value;
				}
			}	

			/*get the emails to notify*/
			if(is_array($agent_id)){
				foreach($agent_id as $id){
					$to_address[] = get_userdata(Inbound_Assigned_Agents_Resources::$user_id_by_term_id[$id])->data->user_email;
				}
			}else{
				$to_address[] = get_userdata(Inbound_Assigned_Agents_Resources::$user_id_by_term_id[$agent_id])->data->user_email;
			}

            if ($template = self::get_agent_notify_email_template()) {
                add_filter('wp_mail_content_type', 'inbound_agents_set_html_content_type');
                function inbound_agents_set_html_content_type() {
                    return 'text/html';
                }

                /* Look for Custom Subject Line ,	Fall Back on Default */
                $subject = (isset($form_meta_data['inbound_notify_email_subject'])) ? $form_meta_data['inbound_notify_email_subject'] : $template['subject'];
               
                /* Discover From Email Address */
                foreach ($lead_data as $key => $value) {
                    if (preg_match('/email|e-mail/i', $key)) {
                        $reply_to_email = $lead_data[$key];
                    }
                }
 
                $domain = get_option('siteurl');
                $domain = str_replace('http://', '', $domain);
                $domain = str_replace('https://', '', $domain);
                $domain = str_replace('www', '', $domain);
                $email_default = 'wordpress@' . $domain;
                /* Leave here for now
                switch( get_option('inbound_forms_enable_akismet', 'noreply' ) ) {
                    case 'noreply':
                        BREAK;
                    case 'lead':
                        BREAK;
                }
                */
               
                $from_email = get_option('admin_email', $email_default);
                $from_email = apply_filters('inbound_admin_notification_from_email', $from_email);

                $reply_to_email = (isset($reply_to_email)) ? $reply_to_email : $from_email;

                $from_name = get_option('blogname', '');
                $from_name = apply_filters('inbound_admin_notification_from_name', $from_name);

                $Inbound_Templating_Engine = Inbound_Templating_Engine();
                $subject = $Inbound_Templating_Engine->replace_tokens($subject, array($lead_data, $form_meta_data));
                $body = $Inbound_Templating_Engine->replace_tokens($template['body'], array($lead_data, $form_meta_data));
 

                /* Fix broken HTML tags from wp_mail garbage */
                /* $body = '<tbody> <t body> <tb ody > <tbo dy> <tbod y> < t d class = "test" > < / td > '; */
                $body = preg_replace("/ \>/", ">", $body);
                $body = preg_replace("/\/ /", "/", $body);
                $body = preg_replace("/\< /", "<", $body);
                $body = preg_replace("/\= /", "=", $body);
                $body = preg_replace("/ \=/", "=", $body);
                $body = preg_replace("/t d/", "td", $body);
                $body = preg_replace("/t r/", "tr", $body);
                $body = preg_replace("/t h/", "th", $body);
                $body = preg_replace("/t body/", "tbody", $body);
                $body = preg_replace("/tb ody/", "tbody", $body);
                $body = preg_replace("/tbo dy/", "tbody", $body);
                $body = preg_replace("/tbod y/", "tbody", $body);
                $headers = 'From: ' . $from_name . ' <' . $from_email . '>' . "\r\n";
                $headers .= "Reply-To: " . $reply_to_email . "\r\n";
                $headers = apply_filters('inbound_email_response/headers', $headers);
                foreach ($to_address as $key => $recipient) {
                    $result = wp_mail($recipient, $subject, $body, $headers, apply_filters('inbound_lead_notification_attachments', false));
                }
            }
        }
		
		
        /**
         *  Get Email Template for New Lead Notification
         */
       public static function get_agent_notify_email_template() {
            if (get_option('inbound_admin_notification_inboundnow_link',true)) {
                $credit = '<tr>
                        <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
                          <td width="50" height="40" valign="middle" align="left" style="color:#272727">
                            <a href="http://www.inboundnow.com" target="_blank"><img src="{{leads-urlpath}}assets/images/inbound-email.png" height="40" width="40" alt=" " style="outline:none;text-decoration:none;max-width:100%;display:block;width:40px;min-height:40px;border-radius:20px"></a>
                          </td>
                        <td style="color:#272727">
                            <a style="color:#272727;text-decoration:none;" href="http://www.inboundnow.com" target="_blank">
                            ' . __('<b>Leads</b> from Inbound Now', 'inbound-pro') . '
                            </a>
                        </td>
                        <td valign="middle" align="left" style="color:#545454;text-align:right">{{date-time}}</td>
                        <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
                      </tr>';
            } else {
                $credit = '';
            }
            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html>
                    <head>
                      <meta http-equiv="Content-Type" content="text/html;" charset="UTF-8" />
                    <style type="text/css">
                      html {
                        background: #EEEDED;
                      }
                    </style>
                    </head>
                    <body style="margin: 0px; background-color: #FFFFFF; font-family: Helvetica, Arial, sans-serif; font-size:12px;" text="#444444" bgcolor="#FFFFFF" link="#21759B" alink="#21759B" vlink="#21759B" marginheight="0" topmargin="0" marginwidth="0" leftmargin="0">
                    <table cellpadding="0" width="600" bgcolor="#FFFFFF" cellspacing="0" border="0" align="center" style="width:100%!important;line-height:100%!important;border-collapse:collapse;margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
                      <tbody><tr>
                        <td valign="top" height="20">&nbsp;</td>
                      </tr>
                      <tr>
                        <td valign="top">
                          <table cellpadding="0" bgcolor="#ffffff" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px;font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;border-radius:3px;margin-top:0;margin-right:auto;margin-bottom:0;margin-left:auto">
                      <tbody><tr>
                        <td valign="top">
                            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;width:100%;border-radius:3px 3px 0 0;font-size:1px;line-height:3px;height:3px;border-top-color:#0298e3;border-right-color:#0298e3;border-bottom-color:#0298e3;border-left-color:#0298e3;border-top-style:solid;border-right-style:solid;border-bottom-style:solid;border-left-style:solid;border-top-width:1px;border-right-width:1px;border-bottom-width:1px;border-left-width:1px">
                              <tbody><tr>
                                <td valign="top" style="font-family:Arial,sans-serif;background-color:#5ab8e7;border-top-width:1px;border-top-color:#8ccae9;border-top-style:solid" bgcolor="#5ab8e7">&nbsp;</td>
                              </tr>
                            </tbody></table>
                          <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;width:600px;border-radius:0 0 3px 3px;border-top-color:#8c8c8c;border-right-color:#8c8c8c;border-bottom-color:#8c8c8c;border-left-color:#8c8c8c;border-top-style:solid;border-right-style:solid;border-bottom-style:solid;border-left-style:solid;border-top-width:0;border-right-width:1px;border-bottom-width:1px;border-left-width:1px">
                            <tbody><tr>
                              <td valign="top" style="font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;border-radius:0 0 3px 3px;padding-top:3px;padding-right:30px;padding-bottom:15px;padding-left:30px">
                      <h1 style="margin-top:20px;margin-right:0;margin-bottom:20px;margin-left:0; font-size:28px; line-height: 28px; color:#000;"> ' . __('A New Lead on {{form-name}} has been assigned to you.', 'inbound-pro') . '</h1>
                      <p style="margin-top:20px;margin-right:0;margin-bottom:20px;margin-left:0">' . __('There is a new lead that just converted on <strong>{{date-time}}</strong> from page: <a href="{{source}}">{{source}}</a> {{redirect-message}}', 'inbound-pro') . '</p>
                    <!-- NEW TABLE -->
                    <table class="heavyTable" style="width: 100%;
                        max-width: 600px;
                        border-collapse: collapse;
                        border: 1px solid #cccccc;
                        background: white;
                       margin-bottom: 20px;">
                       <tbody>
                         <tr style="background: #3A9FD1; height: 54px; font-weight: lighter; color: #fff;border: 1px solid #3A9FD1;text-align: left; padding-left: 10px;">
                                 <td  align="left" width="600" style="-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; color: #fff; font-weight: bold; text-decoration: none; font-family: Helvetica, Arial, sans-serif; display: block;">
                                  <h1 style="font-size: 30px; display: inline-block;margin-top: 15px;margin-left: 10px; margin-bottom: 0px; letter-spacing: 0px; word-spacing: 0px; font-weight: 300;">' . __('Lead Information', 'inbound-pro') . '</h1>
                                  <div style="float:right; margin-top: 5px; margin-right: 15px;"><!--[if mso]>
                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}" style="height:40px;v-text-anchor:middle;width:130px;font-size:18px;" arcsize="10%" stroke="f" fillcolor="#ffffff">
                                      <w:anchorlock/>
                                      <center>
                                    <![endif]-->
                                        <a href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}"
                                  style="background-color:#ffffff;border-radius:4px;color:#3A9FD1;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:130px;-webkit-text-size-adjust:none;">' . __('View Lead', 'inbound-pro') . '</a>
                                    <!--[if mso]>
                                      </center>
                                    </v:roundrect>
                                  <![endif]-->
                                  </div>
                                 </td>
                         </tr>
                         <!-- LOOP THROUGH POST PARAMS -->
                         [inbound-email-post-params]
                         <!-- END LOOP -->
                         <!-- IF CHAR COUNT OVER 50 make label display block -->
                       </tbody>
                     </table>
                     <!-- END NEW TABLE -->
                    <!-- Start 3 col -->
                    <table style="margin-bottom: 20px; border: 1px solid #cccccc; border-collapse: collapse;" width="100%" border="1" BORDERWIDTH="1" BORDERCOLOR="CCCCCC" cellspacing="0" cellpadding="5" align="left" valign="top" borderspacing="0" >
                    <tbody valign="top">
                     <tr valign="top" border="0">
                      <td width="160" height="50" align="center" valign="top" border="0">
                         <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}&tab=tabs-wpleads_lead_tab_conversions">' . __('View Lead Activity', 'inbound-pro') . '</a></h3>
                      </td>
                      <td width="160" height="50" align="center" valign="top" border="0">
                         <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}&scroll-to=wplead_metabox_conversion">' . __('Pages Viewed', 'inbound-pro') . '</a></h3>
                      </td>
                     <td width="160" height="50" align="center" valign="top" border="0">
                        <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}&tab=tabs-wpleads_lead_tab_raw_form_data">' . __('View Form Data', 'inbound-pro') . '</a></h3>
                     </td>
                     </tr>
                    </tbody></table>
                    <!-- end 3 col -->
                     <!-- Start half/half -->
                     <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:10px;">
                         <tbody><tr>
                          <td align="center" width="250" height="30" cellpadding="5">
                             <div><!--[if mso]>
                               <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}" style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#7490af" fillcolor="#3A9FD1">
                                 <w:anchorlock/>
                                 <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">' . __('View Lead', 'inbound-pro') . '</center>
                               </v:roundrect>
                             <![endif]--><a href="{{admin-url}}edit.php?post_type=wp-lead&s={{lead-email-address}}"
                             style="background-color:#3A9FD1;border:1px solid #7490af;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;" title="' . __('View the full Lead details in WordPress', 'inbound-pro') . '">' . __('View Full Lead Details', 'inbound-pro') . '</a>
                           </div>
                          </td>
                           <td align="center" width="250" height="30" cellpadding="5">
                             <div><!--[if mso]>
                               <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="mailto:{{lead-email-address}}?subject=RE:{{form-name}}&body=' . __('Thanks for filling out our form.', 'inbound-pro') . '" style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#558939" fillcolor="#59b329">
                                 <w:anchorlock/>
                                 <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">' . __('Reply to Lead Now', 'inbound-pro') . '</center>
                               </v:roundrect>
                             <![endif]--><a href="mailto:{{lead-email-address}}?subject=RE:{{form-name}}&body=' . __('Thanks for filling out our form on {{current-page-url}}', 'inbound-pro') . '"
                             style="background-color:#59b329;border:1px solid #558939;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;" title="' . __('Email This Lead now', 'inbound-pro') . '">' . __('Reply to Lead Now', 'inbound-pro') . '</a></div>
                           </td>
                         </tr>
                       </tbody>
                     </table>
                    <!-- End half/half -->
                              </td>
                            </tr>
                          </tbody></table>
                        </td>
                      </tr>
                    </tbody></table>
                    <table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px;font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;margin-top:0;margin-right:auto;margin-bottom:0;margin-left:auto">
                      <tbody><tr>
                        <td valign="top" width="30" style="color:#272727">&nbsp;</td>
                        <td valign="top" height="18" style="height:18px;color:#272727"></td>
                          <td style="color:#272727">&nbsp;</td>
                        <td style="color:#545454;text-align:right" align="right">&nbsp;</td>
                        <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
                      </tr>
                      '.$credit.'
                      <tr>
                        <td valign="top" height="6" style="color:#272727;line-height:1px">&nbsp;</td>
                        <td style="color:#272727;line-height:1px">&nbsp;</td>
                          <td style="color:#272727;line-height:1px">&nbsp;</td>
                        <td style="color:#545454;text-align:right;line-height:1px" align="right">&nbsp;</td>
                        <td valign="middle" width="30" style="color:#272727;line-height:1px">&nbsp;</td>
                      </tr>
                    </tbody></table>
                          <table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px">
                            <tbody><tr>
                              <td valign="top" style="color:#b1b1b1;font-size:11px;line-height:16px;font-family:Arial,sans-serif;text-align:center" align="center">
                                <p style="margin-top:1em;margin-right:0;margin-bottom:1em;margin-left:0"></p>
                              </td>
                            </tr>
                          </tbody></table>
                        </td>
                      </tr>
                      <tr>
                        <td valign="top" height="20">&nbsp;</td>
                      </tr>
                    </tbody></table>
                    </body>';
            $email_template['subject'] = apply_filters('inbound_new_lead_notification/subject', '');
            $email_template['body'] = apply_filters('inbound_new_lead_notification/body', $html);
            return $email_template;
        }

		/**
		 * Filter the data used for generating the email rows
		 */
		public static function filter_email_post_params($data){
			/*if the data isn't already filtered*/
			if(isset($data['raw_params'])){
				/*and if agents are to be notified*/
				if(isset($data['inbound_form_id']) && get_post_meta($data['inbound_form_id'], 'inbound_agents_notify_agents', true) == '1'){
					$new_data = array();
					
					$new_data['wpleads_email_address'] = urldecode($data['email']);
					
					parse_str($data['mapped_params'], $mapped);
					
					foreach($mapped as $key=>$value){
						if(!isset($new_data[$key])){
							$new_data[$key] = $value;
						}
					}
					
					parse_str($data['raw_params'], $raw_params);
					
					foreach($raw_params as $key=>$value){
						if(!isset($new_data[$key])){
							$new_data[$key] = $value;
						}
					}
				
					$assigned_from_groups = get_post_meta($data['inbound_form_id'], 'inbound_agents_notify_agent_groups', true);
					if(!empty($assigned_from_groups) && $assigned_from_groups != ''){
						$new_data['in_agent_groups'] = $assigned_from_groups;
					}
					
					/*update the data*/
					$data = $new_data;
				}
			}
			return $data;
		}

	}	

	new Inbound_Assigned_Agents_Inbound_Forms_Submissions;

}


?>
