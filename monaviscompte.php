<?php
/*
Plugin Name: monaviscompte
Plugin URI: https://www.monaviscompte.fr
Description: Easily enable monaviscompte features (widget, post-purchase reviews, export reviews) on your website.
Version: 2.1.0
Author: monaviscompte
Author URI: https://www.monaviscompte.fr
License: GPL2 or later
Text Domain: monaviscompte
Domain Path: /languages
*/

include_once plugin_dir_path( __FILE__ ).'/monaviscompte_widget.php';
include_once plugin_dir_path( __FILE__ ).'/includes/constants.php';

class monaviscompte_Plugin
{
	public function __construct()
  {
  	load_plugin_textdomain('monaviscompte', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_action('widgets_init', function() { register_widget('monaviscompte_Widget'); });
		add_action('plugins_loaded', function() { self::monaviscompte_load_translation_files(); });
		add_action('admin_enqueue_scripts', function() { self::monaviscompte_load_styles(); });
		add_action('admin_menu', function() { self::monaviscompte_add_admin_menu(); });
    	
    if (self::has_woocommerce()) {
    	add_action('woocommerce_order_status_completed', function($orderId) { self::monaviscompte_process_post_purchase($orderId); } );
    }
	}
    
	private function monaviscompte_load_translation_files() 
	{
		load_plugin_textdomain('monaviscompte', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	private function monaviscompte_load_styles() 
	{
		wp_register_style('monaviscompte_admin_menu_styles', plugins_url() . '/monaviscompte/assets/css/menu.css', array(), null);
		wp_enqueue_style('monaviscompte_admin_menu_styles');
	}
	
	private function monaviscompte_add_admin_menu()
	{
		add_menu_page(__('monaviscompte'), __('monaviscompte'), 'manage_options', 'monaviscompte-menu', null, null, 58 );
		add_submenu_page('monaviscompte-menu', __('Configuration', 'monaviscompte'), __('Configuration', 'monaviscompte'), 'manage_options', 'monaviscompte-menu', function() { self::monaviscompte_configure(); } );
		
		if (self::has_woocommerce()) {
			add_submenu_page('monaviscompte-menu', __('Orders export', 'monaviscompte'), __('Orders export', 'monaviscompte'), 'manage_options', 'monaviscompte-orders-export', function() { self::monaviscompte_export_orders(); } );
		}
	}
	
	private function monaviscompte_configure() 
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'monaviscompte'));
		}
		
		$itemIdFieldName = MONAVISCOMPTE_ITEM_ID_FIELD_NAME;
		$accessKeyFieldName = MONAVISCOMPTE_ACCESS_KEY_FIELD_NAME;
		$apiKeyFieldName = MONAVISCOMPTE_API_KEY_FIELD_NAME;
		$hiddenFieldName = 'monaviscompte_submit_hidden';
		
		$itemId = get_option($itemIdFieldName);
		$accessKey = get_option($accessKeyFieldName);
		$apiKey = get_option($apiKeyFieldName);
		
		if (isset($_POST[$hiddenFieldName]) && $_POST[$hiddenFieldName] == 'Y') {
		
			if (isset($_POST[$itemIdFieldName])) {
				$itemId = $_POST[$itemIdFieldName];
				update_option($itemIdFieldName, $itemId);
			}
			
			if (isset($_POST[$accessKeyFieldName])) {
				$accessKey = $_POST[$accessKeyFieldName];
				update_option($accessKeyFieldName, $accessKey);
			}
			
			if (isset($_POST[$apiKeyFieldName])) {
				$apiKey = $_POST[$apiKeyFieldName];
				update_option($apiKeyFieldName, $apiKey);
			}

			echo '<div class="notice notice-success">';
			echo	'<p>';
			echo		'<strong>'; 
			echo			__('Settings saved.', 'monaviscompte');
			echo		'</strong>';
			echo	'</p>';
			echo '</div>';
		}
		
