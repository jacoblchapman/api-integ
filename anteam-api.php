

<?php

require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-woo-api.php' );
class AnteamApi {

    public function __construct() {
        $this->auth_token                 = '';
    }

    private function setup_curl_post($url, $data) {

        $handle = curl_init($url);
    
        $encodedData = json_encode($data);
        curl_setopt($handle, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: TOKEN '. $this->auth_token,
        ]);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        return $handle;
    }

    public function checkOrders($orders){        
        $checkOrders = $this->checkAddresses($orders);
        $retOrders = array();
        foreach ($orders as $order) {            
            // if(isUnknown($order)) {
                foreach ($checkOrders as $orderChecked) {
                    if($orderChecked->id == $order->get_id()) {
                        if($orderChecked->accepted) {
                            array_push($retOrders, $order);
                            break;
                        }
                    }
                }
            // }
        }
        return $retOrders;
    }
  
    public function checkAddresses($orders) {
        $woo_api = new AnteamWooApi();
    
        $data = array();
        foreach ($orders as $order) {
            $d = [
                'id' => $order->get_id(),
                'postcode' => $order->get_shipping_postcode(),
                'weight' => $woo_api->getOrderWeight($order),
                'unit' => $woo_api->getWeightUnit(),
            ];
            array_push($data, $d);
        }
    
        
    
        $url = "https://api.anteam.co.uk/profiles/check_address/";
    
        $handle = $this->setup_curl_post($url, $data);
    
        $result = curl_exec($handle);
        $httpStatus = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if (curl_errno($handle)) {
            $error_msg = curl_error($handle);
        }
        curl_close($handle);
        if($httpStatus==200) {
            // Decode the response as a JSON object
            $json = json_decode($result);
            // accepted
            return $json;
        } else {
            
            if (isset($error_msg)) {
                echo '<div class="error"><p>Anteam connection error:</p><p>';
                echo $error_msg;
                echo '</p></div>';
            }
            return array();
        }
    }

    private function getSize($order, $woo_api){
        $total_weight = $woo_api->getOrderWeight($order);
        $multiplier = $this->getWeightMultiplier();
        $size = "extra_large";
        // get package size
         if($total_weight < (0.5*$multiplier)) {
            $size = "extra_small";
        } else if ($total_weight < (2*$multiplier)) {
            $size = "small";
        } else if ($total_weight < (5*$multiplier)) {
           $size = "medium";
        } else if ($total_weight < (10*$multiplier)) {
            $size = "large";
        } else if ($total_weight < (15*$multiplier)) {
            $size = "extra_large";
        } else {
            error_log("Error , line 92 : anteam-api.php");
        }
        return $size;
    }
     
    // Approval and Denied functions
    public function orderApproved($order, $Anteam_shipping_instance, $woo_api)
    {
             
            // Prepare additional data for POST request
            date_default_timezone_set('Europe/London');
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');
            $start_time = $currentDate . 'T' . $currentTime;
            // JSON data
            $data = [
                    'pickup' => [
                    'address' => $Anteam_shipping_instance->get_pickup_address(),
                    'lat' => 0,
                    'lon' => 0,
                    'instructions' => '',
                    'contactName' => $Anteam_shipping_instance->get_owner_name(),
                    'contactNumber' => $Anteam_shipping_instance->get_owner_phone(),
                    'business' => true,
                ],
                'dropoff' => [
                    'address' => $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode(),
                    'lat' => 0,
                    'lon' => 0,
                    'instructions' => '',
                    'contactName' => $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name(),
                    'contactNumber' => $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone(),
                    'business' => null,
                ],
                'pickup_start_time' => $start_time,
                'dropoff_start_time' => $start_time,
                'size' => $this->getSize($order, $woo_api),
            ];
    
            

            $handle = $this->setup_curl_post('https://api.anteam.co.uk/api/requests/woo_create/', $data);
            // Prepare JSON and send request
            
            $result = curl_exec($handle);
            $httpStatus = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
    
            echo '<p>'.$httpStatus.'</p>';
            // error handling from API
            if($httpStatus==201) {
                // Order has been accepted, update status to completed
                $order->update_status('completed');
                $customer_id = json_decode($result)->customer_id;
    
                // save most recently interacted with order : for undo button
                // $Anteam_shipping_instance->last_order = $order->get_id();
                // writeOrder($order, $customer_id);
                // update_post_meta($order->get_id(), 'anteam_denied', 'false');
                $this->update_anteam_meta($order->get_id(), 'anteam_denied', 'approved');
                $order->save();
    
                return true;
            } else {
                error_log("Error, line 155 : Anteam utilities.php , Result : " . $result . " Status : " . $httpStatus);
                echo '<script type="text/javascript">alert("Error : Order No. ' . $order->get_id() . ' not fulfilled succesfully, please contact Anteam support");</script>';
                return false;
            }
    
    }

    public function orderDenied($order)
    {
        // save most recently interacted with order : for undo button
        // $Anteam_shipping_instance = new WC_Anteam_Shipping_method();
        // $Anteam_shipping_instance->last_order = $order->get_id();
    
        update_post_meta($order->get_id(), 'anteam_denied', 'denied');
        $order->save();
    }



    public function update_anteam_meta($order_id, $key, $value){
    //   echo "1";
    //   $meta = get_post_meta($order_id, 'anteam', true);
    //   echo "2";
    //   if (!isset($meta)){
    //     echo "3";
    //     $meta = array();
    
    //   } else {
    //     echo "4";
    //     $meta = json_decode($meta);
    
    //   }
    //   echo "5";
    //   $meta[$key] => $value;
    //   echo "6";
    //   $meta_str = jason_encode($meta);
    
    //   switch (json_last_error()) {
    //       case JSON_ERROR_NONE:
    //           echo ' - No errors';
    //       break;
    //       case JSON_ERROR_DEPTH:
    //           echo ' - Maximum stack depth exceeded';
    //       break;
    //       case JSON_ERROR_STATE_MISMATCH:
    //           echo ' - Underflow or the modes mismatch';
    //       break;
    //       case JSON_ERROR_CTRL_CHAR:
    //           echo ' - Unexpected control character found';
    //       break;
    //       case JSON_ERROR_SYNTAX:
    //           echo ' - Syntax error, malformed JSON';
    //       break;
    //       case JSON_ERROR_UTF8:
    //           echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    //       break;
    //       default:
    //           echo ' - Unknown error';
    //       break;
    //   }
    
    
    //   echo "7";
      update_post_meta($order_id, $key, $value);
    //   echo "8";
    
    }


    private function getWeightMultiplier() {
        $unit = get_option('woocommerce_weight_unit');
    
        if($unit == 'kg') {
            return 1;
        } else if($unit == 'g') {
            return 1000;
        } else if($unit == 'lbs') {
            return 2.20462262;
        } else {
            return 35.273962;
        }
    }



}
