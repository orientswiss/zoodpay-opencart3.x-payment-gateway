<?php
//LOGO

$_['text_zoodpay']		 				='<a target="_BLANK" href="https://zoodpay.com"><img src="view/image/payment/zoodpay.png" alt="ZoodPay" title="ZoodPay Buy Now Pay Later" style="border: 1px solid #EEEEEE;" /></a>';


// Heading
$_['heading_title']		 				= 'ZoodPay Buy Now Pay Later';

// Text
$_['text_extensions']     				= 'Extensions';
$_['text_edit']          				= 'Edit Zoodpay';
$_['text_general']				 	 	= 'General';
$_['text_order_status']				 	= 'Order Status';
$_['text_checkout_express']				= 'Checkout';
$_['text_checkout_card']				= 'Advanced Card';
$_['text_production']			 	 	= 'Production';
$_['text_sandbox']			 			= 'Sandbox';
$_['text_completed_status']				= 'Completed Status';
$_['text_denied_status']				= 'Denied Status';
$_['text_failed_status']				= 'Failed Status';
$_['text_pending_status']				= 'Pending Status';
$_['text_refunded_status']				= 'Refunded Status';
$_['text_reversed_status']				= 'Reversed Status';
$_['text_voided_status']				= 'Voided Status';
$_['text_zoodpay_orders']				= 'Zoodpay Orders';
$_['text_get_configuration']			= 'Fetch Configuration';
$_['text_additional_tab']			= 'Additional Info';
$_['text_transaction_info']			= 'ZoodPay Transaction ID';
$_['text_refund_tab']			= 'ZoodPay Refund';



// Entry
$_['entry_merchant_key']                = 'Merchant key';
$_['entry_merchant_tc']                = 'ZoodPay T&C';
$_['entry_merchant_secret']             = 'Merchant secret';
$_['entry_merchant_salt_key']           = 'Salt key';
$_['entry_environment']				 	= 'API URL';
$_['entry_debug']				 		= 'Debug Logging';
$_['entry_transaction_method']	 		= 'Settlement Method';
$_['entry_total']		 				= 'Total';
$_['entry_geo_zone']	 				= 'Geo Zone';
$_['entry_status']		 				= 'Status';
$_['entry_should_match_limit']		 	= 'Mismatch order limits hide module ';
$_['entry_IPN']		 	= 'IPN URL';
$_['entry_success']		 	= 'Success URL';
$_['entry_error']		 	= 'Error URL';
$_['entry_refund']		 	= 'Refund URL';
$_['entry_healthcheck']		 	= '';
$_['healthcheck']		 	= 'Check API';
$_['healthcheck_success']		 	= 'API is healthy';
$_['error_healthcheck_url']		 	= 'url is not set';

//Help
$_['help_should_match_limit']           = 'Module would not appears if order amount not match payment configuration limits';

// Column
$_['column_order_id']            		= 'Order ID';
$_['column_customer']            		= 'Customer';
$_['column_status']              		= 'Status';
$_['column_comment']              		= 'Comment';
$_['column_total']               		= 'Total';
$_['column_quantity']               		= 'quantity';
$_['column_name']               		= 'name';
$_['column_date_added']         	 	= 'Date Added';
$_['column_date_modified']       		= 'Date Modified';
$_['column_action']       				= 'Action';
// Help

// Button
$_['success_save']		 				= 'Success: You have modified ZoodPay!';
$_['button_save']		 				= 'send request';

//Success
$_['success_configuration'] 	  		= 'Configuration success';
$_['success_refund'] 	  				= 'refund history added successfully';
$_['no_product_selected'] 	  				= 'No Product Selected';

// Error
$_['error_permission']	 				= 'Warning: You do not have permission to modify payment ZoodPay!';
$_['error_timeout'] 	  				= 'Sorry, ZoodPay is currently busy. Please try again later!';
$_['error_order_id']		 			= 'Order id is not set';
$_['error_amount']		 				= 'Order amount is not set';
$_['error_action']		 				= 'action refund and/or delivery in not set';
$_['error_comment_delivery']		 	= 'please leave a comment about delivery';
$_['error_comment_refund']		 		= 'please describe the refund reason';
$_['error_merchant_secret']		 		= 'merchant_secret is not set';
$_['error_merchant_key']		 		= 'merchant_key is not set';
$_['error_environment']		 			= 'environment is not set';
$_['error_salt_key']		 			= 'salt_key is not set';
$_['error_not_found']		 			= 'the requested resource was not found';

// Custom order status
$_['status_try_refund']		 				= 'try refund';
$_['status_refund_declined']		 		= 'refund declined';
$_['status_refund_initiated']		 		= 'refund initiated';
$_['status_refund_approved']		 		= 'refund approved';
$_['status_try_delivery']		 			= 'try delivery';
$_['status_delivered']		 				= 'delivered';

$_['status_Paid']		 				    = 'Paid';
$_['status_Failed']		 				    = 'Failed';
$_['status_Cancelled']		 				= 'Cancelled';
$_['status_Inactive']		 				= 'Inactive';
$_['status_Initiated']		 				= 'Initiated';
$_['status_Approved']		 				= 'Approved';
$_['status_Declined']		 				= 'Declined';


//Added for the Status
$_['text_order_status_tab']			= 'Order Statuses ';
$_['entry_complete_status']			= 'Order Delivered Status';
$_['entry_processing_status']			= 'Order Paid Status';
//$_['entry_processed_status']			= 'Order Processed Status';
$_['entry_failed_status']			= 'Order Failed Status';
$_['entry_cancelled_status']			= 'Order Cancelled Status';
$_['entry_pending_status']			= 'Order Pending Payment  Status';
$_['entry_refund_initiated_status']			= 'Order Refund Initiated Status';
$_['entry_refund_approved_status']			= 'Order Refund Approved Status';
$_['entry_refund_declined_status']			= 'Order Refund Declined Status';
//Added for the Refund Comment
$_['refund_reason']			= 'Refund Reason: ';
$_['refund_status']			= ' ,Refund Status: ';