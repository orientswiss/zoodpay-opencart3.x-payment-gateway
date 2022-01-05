<?php

class ModelExtensionPaymentZoodPay extends Model
{
    private $declined;
    private $initiated;
    private $approved;

    const order_processing = 2;
    const order_failed = 10;
    const refund_approved = 11;
    const refund_declined = 8;
    const refund_initiated = 9;
    const order_processed = 15;
    const order_complete = 5;
    const order_cancelled = 6;
    const order_pending = 1;

    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "zoodpay_configuration` (
              `config_id` INT(11) NOT NULL AUTO_INCREMENT,
              `market_code` varchar(3),
              `description` longtext,
              `min_limit` INT(11) NOT NULL,
              `max_limit` INT(11) NOT NULL,
              `instalments` INT(11)  NULL,
              `service_code` VARCHAR(3),
              `service_name` VARCHAR(50),
              PRIMARY KEY (`config_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "zoodpay_order_information` (
              `order_information_id` INT(11) NOT NULL AUTO_INCREMENT,
              `order_id` int(11),
              `transaction_id` VARCHAR(50),
              `refund_id` VARCHAR(128) NULL,
              PRIMARY KEY (`order_information_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "zoodpay_refund_order` (
              `order_refund_id` INT(11) NOT NULL AUTO_INCREMENT,
              `order_history_id` int(11),
              `request_id` VARCHAR(32),
              `server_refund_id` VARCHAR(32),
              `order_id` int(11),
              `status` VARCHAR(50),
              `product_id` int(11) NULL,
              `quantity` int(11) NULL,
              PRIMARY KEY (`order_refund_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        $this->load->language('extension/payment/zoodpay');

        //Added for the Status
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_refund_declined_status_id" . "','" . $this::refund_declined . "','" . 0 . "' ) ,
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_refund_approved_status_id" . "','" . $this::refund_approved . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_refund_initiated_status_id" . "','" . $this::refund_initiated . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_pending_status_id" . "','" . $this::order_pending . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_cancelled_status_id" . "','" . $this::order_cancelled . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_failed_status_id" . "','" . $this::order_failed . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_processing_status_id" . "','" . $this::order_processing . "','" . 0 . "' ),
         (null,'" . (int) $this->config->get('config_store_id') . "','" . "payment_zoodpay" . "','" . "payment_zoodpay_complete_status_id" . "','" . $this::order_complete . "','" . 0 . "' )         ");

        $this->load->model('setting/event');

        $this->model_setting_event->addEvent('zoodpay_delivery', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/payment/zoodpay/deliveryOrderStatus');

        $current = file_get_contents(DIR_SYSTEM . 'config/zoodpay.php');
        $current .= PHP_EOL . '$_[\'encryption_key\'] = \'' . $this->token() . '\';';
        file_put_contents(DIR_SYSTEM . 'config/zoodpay.php', $current);
    }

    private function token($length = 16)
    {
        // Create random token
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $max = strlen($string) - 1;

        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $string[mt_rand(0, $max)];
        }

        return $token;
    }

    public function editConfiguration($data)
    {
        $this->load->model('setting/setting');
        $this->load->model('localisation/country');
        $this->load->language('extension/payment/zoodpay');
        $country = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
        if ($country['iso_code_2'] === 'SA') {
            $country['iso_code_2'] = 'KSA';
        }
        require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
        $zoodpay = new ZoodPay($data['merchant_key'], $data['merchant_secret'], $data['salt_key'], $data['environment']);
        $result = $zoodpay->getConfiguration(array("market_code" => $country['iso_code_2']));
        if ($zoodpay->hasErrors()) {
            $errors = $zoodpay->getErrors();
            foreach ($errors as $error) {
                $data['zoodpay_error'] = $error;
            }
            return $data['zoodpay_error'];
        } else {
            if (isset($result) && $result) {
                $this->db->query("TRUNCATE `" . DB_PREFIX . "zoodpay_configuration`");
                foreach ($result as $item) {
                    foreach ($item as $value) {
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "zoodpay_configuration`  SET market_code = '" . $this->db->escape($country['iso_code_2']) . "',
               description = '" . $this->db->escape($value['description']) . "',
                min_limit = '" . $this->db->escape($value['min_limit']) . "',
                 max_limit = '" . $this->db->escape($value['max_limit']) . "',
                 instalments = '" . (isset($value['instalments']) ? $this->db->escape($value['instalments']) : null) . "',
                  service_code = '" . $this->db->escape($value['service_code']) . "',
                  service_name = '" . $this->db->escape($value['service_name']) . "'");

                    }
                }
            } else {
                return $data['zoodpay_error'] = array('status:' => 404, 'message' => $this->language->get('error_not_found'));
            }
        }

    }

