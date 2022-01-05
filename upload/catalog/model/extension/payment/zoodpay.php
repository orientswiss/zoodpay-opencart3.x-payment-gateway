<?php
class ModelExtensionPaymentZoodPay extends Model {
    private $errors = array();

    public function getMethod($address, $total, $service_code=false) {
        if(empty($service_code))
        {
            return false;
        }
        $this->load->language('extension/payment/zoodpay');
        $iso_code_2= array(
            "SA"=>"KSA",
            "KZ"=>"KZ",
            "UZ"=>"UZ",
            "IQ"=>"IQ",
            "JO"=>"JO",
            "KW"=>"KW",
        );
        $terms='';
        $zterms = ' <div class="pull-right">  '.$this->language->get('text_termsbefore').'
      
      <a target="_blank" href="' . htmlspecialchars($this->config->get('payment_zoodpay_merchant_tc')) . '" > ZoodPay '.$this->language->get('text_termsc').'</a> </div> <br>';
        //$terms= '<button type="button"  data-toggle="modal" data-target="#myModal">terms</button>';
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zoodpay_configuration` WHERE market_code IN ('" . $iso_code_2[$address['iso_code_2']] . "','" . $address['iso_code_3'] . "') AND service_code ='" .  $service_code . "'");
        $service_name = '';
        $sort_order = 0;
        $instalments = '';
        if ($query->num_rows) {
            $data = $query->row;
            $service_name = $data['service_name'];
            $sort_order = $data['config_id'];
            if (isset($data['instalments']) && $data['instalments']>0) {
                $instalments = $data['instalments'];
            }
            if($this->config->get('payment_zoodpay_should_match_limit')) {
                if ($total > $data['min_limit'] && $data['max_limit'] > $total) {
                    $status = true;
                }
                else {
                    $this->errors[] = 'Your total should be more than' . $data['min_limit'] . 'and less than' . $data['max_limit'] ;
                    $status = false;
                }
            }
            else {
                $status = true;
            }

        }
        else {
            $status = false;
        }
        $method_data = array();
        $terms = ' <a class="agree" href="' . htmlspecialchars($this->url->link("extension/payment/zoodpay/agree")."&service_id=".$service_code) . '" >  '.$this->language->get('text_termsc').'</a> ';
        $imgSource= '  <img src="' . HTTPS_SERVER . 'catalog/view/theme/default/image/'. "zoodpay_".$service_code."_". $this->language->get('code') .".png" .'" alt="ZoodPay.com" title="ZoodPay.com"  style="height:35px;"/>';

        if ($status) {
            $method_data = array(
                'code'       => "$service_code",
                'title'      => ($instalments !== '') ? ( $service_name . $this->language->get('text_of') . round(floatval($total) / floatval($instalments), 2) .' ' . $this->session->data['currency'] .$imgSource ) : $service_name .$imgSource ,
              //  'title'      => ($instalments !== '') ? ($instalments . $this->language->get('text_monthly') . $service_name . $this->language->get('text_of') . round(floatval($total) / floatval($instalments), 2) .' ' . $this->session->data['currency'] . $this->language->get('text_with_zoodpay') . $service_name) : $service_name,
                'terms'      => "$terms",
                'sort_order' => $sort_order,
                'service_code' => $service_code,
                'zoodpayterms' => "$zterms"
            );
        }

        return $method_data;
    }

    public function getCategoriesByProduct($product_id) : array {
        $query = $this->db->query("SELECT name FROM  " . DB_PREFIX . "category_description cd LEFT JOIN " . DB_PREFIX . "category c ON (cd.category_id = c.category_id ) LEFT JOIN " . DB_PREFIX . "product_to_category AS ptc ON (c.category_id=ptc.category_id) WHERE ptc.product_id = '" . (int)$product_id. "'");
        $result=[];
        foreach ($query->rows as $row) {
            $result[]= $row['name'];
        }
        return $result;
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product " . DB_PREFIX . " WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrder($order_id) {
        $order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            if ($payment_iso_code_2 === 'SA') {
                $payment_iso_code_2 = 'KSA';
            }
            if ($shipping_iso_code_2 === 'SA') {
                $shipping_iso_code_2 = 'KSA';
            }

            //Added for the Local Pickup

            if ($this->customer->isLogged()  && ($order_query->row['shipping_code'] == 'pickup.pickup' || $order_query->row['payment_firstname'] == null || $order_query->row['shipping_postcode'] == null  || $order_query->row['currency_code'] = null || $order_query->row['telephone'] == null )){
                $address_id = $this->customer->getAddressId();
                $this->load->model('account/address');
                $address = $this->model_account_address->getAddress($address_id);
                $this->customer->getAddressId();

                return array(
                    "customer" => array(
                        "customer_email"=> $this->customer->getEmail(),
                        "customer_phone"=>$this->clearSpecialChar($this->customer->getTelephone()),
                        "first_name"=> $this->customer->getFirstName(),
                        "last_name"=> $this->customer->getLastName(),
                    ),

                    "order" => array(
                        "amount" => round($order_query->row['total']),
                        "currency" =>  $order_query->row['currency_code'],
                        "market_code" => $payment_iso_code_2,
                        "merchant_reference_no" => $order_query->row['order_id'],

                    ),
                    "billing" => array(
                        "address_line1"=> $address['address_1'],
                        "address_line2"=>$address['address_2'],
                        "city"=> $address['city'],
                        "country_code"=> $payment_iso_code_2,
                        "name"=> $address['firstname'] . $address['lastname'],
                        "phone_number"=> $this->clearSpecialChar($this->customer->getTelephone()),
                        "state"=> $address['zone'],
                        "zipcode"=> $address['postcode']
                    ),
                    "shipping" => array(
                        "address_line1"=>  $address['address_1'],
                        "address_line2"=>  $address['address_2'],
                        "city"=> $address['city'],
                        "country_code"=> $shipping_iso_code_2,
                        "name"=> $address['firstname'] . $address['lastname'],
                        "phone_number"=> $this->clearSpecialChar($this->customer->getTelephone()),
                        "state"=> $address['zone'],
                        "zipcode"=>$address['postcode']
                    ),
                    "shipping_service" => array(
                        "name"=> $order_query->row['shipping_method'],
                        "priority"=> "null",
                        "shipped_at"=> "null",
                        "tracking"=> "null"
                    )

                );




            }
            else{

                //if The Currency is not available, we set it from original
                if( !( $order_query->row['currency_code'])){
                    $order_query->row['currency_code'] = $order_query->rows[0]['currency_code'];
                }


                return array(
                    "customer" => array(
                        "customer_email"=> $order_query->row['email'],
                        "customer_phone"=>$this->clearSpecialChar( $order_query->row['telephone']),
                        "first_name"=> $order_query->row['firstname'],
                        "last_name"=> $order_query->row['lastname'],
                    ),

                    "order" => array(
                        "amount" => round($order_query->row['total']),
                        "currency" =>  $order_query->row['currency_code'],
                        "market_code" => $payment_iso_code_2,
                        "merchant_reference_no" => $order_query->row['order_id'],

                    ),
                    "billing" => array(
                        "address_line1"=> $order_query->row['payment_address_1'],
                        "address_line2"=> $order_query->row['payment_address_2'],
                        "city"=> $order_query->row['payment_city'],
                        "country_code"=> $payment_iso_code_2,
                        "name"=> $order_query->row['payment_firstname'] . $order_query->row['payment_lastname'],
                        "phone_number"=> $this->clearSpecialChar( $order_query->row['telephone']),
                        "state"=> $order_query->row['payment_zone'],
                        "zipcode"=> $order_query->row['payment_postcode']
                    ),
                    "shipping" => array(
                        "address_line1"=>  $order_query->row['shipping_address_1'],
                        "address_line2"=>  $order_query->row['shipping_address_2'],
                        "city"=> $order_query->row['shipping_city'],
                        "country_code"=> $shipping_iso_code_2,
                        "name"=> $order_query->row['shipping_firstname'] . $order_query->row['shipping_lastname'],
                        "phone_number"=> $this->clearSpecialChar( $order_query->row['telephone']),
                        "state"=> $order_query->row['shipping_zone'],
                        "zipcode"=> $order_query->row['shipping_postcode']
                    ),
                    "shipping_service" => array(
                        "name"=>$order_query->row['shipping_method'],
                        "priority"=> "null",
                        "shipped_at"=> "null",
                        "tracking"=> "null"
                    )

                );
            }


        } else {
            return false;
        }
    }


    public function addTransactionHistory($order_id, $transaction_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "zoodpay_order_information SET order_id = '" . (int)$order_id . "', transaction_id = '" . $transaction_id . "'");
    }

    public function changeRefundStatus($order_id, $refund_id, $status) {
        $this->load->language('extension/payment/zoodpay');
        $this->load->model('checkout/order');
        $order_history_id = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "' ORDER BY order_history_id DESC LIMIT 0,1");
        $this->db->query("UPDATE " . DB_PREFIX . "zoodpay_refund_order SET order_history_id = '" . (int)$order_history_id->row['order_history_id'] . "', status = '" . $status ."' WHERE server_refund_id = '" .$refund_id . "' ");
    }

    public function getOrderByTransaction($transaction_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zoodpay_order_information WHERE transaction_id = '" . $transaction_id . "' ORDER BY order_information_id DESC LIMIT 0,1");
        if (!$query->num_rows) {
            return false;
        }
        return $this->getOrder($query->row['order_id']);
    }

    public function getOrderIdByRefundId($refund_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zoodpay_refund_order WHERE server_refund_id = '" . $refund_id . "' ORDER BY order_refund_id DESC LIMIT 0,1");
        if (!$query->num_rows) {
            return false;
        }
        return $query->row['order_id'];
    }

    public function getStatuses() {
        $query= $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') ."'");
        foreach ($query->rows as $row) {
            $result[$row['name']] = $row['order_status_id'];
        }
    }

    public function getErrors()	{
        return $this->errors;
    }

    public function clearSpecialChar($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }
}
