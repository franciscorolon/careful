<?php
/**
 * Plugin Name: Envia.com Module
 * Plugin URI: https://www.envia.com/integraciones/woocommerce/
 * Version: 3.2.1
 * Description: Send easily WooCommerce orders with Envia and Rate Shipment Cost.
 * Author: Tendecys Innovations
 * Author URI: https://www.envia.com/
 * Text Domain: Envia.com Module
 */

ob_start();
define('ENVIA_HOSTNAME', 'https://ship.envia.com/');
define('ENVIA_QUERIES_HOSTNAME', 'https://queries.envia.com');
define('ENVIA_APP_HOSTNAME', 'https://api-clients.envia.com');
define('ENVIA_LANDING', 'https://envia.com');
define('ENVIA_S3', 'https://s3.us-east-2.amazonaws.com/enviapaqueteria');

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	add_filter( 'bulk_actions-edit-shop_order', 'downloads_bulk_actions_edit_product_envia', 20, 1 );
	function downloads_bulk_actions_edit_product_envia( $actions ) {
		$actions['write_downloads'] = __( 'Create multiple labels', 'woocommerce' );
		return $actions;
	}

	// Make the action from selected orders
	add_filter( 'handle_bulk_actions-edit-shop_order', 'downloads_handle_bulk_action_edit_shop_order_envia', 10, 3 );
	function downloads_handle_bulk_action_edit_shop_order_envia( $redirect_to, $action, $post_ids ) {
		if ( 'write_downloads' !== $action ) {
			return $redirect_to; // Exit
		}

		global $attach_download_dir, $attach_download_file; // ???

		$processed_ids = array();

		$site = preg_replace('(^https?://)', '', get_site_url());

		$url = ' https://ship.envia.com/integrate/createmultilabel?shop=' . $site;

		foreach ( $post_ids as $post_id ) {
			$url .= '&ids[]=' . $post_id;
			$order = wc_get_order( $post_id );
			$order_data = $order->get_data();

			// Your code to be executed on each selected order
			fwrite($myfile,
				$order_data['date_created']->date('d/M/Y') . '; ' .
				'#' . ( ( $order->get_type() === 'shop_order' ) ? $order->get_id() : $order->get_parent_id() ) . '; ' .
				'#' . $order->get_id()
			);
			$processed_ids[] = $post_id;
		}

		echo '<script type="text/javascript" language="Javascript">window.open("' . esc_js( $url ) . '");</script>';
		die();

		/*return $redirect_to = add_query_arg( array(
		'write_downloads' => '1',
		'processed_count' => count( $processed_ids ),
		'processed_ids' => implode( ',', $processed_ids ),
		), $redirect_to );*/
	}

	// The results notice from bulk action on orders
	add_action( 'admin_notices', 'downloads_bulk_action_admin_notice_envia' );
	function downloads_bulk_action_admin_notice_envia() {
		if ( empty( $_REQUEST['write_downloads'] ) ) {
			return; // Exit
		}

		$count = intval( ( isset( $_REQUEST['processed_count'] ) ? $_REQUEST['processed_count'] : 0 ) );

		printf( 
			'<div id="message" class="updated fade"><p>' .
			/* translators: %s: Number of Orders*/
			esc_html(_n( 'Processed %s Order for download.',
				'Processed %s Orders for downloads.',
				$count,
				'write_downloads'
			)) . '</p></div>', esc_html( $count ) );
	}

	add_action( 'add_meta_boxes', 'my_order_meta_box_envia' );
	function my_order_meta_box_envia() {
		add_meta_box(
			'woocommerce-order-enviamodule',
			__('Generate New Shipping Label - ENVIA.COM'),
			'order_meta_box_content_envia',
			'shop_order',
			'side',
			'high'
		);
	}

	function wp_enqueue_custom_stylesheets() {
		wp_enqueue_style( 'envia_bootstrap_stylesheet', plugins_url( 'assets/css/bootstrap.min.css', __FILE__ ), array(), '1' );
		wp_enqueue_style( 'envia_stylesheet', plugins_url( 'assets/css/woocommerce_module.min.css?3', __FILE__ ), array(), '3' );
	}
	add_action( 'envia_styles_hook', 'wp_enqueue_custom_stylesheets' );

	function wp_enqueue_custom_scriptsheets() {
		wp_enqueue_script( 'envia_bootstrap_script', plugins_url( 'assets/js/bootstrap.min.js', __FILE__ ), array(), '1' );
		wp_enqueue_script( 'envia_script', plugins_url( 'assets/js/woocommerce_module.min.js?8', __FILE__ ), array(), '6' );
	}
	add_action( 'envia_scripts_hook', 'wp_enqueue_custom_scriptsheets' );

	function order_meta_box_content_envia() {
		do_action('envia_styles_hook');
		do_action('envia_scripts_hook');
		$url = get_site_url();
		$id = isset($_GET['post']) ? sanitize_text_field( $_GET['post'] ) : '';
		$enviaHostname = ENVIA_HOSTNAME;
		$enviaLanding = ENVIA_LANDING;
		$enviaS3 = ENVIA_S3;

		$order = get_content("{$enviaHostname}/integrate/orders?id={$id}&shop={$url}&ecommerce=woocommerce");
		$o = json_decode($order, true);

		echo '
		<input style="display:none;" data-name="enviaJQuery" data-hostname="' . esc_attr( $enviaHostname ) . '" data-shop="' . esc_attr( $url ) . '">
		
		<div id="enviaPrestashopModal" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-lg" role="document" style="min-width: 85%;left: 3%;">
				<div class="modal-content">
					<div class="modal-body">
						<div id="preloader">
							<div class="preloader-content">
								<div class="preload"></div>
								<img src="' . esc_url( $enviaS3 ) . '/uploads/images/logo-enviapaqueteria.png" alt="">
							</div>
						</div>
						<iframe src="" style="display: block; position: relative; width: 100%; height: 800px;" frameborder="0" id="iframe"></iframe>
					</div>
				</div>
			</div>
		</div>';
		echo '<div id="enviapaqueteria_module">';
		if ( true == $o['success'] && 0 < $o['count'] ) {
			echo '
			<div style="text-align:center;padding: 15px 0px;">
				<p style="margin: 0;padding-top:5px;" class="alert-success">Guía Generada con Éxito</p>
				<h2 style="margin-top: 0;padding: 0;font-size: 20px;padding-bottom:5px;font-weight:bolder;" class="alert-success"><a href="{$enviaLanding}/rastreo?label=' . esc_url( $o['data']['tracking_number'] ) . '" class="text-success" target="_blank">' . esc_html( $o['data']['tracking_number'] ) . '</a></h2>
				<p style="margin:0;margin-top:10px;"><strong>Creada:</strong> ' . esc_html( strftime( '%d %b, %Y', strtotime( $o['data']['created_at'] ) ) ) . '</p>
				<p style="margin:0; text-transform: capitalize;"><strong>Paquetería:</strong> ' . esc_html( $o['data']['carrier'] ) . '</p>
				<p style="margin:0; text-transform: capitalize;"><strong>Tipo:</strong> ' . esc_html( $o['data']['service_type'] ) . '</p>
			</div>
			<div class="wide">
				<div id="delete-action">
					<a class="btn btn-default" target="_blank" href="' . esc_url( $enviaS3 ) . '/uploads/' . esc_url( $o['data']['carrier_id'] ) . '/' . esc_url( $o['data']['file'] ) . '">Descargar Guía</a>
				</div>
				<a href="{$enviaLanding}/rastreo?label=' . esc_url( $o['data']['tracking_number'] ) . '" target="_blank" type="submit" class="button save_order button-primary">Rastrea tu Guía</a>
			</div>
			';
		} else {
			echo '
			<button type="button" class="btn btn-default enviaBtn" data-id="' . esc_attr( $id ) . '" data-shop="' . esc_attr( $url ) . '" data-type="">
				<span class="img"><img src="' . esc_url( $enviaS3 ) . '/uploads/images/logo-enviapaqueteria.png" alt="" width="40"></span>
				<span>Quote Shipping</span>
			</button>
			<button type="button" class="btn btn-default enviaBtn" data-id="' . esc_attr( $id ) . '" data-shop="' . esc_attr( $url ) . '" data-type="true">
				<span class="img"><img src="' . esc_url( $enviaS3 ) . '/uploads/images/logo-enviapaqueteria.png" alt="" width="40"></span>
				<span>Generate Shipping</span>
			</button>';
		}
		echo '</div>';
	}

	add_action( 'woocommerce_admin_order_data_after_order_details', 'fulfillment_display_order_data_envia' );
	function fulfillment_display_order_data_envia( $order ) {
		if ( get_post_meta( $order->get_id(), '_carrier', true ) && get_post_meta( $order->get_id(), '_tracking_url', true ) ) { ?>
			<div class="order_data_column" style='width:100%'>
				<h3><?php esc_html_e( 'Fulfillment' ); ?></h3>
				<div>
					<?php 
					echo '<p><strong>' . esc_html_e( 'Carrier' ) . ':</strong></br>';
					echo esc_html( get_post_meta( $order->get_id(), '_carrier', true ) ) . '</p>';

					echo '<p><strong>' . esc_html_e( 'Tracking number' ) . ':</strong></br>';
					echo '<a target="blank" href="' . esc_html( get_post_meta( $order->get_id(), '_tracking_url', true ) ) . '">' . esc_html( get_post_meta( $order->get_id(), '_tracking_number', true ) ) . '</a></p>';
					echo '<p><strong>' . esc_html_e( 'Fulfilled by' ) . ':</strong></br>';
					echo esc_html_e( get_post_meta( $order->get_id(), '_fulfilled_by', true ) ) . '</p>'; 
					?>
				</div>
			</div>
		<?php 
		}
	}

	add_action( 'woocommerce_checkout_order_processed', 'copy_product_input_fields_to_order_meta_envia', PHP_INT_MAX );
	function copy_product_input_fields_to_order_meta_envia( $order_id ) {
		$fields_to_update = array(
			'_wcj_product_input_fields_global_1',
			'_wcj_product_input_fields_local_1',
		);
		foreach ( $fields_to_update as $field_to_update ) {
			update_post_meta(
				$order_id,
				$field_to_update,
				do_shortcode( '[wcj_order_items_meta order_id="' . $order_id . '" meta_key="' . $field_to_update . '"]' )
			);
		}
	}

	function shipping_method_envia_init() {
		if ( ! class_exists( 'Shipping_Method_Envia' ) ) {
			class Shipping_Method_Envia extends WC_Shipping_Method {

				public function __construct() {
					$this->id                 = 'checkout_envia_shipping';
					$this->method_title       = __( 'Envia.com' );
					$this->method_description = __( 'Envia shipping for your checkout, Fill in your address of origin that will be used when quoting' );
					$this->title       = __( 'Envia.com' );

					$this->init();
					$this->postalCode = $this->get_option( 'postalCode' );
					$this->state = $this->get_option( 'state' );
					$this->city = $this->get_option( 'city' );
					$this->country = WC_Countries::get_base_country();
					$this->active = $this->get_option( 'active' );
					
				}

				public function init() {
					// Load the settings API
					// $this->admin_options();
					$this->init_form_fields();
					$this->init_settings();
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				public function init_form_fields() {
					do_action('envia_scripts_hook');
					$baseCountry = WC_Countries::get_base_country();
					$postalcodeName = 'Zip Code';
					if ( 'CO' == $baseCountry ) {
						$postalcodeName = 'Nit / CC';
					}
					$response = json_decode(get_content(ENVIA_QUERIES_HOSTNAME . '/state?country_code=' . $baseCountry));
					$statesArray = isset($response->data) ? $response->data : array();
					$states = array();
					foreach ( $statesArray as $value ) {
						$states[$value->code_2_digits] = $value->name;
					}
					$this->form_fields = array(
						'active' => array(
							'title' => 'Active/Inactive',
							'type' => 'checkbox',
							'label' => 'Enable or disable envia shipping method in your checkout',
							'default' => 'no'
						),
						'state' => array(
							'title' => 'State',
							'type'        => 'select',
							'class'       => 'wc-enhanced-select',
							'description' => 'Select your origin state ',
							'desc_tip' => true,
							'default' => '1',
							'options'     => $states
						),
						'city' => array(
							'title' => 'City',
							'type' => 'text',
							'description' => '',
							'default' => '',
							'desc_tip'    => true
						),
						'postalCode' => array(
							'title' => $postalcodeName,
							'type' => 'text',
							'description' => '',
							'default' => '',
							'desc_tip'    => true
						)
					);
				}

				public function admin_options() {
					?>
					<input id="ENVIA_APP_HOSTNAME" data-env="<?php echo esc_attr( ENVIA_HOSTNAME ); ?>" style="display: none;">
					<h2><?php esc_html_e( 'Envia', 'woocommerce' ); ?></h2>
					<p>Envia shipping for your checkout, Fill in your address of origin that will be used when quoting.</p>
					<table class="form-table" style="margin-bottom: 10px;">
						<?php $this->generate_settings_html(); ?>
					</table> 
					<p><a href="" class="shipping-config" data-url="<?php echo esc_attr( get_site_url() ); ?>" style="margin-bottom: 10px;">Configure your checkout in envia</a></p>
					<p><a href="" class="shipping-rates" data-url="<?php echo esc_attr( get_site_url() ); ?>">Click here to set your custom shipping rates in envia</a></p>
					<?php
				}

				public function calculate_shipping( $package = array() ) {
					if ( 'yes' == $this->active ) {
						global $woocommerce;
						$cart = $woocommerce->cart->get_cart();
						$origin = array(
							'postalCode' => $this->postalCode,
							'country' => $this->country,
							'city' => $this->city,
							'state' => $this->state
						);
						$response = json_decode(getCheckout($package, $origin, $cart));
						if ( is_array( $response ) ) {
							foreach ( $response as $key => $value ) {
								$rate = array(
									'id'      => $key + 1,
									'label' => $value->carrier . '(' . $value->service . ')',
									'cost' => $value->totalPrice,
									'calc_tax' => 'per_item',
									'package' => $package
								);
								$this->add_rate( $rate );
							}
						}
					}
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'shipping_method_envia_init' );

	function shipping_add_method( $methods ) {
		$methods['checkout_envia_shipping'] = 'Shipping_Method_Envia'; 
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'shipping_add_method' );
}

ob_end_flush();

function get_content ( $URL ) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $URL);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function getCheckout( $package, $origin, $cart ) {
	$url = ENVIA_APP_HOSTNAME . '/v2/checkout/woocommerce/0?shop_url=' . get_site_url();

	$statesCO = array(
		'AMZ' => 'AM',
		'ANT' => 'AN',
		'ARU' => 'AR',
		'ATL' => 'AT',
		'BOL' => 'BL',
		'BOY' => 'BY',
		'CAL' => 'CL',
		'CAQ' => 'CA',
		'CAS' => 'CS',
		'CAU' => 'CU',
		'CES' => 'CE',
		'CHOC' => 'CH',
		'COR' => 'CO',
		'CUN' => 'CN',
		'GUA' => 'GU',
		'GUV' => 'GA',
		'HUI' => 'HU',
		'GUJ' => 'LG',
		'MAG' => 'MA',
		'MET' => 'ME',
		'NAR' => 'NA',
		'NOR' => 'NS',
		'PUT' => 'PU',
		'QUI' => 'QU',
		'RIS' => 'RI',
		'SAP' => 'SA',
		'SAN' => 'SN',
		'SUC' => 'SU',
		'TOL' => 'TO',
		'VAC' => 'VC',
		'VAU' => 'VA',
		'VIC' => 'VI'
	);

	// $unicodeCharact = array(
	// 	'\u00f3' => 'o',
	// 	'\u00eD' => 'i',
	// 	'\u00fA' => 'u',
	// 	'\u00e9' => 'e',
	// 	'\u00e1' => 'a'
	// );

	// $unicodeValue;

	// if($package['destination']['country'] == 'CO'){
	// 	foreach (array_keys($unicodeCharact) as $value) {
	// 		$find = strpos($package['destination']['city'], $value);
	// 		if($find !== false) $unicodeValue = $value;
	// 		$unicodeValue = $value;
	// 	}
	// }

	// $package['destination']['city'] = str_replace($unicodeValue, $unicodeCharact[$unicodeValue] , $package['destination']['city']);

	if ( 'CO' == $package['destination']['country'] && 'DC' == $package['destination']['state'] ) {
		$package['destination']['state'] = 'CUN';
	}
	if ( 'CO' == $package['destination']['country'] && array_key_exists( $package['destination']['state'], $statesCO ) ) {
		$package['destination']['state'] = $statesCO[$package['destination']['state']];
	}
	
	$origin = (object) array(
		'name' => 'envia',
		'company' => 'envia',
		'email' => 'clients@envia.com',
		'phone' => '1234567890',
		'street' => 'Vasconcelos 1400',
		'number' => 'null',
		'district' => '',
		'city' => $origin['city'],
		'state' => $origin['state'],
		'country' => $origin['country'],
		'postalCode' => $origin['postalCode'] 
	);
	$arrayDest = (object) array(
		'name' => 'envia',
		'company' => 'envia',
		'email' => 'clients@envia.com',
		'phone' => '1234567890',
		'street' => $package['destination']['address'],
		'number' => $package['destination']['address_2'],
		'district' => $package['destination']['address_1'],
		'city' => $package['destination']['city'],
		'state' => $package['destination']['state'],
		'country' => $package['destination']['country'],
		'postalCode' => $package['destination']['postcode']
	);
	$destination = (object) $arrayDest;

	$packInfo = package_dimensions($cart);

	$currency = get_woocommerce_currency();

	$data = (object) array('origin' => $origin, 'destination' => $destination, 'currency' => $currency);
	$data->items = $packInfo['items'];
	$data->packages = $packInfo['packages'];
	$data = json_encode($data);

	$req = curl_init();
	curl_setopt($req, CURLOPT_URL, $url);
	curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($req, CURLOPT_POSTFIELDS, $data);
	curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
	$response = curl_exec($req);
	curl_close($req);
	return $response;
}

function package_dimensions ( $cart ) {
	$volumetricLimit = 68;
	$volumetric = 0;
	$volumetricQuote = 0;
	$weight = 0;
	$items = array();
	$packages = array();

	foreach ( $cart as $value ) {
		$productId =  isset( $value['product_id'] ) ? $value['product_id'] : $value->get_product_id();
		$variationId = isset( $value['variation_id'] ) ? $value['variation_id'] : $value->get_variation_id();
		$product = wc_get_product($productId);
		$height = floatval($product->get_height());
		$length = floatval($product->get_width());
		$width = floatval($product->get_length());
		$variationWeight = 0;
		if ( 0 != $variationId ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation ) {
				if ( $variation['variation_id'] == $variationId ) { 
					$height = empty(floatval($variation['dimensions']['height'])) ? $height : floatval($variation['dimensions']['height']);
					$width = empty(floatval($variation['dimensions']['width'])) ? $width : floatval($variation['dimensions']['width']);
					$length = empty(floatval($variation['dimensions']['length'])) ? $length : floatval($variation['dimensions']['length']);
					$variationWeight = empty(floatval($variation['weight'])) ? 0 : floatval($variation['weight']);
				}
			}
		}
		$finalWeight = ( 0 != $variationWeight ? $variationWeight : ( !empty( $product->get_weight() ) ? floatval( $product->get_weight() ) : 2 ) );

		for ( $i=0; $i < $value['quantity']; $i++ ) {
			$volumetric += ( $height * $width * $length ) / 5000;
			$weight += $finalWeight;

			$volumetricQuote = $volumetric; 
			$volumetricSub = ( $height * $width * $length ) / 5000;

			if ( $volumetricQuote >= $volumetricLimit ) {
				$volumetricRate = $volumetricQuote - $volumetricSub;
				$weight -= $finalWeight;
				$volumetricDimensions = pow( ( $volumetricRate * 5000 ), 1/3 );
				$package = (object) array(
					'content' => 'Packages',
					'amount' => 1,
					'type' => 'box',
					'dimensions' => (object) array('length' => $volumetricDimensions, 'width' => $volumetricDimensions, 'height' => $volumetricDimensions),
					'weight' => $weight,
					'lengthUnit' => 'cm',
					'weightUnit' => 'kg',
					'insurance' => 0,
					'declaredValue' => 0
				);
				array_push($packages, $package);
				$weight = $finalWeight;
				$volumetric = ( $height * $width * $length ) / 5000;
			}
		}

		$item = (object) array(
			'name' => $product->get_name(),
			'sku' => $product->get_sku(),
			'quantity' => $value['quantity'],
			'weight' => $finalWeight,
			'price' => $product->get_price(),
			'vendor' => '',
			'requiresShipping' => true,
			'taxable' => true,
			'fulfillmentService' => 'manual',
			'properties' => $height . ' * ' . $width . ' * ' . $length,
			'productId' => $product->get_id(),
			'variantId' => $variationId
		);
		array_push($items, $item);
	}

	$volumetricDimensions = pow( ( $volumetricQuote * 5000 ), 1/3 );
	$package = (object) array(
		'content' => 'Package',
		'amount' => 1,
		'type' => 'box',
		'dimensions' => (object) array( 'length' => $volumetricDimensions, 'width' => $volumetricDimensions, 'height' => $volumetricDimensions ),
		'weight' => $weight,
		'lengthUnit' => 'cm',
		'weightUnit' => 'kg',
		'insurance' => 0,
		'declaredValue' => 0
	);
	array_push( $packages, $package );

	return array(
		'items' => $items,
		'packages' => $packages
	);
} ?>
