<?php
  if ( ! defined( 'ABSPATH' ) ) {
     exit;
 } ?><div class="crm_fields_table">
    <div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_name"><?php esc_html_e("Account Name",'contact-form-mailchimp-crm'); ?></label>
  </div>
  <div class="crm_field_cell2">
  <input type="text" name="crm[name]" value="<?php echo !empty($name) ? esc_html($name) : 'Account #'.esc_html($id); ?>" id="vx_name" class="crm_text">

  </div>
  <div class="clear"></div>
  </div>
                
    <?php if(isset($info['api_token'])  && $info['api_token']!="") {
  ?>
      <div class="crm_field">
  <div class="crm_field_cell1"><label><?php esc_html_e("Test Connection",'contact-form-mailchimp-crm'); ?></label></div>
  <div class="crm_field_cell2">      <button type="submit" class="button button-secondary" name="vx_test_connection"><i class="fa fa-refresh"></i> <?php esc_html_e("Test Connection",'contact-form-mailchimp-crm'); ?></button>
  </div>
  <div class="clear"></div>
  </div> 
  <?php
    }
  ?>
<div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_pass"><?php esc_html_e('API Key','contact-form-mailchimp-crm'); ?></label></div>
  <div class="crm_field_cell2">
  <div class="vx_tr" >
  <div class="vx_td">
  <input type="password" id="vx_pass" name="crm[api_key]" class="crm_text" placeholder="<?php esc_html_e('API Key','contact-form-mailchimp-crm'); ?>" value="<?php echo $this->post('api_key',$info); ?>" required>
  <span class="howto"><?php echo sprintf(esc_html__('in mailchimp, go to Profile > Extras > API Leys and click "create Key" button , check %sDocs on crmperks.com%s','contact-form-mailchimp-crm'),'<a href="https://www.crmperks.com/post-data-to-mailchimp/" target="_blank">','</a>'); ?></span>
  </div>
  <div class="vx_td2">
  <a href="#" class="button vx_toggle_btn vx_toggle_key" title="<?php esc_html_e('Toggle Key','contact-form-mailchimp-crm'); ?>"><?php esc_html_e('Show Key','contact-form-mailchimp-crm') ?></a>
  
  </div>
  </div>
  </div>
  <div class="clear"></div>
  </div>    
  <div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_error_email"><?php esc_html_e("Notify by Email on Errors",'contact-form-mailchimp-crm'); ?></label></div>
  <div class="crm_field_cell2"><textarea name="crm[error_email]" id="vx_error_email" placeholder="<?php esc_html_e("Enter comma separated email addresses",'contact-form-mailchimp-crm'); ?>" class="crm_text" style="height: 70px"><?php echo isset($info['error_email']) ? esc_html($info['error_email']) : ""; ?></textarea>
  <span class="howto"><?php esc_html_e("Enter comma separated email addresses. An email will be sent to these email addresses if an order is not properly added to Mailchimp. Leave blank to disable.",'contact-form-mailchimp-crm'); ?></span>
  </div>
  <div class="clear"></div>
  </div>  
   
 
  <button type="submit" value="save" class="button-primary" title="<?php esc_html_e('Save Changes','contact-form-mailchimp-crm'); ?>" name="save"><?php esc_html_e('Save Changes','contact-form-mailchimp-crm'); ?></button>  
  </div>  