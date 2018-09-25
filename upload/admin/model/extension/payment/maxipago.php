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
 * @property ModelCheckoutOrder model_checkout_order
 * @property Url url
 * @property Request request
 * @property Config config
 * @property DB db
 */

require_once(DIR_SYSTEM . 'library/maxipago/maxipago.php');
class ModelExtensionPaymentMaxipago extends Model
{
    protected $_maxipago;
    const MAXIPAGO_CODE = 'maxipago';

    protected $_responseCodes = array(
        '0' => 'Pagamento Aprovado',
        '1' => 'Pagamento Reprovado',
        '2' => 'Pagamento Reprovado',
        '5' => 'Pagamento em análise',
        '1022' => 'Ocorreu um erro com a finalizadora, entre em contato com nossa equipe',
        '1024' => 'Erros, dados enviados inválidos, entre em contato com nossa equipe',
        '1025' => 'Erro nas credenciais de envio, entre em contato com nossa equipe',
        '2048' => 'Erro interno, entre em contato com nossa equipe',
        '4097' => 'Erro de tempo de execução, entre em contato com nossa equipe'
    );

    protected $_transactionStates = array(
        '1' => 'In Progress',
        '3' => 'Captured',
        '6' => 'Authorized',
        '7' => 'Declined',
        '9' => 'Voided',
        '10' => 'Paid',
        '22' => 'Boleto Issued',
        '34' => 'Boleto Viewed',
        '35' => 'Boleto Underpaid',
        '36' => 'Boleto Overpaid',

        '4' => 'Pending Capture',
        '5' => 'Pending Authorization',
        '8' => 'Reversed',
        '11' => 'Pending Confirmation',
        '12' => 'Pending Review (check with Support)',
        '13' => 'Pending Reversion',
        '14' => 'Pending Capture (retrial)',
        '16' => 'Pending Reversal',
        '18' => 'Pending Void',
        '19' => 'Pending Void (retrial)',
        '29' => 'Pending Authentication',
        '30' => 'Authenticated',
        '31' => 'Pending Reversal (retrial)',
        '32' => 'Authentication in progress',
        '33' => 'Submitted Authentication',
        '38' => 'File submission pending Reversal',
        '44' => 'Fraud Approved',
        '45' => 'Fraud Declined',
        '46' => 'Fraud Review'
    );

    /**
     * maxiPago! lib Object
     * @return MaxiPagoPayment
     */
    public function getMaxipago()
    {
        if (!$this->_maxipago) {
            $merchantId = $this->config->get('payment_maxipago_store_id');
            $sellerKey = $this->config->get('payment_maxipago_store_key');
            if ($merchantId && $sellerKey) {
                $environment = ($this->config->get('payment_maxipago_environment') == 'test') ? 'TEST' : 'LIVE';
                $this->_maxipago = new maxiPagoPayment();
                $this->_maxipago->setCredentials($merchantId, $sellerKey);
                $this->_maxipago->setEnvironment($environment);
            }
        }

        return $this->_maxipago;
    }

    /**
     * Sync orders
     * @param $transaction
     */
    public function sync($transaction)
    {
        $updated = false;
        $return = json_decode($transaction['return']);
        $search = array(
            'orderID' => $return->orderID
        );

        $this->getMaxipago()->pullReport($search);
        $response = $this->getMaxipago()->getReportResult();

        $state = isset($response[0]['transactionState']) ? $response[0]['transactionState'] : null;

        $storeOrderId = $transaction['id_order'];
        if ($state && $storeOrderId) {
            $comment = $this->language->get('comment_updated_order') . ' ' . $state;

            if ($state == '10' || $state == '3' || $state == '44' || $state == '36') {
                $updated = $storeOrderId;
                $this->_addOrderHistory($storeOrderId, $this->config->get('payment_maxipago_order_status_approved'), $comment);
            } else if ($state == '45' || $state == '7' || $state == '9') {
                $updated = $storeOrderId;
                $this->_addOrderHistory($storeOrderId, $this->config->get('payment_maxipago_order_status_cancelled'), $comment);
            }

            $this->_updateTransactionState($storeOrderId, $return, $response);

        }

        return $updated;
    }

