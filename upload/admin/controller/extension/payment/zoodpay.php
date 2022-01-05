<?php

class ControllerExtensionPaymentZoodPay extends Controller
{

    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/zoodpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('extension/payment/zoodpay');
        require_once DIR_SYSTEM . 'library/zoodpay/encryption.php';
        $encryption = new VectorEncryption();
        $_config = new Config();
        $_config->load('zoodpay');
        $encryption_key = $_config->get('encryption_key');
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->request->post['payment_zoodpay_merchant_secret'] =$encryption->encrypt($encryption_key,$this->request->post['payment_zoodpay_merchant_secret']);
            $this->request->post['payment_zoodpay_salt_key'] =$encryption->encrypt($encryption_key,$this->request->post['payment_zoodpay_salt_key']);
            $this->model_setting_setting->editSetting('payment_zoodpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('success_save');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extensions'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        $data['user_token'] = $this->session->data['user_token'];


        if (isset($environment)) {
            $data['environment'] = $environment;
        } elseif (isset($this->request->post['payment_zoodpay_environment'])) {
            $data['environment'] = $this->request->post['payment_zoodpay_environment'];
        } elseif ($this->config->get('payment_zoodpay_environment')) {
            $data['environment'] = $this->config->get('payment_zoodpay_environment');
        } else {
            $data['environment'] = '';
        }
        $data['IPN_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/zoodpay/IPN';
        $data['success_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/zoodpay/success';
        $data['error_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/zoodpay/error';
        $data['refund_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/zoodpay/refund';

        if (isset($merchant_tc)) {
            $data['merchant_tc'] = $merchant_tc;
        } elseif (isset($this->request->post['payment_zoodpay_merchant_tc'])) {
            $data['merchant_tc'] = $this->request->post['payment_zoodpay_merchant_tc'];
        } else {
            $data['merchant_tc'] = $this->config->get('payment_zoodpay_merchant_tc');
        }

        if (isset($merchant_key)) {
            $data['merchant_key'] = $merchant_key;
        } elseif (isset($this->request->post['payment_zoodpay_merchant_key'])) {
            $data['merchant_key'] = $this->request->post['payment_zoodpay_merchant_key'];
        } else {
            $data['merchant_key'] = $this->config->get('payment_zoodpay_merchant_key');
        }


        if (isset($merchant_secret)) {
            $data['merchant_secret'] = $merchant_secret;
        } elseif (isset($this->request->post['payment_zoodpay_merchant_secret'])) {
            $data['merchant_secret'] = $this->request->post['payment_zoodpay_merchant_secret'];
        } else {
            $data['merchant_secret'] = $encryption->decrypt($encryption_key,$this->config->get('payment_zoodpay_merchant_secret'));
        }
        if (isset($payment_zoodpay_should_match_limit)) {
            $data['payment_zoodpay_should_match_limit'] = $payment_zoodpay_should_match_limit;
        } elseif (isset($this->request->post['payment_zoodpay_should_match_limit'])) {
            $data['payment_zoodpay_should_match_limit'] = $this->request->post['payment_zoodpay_should_match_limit'];
        } else {
            $data['payment_zoodpay_should_match_limit'] = $this->config->get('payment_zoodpay_should_match_limit');
        }
        if (isset($salt_key)) {
            $data['salt_key'] = $salt_key;
        } elseif (isset($this->request->post['payment_zoodpay_salt_key'])) {
            $data['salt_key'] = $this->request->post['payment_zoodpay_salt_key'];
        } else {
            $data['salt_key'] = $encryption->decrypt($encryption_key,$this->config->get('payment_zoodpay_salt_key'));
        }

        if (isset($this->request->post['payment_zoodpay_status'])) {
            $data['status'] = $this->request->post['payment_zoodpay_status'];
        } else {
            $data['status'] = $this->config->get('payment_zoodpay_status');
        }

        //Added for the Status
        if (isset($this->request->post['payment_zoodpay_refund_declined_status_id'])) {
            $data['payment_zoodpay_refund_declined_status_id'] = $this->request->post['payment_zoodpay_refund_declined_status_id'];
        } else {
            $data['payment_zoodpay_refund_declined_status_id'] = $this->config->get('payment_zoodpay_refund_declined_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_refund_approved_status_id'])) {
            $data['payment_zoodpay_refund_approved_status_id'] = $this->request->post['payment_zoodpay_refund_approved_status_id'];
        } else {
            $data['payment_zoodpay_refund_approved_status_id'] = $this->config->get('payment_zoodpay_refund_approved_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_refund_initiated_status_id'])) {
            $data['payment_zoodpay_refund_initiated_status_id'] = $this->request->post['payment_zoodpay_refund_initiated_status_id'];
        } else {
            $data['payment_zoodpay_refund_initiated_status_id'] = $this->config->get('payment_zoodpay_refund_initiated_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_pending_status_id'])) {
            $data['payment_zoodpay_pending_status_id'] = $this->request->post['payment_zoodpay_pending_status_id'];
        } else {
            $data['payment_zoodpay_pending_status_id'] = $this->config->get('payment_zoodpay_pending_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_cancelled_status_id'])) {
            $data['payment_zoodpay_cancelled_status_id'] = $this->request->post['payment_zoodpay_cancelled_status_id'];
        } else {
            $data['payment_zoodpay_cancelled_status_id'] = $this->config->get('payment_zoodpay_cancelled_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_failed_status_id'])) {
            $data['payment_zoodpay_failed_status_id'] = $this->request->post['payment_zoodpay_failed_status_id'];
        } else {
            $data['payment_zoodpay_failed_status_id'] = $this->config->get('payment_zoodpay_failed_status_id');
        }

//        if (isset($this->request->post['payment_zoodpay_processed_status_id'])) {
//            $data['payment_zoodpay_processed_status_id'] = $this->request->post['payment_zoodpay_processed_status_id'];
//        } else {
//            $data['payment_zoodpay_processed_status_id'] = $this->config->get('payment_zoodpay_processed_status_id');
//        }

