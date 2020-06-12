<form id="dialog-blockchain" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('Learn more about Bitcoins', 'payment_pro'); ?></h3>
            <p>
                <?php printf(__('Bitcoin official website: %s', 'payment_pro'), '<a href="http://bitcoin.org/en/">http://bitcoin.org/en/</a>'); ?>.
                <br/>
                <?php printf(__('Getting started: %s', 'payment_pro'), '<a href="https://en.bitcoin.it/wiki/Getting_started">https://en.bitcoin.it/wiki/Getting_started</a>'); ?>.
                <br/>
                <?php printf(__('F.A.Q.: %s', 'payment_pro'), '<a href="https://en.bitcoin.it/wiki/FAQ">https://en.bitcoin.it/wiki/FAQ</a>'); ?>.
                <br/>
                <?php printf(__('We use coins: %s', 'payment_pro'), '<a href="http://www.weusecoins.com/en/">http://www.weusecoins.com/en/</a>'); ?>.
                <br/>
                <?php printf(__('More info about Blockchain: %s', 'payment_pro'), '<a href="https://blockchain.info/api">https://blockchain.info/api</a>'); ?>.
                <br/>
            </p>
            <h3><?php _e('Blockchain', 'payment_pro'); ?></h3>
            <p>
                <?php _e('To use Blockchain as a method of payment you need to apply for <a href="https://api.blockchain.info/v2/apikey/request/">blockchain.info API key here</a> and a bitcoin XPUB address, there are several ways to obtain one (they are free of charge), please refer to some of the previous links on more information about how to obtain one.', 'payment_pro'); ?>.
                <br/>
                <?php _e('The services of Blockchain are free if the usage fits in their "fair use policy".', 'payment_pro'); ?>.
                <br/>
            </p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-blockchain').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $("#dialog-blockchain").dialog({
        autoOpen: false,
        modal: true,
        width: '90%',
        title: '<?php echo osc_esc_js( __('Blockchain help', 'payment_pro') ); ?>'
    });
</script>