<?php

class PagSeguroConfigWrapper
{
    public static function getConfig()
    {
        $PagSeguroConfig = array();

        $PagSeguroConfig['environment'] = osc_get_preference('pagseguro_sandbox', 'payment_pro')==1?"sandbox":"production";

        $PagSeguroConfig['credentials'] = array();
        $PagSeguroConfig['credentials']['email'] = osc_get_preference('pagseguro_email', 'payment_pro');
        $PagSeguroConfig['credentials']['token']['production'] = payment_pro_decrypt(osc_get_preference('pagseguro_token', 'payment_pro'));
        $PagSeguroConfig['credentials']['token']['sandbox'] = payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_token', 'payment_pro'));
        $PagSeguroConfig['credentials']['appId']['production'] = payment_pro_decrypt(osc_get_preference('pagseguro_appid', 'payment_pro'));
        $PagSeguroConfig['credentials']['appId']['sandbox'] = payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_appid', 'payment_pro'));
        $PagSeguroConfig['credentials']['appKey']['production'] = payment_pro_decrypt(osc_get_preference('pagseguro_appkey', 'payment_pro'));
        $PagSeguroConfig['credentials']['appKey']['sandbox'] = payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_appkey', 'payment_pro'));

        $PagSeguroConfig['application'] = array();
        $PagSeguroConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

        $PagSeguroConfig['log'] = array();
        $PagSeguroConfig['log']['active'] = false;
        // Informe o path completo (relativo ao path da lib) para o arquivo, ex.: ../PagSeguroLibrary/logs.txt
        $PagSeguroConfig['log']['fileLocation'] = "";

        return $PagSeguroConfig;
    }
}

