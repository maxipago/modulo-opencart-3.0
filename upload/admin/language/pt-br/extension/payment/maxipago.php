<?php
$_['heading_title']       		        = 'maxiPago!';

$_['text_extension']	                = 'Extensões';
$_['text_success']                      = 'Sucesso: Você modificou os dados do módulo de pagamento maxiPago!';
$_['text_edit']                         = 'Editar maxiPago!';
$_['text_maxipago']                     = '<a target="_BLANK" href="https://maxipago.com"><img src="view/image/payment/maxipago.jpg" alt="maxiPago! Smart Payments" title="maxiPago! Smart Payments" style="border: 1px solid #EEEEEE;height:32px;"/></a>';
$_['text_select']                       = 'Selecione ...';

// HEADER
$_['text_payment']                      = 'Pagamento';

$_['text_sync']                         = 'Sincronizar Pedidos';
$_['button_sync']                       = 'Sincronizar';
$_['text_save']                         = 'Salvar Alterações nas Configurações';
$_['button_save']                       = 'Salvar';
$_['text_cancel']                       = 'Voltar para Extensões';
$_['button_cancel']                     = 'Voltar';

$_['text_cron']                         = 'O botão "Sincronizar" consulta na maxiPago! se há atualização dos pedidos pendentes. Para que a consulta seja feita automaticamente, deve-se criar uma cron no seu servidor com o comando abaixo:';

$_['text_notification']                 = 'URLs de notificação';
$_['text_notification_configure']       = 'Configure essas URLs dentro do painel do maxiPago!';
$_['text_notification_success']         = 'Sucesso';
$_['text_notification_error']           = 'Erro';
$_['text_notification_notification']    = 'Notificação';

// BODY
$_['text_configure']                    = 'Configurar';

$_['tab_general']                       = 'Configurações Gerais';
$_['tab_order_status']                  = 'Status dos Pedidos';
$_['tab_credit_card']                   = 'Cartão de Crédito';
$_['tab_debit_card']                    = 'Cartão de Débito';
$_['tab_invoice']                       = 'Boleto';
$_['tab_eft']                           = 'TEF';
$_['tab_redepay']                       = 'RedePay';

// BODY/GENERAL
$_['status_label']                      = 'Status';
$_['status_tooltip']         	        = 'Habilita/desabilita esta extensão';

$_['environment_label']                 = 'Ambiente';
$_['environment_tooltip']         	    = 'Escolha se a extensão executará em ambiente de teste ou produção';
$_['environment_entry_test']            = 'Simulador de Testes';
$_['environment_entry_production']      = 'Produção';

$_['merchant_id_label']                 = 'ID da Loja';
$_['merchant_id_tooltip']         	    = 'ID da Loja fornecido pela maxiPago!';

$_['merchant_key_label']                = 'Chave Pública da Loja';
$_['merchant_key_tooltip']         	    = 'Chave públic de acesso da loja, fornecido pela maxiPago!';

$_['merchant_secret_label']             = 'Chave Privada da Loja';
$_['merchant_secret_tooltip']         	= 'Chave secreta de acesso da loja, fornecido pela maxiPago!';

$_['address_number_field_tooltip']      = 'Campo personalizado para o número do endereço';
$_['address_number_field_label']        = 'Campo de Número';

$_['address_complement_field_tooltip']  = 'Campo personalizado para o complemento do endereço';
$_['address_complement_field_label']    = 'Campo de Complemento';

$_['log_label']                         = 'Log';
$_['log_tooltip']         	            = 'Habilita/desabilita logs do sistema';

$_['text_error_permission']    		    = 'Atenção: Você não possui permissão para modificar a maxiPago!!';
$_['text_error_store_id']               = 'Digite o ID da loja!';
$_['text_error_store_key']         		= 'Digite a chave pública da loja!';
$_['text_error_store_secret']           = 'Digite a chave privada da loja!';

// BODY/ORDER STATUS
$_['status_processing_label'] 	        = 'Análise';
$_['status_processing_tooltip']		    = 'O maxiPago! recebeu a transação e o pedido está em análise';

