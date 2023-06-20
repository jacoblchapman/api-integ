<?php
// Test with normal WC pending status before adding custom status and shipping options

// Include the WooCommerce plugin file.
require_once 'woocommerce.php';

// Create a new class for the plugin.
class WooCommercePendingOrdersTable
{
    // Constructor.
    public function __construct()
    {
        // Add the plugin action hook to display the table of orders.
	    // Array wraps the class method up as a function
        add_action('admin_menu', array($this, 'addPage'));
    }

    // Function to add the new page to the admin menu.
    public function addPage()
    {
        // Add a new page to the admin menu.
	    // Page Name, Page Title, Access permissions, Slug, Construct Function, ?Icon
        add_menu_page('WooCommerce Pending Orders', 'Pending Orders', 'manage_woocommerce', 'woocommerce-pending-orders', array($this, 'displayPage'));
    }

    // Function to display the table of orders on the new page.
    public function displayPage()
    {
        // Get all orders with status as pending.
        $orders = wc_get_orders(array('status' => 'pending'));
        
        echo '<table>';

        // Add the table header.
        echo '<thead>';
        echo '<tr>';
        echo '<th>Order ID</th>';
        echo '<th>Approve / Deny</th>';
        echo '</tr>';
        echo '</thead>';

        // Add the table rows.
        foreach ($orders as $order) {
            echo '<tr>';
            echo '<td>' . $order->id . '</td>';
            echo '<td>';
            echo '<button onclick="$this->orderApproved($order);">Approve</button>';
            echo '<button onclick="$this->orderDenied($order);">Deny</button>';
            echo '</td>';
            echo '</tr>';
        }

        // End the table output.
        echo '</table>';
    }

    public function orderApproved($order)
    {
	    // Placeholder
        echo 'Order Approved';
    }
    public function orderDenied($order)
    {
	    // Placeholder
        echo 'Order Denied';
    }
}

// Initialize the class
new WooCommercePendingOrdersTable();