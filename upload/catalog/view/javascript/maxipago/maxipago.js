var maxipago = {

    load: function() {
        var self = this;

        $('input.maxipago-type').on('click', function(){
            $('.maxipago-tab').hide();
            $('.maxipago-tab-' + $(this).val()).show();
        });

        $('input.maxipago-brand').on('click', function(){
            $('.mp-card-brand img').removeClass('active');
            $(this).siblings('img').addClass('active');
        });

        $('input.maxipago-bank').on('click', function(){
            $('.mp-card-bank img').removeClass('active');
            $(this).siblings('img').addClass('active');
        });

        $('#maxipago-form input').on('click focus', function(){
            self.hideError();
        });

        $('#button-confirm').on('click', function () {
            self.maxipagoRequest();
        });

        $('#remove-cc').click(function() {
            self.deleteCard();
        });

        self.changeSavedCard();
        self.mask();

    },

    showError: function(message) {
        if (!$('#maxipago-error').is(":visible")) {
            $('#maxipago-error').slideDown();
        }
        $("#maxipago-error").html(message)
    },

    hideError: function() {
        $('#maxipago-error').slideUp();
    },

    changeSavedCard: function() {
        $('#select-cc-saved-card').change(function(){
            var desc = $(this).val();
            if (desc) {
                $('.saved-cards .remove').show();
                $('#payment-maxipago-cc .new-card').hide();
            } else {
                $('.saved-cards .remove').hide();
                $('#payment-maxipago-cc .new-card').show();
            }
        });
    },

    deleteCard: function() {
        var urlAjaxMaxipago = 'index.php?route=extension/payment/maxipago/delete';
        //remove
        var desc = $('#select-cc-saved-card').val();

        if (desc) {
            $.ajax({
                url: urlAjaxMaxipago,
                type: "POST",
                cache: false,
                headers: {"cache-control": "no-cache"},
                data: {
                    action: 'remove-cc',
                    ident: desc
                },
                dataType: "json",
                success: function(result) {
                    if (result.success == true) {
                        $('#select-cc-saved-card option[value="' + desc + '"]').remove();
                        $('#select-cc-saved-card').val('').change();
                    }
                }
            });
        }
    },

    validateCPF: function(cpf) {
        cpf = cpf.replace(/[^\d]+/g,'');

        if(cpf == '' || cpf.length != 11)
            return false;

        var resto;
        var soma = 0;

        if (
            cpf == "00000000000"
            || cpf == "11111111111"
            || cpf == "22222222222"
            || cpf == "33333333333"
            || cpf == "44444444444"
            || cpf == "55555555555"
            || cpf == "66666666666"
            || cpf == "77777777777"
            || cpf == "88888888888"
            || cpf == "99999999999"
            || cpf == "12345678909"
        ) {
            return false;
        }

        for (i=1; i<=9; i++)
            soma = soma + parseInt(cpf.substring(i-1, i)) * (11 - i);

        resto = (soma * 10) % 11;

        if ((resto == 10) || (resto == 11))
            resto = 0;

        if (resto != parseInt(cpf.substring(9, 10)) )
            return false;

        soma = 0;
        for (i = 1; i <= 10; i++)
            soma = soma + parseInt(cpf.substring(i-1, i)) * (12 - i);

        resto = (soma * 10) % 11;

        if ((resto == 10) || (resto == 11))
            resto = 0;
        if (resto != parseInt(cpf.substring(10, 11) ) )
            return false;

        return true;
    },

    mask: function() {

        $('.cpf').mask('000.000.000-00', {autoclear: false, 'placeholder': '___.___.___-__'});
        $('.cvv').mask('0009', {autoclear: false, 'placeholder': '___'});

        var options = {
            onKeyPress: function(cc, e, field, options){
                var masks = ['0000000000000000', '000000000000000', '0000000000000099', '0000000000000999999'];
                var brand = $('input.maxipago-brand:checked').val();
                switch (brand) {
                    case 'AMEX':
                        mask = masks[1];
                        break;
                    case 'DINERS':
                        mask = masks[2];
                        break;
                    case 'HIPERCARD':
                        mask = masks[3];
                        break;
                    default:
                        mask = masks[0];
                }
                $('.credit-card').mask(mask, options);
            },
            autoclear: false
        };
        $('.credit-card').mask('0000000000000000', options);

    }

};