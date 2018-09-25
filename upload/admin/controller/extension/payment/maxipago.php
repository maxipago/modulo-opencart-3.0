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
 * -------------------------------------------------------------------------
 *
 * maxiPago! Payment Method
 *
 * @package    maxiPago!
 * @author     Bizcommerce
 * @copyright  Copyright (c) 2016 BizCommerce
 *
 * @property ModelExtensionPaymentMaxipago model_extension_payment_maxipago
 * @property ModelCheckoutOrder model_checkout_order
 * @property Url url
 * @property Request request
 * @property Config config
 * @property DB db
 */

require_once(DIR_SYSTEM . 'library/maxipago/maxipago.php');

class ControllerExtensionPaymentMaxipago extends Controller
{
    private $error = array();

    public function install()
    {
        $this->registerEvents();
        $this->createTables();
    }

    private function registerEvents()
    {
        $this->load->model('setting/event');

        $this->model_setting_event->addEvent('maxipago_delete', 'catalog/controller/api/order/delete/before', 'extension/payment/maxipago/deleteOrder');

        /*$this->model_setting_event->addEvent('maxipago_delete', 'admin/model/sale/order/deleteOrder/before', 'extension/payment/maxipago/reverse');
        $this->model_setting_event->addEvent('maxipago_change', 'catalog/controller/api/order/history/after', 'extension/payment/maxipago/change');
        $this->model_setting_event->addEvent('maxipago_capture', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/payment/maxipago/change');*/
    }

    private function createTables()
    {
        $this->createCreditCardTokenTable();
        $this->createMaxiPagoTransactionsTable();
        $this->createMaxiPagoRecurringTransactionsTable();
    }

