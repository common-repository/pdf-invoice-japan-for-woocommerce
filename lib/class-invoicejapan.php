<?php
/**
 * PDF Invoice Japan for WooCommerce
 *
 * @package    PDF Invoice Japan for WooCommerce
 * @subpackage InvoiceJapan Main Functions
/*  Copyright (c) 2023- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$invoicejapan = new InvoiceJapan();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class InvoiceJapan {

	/** ==================================================
	 * Path
	 *
	 * @var $tmp_dir  tmp_dir.
	 */
	public $tmp_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $tmp_url  tmp_url.
	 */
	public $tmp_url;

	/** ==================================================
	 * Path
	 *
	 * @var $pdf_invoice  pdf_invoice.
	 */
	private $pdf_invoice;

	/** ==================================================
	 * Order Statuse
	 *
	 * @var $order_statuses  order_statuses.
	 */
	private $order_statuses;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$wp_uploads = wp_upload_dir();
		$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		$upload_url = $wp_uploads['baseurl'];
		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}
		$upload_dir = untrailingslashit( $upload_dir );
		$upload_url = untrailingslashit( $upload_url );
		$this->tmp_dir = $upload_dir . '/invoice-japan';
		$this->tmp_url = $upload_url . '/invoice-japan';

		/* Make tmp dir */
		if ( ! is_dir( $this->tmp_dir ) ) {
			wp_mkdir_p( $this->tmp_dir );
		}
		$this->pdf_invoice = $this->tmp_dir . '/invoice-';

		if ( ! class_exists( 'TCPDF' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'TCPDF/tcpdf.php';
		}

		$this->order_statuses = array( 'pending', 'processing', 'on-hold', 'completed' );

		foreach ( $this->order_statuses as $value ) {
			add_action( 'woocommerce_order_status_' . $value, array( $this, 'sa_wc_after_order_complete' ) );
		}
		add_action( 'woocommerce_order_refunded', array( $this, 'after_refund' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10, 1 );

		add_action( 'admin_menu', array( $this, 'plugin_menu' ), 100 );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

		/* Order & Refund actions for mail resending  */
		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'woocommerce_order_action_send_invoice_japan_invoice', array( $this, 'fired_send_invoice_japan_invoice' ), 10, 1 );
		add_action( 'woocommerce_order_action_send_invoice_japan_refund', array( $this, 'fired_send_invoice_japan_refund' ), 10, 1 );

		/* Order details custom for resend email */
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'order_details' ), 10, 1 );
	}

	/** ==================================================
	 * When the WooCommerce order is completed.
	 *
	 * @param int $order_id  order id.
	 * @since 1.00
	 */
	public function sa_wc_after_order_complete( $order_id ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$order = wc_get_order( $order_id );

		list( $gateway_txt, $gateway_mail_timings, $gateway_remarks, $gateway_refunds ) = $this->init_gateway_mail_timing_remarks_refunds();
		$gateway_mail_timings = get_option( 'invoicejapan_gateway_mail_timing', $gateway_mail_timings );
		if ( ! $gateway_mail_timings[ $order->get_payment_method() ][ $order->get_status() ] ) {
			return;
		}
		$gateway_remarks = get_option( 'invoicejapan_gateway_remarks', $gateway_remarks );

		$emails = array();
		$emails[] = $order->get_billing_email();

		$info_arr = $this->get_info( $order_id, $order, 'order' );

		$tax = new WC_Tax();

		$items = array();
		$total_none = 0;
		$total_reduced = 0;
		$total_normal = 0;
		$tax_status_none = true;
		$only_virtual = true;
		/* Get and Loop Over Order Items */
		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$product_item = wc_get_product( $product_id );
			$vendor_id = get_post_field( 'post_author', $product_id );
			$product_name = $item->get_name();
			$quantity = $item->get_quantity();
			$subtotal = $item->get_subtotal();
			$tax_subtotal = $item->get_subtotal_tax();
			$tax_class = $item->get_tax_class();
			$taxes = $tax->get_rates( $tax_class );
			$taxes_key = key( $taxes );
			$rate = $taxes[ $taxes_key ]['rate'];
			if ( 'none' === $product_item->get_tax_status() ) {
				$product_name .= ' **';
				$total_none += $subtotal;
			} else if ( $invoicejapan_set['reduced_tax'] == $rate ) {
				$product_name .= ' *';
				$total_reduced += $subtotal;
				$tax_status_none = false;
			} else if ( $invoicejapan_set['normal_tax'] == $rate ) {
				$total_normal += $subtotal;
				$tax_status_none = false;
			}
			$virtual = $product_item->is_virtual();
			if ( ! $virtual ) {
				$only_virtual = false;
			}
			$items[ $product_id ] = array(
				'name' => $product_name,
				'quantity' => $quantity,
				'total' => number_format( $subtotal, 0 ) . '円',
				'virtual' => $virtual,
			);
		}

		$tax_reduced = $this->round_ceil_floor( $total_reduced * ( $invoicejapan_set['reduced_tax'] / 100 ), 0 );
		$tax_normal = $this->round_ceil_floor( $total_normal * ( $invoicejapan_set['normal_tax'] / 100 ), 0 );

		$discount_arr = $this->discount( $order, $total_reduced, $tax_reduced, $total_normal, $tax_normal );

		$fee_arr = $this->fee( $order, $vendor_id );

		$shipping = 0;
		$shipping_tax = 0;
		if ( 0 < $order->get_shipping_total() ) {
			$shipping = $order->get_shipping_total();
			$shipping_tax = $order->get_shipping_tax();
		}
		$total_taxs = array(
			'grand_total' => number_format( $order->get_total() ) . '円',
			'total' => number_format( $total_normal + $total_reduced + $total_none ) . '円',
			'total_tax' => number_format( $tax_normal + $tax_reduced ) . '円',
			'none' => number_format( $total_none ) . '円',
			'reduced' => number_format( $total_reduced ) . '円',
			'reduced_tax' => number_format( $tax_reduced ) . '円',
			'normal' => number_format( $total_normal ) . '円',
			'normal_tax' => number_format( $tax_normal ) . '円',
			'shipping_total' => number_format( $shipping ) . '円',
			'shipping_tax' => number_format( $shipping_tax ) . '円',
		);

		$info_arr['name'] = apply_filters( 'invoice_japan_name', $info_arr['name'], $order, $vendor_id );
		if ( $tax_status_none ) {
			$info_arr['title_grand_total_text'] = '請求金額';
		}
		$info_arr['remark'] = apply_filters( 'invoice_japan_remark_order_' . $order->get_payment_method(), $gateway_remarks[ $order->get_payment_method() ]['order'], $vendor_id );
		$info_arr['vendor_id'] = $vendor_id;
		$info_arr['total_none'] = $total_none;
		$info_arr['status'] = wc_get_order_status_name( $order->get_status() );
		$info_arr['only_virtual'] = $only_virtual;

		$html = $this->generate_html( $order_id, $this->store_info( $vendor_id ), $info_arr, $items, $total_taxs, $discount_arr, $fee_arr, 'order' );

		$vendor = get_userdata( $vendor_id );
		$emails[] = $vendor->user_email;

		$pdf_file = $this->pdf_invoice . $order_id . '.pdf';
		$invoice_japan_tcpdf_off = apply_filters( 'invoice_japan_tcpdf_off', false );
		if ( $invoice_japan_tcpdf_off ) {
			if ( apply_filters( 'invoice_japan_pdf_write', $pdf_file, $html, $info_arr ) ) {
				$this->sent_mail( $emails, $pdf_file, $order_id, $info_arr['name'], $info_arr['create_date'], $info_arr['status'], $vendor_id, 'order' );
			}
		} elseif ( $this->pdf_write( $pdf_file, $html, $info_arr ) ) {
			$this->sent_mail( $emails, $pdf_file, $order_id, $info_arr['name'], $info_arr['create_date'], $info_arr['status'], $vendor_id, 'order' );
		}
	}

	/** ==================================================
	 * When the WooCommerce order is refund.
	 *
	 * @param int $order_id  order id.
	 * @param int $refund_id  refund_id.
	 * @since 1.00
	 */
	public function after_refund( $order_id, $refund_id ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$order = wc_get_order( $order_id );

		list( $gateway_txt, $gateway_mail_timings, $gateway_remarks, $gateway_refunds ) = $this->init_gateway_mail_timing_remarks_refunds();
		$gateway_remarks = get_option( 'invoicejapan_gateway_remarks', $gateway_remarks );
		$gateway_refunds = get_option( 'invoicejapan_gateway_refunds', $gateway_refunds );

		$emails = array();
		$emails[] = $order->get_billing_email();

		$info_arr = $this->get_info( $refund_id, $order, 'refund' );

		$refunds = $order->get_refunds();

		$tax = new WC_Tax();

		$items = array();
		$total_none = 0;
		$total_reduced = 0;
		$total_normal = 0;
		$fee_arr = array();
		$tax_status_none = true;
		$only_virtual = true;
		/* Get and Loop Over Refund Items */
		foreach ( $refunds as $refund ) {
			foreach ( $refund->get_items() as $item_id => $item ) {
				$product_id = $item->get_product_id();
				$product_item = wc_get_product( $product_id );
				$vendor_id = get_post_field( 'post_author', $product_id );
				$product_name = $item->get_name();
				$quantity = abs( $item->get_quantity() );
				$subtotal = abs( $item->get_subtotal() );
				$tax_subtotal = abs( $item->get_subtotal_tax() );
				$tax_class = $item->get_tax_class();
				$taxes = $tax->get_rates( $tax_class );
				$taxes_key = key( $taxes );
				$rate = $taxes[ $taxes_key ]['rate'];
				if ( 'none' === $product_item->get_tax_status() ) {
					$product_name .= ' **';
					$total_none += $subtotal;
				} else if ( $invoicejapan_set['reduced_tax'] == $rate ) {
					$product_name .= ' *';
					$total_reduced += $subtotal;
					$tax_status_none = false;
				} else if ( $invoicejapan_set['normal_tax'] == $rate ) {
					$total_normal += $subtotal;
					$tax_status_none = false;
				}
				$virtual = $product_item->is_virtual();
				if ( ! $virtual ) {
					$only_virtual = false;
				}
				$items[ $product_id ] = array(
					'date' => get_the_date( 'Y年m月d日', $item->get_order_id() ),
					'name' => $product_name,
					'quantity' => $quantity,
					'total' => number_format( $subtotal, 0 ) . '円',
					'virtual' => $virtual,
				);
				$info_arr['order_refund_date_text'] = '払戻日時：' . get_the_date( 'Y年m月d日 H時i分', $item->get_order_id() );
				if ( empty( $fee_arr ) ) {
					$fee_arr = $this->fee( $refund, $vendor_id );
				}
			}
		}
		$tax_reduced = $this->round_ceil_floor( $total_reduced * ( $invoicejapan_set['reduced_tax'] / 100 ), 0 );
		$tax_normal = $this->round_ceil_floor( $total_normal * ( $invoicejapan_set['normal_tax'] / 100 ), 0 );

		$discount_arr = array();

		$shipping = 0;
		$shipping_tax = 0;
		if ( 0 < $order->get_total_shipping_refunded() ) {
			$shipping = $order->get_total_shipping_refunded();
			$shipping_tax = $order->get_shipping_tax();
		}
		$total_taxs = array(
			'grand_total' => number_format( $order->get_total_refunded() ) . '円',
			'total' => number_format( $total_normal + $total_reduced + $total_none ) . '円',
			'total_tax' => number_format( $tax_normal + $tax_reduced ) . '円',
			'none' => number_format( $total_none ) . '円',
			'reduced' => number_format( $total_reduced ) . '円',
			'reduced_tax' => number_format( $tax_reduced ) . '円',
			'normal' => number_format( $total_normal ) . '円',
			'normal_tax' => number_format( $tax_normal ) . '円',
			'shipping_total' => number_format( $shipping ) . '円',
			'shipping_tax' => number_format( $shipping_tax ) . '円',
		);

		$info_arr['name'] = apply_filters( 'invoice_japan_name', $info_arr['name'], $order, $vendor_id );
		if ( $tax_status_none ) {
			$info_arr['title_grand_total_text'] = '払戻金額';
		}
		$info_arr['remark'] = apply_filters( 'invoice_japan_remark_refund_' . $order->get_payment_method(), $gateway_remarks[ $order->get_payment_method() ]['refund'], $vendor_id );
		$gateway_refund = apply_filters( 'invoice_japan_refund_text_' . $order->get_payment_method(), $gateway_refunds[ $order->get_payment_method() ], $vendor_id );
		if ( ! empty( $gateway_refund ) ) {
			$info_arr['order_refund_payment_text'] .= $gateway_refund;
		} else {
			$info_arr['order_refund_payment_text'] .= $order->get_payment_method_title();
		}
		$info_arr['vendor_id'] = $vendor_id;
		$info_arr['total_none'] = $total_none;
		$info_arr['status'] = wc_get_order_status_name( $order->get_status() );
		$info_arr['only_virtual'] = $only_virtual;

		$html = $this->generate_html( $refund_id, $this->store_info( $vendor_id ), $info_arr, $items, $total_taxs, $discount_arr, $fee_arr, 'refund' );

		$vendor = get_userdata( $vendor_id );
		$emails[] = $vendor->user_email;

		$pdf_file = $this->pdf_invoice . $refund_id . '.pdf';
		$invoice_japan_tcpdf_off = apply_filters( 'invoice_japan_tcpdf_off', false );
		if ( $invoice_japan_tcpdf_off ) {
			if ( apply_filters( 'invoice_japan_pdf_write', $pdf_file, $html, $info_arr ) ) {
				$this->sent_mail( $emails, $pdf_file, $refund_id, $info_arr['name'], $info_arr['create_date'], $info_arr['status'], $vendor_id, 'refund' );
			}
		} elseif ( $this->pdf_write( $pdf_file, $html, $info_arr ) ) {
			$this->sent_mail( $emails, $pdf_file, $refund_id, $info_arr['name'], $info_arr['create_date'], $info_arr['status'], $vendor_id, 'refund' );
		}
	}

	/** ==================================================
	 * Get infomation.
	 *
	 * @param int    $id  order or refund id.
	 * @param object $order  order object.
	 * @param string $flag  flag 'order','refund'.
	 * @return array $info_arr  infomation array.
	 * @since 1.00
	 */
	private function get_info( $id, $order, $flag ) {

		$name = $order->get_billing_last_name() . ' ' . $order->get_billing_first_name();
		$shipping_name = null;
		if ( ! empty( $order->get_shipping_last_name() . $order->get_shipping_first_name() ) ) {
			$shipping_name = $order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name();
		}
		$date = $order->get_date_created()->date( 'Y年m月d日 H時i分' );
		$create_date = '発行日時：' . wp_date( 'Y年m月d日 H時i分' );

		$invoicejapan_set = get_option( 'invoicejapan' );

		$order_bank = null;
		switch ( $flag ) {
			case 'order':
				$title = '請求書';
				$title_money = '金額';
				$title_grand_total = '請求金額（税込み）';
				$order_refund_num = '注文番号：' . $id;
				$order_refund_date = '注文日時：' . $date;
				$order_refund_payment = '支払い方法：' . $order->get_payment_method_title();
				$order_payment_key = $order->get_payment_method();
				if ( 'bankjp' === $order_payment_key || 'postofficebank' === $order_payment_key ) {
					$order_bank_s = array();
					if ( 'bankjp' === $order_payment_key ) {
						$bank_acount = get_option( 'woocommerce_bankjp_accounts' );
						if ( ! empty( $bank_acount ) ) {
							foreach ( $bank_acount as $value ) {
								$order_bank_s[] = '&nbsp;&nbsp;&nbsp;' . $value['bank_name'] . ' ' . $value['bank_branch'] . ' ' . $value['bank_type'] . ' ' . $value['account_number'] . ' ' . $value['account_name'];
							}
						}
					} else if ( 'postofficebank' === $order_payment_key ) {
						$bank_acount = get_option( 'woocommerce_postofficebankjp_accounts' );
						if ( ! empty( $bank_acount ) ) {
							foreach ( $bank_acount as $value ) {
								$order_bank_s[] = '&nbsp;&nbsp;&nbsp;記号:' . $value['bank_symbol'] . ' 口座番号:' . $value['account_number'] . ' ' . $value['account_name'];
							}
						}
					}
					if ( ! empty( $bank_acount ) ) {
						$order_bank = '支払い先<br />' . implode( '<br />', $order_bank_s );
					}
				}
				break;
			case 'refund':
				$title = '払戻明細書';
				$title_money = '払戻金額';
				$title_grand_total = '払戻金額（税込み）';
				$order_refund_num = '払戻番号：' . $id;
				$order_refund_date = null;
				$order_refund_payment = '払戻方法：';
				break;
		}

		$billing_postcode_text = null;
		if ( ! empty( $order->get_billing_postcode() ) ) {
			$billing_postcode_text = '〒' . $order->get_billing_postcode();
		}
		$shipping_postcode_text = null;
		if ( ! empty( $order->get_shipping_postcode() ) ) {
			$shipping_postcode_text = '〒' . $order->get_shipping_postcode();
		}

		$billing_address_text = $this->pref( $order->get_billing_state() ) . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2();
		$shipping_address_text = $this->pref( $order->get_shipping_state() ) . $order->get_shipping_city() . $order->get_shipping_address_1() . $order->get_shipping_address_2();

		$billing_company_text = null;
		if ( ! empty( $order->get_billing_company() ) ) {
			$billing_company_text = $order->get_billing_company();
		}
		$shipping_company_text = null;
		if ( ! empty( $order->get_shipping_company() ) ) {
			$shipping_company_text = $order->get_shipping_company();
		}

		$info_arr = array(
			'name' => $name,
			'date' => $date,
			'create_date' => $create_date,
			'shipping_name' => $shipping_name,
			'billing_postcode_text' => $billing_postcode_text,
			'billing_address_text' => $billing_address_text,
			'billing_company_text' => $billing_company_text,
			'shipping_postcode_text' => $shipping_postcode_text,
			'shipping_address_text' => $shipping_address_text,
			'shipping_company_text' => $shipping_company_text,
			'title_text' => $title,
			'title_money_text' => $title_money,
			'title_grand_total_text' => $title_grand_total,
			'order_refund_num_text' => $order_refund_num,
			'order_refund_date_text' => $order_refund_date,
			'order_refund_payment_text' => $order_refund_payment,
			'order_bank' => $order_bank,
		);

		return $info_arr;
	}

	/** ==================================================
	 * Discount.
	 *
	 * @param object $order  order object.
	 * @param float  $total_reduced  total reduced.
	 * @param float  $tax_reduced  tax reduced.
	 * @param float  $total_normal  total normal.
	 * @param float  $tax_normal  tax normal.
	 * @return array $discount_arr  discount array.
	 * @since 1.00
	 */
	private function discount( $order, $total_reduced, $tax_reduced, $total_normal, $tax_normal ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$discount_arr = array();

		$discount_total = 0;
		$discount_reduced = 0;
		$discount_normal = 0;
		$discount_reduced_total = 0;
		$discount_normal_total = 0;
		if ( 0 < $order->get_discount_total() ) {

			$total = $total_reduced + $tax_reduced + $total_normal + $tax_normal;
			$discount_flag = true;

			$discount_total = $this->round_ceil_floor( $order->get_discount_total() + $order->get_discount_tax(), 0 );

			$discount_reduced = ( $total_reduced + $tax_reduced ) / $total * $discount_total;
			$discount_normal = ( $total_normal + $tax_normal ) / $total * $discount_total;

			$discount_reduced_total = $total_reduced + $tax_reduced - $discount_reduced;
			$discount_total_reduced_tax = $this->round_ceil_floor( $discount_reduced_total * $invoicejapan_set['reduced_tax'] / 108, 0 );

			$shipping = 0;
			$shipping_tax = 0;
			if ( 0 < $order->get_shipping_total() ) {
				$shipping = $order->get_shipping_total() + $order->get_shipping_tax();
				$shipping_tax = $order->get_shipping_tax();
			}

			if ( 0 === $total_normal && 0 < $shipping ) {
				$discount_normal_total = $shipping;
				$discount_total_normal_tax = $shipping_tax;
			} else {
				$discount_normal_total = $total_normal + $tax_normal - $discount_normal + $shipping;
				$discount_total_normal_tax = $this->round_ceil_floor( $discount_normal_total * $invoicejapan_set['normal_tax'] / 110, 0 );
			}

			$discount_arr = array(
				'discount_total' => number_format( $discount_total ) . '円',
				'discount_reduced' => number_format( $discount_reduced ) . '円',
				'discount_normal' => number_format( $discount_normal ) . '円',
				'discount_reduced_total' => number_format( $discount_reduced_total ) . '円',
				'discount_normal_total' => number_format( $discount_normal_total ) . '円',
				'discount_total_reduced_tax' => number_format( $discount_total_reduced_tax ) . '円',
				'discount_total_normal_tax' => number_format( $discount_total_normal_tax ) . '円',
			);
		}

		return $discount_arr;
	}

	/** ==================================================
	 * Round method
	 *
	 * @param float $value  value.
	 * @param int   $precision  precision.
	 * @return float $result  result.
	 * @since 2.03
	 */
	private function round_ceil_floor( $value, $precision ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		if ( 0 < $value ) {
			$method = $invoicejapan_set['rounding'];
			switch ( $method ) {
				case 'round':
					$value = round( $value, $precision );
					break;
				case 'ceil':
					$value = round( $value + 0.5 * pow( 0.1, $precision ), $precision, PHP_ROUND_HALF_DOWN );
					break;
				case 'floor':
					$value = round( $value - 0.5 * pow( 0.1, $precision ), $precision, PHP_ROUND_HALF_UP );
					break;
				default:
					$value = round( $value, $precision );
			}
		}

		return $value;
	}

	/** ==================================================
	 * Fee.
	 *
	 * @param object $order_refund  order,refund object.
	 * @param int    $vendor_id  vendor id.
	 * @return array $fee_arr  fee array.
	 * @since 1.00
	 */
	private function fee( $order_refund, $vendor_id ) {

		$fee_arr = array();

		if ( ! empty( $order_refund->get_items( 'fee' ) ) ) {
			foreach ( $order_refund->get_items( 'fee' ) as $item_id => $item_fee ) {
				$fee_total = abs( $item_fee->get_total() );
				$fee_total_tax = abs( $item_fee->get_total_tax() );
			}

			$invoicejapan_set = get_option( 'invoicejapan' );
			$fee_arr = array(
				'fee_text' => apply_filters( 'invoice_japan_fee_text', $invoicejapan_set['fee_text'], $vendor_id ),
				'fee_total' => number_format( $fee_total ) . '円',
				'fee_tax' => number_format( $fee_total_tax ) . '円',
			);
		}

		return $fee_arr;
	}

	/** ==================================================
	 * Store info.
	 *
	 * @param int $vendor_id  vendor id.
	 * @return array $store_info_arr  store info array.
	 * @since 1.02
	 */
	private function store_info( $vendor_id ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$store_raw_country = get_option( 'woocommerce_default_country' );
		$split_country = explode( ':', $store_raw_country );
		$store_state = $split_country[1];
		$store_address = $this->pref( $store_state ) . get_option( 'woocommerce_store_city' ) . get_option( 'woocommerce_store_address' ) . ' ' . get_option( 'woocommerce_store_address_2' );
		$store_info_arr = array(
			'postcode' => '〒' . apply_filters( 'invoice_japan_store_postcode', get_option( 'woocommerce_store_postcode' ), $vendor_id ),
			'address' => apply_filters( 'invoice_japan_store_address', $store_address, $vendor_id ),
			'add_text' => nl2br( apply_filters( 'invoice_japan_add_text', $invoicejapan_set['store_add_text'], $vendor_id ) ),
			'number' => '登録番号：' . apply_filters( 'invoice_japan_number', $invoicejapan_set['number'], $vendor_id ),
		);

		return $store_info_arr;
	}

	/** ==================================================
	 * Generate html.
	 *
	 * @param int    $id  order_id, refund_id.
	 * @param array  $store_info_arr  store info array.
	 * @param array  $info_arr  infomation.
	 * @param array  $items  name,quantity,total.
	 * @param array  $total_taxs  total,reduced,normal.
	 * @param array  $discount_arr  discount array.
	 * @param array  $fee_arr  fee array.
	 * @param string $flag  flag 'order','refund'.
	 * @since 1.00
	 */
	private function generate_html( $id, $store_info_arr, $info_arr, $items, $total_taxs, $discount_arr, $fee_arr, $flag ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$sp_option = apply_filters( 'invoice_japan_order_generate_template_sp_options', array() );

		$tmps = array(
			'dir' => $this->tmp_dir,
			'url' => $this->tmp_url,
		);

		$template_file = apply_filters( 'invoice_japan_order_generate_template_file', plugin_dir_path( __DIR__ ) . 'template/pdf-invoice-japan-template.php' );

		ob_start();
		include $template_file;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/** ==================================================
	 * Prefecture selected.
	 *
	 * @param string $state_num  State number.
	 * @return string $prefecture  Prefecture.
	 * @since 1.00
	 */
	private function pref( $state_num ) {

		if ( empty( $state_num ) ) {
			return null;
		}

		$prefectures = array(
			'JP01' => '北海道',
			'JP02' => '青森県',
			'JP03' => '岩手県',
			'JP04' => '宮城県',
			'JP05' => '秋田県',
			'JP06' => '山形県',
			'JP07' => '福島県',
			'JP08' => '茨城県',
			'JP09' => '栃木県',
			'JP10' => '群馬県',
			'JP11' => '埼玉県',
			'JP12' => '千葉県',
			'JP13' => '東京都',
			'JP14' => '神奈川県',
			'JP15' => '新潟県',
			'JP16' => '富山県',
			'JP17' => '石川県',
			'JP18' => '福井県',
			'JP19' => '山梨県',
			'JP20' => '長野県',
			'JP21' => '岐阜県',
			'JP22' => '静岡県',
			'JP23' => '愛知県',
			'JP24' => '三重県',
			'JP25' => '滋賀県',
			'JP26' => '京都府',
			'JP27' => '大阪府',
			'JP28' => '兵庫県',
			'JP29' => '奈良県',
			'JP30' => '和歌山県',
			'JP31' => '鳥取県',
			'JP32' => '島根県',
			'JP33' => '岡山県',
			'JP34' => '広島県',
			'JP35' => '山口県',
			'JP36' => '徳島県',
			'JP37' => '香川県',
			'JP38' => '愛媛県',
			'JP39' => '高知県',
			'JP40' => '福岡県',
			'JP41' => '佐賀県',
			'JP42' => '長崎県',
			'JP43' => '熊本県',
			'JP44' => '大分県',
			'JP45' => '宮崎県',
			'JP46' => '鹿児島県',
			'JP47' => '沖縄県',
		);

		$prefecture = $prefectures[ $state_num ];

		return $prefecture;
	}

	/** ==================================================
	 * PDF write.
	 *
	 * @param string $pdf_file  pdf filename.
	 * @param string $html  html.
	 * @param array  $info_arr  infomation.
	 * @return bool
	 * @since 1.00
	 */
	private function pdf_write( $pdf_file, $html, $info_arr ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$pdf_set = array(
			'page_ort' => $invoicejapan_set['page_ort'],
			'page_size' => $invoicejapan_set['page_size'],
			'margin_left' => $invoicejapan_set['margin_left'],
			'margin_top' => $invoicejapan_set['margin_top'],
			'margin_right' => $invoicejapan_set['margin_right'],
			'margin_bottom' => $invoicejapan_set['margin_bottom'],
			'fontsize' => $invoicejapan_set['fontsize'],
			'font' => $invoicejapan_set['font'],
			'fontsize_header' => $invoicejapan_set['fontsize_header'],
			'fontsize_footer' => $invoicejapan_set['fontsize_footer'],
		);
		$pdf_set = apply_filters( 'invoice_japan_pdf_set', $pdf_set, $info_arr['vendor_id'] );

		$font_arr['font'] = $pdf_set['font'];
		$font_arr = apply_filters( 'invoice_japan_pdf_font', $font_arr, $info_arr['vendor_id'] );

		/* PDF Result Paper */
		$pdf = new TCPDF( $pdf_set['page_ort'], 'mm', $pdf_set['page_size'], true, 'UTF-8', false );
		$pdf->SetY( -10 );
		$pdf->SetFont( $font_arr['font'], 'B', $pdf_set['fontsize_footer'] );

		$pdf->setHeaderFont( array( $font_arr['font'], '', $pdf_set['fontsize_header'] ) );
		/* set margins */
		$pdf->SetMargins( $pdf_set['margin_left'], PDF_MARGIN_HEADER + $pdf_set['margin_top'], $pdf_set['margin_right'] );
		$pdf->SetMargins( $pdf_set['margin_left'], $pdf_set['margin_top'], $pdf_set['margin_right'] );
		$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
		$pdf->SetHeaderData( '', 0, '', '', array( 0, 0, 0 ), array( 255, 255, 255 ) );
		/* set font */
		$pdf->SetFont( $font_arr['font'], '', $pdf_set['fontsize'] );
		/* set auto page breaks */
		$pdf->SetAutoPageBreak( true, PDF_MARGIN_FOOTER + $pdf_set['margin_bottom'] );

		$pdf->AddPage();
		$pdf->writeHTML( $html, true, false, true, false, '' );
		$pdf->lastPage();
		/* Output */
		$pdf->Output( $pdf_file, 'F' );

		return true;
	}

	/** ==================================================
	 * Sent email.
	 *
	 * @param array  $emails  email.
	 * @param string $pdf_file  pdf filename.
	 * @param int    $id  order or refund number.
	 * @param string $name  order or refund name.
	 * @param string $date  create date.
	 * @param string $status  order status.
	 * @param int    $vendor_id  vendor id.
	 * @param string $flag  flag 'order','refund'.
	 * @since 1.00
	 */
	private function sent_mail( $emails, $pdf_file, $id, $name, $date, $status, $vendor_id, $flag ) {

		$invoicejapan_set = get_option( 'invoicejapan' );

		$mail_items = array(
			'order'  => array(
				'documents' => '請求書',
				'item' => '注文',
			),
			'refund' => array(
				'documents' => '払戻明細書',
				'item' => '払戻',
			),
		);

		$subject = sprintf( '%1$s ショップ %2$s', get_bloginfo( 'name' ), $mail_items[ $flag ]['documents'] );
		$subject = apply_filters( 'invoice_japan_' . $flag . '_mail_subject', $subject, $vendor_id );
		$mail_name = sprintf( '%1$s 様', $name );
		$mail_name = apply_filters( 'invoice_japan_' . $flag . '_mail_name', $mail_name, $name, $vendor_id );
		$mail_head = sprintf( '%1$s で注文した商品の%2$sを PDF として作成・添付いたしました。', get_bloginfo( 'name' ), $mail_items[ $flag ]['documents'] );
		$mail_head = apply_filters( 'invoice_japan_' . $flag . '_mail_head', $mail_head, $vendor_id );
		$mail_order_status = '注文状況:' . $status;
		$mail_number_date = sprintf( '%1$s番号:%2$d %3$s', $mail_items[ $flag ]['item'], $id, $date );
		$mail_number_date = apply_filters( 'invoice_japan_' . $flag . '_mail_number_date', $mail_number_date, $id, $date, $vendor_id );
		$mail_body = $invoicejapan_set[ $flag . '_mail_body' ];
		$mail_body = apply_filters( 'invoice_japan_' . $flag . '_mail_body', $mail_body, $vendor_id );

		$message = $mail_name . "\r\n\r\n";
		$message .= $mail_head . "\r\n\r\n";
		$message .= $mail_order_status . "\r\n\r\n";
		$message .= $mail_number_date . "\r\n\r\n";
		$message .= $mail_body;

		$headers = array();
		$from_name = wp_specialchars_decode( get_option( 'woocommerce_email_from_name' ), ENT_QUOTES );
		$from_name = apply_filters( 'invoice_japan_' . $flag . '_mail_headers_fromname', $from_name, $vendor_id );
		$from_address = sanitize_email( get_option( 'woocommerce_email_from_address' ) );
		$from_address = apply_filters( 'invoice_japan_' . $flag . '_mail_headers_fromaddress', $from_address, $vendor_id );
		$headers[] = 'From: ' . $from_name . ' <' . $from_address . '>';

		$emails = apply_filters( 'invoice_japan_mail', $emails, $vendor_id );

		$attachements = array(
			$pdf_file,
		);
		$attachements = apply_filters( 'invoice_japan_attache', $attachements, $vendor_id );

		foreach ( $emails as $email ) {
			wp_mail( $email, $subject, $message, $headers, $attachements );
		}

		if ( file_exists( $pdf_file ) ) {
			unlink( $pdf_file );
		}
	}

	/** ==================================================
	 * Order actions.
	 *
	 * @param array  $actions  actions.
	 * @param object $order  order object.
	 * @since 1.30
	 */
	public function order_actions( $actions, $order ) {

		if ( is_array( $actions ) ) {
			$refunds = $order->get_refunds();
			if ( ! empty( $refunds ) ) {
				$actions['send_invoice_japan_refund'] = '払戻明細書を再送信<Invoice Japan>';
			} else {
				$actions['send_invoice_japan_invoice'] = '請求書を再送信<Invoice Japan>';
			}
		}

		return $actions;
	}

	/** ==================================================
	 * Resend invoice by email.
	 *
	 * @param object $order  order object.
	 * @since 1.30
	 */
	public function fired_send_invoice_japan_invoice( $order ) {

		$this->sa_wc_after_order_complete( $order->get_id() );
	}

	/** ==================================================
	 * Resend refund statement by email.
	 *
	 * @param object $order  order object.
	 * @since 1.30
	 */
	public function fired_send_invoice_japan_refund( $order ) {

		$refunds = $order->get_refunds();

		$refund_ids = array();
		/* Get and Loop Over Refund Items */
		foreach ( $refunds as $refund ) {
			$refund_ids[] = $refund->get_id();
		}

		$this->after_refund( $order->get_id(), max( $refund_ids ) );
	}

	/** ==================================================
	 * Order details custom for resend email.
	 *
	 * @param object $order  order object.
	 * @since 1.33
	 */
	public function order_details( $order ) {

		if ( is_user_logged_in() ) {

			$order_id = $order->get_id();

			$status = $order->get_status();

			$refund_id = 0;
			$refunds = $order->get_refunds();
			if ( ! empty( $refunds ) ) {
				$refund_ids = array();
				/* Get and Loop Over Refund Items */
				foreach ( $refunds as $refund ) {
					$refund_ids[] = $refund->get_id();
				}
				$refund_id = max( $refund_ids );
			}

			if ( 'refunded' !== $status ) {
				$gateway_mail_timings = get_option( 'invoicejapan_gateway_mail_timing' );
				if ( $gateway_mail_timings[ $order->get_payment_method() ][ $status ] ) {
					$status = 'resending_invoice';
				} else {
					$status = 'no_resending_invoice';
				}
			}

			$asset_file = include plugin_dir_path( __DIR__ ) . 'guten/dist/resending/invoicejapan-resending.asset.php';

			wp_enqueue_style(
				'invoicejapan_resending',
				plugin_dir_url( __DIR__ ) . 'guten/dist/resending/invoicejapan-resending.css',
				array( 'wp-components' ),
				'1.0.0',
			);

			wp_enqueue_script(
				'invoicejapan_resending',
				plugin_dir_url( __DIR__ ) . 'guten/dist/resending/invoicejapan-resending.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);

			wp_localize_script(
				'invoicejapan_resending',
				'invoicejapan_resending_data',
				array(
					'order_id' => $order_id,
					'refund_id' => $refund_id,
					'status' => $status,
				)
			);

			echo '<div id="invoicejapanresending"></div>';
		}
	}

	/** ==================================================
	 * Register Rest API
	 *
	 * @since 1.00
	 */
	public function register_rest() {

		register_rest_route(
			'rf/invoicejapan-admin_api',
			'/token',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'settings_save' ),
				'permission_callback' => array( $this, 'settings_rest_permission' ),
			)
		);

		register_rest_route(
			'rf/invoicejapan-resending_api',
			'/token',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'resending_api' ),
				'permission_callback' => array( $this, 'resending_rest_permission' ),
			)
		);
	}

	/** ==================================================
	 * Rest Permission for Settings
	 *
	 * @since 1.00
	 */
	public function settings_rest_permission() {

		return current_user_can( 'manage_options' );
	}

	/** ==================================================
	 * Rest Permission for Resending
	 *
	 * @since 1.37
	 */
	public function resending_rest_permission() {

		return current_user_can( 'customer' );
	}

	/** ==================================================
	 * Rest API save for Settings
	 *
	 * @param object $request  changed data.
	 * @since 1.00
	 */
	public function settings_save( $request ) {

		$args = json_decode( $request->get_body(), true );

		$invoicejapan_set = get_option( 'invoicejapan' );

		$invoicejapan_set['reduced_tax'] = intval( $args['reduced_tax'] );
		$invoicejapan_set['normal_tax'] = intval( $args['normal_tax'] );
		$invoicejapan_set['number'] = sanitize_text_field( wp_unslash( $args['number'] ) );
		$invoicejapan_set['store_add_text'] = sanitize_textarea_field( wp_unslash( $args['store_add_text'] ) );
		$invoicejapan_set['page_ort'] = sanitize_text_field( wp_unslash( $args['page_ort'] ) );
		$invoicejapan_set['page_size'] = sanitize_text_field( wp_unslash( $args['page_size'] ) );
		$invoicejapan_set['margin_left'] = intval( $args['margin_left'] );
		$invoicejapan_set['margin_top'] = intval( $args['margin_top'] );
		$invoicejapan_set['margin_right'] = intval( $args['margin_right'] );
		$invoicejapan_set['margin_bottom'] = intval( $args['margin_bottom'] );
		$invoicejapan_set['font'] = sanitize_text_field( wp_unslash( $args['font'] ) );
		$invoicejapan_set['fontsize'] = intval( $args['fontsize'] );
		$invoicejapan_set['fontsize_header'] = intval( $args['fontsize_header'] );
		$invoicejapan_set['fontsize_footer'] = intval( $args['fontsize_footer'] );
		$invoicejapan_set['order_mail_body'] = sanitize_textarea_field( wp_unslash( $args['order_mail_body'] ) );
		$invoicejapan_set['refund_mail_body'] = sanitize_textarea_field( wp_unslash( $args['refund_mail_body'] ) );
		$invoicejapan_set['fee_text'] = sanitize_text_field( wp_unslash( $args['fee_text'] ) );
		$invoicejapan_set['rounding'] = sanitize_text_field( wp_unslash( $args['rounding'] ) );

		update_option( 'invoicejapan', $invoicejapan_set );

		$gateway_mail_timings = filter_var(
			wp_unslash( $args['gateway_mail_timings'] ),
			FILTER_CALLBACK,
			array(
				'options' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
		update_option( 'invoicejapan_gateway_mail_timing', $gateway_mail_timings );

		$gateway_remarks = filter_var(
			wp_unslash( $args['gateway_remarks'] ),
			FILTER_CALLBACK,
			array(
				'options' => function ( $value ) {
					return sanitize_textarea_field( $value );
				},
			)
		);
		update_option( 'invoicejapan_gateway_remarks', $gateway_remarks );

		$gateway_refunds = filter_var(
			wp_unslash( $args['gateway_refunds'] ),
			FILTER_CALLBACK,
			array(
				'options' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
		update_option( 'invoicejapan_gateway_refunds', $gateway_refunds );

		return new WP_REST_Response( $args, 200 );
	}

	/** ==================================================
	 * Rest API save for Resending
	 *
	 * @param object $request  changed data.
	 * @since 1.37
	 */
	public function resending_api( $request ) {

		$args = json_decode( $request->get_body(), true );

		$order_button = absint( $args['order_button'] );
		$refund_button = absint( $args['refund_button'] );

		$order_id = absint( $args['order_id'] );
		$refund_id = absint( $args['refund_id'] );

		if ( $order_button ) {
			$this->sa_wc_after_order_complete( $order_id );
		}

		if ( $refund_button ) {
			$this->after_refund( $order_id, $refund_id );
		}

		return new WP_REST_Response( $args, 200 );
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_option( 'invoicejapan' ) ) {
			$invoicejapan_set = array(
				'reduced_tax' => 8,
				'normal_tax' => 10,
				'number' => null,
				'store_add_text' => null,
				'page_ort' => 'P',
				'page_size' => 'A4',
				'margin_left' => 15,
				'margin_top' => 15,
				'margin_right' => 15,
				'margin_bottom' => 15,
				'fontsize' => 10,
				'font' => 'ipaexg',
				'fontsize_header' => 10,
				'fontsize_footer' => 7,
				'order_mail_body' => null,
				'refund_mail_body' => null,
				'fee_text' => '手数料',
				'rounding' => 'round',
			);
			update_option( 'invoicejapan', $invoicejapan_set );
		} else {
			$invoicejapan_set = get_option( 'invoicejapan' );
			/* @since 1.11 */
			if ( ! array_key_exists( 'order_remarks', $invoicejapan_set ) ) {
				$invoicejapan_set['order_remarks'] = null;
				update_option( 'invoicejapan', $invoicejapan_set );
			}
			/* @since 1.11 */
			if ( ! array_key_exists( 'refund_remarks', $invoicejapan_set ) ) {
				$invoicejapan_set['refund_remarks'] = null;
				update_option( 'invoicejapan', $invoicejapan_set );
			}
			/* @since 1.13 */
			if ( array_key_exists( 'order_remarks', $invoicejapan_set ) ) {
				unset( $invoicejapan_set['order_remarks'] );
			}
			/* @since 1.14 */
			if ( array_key_exists( 'refund_remarks', $invoicejapan_set ) ) {
				unset( $invoicejapan_set['refund_remarks'] );
			}
			if ( array_key_exists( 'refund_payment', $invoicejapan_set ) ) {
				unset( $invoicejapan_set['refund_payment'] );
			}
			/* @since 1.30 */
			if ( ! array_key_exists( 'fee_text', $invoicejapan_set ) ) {
				$invoicejapan_set['fee_text'] = '手数料';
				update_option( 'invoicejapan', $invoicejapan_set );
			}
			/* @since 2.01 */
			if ( array_key_exists( 'address_position', $invoicejapan_set ) ) {
				unset( $invoicejapan_set['address_position'] );
			}
			/* @since 2.03 */
			if ( ! array_key_exists( 'rounding', $invoicejapan_set ) ) {
				$invoicejapan_set['rounding'] = 'round';
				update_option( 'invoicejapan', $invoicejapan_set );
			}
		}

		list( $gateway_txt, $gateway_mail_timings, $gateway_remarks, $gateway_refunds ) = $this->init_gateway_mail_timing_remarks_refunds();
		if ( ! get_option( 'invoicejapan_gateway_mail_timing' ) ) {
			update_option( 'invoicejapan_gateway_mail_timing', $gateway_mail_timings );
		}
		if ( ! get_option( 'invoicejapan_gateway_remarks' ) ) {
			update_option( 'invoicejapan_gateway_remarks', $gateway_remarks );
		} else {
			/* @since 1.14 */
			$gateway_remarks = get_option( 'invoicejapan_gateway_remarks', $gateway_remarks );
			$tmp_arr = array_keys( $gateway_remarks );
			if ( ! is_array( $gateway_remarks[ $tmp_arr[0] ] ) ) {
				foreach ( $gateway_remarks as $key => $value ) {
					$new_gateway_remarks[ $key ]['order'] = $value;
					$new_gateway_remarks[ $key ]['refund'] = null;
				}
				update_option( 'invoicejapan_gateway_remarks', $new_gateway_remarks );
			}
		}
		/* @since 1.14 */
		if ( ! get_option( 'invoicejapan_gateway_refunds' ) ) {
			update_option( 'invoicejapan_gateway_refunds', $gateway_refunds );
		}
		/* @since 1.21 */
		if ( 'kozgopromedium' === $invoicejapan_set['font'] ) {
			$invoicejapan_set['font'] = 'ipaexg';
			update_option( 'invoicejapan', $invoicejapan_set );
		}
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'pdf-invoice-japan-for-woocommerce/invoicejapan.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=invoicejapan_set' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_submenu_page(
			'woocommerce',
			'Invoice Japan',
			'Invoice Japan',
			'manage_woocommerce',
			'invoicejapan_set',
			array( $this, 'plugin_options' )
		);
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		echo '<div id="invoicejapanadmin"></div>';
		echo '<div id="invoicejapanaddonadmin"></div>';
	}

	/** ==================================================
	 * Load scripts
	 *
	 * @param string $hook_suffix  hook_suffix.
	 * @since 1.00
	 */
	public function admin_scripts( $hook_suffix ) {

		if ( 'woocommerce_page_invoicejapan_set' !== $hook_suffix ) {
			return;
		}

		$asset_file = include plugin_dir_path( __DIR__ ) . 'guten/dist/invoicejapan-admin.asset.php';

		wp_enqueue_style(
			'invoicejapan',
			plugin_dir_url( __DIR__ ) . 'guten/dist/invoicejapan-admin.css',
			array( 'wp-components' ),
			'1.0.0',
		);

		wp_enqueue_script(
			'invoicejapan',
			plugin_dir_url( __DIR__ ) . 'guten/dist/invoicejapan-admin.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$invoicejapan_set = get_option( 'invoicejapan' );

		list( $gateway_txt, $gateway_mail_timings, $gateway_remarks, $gateway_refunds ) = $this->init_gateway_mail_timing_remarks_refunds();
		$gateway_mail_timings = get_option( 'invoicejapan_gateway_mail_timing', $gateway_mail_timings );
		$gateway_remarks = get_option( 'invoicejapan_gateway_remarks', $gateway_remarks );
		$gateway_refunds = get_option( 'invoicejapan_gateway_refunds', $gateway_refunds );

		$diff_add = array_diff( array_keys( $gateway_txt ), array_keys( $gateway_mail_timings ) );
		$diff_del = array_diff( array_keys( $gateway_mail_timings ), array_keys( $gateway_txt ) );
		if ( ! empty( $diff_add ) ) {
			foreach ( $diff_add as $value ) {
				$gateway_mail_timings[ $value ] = array(
					'pending' => false,
					'processing' => true,
					'on-hold' => false,
					'completed' => true,
				);
				$gateway_remarks[ $value ]['order'] = null;
				$gateway_remarks[ $value ]['refund'] = null;
				$gateway_refunds[ $value ] = null;
			}
		}
		if ( ! empty( $diff_del ) ) {
			foreach ( $diff_del as $value ) {
				unset( $gateway_mail_timings[ $value ] );
				unset( $gateway_remarks[ $value ] );
				unset( $gateway_refunds[ $value ] );
			}
		}

		wp_localize_script(
			'invoicejapan',
			'invoicejapan_data',
			array(
				'reduced_tax' => $invoicejapan_set['reduced_tax'],
				'normal_tax' => $invoicejapan_set['normal_tax'],
				'number' => $invoicejapan_set['number'],
				'store_add_text' => $invoicejapan_set['store_add_text'],
				'gateway_remarks' => wp_json_encode( $gateway_remarks, JSON_UNESCAPED_SLASHES ),
				'page_ort' => $invoicejapan_set['page_ort'],
				'page_size' => $invoicejapan_set['page_size'],
				'margin_left' => $invoicejapan_set['margin_left'],
				'margin_top' => $invoicejapan_set['margin_top'],
				'margin_right' => $invoicejapan_set['margin_right'],
				'margin_bottom' => $invoicejapan_set['margin_bottom'],
				'fontsize' => $invoicejapan_set['fontsize'],
				'font' => $invoicejapan_set['font'],
				'fontsize_header' => $invoicejapan_set['fontsize_header'],
				'fontsize_footer' => $invoicejapan_set['fontsize_footer'],
				'order_mail_body' => $invoicejapan_set['order_mail_body'],
				'refund_mail_body' => $invoicejapan_set['refund_mail_body'],
				'fee_text' => $invoicejapan_set['fee_text'],
				'rounding' => $invoicejapan_set['rounding'],
				'gateway_mail_timings' => wp_json_encode( $gateway_mail_timings, JSON_UNESCAPED_SLASHES ),
				'gateway_txt' => wp_json_encode( $gateway_txt, JSON_UNESCAPED_SLASHES ),
				'gateway_refunds' => wp_json_encode( $gateway_refunds, JSON_UNESCAPED_SLASHES ),
				'addon' => boolval( class_exists( 'InvoiceJapanAddOn' ) ),
				'addon_url' => esc_url( 'https://shop-jp.riverforest-wp.info/product/pdf-invoice-japan-for-woocommerce-add-on/' ),
			)
		);

		$this->credit_gutenberg( 'invoicejapan' );
	}

	/** ==================================================
	 * Initialaize for Gateway and Mail timings and Remarks and Refunds
	 *
	 * @since 1.07
	 */
	private function init_gateway_mail_timing_remarks_refunds() {
		$gateway_txt = array();
		$gateway_mail_timings = array();
		$gateway_remarks = array();
		$gateway_refunds = array();
		if ( class_exists( 'WC_Payment_Gateways' ) ) {
			$payment_gateways = new WC_Payment_Gateways();
			$gateways = $payment_gateways->payment_gateways();
			if ( $gateways ) {
				foreach ( $gateways as $gateway ) {
					if ( 'yes' === $gateway->enabled ) {
						$gateway_txt[ $gateway->id ] = $gateway->title;
						foreach ( $this->order_statuses as $value ) {
							$gateway_mail_timings[ $gateway->id ] = array(
								'pending' => false,
								'processing' => true,
								'on-hold' => false,
								'completed' => true,
							);
						}
						/* @since 1.13 */
						$gateway_remarks[ $gateway->id ] = null;
						$gateway_remarks[ $gateway->id ]['order'] = null;
						$gateway_remarks[ $gateway->id ]['refund'] = null;
						/* @since 1.14 */
						$gateway_refunds[ $gateway->id ] = null;
					}
				}
			}
		}
		return array( $gateway_txt, $gateway_mail_timings, $gateway_remarks, $gateway_refunds );
	}

	/** ==================================================
	 * Credit for Gutenberg
	 *
	 * @param string $handle  handle.
	 * @since 1.00
	 */
	private function credit_gutenberg( $handle ) {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}

		wp_localize_script(
			$handle,
			'credit',
			array(
				'links'          => 'このプラグインの各種リンク',
				'plugin_version' => 'バージョン: ' . $plugin_ver_num,
				'faq'            => sprintf( 'https://ja.wordpress.org/plugins/%s/faq', $slug ),
				'support'        => 'https://wordpress.org/support/plugin/' . $slug,
				'review'         => 'https://wordpress.org/support/view/plugin-reviews/' . $slug,
				'translate'      => 'https://translate.wordpress.org/projects/wp-plugins/' . $slug,
				'translate_text' => sprintf( '%sの翻訳', $plugin_name ),
				'facebook'       => 'https://www.facebook.com/katsushikawamori/',
				'twitter'        => 'https://twitter.com/dodesyo312',
				'youtube'        => 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w',
				'donate'         => 'https://shop-jp.riverforest-wp.info/donate/',
				'donate_text'    => '私はプラグインの開発とサポートを継続させるために寄付を必要としています。',
				'donate_button'  => 'このプラグインに寄付 &#187;',
			)
		);
	}
}
