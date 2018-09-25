<?php
$_['heading_title']       		        = 'maxiPago!';

$_['text_extension']	                = 'Extensions';
$_['text_success']                      = 'Success: You have modified maxiPago! payment module! ';
$_['text_edit']                         = 'Edit maxiPago!';
$_['text_maxipago']                     = '<a target="_BLANK" href="https://maxipago.com"><img src="view/image/payment/maxipago.jpg" alt="maxiPago! Smart Payments" title="maxiPago! Smart Payments" style="border: 1px solid #EEEEEE;height:32px;"/></a>';
$_['text_select']                       = 'Choose One ...';

// HEADER
$_['text_payment']                      = 'Payment';

$_['text_sync']                         = 'Synchronize Orders';
$_['button_sync']                       = 'Synchronize';
$_['text_save']                         = 'Save Settings Change';
$_['button_save']                       = 'Save';
$_['text_cancel']                       = 'Return to Extensions';
$_['button_cancel']                     = 'Return';

$_['text_cron']                         = 'The "Synchronize" button checks maxiPago! for pending orders update. For automatic synchronization, an cron must be created on your server, with the command bellow:';

$_['text_notification']                 = 'Notification URL\'s';
$_['text_notification_configure']       = 'Configure these URL\'s on maxiPago! panel.';
$_['text_notification_success']         = 'Success';
$_['text_notification_error']           = 'Error';
$_['text_notification_notification']    = 'Notification';

// BODY
$_['text_configure']                    = 'Configure';

$_['tab_general']                       = 'General Settings';
$_['tab_order_status']                  = 'Order Status';
$_['tab_credit_card']                   = 'Credit Card';
$_['tab_debit_card']                    = 'Debit Card';
$_['tab_invoice']                       = 'Invoice';
$_['tab_eft']                           = 'EFT';
$_['tab_redepay']                       = 'RedePay';

// BODY/GENERAL
$_['status_label']                      = 'Status';
$_['status_tooltip']         	        = 'Enable/disable this extension';

$_['environment_label']                 = 'Environment';
$_['environment_tooltip']         	    = 'Select if this extension will run on test or deployment environment';
$_['environment_entry_test']            = 'Test Simulator';
$_['environment_entry_production']      = 'Deploy';

$_['merchant_id_label']                 = 'Merchant ID';
$_['merchant_id_tooltip']         	    = 'Merchant id provided by maxiPago!';

$_['merchant_key_label']                = 'Merchant Key';
$_['merchant_key_tooltip']         	    = 'Merchant key provided by maxiPago!';

$_['merchant_secret_label']             = 'Merchant Secret';
$_['merchant_secret_tooltip']         	= 'Merchant secret provided by maxiPago!';

$_['log_label']                         = 'Log';
$_['log_tooltip']         	            = 'Enable/disable system loggin';

$_['address_number_field_tooltip']      = 'Custom field for address number';
$_['address_number_field_label']        = 'Number Field';

$_['address_complement_field_tooltip']  = 'Custom field for address complement';
$_['address_complement_field_label']    = 'Complement Field';

$_['text_error_permission']    		    = 'Warning: You don\'t have permission to change maxiPago! settings!';
$_['text_error_store_id']               = 'Type the store ID!';
$_['text_error_store_key']         		= 'Type the store key!';
$_['text_error_store_secret']           = 'Type the store secret!';

// BODY/ORDER STATUS
$_['status_processing_label'] 	        = 'Analysis';
$_['status_processing_tooltip']		    = 'maxiPago! received the transaction and the order is under analysis';

$_['status_authorized_label'] 	        = 'Authorized';
$_['status_authorized_tooltip']		    = 'Transaction complete with authorized payment';

$_['status_refunded_label'] 	        = 'Refunded';
$_['status_refunded_tooltip']		    = 'When and order changes to this status, a refund operation will be fired to maxiPago!';

$_['status_approved_label'] 	        = 'Approved';
$_['status_approved_tooltip']		    = 'Transaction complete with approved payment';

$_['status_cancelled_label'] 	        = 'Canceled';
$_['status_cancelled_tooltip']		    = 'Transaction canceled, payment denied, refunded or a chargeback ocurred';

// BODY/CREDIT CARD
$_['credit_card_enable_label']                          = 'Status';
$_['credit_card_enable_tooltip']                        = 'Enable/disable this payment method';