    private function createCreditCardTokenTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'maxipago_cc_token` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `id_customer` INT(10) UNSIGNED NOT NULL ,
              `id_customer_maxipago` INT(10) UNSIGNED NOT NULL ,
              `brand` VARCHAR(255) NOT NULL, 
              `token` VARCHAR(255) NOT NULL ,
              `description` VARCHAR(255) NOT NULL ,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
        $this->db->query($query);
    }

    private function createMaxiPagoTransactionsTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'maxipago_transactions` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `id_order` INT(10) UNSIGNED NOT NULL ,
              `boleto_url` VARCHAR(255) NULL ,
              `online_debit_url` VARCHAR(255) NULL,
              `authentication_url` VARCHAR(255) NULL,
              `method` VARCHAR(255) NOT NULL, 
              `request` TEXT NOT NULL ,
              `return` TEXT NOT NULL ,
              `response_message` VARCHAR(255) NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
        $this->db->query($query);
    }

    private function createMaxiPagoRecurringTransactionsTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'maxipago_recurring_transactions` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, 
              `order_id` INT(10) UNSIGNED NOT NULL,
              `order_recurring_id` INT(10) UNSIGNED NOT NULL,
              `maxipago_order_id` VARCHAR(255) NOT NULL,
              `maxipago_status` VARCHAR(255) NOT NULL, 
              `request` TEXT NOT NULL,
              `response` TEXT NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
        $this->db->query($query);
    }

    public function uninstall()
    {
        $this->removeEvents();
        $this->deleteTables();
    }

    private function removeEvents()
    {
        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('maxipago_delete');
        //$this->model_setting_event->deleteEventByCode('maxipago_delete_order');
    }

    private function deleteTables()
    {
        $this->deleteCreditCardTokenTable();
        $this->deleteMaxiPagoTransactionsTable();
        $this->deleteMaxiPagoRecurringTransactionsTable();
    }

    private function deleteCreditCardTokenTable()
    {
        $query = 'DROP TABLE `' . DB_PREFIX . 'maxipago_cc_token`';
        $this->db->query($query);
    }

    private function deleteMaxiPagoTransactionsTable()
    {
        $query = 'DROP TABLE `' . DB_PREFIX . 'maxipago_transactions`';
        $this->db->query($query);
    }

    private function deleteMaxiPagoRecurringTransactionsTable()
    {
        $query = 'DROP TABLE `' . DB_PREFIX . 'maxipago_recurring_transactions`';
        $this->db->query($query);
    }

    public function index()
    {
        $this->loadDependencies();

        if($this->isSubmitingConfiguration() && $this->submitIsValid())
            $this->submitConfiguration();

        $this->loadConfigurationForm();
    }

    private function loadDependencies()
    {
        $this->load->language('extension/payment/maxipago');

        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');
        $this->load->model('localisation/geo_zone');
        //$this->load->model('customer/custom_field');
    }

    private function isSubmitingConfiguration()
    {
        return $this->request->server['REQUEST_METHOD'] == 'POST';
    }

    private function submitIsValid()
    {
        $this->error = array();

        if (!$this->user->hasPermission('modify', 'extension/payment/maxipago'))
            $this->error['permission'] = $this->language->get('text_error_permission');

        if (!$this->request->post['payment_maxipago_store_id'])
            $this->error['store_id'] = $this->language->get('text_error_store_id');

        if (!$this->request->post['payment_maxipago_store_key'])
            $this->error['store_key'] = $this->language->get('text_error_store_key');

        if (!$this->request->post['payment_maxipago_store_secret'])
            $this->error['store_secret'] = $this->language->get('text_error_store_secret');

        return empty($this->error);
    }

    private function submitConfiguration()
    {
        $this->model_setting_setting->editSetting('payment_maxipago', $this->request->post);
        $this->session->data['success'] = $this->language->get('text_success');
        $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
    }

    private function loadConfigurationForm()
    {
        $this->document->setTitle($this->language->get('heading_title'));

        $data = $this->loadConfigurationFormData();
        $view = $this->loadConfigurationFormView($data);
        $this->response->setOutput($view);
    }

    private function loadConfigurationFormData()
    {
        $data = array();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['action_url'] = $this->getActionURL();
        $data['cancel_url'] = $this->getCancelURL();
        $data['sync_url'] = $this->getSyncUrl();

        $data['notification_sync_url'] = $this->getCatalogUrl('synchronize', 'mpKey=' . $this->config->get('payment_maxipago_store_key'));
        $data['notification_url_success'] = $this->getCatalogUrl('success', 'mpKey=' . $this->config->get('payment_maxipago_store_key'));
        $data['notification_url_error'] = $this->getCatalogUrl('error', 'mpKey=' . $this->config->get('payment_maxipago_store_key'));
        $data['notification_url_notification'] = $this->getCatalogUrl('notification','mpKey=' . $this->config->get('payment_maxipago_store_key'));

        $data['breadcrumbs'] = $this->getBreadcrumbs();

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data = $this->loadSettingsOptions($data);
        $data = $this->loadSavedSettings($data);
        $data = $this->getFormErrors($data);
        $data = $this->getSessionMessages($data);

        return $data;
    }

    private function loadConfigurationFormView($data)
    {
        return $this->load->view('extension/payment/maxipago', $data);
    }

    private function getActionUrl()
    {
        return $this->url->link('extension/payment/maxipago', $this->getUserTokenParameter(), true);
    }

    private function getCancelUrl()
    {
        return $this->url->link('marketplace/extension', $this->getUserTokenParameter() . '&type=payment', true);
    }

    private function getSyncUrl()
    {
        return $this->url->link('extension/payment/maxipago/synchronize', $this->getUserTokenParameter(), true);
    }

    private function getCatalogUrl($type, $query_string = '')
    {
        $use_ssl = $this->config->get('config_secure');
        $catalog_base_url = new Url(HTTP_CATALOG,  $use_ssl ? HTTP_CATALOG : HTTPS_CATALOG);

        if(strlen($query_string) > 0 && $query_string[0] != '&')
            $query_string = '&' . $query_string;

        $notification_url = $catalog_base_url->link('extension/payment/maxipago/' . $type, $query_string, $use_ssl);
        return $notification_url;
    }

    private function getBreadcrumbs()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->getUserTokenParameter(), 'SSL')
        );

        $breadcrumbs[] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', $this->getUserTokenParameter() . '&type=payment', true)
        );

        $breadcrumbs[] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/maxipago', $this->getUserTokenParameter(), 'SSL')
        );

        return $breadcrumbs;
    }

    private function getFormErrors($data)
    {
        if (isset($this->error['permission']))
        {
            $data['error_permission'] = $this->error['permission'];
            unset($this->error['permission']);
        }

        if (isset($this->error['store_id']))
        {
            $data['error_store_id'] = $this->error['store_id'];
            unset($this->error['store_id']);
        }

        if (isset($this->error['store_key']))
        {
            $data['error_store_key'] = $this->error['store_key'];
            unset($this->error['store_key']);
        }

        if (isset($this->error['store_secret']))
        {
            $data['error_store_secret'] = $this->error['store_secret'];
            unset($this->error['store_secret']);
        }

        return $data;
    }

    private function loadSettingsOptions($data)
    {
        //$data = $this->loadCustomFields($data);
        $data = $this->loadCreditCardAcquirerProcessors($data);
        $data = $this->loadDebitCardAcquirerProcessors($data);
        $data = $this->loadInvoiceBanks($data);
        $data = $this->loadEftBanks($data);

        return $data;
    }

    private function getSessionMessages($data)
    {
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (isset($this->session->data['error'])) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        return $data;
    }

    /**
     * @deprecated
     * @param $data
     * @return mixed
     */
    private function loadCustomFields($data)
    {
        $address_custom_fields = array();
        $custom_fields = $this->model_customer_custom_field->getCustomFields();
        $custom_fields_url = $this->url->link('customer/custom_field', $this->getUserTokenParameter(), true);

        foreach($custom_fields as $custom_field)
        {
            if($custom_field['location'] == 'address')
            {
                $address_custom_fields[$custom_field['custom_field_id']] = $custom_field['name'];
            }
        }

        $data['custom_fields'] = $address_custom_fields;
        $data['custom_fields_url'] = $custom_fields_url;

        return $data;
    }

    private function loadCreditCardAcquirerProcessors($data)
    {
        $data['credit_card_visa_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('visa'));
        $data['credit_card_mastercard_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('mastercard'));
        $data['credit_card_amex_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('amex'));
        $data['credit_card_diners_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('diners'));
        $data['credit_card_elo_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('elo'));
        $data['credit_card_discover_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('discover'));
        $data['credit_card_hipercard_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('hipercard'));
        $data['credit_card_hiper_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('hiper'));
        $data['credit_card_jcb_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('jcb'));
        $data['credit_card_aura_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('aura'));
        $data['credit_card_credz_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForCreditCardAcquirer('credz'));

        return $data;
    }

    private function loadDebitCardAcquirerProcessors($data)
    {
        $data['debit_card_visa_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForDebitCardAcquirer('visa'));
        $data['debit_card_mastercard_processors'] = $this->getAcquirerProcessors($this->getAllowedProcessorsForDebitCardAcquirer('mastercard'));

        return $data;
    }

    private function loadInvoiceBanks($data)
    {
        $data['invoice_banks'] = array(
            '11' => 'Itaú',
            '12' => 'Bradesco',
            '13' => 'Banco do Brasil',
            '14' => 'HSBC',
            '15' => 'Santander',
            '16' => 'Caixa Econômica Federal'
        );

        return $data;
    }

    private function loadEftBanks($data)
    {
        $data['eft_banks'] = array(
            '17' => 'Bradesco',
            '18' => 'Itaú'
        );

        return $data;
    }

    private function getAllowedProcessorsForCreditCardAcquirer($acquirer_name)
    {
        $processors = array(
            'all' => array(1, 2, 3, 4, 5, 6, 9, 10),
            'amex' => array(1, 4, 5),
            'diners' => array(1, 2, 4, 5, 6),
            'elo' => array(1, 3, 4, 5),
            'discover' => array(1, 2, 4, 5, 6),
            'hipercard' => array(1, 2, 5),
            'hiper' => array(1, 2, 5),
            'jcb' => array(1, 2, 4, 5),
            'aura' => array(1, 4),
            'credz' => array(1, 2, 5)
        );

        if(in_array($acquirer_name, array_keys($processors)))
            return $processors[$acquirer_name];

        return $processors['all'];
    }

    private function getAllowedProcessorsForDebitCardAcquirer($acquirer_name)
    {
        $processors = array(
            'all' => array(1, 2, 3, 4, 5, 6, 9, 10)
        );

        if(in_array($acquirer_name, array_keys($processors)))
            return $processors[$acquirer_name];

        return $processors['all'];
    }

    private function getAcquirerProcessors($acquirer_allowed_processors)
    {
        $processors = array(
            '1' => $this->language->get('acquirer_processor_test_simulator'),
            '2' => 'RedeCard',
            '3' => 'GetNet',
            '4' => 'Cielo',
            '5' => 'e.Rede',
            '6' => 'Elavon',
            '9' => 'Stone',
            '10' => 'Bin'
        );

        foreach($processors as $id => $name)
            if(!in_array($id, $acquirer_allowed_processors))
                unset($processors[$id]);

        return $processors;
    }

    private function loadSavedSettings($data)
    {
        $data = $this->loadGeneralSettings($data);
        $data = $this->loadOrderStatusSettings($data);
        $data = $this->loadCreditCartSettings($data);
        $data = $this->loadDebitCartSettings($data);
        $data = $this->loadInvoiceSettings($data);
        $data = $this->loadEFTSettings($data);
        $data = $this->loadRedePaySettings($data);
        return $data;
    }

    private function loadGeneralSettings($data)
    {
        $general_fields = array(
            'payment_maxipago_status',
            'payment_maxipago_environment',
            'payment_maxipago_store_id',
            'payment_maxipago_store_key',
            'payment_maxipago_store_secret',
            //'payment_maxipago_address_number__field',
            //'payment_maxipago_address_complement_field',
            'payment_maxipago_log'
        );

        foreach ($general_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadOrderStatusSettings($data)
    {
        $order_status_fields = array(
            'payment_maxipago_order_status_processing',
            'payment_maxipago_order_status_authorized',
            'payment_maxipago_order_status_refunded',
            'payment_maxipago_order_status_approved',
            'payment_maxipago_order_status_cancelled'
        );

        foreach ($order_status_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadCreditCartSettings($data)
    {
        $credit_card_fields = array(
            'payment_maxipago_credit_card_enabled',
            'payment_maxipago_credit_card_processing_type',
            'payment_maxipago_credit_card_soft_descriptor',
            'payment_maxipago_credit_card_allow_save',
            'payment_maxipago_credit_card_maximum_installments',
            'payment_maxipago_credit_card_minimum_by_installments',
            'payment_maxipago_credit_card_installments_without_interest',
            'payment_maxipago_credit_card_interest_type',
            'payment_maxipago_credit_card_interest_rate',
            'payment_maxipago_credit_card_fraud_check',
            'payment_maxipago_credit_card_auto_void',
            'payment_maxipago_credit_card_auto_capture',
            'payment_maxipago_credit_card_fraud_processor',
            'payment_maxipago_credit_card_clearsale_app',
            'payment_maxipago_credit_card_use_3ds',
            'payment_maxipago_credit_card_mpi_processor',
            'payment_maxipago_credit_card_failure_action',
            'payment_maxipago_credit_card_visa_processor',
            'payment_maxipago_credit_card_mastercard_processor',
            'payment_maxipago_credit_card_amex_processor',
            'payment_maxipago_credit_card_diners_processor',
            'payment_maxipago_credit_card_elo_processor',
            'payment_maxipago_credit_card_discover_processor',
            'payment_maxipago_credit_card_hipercard_processor',
            'payment_maxipago_credit_card_hiper_processor',
            'payment_maxipago_credit_card_jcb_processor',
            'payment_maxipago_credit_card_aura_processor',
            'payment_maxipago_credit_card_credz_processor'
        );

        foreach ($credit_card_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadDebitCartSettings($data)
    {
        $debit_card_fields = array(
            'payment_maxipago_debit_card_enabled',
            'payment_maxipago_debit_card_soft_descriptor',
            'payment_maxipago_debit_card_mpi_processor',
            'payment_maxipago_debit_card_failure_action',
            'payment_maxipago_debit_card_visa_processor',
            'payment_maxipago_debit_card_mastercard_processor'
        );

        foreach ($debit_card_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadInvoiceSettings($data)
    {
        $invoice_fields = array(
            'payment_maxipago_invoice_enabled',
            'payment_maxipago_invoice_bank',
            'payment_maxipago_invoice_days_to_pay',
            'payment_maxipago_invoice_instructions'
        );

        foreach ($invoice_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadEFTSettings($data)
    {
        $eft_fields = array(
            'payment_maxipago_eft_enabled',
            'payment_maxipago_eft_banks'
        );

        foreach ($eft_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function loadRedePaySettings($data)
    {
        $redepay_fields = array(
            'payment_maxipago_redepay_enabled'
        );

        foreach ($redepay_fields as $field)
            $data[$field] = $this->getFieldValue($field);

        return $data;
    }

    private function getFieldValue($fieldname)
    {
        $field_on_post = isset($this->request->post[$fieldname]);
        return $field_on_post ? $this->request->post[$fieldname] : $this->config->get($fieldname);
    }

    private function getUserTokenParameter()
    {
        return 'user_token=' . $this->session->data['user_token'];
    }

    public function synchronize()
    {
        $this->load->language('extension/payment/maxipago');
        $this->load->model('extension/payment/maxipago');

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

            $this->session->data['success'] = count($updated_transactions) > 0 ? $this->getSyncSuccessText($updated_transactions) : $this->language->get('text_success_no_sync');
        } else
        {
            $this->session->data['success'] = $this->language->get('text_sync_no_rows');
        }

        $updated_registers_count = count($refunded_orders) + count($captured_orders) + count($updated_transactions);

        if($updated_registers_count == 0)
            $this->session->data['success'] = $this->language->get('text_sync_no_rows');
        else
        {
            $sync_message = '<li>' . sprintf($this->language->get('text_success_sync'), $updated_registers_count) . '</li>';

            if(count($refunded_orders) > 0)
                $sync_message .= '<li>' . sprintf($this->language->get('text_sync_orders_refunded'), implode(',', $refunded_orders)) . '</li>';

            if(count($captured_orders) > 0)
                $sync_message .= '<li>' . sprintf($this->language->get('text_sync_orders_captured'), implode(',', $captured_orders)) . '</li>';

            if(count($updated_transactions) > 0)
                $sync_message .= '<li>' . sprintf($this->language->get('text_sync_orders'), implode(',', $updated_transactions)) . '</li>';

            $this->session->data['success'] = '<ul>' . $sync_message . '</ul>';
        }

        $this->response->redirect($this->url->link('extension/payment/maxipago', $this->getUserTokenParameter(), true));
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

                if($this->model_extension_payment_maxipago->reverse($possibleRefundableOrder))
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
                    $captured = $this->model_extension_payment_maxipago->capture($possibleCapturableOrder);

                    if($captured)
                    {
                        array_push($capturedOrders, $possibleCapturableOrder['order_id']);
                    }
                }
            }
        }

        return $capturedOrders;
    }


    ///////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////
    //////////////////////// OLD :: Rewritting ////////////////////////
    ///////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * Sync maxipago Orders from the last 15 days
     */
    public function syncronize()
    {
        $success = array();
        $ordersUpdated = array();

        try {
            $this->load->language('extension/payment/maxipago');
            $this->load->model('extension/payment/maxipago');

            $total = 0;
            $consumerKey = isset($this->request->get['mpKey']) ? $this->request->get['mpKey'] : null;

            if (trim($consumerKey) && $consumerKey == $this->config->get('maxipago_consumer_key')) {

                $orderId = isset($this->request->get['orderId']) ? $this->request->get['orderId'] : null;

                $searchStatues = array(
                    '"ISSUED"',
                    '"VIEWED"',
                    '"BOLETO ISSUED"',
                    '"BOLETO VIEWED"',
                    '"PENDING"',
                    '"PENDING CONFIRMATION"',
                    '"AUTHORIZED"'
                );

                $date = new DateTime('-15 DAYS'); // first argument uses strtotime parsing
                $fromDate = $date->format('Y-m-d 00:00:00');

                $sql = 'SELECT *
                    FROM ' . DB_PREFIX . 'maxipago_transactions
                    WHERE `created_at` > "' . $fromDate . '" 
                    AND `response_message` IN (' . implode(',', $searchStatues). ')
                    ';

                if ($orderId) {
                    $sql .= 'AND `id_order` = "' . $orderId . '"';
                }

                $query = $this->db->query($sql);
                if ($query->num_rows) {

                    foreach ($query->rows as $transaction) {
                        $orderUpadted = $this->model_extension_payment_maxipago->sync($transaction);
                        if ($orderUpadted) {
                            $total++;
                            array_push($ordersUpdated, $orderUpadted);
                        }
                    }

                }

            }

            if ($total) {
                //Total de pedidos atualizados
                array_push($success, sprintf($this->language->get('text_success_sync'), $total));
                //Pedidos atualizados
                array_push($success, implode(', ', $ordersUpdated));
            } else {
                $success = $this->language->get('text_success_no_sync');
            }

            $this->session->data['success'] = $success;

        } catch (Exception $e) {
            $this->session->data['error'] = $this->language->get('text_error_sync');
        }

        $this->response->redirect($this->url->link('extension/payment/maxipago', 'token=' . $_REQUEST['user_token'], true));

    }

    /**
     * Reverse a cancelled order
     *
     * @param $route
     * @param $orders
     */
    public function reverse($route, $orders)
    {
        if ($this->config->get('maxipago_cc_order_reverse')) {
            $this->load->language('extension/payment/maxipago');
            $this->load->model('extension/payment/maxipago');

            try {
                $query = $this->db->query("
                  SELECT * 
                  FROM `" . DB_PREFIX . "order` AS o
                  JOIN " . DB_PREFIX . "maxipago_transactions  as mt ON o.order_id = mt.id_order
                  WHERE o.payment_code = 'maxipago' 
                  AND o.order_id IN (" . implode(',', $orders) . ");
                ");
                if ($query->num_rows) {
                    foreach ($query->rows as $order_info) {
                        $this->model_extension_payment_maxipago->reverse($order_info);
                    }
                }

            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
            }
        }
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
