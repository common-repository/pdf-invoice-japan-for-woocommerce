import './invoicejapan-resending.scss';

import apiFetch from '@wordpress/api-fetch';

import { Button, Notice } from '@wordpress/components';

import {
	render,
	useState,
	useEffect
} from '@wordpress/element';

const InvoicejapanResending = () => {

	const [ currentOrderid, updatecurrentOrderid ] = useState( parseInt( invoicejapan_resending_data.order_id ) );
	const [ currentRefundid, updatecurrentRefundid ] = useState( parseInt( invoicejapan_resending_data.refund_id ) );

	const [ currentSubmitinvoice, updatecurrentSubmitinvoice ] = useState( false );
	const [ currentSubmitrefund, updatecurrentSubmitrefund ] = useState( false );

	useEffect( () => {
		apiFetch( {
			path: 'rf/invoicejapan-resending_api/token',
			method: 'POST',
			data: {
				order_id: currentOrderid,
				refund_id: currentRefundid,
				order_button: currentSubmitinvoice,
				refund_button: currentSubmitrefund,
			}
		} ).then( ( response ) => {
			//console.log( response );
		} );
	}, [ currentSubmitinvoice, currentSubmitrefund ] );

	const onclick_submitinvoice = () => {
		updatecurrentSubmitinvoice( true );
	};
	const items_invoice_button = [];
	if ( 'resending_invoice' === invoicejapan_resending_data.status ) {
		if ( ! currentSubmitinvoice ) {
			items_invoice_button.push(
				<Button
					className = { 'button resending_button' }
					onClick = { onclick_submitinvoice }
				>
				{ '請求書の再送信' }
				</Button>
			);
		} else {
			items_invoice_button.push(
				<Notice
					status = "success"
					onRemove = { () =>
						{
							updatecurrentSubmitinvoice( false );
						}
					}
				>
				{ '請求書を再送信しました。' }
				</Notice>
			);
		}
	}

	const onclick_submitrefund = () => {
		updatecurrentSubmitrefund( true );
	};
	const items_refund_button = [];
	if ( 0 < currentRefundid ) {
		if ( ! currentSubmitrefund ) {
			items_refund_button.push(
				<Button
					className = { 'button resending_button' }
					onClick = { onclick_submitrefund }
				>
				{ '払戻明細書の再送信' }
				</Button>
			);
		} else {
			items_refund_button.push(
				<Notice
					status = "success"
					onRemove = { () =>
						{
							updatecurrentSubmitrefund( false );
						}
					}
				>
				{ '払戻明細書を再送信しました。' }
				</Notice>
			);
		}
	}

	return (
		<div className="wrap">
			<h2>請求書あるいは払戻明細書の再送信</h2>
			<p>{ items_invoice_button }</p>
			<p>{ items_refund_button }</p>
		</div>
	);

};

render(
	<InvoicejapanResending />,
	document.getElementById( 'invoicejapanresending' )
);

