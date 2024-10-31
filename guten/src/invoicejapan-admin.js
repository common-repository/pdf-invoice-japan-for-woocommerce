import './invoicejapan-admin.scss';

import apiFetch from '@wordpress/api-fetch';

import { SelectControl, CheckboxControl, RadioControl, TextControl, TextareaControl } from '@wordpress/components';

import NumericInput from 'react-numeric-input';

import {
	render,
	useState,
	useEffect
} from '@wordpress/element';

import Credit from './components/credit';

const InvoicejapanAdmin = () => {

	const gateway_mail_timings = JSON.parse( invoicejapan_data.gateway_mail_timings );
	const gateway_txt = JSON.parse( invoicejapan_data.gateway_txt );
	const gateway_remarks = JSON.parse( invoicejapan_data.gateway_remarks );
	const gateway_refunds = JSON.parse( invoicejapan_data.gateway_refunds );

	const [ currentReducedtax, updatecurrentReducedtax ] = useState( parseInt( invoicejapan_data.reduced_tax ) );
	const [ currentNormaltax, updatecurrentNormaltax ] = useState( parseInt( invoicejapan_data.normal_tax ) );
	const [ currentNumber, updatecurrentNumber ] = useState( invoicejapan_data.number );
	const [ currentStoreaddtext, updatecurrentStoreaddtext ] = useState( invoicejapan_data.store_add_text );
	const [ currentPageort, updatecurrentPageort ] = useState( invoicejapan_data.page_ort );
	const [ currentPagesize, updatecurrentPagesize ] = useState( invoicejapan_data.page_size );
	const [ currentMarginleft, updatecurrentMarginleft ] = useState( parseInt( invoicejapan_data.margin_left ) );
	const [ currentMargintop, updatecurrentMargintop ] = useState( parseInt( invoicejapan_data.margin_top ) );
	const [ currentMarginright, updatecurrentMarginright ] = useState( parseInt( invoicejapan_data.margin_right ) );
	const [ currentMarginbottom, updatecurrentMarginbottom ] = useState( parseInt( invoicejapan_data.margin_bottom ) );
	const [ currentFontsize, updatecurrentFontsize ] = useState( parseInt( invoicejapan_data.fontsize ) );
	const [ currentFont, updatecurrentFont ] = useState( invoicejapan_data.font );
	const [ currentFontsizeheader, updatecurrentFontsizeheader ] = useState( parseInt( invoicejapan_data.fontsize_header ) );
	const [ currentFontsizefooter, updatecurrentFontsizefooter ] = useState( parseInt( invoicejapan_data.fontsize_footer ) );
	const [ currentOrdermailbody, updatecurrentOrdermailbody ] = useState( invoicejapan_data.order_mail_body );
	const [ currentRefundmailbody, updatecurrentRefundmailbody ] = useState( invoicejapan_data.refund_mail_body );
	const [ currentFeetext, updatecurrentFeetext ] = useState( invoicejapan_data.fee_text );
	const [ currentRounding, updatecurrentRounding ] = useState( invoicejapan_data.rounding );

	const [ currentGatewaymailtiming, updatecurrentGatewaymailtiming ] = useState( gateway_mail_timings );
	const [ currentGatewayremarks, updatecurrentGatewayremarks ] = useState( gateway_remarks );
	const [ currentGatewayrefunds, updatecurrentGatewayrefunds ] = useState( gateway_refunds );

	useEffect( () => {
		apiFetch( {
			path: 'rf/invoicejapan-admin_api/token',
			method: 'POST',
			data: {
				reduced_tax: currentReducedtax,
				normal_tax: currentNormaltax,
				number: currentNumber,
				store_add_text: currentStoreaddtext,
				gateway_remarks: currentGatewayremarks,
				page_ort: currentPageort,
				page_size: currentPagesize,
				margin_left: currentMarginleft,
				margin_top: currentMargintop,
				margin_right: currentMarginright,
				margin_bottom: currentMarginbottom,
				fontsize: currentFontsize,
				font: currentFont,
				fontsize_header: currentFontsizeheader,
				fontsize_footer: currentFontsizefooter,
				order_mail_body: currentOrdermailbody,
				refund_mail_body: currentRefundmailbody,
				fee_text: currentFeetext,
				rounding: currentRounding,
				gateway_refunds: currentGatewayrefunds,
				gateway_mail_timings: currentGatewaymailtiming,
			}
		} ).then( ( response ) => {
			//console.log( response );
		} );
	}, [ currentReducedtax, currentNormaltax, currentNumber, currentStoreaddtext, currentGatewayremarks, currentPageort, currentPagesize, currentMarginleft, currentMargintop, currentMarginright, currentMarginbottom, currentFontsize, currentFont, currentFontsizeheader, currentFontsizefooter, currentOrdermailbody, currentRefundmailbody, currentFeetext, currentRounding, currentGatewayrefunds, currentGatewaymailtiming ] );

	const items_tax = [];
	if( typeof currentReducedtax !== 'undefined' ) {
		items_tax.push(
			<div className="boxRowContainer">
				<strong>軽減税率</strong>
				&nbsp;&nbsp;&nbsp;
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 8 }
					max = { 30 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentReducedtax ) }
					onChange = { ( value ) => updatecurrentReducedtax( value ) }
				/>
				&nbsp;%
			</div>
		);
	}
	if( typeof currentNormaltax !== 'undefined' ) {
		items_tax.push(
			<div className="boxRowContainer">
				<strong>標準税率</strong>
				&nbsp;&nbsp;&nbsp;
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 10 }
					max = { 30 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentNormaltax ) }
					onChange = { ( value ) => updatecurrentNormaltax( value ) }
				/>
				&nbsp;%
			</div>
		);
	}

	const items_rounding = [];
	if( typeof currentRounding !== 'undefined' ) {
		items_rounding.push(
			<div className="boxRowContainer">
				&nbsp;&nbsp;&nbsp;
				<RadioControl
					selected = { currentRounding }
					options={ [
						{ label: '四捨五入', value: 'round' },
						{ label: '切り上げ', value: 'ceil' },
						{ label: '切り捨て', value: 'floor' },
					] }
					onChange = { ( value ) => updatecurrentRounding( value ) }
				/>
			</div>
		);
	}

	const items_gateway_remarks = [];
	if( typeof currentGatewayremarks !== 'undefined' ) {
		Object.keys( currentGatewayremarks ).map(
			( key1 ) => {
				//console.log( key1 );
				if( currentGatewayremarks.hasOwnProperty( key1 ) ) {
					let remark = [];
					Object.keys( currentGatewayremarks[ key1 ] ).map(
						( key2 ) => {
							remark.push(
								<td>
									<TextareaControl
										cols={ 60 }
										value={ currentGatewayremarks[ key1 ][ key2 ] }
										onChange={ ( value ) =>
											{
												currentGatewayremarks[ key1 ][ key2 ] = value;
												let data = Object.assign( {}, currentGatewayremarks );
												updatecurrentGatewayremarks( data );
											}
										}
									/>
								</td>
							);
						}
					);
					items_gateway_remarks.push(
						<tr>
						<td align="right">
						{ gateway_txt[ key1 ] }
						</td>
						{ remark }
						</tr>
					);
				}
			}
		);
	}

	const items_gateway_refunds = [];
	if( typeof currentGatewayrefunds !== 'undefined' ) {
		Object.keys( currentGatewayrefunds ).map(
			( key ) => {
				//console.log( key );
				if( currentGatewayrefunds.hasOwnProperty( key ) ) {
					let refund = [];
					refund.push(
						<td>
							<TextControl
								size="40"
								value={ currentGatewayrefunds[ key ] }
								onChange={ ( value ) =>
									{
										currentGatewayrefunds[ key ] = value;
										let data = Object.assign( {}, currentGatewayrefunds );
										updatecurrentGatewayrefunds( data );
									}
								}
							/>
						</td>
					);
					items_gateway_refunds.push(
						<tr>
						<td align="right">
						{ gateway_txt[ key ] }
						</td>
						{ refund }
						</tr>
					);
				}
			}
		);
	}

	const items = [];
	if( typeof currentNumber !== 'undefined' &&
		typeof currentStoreaddtext !== 'undefined' &&
		typeof currentFeetext !== 'undefined' ) {
		items.push(
			<>
				<div className="boxRowContainer">
					<strong>登録番号</strong>
					&nbsp;&nbsp;&nbsp;
					<TextControl
						value={ currentNumber }
						onChange={ ( value ) => updatecurrentNumber( value ) }
					/>
				</div>
				<div className="boxRowContainer">
					<strong>店舗の付記情報</strong>
					&nbsp;&nbsp;&nbsp;
					<TextareaControl
						cols={ 60 }
						value={ currentStoreaddtext }
						onChange={ ( value ) => updatecurrentStoreaddtext( value ) }
					/>
				</div>
				<div className="boxRowContainer">
					<strong>備考</strong>
					&nbsp;&nbsp;&nbsp;
					<table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000">
					<tr>
					<th align="right">決済</th>
					<th align="center">注文</th>
					<th align="center">払い戻し</th>
					</tr>
					{ items_gateway_remarks }
					</table>
				</div>
				<div className="boxRowContainer">
					<strong>払戻明細書の払戻方法</strong>
					&nbsp;&nbsp;&nbsp;
					<table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000">
					<tr>
						<th align="right">決済</th>
						<th align="center">払戻方法</th>
					</tr>
					{ items_gateway_refunds }
					<tr>
					<td colspan="2" align="center">※ 空白の場合は、購入時の支払い方法が記されます。</td>
					</tr>
					</table>
				</div>
				<div className="boxRowContainer">
					<strong>手数料の別名</strong>
					&nbsp;&nbsp;&nbsp;
					<TextControl
						value={ currentFeetext }
						onChange={ ( value ) => updatecurrentFeetext( value ) }
					/>
				</div>
			</>
		);
	}

	const items_pdf = [];
	if( typeof currentPageort !== 'undefined' ) {
		items_pdf.push(
			<div className="boxRowContainer">
				<strong>印刷の向き</strong>
				&nbsp;&nbsp;&nbsp;
				<RadioControl
					selected = { currentPageort }
					options={ [
						{ label: '縦', value: 'P' },
						{ label: '横', value: 'L' },
					] }
					onChange = { ( value ) => updatecurrentPageort( value ) }
				/>
			</div>
		);
	}
	if( typeof currentPagesize !== 'undefined' ) {
		items_pdf.push(
			<div className="boxRowContainer">
				<strong>サイズ</strong>
				&nbsp;&nbsp;&nbsp;
				<RadioControl
					selected = { currentPagesize }
					options={ [
						{ label: 'B5', value: 'B5' },
						{ label: 'A4', value: 'A4' },
						{ label: 'B4', value: 'B4' },
						{ label: 'A3', value: 'A3' },
					] }
					onChange = { ( value ) => updatecurrentPagesize( value ) }
				/>
			</div>
		);
	}
	if( typeof currentMarginleft !== 'undefined' &&
		typeof currentMargintop !== 'undefined' &&
		typeof currentMarginright !== 'undefined' &&
		typeof currentMarginbottom !== 'undefined' ) {
		items_pdf.push(
			<div className="boxRowContainer">
				<strong>マージン</strong>
				&nbsp;&nbsp;&nbsp;
				左:
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentMarginleft ) }
					onChange = { ( value ) => updatecurrentMarginleft( value ) }
				/>
				&nbsp;&nbsp;
				上:
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentMargintop ) }
					onChange = { ( value ) => updatecurrentMargintop( value ) }
				/>
				&nbsp;&nbsp;
				右:
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentMarginright ) }
					onChange = { ( value ) => updatecurrentMarginright( value ) }
				/>
				&nbsp;&nbsp;
				下:
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentMarginbottom ) }
					onChange = { ( value ) => updatecurrentMarginbottom( value ) }
				/>
			</div>
		);
	}
	if( typeof currentFontsize !== 'undefined' ) {
		items_pdf.push(
			<div className="boxRowContainer">
				<strong>フォントサイズ</strong>
				&nbsp;&nbsp;&nbsp;
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 2 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentFontsize ) }
					onChange = { ( value ) => updatecurrentFontsize( value ) }
				/>
			</div>
		);
	}

	const items_pdf_font = [];
	if( typeof currentFont !== 'undefined' && ! invoicejapan_data.addon ) {
		items_pdf_font.push(
			<div className="boxRowContainer">
				<strong>フォント</strong>
				&nbsp;&nbsp;&nbsp;
				<SelectControl
					value = { currentFont }
					options={ [
						{ label: 'IPAexゴシック', value: 'ipaexg' },
						{ label: 'IPAex明朝', value: 'ipaexm' },
						{ label: '源真ゴシック Medium', value: 'genshingothicmedium' },
						{ label: 'あおぞら明朝 Medium', value: 'aozoraminchomedium' },
					] }
					onChange = { ( value ) => updatecurrentFont( value ) }
					className = "settings_select"
				/>
			</div>
		);
	}
	if( typeof currentFontsizeheader !== 'undefined' ) {
		items_pdf_font.push(
			<div className="boxRowContainer">
				<strong>ヘッダー</strong>
				&nbsp;&nbsp;&nbsp;
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentFontsizeheader ) }
					onChange = { ( value ) => updatecurrentFontsizeheader( value ) }
				/>
			</div>
		);
	}
	if( typeof currentFontsizefooter !== 'undefined' ) {
		items_pdf_font.push(
			<div className="boxRowContainer">
				<strong>フッター</strong>
				&nbsp;&nbsp;&nbsp;
				<NumericInput
					mobile = { true }
					pattern = "^([1-9]\d*|0)(\.\d+)?$"
					inputmode = "numeric"
					min = { 5 }
					max = { 50 }
					step = { 1 }
					size = { 5 }
					value = { parseInt( currentFontsizefooter ) }
					onChange = { ( value ) => updatecurrentFontsizefooter( value ) }
				/>
			</div>
		);
	}

	const items_mail_body = [];
	if( typeof currentOrdermailbody !== 'undefined' &&
		typeof currentRefundmailbody !== 'undefined' ) {
		items_mail_body.push(
			<>
				<div className="boxRowContainer">
					<strong>請求書</strong>
					&nbsp;&nbsp;&nbsp;
					<TextareaControl
						cols={ 60 }
						value={ currentOrdermailbody }
						onChange={ ( value ) => updatecurrentOrdermailbody( value ) }
					/>
				</div>
				<div className="boxRowContainer">
					<strong>払戻明細書</strong>
					&nbsp;&nbsp;&nbsp;
					<TextareaControl
						cols={ 60 }
						value={ currentRefundmailbody }
						onChange={ ( value ) => updatecurrentRefundmailbody( value ) }
					/>
				</div>
			</>
		);
	}

	const items_gateway_mail_timing = [];
	if( typeof currentGatewaymailtiming !== 'undefined' ) {
		Object.keys( currentGatewaymailtiming ).map(
			( key1 ) => {
				//console.log( key1 );
				if( currentGatewaymailtiming.hasOwnProperty( key1 ) ) {
					let timing_checks = [];
					Object.keys( currentGatewaymailtiming[ key1 ] ).map(
						( key2 ) => {
							timing_checks.push(
								<td>
									<CheckboxControl
										checked={ currentGatewaymailtiming[ key1 ][ key2 ] }
										onChange={ ( value ) =>
											{
												currentGatewaymailtiming[ key1 ][ key2 ] = value;
												let data = Object.assign( {}, currentGatewaymailtiming );
												updatecurrentGatewaymailtiming( data );
											}
										}
									/>
								</td>
							);
						}
					);
					items_gateway_mail_timing.push(
						<tr>
						<td align="right">
						{ gateway_txt[ key1 ] }
						</td>
						{ timing_checks }
						</tr>
					);
				}
			}
		);
	}

	const items_addon = [];
	if ( ! invoicejapan_data.addon ) {
		items_addon.push(
			<>
				<hr />
				<h3>アドオン</h3>
				<div>アドオンが有効化されていないか、ありません。</div>
				<div>以下のリンクからアドオンを購入できます。</div>
				<a className="aStyle" href={ invoicejapan_data.addon_url } target="_blank" rel="noopener noreferrer">Riverforest Plugins ショップ</a>
			</>
		);
	}

	return (
		<div className="wrap">
			<h2>PDF Invoice Japan for WooCommerce</h2>
			<Credit />
			<hr />
			<h3>税率</h3>
			{ items_tax }
			<hr />
			<h3>端数処理</h3>
			{ items_rounding }
			<hr />
			<h3>PDF 設定</h3>
			{ items }
			{ items_pdf }
			{ items_pdf_font }
			<hr />
			<h3>メール本文付記</h3>
			{ items_mail_body }
			<hr />
			<h3>メール送信のタイミング</h3>
			<div className="boxRowContainer">
				<strong>請求書</strong>
				&nbsp;&nbsp;&nbsp;
				<table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000">
				<tr>
				<td align="right"><strong>決済/注文状況</strong></td>
				<td align="center" width="70px">支払い待ち</td>
				<td align="center" width="70px">処理中</td>
				<td align="center" width="70px">保留中</td>
				<td align="center" width="70px">完了</td>
				</tr>
				{ items_gateway_mail_timing }
				</table>
			</div>
			<div className="boxRowContainer">
				<strong>払戻明細書</strong>
				&nbsp;&nbsp;&nbsp;
				<div>払戻の都度</div>
			</div>
			{ items_addon }
		</div>
	);

};

render(
	<InvoicejapanAdmin />,
	document.getElementById( 'invoicejapanadmin' )
);

