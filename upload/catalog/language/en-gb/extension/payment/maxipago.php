<?php
$_['text_title_credit_card']    = 'Credit Card';
$_['text_title_debit_card']     = 'Debit Card';
$_['text_title_eft']            = 'Electronic Funds Transfer';
$_['text_title_invoice']        = 'Invoice';
$_['text_title_redepay']        = 'RedePay';

$_['bad_configuration_title']   = 'Bad Configuration';
$_['bad_configuration_text']    = 'maxiPago! gateway is facing a configuration problem. Gateway is enabled, but there\'s no enabled payment method.';

// CREDIT CARD
$_['credit_card_submit_checkout']           = 'Pay using Credit Card';
$_['credit_card_delete_card']               = 'Delete Selected Card';
$_['credit_card_use_card_label']            = 'Use Existing Card';
$_['credit_card_brand_label']               = 'Brand';
$_['credit_card_card_number_label']         = 'Card Number';
$_['credit_card_card_owner_label']          = 'Card Owner';
$_['credit_card_card_owner_placeholder']    = 'Name printed on the card';
$_['credit_card_expiry_month_label']        = 'Expiry Date (Month)';
$_['credit_card_expiry_year_label']         = 'Expiry Date (Year)';
$_['credit_card_cvv_label']                 = 'Security Code';
$_['credit_card_cvv_placeholder']           = 'CVV';
$_['credit_card_installments_label']        = 'Installments';
$_['credit_card_document_label']            = 'CPF/CNPJ';
$_['credit_card_save_card']                 = 'Save Card';

// DEBIT CARD
$_['debit_card_submit_checkout']        = 'Pay using Debit Card';
$_['debit_card_brand_label']            = 'Brand';
$_['debit_card_card_number_label']      = 'Card Number';
$_['debit_card_card_owner_label']       = 'Card Owner';
$_['debit_card_card_owner_placeholder'] = 'Name printed on the card';
$_['debit_card_expiry_month_label']     = 'Expiry Date (Month)';
$_['debit_card_expiry_year_label']      = 'Expiry Date (Year)';
$_['debit_card_cvv_label']              = 'Security Code';
$_['debit_card_cvv_placeholder']        = 'CVV';
$_['debit_card_document_label']         = 'CPF/CNPJ';

// EFT
$_['eft_submit_checkout']               = 'Pay using EFT';
$_['eft_document_label']                = 'CPF/CNPJ';
$_['eft_bank_label']                    = 'Bank';

// INVOICE
$_['invoice_submit_checkout']           = 'Pay using Invoice';
$_['invoice_document_label']            = 'CPF/CNPJ';

// REDEPAY
$_['redepay_submit_checkout']           = 'Pay with RedePay';
$_['redepay_document_label']            = 'CPF/CNPJ';

// OTHERS
$_['unknown_method_transaction_error']  = 'Error: Unknown method';
$_['comment_updated_order']             = 'The order was updated by maxiPago with status:';
$_['text_success_sync']                 = 'Success: %s updated order(s)';
$_['text_success_no_sync']              = 'Synchronization completed: no order was updated';
$_['text_error_sync']                   = 'Error: And error ocurred within the integration, try again later or <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">contact us</a>';
$_['exception_method_not_allowed']      = 'method not allowed';
$_['exception_recurrency_not_supported']= 'Recurring payment with paid trial are not supported!';
$_['order_cc_text']                     = 'Order placed by credit card, the status of your order is:';
$_['maxipago_order_cancelled']          = 'Order cancelled by maxiPago!';
$_['order_error']                       = 'There was an error with your request, contact us for more information';

$_['eft_link_text']                     = 'Pay the order within the bank';
$_['order_eft_text']                    = 'Order placed, you can pay by clicking on the link:';
$_['debit_card_link_text']              = 'Pay the order within the bank';
$_['order_debit_card_text']             = 'Order placed, you can pay by clicking on the link:';
$_['invoice_link_text']                 = 'Generate the Invoice';
$_['order_invoice_text']                = 'Order placed, you can pay by clicking on the link:';
$_['redepay_link_text']                 = 'Pay with RedePay';
$_['order_redepay_text']                = 'Order placed, you can pay by clicking on the link:';