        if (isset($this->request->post['payment_zoodpay_processing_status_id'])) {
            $data['payment_zoodpay_processing_status_id'] = $this->request->post['payment_zoodpay_processing_status_id'];
        } else {
            $data['payment_zoodpay_processing_status_id'] = $this->config->get('payment_zoodpay_processing_status_id');
        }

        if (isset($this->request->post['payment_zoodpay_complete_status_id'])) {
            $data['payment_zoodpay_complete_status_id'] = $this->request->post['payment_zoodpay_complete_status_id'];
        } else {
            $data['payment_zoodpay_complete_status_id'] = $this->config->get('payment_zoodpay_complete_status_id');
        }


        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $this->load->model('sale/order');

        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        } else {
            $filter_order_id = '';
        }

        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = '';
        }

        if (isset($this->request->get['filter_order_status'])) {
            $filter_order_status = $this->request->get['filter_order_status'];
        } else {
            $filter_order_status = '';
        }

        if (isset($this->request->get['filter_order_status_id'])) {
            $filter_order_status_id = $this->request->get['filter_order_status_id'];
        } else {
            $filter_order_status_id = '';
        }

        if (isset($this->request->get['filter_total'])) {
            $filter_total = $this->request->get['filter_total'];
        } else {
            $filter_total = '';
        }

        if (isset($this->request->get['filter_date_added'])) {
            $filter_date_added = $this->request->get['filter_date_added'];
        } else {
            $filter_date_added = '';
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $filter_date_modified = $this->request->get['filter_date_modified'];
        } else {
            $filter_date_modified = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'o.order_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_order_status_id'])) {
            $url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }


        $data['text_zoodpay_orders'] = $this->language->get('text_zoodpay_orders');
        $data['text_get_configuration'] = $this->language->get('text_get_configuration');

        $data['orders'] = array();

        $filter_data = array(
            'filter_order_id' => $filter_order_id,
            'filter_customer' => $filter_customer,
            'filter_order_status' => $filter_order_status,
            'filter_order_status_id' => $filter_order_status_id,
            'filter_total' => $filter_total,
            'filter_date_added' => $filter_date_added,
            'filter_date_modified' => $filter_date_modified,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $results = $this->model_sale_order->getOrders($filter_data);
        $order_count = 0;
        foreach ($results as $result) {
            if (!$this->model_extension_payment_zoodpay->isZoodPayOrder($result['order_id'])) {$order_count++; continue;}
            $result['order_status'] = $this->model_extension_payment_zoodpay->getStatus($result['order_id']);
            $data['orders'][] = array(
                'order_id' => $result['order_id'],
                'customer' => $result['customer'],
                'order_status' => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
                'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'shipping_code' => $result['shipping_code'],
                'view' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . '&zoodpay=true' . $url, true),
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_order_status_id'])) {
            $url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_order'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . $url, true);
        $data['sort_customer'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . $url, true);
        $data['sort_status'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=order_status' . $url, true);
        $data['sort_total'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=o.total' . $url, true);
        $data['sort_date_added'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);
        $data['sort_date_modified'] = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_modified' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_order_status_id'])) {
            $url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $order_count;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/payment/zoodpay', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_count) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_count - $this->config->get('config_limit_admin'))) ? $order_count : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_count, ceil($order_count / $this->config->get('config_limit_admin')));

        $data['filter_order_id'] = $filter_order_id;
        $data['filter_customer'] = $filter_customer;
        $data['filter_order_status'] = $filter_order_status;
        $data['filter_order_status_id'] = $filter_order_status_id;
        $data['filter_total'] = $filter_total;
        $data['filter_date_added'] = $filter_date_added;
        $data['filter_date_modified'] = $filter_date_modified;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/zoodpay', $data));
    }

    public function buildConfiguration()
    {
        $this->load->language('extension/payment/zoodpay');
        $data = array();
        $json = array();
        require_once DIR_SYSTEM . 'library/zoodpay/encryption.php';
        $encryption = new VectorEncryption();
        $_config = new Config();
        $_config->load('zoodpay');
        $encryption_key = $_config->get('encryption_key');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') ) {
            if (isset($this->request->post['merchant_secret']) && $this->request->post['merchant_secret']) {
                $data['merchant_secret'] =  $encryption->encrypt($encryption_key,$this->request->post['merchant_secret']);
            } else {
                $json['error1'][] = $this->language->get('error_merchant_secret');
            }
            if (isset($this->request->post['merchant_key']) && $this->request->post['merchant_key']) {
                $data['merchant_key'] = $this->request->post['merchant_key'];
            } else {
                $json['error1'][] = $this->language->get('error_merchant_key');
            }

//            if (isset($this->request->post['merchant_tc']) && $this->request->post['merchant_tc']) {
//                $data['merchant_tc'] = $this->request->post['merchant_tc'];
//            } else {
//                $json['error1'][] = $this->language->get('error_merchant_tc');
//            }




            if (isset($this->request->post['environment']) && $this->request->post['environment']) {
                $data['environment'] = $this->request->post['environment'];
            } else {
                $json['error1'][] = $this->language->get('error_environment');
            }
            if (isset($this->request->post['salt_key']) && $this->request->post['salt_key']) {
                $data['salt_key'] = $encryption->encrypt($encryption_key, $this->request->post['salt_key']);
            } else {
                $json['error1'][] = $this->language->get('error_salt_key');
            }

        }
        $this->load->model('extension/payment/zoodpay');
        if (!isset($json['error1'])) {
            $result = $this->model_extension_payment_zoodpay->editConfiguration($data);
            if ($result) {
                $json['error'] = $result;
            } else {
                $json['success'] = $this->language->get('success_configuration');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function refundOrder()
    {
        $this->load->language('extension/payment/zoodpay');
        $json = array();
        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('sale/order');
            $this->load->model('extension/payment/zoodpay');
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $json['error'] = $this->language->get('error_order_id');
            }
            if (isset($this->request->post['comment1']) && $this->request->post['comment1']) {
                $comment = $this->request->post['comment1'];
            } else {

                $json['error'] = $this->language->get('error_comment_refund');
            }
            if (isset($this->request->post['product_category_price']) && $this->request->post['product_category_price']) {
                $amount= 0;
                $refund_products=array();
                foreach ($this->request->post['product_category_price'] as $key=>$item) {
                    $refund_products[$key]['id'] = (int)$this->request->post['product_category'][$key];
                    $refund_products[$key]['quantity'] = (int)$this->request->post['product_category_quantity'][$key];
                    $refund_products[$key]['price'] = (int)$item;
                    $amount += (int)$item * (int)$this->request->post['product_category_quantity'][$key];
                }
                if (isset($this->request->post['product_additional_price'])&& $this->request->post['product_additional_price']) {
                    $amount += (int)$this->request->post['product_additional_price'];
                }
            } else {
//                $amount = 0;
//                $refund_products=array();
                $json['error'] = $this->language->get('no_product_selected');
            }
            $zoodpay_data['merchant_key'] = $this->config->get('payment_zoodpay_merchant_key');
            $zoodpay_data['merchant_secret'] = $this->config->get('payment_zoodpay_merchant_secret');
            $zoodpay_data['salt_key'] = $this->config->get('payment_zoodpay_salt_key');
            $zoodpay_data['environment'] = $this->config->get('payment_zoodpay_environment');
            if (!isset($json['error'])) {
                $order_info = $this->model_extension_payment_zoodpay->refundOrder($order_id, $comment, $refund_products, $zoodpay_data , $amount);
                if (!$order_info) {
                    $json['success'] = $this->language->get('success_refund');
                } else {
                    $json['error'] = $order_info;
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deliveryOrder()
    {
        $this->load->language('extension/payment/zoodpay');
        $json = array();
        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('sale/order');
            $this->load->model('extension/payment/zoodpay');
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $json['error'] = $this->language->get('error_order_id');
            }
            if (isset($this->request->post['comment1']) && $this->request->post['comment1']) {
                $comment = $this->request->post['comment1'];
            } else {
                $json['error'] = $this->language->get('error_comment_delivery');
            }
            if (isset($this->request->post['amount']) && $this->request->post['amount']) {
                $amount = $this->request->post['amount'];
            } else {
                $json['error'] = $this->language->get('error_amount');
            }
            $zoodpay_data['merchant_key'] = $this->config->get('payment_zoodpay_merchant_key');
            $zoodpay_data['merchant_secret'] = $this->config->get('payment_zoodpay_merchant_secret');
            $zoodpay_data['salt_key'] = $this->config->get('payment_zoodpay_salt_key');
            $zoodpay_data['environment'] = $this->config->get('payment_zoodpay_environment');
            if (!isset($json['error'])) {
            $order_info = $this->model_extension_payment_zoodpay->deliveryOrder($order_id, $comment, $amount, $zoodpay_data);
                if (!$order_info) {

                    $json['success'] = 'delivery date added successfully';
                } else {
                    $json['error'] = $order_info;
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function modal()
    {
        $this->load->language('extension/payment/zoodpay');
        $this->document->setTitle($this->language->get('heading_modal_title'));
        $data['user_token'] = $this->session->data['user_token'];
        if (isset($this->request->post['order_id'])) {
            $data['order_id'] = $this->request->post['order_id'];
        } else {
            $data['error'] = $this->language->get('error_order_id');
        }
        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } else {
            $data['error'] = $this->language->get('');
        }
        if (isset($this->request->post['method'])) {
            $data['method'] = $this->request->post['method'];
            if ($data['method'] === 'refundOrder') {
                $data['textarea_legend'] = 'please describe the refund reason';
            } else {
                $data['textarea_legend'] = 'please leave a comment about delivery';
            }
        } else {
            $data['error'] = $this->language->get('error_action');
        }
        $this->response->setOutput($this->load->view('extension/payment/zoodpay_modal', $data));
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_order'])) {
            $this->load->model('extension/payment/zoodpay');

            if (isset($this->request->get['filter_order'])) {
                $filter_order = $this->request->get['filter_order'];
            } else {
                $filter_order = '';
            }

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name'  => $filter_name,
                'filter_model' => $filter_model,
                'filter_order' => $filter_order,
                'start'        => 0,
                'limit'        => $limit
            );

            $results = $this->model_extension_payment_zoodpay->getProducts($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model'      => $result['model'],
                    'price'      => $result['price'],
                    'quantity'   => $result['quantity']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function history() {
        $this->load->language('sale/order');
        $this->load->language('extension/payment/zoodpay');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['histories'] = array();

        $this->load->model('extension/payment/zoodpay');

        $zoodpay_data['merchant_key'] = $this->config->get('payment_zoodpay_merchant_key');
        $zoodpay_data['merchant_secret'] = $this->config->get('payment_zoodpay_merchant_secret');
        $zoodpay_data['salt_key'] = $this->config->get('payment_zoodpay_salt_key');
        $zoodpay_data['environment'] = $this->config->get('payment_zoodpay_environment');
        $results = $this->model_extension_payment_zoodpay->getRefundHistories($this->request->get['order_id'], $zoodpay_data, ($page - 1) * 10, 10);

        foreach ($results as $result) {
            $data['histories'][] = array(
                'status'     => $result['status'],
                'product'     => $result['name'],
                'quantity'     => $result['quantity'],
                'comment'    => nl2br($result['comment']),
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
            );
        }

        $history_total = $this->model_extension_payment_zoodpay->getTotalOrderHistories($this->request->get['order_id']);

        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('extension/payment/zoodpay/history', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $this->request->get['order_id'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

        $this->response->setOutput($this->load->view('extension/payment/refund_history', $data));
    }

    public function healthcheck() {
        $json = array();
        $this->load->language('extension/payment/zoodpay');
        if (isset($this->request->post['url'])) {
            if (preg_match('/(http(|s)):\/\/(.*?)\//si',$this->request->post['url'], $url)) {
                $this->request->post['url'] = $url[0];
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL =>  $this->request->post['url'] . 'healthcheck',
                CURLOPT_ENCODING => "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
            ));
            $response = curl_exec($curl);
            if(curl_exec($curl) === false) {
                $error = curl_error($curl);
            }
            curl_close($curl);
            $decoded_response =json_decode($response, true);
            if (isset($error)) {
                $json['response'] = $error;
            } else if (isset($decoded_response['message'])) {
                $json['response'] = $decoded_response['message'];
            } else {
                $json['response'] = '';
            }
        } else {
            $json['error'] = $this->language>get('error_healthcheck_url');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install()
    {
        $this->load->model('extension/payment/zoodpay');
        $this->model_extension_payment_zoodpay->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/zoodpay');
        $this->model_extension_payment_zoodpay->uninstall();
    }

}



