<?php
/**
 * PDF Invoice Japan for WooCommerce Template File
 *
 * @package WordPress
 * @subpackage PDF Invoice Japan for WooCommerce
 * @since PDF Invoice Japan for WooCommerce 2.00
 */

?>
<style>
/* メインテーブルのヘッダー */
.main_table_header_refund_date {
/* 払戻明細書の日付 */
  width: 20%;
  text-align: center;
  font-weight: bold;
}
.main_table_header_refund_name {
/* 払戻明細書の品名 */
  width: 55%;
  text-align: center;
  font-weight: bold;
}
.main_table_header_name {
/* 品名 */
  width: 75%;
  text-align: center;
  font-weight: bold;
}
.main_table_header_quantity {
/* 数量 */
  width: 10%;
  text-align: center;
  font-weight: bold;
}
.main_table_header_total {
/* 合計 */
  width: 15%;
  text-align: center;
  font-weight: bold;
}

/* メインテーブル */
.main_table_td_refund_date {
/* 払戻明細書の日付 */
  text-align: center;
}
.main_table_td_name {
/* 品名 */
  text-align: left;
}
.main_table_td_quantity {
/* 数量 */
  text-align: center;
}
.main_table_td_total {
/* 合計 */
  text-align: right;
}

/* 送料 */
.shipping_tr {
}
.shipping_td {
  text-align: right;
}

/* 手数料 */
.fee {
  text-align: right;
}

/* 請求先住所 */
.billing_address {
  text-align: left;
}

/*配送先住所 */
.shipping_address {
  text-align: left;
}

/* 備考 */
.remarks {
  text-align: left;
}

/* 注釈 */
.comment {
  text-align: right;
}
</style>

<h1 style="text-align: center;"><?php echo esc_html( $info_arr['title_text'] ); ?></h1><!-- タイトル（請求書、払戻明細書） -->

<!-- ヘッダー -->
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td></td>
		<td style="text-align: right;"><?php echo esc_html( $info_arr['create_date'] ); ?></td><!-- 発行日時 -->
	</tr>

	<tr>
		<td></td>
		<td></td>
	</tr>

	<tr>
		<td><?php echo esc_html( $info_arr['name'] ); ?> 様</td><!-- 氏名 -->
		<td style="text-align: right;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></td><!-- ショップサイト名 -->
	</tr>

	<tr>
		<td></td>
		<td style="text-align: right;"><?php echo esc_html( $store_info_arr['postcode'] ); ?></td><!-- 店舗郵便番号 -->
	</tr>

	<tr>
		<td>
			<?php echo esc_html( $info_arr['title_grand_total_text'] ); ?><!-- 請求金額（税込み）、払戻金額（税込み） -->
			<u><?php echo esc_html( $total_taxs['grand_total'] ); ?></u><!-- 請求金額 -->
		</td>
		<td style="text-align: right;"><?php echo esc_html( $store_info_arr['address'] ); ?></td><!-- 店舗住所 -->
	</tr>

	<tr>
		<td>
			<?php echo esc_html( $info_arr['order_refund_num_text'] ); ?><br /><!-- 注文番号：$d、払戻番号：$d -->
			<?php echo esc_html( $info_arr['order_refund_date_text'] ); ?><br /><!-- 注文日時：$s、払戻日時：$s -->
			<?php echo esc_html( $info_arr['order_refund_payment_text'] ); ?><br /><!-- 支払い方法、払戻方法：$s -->
			<?php invoice_japan_order_banks_html( $info_arr['order_bank'] ); ?><!-- 支払い先( Japanized for WooCommerce の「銀行振込 (日本国内向け)」「郵便振替」の口座詳細) -->
		</td>
		<td style="text-align: right;">
			<?php invoice_japan_add_text_html( $store_info_arr['add_text'] ); ?><!-- 店舗の付記情報 -->
			<?php echo esc_html( $store_info_arr['number'] ); ?><!-- 登録番号：T************* -->
			<br />
		</td>
	</tr>
</table>

