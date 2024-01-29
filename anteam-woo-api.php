<?php

require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );

class AnteamWooApi {

    public function reset_order($order) {
        update_post_meta($order->get_id(), 'anteam_denied', 'denied');
        $order->update_status('processing');
    }
  
    public function isUnknown($order) {
    
        if($this->metaExists($order)) {
            return !($this->getMeta($order) == 'approved' || $this->getMeta($order) == 'denied');
        }
        return false;
    }
    
    public function isApproved($order)
    {
        if($this->metaExists($order)) {        
            return $this->getMeta($order) == 'approved';
        }
        return false;
    }
    
    public function isDenied($order)
    {
        if($this->metaExists($order)) {
            return $this->getMeta($order) == 'denied';
        }
        return false;
    }
    
    public function metaExists($order)
    {
        return metadata_exists('post', $order->get_id(), 'anteam_denied');
    }
    
    
    public function getMeta($order)
    {
        return get_post_meta($order->get_id(), 'anteam_denied', true);
    }


    public function getOrderWeight($order) {

        // Get the items in the order
        $items = $order->get_items();
        $total_weight = 0;
        $size = '';

        // Loop through each item and calculate the total weight
        foreach ($items as $item) {
            $product = $item->get_product();

            if ($product && $product->get_weight()) {
                $weight = floatval($product->get_weight());
                $quantity = intval($item->get_quantity());
                $item_weight = $weight * $quantity;
                $total_weight += $item_weight;
            }

        }

        // returns weight in whatever is shop's basic unit
        return $total_weight;

    }

    public function getWeightUnit(){
        return get_option('woocommerce_weight_unit');
    }



    // fetch all orders to be displayed in table, in accordance to current setting enabled
    public function fetchOrders($arr = array('status' => 'wc-processing','limit' => -1,'orderby' => 'date', 'order' => 'DESC',)) {

        // fetch all store orders
        $orders = wc_get_orders($arr);
        
        // create array to store valid orders
        $Anteam_shipping_instance = new WC_Anteam_Shipping_method();

        // filter based on setting
        if($Anteam_shipping_instance->get_enabled() == true) {
            $retOrders = array();
            foreach ($orders as $order) {
                // select only orders with Anteam shipping method
                $shipping_method = $order->get_shipping_method();

                // if shipping method is Anteam shipping, add it to ret array
                if ($shipping_method == 'Anteam Shipping') {
                    array_push($retOrders, $order);
                }
            }
            return retOrders;
        } 
        // else {

            // $authToken = $Anteam_shipping_instance->get_auth_token();
            // $checkOrders = checkAddresses($orders, $authToken);
            // foreach ($orders as $order) {            
                // if(isUnknown($order)) {
                    // foreach ($checkOrders as $orderChecked) {
                    //     if($orderChecked->id == $order->get_id()) {
                    //         if($orderChecked->accepted) {
                    //             array_push($retOrders, $order);
                    //             break;
                    //         }
                    //     }
                    // }
                // }
            // }
        // }

        return $orders;
    }


}
