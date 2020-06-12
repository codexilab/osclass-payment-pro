<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    class CheckoutInvoicesDataTable extends DataTable
    {
        public function __construct()
        {
            osc_add_filter('datatable_payment_log_status_class', array(&$this, 'row_class'));
            osc_add_filter('datatable_payment_log_status_text', array(&$this, '_status'));
        }

        public function table($params)
        {

            $this->addTableHeader();

            $start = ((int)$params['iPage']-1) * $params['iDisplayLength'];

            $this->start = intval( $start );
            $this->limit = intval( $params['iDisplayLength'] );

            $invoices = ModelPaymentPro::newInstance()->invoices(array(
                'start'     => $this->start,
                'limit'     => $this->limit,
                'status'  => Params::getParam('status'),
                'source' => Params::getParam('source')
            ));
            $this->processData($invoices);

            $this->total = ModelPaymentPro::newInstance()->invoicesTotal();
            $this->total_filtered = $this->total;

            return $this->getData();
        }

        private function addTableHeader()
        {

            $this->addColumn('status', __('Status', 'payment_pro'));
            $this->addColumn('date', __('Date', 'payment_pro'));
            $this->addColumn('items', __('Items', 'payment_pro'));
            $this->addColumn('amount', __('Subtotal', 'payment_pro'));
            $this->addColumn('amount_tax', __('Taxes', 'payment_pro'));
            $this->addColumn('amount_total', __('Total', 'payment_pro'));
            $this->addColumn('user', __('User', 'payment_pro'));
            $this->addColumn('email', __('Email', 'payment_pro'));
            $this->addColumn('code', __('Tx ID', 'payment_pro'));
            $this->addColumn('source', __('source', 'payment_pro'));

            $dummy = &$this;
            osc_run_hook("admin_payment_pro_invoices_table", $dummy);
        }

        private function processData($invoices)
        {
            if(!empty($invoices)) {

                foreach($invoices as $aRow) {
                    $row     = array();

                    $row['status'] = $aRow['i_status'];
                    $row['date'] = $aRow['dt_date'];
                    $row['code'] = payment_pro_tx_link($aRow['s_code'], $aRow['s_source']);

                    $row['items'] = $this->_invoiceRows($aRow['pk_i_id'], $aRow['s_currency_code']);
                    if($aRow['s_currency_code']=="BTC") {
                        // FORGET FORMAT IF BTC
                        $row['amount'] = ($aRow['i_amount']/1000000) . " " . $aRow['s_currency_code'];
                        $row['amount_tax'] = ($aRow['i_amount_tax']/1000000) . " " . $aRow['s_currency_code'];
                        $row['amount_total'] = ($aRow['i_amount_total']/1000000) . " " . $aRow['s_currency_code'];
                    } else {
                        $row['amount'] = payment_pro_format_price($aRow['i_amount'], $aRow['s_currency_code']);
                        $row['amount_tax'] = payment_pro_format_price($aRow['i_amount_tax'], $aRow['s_currency_code']);
                        $row['amount_total'] = payment_pro_format_price($aRow['i_amount_total'], $aRow['s_currency_code']);
                    }
                    $row['email'] = $aRow['s_email'];
                    $row['user'] = $aRow['fk_i_user_id'];
                    $row['source'] = $aRow['s_source'];

                    $row = osc_apply_filter('payment_pro_invoices_processing_row', $row, $aRow);

                    $this->addRow($row);
                    $this->rawRows[] = $aRow;
                }

            }
        }

        private function _invoiceRows($id, $currency) {
            $items = ModelPaymentPro::newInstance()->itemsByInvoice($id);
            $rows = '';
            foreach($items as $item) {
                $rows .= '<li>' . payment_pro_format_price($item['i_amount'], $currency) . ' - ' . $item['i_product_type'] . ' - ' . $item['s_concept'] . '</li>';
            }

            return '<ul>' . $rows . '</ul>';
        }

       public function _status($status) {
            switch($status) {
                case PAYMENT_PRO_FAILED:
                    return __('Failed', 'payment_pro');
                    break;
                case PAYMENT_PRO_COMPLETED:
                    return __('Completed', 'payment_pro');
                    break;
                case PAYMENT_PRO_PENDING:
                    return __('Pending', 'payment_pro');
                    break;
                case PAYMENT_PRO_ALREADY_PAID:
                    return __('Already paid', 'payment_pro');
                    break;
                case PAYMENT_PRO_WRONG_AMOUNT_TOTAL:
                    return __('Wrong amount/total', 'payment_pro');
                    break;
                case PAYMENT_PRO_WRONG_AMOUNT_ITEM:
                    return __('Wrong amount/listing', 'payment_pro');
                    break;
                default:
                    return 'ERROR';
                    break;
            }

        }

        public function row_class($status)
        {
            return $this->get_row_status_class($status);
        }

        private function get_row_status_class($status) {
            switch($status) {
                case PAYMENT_PRO_FAILED:
                    return 'status-spam';
                    break;
                case PAYMENT_PRO_COMPLETED:
                    return 'status-active';
                    break;
                case PAYMENT_PRO_PENDING:
                    return 'status-inactive';
                    break;
                case PAYMENT_PRO_ALREADY_PAID:
                    return 'status-expired';
                    break;
                case PAYMENT_PRO_WRONG_AMOUNT_TOTAL:
                    return 'status-spam';
                    break;
                case PAYMENT_PRO_WRONG_AMOUNT_ITEM:
                    return 'status-spam';
                    break;
                default:
                    return 'status-spam';
                    break;
            }
        }
    }

?>