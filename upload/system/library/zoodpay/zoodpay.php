<?php

class ZoodPay
{
    private $environment = '';
    private $partner_id = '';
    private $merchant_key = '';
    private $secret = '';
    private $configuration = array();
    private $errors = array();
    private $last_response = array();
    private $salt = '';

    private function decrypt($target) {
        require_once DIR_SYSTEM . 'library/zoodpay/encryption.php';
        require_once DIR_SYSTEM . 'config/zoodpay.php';
        $_config = new Config();
        $_config->load('zoodpay');
        $encryption_key = $_config->get('encryption_key');
        $encryption= new VectorEncryption();
        $target = $encryption->decrypt($encryption_key,$target);
        return $target;
    }
    public function __construct($merchant_key, $secret, $salt, $environment)
    {
        $this->merchant_key = $merchant_key;
        $this->secret = $this->decrypt($secret);
        $this->salt = $this->decrypt($salt);
        $this->environment = $environment;
    }
    private function executePut($id,$post) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL =>  $this->environment . "transactions/" .$id. "/delivery",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS =>"{\r\n     \"delivered_at\" : \"" .$post['delivered_at']. "\",\r\n            \"final_capture_amount\" : " .$post['final_capture_amount']. "\r\n}",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic ". base64_encode($this->merchant_key . ":" . $this->secret),
                "Content-Type: application/json"
            ),
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    private function execute($method, $command, $params = array(), $json = true)
    {
        $ch = curl_init();

        if (preg_match("/(staging|sandbox|uat)/", $this->environment)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }


        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_URL, $this->environment . $command);
        curl_setopt($ch, CURLOPT_USERPWD, $this->merchant_key .":". $this->secret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        switch (strtolower(trim($method))) {
            case 'post' :
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildQuery($params, $json));
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_PUT, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "{\r\n     \"delivered_at\" : \"" .$params['delivered_at']. "\",\r\n            \"final_capture_amount\" : " .$params['final_capture_amount']. "\r\n}");
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        }
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $curl_code = curl_errno($ch);
            $constant = get_defined_constants(true);
            $curl_constant = preg_grep('/^CURLE_/', array_flip($constant['curl']));
            $this->errors[] =  array('status' => $curl_constant[$curl_code], 'message' => curl_error($ch));
        }
        curl_close($ch);
        $this->last_response = json_decode($response, true);
        return $this->last_response;
    }


    private function buildQuery($params, $json)
    {
        if (is_string($params)) {
            return $params;
        }

        if ($json) {
            return json_encode($params);
        } else {
            return http_build_query($params);
        }
    }

    public function createSignature($transaction_info = array(), $type = '')
    {
        $salt = $this->salt;
        $key = $this->merchant_key;
        if ($type === 'refund') {
            $string = $transaction_info["merchant_refund_reference"] . '|' . $transaction_info["refund_amount"] . '|' .
                $transaction_info["status"] . '|' . $key . '|' . $transaction_info["refund_id"] . '|' . $salt;
        } else if ($type === 'IPN'){
            $string = $transaction_info["market_code"] . '|' . $transaction_info["currency"] . '|' . $transaction_info["amount"] . '|' .
                $transaction_info["merchant_reference_no"] . '|' . $key . '|' . $transaction_info["transaction_id"] . '|' . $salt;
        } else {
            $string = $key . '|' . $transaction_info["merchant_reference_no"] . '|' . $transaction_info["amount"] . '|' .
                $transaction_info["currency"] . '|' . $transaction_info["market_code"] . '|' . $salt;
        }


        return hash('sha512', $string);
    }

    public function getConfiguration($post)
    {
        $command = 'configuration';
        $params = $post;
        $result = $this->execute('POST', $command, $params);
        if (isset($result["configuration"]) && $result["configuration"]) {
            return $result;
        } else if (isset($result)) {
            $this->errors[] = $result;
            return false;
        } else {
            return false;
        }

    }

    public function createTransaction($post)
    {
        $command = 'transactions';
        $params = $post;
        $result = $this->execute('POST', $command, $params);
        if (isset($result['payment_url'])) {
            return $result;
        } else {
            $this->errors[] = $result;
            return false;
        }
    }

    public function createRefundRequest($post)
    {
        $command = 'refunds';
        $params = $post;
        $result = $this->execute('POST', $command, $params);
        if (isset($result['refund'])) {
            return $result;
        } else {
            $this->errors[] = $result;
            return false;
        }
    }

    public function getTransactionByID($id)
    {
        $command = 'transactions/' . $id;
        $result = $this->execute('GET', $command);
        if (isset($result)) {
            return $result;
        } else {
            $this->errors[] = $result;
            return false;
        }
    }

    public function getRefundByRefundID($id)
    {
        $command = 'refunds/' . $id;
        $result = $this->execute('GET', $command);
        if (isset($result['refund'])) {
            return $result;
        } else {
            $this->errors[] = $result;
            return false;
        }
    }

    public function setDeliveryDate($transaction_id, $params) {
        $command = 'transactions/' . $transaction_id . '/delivery';
        $result = $this->executePut($transaction_id, $params);
        if (isset($result['transaction_id'])) {
            return $result;
        } else {
            $this->errors[] = $result;
            return false;
        }
    }

    public function hasErrors()
    {
        return count($this->errors);
    }

    //OUT: array of errors
    public function getErrors()
    {
        return $this->errors;
    }

    //OUT: last response
    public function getResponse()
    {
        return $this->last_response;
    }


}
