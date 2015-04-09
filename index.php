<?php
/*
Plugin Name: Moulton Payment Gateway For WooCommerce
Description: Extends WooCommerce to Process Payments with Moulton gateway
Version: 1.0
Author: Birds Host
Author URI: info@birdshost.com
*/
add_action('plugins_loaded', 'woocommerce_moulton_payment_init', 0);
function woocommerce_moulton_payment_init() {
  if ( !class_exists( 'WC_Payment_Gateway' ) ) 
    return;
   /**
   * Localisation
   */
   load_plugin_textdomain('wc-tech-automoulton', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * Moulton Payment Gateway class
   */
   class WC_Tech_Moulton extends WC_Payment_Gateway 
   {
      protected $msg = array();
      
      public function __construct(){
         $this->id               = 'moulton';
         $this->method_title     = __('Moulton', 'wc-tech-automoulton');
         $this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/mouton-logo-new-3.png';
         $this->has_fields       = true;
         $this->init_form_fields();
         $this->init_settings();
         $this->title            = $this->settings['title'];
         $this->description      = $this->settings['description'];
         $this->username         = $this->settings['username'];
         $this->mode             = $this->settings['working_mode'];
         $this->password 		     = $this->settings['password'];
         $this->groupcode		     = $this->settings['groupcode'];
         $this->CLNO             = $this->settings['CLNO'];
         $this->project          = $this->settings['project'];
         $this->XMLFormatCode    = $this->settings['XMLFormatCode'];
         $this->UNIQUEID         = $this->settings['UNIQUEID'];
         $this->success_message  = $this->settings['success_message'];
         $this->failed_message   = $this->settings['failed_message'];
         $this->liveurl          = 'https://www.moultonordervision.com/Ws/ORDAPI.asmx/OrderNewAPI';
         $this->testurl          = 'https://www.qcmoultonordervision.com/Ws/ORDAPI.asmx/OrderNewAPI';
         $this->msg['message']   = "";
         $this->msg['class']     = "";
        
         
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
          {
             add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          }
          else
          {
             add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
          }
         
         add_action('woocommerce_receipt_moulton', array(&$this, 'receipt_page'));
         
         add_action('woocommerce_thankyou_moulton',array(&$this, 'thankyou_page'));
      }
      
      function init_form_fields()
      {
         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'wc-tech-automoulton'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable Moulton Payment Module.', 'wc-tech-automoulton'),
                  'default'      => 'no'),

            'title'        => array(
                  'title'        => __('Title:', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  => __('This controls the title which the user sees during checkout.', 'wc-tech-automoulton'),
                  'default'      => __('Moulton', 'wc-tech-automoulton')),

            'description'  => array(
                  'title'        => __('Description:', 'wc-tech-automoulton'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'wc-tech-automoulton'),
                  'default'      => __('Pay securely by Credit or Debit Card through Moulton Secure Servers.', 'wc-tech-automoulton')),
            'username'     => array(
                  'title'        => __('Username', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  => __('This is API username ID')),
            'groupcode'     => array(
                  'title'        => __('GroupCode', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  => __('This is API GroupCode ID')),
            'password' => array(
                  'title'        => __('Password', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  =>  __('API Password Key', 'wc-tech-automoulton')),
            'CLNO' => array(
                  'title'        => __('CLNO', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  =>  __('API CLNO Key', 'wc-tech-automoulton')),
            'project' => array(
                  'title'        => __('project', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  =>  __('API project Key', 'wc-tech-automoulton')),
            'XMLFormatCode' => array(
                  'title'        => __('XMLFormatCode', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  =>  __('API XMLFormatCode Key', 'wc-tech-automoulton')),

            'UNIQUEID' => array(
                  'title'        => __('UNIQUEID', 'wc-tech-automoulton'),
                  'type'         => 'text',
                  'description'  =>  __('API UNIQUEID Key', 'wc-tech-automoulton')),

            'success_message' => array(
                  'title'        => __('Transaction Success Message', 'wc-tech-automoulton'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'wc-tech-automoulton'),
                  'default'      => __('Your payment has been procssed successfully.', 'wc-tech-automoulton')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'wc-tech-automoulton'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'wc-tech-automoulton'),
                  'default'      => __('Your transaction has been declined.', 'wc-tech-automoulton')),
            'working_mode'    => array(
                  'title'        => __('API Mode'),
                  'type'         => 'select',
            'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
                  'description'  => "Live/Test Mode" )
         );


      }
      
      /**
       * Admin Panel Options
       * 
      **/
      public function admin_options()
      {
         
         echo '<h3>'.__('Moulton Payment Gateway', 'wc-tech-automoulton').'</h3>';
         
         echo '<p>'.__('Moulton is most popular payment gateway for online payment processing').'</p>';
         
         echo '<table class="form-table">';
         
          $this->generate_settings_html();
         
         echo '</table>';
      }
      
      /**
      *  Fields for Molton
      **/
      function payment_fields()
      {
         if ( $this->description ) 
            
            echo wpautop(wptexturize($this->description));
            
            echo '<label style="margin-right:46px; line-height:40px;">Credit Card :</label> <input type="text" name="moulton_credircard" /><br/>';
            
            echo '<label style="margin-right:30px; line-height:40px;">Expiry (MMYY) :</label> <input type="text"  style="width:50px;" name="moulton_ccexpdate" maxlength="4" /><br/>';
            
            echo '<label style="margin-right:89px; line-height:40px;">CVV :</label> <input type="text" name="moulton_ccvnumber"  maxlength=4 style="width:40px;" /><br/>';
      }
      
      /*
      * Basic Card validation
      */
      public function validate_fields()
      { 
           global $woocommerce;
           
           if ( !$this->isCreditCardNumber( $_POST['moulton_credircard'] ) ) 
               
               $woocommerce->add_error(__('(Credit Card Number) is not valid.', 'wc-tech-automoulton')); 
           
           if ( !$this->isCorrectExpireDate( $_POST['moulton_ccexpdate'] ) )    
               
               $woocommerce->add_error(__('(Card Expiry Date) is not valid.', 'wc-tech-automoulton')); 
           
           if (!$this->isCCVNumber($_POST['moulton_ccvnumber'])) 
               
               $woocommerce->add_error(__('(Card Verification Number) is not valid.', 'wc-tech-automoulton')); 
      }
      
      /*
      * Check card 
      */
      private function isCreditCardNumber($toCheck) 
      {
         if (!is_numeric($toCheck))
            return false;
        
        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;
        if ($strlen < 13)
            return false; 
            
        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            } 
            else 
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }
        
        if ($sum > 0 AND $sum % 10 == 0)
            return true; 
        return false;
      }
        
      private function isCCVNumber($toCheck) 
      {
         $length = strlen($toCheck);
         return is_numeric($toCheck) AND $length > 2 AND $length < 5;
      }
    
      /*
      * Check expiry date
      */
      private function isCorrectExpireDate($date) 
      {
          
         if (is_numeric($date) && (strlen($date) == 4)){
            return true;
         }
         return false;
      }
      
      public function thankyou_page($order_id) 
      {
      
       
      }
      
      /**
      * Receipt Page
      **/
      function receipt_page($order)
      {
         echo '<p>'.__('Thank you for your order.', 'wc-tech-automoulton').'</p>';
        
      }
      
      /**
       * Process the payment and return the result
      **/
      function process_payment($order_id)
      {
        global $woocommerce;
        $order = new WC_Order($order_id);
         if($this->mode == 'true'){
           $process_url = $this->testurl;
         }
         else{
           $process_url = $this->liveurl;
         }
         
        define('POSTVARS','Username='.$this->username.'&Password='.$this->password.'&groupcode='.$this->groupcode.'&CLNO='.$this->CLNO.'&project='.$this->project.'&XMLFormatCode='.$this->XMLFormatCode.'&UNIQUEID='.$this->UNIQUEID.'&ORDXML=');
         
        $loadXmlFile = wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/moulton-gateway/xml.xml' ) );
  
        $document                               = new SimpleXMLElement($loadXmlFile , 0, false);
        $document->OrderHeader->GROUP_CODE      = $this->groupcode;
        $document->OrderHeader->DATE_ORD        = date('Ymd');
        $document->OrderHeader->CL_NO           = $this->CLNO;
        $document->OrderHeader->CSOURCE         = substr( md5(rand()), 0, 7);
        $document->OrderHeader->CREDCD          = $_POST['moulton_credircard'];
        $document->OrderHeader->EXPDT           = $_POST['moulton_ccexpdate'];
        $document->OrderHeader->PROJECT         = $this->project;
        $document->OrderHeader->AMTPAY          = $order->order_total;
        $document->OrderHeader->EMAIL           = $order->billing_email;
        $document->OrderHeader->F_NAME          = $order->billing_first_name;
        $document->OrderHeader->L_NAME          = $order->billing_last_name;
        $document->OrderHeader->ADDR_1          = $order->billing_address_1;
        $document->OrderHeader->CITY            = $order->billing_city;
        $document->OrderHeader->ST              = $order->billing_state;
        $document->OrderHeader->ZIP             = $order->billing_postcode;
        $document->OrderHeader->BILL_TO_F_NAME  = $order->billing_first_name;
        $document->OrderHeader->BILL_TO_L_NAME  = $order->billing_last_name;
        $document->OrderHeader->BILL_TO_ADDR_1  = $order->billing_address_1;
        $document->OrderHeader->BILL_TO_CITY    = $order->billing_city;
        $document->OrderHeader->BILL_TO_ST      = $order->billing_state;
        $document->OrderHeader->BILL_TO_ZIP     = $order->billing_postcode;


        $xml                                    = $document->asXML();
        $curl = curl_init($process_url);
        curl_setopt($curl, CURLOPT_SSLVERSION,3);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_HEADER ,0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, POSTVARS.$xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER ,1);
        $str = curl_exec($curl);
        curl_close($curl);

        $response   = simplexml_load_string($str);
        $xml_array  = $this->object2array($response); 
      
         if ( $xml_array['ErrorMsg'] == "Success" ){ 
            if($xml_array['ErrorMsg'] == "Success" ){
                if ($order->status != 'completed') {
                    $order->payment_complete("Success");
                     $woocommerce->cart->empty_cart();
                     $order->add_order_note($this->success_message);
                     unset($_SESSION['order_awaiting_payment']);
                 }
                  return array('result'   => 'success',
                     'redirect'  => get_site_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
            }
            else{
            
                $order->add_order_note($this->failed_message .$xml_array['ErrorMsg'] );
                $woocommerce->add_error(__('(Transaction Error) '. $xml_array['ErrorMsg'], 'wc-tech-automoulton'));
            }
        }
        else {
            
            $order->add_order_note($this->failed_message);
            $order->update_status('failed');
            
            $woocommerce->add_error(__('(Transaction Error) Error processing payment.', 'wc-tech-automoulton')); 
        }    
      }
      /*
      * Converting XML Into Object
      */
      function object2array($object) { return @json_decode(@json_encode($object),1); } 
      
   }
   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_add_tech_moulton_gateway($methods) 
   {
      $methods[] = 'WC_Tech_Moulton';
      return $methods;
   }
   add_filter('woocommerce_payment_gateways', 'woocommerce_add_tech_moulton_gateway' );
}