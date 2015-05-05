<?php
/**
 * Created by PhpStorm.
 * User: vinuth
 * Date: 1/5/15
 * Time: 2:49 PM
 */

trait KyashModel {
    function init() {
        $this->load->model('setting/setting');
        $this->settings = $this->model_setting_setting->getSetting('kyash');

        $this->load->library('log');
        $this->logger = new Log('kyash.log');

        require_once(DIR_SYSTEM . 'lib/KyashPay.php');
        $this->api = new KyashPay($this->settings['kyash_public_api_id'], $this->settings['kyash_api_secrets'], $this->settings['kyash_callback_secret'], $this->settings['kyash_hmac_secret']);
        $this->api->setLogger($this->logger);
    }

    public function getOrderInfo($order_id) {
        $result = $this->db->query('SELECT kyash_code, kyash_status, kyash_expires FROM ' . DB_PREFIX . 'order  WHERE order_id = ' . (int)$order_id);
        return array($result->row['kyash_code'], $result->row['kyash_status'], $result->row['kyash_expires']);
    }

    public function updateKyashStatus($order_id, $status) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET kyash_status = '" . $status . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function update($order_id) {
        if ($order_id > 0) {
            $order_info = $this->model_order->getOrder($order_id);
            list($kyash_code, $kyash_status, ) = $this->getOrderInfo($order_id);

            if ($order_info && !empty($kyash_code)) {
                if ($order_info['order_status_id'] == 7) {
                    if ($kyash_status === 'pending' || $kyash_status === 'paid') {
                        $response = $this->api->cancel($kyash_code);
                        if (isset($response['status']) && $response['status'] === 'error') {
                            return '<span class="error">' . $response['message'] . '</span>';
                        }

                        $this->updateKyashStatus($order_id, 'cancelled');
                        if ($kyash_status === 'pending') {
                            $message = '<br/>Kyash payment collection has been cancelled for this order.';
                        }
                        else {
                            $message = '<br/>Payment will be refunded to the customer by Kyash.';
                        }
                        return $message;
                    }
                    else if ($kyash_status === 'captured') {
                        $message = 'Customer payment has already been captured. Refunds if any, are to be handled by you.';
                        return $message;
                    }
                }
                else if ($order_info['order_status_id'] == 3) {
                    $server_kc = $this->api->getKyashCode($kyash_code);
                    if (isset($server_kc['status']) && $server_kc['status'] !== 'error') {
                        if ($kyash_status !== $server_kc['status']) {
                            $this->updateKyashStatus($order_id, $server_kc['status']);
                            $kyash_status = $server_kc['status'];
                        }
                    }

                    if ($kyash_status === 'pending') {
                        $response = $this->api->cancel($kyash_code);
                        if (isset($response['status']) && $response['status'] === 'error') {
                            return '<span class="error">' . $response['message'] . '</span>';
                        }

                        $this->updateKyashStatus($order_id, 'cancelled');
                        $message = '<br/>You have shipped before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
                        return $message;
                    }
                    else if ($kyash_status === 'paid') {
                        $response = $this->api->capture($kyash_code);
                        if (isset($response['status']) && $response['status'] === 'error') {
                            return '<span class="error">' . $response['message'] . '</span>';
                        }

                        $this->updateKyashStatus($order_id, 'captured');
                        $message = '<br/>Kyash payment has been successfully captured.';
                        return $message;
                    }
                }
            }
        }
        return '';
    }

    function lookup($dictionary, $key, $default=NULL) {
        return isset($dictionary[$key])? $dictionary[$key]: $default;
    }
}


