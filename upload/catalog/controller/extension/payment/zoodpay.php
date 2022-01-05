<?php

class ControllerExtensionPaymentZoodPay extends Controller
{
    private $error = array();


    /**
     * @var array
     * change statuses handle as described https://webocreation.com/blog/order-statuses-management-in-opencart-3/
     */

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/payment/zoodpay');
        $this->load->model('extension/payment/zoodpay');
        // $this->statuses =  $this->model_extension_payment_zoodpay->getStatuses() ;
    }

    public function index()
    {
        require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
        $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));
        $data['zoodpay_error'] = array();
        if ($payment->hasErrors()) {
            $errors = $payment->getErrors();
            foreach ($errors as $error) {
                $data['zoodpay_error'] = $error;
            }
        }
        return $this->load->view('extension/payment/zoodpay', $data);
    }

    public function save()
    {
        $json = array();
        $this->load->model('extension/payment/zoodpay');
        $order_info = $this->model_extension_payment_zoodpay->getOrder($this->session->data['order_id']);
        $this->load->model('checkout/order');
        $order_products = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);
        $item =array();
        foreach ($order_products as $key=>$order_product) {
            $item[$key]['categories'] = [$this->model_extension_payment_zoodpay->getCategoriesByProduct($order_product['product_id'])];
            $item[$key]['name'] =$order_product['name'];
            $item[$key]['price'] =$order_product['price'];
            $item[$key]['sku'] =$order_product['model'];
            $item[$key]['currency_code'] =$order_info['order']['currency'];
            $item[$key]['quantity'] =(int)$order_product['quantity'];
            $item[$key]['discount_amount'] =0;
            $item[$key]['tax_amount'] =0;
        }
        $order_info['items'] = array_values($item);


        $_config = new Config();
        $_config->load('zoodpay');
        $order_info['customer']['customer_dob'] = '1990-12-12'; //from config
        $order_info['customer']['customer_pid'] = 0; //from config

        require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';

        $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));
      //  $order_info['order']['merchant_reference_no'] = $this->config->get('payment_zoodpay_merchant_key') . 'v_' .  $this->session->data['order_id'];
        $order_info['order']['merchant_reference_no'] = (string) $this->session->data['order_id'];
        $order_info['order']['lang']= preg_replace('/-(.+?)+/', '', $this->config->get('config_language')) ;
        $order_info['order']['discount_amount']= 0;
        $order_info['order']['tax_amount']= 0;
        $order_info['order']['service_code']= $this->session->data['payment_method']['service_code'];
        $order_info['order']['shipping_amount']= 0;

        $order_info['order']['signature'] = $payment->createSignature(array(
            "merchant_reference_no" => $order_info['order']['merchant_reference_no'],
            "amount" => round($order_info['order']['amount']),
            "currency" => $order_info['order']['currency'],
            "market_code" => $order_info['order']['market_code'],));

        if (isset($this->session->data['shipping_method'])) {
            $order_info['order']['shipping_amount'] = $this->tax->calculate($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id'], $this->config->get('config_tax'));
            $order_info['shipping_service']['name']= $this->session->data['shipping_method']['title'];
        }

        $order = $payment->createTransaction($order_info);
        if ($payment->hasErrors()) {
            $errors = $payment->getErrors();
            foreach ($errors as $error) {
                $this->error[] = $error;
            }
            $json['error'] = $this->error;
        } else {
            $this->load->model('checkout/order');
            $this->model_extension_payment_zoodpay->addTransactionHistory($this->session->data['order_id'], $order['transaction_id']);
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_zoodpay_pending_status_id')); // with 1 always Pending status

            $json['action'] = $order['payment_url'];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function success()
    {
        $this->load->language('extension/payment/zoodpay');
        $this->load->model('extension/payment/zoodpay');
        $this->load->model('checkout/order');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/zoodpay');
        $order_status_id = $this->config->get('payment_zoodpay_failed_status_id');
        // $this->log->write("Success Called");

        $json['status'] = 'transaction id or signature is not set';
        if (isset($this->request->post['transaction_id']) && isset($this->request->post['signature'])) {
            $transaction_id = $this->request->post['transaction_id'];
            $signature = $this->request->post['signature'];
            $order_data = $this->model_extension_payment_zoodpay->getOrderByTransaction($transaction_id);
            $signature_data = array(
                'merchant_reference_no' => $this->request->post['merchant_order_reference'],
                'market_code' => $order_data['order']['market_code'],
                'amount' => $this->request->post['amount'],
                'currency' => $order_data['order']['currency'],
                'transaction_id' => $transaction_id,
            );
            require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
            $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));
            $json['status'] = 'signature is not valid';





            if ($signature === $payment->createSignature($signature_data, 'IPN')) {
                if (isset($this->request->post['status'])) {
                    $this->log->write("1");
                    $status = $this->request->post['status'];

                    switch ($status){
                        case "Paid" : {

                            $order_status_id = $this->config->get('payment_zoodpay_processing_status_id');
                            break;
                        }

                        case "Pending" : {

                            $order_status_id = $this->config->get('payment_zoodpay_pending_status_id');
                            break;
                        }

                        default : {

                            $order_status_id = $this->config->get('payment_zoodpay_failed_status_id');
                            break;
                        }
                    }
                } else {
                    $status = '';


                    $order_status_id = $this->config->get('payment_zoodpay_failed_status_id');
                }
                if (isset($this->request->post['created_at'])) {
                    $comment = $this->request->post['created_at'];
                } else {
                    $comment = '';
                }
                $order_id = $order_data['order']['merchant_reference_no'];
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment . ' ' . $status);
                $this->model_extension_payment_zoodpay->addTransactionHistory($order_id, $transaction_id);
                $json['status'] = 'status successfully changed';

            }
        }


        if($order_status_id == $this->config->get('payment_zoodpay_processing_status_id')){
            $this->response->redirect($this->url->link('checkout/success', '', true));
        }
        else $this->response->redirect($this->url->link('checkout/checkout', '', true));

    }

    public function IPN() {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/zoodpay');

        // $this->log->write("IPN Called");



        $request = json_decode(file_get_contents('php://input'), true);


        $json['status'] = 'transaction id or signature is not set';
        if (isset($request['transaction_id']) && isset($request['signature'])) {
            $transaction_id = $request['transaction_id'];
            $signature = $request['signature'];
            $order_data = $this->model_extension_payment_zoodpay->getOrderByTransaction($transaction_id);
            $signature_data = array(
                'merchant_reference_no' => $request['merchant_order_reference'],
                'market_code' => $order_data['order']['market_code'],
                'amount' =>  number_format($order_data['order']['amount'], 2, '.', ''),
                'currency' => $order_data['order']['currency'],
                'transaction_id' => $transaction_id,
            );
            require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
            $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));
            $json['status'] = 'signature is not valid';



            if ($signature === $payment->createSignature($signature_data, 'IPN')) {
                if (isset($request['status'])) {
                    $status = $request['status'];

                    switch ($status){
                        case "Paid" : {
                            $order_status_id = $this->config->get('payment_zoodpay_processing_status_id');
                            break;
                        }

                        case "Pending" : {

                            $order_status_id = $this->config->get('payment_zoodpay_pending_status_id');
                            break;
                        }

                        default : {
                            $order_status_id = $this->config->get('payment_zoodpay_failed_status_id');
                            break;
                        }
                    }
                } else {
                    $status = '';

                    $order_status_id = $this->config->get('payment_zoodpay_failed_status_id');
                }
                if (isset($request['created_at'])) {
                    $comment = $request['created_at'];
                } else {
                    $comment = '';
                }
                $order_id = $order_data['order']['merchant_reference_no'];
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, 'IPN Callback '.$comment . ' ' . $status);
                $this->model_extension_payment_zoodpay->addTransactionHistory($order_id, $transaction_id);
                $json['status'] = 'status successfully changed';

            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function refund() {

        $this->load->model('extension/payment/zoodpay');
        $this->load->model('checkout/order');
        $json['status'] = 'refund_id not set';
        $request = json_decode(file_get_contents('php://input'), true);

        if (isset($request['refund_id']) && isset($request['signature'])) {
            $refund_id = $request['refund_id'];
            $signature = $request['signature'];
            $refund_data = array(
                'merchant_refund_reference' => $request['refund']['merchant_refund_reference'],
                'refund_amount' => $request['refund']['refund_amount'],
                'status' => $request['refund']['status'],
                'refund_id' => $refund_id,
            );
            $json['status'] = 'Signature is not valid';
            require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
            $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));
            if ($signature === $payment->createSignature($refund_data, 'refund')) {
                $order_id = $this->model_extension_payment_zoodpay->getOrderIdByRefundId($refund_id);
                if (isset($request['refund']['status'])) {
                    $status = $request['refund']['status'];
//                    $order_status_id = isset($this->statuses[$status])? $this->statuses[$status] : $this->statuses['Failed'];
                    switch ($status){
                        case "Approved" : {
                            $order_status_id = $this->config->get('payment_zoodpay_refund_approved_status_id');
                            break;

                        }
                        case "Declined" : {
                            $order_status_id = $this->config->get('payment_zoodpay_refund_declined_status_id');
                            break;
                        }
                    }

                }
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, 'refund ' . $status);
                $this->model_extension_payment_zoodpay->changeRefundStatus($order_id, $refund_id, $status);
                $json['status'] = 'refund status is changed';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function error()
    {

        $this->load->language('extension/payment/zoodpay');
        $this->load->model('extension/payment/zoodpay');
        $this->load->model('checkout/order');




        $json['status'] = 'transaction id or signature is not set';
        if (isset($this->request->post['transaction_id']) && isset($this->request->post['signature'])&& isset($this->request->post['errorMessage']) && isset($this->request->post['status'])  ) {
            $transaction_id = $this->request->post['transaction_id'];
            $error_message = $this->request->post['errorMessage'];
            $signature = $this->request->post['signature'];
            $order_data = $this->model_extension_payment_zoodpay->getOrderByTransaction($transaction_id);
            $signature_data = array(
                'merchant_reference_no' => $this->request->post['merchant_order_reference'],
                'market_code' => $order_data['order']['market_code'],
                'amount' => $this->request->post['amount'],
                'currency' => $order_data['order']['currency'],
                'transaction_id' => $transaction_id,
            );
            require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
            $payment = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));


            if ($signature === $payment->createSignature($signature_data, 'IPN')) {


                if($this->request->post['status'] == "Failed")
                {


                    $comment = $error_message." ". $this->request->post['created_at'];
                    $transaction_id = $this->request->post['transaction_id'];
                    $order_data = $this->model_extension_payment_zoodpay->getOrderByTransaction($transaction_id);
                    $order_id = $order_data['order']['merchant_reference_no'];
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_zoodpay_failed_status_id') ,  $comment);

                }



            }
        }


        $this->response->redirect($this->url->link('checkout/failure', '', true));

    }



    public function deliveryOrderStatus(&$route, &$data, &$output) {


        $orderId= $data[0];
        $orderStatus= $data[1];

        if ( $orderStatus == $this->config->get('payment_zoodpay_complete_status_id'))
        {

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zoodpay_order_information WHERE order_id = '" . $orderId . "' ORDER BY order_information_id DESC LIMIT 0,1");
            if (!$query->num_rows) {
                return true;
            }
            else{

                require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
                $this->load->language('extension/payment/zoodpay');

                $transaction = $this->db->query("SELECT * FROM " . DB_PREFIX . "zoodpay_order_information  WHERE order_id = '" . (int)$orderId . "'");
                $zoodpay = new ZoodPay($this->config->get('payment_zoodpay_merchant_key'), $this->config->get('payment_zoodpay_merchant_secret'), $this->config->get('payment_zoodpay_salt_key'), $this->config->get('payment_zoodpay_environment'));




                $amount = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$orderId . "'");

                $post = array(

                    "delivered_at" => date("Y-m-d H:i:s",time()),
                   "final_capture_amount" => $amount->row['total'],
                );

                $result = $zoodpay->setDeliveryDate($transaction->row['transaction_id'], $post);

                if (isset($result['status'])) {

                    $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = '" . $this->config->get('payment_zoodpay_complete_status_id') . "', date_modified = NOW() WHERE order_id = '" . (int)$orderId . "'");
                }

                if ($zoodpay->hasErrors()) {
                    $errors = $zoodpay->getErrors();
                    foreach ($errors as $error) {
                        $data['zoodpay_error'][] = $error;
                    }
                    return $data['zoodpay_error'];
                }


            }








        }



        return true;
    }


    public function agree() {
        $this->load->model('catalog/information');
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_configuration`");
        $result = $query->rows;

        if (isset($this->request->get['service_id'])) {
            $service_id = $this->request->get['service_id'];
        } else {
            $service_id = '';
        }
        $information_info = '';
        $output = '';

        for ( $i=0; $i <count($result) ; $i++){
            if($result[$i]['service_code'] == $service_id){
                $information_info = $result[$i]['description'];
            }
        }


        if ($information_info) {
            $output .= html_entity_decode($information_info, ENT_QUOTES, 'UTF-8') . "\n";
        }

        $this->response->setOutput($output);
    }



}