    /**
     * Refund and order to maxiPago!
     *
     * @param $order_info
     * @throws Exception
     * @return boolean
     */
    public function reverse($order_info)
    {
        try {
            $order_id = $order_info['order_id'];
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `id_order` = "' . $order_id . '"
                    AND `method` = "credit-card";';

            $date = date('Ymd', strtotime($order_info['date_added']));
            $transaction = $this->db->query($sql)->row;
            $transactionAlreadyCanceled = in_array($transaction['response_message'], array(
               'VOIDED', 'CANCELLED', 'CANCELED', 'FRAUD'
            ));

            if (!empty($transaction) && !$transactionAlreadyCanceled) {
                $request = json_decode($transaction['request']);
                $response = json_decode($transaction['return']);

                $data = array(
                    'orderID' => $response->orderID,
                    'referenceNum' => $response->referenceNum,
                    'chargeTotal' => $request->chargeTotal,
                );

                if($date == date('Ymd'))
                {
                    $transaction_type = 'voided';
                    $data = array(
                        'transactionID' => $response->transactionID
                    );
                    $this->getMaxipago()->creditCardVoid($data);
                    $this->_updateTransactionState($order_id, array(), array(), 'VOIDED');
                } else
                {
                    $transaction_type = 'refunded';
                    $this->getMaxipago()->creditCardRefund($data);
                    $this->_updateTransactionState($order_id, array(), array(), 'REFUNDED');
                }

                $this->log($this->getMaxipago()->xmlRequest);
                $this->log($this->getMaxipago()->xmlResponse);

                $this->_saveTransaction($transaction_type, $data, $this->getMaxipago()->response, null, false);
                return true;
            }

        } catch (Exception $e) {
            return false;
        }

        return false;
    }
    