<!-- メインの表（明細） -->
<table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000">
<?php invoice_japan_items_html( $items, $info_arr, $flag ); ?>
</table>

<!-- 小計、手数料、値引き、送料 -->
<table border="1" cellspacing="0" cellpadding="5">
<tr>

<!-- 小計 -->
<td style="text-align: right;">
<?php invoice_japan_totals_html( $discount_arr, $total_taxs, $invoicejapan_set['reduced_tax'], $invoicejapan_set['normal_tax'], $info_arr['total_none'] ); ?>
<!--
	購入品の小計額、購入品の小計税額
	軽減税率対象購入品の小計額、軽減税率対象購入品の小計税額
	標準税率対象購入品の小計額、標準税率対象購入品の小計税額
	不課税対象品合計
-->
</td>
</tr>

<!-- 手数料 -->
<?php invoice_japan_fee_html( $fee_arr, $invoicejapan_set['normal_tax'], true ); ?>
<!--
	非課税対象の手数料
	手数料合計、手数料消費税
	標準税率対象の手数料、標準税率対象の手数料税額
-->

<?php
if ( ! empty( $discount_arr ) ) { /* 値引きがある場合 */
	?>
	<tr>
		<td style="text-align: right;">
			<?php invoice_japan_discount_total_html( $discount_arr['discount_total'], $flag ); ?><br /><!-- 値引き金額合計 -->
			<?php echo esc_html( $invoicejapan_set['reduced_tax'] . '%対象:' . $discount_arr['discount_reduced'] ); ?><br /><!--  軽減税の値引き金額 -->
			<?php echo esc_html( $invoicejapan_set['normal_tax'] . '%対象:' . $discount_arr['discount_normal'] ); ?><!-- 標準税の値引き金額 -->
		</td>
	</tr>
	<?php invoice_japan_shipping_html( $total_taxs, $invoicejapan_set['normal_tax'], $info_arr['only_virtual'], true ); ?><!-- 送料と税額 -->
	<tr>
		<td style="text-align: right;">
			<?php echo esc_html( '合計:' . $total_taxs['grand_total'] ) . '（税込み）'; ?><br /><!-- 請求金額 -->
			<?php echo esc_html( $invoicejapan_set['reduced_tax'] . '%対象:' . $discount_arr['discount_reduced_total'] . '（税込み） 消費税:' . $discount_arr['discount_total_reduced_tax'] ); ?><br /><!-- 値引き後の軽減税率対象品税込み合計、税込み価格に対する軽減税( 8/108 ) -->
			<?php echo esc_html( $invoicejapan_set['normal_tax'] . '%対象:' . $discount_arr['discount_normal_total'] . '（税込み） 消費税:' . $discount_arr['discount_total_normal_tax'] ); ?><!-- 値引き後の標準税率対象品税込み合計、税込み価格に対する標準税( 10/110 ) -->
		</td>
	</tr>
	<?php
} else {
	/* 送料と税額 */
	invoice_japan_shipping_html( $total_taxs, $invoicejapan_set['normal_tax'], $info_arr['only_virtual'], true );
}
?>
</table>

<!-- 注釈 -->
<div class="comment">
* 軽減税率対象
<?php invoice_japan_total_none_html( $info_arr['total_none'], '&nbsp;&nbsp;&nbsp;&nbsp;' ); ?><!-- /* 不課税対象注釈 */ 引数：不課税対象品合計, 空白 -->
</div>

<!-- 請求先住所、配送先住所 -->
<table border="0" cellspacing="0" cellpadding="5">
	<tr>
		<td class="billing_address">
		<?php invoice_japan_billing_address_html( $info_arr ); ?><!-- /* 請求先住所 */ 引数：インフォメーション -->
		</td>
		<td class="shipping_address">
		<?php invoice_japan_shipping_address_html( $info_arr ); ?><!-- /* 配送先住所 */ 引数：インフォメーション -->
		</td>
	</tr>
</table>

<!-- 備考 -->
<?php invoice_japan_remarks_html( $info_arr['remark'], true ); ?><!-- /* 備考 */ 引数：備考, table の利用 -->