		echo '<div class="wrap">';
		echo 	'<h1>' . __('monaviscompte Settings', 'monaviscompte') . '</h1>';
		echo 	'<form method="post" action="">';
		echo 		'<input type="hidden" name="'.$hiddenFieldName.'" value="Y">';
		echo		'<table class="form-table">';
		echo 			'<tbody>';
		echo				'<tr>';
		echo					'<th scope="row">';
		echo						'<label for="'.$itemIdFieldName.'">'.__('Item identifier', 'monaviscompte').'</label>';
		echo					'</th>';
		echo					'<td>';
		echo						'<input name="'.$itemIdFieldName.'" type="text" id="'.$itemIdFieldName.'" value="'.$itemId.'" class="regular-text">';
		echo						'<p class="description" id="'.$itemIdFieldName.'-description">'.__('This value is required to display the widget or to send automatic post-purchase review emails. You can find it in the monaviscompte back-office.', 'monaviscompte').'</p>';
		echo					'</td>';
		echo				'</tr>';
		echo				'<tr>';
		echo					'<th scope="row">';
		echo						'<label for="'.$accessKeyFieldName.'">'.__('Access key', 'monaviscompte').'</label>';
		echo					'</th>';
		echo					'<td>';
		echo						'<input name="'.$accessKeyFieldName.'" type="text" id="'.$accessKeyFieldName.'" value="'.$accessKey.'" class="regular-text">';
		echo						'<p class="description" id="'.$accessKeyFieldName.'-description">'.__('This value is required to display the widget. You can find it in the monaviscompte back-office.', 'monaviscompte').'</p>';
		echo					'</td>';
		echo				'</tr>';
		
		if (self::has_woocommerce()) {
			echo				'<tr>';
			echo					'<th scope="row">';
			echo						'<label for="'.$apiKeyFieldName.'">'.__('API key').'</label>';
			echo					'</th>';
			echo					'<td>';
			echo						'<input name="'.$apiKeyFieldName.'" type="text" id="'.$apiKeyFieldName.'" value="'.$apiKey.'" class="regular-text">';
			echo						'<p class="description" id="'.$apiKeyFieldName.'-description">'.__('This value is required to send automatic post purchase review emails. You can find it in the monaviscompte back-office.', 'monaviscompte').'</p>';
			echo					'</td>';
			echo				'</tr>';
		}
		
