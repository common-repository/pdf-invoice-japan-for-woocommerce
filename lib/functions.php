<?php
/**
 * Functions file for Invoice Japan
 *
 * @package PDF Invoice Japan for WooCommerce
 */

/** ==================================================
 * Generate html for Billing Address.
 *
 * @param array $info_arr  infomation.
 * @since 1.10
 */
function invoice_japan_billing_address_html( $info_arr ) {

	if ( ! empty( $info_arr['billing_address_text'] ) ) {
		?>請求先住所<br />
		<?php
	}
	if ( ! empty( $info_arr['billing_postcode_text'] ) ) {
		echo esc_html( $info_arr['billing_postcode_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['billing_address_text'] ) ) {
		echo esc_html( $info_arr['billing_address_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['billing_company_text'] ) ) {
		echo esc_html( $info_arr['billing_company_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['billing_address_text'] ) ) {
		echo esc_html( $info_arr['name'] );
		?>
		 様
		<?php
	}
}

/** ==================================================
 * Generate html for Shipping Address.
 *
 * @param array $info_arr  infomation.
 * @since 1.10
 */
function invoice_japan_shipping_address_html( $info_arr ) {

	if ( ! empty( $info_arr['shipping_address_text'] ) ) {
		?>
		配送先住所<br />
		<?php
	}
	if ( ! empty( $info_arr['shipping_postcode_text'] ) ) {
		echo esc_html( $info_arr['shipping_postcode_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['shipping_address_text'] ) ) {
		echo esc_html( $info_arr['shipping_address_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['shipping_company_text'] ) ) {
		echo esc_html( $info_arr['shipping_company_text'] );
		?>
		<br />
		<?php
	}
	if ( ! empty( $info_arr['shipping_name'] ) ) {
		echo esc_html( $info_arr['shipping_name'] );
		?>
		 様
		<?php
	}
}

/** ==================================================
 * Generate html for Shipping.
 *
 * @param array $total_taxs  total,reduced,normal.
 * @param int   $normal_tax  Normal tax.
 * @param bool  $virtual  virtual product.
 * @param bool  $flag  Using table tags.
 * @since 1.35
 */
function invoice_japan_shipping_html( $total_taxs, $normal_tax, $virtual, $flag ) {

	if ( ! $virtual ) {
		if ( $flag ) {
			?>
			<tr class="shipping_tr">
			<td class="shipping_td">
			<?php echo esc_html( '送料:' . $total_taxs['shipping_total'] . ' 消費税:' . $total_taxs['shipping_tax'] ); ?>
			<br />
			<?php echo esc_html( $normal_tax . '%対象:' . $total_taxs['shipping_total'] . ' 消費税:' . $total_taxs['shipping_tax'] ); ?>
			</td>
			</tr>
			<?php
		} else {
			echo esc_html( '送料:' . $total_taxs['shipping_total'] . ' 消費税:' . $total_taxs['shipping_tax'] );
			?>
			<br />
			<?php
			echo esc_html( $normal_tax . '%対象:' . $total_taxs['shipping_total'] . ' 消費税:' . $total_taxs['shipping_tax'] );
		}
	}
}

/** ==================================================
 * Generate html for Remarks.
 *
 * @param string $remark  Remark.
 * @param bool   $flag  Using table tags.
 * @since 1.12
 */
function invoice_japan_remarks_html( $remark, $flag ) {

	if ( ! empty( $remark ) ) {
		if ( $flag ) {
			?>
			<table border="1" cellspacing="0" cellpadding="5">
				<tr>
				<td class="remarks">
				<div><strong>備考</strong></div>
				<?php echo esc_html( nl2br( $remark ) ); ?>
				</td>
				</tr>
			</table>
			<?php
		} else {
			?>
			<div><strong>備考</strong></div>
			<?php
			echo esc_html( nl2br( $remark ) );
		}
	}
}

/** ==================================================
 * Generate html for Items.
 *
 * @param array  $items  Items.
 * @param array  $info_arr  infomation.
 * @param string $flag  Flag.
 * @since 2.01
 */
function invoice_japan_items_html( $items, $info_arr, $flag ) {

	?>
	<tr>
	<?php
	if ( 'refund' === $flag ) {
		?>
		<td class="main_table_header_refund_date">日付</td>
		<td class="main_table_header_refund_name">品名</td>
		<td class="main_table_header_quantity">数量</td>
		<td class="main_table_header_total"><?php echo esc_html( $info_arr['title_money_text'] ); ?></td><!-- 払戻金額 -->
		<?php
	} else {
		?>
		<td class="main_table_header_name">品名</td>
		<td class="main_table_header_quantity">数量</td>
		<td class="main_table_header_total"><?php echo esc_html( $info_arr['title_money_text'] ); ?></td><!-- 金額 -->
		<?php
	}
	?>
	</tr>
	<?php
	foreach ( $items as $key => $value ) {
		?>
		<tr>
		<?php
		if ( 'refund' === $flag ) {
			?>
			<td class="main_table_td_refund_date"><?php echo esc_html( $value['date'] ); ?></td><!-- 払戻日付 -->
			<?php
		}
		?>
		<td class="main_table_td_name"><?php echo esc_html( $value['name'] ); ?></td><!-- 品名 -->
		<td class="main_table_td_quantity"><?php echo esc_html( $value['quantity'] ); ?></td><!-- 数量 -->
		<td class="main_table_td_total"><?php echo esc_html( $value['total'] ); ?></td><!-- 金額 -->
		</tr>
		<?php
	}
}

/** ==================================================
 * Generate html for Order banks.
 *
 * @param string $order_bank  Order banks.
 * @since 2.01
 */
function invoice_japan_order_banks_html( $order_bank ) {

	$allowed_html = array(
		'br' => array(),
	);

	if ( ! empty( $order_bank ) ) {
		echo wp_kses( $order_bank, $allowed_html );
	}
}

/** ==================================================
 * Generate html for Store add text.
 *
 * @param string $store_add_text  Store add text.
 * @since 2.01
 */
function invoice_japan_add_text_html( $store_add_text ) {

	if ( ! empty( $store_add_text ) ) {
		echo esc_html( $store_add_text );
		?>
		<br />
		<?php
	}
}

/** ==================================================
 * Generate html for Totals.
 *
 * @param array $discount_arr  Discount array.
 * @param array $total_taxs  Total and Tax.
 * @param int   $reduced_tax  Reduced Tax.
 * @param int   $normal_tax  Normal Tax.
 * @param int   $total_none  Total non tax.
 * @since 2.01
 */
function invoice_japan_totals_html( $discount_arr, $total_taxs, $reduced_tax, $normal_tax, $total_none ) {

	$sum_text = '合計';
	if ( ! empty( $discount_arr ) ) {
		$sum_text = '小計';
	} elseif ( '0円' !== $total_taxs['shipping_total'] ) {
		$sum_text = '小計';
	}

	echo esc_html( $sum_text . ':' . $total_taxs['total'] . ' 消費税:' . $total_taxs['total_tax'] );
	?>
	<br />
	<?php echo esc_html( $reduced_tax . '%対象:' . $total_taxs['reduced'] . ' 消費税:' . $total_taxs['reduced_tax'] ); ?><br />
	<?php
	echo esc_html( $normal_tax . '%対象:' . $total_taxs['normal'] . ' 消費税:' . $total_taxs['normal_tax'] );

	if ( 0 < $total_none ) {
		?>
		<br />不課税対象:
		<?php
		echo esc_html( $total_taxs['none'] );
	}
}

/** ==================================================
 * Generate html for Fee.
 *
 * @param array $fee_arr  Fee array.
 * @param int   $normal_tax  Normal Tax.
 * @param bool  $flag  Using table tags.
 * @since 2.01
 */
function invoice_japan_fee_html( $fee_arr, $normal_tax, $flag ) {

	if ( ! empty( $fee_arr ) ) {
		if ( $flag ) {
			?>
			<tr>
			<td class="fee">
			<?php
			if ( '0円' === $fee_arr['fee_tax'] ) {
				echo esc_html( $fee_arr['fee_text'] . '(非課税):' . $fee_arr['fee_total'] );
			} else {
				echo esc_html( $fee_arr['fee_text'] . ':' . $fee_arr['fee_total'] . ' 消費税:' . $fee_arr['fee_tax'] );
				?>
				<br />
				<?php
				echo esc_html( $normal_tax . '%対象:' . $fee_arr['fee_total'] . ' 消費税:' . $fee_arr['fee_tax'] );
			}
			?>
			</td>
			</tr>
			<?php
		} elseif ( '0円' === $fee_arr['fee_tax'] ) {
				echo esc_html( $fee_arr['fee_text'] . '(非課税):' . $fee_arr['fee_total'] );
		} else {
			echo esc_html( $fee_arr['fee_text'] . ':' . $fee_arr['fee_total'] . ' 消費税:' . $fee_arr['fee_tax'] );
			?>
				<br />
			<?php
			echo esc_html( $normal_tax . '%対象:' . $fee_arr['fee_total'] . ' 消費税:' . $fee_arr['fee_tax'] );
		}
	}
}

/** ==================================================
 * Generate html for Total none.
 *
 * @param int    $total_none  None Tax.
 * @param string $blank  blank.
 * @since 2.01
 */
function invoice_japan_total_none_html( $total_none, $blank ) {

	if ( 0 < $total_none ) {
		echo esc_html( $blank );
		?>
		** 不課税対象
		<?php
	}
}

/** ==================================================
 * Generate html for Discount Total.
 *
 * @param string $discount_total  Discount total.
 * @param bool   $flag  flag.
 * @since 2.01
 */
function invoice_japan_discount_total_html( $discount_total, $flag ) {
	if ( 'refund' === $flag ) {
		?>
		*** 
		<?php
	}
	echo esc_html( '割引:' . $discount_total );
}