    public function __construct($registry)
    {

        parent::__construct($registry);
        $this->load->language('extension/payment/zoodpay');
        $this->declined = $this->language->get('status_refund_declined');
        $this->initiated = $this->language->get('status_refund_initiated');
        $this->approved = $this->language->get('status_refund_approved');
    }

    public function getConfiguration()
    {
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_configuration`");
        return $result->rows;
    }

    /**
     * Function RefundOrder
     * Description: Function to calculate the refund amount respective to quantity and process
     * JIRA: https://orientswiss.atlassian.net/browse/ZDP-830
     * Modified By: Hiren Sejpal
     * Modified Date: 21/05/2021
     */
    public function refundOrder($order_id, $comment, $refund_products, $data, $amount = 0)
    {
        require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
        $this->load->language('extension/payment/zoodpay');

        $transaction = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_order_information` WHERE order_id = '" . (int) $order_id . "'");
        $orderTotalInfo = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int) $order_id . "'");
        $refundOrderInfo = $this->db->query("SELECT  SUM(quantity) AS total_quantity  FROM `" . DB_PREFIX . "zoodpay_refund_order` WHERE order_id = '" . (int) $order_id . "' GROUP BY order_id");
        $products = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int) $order_id . "'");

        $zoodpay = new ZoodPay($data['merchant_key'], $data['merchant_secret'], $data['salt_key'], $data['environment']);

        if ($amount === 0) {
            $amount = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "'")->row['total'];
            foreach ($products->rows as $index => $row) {
                $refund_products[$index]['id'] = $row['product_id'];
                $refund_products[$index]['quantity'] = $row['quantity'];
            }
        }

        $quantityToRefund = $priceToRefund = $productQuantity = $productSubTotal = 0;

        foreach ($refund_products as $index => $row) {
            $currRefundProductId = $row['id'];
            $currQuantity = (int) $row['quantity'];
            $quantityToRefund += $currQuantity;
            foreach ($products->rows as $product_index => $product_row) {
                if ($currRefundProductId == $product_row['product_id'] && (int) $row['quantity'] > 0) {
                    $priceToRefund += $currQuantity * (float) $product_row['price'];
                }
            }
        }
        $amount = number_format($priceToRefund, 2, '.', '');

        foreach ($products->rows as $index => $row) {
            $productQuantity += (int) $row['quantity'];
            $productSubTotal += number_format((float) $row['total'], 2, '.', '');
        }

        $priceInfo = [];
        foreach ($orderTotalInfo->rows as $index => $row) {
            $priceInfo[$row['code']] = number_format((float) $row['value'], 2, '.', '');
        }

        $orderSubTotal = $priceInfo['sub_total'];
        $orderShippingCharge = isset($priceInfo['shipping']) ? $priceInfo['shipping'] : 0;
        $orderTotal = $priceInfo['total'];

        if ($quantityToRefund === $productQuantity) {
            $amount += $orderShippingCharge;
        } else if (isset($refundOrderInfo->row['total_quantity'])) {
            $refundedQuantity = (int) $refundOrderInfo->row['total_quantity'];

            if ($productQuantity === $refundedQuantity + $quantityToRefund) {
                $amount += $orderShippingCharge;
            }
        }

        $post = array(
            "merchant_refund_reference" => $data['merchant_key'] . "_" . $order_id,
            "reason" => $comment,
            "refund_amount" => $amount,
            "request_id" => uniqid('', true),
            "transaction_id" => $transaction->row['transaction_id'],
        );

        $result = $zoodpay->createRefundRequest($post);

        if (isset($result['refund'])) {
            if (isset($result['refund']['declined_reason']) && ($result['refund']['declined_reason'])) {
                $status_after = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id= '" . $this::refund_declined . "'");
                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . $status_after->row['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_id . "', comment = '" . $this->db->escape($result['refund']['declined_reason']) . "', date_added=NOW(), order_status_id= '" . (int) $this->config->get('payment_zoodpay_refund_initiated_status_id') . "'");
                $history_id = $this->db->getLastId();
                $this->addRefundHistory($history_id, $order_id, $result['refund_id'], $post['request_id'], $refund_products);
            } else {
                $status_after = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id= '" . $this::order_pending . "'");
                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . $status_after->row['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
//                $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', comment = '" . $this->db->escape($result['refund']['status']) . "', date_added=NOW(), order_status_id= '" . (int)$this->config->get('payment_zoodpay_refund_initiated_status_id') . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_id . "', comment = ' " . $this->language->get('refund_reason') . $comment . $this->language->get('refund_status') . $this->db->escape($result['refund']['status']) . "', date_added=NOW(), order_status_id= '" . (int) $this->config->get('payment_zoodpay_refund_initiated_status_id') . "'");
                $history_id = $this->db->getLastId();
                $this->addRefundHistory($history_id, $order_id, $result['refund_id'], $post['request_id'], $refund_products);
            }
        }

        if ($zoodpay->hasErrors()) {
            $errors = $zoodpay->getErrors();
            foreach ($errors as $error) {
                $data['zoodpay_error'][] = $error;
            }
            return $data['zoodpay_error'];
        } else {
            return false;
        }
    }

    public function deliveryOrder($order_id, $comment, $amount, $data)
    {
        require_once DIR_SYSTEM . 'library/zoodpay/zoodpay.php';
        $this->load->language('extension/payment/zoodpay');

        $transaction = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_order_information` WHERE order_id = '" . (int) $order_id . "'");
        $zoodpay = new ZoodPay($data['merchant_key'], $data['merchant_secret'], $data['salt_key'], $data['environment']);
        $amount = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "'");
        $status_before = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE name= '" . $this->language->get('status_try_delivery') . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . $status_before->row['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_id . "', comment = '" . $this->db->escape($comment) . "', date_added=NOW(), order_status_id= '" . (int) $this::order_complete . "'");
        $request_id = $this->db->getLastId();
        $delivered_at = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_history_id= '" . (int) $request_id . "'");
        $post = array(
            "delivered_at" => $delivered_at->row['date_added'],
            "final_capture_amount" => $amount->row['total'],
        );

        $result = $zoodpay->setDeliveryDate($transaction->row['transaction_id'], $post);

        if (isset($result['status'])) {
            $status_after = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE name= '" . $this->language->get('status_delivered') . "'");
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . $status_after->row['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_id . "', comment = '" . $this->db->escape($result['status']) . "', date_added=NOW(), order_status_id= '" . (int) $this::order_complete . "'");
        }

        if ($zoodpay->hasErrors()) {
            $errors = $zoodpay->getErrors();
            foreach ($errors as $error) {
                $data['zoodpay_error'][] = $error;
            }
            return $data['zoodpay_error'];
        } else {
            return false;
        }
    }

    public function addRefundHistory($order_history_id, $order_id, $refund_id, $request_id, $refund_products = '')
    {
        if (empty($refund_products)) {
            $this->db->query("UPDATE " . DB_PREFIX . "zoodpay_refund_order SET order_history_id = '" . (int) $order_history_id . "' WHERE server_refund_id = '" . $refund_id . "' ");
        } else {

            $this->load->language('extension/payment/zoodpay');

            foreach ($refund_products as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "zoodpay_refund_order SET order_history_id = '" . (int) $order_history_id . "', order_id = '" . (int) $order_id . "', request_id = '" . $request_id . "', server_refund_id = '" . $refund_id . "', status = '" . $this->db->escape($this->language->get('status_refund_initiated')) . "', product_id =  '" . (int) $item['id'] . "', quantity = '" . (int) $item['quantity'] . "' ");
            }
        }

    }

    public function getTotalOrderHistories($order_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON (oh.order_status_id = os.order_status_id) RIGHT JOIN " . DB_PREFIX . "zoodpay_refund_order ro ON (oh.order_history_id = ro.order_history_id) LEFT JOIN " . DB_PREFIX . "product_description p ON (ro.product_id = p.product_id) WHERE oh.order_id = '" . (int) $order_id . "' AND os.language_id = '" . (int) $this->config->get('config_language_id') . "'");

        return $query->row['total'];
    }

    public function getRefundHistories($order_id, $data, $start = 0, $limit = 10)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }
        $query = $this->db->query("SELECT oh.date_added, os.name as status, oh.comment, p.name, ro.quantity FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON (oh.order_status_id = os.order_status_id) RIGHT JOIN " . DB_PREFIX . "zoodpay_refund_order ro ON (oh.order_history_id = ro.order_history_id) LEFT JOIN " . DB_PREFIX . "product_description p ON (ro.product_id = p.product_id) WHERE oh.order_id = '" . (int) $order_id . "' AND os.language_id = '" . (int) $this->config->get('config_language_id') . "' ORDER BY oh.date_added DESC LIMIT " . (int) $start . "," . (int) $limit);

        return $query->rows;
    }

    public function isZoodPayOrder($order_id)
    {
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_order_information` WHERE order_id = '" . (int) $order_id . "'");
        if ($result->num_rows) {
            return true;
        }
        return false;
    }

    public function getStatus($order_id)
    {
        return $this->db->query(" SELECT name AS status FROM  `" . DB_PREFIX . "order_status` WHERE order_status_id =(SELECT order_status_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "')")->row['status'];
    }

    public function getProducts($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "order_product po ON (p.product_id = po.product_id) WHERE pd.language_id = '" . (int) $this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
            $sql .= " AND p.quantity = '" . (int) $data['filter_quantity'] . "'";
        }

        if (isset($data['filter_order']) && $data['filter_order'] !== '') {
            $sql .= " AND po.order_id = '" . (int) $data['filter_order'] . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int) $data['filter_status'] . "'";
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "zoodpay_configuration`;");

        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('zoodpay_delivery');

    }
}