		echo 			'</tbody>';
		echo		'</table>';
		echo		'<p class="submit">';
		echo			'<input type="submit" name="submit" id="submit" class="button button-primary" value="'.__('Save changes', 'monaviscompte').'">';
		echo		'</p>';
		echo 	'</form>';
		echo '</div>';
	}
	
	private function monaviscompte_export_orders() 
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'monaviscompte'));
		}
		
		$hiddenFieldName = 'monaviscompte_submit_hidden';
		$sinceFieldName = 'monaviscompte_export_since';
		
		if (isset($_POST[$hiddenFieldName]) && $_POST[$hiddenFieldName] == 'Y') {
			
			$exportOrdersSince = $_POST[$sinceFieldName];
			$interval = '';
			
			switch ($exportOrdersSince)
			{
				case '1':
					$interval = date("Y-m-d", strtotime("-1 week"));
					break;
				case '2':
					$interval = date("Y-m-d", strtotime("-1 month"));
					break;
				case '3':
					$interval = date("Y-m-d", strtotime("-6 month"));
					break;
				default:
					$interval = date("Y-m-d", strtotime("-1 year"));
					break;
			}
			
			$output = '';
			
			$args = array('post_type' => 'shop_order', 'posts_per_page' => -1, 'post_status' => 'wc-completed');
			$args['date_query'] = array(array('after' => $interval, 'inclusive' => true));

			$orders = new WP_Query($args);
			
			$ordersCount = 0;
			
			if ($orders->have_posts()) {
			
				$output .= 'id;date;email;firstname;lastname'."\r\n";
			
				while($orders->have_posts()) {
				
					$ordersCount++;
				
					$orders->the_post();

					$order_details = new WC_Order( get_the_ID() );
					$user_id = $order_details->get_user_id();
					$user = get_user_by('id', $user_id);
					
					$output .= get_the_ID().';';
					$output .= get_the_date('Y-m-d').';';
					$output .= $order_details->billing_email.';';
					$output .= $order_details->billing_first_name.';';
					$output .= $order_details->billing_last_name;
					$output .= "\r\n";
				}
				
				$filename = 'orders_'.date('Ymd_His').'.csv';
			
				$upload = wp_upload_bits($filename, null, $output);

				if (empty($upload[error])) {
					echo '<div class="notice notice-success">';
					echo	'<p>';
					echo		'<strong>'; 
					echo			sprintf(__('%d orders were exported.', 'monaviscompte'), $ordersCount).'&nbsp;';
					echo			'<a href="'.$upload['url'].'">'. __('Click here to download the file', 'monaviscompte') . '</a>';
					echo		'</strong>';
					echo	'</p>';
					echo '</div>';
				}
			
				else {
					echo '<div class="notice notice-error">';
					echo	'<p>';
					echo		'<strong>'; 
					echo			__('File could not be saved to uploads directory:', 'monaviscompte') . '&nbsp;' . $upload[error]; 
					echo		'</strong>';
					echo	'</p>';
					echo '</div>';
				}
			
			}
			else {
				echo '<div class="notice notice-warning">';
				echo	'<p>';
				echo		'<strong>'; 
				echo			__('No orders to export.', 'monaviscompte'); 
				echo		'</strong>';
				echo	'</p>';
				echo '</div>';
			}
		}
		
		echo '<div class="wrap">';
		echo 	'<h1>' . __('Orders export', 'monaviscompte') . '</h1>';
		echo 	'<form method="post" action="">';
		echo 		'<input type="hidden" name="'.$hiddenFieldName.'" value="Y">';
		echo		'<table class="form-table">';
		echo 			'<tbody>';
		echo				'<tr>';
		echo					'<th scope="row">';
		echo						'<label for="'.$sinceFieldName.'">'.__('Since', 'monaviscompte').'</label>';
		echo					'</th>';
		echo					'<td>';
		echo						'<select name="'.$sinceFieldName.'" id="'.$sinceFieldName.'">';
		echo							'<option selected="selected" value="1">'. __('One week', 'monaviscompte') .'</option>';
		echo							'<option value="2">'. __('One month', 'monaviscompte') .'</option>';
		echo							'<option value="3">'. __('Six months', 'monaviscompte') .'</option>';
		echo							'<option value="4">'. __('One year', 'monaviscompte') .'</option>';
		echo						'</select>';
		echo					'</td>';
		echo				'</tr>';
		echo 			'</tbody>';
		echo		'</table>';
		echo		'<p class="submit">';
		echo			'<input type="submit" name="submit" id="submit" class="button button-primary" value="'.__('Export orders', 'monaviscompte').'">';
		echo		'</p>';
		echo 	'</form>';
		echo '</div>';
	}
	
	private function monaviscompte_process_post_purchase($order_id) 
	{
		$apiKey = get_option(MONAVISCOMPTE_API_KEY_FIELD_NAME);
		
		if (!empty($apiKey)) {
			$order = new WC_Order($order_id);
			
			$data = array();
			$data['private_key'] = $apiKey;
      $data['order_id'] = $order_id;
      $data['source'] = 'wordpress';
      $data['recipient'] = $order->billing_email;
      $data['first_name'] = $order->billing_first_name;
            
			$serializedProducts = array();

			$i = 0;
			foreach($order->get_items() as $item) {
				$product = wc_get_product($item['product_id']);
				
				$serializedProducts['products'][$i] = array(
					"id" => strval($item['product_id']),
					"name" => $product->get_title(),
					"summary" => $product->get_post_data()->post_excerpt,
					"picture" => wp_get_attachment_image_src(get_post_thumbnail_id($product->get_post_data()->ID), 'single-post-thumbnail')[0]
				);
				$i++;
			}
	
			$data['cart'] = json_encode($serializedProducts);

			$curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, "https://api.monaviscompte.fr/post-purchase/create/");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10000);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10000);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_exec($curl);
			curl_close($curl);
		}
	}
	
	private function has_woocommerce()
	{
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		} else {
			return false;
		}
	}
}

new monaviscompte_Plugin();
