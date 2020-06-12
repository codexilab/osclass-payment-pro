<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    class SubscriptionsDataTable extends DataTable
    {

        private $_totalRows;
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

            $subscriptions = ModelPaymentPro::newInstance()->subscriptions(array(
                'start'     => $this->start,
                'limit'     => $this->limit,
                'status'  => (isset($params['status']) && $params['status']!='')?$params['status']:Params::getParam('status'),
                'source' => (isset($params['source']) && $params['source']!='')?$params['source']:Params::getParam('source')
            ));
            $this->processData($subscriptions);

            $this->total = ModelPaymentPro::newInstance()->subscriptionsTotal();
            $this->total_filtered = $this->total;

            return $this->getData();
        }

        private function addTableHeader()
        {

            $this->addColumn('status', __('Status', 'payment_pro'));
            $this->addColumn('code', __('Subscription ID', 'payment_pro'));
            $this->addColumn('date', __('Date', 'payment_pro'));
            $this->addColumn('items', __('Items', 'payment_pro'));
            $this->addColumn('amount', __('Price', 'payment_pro'));
            $this->addColumn('quantity', __('Quantity', 'payment_pro'));
            $this->addColumn('amount_tax', __('Taxes', 'payment_pro'));
            $this->addColumn('amount_total', __('Total', 'payment_pro'));
            $this->addColumn('source_code', __('Source TX', 'payment_pro'));
            $this->addColumn('source', __('Source', 'payment_pro'));

            $dummy = &$this;
            osc_run_hook("admin_payment_pro_subscriptions_table", $dummy);
        }

        private function processData($subscriptions)
        {
            $this->_totalRows = count($subscriptions);
            if(!empty($subscriptions)) {

                foreach($subscriptions as $codeRow) {
                    $row     = array();

                    $elements = ModelPaymentPro::newInstance()->itemsBySubscription($codeRow['s_code']);

                    foreach($elements as $aRow) {
                        $row['code'] = $aRow['s_code'];
                        $row['status'] = $aRow['i_status'];
                        $row['date'] = $aRow['dt_date'];
                        $row['items'] = $aRow['i_product_type'] . " ## " . $aRow['s_concept'];
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
                        $row['quantity'] = $aRow['i_quantity'];
                        $row['source_code'] = payment_pro_tx_link($aRow['s_source_code'], $aRow['s_source']);
                        $row['source'] = $aRow['s_source'];

                        $row = osc_apply_filter('payment_pro_subscriptions_processing_row', $row, $aRow);

                        $this->addRow($row);
                        $this->rawRows[] = $aRow;
                    }
                }

            }
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
                case PAYMENT_PRO_CREATED:
                    return __('Not paid (will be removed)', 'payment_pro');
                    break;
                case PAYMENT_PRO_CANCELED:
                    return __('Canceled', 'payment_pro');
                    break;
                default:
                    return 'ERROR';
                    break;
            }

        }

        public function totalRows() {
            return $this->_totalRows;
        }

        public function row_class($status)
        {
            $status_class = $this->get_row_status_class($status);
            return $status_class;
        }

        private function get_row_status_class($status) {
            switch($status) {
                case PAYMENT_PRO_FAILED:
                    return 'status-spam';
                    break;
                case PAYMENT_PRO_COMPLETED:
                    return 'status-active';
                    break;
                case PAYMENT_PRO_CANCELED:
                    return 'status-inactive';
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
                case PAYMENT_PRO_CREATED:
                    return 'status-spam';
                    break;
                default:
                    return 'status-spam';
                    break;
            }
        }
    }

?>