$_['common_products_transaction_message']   = '%s product(s) finnished by credit card with status %s';
$_['recurring_product_transaction_message'] = 'Product "%s" with recurring profile "%s" paid by credit card';

$_['text_error_response_voided_on_common']      = 'Status [%s] for credit card';
$_['text_error_responses_voided_on_recurring']  = 'Status [%s] for product "%s" with recurring profile "%s"';


$_['text_title'] 		    = 'maxiPago!';
$_['button_confirm'] 	    = 'Finalizar pedido com';
$_['button_sending_text']   = 'Processando...';
$_['ticket_link_text']      = 'Gerar Boleto Bancário';
$_['eft_link_text']         = 'Finalizar o Pedido na instituição bancária';
$_['text_error_reverse']    = 'Erro (%s): Ocorreu um erro com a integração e o pedido não foi estornado. Entre em contato para fazer o procedimento manual <a href="https://www.maxipago.com.br/" target="_blank">em contato conosco </a>';

//Errors
$_['error_transaction']       = 'Ocorreu um erro ao finalizar o pedido, tente novamente ou com outra forma de pagamento.';
$_['error_already_processed'] = 'Pedido já foi processado pela maxiPago!';
$_['error_save_card'] = 'maxiPago! Não foi possível salvar o cartão de crédito.';

$_['error_cc_brand']    = 'Selecione a bandeira do cartão de crédito';
$_['error_cc_number']   = 'Número de cartão inválido';
$_['error_cc_owner']    = 'Preencha o nome no cartão';
$_['error_cc_cvv2']     = 'CVV inválido';
$_['error_eft_bank']    = 'Selecione o banco ';
$_['error_cpf']         = 'CPF inválido';

$_['ticket_text']   = 'Boleto Bancário';
$_['cc_text']       = 'Cartão de Crédito';
$_['eft_text']      = 'Transferência Eletrônica';

$_['entry_cpf_number'] = 'CPF';

//CC
$_['entry_select']          = 'Selecione';
$_['entry_cc_owner']        = 'Nome no Cartão';
$_['entry_cc_type']         = 'Bandeira';
$_['entry_cc_number']       = 'Número no Cartão';
$_['entry_cc_expire_date']  = 'Data de Expiração';
$_['entry_cc_cvv']          = 'CVV';
$_['entry_cc_cvv2']         = 'Código de Segurança';
$_['entry_installments']    = 'Parcelas';
$_['entry_per_month']       = 'a.m.';
$_['entry_without_interest']= 'sem juros';
$_['entry_total']           = 'Total';
$_['entry_save_card']       = 'Salvar Cartão';
$_['entry_use_saved_card']  = 'Usar Cartão Salvo';
$_['entry_remove_card']     = 'Remover Cartão';

//EFT
$_['entry_eft_bank']        = 'Banco';

//Ticket
$_['entry_ticket_instructions']  = 'Ao finalizar, você receberá um link para impressão do boleto';


$_['order_message_complete']    = 'O pedido foi finalizado';
$_['order_message_processing']  = 'O pedido foi processado pela maxiPago!';
$_['order_message_cancelled']   = 'O pedido foi cancelado pela maxiPago!';
$_['order_message_reverse']     = 'O pedido foi estornado para a maxiPago!';
$_['order_error']               = 'Houve um erro com seu pedido, entre em contato conosco para maiores informações';
$_['order_cancelled']           = 'O pedido foi cancelado na administração da loja';

$_['order_cc_text']     = 'Pedido finzalido na maxiPago! por cartão de crédito, o status do seu pedido é:';
$_['order_ticket_text'] = 'Pedido finzalido na maxiPago! por Boleto Bancário.';
$_['order_eft_text']    = 'Pedido finzalido na maxiPago, faça o pagamento clicando no link a seguir: ';

$_['comment_update_order'] = 'Order updated at maxiPago! to status: ';