$_['credit_card_processing_type_label']                 = 'Processing Type';
$_['credit_card_processing_type_tooltip']               = 'maxiPago! processing type for the transactions';
$_['text_processing_type_auth']                         = 'Authorization';
$_['text_processing_type_sale']                         = 'Sale';

$_['credit_card_soft_descriptor_label']                 = 'Soft Descriptor';
$_['credit_card_soft_descriptor_tooltip']               = 'Description that appears on card invoice';

$_['credit_card_allow_save_label']                      = 'Allow Card Tokenization';
$_['credit_card_allow_save_tooltip']                    = 'Saves credit card token for future purchases';

$_['credit_card_maximum_installments_label']            = 'Maximum Installments';
$_['credit_card_maximum_installments_tooltip']          = 'Maximum installments allowed';
$_['credit_card_maximum_installments_at_sight']         = 'At Sight';

$_['credit_card_minimum_by_installments_label']         = 'Minimum Installment Value';
$_['credit_card_minimum_by_installments_tooltip']       = 'Minimum value required by installment';

$_['credit_card_installments_without_interest_label']   = 'Installments Amount without Interest';
$_['credit_card_installments_without_interest_tooltip'] = 'Installments Amount without Interest';

$_['credit_card_interest_type_label']                   = 'Interest Type';
$_['credit_card_interest_type_tooltip']                 = 'How interest is calculated';
$_['text_credit_card_interest_simple']                  = 'Simple';
$_['text_credit_card_interest_compound']                = 'Compound';
$_['text_credit_card_interest_price']                   = 'Price';

$_['credit_card_interest_rate_label']                   = 'Interest Rate (%)';
$_['credit_card_interest_rate_tooltip']                 = 'Rate for interest account';

$_['credit_card_fraud_check_label']                     = 'Fraud Check';
$_['credit_card_fraud_check_tooltip']                   = 'Enable/disable fraud check';

$_['credit_card_auto_void_label']                       = 'Auto Void';
$_['credit_card_auto_void_tooltip']                     = 'Automatically void high risk transactions';

$_['credit_card_auto_capture_label']                    = 'Auto Capture';
$_['credit_card_auto_capture_tooltip']                  = 'Automatically capture low risk transactions';

$_['credit_card_fraud_processor_label']                 = 'Fraud Processor';
$_['credit_card_fraud_processor_tooltip']               = 'Responsible for fraud analysis';

$_['credit_card_clearsale_app_label']                   = 'Clearsale App';
$_['credit_card_clearsale_app_tooltip']                 = 'App code for Clearsale processor';

$_['credit_card_use_3ds_label']                         = 'Use 3DS';
$_['credit_card_use_3ds_tooltip']                       = 'Enable/disable 3DS';

$_['credit_card_mpi_processor_label']                   = 'MPI Processor';
$_['credit_card_mpi_processor_tooltip']                 = 'MPI that will process the 3DS';
$_['credit_card_mpi_test']                              = 'Test';
$_['credit_card_mpi_deployment']                        = 'Deployment';

$_['credit_card_failure_action_label']                  = 'Failure Action';
$_['credit_card_failure_action_tooltip']                = 'Action taken by MPI on failure cases';
$_['credit_card_failure_action_decline']                = 'Stop Processing';
$_['credit_card_failure_action_continue']               = 'Keep Processing';

$_['credit_card_acquirers_label']                       = 'Acquirers';
$_['credit_card_acquirers_tooltip']                     = 'Choose the acquirer for each credit card';
$_['credit_card_processor_deactivate']                  = 'Deactivate';
$_['acquirer_processor_test_simulator']                 = 'Test Simulator';
$_['credit_card_visa_processor_label']                  = 'Visa';
$_['credit_card_mastercard_processor_label']            = 'Mastercard';
$_['credit_card_amex_processor_label']                  = 'Amex';
$_['credit_card_diners_processor_label']                = 'Diners';
$_['credit_card_elo_processor_label']                   = 'Elo';
$_['credit_card_discover_processor_label']              = 'Discover';
$_['credit_card_hipercard_processor_label']             = 'Hipercard';
$_['credit_card_hiper_processor_label']                 = 'Hiper';
$_['credit_card_jcb_processor_label']                   = 'JCB';
$_['credit_card_aura_processor_label']                  = 'Aura';
$_['credit_card_credz_processor_label']                 = 'Credz';

