<?php
/**
  * Plugin Name: WP e-commerce Bypass Merchant
  * Description: A simple plugin for wp-e-commerce to allow for the merchant step to be bypassed when purchase total price is 0. Originally thought to allow a coupon to apply 100% discount. A normal checkout operation would normally fail due to a non allowed zero amount. This plugin is an attempt to circumvent this obstacle. 
  * Version: 0.0.1
  * Author: Fabrizio Regini
  * Author email: freegenie@gmail.com
  **/

class WP_BypassMerchant {

  const NO_GATEWAY = '_no_gateway'; 

  function WP_BypassMerchant() {
    add_action('plugins_loaded' , array( $this, 'init' ), 8 );
  }

  function init() {
    add_action( 'wpsc_submit_checkout_gateway', array( $this, 'override_gateway_selected_payment'), 10, 2);
  }

  function override_gateway_selected_payment($submitted_gateway, $purchase_log) {
    if ((int) $purchase_log->get('totalprice') == 0) {
      $this->override_purchase_log($purchase_log); 
      $this->send_emails($purchase_log);
      $this->update_customer_meta(); 
      $this->redirect_to_transaction_page($purchase_log->get('sessionid'));
    }
  }

  private function update_customer_meta() {
    wpsc_update_customer_meta('selected_gateway', self::NO_GATEWAY); 
  }

  private function send_emails($purchase_log) {
    wpsc_send_customer_email($purchase_log); 
    wpsc_send_admin_email($purchase_log);
  }

  private function override_purchase_log($purchase_log) {
    $purchase_log->set(array('gateway' => self::NO_GATEWAY, 'processed' => WPSC_Purchase_Log::CLOSED_ORDER));
    $purchase_log->save(); 
  }

  private function redirect_to_transaction_page($sessionid) {
    $transaction_url_with_sessionid = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
    wp_redirect( $transaction_url_with_sessionid );
    exit(); 
  }

  function install() {
  }

  function deactivate() {
  }
}

$coupon_zero = new WP_BypassMerchant(); 

register_activation_hook(__FILE__, array($coupon_zero, 'install')); 
register_deactivation_hook(__FILE__, array($coupon_zero, 'deactivate'));



?>