    public function capture($order_info)
    {
        try {
            $order_id = $order_info['order_id'];
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `id_order` = "' . $order_id . '"
                    AND `method` = "credit-card";';

            $transaction = $this->db->query($sql)->row;

            if (!empty($transaction) && $transaction['response_message'] != 'CAPTURED') {

                $request = json_decode($transaction['request']);
                $response = json_decode($transaction['return']);

                $data = array(
                    'orderID' => $response->orderID,
                    'referenceNum' => $response->referenceNum,
                    'chargeTotal' => $request->chargeTotal,
                );
                $this->getMaxipago()->creditCardCapture($data);
                $this->_saveTransaction('capture', $data, $this->getMaxipago()->response, null, false);
                $this->_updateTransactionState($order_id);

                return true;
            }

        } catch (Exception $e) {
            $this->log('Error capturing order ' . $order_id . ': ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Update order status
     *
     * @param $order_id
     * @param $order_status_id
     * @param $comment
     * @param int $notify
     *
     * @return void
     */
    protected function _addOrderHistory($order_id, $order_status_id, $comment, $notify = 1)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . (int)$order_status_id . "', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . (int)$order_id . "', `order_status_id` = '" . (int)$order_status_id . "', `notify` = '" . $notify . "', `comment` = '" . $this->db->escape($comment) . "', `date_added` = NOW()");
    }

    /**
     * Update Transaction State to maxipago tables
     * @param $id_order
     * @param array $return
     * @param array $response
     * @return void
     */
    protected function _updateTransactionState($id_order, $return = array(), $response = array(), $responseMessage = null)
    {
        $this->load->language('extension/payment/maxipago');
        $this->load->model('extension/payment/maxipago');

        if($responseMessage) {
            $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_transactions
                    SET `response_message` = \'' . strtoupper($responseMessage) . '\'
                    WHERE `id_order` = "' . $id_order . '";';

            $this->db->query($sql);
        } else {

            if (empty($return)) {
                $sql = 'SELECT *
                        FROM ' . DB_PREFIX . 'maxipago_transactions
                        WHERE `id_order` = "' . $id_order . '" 
                        ';
                $transaction = $this->db->query($sql)->row;
                if (!empty($transaction)) {

                    $return = json_decode($transaction['return']);

                    $search = array(
                        'orderID' => $return->orderID
                    );

                    $this->getMaxipago()->pullReport($search);
                    $response = $this->getMaxipago()->getReportResult();

                    if (!empty($response)) {
                        $responseCode = isset($response[0]['responseCode']) ? $response[0]['responseCode'] : $return->responseCode;
                        if (!property_exists($return, 'originalResponseCode')) {
                            $return->originalResponseCode = $return->responseCode;
                        }
                        $return->responseCode = $responseCode;

                        if (!property_exists($return, 'originalResponseMessage')) {
                            $return->originalResponseMessage = $return->responseMessage;
                        }
                        $state = isset($response[0]['transactionState']) ? $response[0]['transactionState'] : null;
                        $responseMessage = (array_key_exists($state, $this->_transactionStates)) ? $this->_transactionStates[$state] : $return->responseMessage;
                        $return->responseMessage = $responseMessage;
                        $return->transactionState = $state;
                        $transaction['response_message'] = $responseMessage;

                        $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_transactions 
                               SET `response_message` = \'' . strtoupper($responseMessage) . '\',
                                   `return` = \'' . json_encode($return) . '\'
                             WHERE `id_order` = "' . $id_order . '";
                            ';

                        $this->db->query($sql);

                    }

                }
            }
        }
    }

    /**
     * Save at the DB the data of the transaction and the Boleto URL when the payment is made with boleto
     *
     * @param $method
     * @param $request
     * @param $return
     * @param null $transactionUrl
     * @param boolean $hasOrder
     */
    protected function _saveTransaction($method, $request, $return, $transactionUrl = null, $hasOrder = true)
    {
        $onlineDebitUrl = null;
        $boletoUrl = null;

        if ($transactionUrl) {
            if ($method == 'eft') {
                $onlineDebitUrl = $transactionUrl;
            } else if ($method == 'ticket') {
                $boletoUrl = $transactionUrl;
            }
        }

        if (is_object($request) || is_array($request)) {

            if (isset($request['number'])) {
                $request['number'] = substr($request['number'], 0, 6) . 'XXXXXX' . substr($request['number'], -4, 4);
            }

            $request = json_encode($request);
        }

        $responseMessage = null;
        if (is_object($return) || is_array($return)) {
            $responseMessage = isset($return['responseMessage']) ? $return['responseMessage'] : null;
            $return = json_encode($return);
        }

        $order_id = $this->session->data['order_id'];
        if (! $hasOrder) {
            $order_id = 0;
        }

        $request = $this->db->escape($request);
        $return = $this->db->escape($return);
        $responseMessage = $this->db->escape($responseMessage);

        $sql = 'INSERT INTO `' . DB_PREFIX . 'maxipago_transactions` 
                    (`id_order`, `boleto_url`, `online_debit_url`, `method`, `request`, `return`, `response_message`)
                VALUES
                    ("' . $order_id . '", "' . $boletoUrl . '",  "' . $onlineDebitUrl . '", "' . $method . '" ,"' . $request . '", "' . $return . '", "' . $responseMessage . '" )
                ';

        $this->db->query($sql);
    }

    /**
     * @param $data
     * @param int $step
     */
    public function log($data, $step = 6)
    {
        if ($this->config->get('payment_maxipago_log')) {
            $backtrace = debug_backtrace();
            $log = new Log('maxipago.log');
            $log->write('(' . $backtrace[$step]['class'] . '::' . $backtrace[$step]['function'] . ') - ' . $data);
        }
    }
}