// BODY/DEBIT CARD
$_['debit_card_enable_label']                           = 'Status';
$_['debit_card_enable_tooltip']                         = 'Enable/disable this payment method';

$_['debit_card_soft_descriptor_label']                  = 'Soft Descriptor';
$_['debit_card_soft_descriptor_tooltip']                = 'Description that appears on card invoice';

$_['debit_card_mpi_processor_label']                    = 'MPI Processor';
$_['debit_card_mpi_processor_tooltip']                  = 'MPI that will process the transaction';
$_['debit_card_mpi_test']                               = 'Teste';
$_['debit_card_mpi_deployment']                         = 'Produção';

$_['debit_card_failure_action_label']                   = 'Failure Action';
$_['debit_card_failure_action_tooltip']                 = 'Action taken by MPI on failure cases';
$_['debit_card_failure_action_decline']                 = 'Stop Processing';
$_['debit_card_failure_action_continue']                = 'Keep Processing';

$_['debit_card_acquirers_label']                        = 'Acquirers';
$_['debit_card_acquirers_tooltip']                      = 'Choose the acquirer for each credit card';
$_['debit_card_processor_deactivate']                   = 'Deactivate';
$_['acquirer_processor_test_simulator']                 = 'Test Simulator';
$_['debit_card_visa_processor_label']                   = 'Visa';
$_['debit_card_mastercard_processor_label']             = 'Mastercard';

// BODY/INVOICE
$_['invoice_enable_label']              = 'Status';
$_['invoice_enable_tooltip']            = 'Enable/disable this payment method';

$_['invoice_bank_label']                = 'Invoice Bank';
$_['invoice_bank_tooltip']              = 'Invoice issuing bank';

$_['invoice_days_to_pay_label']         = 'Days to Pay';
$_['invoice_days_to_pay_tooltip']       = 'Days to pay the invoice';

$_['invoice_instructions_label']        = 'Instructions';
$_['invoice_instructions_tooltip']      = 'Instructions written on the invoice';

// BODY/EFT
$_['eft_enable_label']      = 'Status';
$_['eft_enable_tooltip']    = 'Enable/disable this payment method';

$_['eft_bank_label']        = 'Banks';
$_['eft_bank_tooltip']      = 'Allowed banks';

// BODY/REDEPAY
$_['redepay_enable_label']      = 'Status';
$_['redepay_enable_tooltip']    = 'Enable/disable this payment method';

// TEXTS
$_['text_error_sync']               = 'Error: And error ocurred within the integration, try again later or <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">contact us</a>';
$_['text_sync_no_rows']             = 'No order to synchronize';
$_['text_success_sync']             = 'Success: %s updates';
$_['text_success_no_sync']          = 'Synchronization completed: no order was updated';
$_['comment_updated_order']    	    = 'The order was updated by maxiPago with status:';
$_['text_sync_orders_refunded']     = 'Refunds: %s';
$_['text_sync_orders_captured']     = 'Captures: %s';
$_['text_sync_orders']              = 'Orders: %s';
$_['text_error_sync_invalid_key']   = 'Invalid Credentials';

////////////
/*
// Text
$_['text_success_no_sync'] 		= 'Consulta finalizada, nenhum pedido foi atualizado';
$_['text_success_sync']    		= 'Sucesso: um total de %s pedidos foram atualizados';
$_['text_success_order_sync']   = 'Pedido: %s';
$_['text_error_sync']    		= 'Erro: Ocorreu um erro com a integração, tente novamente mais tarde ou entre <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">em contato conosco </a>';
$_['text_error_reverse']    	= 'Erro: Ocorreu um erro com a integração e o pedido não foi estornado na maxiPago!. Entre em contato para fazer o procedimento manual <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">em contato conosco </a>';
$_['comment_updated_order']    	= 'O pedido foi atualizado pelo maxiPago! com o status:';

//Order Messages
$_['order_message_processing']  = 'O pedido está em Análise pela maxiPago!';
$_['order_message_complete']    = 'O pedido foi aprovado pela maxiPago!';
$_['order_message_cancelled']   = 'O pedido foi cancelado pela maxiPago!';
$_['order_message_reverse']     = 'O pedido foi estornado para a maxiPago!';

//Buttons

$_['boleto_text']               = 'Gerar Boleto';
$_['order_deleted']             = 'O pedido foi excluído da administração da loja e estornado ao maxiPago!';
$_['order_cancelled']           = 'O pedido foi cancelado na administração da loja';
*/