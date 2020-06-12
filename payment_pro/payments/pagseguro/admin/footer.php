<form id="dialog-pagseguro" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('How to configure Pagseguro', 'payment_pro'); ?></h3>
            <p><?php _e('You need to create an account at <a href="https://pagseguro.uol.com.br/">Pagseguro</a>.', 'payment_pro'); ?></p>
            <p><?php _e('Access your account and go to <a href="https://pagseguro.uol.com.br/preferencias/integracoes.jhtml"> "Minha Conta" > Preferências > <b>Configurações de integração</b>"</a>.', 'payment_pro'); ?></p>
            <p><?php printf(__('There you need to generate the TOKEN you will use for the plugin and also configure the Notificação de transação as: <i><b>%s</b></i>', 'payment_pro'), osc_route_url('pagseguro-notify')); ?></p>
            <p><?php _e('Then you need to create a new application, for that, go to <a href="https://pagseguro.uol.com.br/preferencias/integracoes.jhtml"> "Minha Conta" > Aplicações > <b>Minhas aplicações</b>"</a> and click on <a href="https://pagseguro.uol.com.br/aplicacao/cadastro.jhtml"><b>Criar nova aplicação</b></a>.', 'payment_pro'); ?></p>
            <p><?php _e('Input any name for your application, but remember the <b>"ID da aplicação"</b>, this is your <b>appId</b>.', 'payment_pro'); ?></p>
            <p><?php printf(__('Configure your <b>"URL de notificação"</b> as: <i><b>%s</b></i>', 'payment_pro'), osc_route_url('pagseguro-notify')); ?></p>
            <p><?php _e('On the next page, you will receive your <b>Chave</b>, this is your <b>appKey</b>.', 'payment_pro'); ?></p>
        </div>

        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-pagseguro').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $("#dialog-pagseguro").dialog({
        autoOpen: false,
        modal: true,
        width: '90%',
        title: '<?php echo osc_esc_js( __('Bank help', 'payment_pro') ); ?>'
    });
</script>