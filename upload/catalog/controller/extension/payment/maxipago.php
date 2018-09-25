<?php
/**
 * maxiPago!
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 */

/**
 * maxiPago! Payment Method
 *
 * @package    maxiPago!
 * @author     Bizcommerce
 * @copyright  Copyright (c) 2016 BizCommerce
 *
 * @property ModelExtensionPaymentMaxipago model_extension_payment_maxipago
 * @property ModelCheckoutOrder model_checkout_order
 */
class ControllerExtensionPaymentMaxipago extends Controller
{
    private static $maxipago_transaction_states = array(
        'In Progress' => 1,
        'Captured' => 3,
        'Pending Capture' => 4,
        'Pending Authorization' => 5,
        'Authorized' => 6,
        'Declined' => 7,
        'Reversed' => 8,
        'Voided' => 9,
        'Paid' => 10,
        'Pending Confirmation' => 11,
        'Pending Review' => 12,
        'Pending Reversion' => 13,
        'Pending Capture (retrial)' => 14,
        'Pending Reversal' => 16,
        'Pending Void' => 18,
        'Pending Void (retrial)' => 19,
        'Boleto Issued' => 22,
        'Pending Authentication' => 29,
        'Authenticated' => 30,
        'Pending Reversal (retrial)' => 31,
        'Authentication in Progress' => 32,
        'Submitted Authentication' => 33,
        'Boleto Viewed' => 34,
        'Boleto Underpaid' => 35,
        'Boleto Overpaid' => 36,
        'File Submission Pending Reversal' => 38,
        'Fraud Approved' => 44,
        'Fraud Declined' => 45,
        'Fraud Review' => 46
    );

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->registry = $registry;
        $this->language->load('extension/payment/maxipago');
    }

    public function index()
    {
        $this->loadDependencies();

        $data = array(
            'base_url' => $this->getBaseUrl(),
            'continue_url' => $this->url->link('checkout/success', '', true),
            'image_banks_base_url' => $this->getImageUrl('banks/'),
            'image_brands_base_url' => $this->getImageUrl('brands/'),
            'months' => $this->getMonths(),
            'expiry_years' => $this->getExpiryYears()
        );

        $data = $this->getMethodsAllowed($data);

        if($data['credit_card_enabled'])
            $data = $this->loadCreditCardFormSettings($data);

        if($data['debit_card_enabled'])
            $data = $this->loadDebitCardFormSettings($data);

        if($data['eft_enabled'])
            $data = $this->loadEftFormSettings($data);

        if($data['invoice_enabled'])
            $data = $this->loadInvoiceFormSettings($data);

        if($data['redepay_enabled'])
            $data = $this->loadRedePayFormSettings($data);

        return $this->load->view('extension/payment/maxipago', $data);
    }

    private function loadDependencies()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/maxipago');
    }

    private function getBaseUrl()
    {
        if(isset($this->request->server['HTTPS']))
            if($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == '1')
                return $this->config->get('config_ssl');

        return $this->config->get('config_url');
    }

    private function getImageUrl($image_name)
    {
        return $this->getBaseUrl() . 'image/payment/maxipago/' . $image_name;
    }

    private function getMonths()
    {
        $months = array();

        for ($i = 1; $i <= 12; $i++) {
            $months[] = array(
                'label'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
                'value' => sprintf('%02d', $i)
            );
        }

        return $months;
    }

    private function getExpiryYears()
    {
        $expiry_years = array();
        $actual_year = getdate()['year'];

        for ($i = $actual_year; $i < $actual_year + 11; $i++) {
            $expiry_years[] = strftime('%Y', mktime(0, 0, 0, 1, 1, $i));
        }

        return $expiry_years;
    }

    private function getMethodsAllowed($data)
    {
        $data['credit_card_enabled'] = $this->config->get('payment_maxipago_credit_card_enabled');
        $data['has_recurring_products'] = $this->cart->hasRecurringProducts();
        $data['debit_card_enabled'] = $data['has_recurring_products'] ? false : $this->config->get('payment_maxipago_debit_card_enabled');
        $data['eft_enabled'] = $data['has_recurring_products'] ? false : $this->config->get('payment_maxipago_eft_enabled');
        $data['invoice_enabled'] = $data['has_recurring_products'] ? false : $this->config->get('payment_maxipago_invoice_enabled');
        $data['redepay_enabled'] = $data['has_recurring_products'] ? false : $this->config->get('payment_maxipago_redepay_enabled');

        return $data;
    }

    private function loadCreditCardFormSettings($data)
    {
        $order_id = $this->session->data['order_id'];
        $order_data = $this->model_checkout_order->getOrder($order_id);

        $saved_cards = $this->model_extension_payment_maxipago->getSavedCards($order_data['customer_id']);
        $installments = $this->model_extension_payment_maxipago->getInstallments($order_data);

        $data['image_cvv'] = $this->getImageUrl('cvv.png');
        $data['image_title_credit_card'] = $this->getImageUrl('ico-cc.png');

        $data['credit_card_brands'] = $this->getCreditCardBrands();
        $data['credit_card_allow_save'] = $this->config->get('payment_maxipago_credit_card_allow_save');
        $data['credit_card_installments'] = $installments;
        $data['credit_card_saved_cards'] = $saved_cards;

        $data['fraud_script'] = $this->getFraudCheckScript();

        return $data;
    }

    private function loadDebitCardFormSettings($data)
    {
        $data['image_cvv'] = $this->getImageUrl('cvv.png');
        $data['image_title_debit_card'] = $this->getImageUrl('ico-dc.png');
        $data['debit_card_brands'] = $this->getDebitCardBrands();
        return $data;
    }

    private function loadEftFormSettings($data)
    {
        $data['image_title_eft'] = $this->getImageUrl('ico-eft.png');
        $data['eft_banks'] = $this->getEftBanks();
        return $data;
    }

    private function loadInvoiceFormSettings($data)
    {
        $data['image_title_invoice'] = $this->getImageUrl('ico-ticket.png');
        $data['invoice_instructions'] = $this->config->get('payment_maxipago_invoice_instructions');
        return $data;
    }

    private function loadRedePayFormSettings($data)
    {
        $data['image_title_redepay'] = $this->getImageUrl('ico-redepay.png');
        return $data;
    }

    private function getCreditCardBrands()
    {
        $allowed_brands = array();
        $brands = $this->getCardBrands('credit');

        foreach ($brands as $brand)
        {
            $brand_processor = $this->config->get('payment_maxipago_credit_card_' . strtolower($brand) .  '_processor');

            if($brand_processor)
                $allowed_brands[] = $brand;
        }

        return $allowed_brands;
    }

    private function getFraudCheckScript()
    {
        $use_fraud_check = $this->config->get('payment_maxipago_credit_card_fraud_check');

        if($use_fraud_check)
        {
            $fraud_check_processors = array(
                'kount' => '99',
                'clearsale' => '98'
            );

            $fraud_check_processor = $this->config->get('payment_maxipago_credit_card_fraud_processor');

            if($fraud_check_processor == $fraud_check_processors['kount'])
                return $this->getKountScript();

            if($fraud_check_processor == $fraud_check_processors['clearsale'])
            {
                $clearsale_app = $this->config->get('payment_maxipago_credit_card_clearsale_app');
                return $this->getClearSaleScript($clearsale_app);
            }
        }


        return '';
    }

    private function getClearSaleScript($clearsale_app)
    {
        $clearsale_script = "(function(a,b,c,d,e,f,g) {
                    a['CsdpObject'] = e;
                    a[e] = a[e] || function() {
                        (a[e].q = a[e].q || []).push(arguments)
                    }, a[e].l = 1 * new Date();
                    f = b.createElement(c), g = b.getElementsByTagName(c)[0];
                    f.async = 1;
                    f.src = d;
                    g.parentNode.insertBefore(f, g)
                })(window, document, 'script', '//device.clearsale.com.br/p/fp.js', 'csdp');\n";

        $clearsale_script .= "csdp('app', '" . $clearsale_app . "');\n";
        $clearsale_script .= "csdp('sessionid', '" . session_id() . "');";

        return '<script>' . $clearsale_script . '</script>';
    }

    private function getKountScript()
    {
        $session_id = session_id();
        $store_id = $this->config->get('payment_maxipago_store_id');
        $store_secret = $this->config->get('payment_maxipago_store_secret');
        $hash = hash_hmac('md5', $store_id . '*' . $session_id, $store_secret);
        $url = "https://testauthentication.maxipago.net/redirection_service/logo?m={$store_id}&s={$session_id}&h={$hash}";
        $kount_script = '<iframe width="1" height="1" frameborder="0" src="' . $url . '"></iframe>';

        return $kount_script;
    }

    private function getDebitCardBrands()
    {
        $allowed_brands = array();
        $brands = $this->getCardBrands('debit');

        foreach ($brands as $brand)
        {
            $brand_processor = $this->config->get('payment_maxipago_debit_card_' . strtolower($brand) .  '_processor');

            if($brand_processor)
                $allowed_brands[] = $brand;
        }

        return $allowed_brands;
    }

    private function getCardBrands($type)
    {
        $card_brands = array(
            'MasterCard',
            'Visa'
        );

        if($type == 'credit') {
            $card_brands = array_merge($card_brands, array(
                'Credz',
                'Aura',
                'Jcb',
                'Hiper',
                'HiperCard',
                'Discover',
                'Elo',
                'Diners',
                'Amex'
            ));
        }

        return $card_brands;
    }

    private function getEftBanks()
    {
        $eft_banks = array();

        $existing_banks = array(
            '17' => 'Bradesco',
            '18' => 'Itaú'
        );

        $enabled_banks = $this->config->get('payment_maxipago_eft_banks');

        foreach($enabled_banks as $enabled_bank)
        {
            if(in_array($enabled_bank, array_keys($existing_banks)))
                $eft_banks[$enabled_bank] = $existing_banks[$enabled_bank];
        }

        return $eft_banks;
    }

    public function transaction()
    {
        try {
            $this->loadTransactionDependencies();

            $order_id = $this->session->data['order_id'];
            $order_data = $this->model_checkout_order->getOrder($order_id);

            $method = isset($this->request->post['method']) ? $this->request->post['method'] : null;

            if ($method == 'credit-card') {
                $response = $this->creditCardMethod($order_data);
            } else if ($method == 'debit-card') {
                $response = $this->model_extension_payment_maxipago->debitCardMethod($order_data);
            } else if ($method == 'eft') {
                $response = $this->model_extension_payment_maxipago->eftMethod($order_data);
            } else if ($method == 'invoice') {
                $response = $this->model_extension_payment_maxipago->invoiceMethod($order_data);
            } else if ($method == 'redepay') {
                $response = $this->model_extension_payment_maxipago->redepayMethod($order_data);
            } else {
                $response = array(
                    'error' => true,
                    'message' => $this->language->get('unknown_method_transaction_error')
                );
            }

            $this->setResponse(
                $this->jsonEncode($response)
            );
        } catch (Exception $e) {
            $this->setResponse(
                $this->jsonEncode(
                    array(
                        'error' => true,
                        'message' => $e->getMessage()
                    )
                )
            );
        }
    }

    private function creditCardMethod($order_data)
    {
        // If we don't have recurring products, it's a common credit card order!
        if(!$this->cart->hasRecurringProducts())
            return $this->model_extension_payment_maxipago->creditCardMethod($order_data);

        $responses = array();
        $products = $this->getSeparatedCartProducts();

        if(count($products['common']) > 0)
        {
            $order_data['total'] = $this->getOrderTotalWithoutRecurringProducts($order_data, $products['recurring']);
            $response = $this->model_extension_payment_maxipago->creditCardMethod($order_data);
            $response['product_data_type'] = 'common';
            $response['product_data'] = $products['common'];
            $responses[] = $response;
        }

        if(!empty($responses))
        {
            $response = $responses[0];

            if($this->invalidResponse($response)) {
                $response['error'] = true;
                $message = sprintf($this->language->get('text_error_response_voided_on_common'), $response['responseMessage']);
                $response['message'] = $message;
                return $response;
            }
        }

        if(!$this->model_checkout_recurring)
            $this->load->model('checkout/recurring');

        foreach($products['recurring'] as $recurring_product)
        {
            $recurring_data = $recurring_product['recurring'];
            $recurring_data['product_id'] = $recurring_product['product_id'];
            $recurring_data['quantity'] = $recurring_product['quantity'];

            $recurring_id = $this->model_checkout_recurring->addRecurring($order_data['order_id'], $recurring_product['name'], $recurring_data);
            $recurring_product['recurring']['order_recurring_id'] = $recurring_id;

            try {
                $response = $this->model_extension_payment_maxipago->recurringMethod($order_data, $recurring_product);
                $response['product_data_type'] = 'recurring';
                $response['product_data'] = $recurring_product;
            } catch (Exception $e) {
                $this->voidAllResponses($order_data);
                return array(
                    'error' => true,
                    'message' => $e->getMessage()
                );
            }

            if($this->invalidResponse($response)) {
                $this->voidAllResponses($order_data);
                $response['error'] = true;
                $message = sprintf($this->language->get('text_error_responses_voided_on_recurring'), $response['responseMessage'], $recurring_product['name'], $recurring_data['name']);
                $response['message'] = $message;
                return $response;
            }

            $this->model_checkout_recurring->editReference($recurring_id, $response['orderID']);
            $responses[] = $response;
        }

        $responses['recurring'] = true;
        return $responses;
    }

    private function invalidResponse($response)
    {
        if(!isset($response['orderID']))
            return true;

        if(empty($response['orderID']))
            return true;

        if(!in_array($response['responseMessage'], array('AUTHORIZED', 'CAPTURED')))
            return true;

        return false;
    }

    private function voidAllResponses($order_data)
    {
        $this->model_extension_payment_maxipago->voidOrder($order_data['order_id']);
    }

    private function getSeparatedCartProducts()
    {
        $products = $this->cart->getProducts();

        $common_products = array();
        $recurring_products = array();

        foreach($products as $product)
        {
            if($product['recurring'])
                $recurring_products[] = $product;
            else
                $common_products[] = $product;
        }

        return array(
            'common' => $common_products,
            'recurring' => $recurring_products
        );
    }

    private function getOrderTotalWithoutRecurringProducts($order_data, $recurring_products)
    {
        $total = $order_data['total'];

        foreach($recurring_products as $product)
            $total -= $product['price'];

        return $total;
    }

    public function loadTransactionDependencies()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/maxipago');
    }

    public function jsonEncode($data)
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data);
        }

        return $data;
    }

    public function setResponse($response)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput($response);
    }

    public function delete()
    {
        try {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/maxipago');

            $response = array('success' => false, 'message' => '');
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            $id_customer = $order_info['customer_id'];

            if ($id_customer) {
                $description = $this->request->post['ident'];
                $sql = 'SELECT *
                        FROM ' . DB_PREFIX . 'maxipago_cc_token
                        WHERE `id_customer` = \'' . $id_customer . '\'
                        AND `description` = \'' . $description . '\'
                        LIMIT 1; ';
                $ccSaved = $this->db->query($sql)->row;

                if ($this->model_extension_payment_maxipago->deleteCC($ccSaved)) {
                    $response = array('success' => true);
                }
            }
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        if (is_array($response) || is_object($response)) {
            $response = json_encode($response);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput($response);
    }

    ///////

    public function success()
    {
        $this->checkIpnResponse();
    }

    public function error()
    {
        $this->checkIpnResponse();
    }

    public function notification()
    {
        $this->checkIpnResponse();
    }

    private function checkIpnResponse()
    {
        $redirect_url = $this->getBaseUrl();
        $body = file_get_contents('php://input');
        $post_is_valid = isset($_POST) && !empty($_POST);
        $parameter_store_key = isset($this->request->get['mpKey']) ? $this->request->get['mpKey'] : null;
        $configured_store_key = $this->config->get('payment_maxipago_store_key');

        if(!$parameter_store_key || $parameter_store_key != $configured_store_key) {
            $this->session->data['error'] = $this->language->get('text_error_sync_invalid_key');
            $this->response->redirect($this->getBaseUrl());
        }

        if($body || $post_is_valid)
        {
            try
            {
                $maxipago_order_id = $this->getIpnMaxipagoOrderId();

                if($maxipago_order_id)
                {
                    $orders_ids = $this->getIpnOrderIds($maxipago_order_id);

                    if($orders_ids) {
                        $this->load->model('extension/payment/maxipago');

                        foreach($orders_ids as $order_id)
                        {
                            $parameters = array(
                                'orderID' => $maxipago_order_id
                            );

                            $redirect_url = $this->url->link('account/order/info', 'order_id=' . $order_id['id_order']);

                            $maxipago_client = $this->model_extension_payment_maxipago->getMaxipago();
                            $maxipago_client->pullReport($parameters);
                            $response = $maxipago_client->getReportResult();

                            if(isset($response[0]['transactionState']))
                            {
                                $transaction_state = $response[0]['transactionState'];
                                $order_status = $this->getOpencartStatusFromTransactionState($transaction_state);
                                $order_comment = $this->language->get('comment_updated_order') . ' ' . $transaction_state;
                                $status_description = array_search($transaction_state, self::$maxipago_transaction_states);

                                $this->model_extension_payment_maxipago->addOrderHistory($order_id['id_order'], $order_status, $order_comment);
                                $this->model_extension_payment_maxipago->updateTransactionState($order_id['id_order'], json_encode($response[0]), $status_description);

                                if($transaction_state == self::$maxipago_transaction_states['Fraud Approved'])
                                {
                                    $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id['id_order'] . "'");

                                    if(count($order_query->rows) > 0)
                                    {
                                        $order = $order_query->row;

                                        $capture_data = array(
                                            'orderID' => $maxipago_order_id,
                                            'referenceNum' => $order['order_id'],
                                            'chargeTotal' => $order['total']
                                        );

                                        $maxipago_client->creditCardCapture($capture_data);
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e)
            {
                $this->response->redirect($this->getBaseUrl());
            }
        }

        $this->response->redirect($redirect_url);
    }

    private function getIpnMaxipagoOrderId()
    {
        $body = file_get_contents('php://input');
        $post_is_valid = isset($_POST) && !empty($_POST);

        if($post_is_valid && isset($_POST['hp_orderid']))
            return $_POST['hp_orderid'];

        if($body)
        {
            $body_xml = simplexml_load_string($body);
            if (property_exists($body_xml, 'orderID'))
                return (string) $body_xml->orderID;
        }

        return null;
    }

    private function getIpnOrderIds($order_id)
    {
        $mp_transaction_table = DB_PREFIX . 'maxipago_transactions';
        $query = 'SELECT id_order FROM ' . $mp_transaction_table . ' WHERE JSON_EXTRACT(`return`, "$.orderID") = "' . $order_id . '" OR JSON_EXTRACT(`return`, "$.orderId") = "' . $order_id . '" GROUP BY id_order';

        $result = $this->db->query($query);

        if($result->rows)
            return $result->rows;
    }

    private function getOpencartStatusFromTransactionState($transaction_state)
    {
        $states = self::$maxipago_transaction_states;

        switch($transaction_state)
        {
            case $states['In Progress']:
                return $this->config->get('payment_maxipago_order_status_processing');
            case $states['Authorized']:
                return $this->config->get('payment_maxipago_order_status_authorized');
            case $states['Captured']:
            case $states['Paid']:
            case $states['Fraud Approved']:
                return $this->config->get('payment_maxipago_order_status_approved');
            case $states['Declined']:
            case $states['Fraud Declined']:
                return $this->config->get('payment_maxipago_order_status_cancelled');
        }
    }

    public function synchronize()
    {
        $parameter_store_key = isset($this->request->get['mpKey']) ? $this->request->get['mpKey'] : null;
        $configured_store_key = $this->config->get('payment_maxipago_store_key');

        if(!$parameter_store_key || $parameter_store_key != $configured_store_key) {
            $this->session->data['error'] = $this->language->get('text_error_sync_invalid_key');
            $this->response->redirect($this->getBaseUrl());
        }

        $this->load->language('extension/payment/maxipago');
        $this->load->model('extension/payment/maxipago');

        $transactions = $this->getSynchronizableTransactions();

        $refunded_orders = $this->synchronizeManuallyRefundedOrders();
        $captured_orders = $this->captureManuallyCapturedOrders();
        $updated_transactions = array();

        $transactions = $this->getSynchronizableTransactions();

        if($transactions)
        {
            try
            {
                foreach($transactions as $transaction)
                {
                    $order_updated = $this->model_extension_payment_maxipago->sync($transaction);
                    if ($order_updated)
                        array_push($updated_transactions, $order_updated);
                }
            } catch(Exception $exception)
            {
                $this->session->data['error'] = $this->language->get('text_error_sync');
            }
        }

        $updated_registers_count = count($refunded_orders) + count($captured_orders) + count($updated_transactions);

        if($updated_registers_count == 0)
            $this->session->data['success'] = $this->language->get('text_sync_no_rows');
        else
        {
            $sync_message = '<li>' . sprintf($this->language->get('text_success_sync'), $updated_registers_count) . '</li>';
            $this->session->data['success'] = '<ul>' . $sync_message . '</ul>';
        }

        $this->response->redirect($this->getBaseUrl());
    }

    private function getSynchronizableTransactions()
    {
        $maxipago_transactions = DB_PREFIX . 'maxipago_transactions';
        $minimum_creation_date = (new DateTime('-15 DAYS'))->format('Y-m-d 00:00:00');
        $response_messages = '"ISSUED", "VIEWED", "BOLETO ISSUED", "BOLETO VIEWED", "PENDING", "PENDING CONFIRMATION", "AUTHORIZED", "ENROLLED"';

        $sql = 'SELECT * FROM ' . $maxipago_transactions .
            ' WHERE `created_at` > "' . $minimum_creation_date . '"' .
            ' AND `response_message` in (' . $response_messages . ')';

        $data = $this->db->query($sql);

        return $data->num_rows > 0 ? $data->rows : null;
    }

    private function getSyncSuccessText($orders_updated)
    {
        $messages = '<li>' . sprintf($this->language->get('text_success_sync'), count($orders_updated)) . '</li>';
        $messages .= '<li>' . $this->language->get('text_sync_orders') . implode(', ', $orders_updated) . '</li>';

        return '<ul>' . $messages . '</ul>';
    }

    private function synchronizeManuallyRefundedOrders()
    {
        $refundedOrders = array();

        $minimum_creation_date = (new DateTime('-15 DAYS'))->format('Y-m-d 00:00:00');
        $refund_order_status_id = $this->config->get('payment_maxipago_order_status_refunded');
        $canceled_order_status_id = $this->config->get('payment_maxipago_order_status_cancelled');

        $sql = 'SELECT * FROM `' . DB_PREFIX . 'order`' .
            'WHERE `date_added` > "' . $minimum_creation_date . '" ' .
            'AND `payment_code` = "maxipago" ' .
            'AND `order_status_id` in (' . $refund_order_status_id . ', ' . $canceled_order_status_id . ')';

        $possibleRefundableOrders = $this->db->query($sql)->rows;

        foreach($possibleRefundableOrders as $possibleRefundableOrder)
        {
            $orderTransactionSql = 'SELECT * FROM `' . DB_PREFIX . 'maxipago_transactions` ' .
                'WHERE `id_order` = ' . $possibleRefundableOrder['order_id'] .
                ' AND `method` = "credit-card"';

            $transactionQuery = $this->db->query($orderTransactionSql);

            if($transactionQuery->num_rows > 0)
            {
                $transaction = $transactionQuery->row;

                // It the status of the order is refund/cancel,
                // but the transaction was already refunded/voided,
                // ignore this order
                if($transaction['response_message'] == 'REFUNDED' || $transaction['response_message'] == 'VOIDED')
                    continue;

                if($this->model_extension_payment_maxipago->reversePayment($possibleRefundableOrder))
                {
                    array_push($refundedOrders, $possibleRefundableOrder['order_id']);
                }
            }
        }

        return $refundedOrders;
    }

    private function captureManuallyCapturedOrders()
    {
        $capturedOrders = array();

        $minimum_creation_date = (new DateTime('-15 DAYS'))->format('Y-m-d 00:00:00');
        $refund_order_status_id = $this->config->get('payment_maxipago_order_status_approved');

        $sql = 'SELECT * FROM `' . DB_PREFIX . 'order`' .
            'WHERE `date_added` > "' . $minimum_creation_date . '" ' .
            'AND `payment_code` = "maxipago" ' .
            'AND `order_status_id` = ' . $refund_order_status_id;

        $possibleCapturableOrders = $this->db->query($sql)->rows;

        foreach($possibleCapturableOrders as $possibleCapturableOrder)
        {
            $orderTransactionSql = 'SELECT * FROM `' . DB_PREFIX . 'maxipago_transactions` ' .
                'WHERE `id_order` = ' . $possibleCapturableOrder['order_id'] .
                ' AND `method` = "credit-card"';

            $transactionQuery = $this->db->query($orderTransactionSql);

            if($transactionQuery->num_rows > 0)
            {
                $transaction = $transactionQuery->row;

                // It the status of the order is refund/cancel,
                // but the transaction was already refunded/voided,
                // ignore this order
                if($transaction['response_message'] == 'AUTHORIZED' || $transaction['response_message'] == 'FRAUD APPROVED')
                {
                    $captured = $this->model_extension_payment_maxipago->capturePayment($possibleCapturableOrder);

                    if($captured)
                    {
                        array_push($capturedOrders, $possibleCapturableOrder['order_id']);
                    }
                }
            }
        }

        return $capturedOrders;
    }

    /**
     * Method that confirms the payment and create a comment with the payment information
     */
    public function confirm()
    {
        $response = array(
            'error' => true,
            'message' => 'Wrong payment method code'
        );

        if ($this->session->data['payment_method']['code'] == 'maxipago') {

            $this->load->model('extension/payment/maxipago');

            $recurring = isset($this->request->post['recurring']) ? $this->request->post['recurring'] : null;
            if($recurring)
                $response = $this->model_extension_payment_maxipago->confirmRecurringPayments();
            else
                $response = $this->model_extension_payment_maxipago->confirmPayment();

            $this->finishCurrentOrder();
        }

        $response = json_encode($response);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput($response);
    }

    protected function finishCurrentOrder()
    {
        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);
        }
    }

    public function deleteOrder($route, $data = array())
    {
        if (!isset($this->request->get['order_id']))
        {
            $response = json_encode(array(
                'error' => '[maxiPago] Request doesn\'t cointain and order id'
            ));

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput($response);
            return $this->response;
        }

        $this->load->model('checkout/order');
        $order_id = $this->request->get['order_id'];

        $order = $this->model_checkout_order->getOrder($order_id);

        if($order['payment_code'] != 'maxipago')
            return;

        $this->load->model('extension/payment/maxipago');

        $transactions = $this->model_extension_payment_maxipago->getOrderTransactions($order_id);

        if(!$transactions || empty($transactions))
            return;

        foreach($transactions as $transaction)
        {
            $maxipago_client = $this->model_extension_payment_maxipago->getMaxipago();

            $param = array(
                'orderID' => $transaction['orderID']
            );

            if($transaction['recurring'])
            {
                $maxipago_client->cancelRecurring($param);

                $response = $maxipago_client->getResult();
                $this->model_extension_payment_maxipago->log($maxipago_client->xmlRequest);
                $this->model_extension_payment_maxipago->log($maxipago_client->xmlResponse);

                $this->model_extension_payment_maxipago->updateDeletedOrderRecurringTransactionStatus($transaction['orderID'], $response);
            } else {
                $status = '';

                if ($transaction['responseMessage'] == 'AUTHORIZED' || $transaction['responseMessage'] == 'APPROVED') {
                    $status = 'VOIDED';
                    $maxipago_client->creditCardVoid($param);
                }
                if ($transaction['responseMessage'] == 'CAPTURED') {
                    $status = 'REFUNDED';
                    $param['referenceNum'] = $transaction['response']->referenceNum;
                    $param['chargeTotal'] = $order['total'];
                    $maxipago_client->creditCardRefund($param);
                }

                $response = $maxipago_client->getResult();
                $this->model_extension_payment_maxipago->log($maxipago_client->xmlRequest);
                $this->model_extension_payment_maxipago->log($maxipago_client->xmlResponse);

                if(!$maxipago_client->isErrorResponse() && $maxipago_client->getResponseCode() == 0)
                {
                    $this->model_extension_payment_maxipago->updateDeletedOrderTransactionStatus($transaction['orderID'], $response, $status);
                } else
                {
                    $error = sprintf('There was an error refunding the payment - Error: %s', $maxipago_client->getMessage());

                    $response = json_encode(array(
                        'error' => $error
                    ));

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput($response);
                    return $this->response;
                }
            }
        }

        return false;
    }

    /**
     * @param $route
     * @param $orders
     */
    public function change($route, $data = array())
    {
        $this->load->model('extension/payment/maxipago');
        $this->load->language('extension/payment/maxipago');
        $this->load->model('checkout/order');

        $order_id = $this->model_extension_payment_maxipago->getRequest('order_id');
        $order_status_id = $this->model_extension_payment_maxipago->getPost('order_status_id');

        if ($order_id && $order_status_id) {
            $order_info = $this->model_checkout_order->getOrder($order_id);

            //If payment method is equal to maxipago and the status is changed
            if (
                $order_info['payment_code'] == 'maxipago'
                && $order_info['order_status_id'] != $order_status_id
            ) {
                if (
                    $this->config->get('maxipago_cc_order_reverse')
                    && $order_status_id == $this->config->get('maxipago_order_cancelled')
                    && $this->config->get('maxipago_order_cancelled') != $this->config->get('maxipago_order_approved')
                    && $this->config->get('maxipago_order_cancelled') != $this->config->get('maxipago_order_processing')
                ) {
                    //If the order uses maxiPago! and status equals cancelled
                    $this->model_extension_payment_maxipago->voidPayment($order_info);
                } else if (
                    $order_status_id == $this->config->get('maxipago_order_refunded')
                    && $this->config->get('maxipago_order_refunded') != $this->config->get('maxipago_order_approved')
                    && $this->config->get('maxipago_order_refunded') != $this->config->get('maxipago_order_processing')
                ) {
                    //If the order uses maxiPago! and status equals approved
                    $this->model_extension_payment_maxipago->reversePayment($order_info);
                } else if (
                    $order_status_id == $this->config->get('maxipago_order_approved')
                    && $this->config->get('maxipago_order_approved') != $this->config->get('maxipago_order_cancelled')
                    && $this->config->get('maxipago_order_approved') != $this->config->get('maxipago_order_processing')
                ) {
                    //If the order uses maxiPago! and status equals approved
                    $this->model_extension_payment_maxipago->capturePayment($order_info, $order_status_id);
                }
            }
        }
    }

    public function checkForCapture($route, $data)
    {
        if(empty($data))
            return;

        $order_id = $data[0];

        if($this->isPaidWithMaxipago($order_id))
        {
            $status_processed = 15;
            $order_status_id = $data[1];

            if($order_status_id == $status_processed)
            {
                $this->load->model('extension/payment/maxipago');
                $response_message = $this->model_extension_payment_maxipago->getOrderTransactionStatus($order_id);

                if($response_message && $response_message != 'CAPTURED')
                {
                    $this->load->model('checkout/order');
                    $order = $this->model_checkout_order->getOrder($order_id);

                    $this->model_extension_payment_maxipago->capturePayment($order, $order_status_id);
                }
            }
        }
    }

    public function checkForVoid($route, $data)
    {
        if(empty($data))
            return;

        $order_id = $data[0];

        if($this->isPaidWithMaxipago($order_id))
        {
            $status_cancelled = 7;
            $status_refunded = 11;
            $order_status_id = $data[1];

            if($order_status_id == $status_cancelled || $order_status_id == $status_refunded)
            {
                $this->load->model('extension/payment/maxipago');
                $response_message = $this->model_extension_payment_maxipago->getOrderTransactionStatus($order_id);

                if($response_message && $response_message != 'VOIDED')
                {
                    if($this->canVoidOrder($order_id))
                        $this->model_extension_payment_maxipago->voidOrder($order_id);
                }
            }
        }
    }

    public function checkForRefund($route, $data)
    {
        if(empty($data))
            return;

        $order_id = $data[0];

        if($this->isPaidWithMaxipago($order_id))
        {
            $status_cancelled = 7;
            $status_refunded = 11;
            $order_status_id = $data[1];

            if($order_status_id == $status_cancelled || $order_status_id == $status_refunded)
            {
                $this->load->model('extension/payment/maxipago');
                $response_message = $this->model_extension_payment_maxipago->getOrderTransactionStatus($order_id);

                if($response_message && $response_message != 'REFUNDED')
                {
                    if($this->canRefundOrder($order_id))
                        $this->model_extension_payment_maxipago->refundOrder($order_id);
                }
            }
        }
    }

    private function isPaidWithMaxipago($order_id)
    {
        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($order_id);

        if($order)
            return $order['payment_code'] == 'maxipago';

        return false;
    }

    private function canVoidOrder($order_id)
    {
        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($order_id);

        if($order)
        {
            $can_void = false;

            $this->load->model('extension/payment/maxipago');
            $response_message = $this->model_extension_payment_maxipago->getOrderTransactionStatus($order_id);

            // if (order_is_authorized || order_was_captured_today) can void;
            if ($response_message && ($response_message == 'AUTHORIZED' || ($response_message == 'CAPTURED' && $this->orderWasMadeToday($order))))
                $can_void = true;

            return $can_void;
        }

        return false;
    }

    private function canRefundOrder($order_id)
    {
        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($order_id);

        if($order)
        {
            $can_refund = true;

            $this->load->model('extension/payment/maxipago');
            $response_message = $this->model_extension_payment_maxipago->getOrderTransactionStatus($order_id);

            // if (order_was_captured_yesterday_or_before) can refund;
            if($response_message == 'CAPTURED' && !$this->orderWasMadeToday($order))
                $can_refund = true;

            return $can_refund;
        }

        return false;
    }

    private function orderWasMadeToday($order)
    {
        $today = date('Ymd');
        $order = date('Ymd', strtotime($order['date_added']));

        return $today == $order;
    }
}
