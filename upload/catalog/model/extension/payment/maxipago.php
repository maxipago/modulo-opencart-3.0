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
 */

require_once(DIR_SYSTEM . 'library/maxipago/maxipago.php');
class ModelExtensionPaymentMaxipago extends Model
{
    protected $_maxipago;

    const DEFAULT_IP = '127.0.0.1';
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
     * @return MaxiPago
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
     * OpenCart 3.x exigency!
     * @return bool is maxiPago! enabled for payment when cart->hasRecurringProducts?
     */
    public function recurringPayments() {
        $recurring_products = $this->cart->getRecurringProducts();

        foreach($recurring_products as $recurring_product)
        {
            $has_trial = $recurring_product['recurring']['trial'] == "1";
            $trial_is_paid = ((float) $recurring_product['recurring']['trial_price']) > 0;


            /*
             * maxiPago! doesn't support recurring payments with paid trials
             */
            if($has_trial && $trial_is_paid)
                return false;
        }


        return true;
    }

    public function creditCardMethod($order_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_credit_card_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $reference_number = $order_data['order_id'];
        $ip_address = $order_data['ip'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $request_data = array(
            'userAgent' => $user_agent,
            'referenceNum' => $reference_number,
            'ipAddress' => $ip_address
        );

        $transaction_detail_data = $this->getCreditCardTransactionDetailData($order_data);
        $fraud_check_data = $this->getFraudCheckData($order_data['order_id']);
        $billing_data = $this->getBillingData($order_data, 'billing');
        $shipping_data = $this->getShippingData($order_data, 'shipping');
        $type_data = $this->getTypeData($order_data);

        $charge_total = $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value'], false);
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'], false);
        $currency_code = $order_data['currency_code'];
        $number_of_installments = $transaction_detail_data['creditCardData']['installments'];
        $charge_interest = 'N';

        $max_installments_without_interest = $this->config->get('payment_maxipago_credit_card_installments_without_interest');
        $interest_rate = $this->config->get('payment_maxipago_credit_card_interest_rate');

        if ($interest_rate && $number_of_installments > $max_installments_without_interest) {
            $charge_interest = 'Y';
            $charge_total = $this->getTotalByInstallments($charge_total, $number_of_installments, $interest_rate);
        }

        $payment_data = array(
            'chargeTotal' => $charge_total,
            'shippingTotal' => $shipping_total,
            'currencyCode' => $currency_code,
            'numberOfInstallments' => $number_of_installments,
            'chargeInterest' => $charge_interest
        );

        $request_data = array_merge($request_data, $fraud_check_data, $billing_data, $shipping_data, $type_data, $transaction_detail_data, $payment_data);

        $soft_descriptor = $this->config->get('payment_maxipago_credit_card_soft_descriptor');
        if($soft_descriptor)
            $request_data['softDescriptor'] = $soft_descriptor;

        $this->creditCardTransaction($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;
        $this->_saveTransaction('credit-card', $request_data, $response);
        return $response;
    }

    public function recurringMethod($order_data, $recurring_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_credit_card_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $reference_number = $order_data['order_id'];
        $ip_address = $order_data['ip'];
        $currency_code = $order_data['currency_code'];

        $charge_total = (float) $recurring_data['recurring']['price'];
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'],false);
        $formated_charge_total = number_format($charge_total, 2, '.', '');

        $request_data = array(
            'referenceNum' => $reference_number,
            'ipAddress' => $ip_address,
            'currencyCode' => $currency_code,
            'chargeTotal' => $formated_charge_total,
            'shippingTotal' => $shipping_total
        );

        $transaction_detail_data = $this->getCreditCardTransactionDetailData($order_data);
        $recurrency_data = $this->getRecurrencyData($order_data, $recurring_data['recurring']);
        $billing_data = $this->getBillingData($order_data);
        $shipping_data = $this->getShippingData($order_data);

        $type_data = $this->getTypeData($order_data);
        $request_data['customerIdExt'] = $type_data['customerIdExt'];

        $request_data = array_merge($request_data, $transaction_detail_data, $recurrency_data, $billing_data, $shipping_data);

        $this->getMaxipago()->createRecurring($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;
        $this->_saveRecurringTransaction($order_data['order_id'], $recurring_data['recurring']['order_recurring_id'], $request_data, $response);
        return $response;
    }

    public function debitCardMethod($order_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_debit_card_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $card_data = array(
            'brand' => $this->getPost('brand'),
            'number' => $this->getPost('number'),
            'cvv' => $this->getPost('cvv'),
            'owner' => $this->getPost('owner'),
            'document' => $this->getPost('document'),
            'expiry' => array(
                'month' => $this->getPost('expiry_month'),
                'year' => $this->getPost('expiry_year')
            )
        );

        $processor_id = $this->config->get('payment_maxipago_debit_card_' . $card_data['brand'] . '_processor');
        $reference_number = $order_data['order_id'];
        $ip_address = $order_data['ip'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $request_data = array(
            'processorID' => $processor_id,
            'referenceNum' => $reference_number,
            'ipAddress' => $ip_address,
            'userAgent' => $user_agent
        );

        $billing_data = $this->getBillingData($order_data);
        $shipping_data = $this->getShippingData($order_data);
        $type_data = $this->getTypeData($order_data);

        $transaction_detail_data = array(
            'number' => $card_data['number'],
            'expMonth' => $card_data['expiry']['month'],
            'expYear' => $card_data['expiry']['year'],
            'cvvNumber' => $card_data['cvv']
        );

        $charge_total = $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value'], false);
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'], false);
        $iata_fee = '0.00'; // TODO: where the hell do i get that?
        $currency_code = $order_data['currency_code'];

        $payment_data = array(
            'chargeTotal' => $charge_total,
            'shippingTotal' => $shipping_total,
            'iataFee' => $iata_fee,
            'currencyCode' => $currency_code
        );

        $mpi_processor = $this->config->get('payment_maxipago_debit_card_mpi_processor');
        $failure_action = $this->config->get('payment_maxipago_debit_card_failure_action');

        $use_3ds_data = array(
            'mpiProcessorID' => $mpi_processor,
            'onFailure' => $failure_action
        );

        $request_data = array_merge($request_data, $billing_data, $shipping_data, $transaction_detail_data, $payment_data, $use_3ds_data, $type_data);

        $soft_descriptor = $this->config->get('payment_maxipago_debit_card_soft_descriptor');
        if($soft_descriptor)
            $request_data['softDescriptor'] = $soft_descriptor;

        $this->getMaxipago()->saleDebitCard3DS($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;
        $url = isset($response['authenticationURL']) ? $response['authenticationURL'] : null;
        $this->_saveTransaction('debit-card', $request_data, $response, $url);
        return $response;
    }

    public function eftMethod($order_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_eft_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $eft_data = array(
            'bank' => $this->getPost('bank'),
            'document' => $this->getPost('document')
        );

        $processor_id = $eft_data['bank'];
        $reference_number = $order_data['order_id'];
        $ip_address = $order_data['ip'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $request_data = array(
            'processorID' => $processor_id,
            'referenceNum' => $reference_number,
            'ipAddress' => $ip_address,
            'userAgent' => $user_agent
        );

        $billing_data = $this->getBillingData($order_data);
        $shipping_data = $this->getShippingData($order_data);
        $type_data = $this->getTypeData($order_data);

        $charge_total = $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value'], false);
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'],false);
        $currency_code = $order_data['currency_code'];

        $payment_data = array(
            'chargeTotal' => $charge_total,
            'shippingTotal' => $shipping_total,
            'currencyCode' => $currency_code
        );

        $request_data = array_merge($request_data, $billing_data, $shipping_data, $type_data, $payment_data);

        $this->getMaxipago()->onlineDebitSale($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;

        $url = isset($response['onlineDebitUrl']) ? $response['onlineDebitUrl'] : null;
        $this->_saveTransaction('eft', $request_data, $response, $url);
        return $response;
    }

    public function invoiceMethod($order_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_invoice_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $invoice_data = array(
            'document' => $this->getPost('document')
        );

        $reference_number = $order_data['order_id'];
        $environment = $this->config->get('payment_maxipago_environment');
        $invoice_bank = ($environment == 'test') ? 12 : $this->config->get('payment_maxipago_invoice_bank');
        $ip_address = $order_data['ip'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $request_data = array(
            'referenceNum' => $reference_number,
            'processorID' => $invoice_bank,
            'ipAddress' => $ip_address,
            'userAgent' => $user_agent
        );

        $type_data = $this->getTypeData($order_data);
        $request_data['customerIdExt'] = $type_data['customerIdExt'];

        $billing_data = $this->getBillingData($order_data);
        $shipping_data = $this->getShippingData($order_data);

        $charge_total = $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value'], false);
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'],false);
        $days_to_pay = $this->config->get('payment_maxipago_invoice_days_to_pay');
        $instructions = $this->config->get('payment_maxipago_invoice_instructions');

        $date = new DateTime();
        $date->modify('+' . $days_to_pay . ' days');
        $expiration_date = $date->format('Y-m-d');

        $payment_data = array(
            'chargeTotal' => $charge_total,
            'shippingTotal' => $shipping_total,
            'number' => $reference_number,
            'expirationDate' => $expiration_date,
            'instructions' => $instructions
        );

        $request_data = array_merge($request_data, $billing_data, $shipping_data, $payment_data);

        $this->getMaxipago()->boletoSale($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;

        $url = isset($response['boletoUrl']) ? $response['boletoUrl'] : null;
        $this->_saveTransaction('invoice', $request_data, $response, $url);
        return $response;
    }

    public function redepayMethod($order_data)
    {
        $method_is_enabled = $this->config->get('payment_maxipago_redepay_enabled');

        if(!$method_is_enabled) {
            $this->load->language('extension/payment/maxipago');
            throw new Exception($this->language->get('exception_method_not_allowed'));
        }

        $redepay_data = array(
            'document' => $this->getPost('document')
        );

        $reference_number = $order_data['order_id'];
        // RedePay processor is always '18'
        $redepay_processor = '18';
        $ip_address = $order_data['ip'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $request_data = array(
            'referenceNum' => $reference_number,
            'processorID' => $redepay_processor,
            'ipAddress' => $ip_address,
            'userAgent' => $user_agent,
            'parametersURL' => 'type=redepay'
        );

        $billing_data = $this->getBillingData($order_data);
        $shipping_data = $this->getShippingData($order_data);
        $type_data = $this->getTypeData($order_data);
        $product_data = $this->getProductsData($order_data);

        $charge_total = $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value'], false);
        $shipping_total = $this->currency->format($this->getOrderShippingValue($order_data['order_id']), $order_data['currency_code'], $order_data['currency_value'],false);

        $payment_data = array(
            'chargeTotal' => $charge_total,
            'shippingTotal' => $shipping_total
        );

        $request_data = array_merge($request_data, $billing_data, $shipping_data, $type_data, $product_data, $payment_data);

        $this->getMaxipago()->redepay($request_data);
        $this->logXmlIfAllowed();

        $response = $this->getMaxipago()->response;

        $url = isset($response['authenticationURL']) ? $response['authenticationURL'] : null;
        $this->_saveTransaction('redepay', $request_data, $response, $url);
        return $response;
    }

    private function getCreditCardTransactionDetailData($order_data)
    {
        $is_new = $this->getPost('new') == 'true';
        $credit_card_data = $is_new ? $this->getUnknownCreditCardData() : $this->getKnownCreditCardData();

        if($credit_card_data['new'])
        {
            $card_tokenization_enabled = $this->config->get('payment_maxipago_credit_card_allow_save') == 1;
            $user_requested_card_save = $credit_card_data['save'] == null ? false : ($credit_card_data['save'] == 'true');
            if($card_tokenization_enabled && $user_requested_card_save)
                $this->saveCard($order_data);
        }

        if($credit_card_data['new'])
        {
            $processor_id = $this->config->get('payment_maxipago_credit_card_' . $credit_card_data['brand'] . '_processor');

            $customer_id = $order_data['customer_id'];

            return array(
                'number' => $credit_card_data['number'],
                'creditCardNumber' => $credit_card_data['number'],
                'expMonth' => $credit_card_data['expiry']['month'],
                'expirationMonth' => $credit_card_data['expiry']['month'],
                'expYear' => $credit_card_data['expiry']['year'],
                'expirationYear' => $credit_card_data['expiry']['year'],
                'cvv' => $credit_card_data['cvv'],
                'cvvNumber' => $credit_card_data['cvv'],
                'customerId' => $customer_id,
                'processorID' => $processor_id,
                'creditCardData' => $credit_card_data
            );
        } else
        {
            $card_token = $this->getCardToken($order_data['customer_id'], $credit_card_data['credit_card']);

            $token = $card_token['token'];
            $customer_id = $card_token['id_customer_maxipago'];
            $processor_id =  $this->config->get('payment_maxipago_credit_card_' . $card_token['brand'] . '_processor');

            return array(
                'token' => $token,
                'expMonth' => $credit_card_data['expiry']['month'],
                'expirationMonth' => $credit_card_data['expiry']['month'],
                'expYear' => $credit_card_data['expiry']['year'],
                'expirationYear' => $credit_card_data['expiry']['year'],
                'cvv' => $credit_card_data['cvv'],
                'cvvNumber' => $credit_card_data['cvv'],
                'customerId' => $customer_id,
                'processorID' => $processor_id,
                'creditCardData' => $credit_card_data
            );
        }
    }

    private function getUnknownCreditCardData()
    {
        return array(
            'new' => true,
            'brand' => $this->getPost('brand'),
            'number' => $this->getPost('number'),
            'cvv' => $this->getPost('cvv'),
            'owner' => $this->getPost('owner'),
            'document' => $this->getPost('document'),
            'expiry' => array(
                'month' => $this->getPost('expiry_month'),
                'year' => $this->getPost('expiry_year')
            ),
            'save' => $this->getPost('save'),
            'installments' => $this->getPost('installments')
        );
    }

    private function getKnownCreditCardData()
    {
        return array(
            'new' => false,
            'credit_card' => $this->getPost('credit_card'),
            'cvv' => $this->getPost('cvv'),
            'installments' => $this->getPost('installments'),
            'document' => $this->getPost('document'),
            'expiry' => array(
                'month' => $this->getPost('expiry_month'),
                'year' => $this->getPost('expiry_year')
            )
        );
    }

    private function getRecurrencyData($order_data, $recurring_data)
    {
        $has_trial = $recurring_data['trial'] == "1";
        $trial_is_paid = $has_trial ? ((float) $recurring_data['trial_price']) > 0 : false;

        if($has_trial)
        {
            $frequencyFromMaxiPagoToOpenCart = array(
                'daily' => 'day',
                'weekly' => 'week',
                'monthly' => 'month'
            );

            /*
             * Theoretically, this exception will never be thrown!
             * If any recurring product has trial and the trial is paid,
             * maxiPago doesn't show as an payment option.
             * (see ModelExtensionPaymentMaxipago::recurringPayments())
             * This only adds an extra security layer.
             */
            if($trial_is_paid)
                throw new Exception($this->language->get('exception_recurrency_not_supported'));

            $frequency = $this->getFrequencyFromCycle($recurring_data['cycle'], $recurring_data['frequency']);
            $period = $this->getPeriodFromFrequency($recurring_data['frequency']);

            $trial_frequency = $this->getFrequencyFromCycle($recurring_data['trial_cycle'], $recurring_data['trial_frequency']);
            $trial_period = $this->getPeriodFromFrequency($recurring_data['trial_frequency']);

            $start_date = new DateTime('today');
            $start_date->modify('+' . $trial_frequency . ' ' . $frequencyFromMaxiPagoToOpenCart[$trial_period]);
            $start_date = $start_date->format('Y-m-d');

            $installments = $this->getRecurringInstallments($recurring_data['duration'], $period, $frequency);
            $failure_threshold = $installments > 99 ? 99 : $installments;

            return array(
                'startDate' => $start_date,
                'frequency' => $frequency,
                'period' => $period,
                'installments' => $installments,
                'failureThreshold' => $failure_threshold
            );
        } else
        {
            $start_date = new DateTime('today');
            $start_date = $start_date->format('Y-m-d');

            $frequency = $this->getFrequencyFromCycle($recurring_data['cycle'], $recurring_data['frequency']);
            $period = $this->getPeriodFromFrequency($recurring_data['frequency']);

            $installments = $this->getRecurringInstallments($recurring_data['duration'], $period, $frequency);
            $failure_threshold = $installments > 99 ? 99 : $installments;

            return array(
                'startDate' => $start_date,
                'frequency' => $frequency,
                'period' => $period,
                'installments' => $installments,
                'failureThreshold' => $failure_threshold
            );
        }
    }

    private function getFrequencyFromCycle($cycle, $frequency)
    {
        $multiplier = 1;

        if($frequency == 'semi_month')
            $multiplier = 15;

        if($frequency == 'year')
            $multiplier = 12;

        return $cycle * $multiplier;
    }

    private function getPeriodFromFrequency($frequency)
    {
        $frequencyFromOpenCartToMaxiPago = array(
            'day' => 'daily',
            'week' => 'weekly',
            'semi_month' => 'daily',
            'month' => 'monthly',
            'year' => 'monthly'
        );

        if(isset($frequencyFromOpenCartToMaxiPago[$frequency]))
            return $frequencyFromOpenCartToMaxiPago[$frequency];

        return 'monthly';
    }

    private function getRecurringInstallments($duration, $period, $frequency)
    {
        if($duration > 0)
            return $duration;

        // 1825 days is the same as 5 years
        if($period == 'daily')
            return (int) (1825 / $frequency);

        // 260 weeks is the same as 5 years
        if($period == 'weekly')
            return (int) (260 / $frequency);

        // 60 months is the same as 5 years
        if($period == 'monthly')
            return (int) (60 / $frequency);

        return 0; // Shall thrown error for invalid
    }

    private function creditCardTransaction($request_data)
    {
        $is_authorization = $this->config->get('payment_maxipago_credit_card_processing_type') == 'auth';
        $is_using_3ds = $this->config->get('payment_maxipago_credit_card_use_3ds');
        if($is_using_3ds) {

            $request_data = array_merge($request_data, $this->get3DSData());

            if($is_authorization)
                $this->getMaxipago()->authCreditCard3DS($request_data);
            else
                $this->getMaxipago()->saleCreditCard3DS($request_data);
        }
        else {
            if($is_authorization)
                $this->getMaxipago()->creditCardAuth($request_data);
            else
                $this->getMaxipago()->creditCardSale($request_data);
        }
    }

    private function get3DSData()
    {
        $mpi_processor = $this->config->get('payment_maxipago_credit_card_mpi_processor');
        $on_fail_action = $this->config->get('payment_maxipago_credit_card_failure_action');

        return array(
            $mpi_processor = $mpi_processor,
            $on_fail_action = $on_fail_action
        );
    }

    private function getFraudCheckData($order_id)
    {
        $fraud_data = array(
            'fraudCheck' => $this->config->get('payment_maxipago_credit_card_fraud_check') ? 'Y' : 'N'
        );

        if($fraud_data['fraudCheck'] == 'N' || $this->config->get('payment_maxipago_credit_card_processing_type') == 'sale')
            return $fraud_data;

        return array_merge($fraud_data, $this->getFraudProcessorData($order_id));
    }

    private function getFraudProcessorData($order_id)
    {
        if($this->config->get('payment_maxipago_credit_card_fraud_processor')) {
            $processor_data = array(
                'fraudProcessorID' => $this->config->get('payment_maxipago_credit_card_fraud_processor'),
                'voidOnHighRisk' => $this->config->get('payment_maxipago_credit_card_auto_void') ? 'Y' : 'N',
                'captureOnLowRisk' => $this->config->get('payment_maxipago_credit_card_auto_capture') ? 'Y' : 'N',
                'websiteId' => 'DEFAULT'
            );

            if($processor_data['fraudProcessorID'] == '98') {
                $sessionId = $this->session->getId();
                $processor_data['fraudToken'] = $sessionId;
            } else if ($processor_data['fraudProcessorID'] == '99') {
                $session_id = session_id();
                $store_id = $this->config->get('payment_maxipago_store_id');
                $store_secret = $this->config->get('payment_maxipago_store_secret');
                $hash = hash_hmac('md5', $store_id . '*' . $session_id, $store_secret);
                $processor_data['fraudToken'] = $hash;
            }

            return $processor_data;
        } else {
            $session_id = session_id();
            $store_id = $this->config->get('payment_maxipago_store_id');
            $store_secret = $this->config->get('payment_maxipago_store_secret');
            $hash = hash_hmac('md5', $store_id . '*' . $session_id, $store_secret);
            $processor_data['fraudToken'] = $hash;

            return array(
                'fraudProcessorID' => '99',
                'voidOnHighRisk' => 'Y',
                'captureOnLowRisk' => 'N',
                'websiteId' => 'DEFAULT',
                'fraudToken' => $hash
            );
        }
    }

    private function getBillingData($order_data)
    {
        $address = $this->_getAddress($order_data, 'billing');

        $billing_data = array (
            'billingId' => $order_data['customer_id'],
            'billingName' => $address['firstname'] . ' ' . $address['lastname'],
            'billingAddress' => $address['address1'],
            'billingAddress1' => $address['address1'],
            'billingAddress2' => $address['address2'],
            'billingDistrict' => 'N/A',
            'billingCity' => $address['city'],
            'billingState' => $address['state'],
            'billingZip' => $address['postcode'],
            'billingPostalCode' => $address['postcode'],
            'billingCountry' => $address['country'],
            'billingPhone' => $address['telephone'],
            'billingEmail' => $order_data['email']
        );

        return $billing_data;
    }

    private function getShippingData($order_data)
    {
        $address = $this->_getAddress($order_data, 'shipping');

        $shipping_data = array (
            'shippingId' => $order_data['email'],
            'shippingName' => $address['firstname'] . ' ' . $address['lastname'],
            'shippingAddress' => $address['address1'],
            'shippingAddress1' => $address['address1'],
            'shippingAddress2' => $address['address2'],
            'shippingDistrict' => 'N/A',
            'shippingCity' => $address['city'],
            'shippingState' => $address['state'],
            'shippingZip' => $address['postcode'],
            'shippingPostalCode' => $address['postcode'],
            'shippingCountry' => $address['country'],
            'shippingPhone' => $address['telephone'],
            'shippingEmail' => $order_data['email']
        );

        return $shipping_data;
    }

    private function getTypeData($order_data)
    {
        // $this->load->model('account/customer');
        // $customer = $this->model_account_customer->getCustomer($order_data['customer_id']);
        // TODO: Must get birthDate and gender from customer,
        // but opencart 3.x doesn't have this field on registration!
        $birthDate = '1990-01-01';
        $gender = 'M';

        $customer_type = 'Individual';
        $document_type = 'CPF';
        $document = $this->getPost('document');

        if (strlen($document) == '14') {
            $customer_type = 'Legal entity';
            $document_type = 'CNPJ';
        }

        $phone = preg_replace('/[^0-9]/', '', $order_data['telephone']);
        $country_code = $this->getCountryCode($order_data['payment_iso_code_2']);
        if(substr($phone, 0, strlen($country_code)) == $country_code)
            $phone = substr($phone, strlen($country_code), strlen($phone) - strlen($country_code));

        $phone_area = $this->getAreaNumber($phone);
        $phone_number = $this->getPhoneNumber($phone);

        return array(
            'customerIdExt' => $document,

            'billingType' => $customer_type,
            'billingDocumentType' => $document_type,
            'billingDocumentValue' => $document,
            'billingBirthDate' => $birthDate,
            'billingGender' => $gender,

            'billingPhoneType' => 'Mobile',
            'billingPhoneCountryCode' => $country_code,
            'billingPhoneAreaCode' => $phone_area,
            'billingPhoneNumber' => $phone_number,

            'shippingType' => $customer_type,
            'shippingDocumentType' => $document_type,
            'shippingDocumentValue' => $document,
            'shippingBirthDate' => $birthDate,
            'shippingGender' => $gender,

            'shippingPhoneType' => 'Mobile',
            'shippingPhoneCountryCode' => $country_code,
            'shippingPhoneAreaCode' => $phone_area,
            'shippingPhoneNumber' => $phone_number
        );
    }

    private function getProductsData($order_data)
    {
        if(!$this->model_account_order)
            $this->load->model('account/order');

        $products = $this->model_account_order->getOrderProducts($order_data['order_id']);

        $products_data = array();
        $products_data['itemCount'] = count($products);

        foreach ($products as $index => $product)
        {
            // maxiPago index starts at '1'
            $i = $index+1;

            $products_data['itemIndex' . $i] = $i;
            $products_data['itemProductCode' . $i] = $product['product_id'];
            $products_data['itemDescription' . $i] = $product['name'];
            $products_data['itemQuantity' . $i] = $product['quantity'];
            $products_data['itemUnitCost' . $i] = number_format($product['price'], 2, '.', '');
            $products_data['itemTotalAmount' . $i] = number_format($product['total'], 2, '.', '');
        }

        return $products_data;
    }

    private function getCountryCode($country)
    {
        $_countryCodes = array(
            'AD' => '376',
            'AE' => '971',
            'AF' => '93',
            'AG' => '1268',
            'AI' => '1264',
            'AL' => '355',
            'AM' => '374',
            'AN' => '599',
            'AO' => '244',
            'AQ' => '672',
            'AR' => '54',
            'AS' => '1684',
            'AT' => '43',
            'AU' => '61',
            'AW' => '297',
            'AZ' => '994',
            'BA' => '387',
            'BB' => '1246',
            'BD' => '880',
            'BE' => '32',
            'BF' => '226',
            'BG' => '359',
            'BH' => '973',
            'BI' => '257',
            'BJ' => '229',
            'BL' => '590',
            'BM' => '1441',
            'BN' => '673',
            'BO' => '591',
            'BR' => '55',
            'BS' => '1242',
            'BT' => '975',
            'BW' => '267',
            'BY' => '375',
            'BZ' => '501',
            'CA' => '1',
            'CC' => '61',
            'CD' => '243',
            'CF' => '236',
            'CG' => '242',
            'CH' => '41',
            'CI' => '225',
            'CK' => '682',
            'CL' => '56',
            'CM' => '237',
            'CN' => '86',
            'CO' => '57',
            'CR' => '506',
            'CU' => '53',
            'CV' => '238',
            'CX' => '61',
            'CY' => '357',
            'CZ' => '420',
            'DE' => '49',
            'DJ' => '253',
            'DK' => '45',
            'DM' => '1767',
            'DO' => '1809',
            'DZ' => '213',
            'EC' => '593',
            'EE' => '372',
            'EG' => '20',
            'ER' => '291',
            'ES' => '34',
            'ET' => '251',
            'FI' => '358',
            'FJ' => '679',
            'FK' => '500',
            'FM' => '691',
            'FO' => '298',
            'FR' => '33',
            'GA' => '241',
            'GB' => '44',
            'GD' => '1473',
            'GE' => '995',
            'GH' => '233',
            'GI' => '350',
            'GL' => '299',
            'GM' => '220',
            'GN' => '224',
            'GQ' => '240',
            'GR' => '30',
            'GT' => '502',
            'GU' => '1671',
            'GW' => '245',
            'GY' => '592',
            'HK' => '852',
            'HN' => '504',
            'HR' => '385',
            'HT' => '509',
            'HU' => '36',
            'ID' => '62',
            'IE' => '353',
            'IL' => '972',
            'IM' => '44',
            'IN' => '91',
            'IQ' => '964',
            'IR' => '98',
            'IS' => '354',
            'IT' => '39',
            'JM' => '1876',
            'JO' => '962',
            'JP' => '81',
            'KE' => '254',
            'KG' => '996',
            'KH' => '855',
            'KI' => '686',
            'KM' => '269',
            'KN' => '1869',
            'KP' => '850',
            'KR' => '82',
            'KW' => '965',
            'KY' => '1345',
            'KZ' => '7',
            'LA' => '856',
            'LB' => '961',
            'LC' => '1758',
            'LI' => '423',
            'LK' => '94',
            'LR' => '231',
            'LS' => '266',
            'LT' => '370',
            'LU' => '352',
            'LV' => '371',
            'LY' => '218',
            'MA' => '212',
            'MC' => '377',
            'MD' => '373',
            'ME' => '382',
            'MF' => '1599',
            'MG' => '261',
            'MH' => '692',
            'MK' => '389',
            'ML' => '223',
            'MM' => '95',
            'MN' => '976',
            'MO' => '853',
            'MP' => '1670',
            'MR' => '222',
            'MS' => '1664',
            'MT' => '356',
            'MU' => '230',
            'MV' => '960',
            'MW' => '265',
            'MX' => '52',
            'MY' => '60',
            'MZ' => '258',
            'NA' => '264',
            'NC' => '687',
            'NE' => '227',
            'NG' => '234',
            'NI' => '505',
            'NL' => '31',
            'NO' => '47',
            'NP' => '977',
            'NR' => '674',
            'NU' => '683',
            'NZ' => '64',
            'OM' => '968',
            'PA' => '507',
            'PE' => '51',
            'PF' => '689',
            'PG' => '675',
            'PH' => '63',
            'PK' => '92',
            'PL' => '48',
            'PM' => '508',
            'PN' => '870',
            'PR' => '1',
            'PT' => '351',
            'PW' => '680',
            'PY' => '595',
            'QA' => '974',
            'RO' => '40',
            'RS' => '381',
            'RU' => '7',
            'RW' => '250',
            'SA' => '966',
            'SB' => '677',
            'SC' => '248',
            'SD' => '249',
            'SE' => '46',
            'SG' => '65',
            'SH' => '290',
            'SI' => '386',
            'SK' => '421',
            'SL' => '232',
            'SM' => '378',
            'SN' => '221',
            'SO' => '252',
            'SR' => '597',
            'ST' => '239',
            'SV' => '503',
            'SY' => '963',
            'SZ' => '268',
            'TC' => '1649',
            'TD' => '235',
            'TG' => '228',
            'TH' => '66',
            'TJ' => '992',
            'TK' => '690',
            'TL' => '670',
            'TM' => '993',
            'TN' => '216',
            'TO' => '676',
            'TR' => '90',
            'TT' => '1868',
            'TV' => '688',
            'TW' => '886',
            'TZ' => '255',
            'UA' => '380',
            'UG' => '256',
            'US' => '1',
            'UY' => '598',
            'UZ' => '998',
            'VA' => '39',
            'VC' => '1784',
            'VE' => '58',
            'VG' => '1284',
            'VI' => '1340',
            'VN' => '84',
            'VU' => '678',
            'WF' => '681',
            'WS' => '685',
            'XK' => '381',
            'YE' => '967',
            'YT' => '262',
            'ZA' => '27',
            'ZM' => '260',
            'ZW' => '263'
        );

        return $_countryCodes[$country];
    }

    private function getAreaNumber($phone)
    {
        $phone = preg_replace('/^D/', '', $phone);
        $phone = substr($phone, 0, 2);
        return $phone;
    }

    private function getPhoneNumber($phone)
    {
        if (strlen($phone) >= 10) {
            $phone = preg_replace('/^D/', '', $phone);
            $phone = substr($phone, 2, strlen($phone) - 2);
        }
        return $phone;
    }

    private function logXmlIfAllowed()
    {
        if($this->config->get('payment_maxipago_log'))
        {
            $this->log($this->hideSensitiveInformation($this->getMaxipago()->xmlRequest));
            $this->log($this->getMaxipago()->xmlResponse);
        }
    }

    private function hideSensitiveInformation($xml)
    {
        $xml = preg_replace('/<number>(.*)<\/number>/m', '<number>*****</number>', $xml);
        $xml = preg_replace('/<cvvNumber>(.*)<\/cvvNumber>/m', '<cvvNumber>***</cvvNumber>', $xml);
        $xml = preg_replace('/<token>(.*)<\/token>/m', '<token>***</token>', $xml);

        return $xml;
    }

    public function getSavedCards($customerId = null)
    {
        $saved_cards = array();

        if ($customerId) {
            //Saved Cards
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_cc_token
                    WHERE `id_customer` = \'' . $customerId . '\'';
            $saved_cards = $this->db->query($sql);
            $saved_cards = $saved_cards->rows;
        }

        return $saved_cards;
    }

    public function getCardToken($customer_id, $card_description)
    {
        $sql = 'SELECT *
                        FROM ' . DB_PREFIX . 'maxipago_cc_token
                        WHERE `id_customer` = \'' . $customer_id . '\'
                        AND `description` = \'' . $card_description . '\'
                        LIMIT 1; ';

        $card_token = $this->db->query($sql)->row;

        return $card_token;
    }

    public function saveCard($order_info)
    {
        try {
            $this->load->language('extension/payment/maxipago');

            $address = $this->_getAddress($order_info, 'billing');

            $customerId = $order_info['customer_id'];
            $firstname = $order_info['firstname'];
            $lastname = $order_info['lastname'];
            $mpCustomerId = null;

            $type_data = $this->getTypeData($order_info);
            $customer_id_ext = $type_data['customerIdExt'];

            $ccBrand = $this->getPost('brand');
            $ccNumber = $this->getPost('number');
            $ccExpMonth = $this->getPost('expiry_month');
            $ccExpYear = $this->getPost('expiry_year');

            $sql = 'SELECT *
                FROM ' . DB_PREFIX . 'maxipago_cc_token
                WHERE `id_customer` = \'' . $customerId . '\'
                LIMIT 1';
            $mpCustomer = $this->db->query($sql)->row;

            if (!$mpCustomer) {
                $customerData = array(
                    'customerIdExt' => $customer_id_ext,
                    'firstName' => $firstname,
                    'lastName' => $lastname
                );
                $this->getMaxipago()->addProfile($customerData);
                $response = $this->getMaxipago()->response;

                $this->_saveTransaction('add-profile', $customerData, $response, null, false);
                if (isset($response['errorCode']) && $response['errorCode'] == 1) {

                    //Search the table to see if the profile already exists
                    $sql = 'SELECT *
                            FROM ' . DB_PREFIX . 'maxipago_transactions
                            WHERE `method` = \'add_profile\';
                        ';

                    $query = $this->db->query($sql);

                    if ($query->num_rows) {
                        foreach ($query->rows as $row) {
                            $requestRow = json_decode($row['request']);
                            if (property_exists($requestRow, 'customerIdExt') && $requestRow->customerIdExt == $customer_id_ext) {
                                $responseRow = json_decode($row['return']);
                                if (property_exists($responseRow, 'result') && property_exists($responseRow->result, 'customerId')) {
                                    $mpCustomerId = $responseRow->result->customerId;
                                }
                            }
                        }
                    }
                } else {
                    $mpCustomerId = $this->getMaxipago()->getCustomerId();
                }

            } else {
                $mpCustomerId = $mpCustomer['id_customer_maxipago'];
            }

            if ($mpCustomerId) {
                $date = new DateTime($ccExpYear . '-' . $ccExpMonth . '-01');
                $date->modify('+1 month');
                $endDate = $date->format('m/d/Y');

                $ccData = array(
                    'customerId' => $mpCustomerId,
                    'creditCardNumber' => $ccNumber,
                    'expirationMonth' => $ccExpMonth,
                    'expirationYear' => $ccExpYear,
                    'billingName' => $firstname . ' ' . $lastname,
                    'billingAddress1' => $address['address1'],
                    'billingAddress2' => $address['address2'],
                    'billingCity' => $address['city'],
                    'billingState' => $address['state'],
                    'billingZip' => $address['postcode'],
                    'billingPhone' => $address['telephone'],
                    'billingEmail' => $order_info['email'],
                    'onFileEndDate' => $endDate,
                    'onFilePermissions' => 'ongoing',
                );

                $this->getMaxipago()->addCreditCard($ccData);
                $token = $this->getMaxipago()->getToken();
                $this->_saveTransaction('save-card', $ccData, $this->getMaxipago()->response, null, false);

                if ($token) {
                    $ccEnc = substr($ccNumber, 0, 6) . 'XXXXXX' . substr($ccNumber, -4, 4);
                    $sql = 'INSERT INTO `' . DB_PREFIX . 'maxipago_cc_token` 
                                (`id_customer`, `id_customer_maxipago`, `brand`, `token`, `description`)
                            VALUES
                                ("' . $customerId . '", "' . $mpCustomerId . '", "' . $ccBrand . '", "' . $token . '", "' . $ccEnc . '" )
                            ';

                    $this->db->query($sql);
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteCC($ccSaved)
    {
        try {
            $data = array(
                'command' => 'delete-card-onfile',
                'customerId' => $ccSaved['id_customer'],
                'token' => $ccSaved['token']
            );

            $this->getMaxipago()->deleteCreditCard($data);
            $response = $this->getMaxipago()->response;
            $this->_saveTransaction('remove-card', $data, $response, null, false);

            $sql = 'DELETE FROM `' . DB_PREFIX . 'maxipago_cc_token` WHERE `id` = \'' . $ccSaved['id'] . '\';';
            $this->db->query($sql);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    ////////////////

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/maxipago');

        $query = $this->db->query("
          SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
          WHERE geo_zone_id = '" . (int)$this->config->get('maxipago_geo_zone_id') . "' 
          AND country_id = '" . (int)$address['country_id'] . "' 
          AND (
            zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0'
          )"
        );

        if ($this->config->get('maxipago_maximum_amount') > 0 && $this->config->get('maxipago_maximum_amount') <= $total) {
            $status = false;
        } elseif ($this->config->get('maxipago_minimum_amount') > 0 && $this->config->get('maxipago_minimum_amount') > $total) {
            $status = false;
        } elseif (!$this->config->get('maxipago_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();
        if ($status) {
            $method_data = array(
                'code' => self::MAXIPAGO_CODE,
                'title' => ($this->config->get(self::MAXIPAGO_CODE . '_method_title')) ? $this->config->get(self::MAXIPAGO_CODE . '_method_title') : $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('maxipago_sort_order')
            );
        }

        return $method_data;
    }

    public function confirmRecurringPayments()
    {
        $post_data = $this->request->post;

        $this->load->model('checkout/order');

        $statuses = array(
            'processing' => $this->config->get('payment_maxipago_order_status_processing'),
            'authorized' => $this->config->get('payment_maxipago_order_status_authorized'),
            'approved' => $this->config->get('payment_maxipago_order_status_approved')
        );

        $statuses_aux = array(
            $statuses['processing'] => 'PENDING',
            $statuses['authorized'] => 'AUTHORIZED',
            $statuses['approved'] => 'CAPTURED'
        );

        $message = '';
        $status = $statuses['processing'];
        $order_id = $this->session->data['order_id'];

        if(count($post_data) <= 2)
            return array(
                'error' => true,
                'message' => 'Missing information for confirmation'
            );

        for($index = 0; $index < (count($post_data) - 2); $index++)
        {
            // The transactions have already been analyzed, and in this step, every transaction is a success!
            $transaction = $post_data[$index];

            if($transaction['responseMessage'] == 'AUTHORIZED')
                $status = $statuses['authorized'];

            if($transaction['responseMessage'] == 'CAPTURED')
                if($status != $statuses['authorized'])
                    $status = $statuses['approved'];

            $transaction_message = '';
            if($transaction['product_data_type'] == 'common')
            {
                $transaction_message = $this->language->get('common_products_transaction_message');
                $transaction_message = sprintf($transaction_message, count($transaction['product_data']), $transaction['responseMessage']);
            } else if($transaction['product_data_type'] == 'recurring')
            {
                $transaction_message = $this->language->get('recurring_product_transaction_message');
                $transaction_message = sprintf($transaction_message, $transaction['product_data']['name'], $transaction['product_data']['recurring']['name']);
            }
            $message .= '<p>' . $transaction_message . '</p>';

            $mp_order_id = $transaction['orderID'];
            $mp_transaction_id = $transaction['transactionID'];
            $mp_auth_code = $transaction['authCode'];

            $has_aditional_information = $mp_order_id || $mp_transaction_id || $mp_auth_code;

            if($has_aditional_information)
                $message .= '<ul>';

            if ($mp_order_id)
                $message .= '<li>orderID: ' . $mp_order_id . '</li>';

            if ($mp_transaction_id)
                $message .= '<li>transactionID: ' . $mp_transaction_id . '</li>';

            if ($mp_auth_code)
                $message .= '<li>authCode: ' . $mp_auth_code . '</li>';

            if($has_aditional_information)
                $message.= '</ul>';

            if(($index + 1) < (count($post_data) - 2))
                $message .= '<hr />';
        }

        if($status == $statuses['approved'] || $status == $statuses['authorized'])
            $message = $this->language->get('order_cc_text') . ' ' . $statuses_aux[$status] . $message;

        $this->model_checkout_order->addOrderHistory($order_id, $status, $message, true);

        return array(
            'url' => $this->url->link('checkout/success', '', true)
        );
    }

    /**
     * Controller that confirms the payment
     */
    public function confirmPayment()
    {
        $this->load->model('checkout/order');

        $paymentType = $this->getPost('type');
        $responseCode = $this->getPost('responseCode');
        $responseMessage = $this->getPost('responseMessage');
        $message = '';

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $status = isset($order_info['order_status_id']) ? $order_info['order_status_id'] : 1;

        $response = array(
            'error' => true,
            'message' => 'Payment type ' . $paymentType . ' not found'
        );

        $continueUrl = $this->url->link('checkout/success', '', true);
        $order_url = $this->url->link('account/order/info', '', true) . '&order_id=' . $order_id;

        switch ($responseCode) {
            //Aprovada
            case '0':
                $status = $this->config->get('payment_maxipago_order_status_processing');

                if ($paymentType == 'credit-card') {
                    $message = '<p>' . $this->language->get('order_cc_text') . ' ' . $responseMessage . '</p>';

                    if ($responseMessage == 'CAPTURED') {
                        $status = $this->config->get('payment_maxipago_order_status_approved');
                    } else if ($responseMessage == 'AUTHORIZED') {
                        $status = $this->config->get('payment_maxipago_order_status_authorized');
                    }

                    if ($this->getPost('installments')) {
                        $installments = $this->getPost('installments');
                        $total = $this->getPost('total');
                        $totalFormatted =  $this->currency->format($total, $this->session->data['currency']);
                        $installmentsValue = $this->currency->format(($total / $installments), $this->session->data['currency']);
                        $message .= '<p>Total: ' . $totalFormatted . ' - ' . $installments . 'x de ' . $installmentsValue . '</p>';
                    }
                    $response = array(
                        'url' => $continueUrl
                    );
                } else if ($paymentType == 'debit-card') {
                    $url = $this->getPost('authenticationURL');
                    $link = '<p><a href="' . $url . '" target="_blank">' . $this->language->get('debit_card_link_text') . '</a></p>';
                    $message = $this->language->get('order_debit_card_text') . $link;
                    $response = array(
                        'url' => $url
                    );
                } else if ($paymentType == 'eft') {
                    $url = $this->getPost('onlineDebitUrl');
                    $link = '<p><a href="' . $url . '" target="_blank">' . $this->language->get('eft_link_text') . '</a></p>';
                    $message = $this->language->get('order_eft_text') . $link;
                    $response = array(
                        'url' => $order_url
                    );
                } else if ($paymentType == 'invoice') {
                    $url = $this->getPost('boletoUrl');
                    $link = '<p><a href="' . $url . '" target="_blank">' . $this->language->get('invoice_link_text') . '</a></p>';
                    $message = $this->language->get('order_invoice_text') . $link;
                    $response = array(
                        'url' => $order_url
                    );
                } else if ($paymentType == 'redepay') {
                    $url = $this->getPost('authenticationURL');
                    $link = '<p><a href="' . $url . '" target="_blank">' . $this->language->get('redepay_link_text') . '</a></p>';
                    $message = $this->language->get('order_redepay_text') . $link;
                    $response = array(
                        'url' => $url
                    );
                }

                if ($this->getPost('orderID')) {
                    $message .= '<p>orderID: ' . $this->getPost('orderID') . '</p>';
                }

                if ($this->getPost('transactionID')) {
                    $message .= '<p>transactionID: ' . $this->getPost('transactionID') . '</p>';
                }

                if ($this->getPost('authCode')) {
                    $message .= '<p>authCode: ' . $this->getPost('authCode') . '</p>';
                }

                break;

            //Cancelado
            case '1':
            case '2':
                $status = $this->config->get('payment_maxipago_order_status_cancelled');
                $message = $this->language->get('maxipago_order_cancelled');
                $response['message'] = $message;
                break;
            //Erro na transação
            default:
                $message = ($responseCode && isset($this->_responseCodes[$responseCode])) ? $this->_responseCodes[$responseCode] : $this->language->get('order_error');
                $response['message'] = $message;

                if ($this->getPost('errorMessage')) {
                    $message .= '<p>transactionID: ' . $this->getPost('errorMessage') . '</p>';
                    $response['message'] = $this->getPost('errorMessage');
                }
        }

        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $status, $message, true);
        return $response;
    }

    /**
     * @param $order_info
     */
    public function capturePayment($order_info, $order_status_id)
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

                $this->log($this->getMaxipago()->xmlRequest);
                $this->log($this->getMaxipago()->xmlResponse);

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
     * Refund an order
     * @param $order_info
     * @return bool
     */
    public function reversePayment($order_info)
    {
        try {
            $order_id = $order_info['order_id'];
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `id_order` = "' . $order_id . '"
                    AND `method` = "credit-card";';

            $date = date('Ymd', strtotime($order_info['date_added']));
            $transaction = $this->db->query($sql)->row;

            if (!empty($transaction)) {

                $request = json_decode($transaction['request']);
                $response = json_decode($transaction['return']);

                $data = array(
                    'orderID' => $response->orderID,
                    'referenceNum' => $response->referenceNum,
                    'chargeTotal' => ($request->chargeTotal - $request->shippingTotal)
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
            $this->log('Error refunding order ' . $order_id . ': ' . $e->getMessage());
        }

        return false;
    }

    public function refundOrder($order_id)
    {
        $sql = 'SELECT * FROM ' . DB_PREFIX . '_maxipago_transactions
        WHERE `order_id` = "' . $order_id . '"
        AND `response_message` = "CAPTURED"';
        $transaction = $this->db->query($sql)->row;

        if($transaction)
        {
            $transaction_request = json_decode($transaction['request']);
            $transaction_response = json_decode($transaction['return']);

            $data = array(
                'orderID' => $transaction_response['orderID'],
                'referenceNum' => $transaction_response['referenceNum'],
                'chargeTotal' => $transaction_request['total']
            );

            $client = $this->getMaxipago();
            $client->creditCardRefund($data);

            if ($client->isErrorResponse() && $client->getResponseCode() == 0) {
                $this->_updateTransactionState($order_id);
            }
        }
    }

    public function voidOrder($order_id)
    {
        $this->voidAllOrderPayments($order_id);
        $this->voidAllOrderRecurringPayments($order_id);
    }

    public function voidAllOrderRecurringPayments($order_id)
    {
        try
        {
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_recurring_transactions
                    WHERE `order_id` = "' . $order_id . '"
                    AND `maxipago_status` in ("AUTHORIZED", "CAPTURED")
                    ';

            $transactions = $this->db->query($sql)->rows;

            if(!empty($transactions))
            {
                foreach($transactions as $transaction)
                {
                    $response = json_decode($transaction['response']);

                    $data = array(
                        'transactionID' => $response->transactionID
                    );

                    $this->getMaxipago()->creditCardVoid($data);
                }

                $this->_updateRecurringTransactionsState($order_id);
            }

        } catch (Exception $e)
        {
            $this->log('Error voiding order ' . $order_id . ': ' . $e->getMessage());
        }
    }

    public function voidAllOrderPayments($order_id)
    {
        try
        {
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `id_order` = "' . $order_id . '"
                    AND `method` = "credit-card"';

            $transaction = $this->db->query($sql)->row;

            if(!empty($transaction))
            {
                $request = json_decode($transaction['request']);
                $response = json_decode($transaction['return']);

                $data = array(
                    'transactionID' => $response->transactionID
                );

                $this->getMaxipago()->creditCardVoid($data);
                $this->_updateTransactionState($order_id);
            }

        } catch (Exception $e)
        {
            $this->log('Error voiding order ' . $order_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Refund an order
     * @param $order_info
     * @return bool
     */
    public function voidPayment($order_info)
    {
        try {
            $order_id = $order_info['order_id'];
            $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `id_order` = "' . $order_id . '"
                    AND `method` = "card";';

            $transaction = $this->db->query($sql)->row;

            if (!empty($transaction)) {

                $request = json_decode($transaction['request']);
                $response = json_decode($transaction['return']);

                $data = array(
                    'transactionID' => $response->transactionID
                );

                $this->getMaxipago()->creditCardVoid($data);
                $this->_saveTransaction('void', $data, $this->getMaxipago()->response, null, false);
                $this->_updateTransactionState($order_id);

                return true;
            }

        } catch (Exception $e) {
            $this->log('Error refunding order ' . $order_id . ': ' . $e->getMessage());
        }

        return false;
    }

    protected function _getAddress($order_info, $type = 'billing')
    {
        $prefix =$this->_getAddressDataPrefix($type);

        $first_name = $order_info[$prefix . '_firstname'];
        $last_name = $order_info[$prefix . '_lastname'];
        $country = isset($order_info[$prefix . '_iso_code_2']) ? $order_info[$prefix . '_iso_code_2'] : 'BR';
        $state = $order_info[$prefix . '_zone_code'];
        $city = $order_info[$prefix . '_city'];
        $address1 = $order_info[$prefix . '_address_1'];
        $address2 = $order_info[$prefix . '_address_2'];

        $postcode = $order_info[$prefix . '_postcode'];
        $telephone = $order_info['telephone'];

        return array(
            'firstname' => $first_name,
            'lastname' => $last_name,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'address1' => $address1,
            'address2' => $address2,
            'postcode' => $postcode,
            'telephone' => $telephone
        );
    }

    protected function _getAddressDataPrefix($type)
    {
        $prefixes = array(
            'billing' => 'payment',
            'shipping' => 'shipping'
        );

        if(!in_array($type, $prefixes))
            $type = 'billing';

        return $prefixes[$type];
    }

    protected function _formatPostCode($postCode)
    {
        $postCode = preg_replace('/[^0-9]/', '', $postCode);
        $postCode = substr($postCode, 0, 5) . '-' . substr($postCode, 5, 3);
        return $postCode;
    }

    protected function _formatTelephone($telephone)
    {
        return preg_replace('/[^0-9]/', '', $telephone);
    }

    /**
     * @deprecated
     * @param $order_custom_fields
     * @return string
     */
    protected function _getCustomFieldsData($order_custom_fields)
    {
        $custom_fields_data = '';

        if(!$this->model_account_custom_field)
            $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields();

        if($custom_fields)
        {
            foreach ($custom_fields as $custom_field) {
                if(isset($order_custom_fields[$custom_field['custom_field_id']])) {
                    $field_is_required = $custom_field['required'];
                    $field_has_value = !empty($order_custom_fields[$custom_field['custom_field_id']]);

                    if($field_is_required || $field_has_value)
                        $custom_fields_data .= sprintf(', %s %s', $custom_field['name'], $order_custom_fields[$custom_field['custom_field_id']]);
                }
            }
        }

        return $custom_fields_data;
    }

    public function addOrderHistory($order_id, $order_status_id, $comment, $notify = 1)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . (int)$order_status_id . "', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . (int)$order_id . "', `order_status_id` = '" . (int)$order_status_id . "', `notify` = '" . $notify . "', `comment` = '" . $this->db->escape($comment) . "', `date_added` = NOW()");
    }

    public function updateTransactionState($order_id, $return, $response_message)
    {
        $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_transactions
            SET `response_message` = \'' . strtoupper($response_message) . '\',
            `return` = \'' . $return . '\'
            WHERE `id_order` = "' . $order_id . '"';

        $this->db->query($sql);
    }

    protected function _updateRecurringTransactionsState($id_order)
    {
        $this->load->language('extension/payment/maxipago');
        $this->load->model('extension/payment/maxipago');

        $sql = 'SELECT * FROM ' . DB_PREFIX . 'maxipago_recurring_transactions
            WHERE `order_id` = "' . $id_order . '"
            AND `maxipago_status` in ("AUTHORIZED", "CAPTURED")';

        $transactions = $this->db->query($sql)->rows;

        if(!empty($transactions))
        {
            foreach($transactions as $transaction)
            {
                $return = json_decode($transaction['response']);

                $search = array(
                    'orderID' => $return->orderID
                );

                $this->getMaxipago()->pullReport($search);
                $response = $this->getMaxipago()->getReportResult();

                if (! empty($response))
                {
                    $responseCode = isset($response[0]['responseCode']) ? $response[0]['responseCode'] : $return->responseCode;
                    if (! property_exists($return, 'originalResponseCode')) {
                        $return->originalResponseCode = $return->responseCode;
                    }
                    $return->responseCode = $responseCode;

                    if (! property_exists($return, 'originalResponseMessage')) {
                        $return->originalResponseMessage = $return->responseMessage;
                    }
                    $state = isset($response[0]['transactionState']) ? $response[0]['transactionState'] : null;
                    $responseMessage = (array_key_exists($state, $this->_transactionStates)) ? $this->_transactionStates[$state] : $return->responseMessage;
                    $return->responseMessage = $responseMessage;
                    $return->transactionState = $state;
                    $transaction['response_message'] = $responseMessage;

                    $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_recurring_transactions
                                   SET `maxipago_status` = \'' . strtoupper($responseMessage) . '\',
                                       `response` = \'' . json_encode($response[0]) . '\'
                                 WHERE `order_id` = "' . $id_order . '"
                                 and `order_recurring_id` = "' . $transaction['order_recurring_id'] . '"
                                ';

                    $this->db->query($sql);
                }
            }
        }
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
            return;
        }

        if (empty($return) ) {
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

                if (! empty($response) ) {
                    $responseCode = isset($response[0]['responseCode']) ? $response[0]['responseCode'] : $return->responseCode;
                    if (! property_exists($return, 'originalResponseCode')) {
                        $return->originalResponseCode = $return->responseCode;
                    }
                    $return->responseCode = $responseCode;

                    if (! property_exists($return, 'originalResponseMessage')) {
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

    protected function _saveRecurringTransaction($order_id, $order_recurring_id, $request, $response)
    {
        $maxipago_order_id = '';
        if(isset($response['orderID']))
            $maxipago_order_id = $this->db->escape($response['orderID']);

        $maxipago_status = '';
        if(isset($response['responseMessage']))
            $maxipago_status = $this->db->escape($response['responseMessage']);

        if(isset($request['number']))
            $request['number'] = substr($request['number'], 0, 6) . 'XXXXXX' . substr($request['number'], -4, 4);

        if(isset($request['token']))
            $request['token'] = 'XXXXXXXXXXXX';

        if(isset($request['cvv']))
            $request['cvv'] = 'XXX';

        if(isset($request['cvvNumber']))
            $request['cvvNumber'] = 'XXX';

        if(isset($request['creditCardData']))
            unset($request['creditCardData']);

        $request = $this->db->escape(json_encode($request));
        $response = $this->db->escape(json_encode($response));

        $sql = 'INSERT INTO `' . DB_PREFIX . 'maxipago_recurring_transactions` 
                    (`order_id`, `order_recurring_id`, `maxipago_order_id`, `maxipago_status`, `request`, `response`)
                VALUES
                    ("' . $order_id . '", "' . $order_recurring_id . '",  "' . $maxipago_order_id . '", "' . $maxipago_status . '", "' . $request . '", "' . $response . '" )
                ';

        $this->db->query($sql);
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
        $authenticationURL = null;

        if ($transactionUrl) {
            if ($method == 'eft') {
                $onlineDebitUrl = $transactionUrl;
            } else if ($method == 'invoice') {
                $boletoUrl = $transactionUrl;
            } else if (in_array($method, array('debit-card', 'redepay'))) {
                $authenticationURL = $transactionUrl;
            }
        }

        if (is_object($request) || is_array($request)) {

            if (isset($request['number'])) {
                $request['number'] = substr($request['number'], 0, 6) . 'XXXXXX' . substr($request['number'], -4, 4);
            }

            if(isset($request['token'])) {
                $request['token'] = 'XXX';
            }

            if(isset($request['cvvNumber'])) {
                $request['cvvNumber'] = 'XXX';
            }

            if ($this->getPost('brand')) {
                $request['brand'] = $this->getPost('brand');
            }

            $request = json_encode($request);
        }

        $responseMessage = null;
        if (is_object($return) || is_array($return)) {
            $responseMessage = isset($return['responseMessage']) ? $return['responseMessage'] : null;
            $return = json_encode($return);
        }

        $order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0;
        if (! $hasOrder) {
            $order_id = 0;
        }

        $request = $this->db->escape($request);
        $return = $this->db->escape($return);
        $responseMessage = $this->db->escape($responseMessage);

        $sql = 'INSERT INTO `' . DB_PREFIX . 'maxipago_transactions` 
                    (`id_order`, `boleto_url`, `online_debit_url`, `authentication_url`, `method`, `request`, `return`, `response_message`)
                VALUES
                    ("' . $order_id . '", "' . $boletoUrl . '",  "' . $onlineDebitUrl . '", "' . $authenticationURL . '", "' . $method . '" ,"' . $request . '", "' . $return . '", "' . $responseMessage . '" )
                ';

        $this->db->query($sql);
    }

    /**
     * Calculate the installments price for maxiPago!
     * @param $price
     * @param $installments
     * @param $interestRate
     * @return float
     */
    public function getInstallmentPrice($price, $installments, $interestRate)
    {
        $price = (float) $price;
        if ($interestRate) {
            $interestRate = (float)(str_replace(',', '.', $interestRate)) / 100;
            $type = $this->config->get('payment_maxipago_credit_card_interest_type');
            $valorParcela = 0;
            switch ($type) {
                case 'price':
                    $value = round($price * (($interestRate * pow((1 + $interestRate), $installments)) / (pow((1 + $interestRate), $installments) - 1)), 2);
                    break;
                case 'compound':
                    //M = C * (1 + i)^n
                    $value = ($price * pow(1 + $interestRate, $installments)) / $installments;
                    break;
                case 'simple':
                    //M = C * ( 1 + ( i * n ) )
                    $value = ($price * (1 + ($installments * $interestRate))) / $installments;
            }
        } else {
            if ($installments)
                $value = $price / $installments;
        }
        return $value;
    }

    /**
     * Calculate the total of the order based on interest rate and installmentes
     * @param $price
     * @param $installments
     * @param $interestRate
     * @return float
     */
    public function getTotalByInstallments($price, $installments, $interestRate)
    {
        $installmentPrice = $this->getInstallmentPrice($price, $installments, $interestRate);
        return $installmentPrice * $installments;
    }

    /**
     * Get MAX installments for a price
     * @param null $price
     * @return array|bool
     */
    public function getInstallment($price = null)
    {
        $price = (float) $price;

        $maxInstallments = $this->config->get('payment_maxipago_credit_card_maximum_installments');//
        $installmentsWithoutInterest = $this->config->get('payment_maxipago_credit_card_installments_without_interest');
        $minimumPerInstallment = $this->config->get('payment_maxipago_credit_card_minimum_by_installments');
        $minimumPerInstallment = (float)$minimumPerInstallment;

        if ($minimumPerInstallment > 0) {
            if ($minimumPerInstallment > $price / 2)
                return false;

            while ($maxInstallments > ($price / $minimumPerInstallment))
                $maxInstallments--;

            while ($installmentsWithoutInterest > ($price / $minimumPerInstallment))
                $installmentsWithoutInterest--;
        }

        $interestRate = str_replace(',', '.', $this->config->get('payment_maxipago_credit_card_interest_rate'));
        $interestRate = ($maxInstallments <= $installmentsWithoutInterest) ? '' : $interestRate;

        $installmentValue = $this->getInstallmentPrice($price, $maxInstallments, $interestRate);
        $totalWithoutInterest = $installmentValue;

        if ($installmentsWithoutInterest)
            $totalWithoutInterest = $price / $installmentsWithoutInterest;

        $total = $installmentValue * $maxInstallments;

        return array(
            'total' => $total,
            'installments_without_interest' => $installmentsWithoutInterest,
            'total_without_interest' => $totalWithoutInterest,
            'max_installments' => $maxInstallments,
            'installment_value' => $installmentValue,
            'interest_rate' => $interestRate,
        );
    }

    /**
     * Get ALL POSSIBLE instalments for a price
     * @param null $price
     * @return array
     */
    public function getInstallments($order_info = array())
    {
        if (! is_array($order_info))
            return false;

        $price = (float) $order_info['total'];

        $maxInstallments = $this->config->get('payment_maxipago_credit_card_maximum_installments');//
        $installmentsWithoutInterest = $this->config->get('payment_maxipago_credit_card_installments_without_interest');
        $minimumPerInstallment = $this->config->get('payment_maxipago_credit_card_minimum_by_installments');
        $interestRate = str_replace(',', '.', $this->config->get('payment_maxipago_credit_card_interest_rate'));

        if ($minimumPerInstallment > 0) {
            while ($maxInstallments > ($price / $minimumPerInstallment)) $maxInstallments--;
        }
        $installments = array();
        if ($price > 0) {
            $maxInstallments = ($maxInstallments == 0) ? 1 : $maxInstallments;
            for ($i = 1; $i <= $maxInstallments; $i++) {
                $interestRateInstallment = ($i <= $installmentsWithoutInterest) ? '' : $interestRate;
                $value = ($i <= $installmentsWithoutInterest) ? ($price / $i) : $this->getInstallmentPrice($price, $i, $interestRate);
                $total = $value * $i;

                $installments[] = array(
                    'total' => $total,
                    'total_formated' => $this->currency->format($total, $order_info['currency_code']),
                    'installments' => $i,
                    'installment_value' => $value,
                    'installment_value_formated' => $this->currency->format($value, $order_info['currency_code']),
                    'interest_rate' => $interestRateInstallment
                );
            }
        }
        return $installments;
    }

    /**
     * Get post data validating if exists
     * @param $data
     * @return null
     */
    public function getPost($data)
    {
        return isset($this->request->post[$data]) ? $this->request->post[$data] : null;
    }

    /**
     * Get post data validating if exists
     * @param $data
     * @return null
     */
    public function getRequest($data)
    {
        $data = isset($this->request->get[$data]) ? $this->request->get[$data] : null;
        if (! $data) {
            $data = isset($this->request->post[$data]) ? $this->request->post[$data] : null;
        }
        return $data;
    }

    public function sync($transaction)
    {
        $updated = false;
        $return = json_decode($transaction['return']);

        $order_id = null;
        if(property_exists($return, 'orderID'))
            $order_id = $return->orderID;
        if(property_exists($return, 'orderId'))
            $order_id = $return->orderId;

        if(!$order_id)
            return false;

        $search = array(
            'orderID' => $order_id
        );

        $this->getMaxipago()->pullReport($search);
        $response = $this->getMaxipago()->getReportResult();

        $state = isset($response[0]['transactionState']) ? $response[0]['transactionState'] : null;

        $storeOrderId = $transaction['id_order'];
        if ($state && $storeOrderId) {
            $comment = $this->language->get('comment_updated_order') . ' ' . $state;

            if ($state == '10' || $state == '3' || $state == '44' || $state == '36') {
                $updated = $storeOrderId;
                $this->addOrderHistory($storeOrderId, $this->config->get('maxipago_order_approved'), $comment);
            } else if ($state == '45' || $state == '7' || $state == '9') {
                $updated = $storeOrderId;
                $this->addOrderHistory($storeOrderId, $this->config->get('maxipago_order_cancelled'), $comment);
            }

            $this->_updateTransactionState($storeOrderId, $return, $response);

        }

        return $updated;
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

    public function validateIP($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipAddress;
        }

        return self::DEFAULT_IP;
    }

    public function getOrderTransactions($order_id)
    {
        $sql = 'SELECT * FROM ' . DB_PREFIX . 'maxipago_transactions WHERE `method` = "credit-card" AND `response_message` IN ("AUTHORIZED", "CAPTURED") AND `id_order` = ' . (int)$order_id;
        $transactions = $this->db->query($sql);

        $sql = 'SELECT * FROM ' . DB_PREFIX . 'maxipago_recurring_transactions WHERE `maxipago_status` IN ("APPROVED", "AUTHORIZED", "CAPTURED") AND `order_id` = ' . (int)$order_id;
        $recurring_transactions = $this->db->query($sql);

        if(empty($transactions->rows) && empty($recurring_transactions->rows))
            return null;

        $order_transactions = array();

        foreach($transactions->rows as $transaction)
        {
            $response = json_decode($transaction['return']);

            $order_transactions[] = array(
                'orderID' => $response->orderID,
                'responseMessage' => $response->responseMessage,
                'recurring' => false,
                'response' => $response
            );
        }

        foreach($recurring_transactions->rows as $transaction)
        {
            $response = json_decode($transaction['response']);

            $order_transactions[] = array(
                'orderID' => $transaction['maxipago_order_id'],
                'responseMessage' => $transaction['maxipago_status'],
                'recurring' => true,
                'response' => $response
            );
        }

        return $order_transactions;
    }

    public function updateDeletedOrderTransactionStatus($maxipago_order_id, $response, $status)
    {
        $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_transactions
        SET `response_message` = "' . $status . '", `return` = \'' . json_encode($response) . '\'
        WHERE `return` is not null AND `return` <> "" AND (JSON_EXTRACT(`return`, "$.orderID")  = "' . $maxipago_order_id . '"
        OR JSON_EXTRACT(`return`, "$.orderId")  = "' . $maxipago_order_id . '")';
        $this->db->query($sql);
    }

    public function updateDeletedOrderRecurringTransactionStatus($maxipago_order_id, $response)
    {
        $sql = 'UPDATE ' . DB_PREFIX . 'maxipago_recurring_transactions 
        SET `maxipago_status` = "' . $response['responseMessage'] . '", `response` = \'' . json_encode($response) . '\'
        WHERE `maxipago_order_id` = "' . $maxipago_order_id . '"';
        $this->db->query($sql);
    }

    public function getOrderTransactionStatus($order_id)
    {
        $sql = 'SELECT * FROM ' . DB_PREFIX . 'maxipago_transactions
        WHERE `id_order` = ' . (int)$order_id;
        $transactionStatus = $this->db->query($sql);

        if($transactionStatus->row)
            return $transactionStatus->row['response_message'];
    }

    protected function getOrderShippingValue($order_id)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "order_total`
        WHERE `order_id` = " . $order_id . " AND `code` = 'shipping';";
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return $query->row['value'];
        }
    }
}