$_['status_authorized_label'] 	        = 'Autorizado';
$_['status_authorized_tooltip']		    = 'A transação foi finalizada e o pagamento autorizado';

$_['status_refunded_label'] 	        = 'Estornado';
$_['status_refunded_tooltip']		    = 'Quando mudar para esse status, irá gerar um estorno para a maxiPago!';

$_['status_approved_label'] 	        = 'Aprovado';
$_['status_approved_tooltip']		    = 'A transação foi finalizada e o pagamento aprovado';

$_['status_cancelled_label'] 	        = 'Cancelado';
$_['status_cancelled_tooltip']		    = 'A transação foi cancelada, o pagamento foi negado, estornado ou ocorreu um chargeback';

// BODY/CREDIT CARD
$_['credit_card_enable_label']                          = 'Status';
$_['credit_card_enable_tooltip']                        = 'Habilita/desabilita este método de pagamento';

$_['credit_card_processing_type_label']                 = 'Tipo de Processamento';
$_['credit_card_processing_type_tooltip']               = 'Tipo de processamento realizado pela maxiPago!';
$_['text_processing_type_auth']                         = 'Autorização';
$_['text_processing_type_sale']                         = 'Venda Direta';

$_['credit_card_soft_descriptor_label']                 = 'Nome na Fatura';
$_['credit_card_soft_descriptor_tooltip']               = 'Nome que aparece na fatura do cartão';

$_['credit_card_allow_save_label']                      = 'Permite Tokenização';
$_['credit_card_allow_save_tooltip']                    = 'Salva o token do cartão de crédito para futuras compras';

$_['credit_card_maximum_installments_label']            = 'Máximo de Parcelas';
$_['credit_card_maximum_installments_tooltip']          = 'Quantidade máxima de parcelas permitido';
$_['credit_card_maximum_installments_at_sight']         = 'À Vista';

$_['credit_card_minimum_by_installments_label']         = 'Valor Mínimo por Parcela';
$_['credit_card_minimum_by_installments_tooltip']       = 'Valor mínimo permitido por parcela';

$_['credit_card_installments_without_interest_label']   = 'Quantidade de Parcelas sem Juros';
$_['credit_card_installments_without_interest_tooltip'] = 'Quantidade de parcelas sem juros';

$_['credit_card_interest_type_label']                   = 'Cálculo de Juros';
$_['credit_card_interest_type_tooltip']                 = 'Forma como o juros é calculado';
$_['text_credit_card_interest_simple']                  = 'Simples';
$_['text_credit_card_interest_compound']                = 'Composto';
$_['text_credit_card_interest_price']                   = 'Tabela Price';

$_['credit_card_interest_rate_label']                   = 'Taxa de Juros (%)';
$_['credit_card_interest_rate_tooltip']                 = 'Taxa para cálculo do juros';

$_['credit_card_fraud_check_label']                     = 'Verificação de Fraude';
$_['credit_card_fraud_check_tooltip']                   = 'Habilita/desabilita a verificação de fraude';

$_['credit_card_auto_void_label']                       = 'Auto Cancelar';
$_['credit_card_auto_void_tooltip']                     = 'Cancela automaticamente transações de alto risco';

$_['credit_card_auto_capture_label']                    = 'Auto Capturar';
$_['credit_card_auto_capture_tooltip']                  = 'Captura automaticamente transações de baixo risco';

$_['credit_card_fraud_processor_label']                 = 'Processador de Fraude';
$_['credit_card_fraud_processor_tooltip']               = 'Responsável por realizar análise de fraude';

$_['credit_card_clearsale_app_label']                   = 'Clearsale App';
$_['credit_card_clearsale_app_tooltip']                 = 'Código app para o processador Clearsale';

$_['credit_card_use_3ds_label']                         = 'Utilizar 3DS';
$_['credit_card_use_3ds_tooltip']                       = 'Habilita/desabilita o uso de 3DS';

$_['credit_card_mpi_processor_label']                   = 'Processador IPM';
$_['credit_card_mpi_processor_tooltip']                 = 'IPM que irá processar o 3DS';
$_['credit_card_mpi_test']                              = 'Teste';
$_['credit_card_mpi_deployment']                        = 'Produção';

