<?php
$_['text_title_credit_card']    = 'Cartão de Crédito';
$_['text_title_debit_card']     = 'Cartão de Débito';
$_['text_title_eft']            = 'Transferência Eletrônica';
$_['text_title_invoice']        = 'Boleto Bancário';
$_['text_title_redepay']        = 'RedePay';

$_['bad_configuration_title']   = 'Problema de Configuração';
$_['bad_configuration_text']    = 'O gateway maxiPago! encontrou um problema de configuração. O gateway está ativo, mas nenhum método de pagamento foi ativado.';

// CREDIT CARD
$_['credit_card_submit_checkout']           = 'Pagar com Cartão de Crédito';
$_['credit_card_delete_card']               = 'Deletar Cartão Selecionado';
$_['credit_card_use_card_label']            = 'Utilizar Cartão Salvo';
$_['credit_card_brand_label']               = 'Bandeira';
$_['credit_card_card_number_label']         = 'Número do Cartão';
$_['credit_card_card_owner_label']          = 'Proprietário do Cartão';
$_['credit_card_card_owner_placeholder']    = 'Nome impresso no cartão';
$_['credit_card_expiry_month_label']         = 'Vencimento (Mês)';
$_['credit_card_expiry_year_label']         = 'Vencimento (Ano)';
$_['credit_card_cvv_label']                 = 'Código de Segurança';
$_['credit_card_cvv_placeholder']           = 'CVV';
$_['credit_card_installments_label']        = 'Parcelas';
$_['credit_card_document_label']            = 'CPF/CNPJ';
$_['credit_card_save_card']                 = 'Salvar Cartão';

// DEBIT CARD
$_['debit_card_submit_checkout']        = 'Pagar com Cartão de Débito';
$_['debit_card_brand_label']            = 'Bandeira';
$_['debit_card_card_number_label']      = 'Número do Cartão';
$_['debit_card_card_owner_label']       = 'Proprietário do Cartão';
$_['debit_card_card_owner_placeholder'] = 'Nome impresso no cartão';
$_['debit_card_expiry_month_label']     = 'Vencimento (Mês)';
$_['debit_card_expiry_year_label']      = 'Vencimento (Ano)';
$_['debit_card_cvv_label']              = 'Código de Segurança';
$_['debit_card_cvv_placeholder']        = 'CVV';
$_['debit_card_document_label']         = 'CPF/CNPJ';

// EFT
$_['eft_submit_checkout']               = 'Pagar com TEF';
$_['eft_document_label']                = 'CPF/CNPJ';
$_['eft_bank_label']                    = 'Banco';

// INVOICE
$_['invoice_submit_checkout']           = 'Pagar com Boleto Bancário';
$_['invoice_document_label']            = 'CPF/CNPJ';

// REDEPAY
$_['redepay_submit_checkout']           = 'Pagar com RedePay';
$_['redepay_document_label']            = 'CPF/CNPJ';

// OTHERS
$_['unknown_method_transaction_error']  = 'Erro: Método desconhecido';
$_['comment_updated_order']             = 'O pedido foi atualizado pelo maxiPago! com o status:';
$_['text_success_sync']                 = 'Sucesso: %s pedido(s) atualizado(s)';
$_['text_success_no_sync']              = 'Sincronização finalizada: nenhum pedido foi atualizado';
$_['text_error_sync']                   = 'Erro: Ocorreu um erro com a integração, tente novamente mais tarde ou entre <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">em contato conosco </a>';
$_['exception_method_not_allowed']      = 'Método não permitido';
$_['exception_recurrency_not_supported']= 'Recorrência com tempo de teste pago não pode ser pago!';
$_['order_cc_text']                     = 'Pedido finzalido na maxiPago! por cartão de crédito, o status do seu pedido é:';
$_['maxipago_order_cancelled']          = 'O pedido foi cancelado pela maxiPago!';
$_['order_error']                       = 'Houve um erro com seu pedido, entre em contato conosco para maiores informações';

$_['eft_link_text']                     = 'Finalizar o Pedido na instituição bancária';
$_['order_eft_text']                    = 'Pedido finalizado na maxiPago!, faça o pagamento clicando no link a seguir: ';
$_['debit_card_link_text']              = 'Finalizar pagamento';
$_['order_debit_card_text']             = 'Pedido finalizado na maxiPago!, faça o pagamento com o banco no link a seguir:';
$_['invoice_link_text']                 = 'Gerar Boleto';
$_['order_invoice_text']                = 'Pedido finalizado na maxiPago!, faça o pagamento do boleto no link a seguir:';
$_['redepay_link_text']                 = 'Pagar na RedePay';
$_['order_redepay_text']                = 'Pedido finalizado na maxiPago!, faça o pagamento na RedePay no link a seguir:';

$_['common_products_transaction_message']   = '%s produto(s) finalizado(s) com cartão de crédito com status %s';
$_['recurring_product_transaction_message'] = 'Produto "%s" com recorrência "%s" pago com cartão de crédito';

$_['text_error_response_voided_on_common']      = 'Status [%s] para a compra com cartão de crédito';
$_['text_error_responses_voided_on_recurring']  = 'Status [%s] no produto "%s" com recorrência "%s"';





$_['text_title'] 		    = 'maxiPago!';
$_['button_confirm'] 	    = 'Finalizar pedido com';
$_['button_sending_text']   = 'Processando...';
$_['ticket_link_text']      = 'Gerar Boleto Bancário';

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

$_['order_cancelled']           = 'O pedido foi cancelado na administração da loja';


$_['order_ticket_text'] = 'Pedido finzalido na maxiPago! por Boleto Bancário.';

$_['comment_update_order'] = 'Pedido atualizado na maxiPago! com o status: ';