$_['credit_card_failure_action_label']                  = 'Ação ao Falhar';
$_['credit_card_failure_action_tooltip']                = 'Ação a ser tomada pelo IPM em caso de falha';
$_['credit_card_failure_action_decline']                = 'Parar Processamento';
$_['credit_card_failure_action_continue']               = 'Continuar Processamento';

$_['credit_card_acquirers_label']                       = 'Adquirintes';
$_['credit_card_acquirers_tooltip']                     = 'Escolha qual será o adquirinte para os cartões disponíveis';
$_['credit_card_processor_deactivate']                  = 'Desativar';
$_['acquirer_processor_test_simulator']                 = 'Simulador de Testes';
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
$_['debit_card_enable_tooltip']                         = 'Habilita/desabilita este método de pagamento';

$_['debit_card_soft_descriptor_label']                  = 'Nome na Fatura';
$_['debit_card_soft_descriptor_tooltip']                = 'Nome que aparece na fatura do cartão';

$_['debit_card_mpi_processor_label']                    = 'Processador IPM';
$_['debit_card_mpi_processor_tooltip']                  = 'IPM que irá processar a transação';
$_['debit_card_mpi_test']                               = 'Teste';
$_['debit_card_mpi_deployment']                         = 'Produção';

$_['debit_card_failure_action_label']                   = 'Ação ao Falhar';
$_['debit_card_failure_action_tooltip']                 = 'Ação a ser tomada pelo IPM em caso de falha';
$_['debit_card_failure_action_decline']                 = 'Parar Processamento';
$_['debit_card_failure_action_continue']                = 'Continuar Processamento';

$_['debit_card_acquirers_label']                        = 'Adquirintes';
$_['debit_card_acquirers_tooltip']                      = 'Escolha qual será o adquirinte para os cartões disponíveis';
$_['debit_card_processor_deactivate']                   = 'Desativar';
$_['acquirer_processor_test_simulator']                 = 'Simulador de Testes';
$_['debit_card_visa_processor_label']                   = 'Visa';
$_['debit_card_mastercard_processor_label']             = 'Mastercard';

// BODY/INVOICE
$_['invoice_enable_label']              = 'Status';
$_['invoice_enable_tooltip']            = 'Habilita/desabilita este método de pagamento';

$_['invoice_bank_label']                = 'Banco do Boleto';
$_['invoice_bank_tooltip']              = 'Banco emissor do boleto';

$_['invoice_days_to_pay_label']         = 'Dias para Vencimento';
$_['invoice_days_to_pay_tooltip']       = 'Dias para realizar o pagamento do boleto';

$_['invoice_instructions_label']        = 'Instruções';
$_['invoice_instructions_tooltip']      = 'Instruções que virão escritas no boleto';

// BODY/EFT
$_['eft_enable_label']      = 'Status';
$_['eft_enable_tooltip']    = 'Habilita/desabilita este método de pagamento';

$_['eft_bank_label']        = 'Bancos';
$_['eft_bank_tooltip']      = 'Bancos permitidos';

// BODY/REDEPAY
$_['redepay_enable_label']      = 'Status';
$_['redepay_enable_tooltip']    = 'Habilita/desabilita este método de pagamento';

// TEXTS
$_['text_error_sync']               = 'Erro: Ocorreu um erro com a integração, tente novamente mais tarde ou entre <a href="https://www.maxipago.com.br/fale-conosco/" target="_blank">em contato conosco </a>';
$_['text_sync_no_rows']             = 'Nenhum pedido para sincronizar';
$_['text_success_sync']             = 'Sucesso: %s atualizações';
$_['text_success_no_sync']          = 'Sincronização finalizada: nenhuma atualização';
$_['comment_updated_order']    	    = 'O pedido foi atualizado pelo maxiPago! com o status:';
$_['text_sync_orders_refunded']     = 'Reembolsos: %s';
$_['text_sync_orders_captured']     = 'Capturas: %s';
$_['text_sync_orders']              = 'Pedidos atualizados: %s';
$_['text_error_sync_invalid_key']   = 'Credenciais Inválidas';

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