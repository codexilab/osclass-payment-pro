<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

class BankDataTable extends DataTable
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

        $data = array(
            'start'     => $this->start,
            'limit'     => $this->limit,
            'status'  => (isset($params['status']) && $params['status']!='')?$params['status']:Params::getParam('status'),
            'source' => 'BANK'
        );
        $invoices = ModelPaymentPro::newInstance()->invoices($data);
        $this->processData($invoices);

        $this->total = ModelPaymentPro::newInstance()->invoicesTotal(array('source' => 'BANK'));
        $this->totalFiltered = ModelPaymentPro::newInstance()->invoicesTotal($data);

        return $this->getData();
    }

    private function addTableHeader()
    {

        $this->addColumn('status', __('Status', 'payment_pro'));
        $this->addColumn('code', __('Tx ID', 'payment_pro'));
        $this->addColumn('date', __('Date', 'payment_pro'));
        $this->addColumn('items', __('Items', 'payment_pro'));
        $this->addColumn('amount', __('Subtotal', 'payment_pro'));
        $this->addColumn('amount_tax', __('Taxes', 'payment_pro'));
        $this->addColumn('amount_total', __('Total', 'payment_pro'));
        $this->addColumn('user', __('User', 'payment_pro'));
        $this->addColumn('email', __('Email', 'payment_pro'));
        $this->addColumn('payment', __('Action', 'payment_pro'));
        $this->addColumn('payment_email', __('Action', 'payment_pro'));
        $this->addColumn('delete', __('Delete', 'payment_pro'));

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
                $row['code'] = $aRow['s_code'];
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
                if($aRow['i_status']!=PAYMENT_PRO_COMPLETED) {
                    $msg_confirm = __('Are you sure you want to mark this invoice as paid? Warning: This action might not be able to be undone.', 'payment_pro');
                    $msg_confirm_email = __('Are you sure you want to mark this invoice as paid and NOTIFY the user about it? Warning: This action can not be undone.', 'payment_pro');
                } else {
                    $msg_confirm = __('Are you sure you want to mark this invoice as unpaid? Warning: We will try to unpaid the products, but in some cases it will not be possible.', 'payment_pro');
                    $msg_confirm_email = __('Are you sure you want to NOTIFY the user that his invoice was paid?? Warning: This action can not be undone.', 'payment_pro');
                };


                $row['payment'] = '<a onclick="javascript:return confirm(\'' . osc_esc_html($msg_confirm) . '\');" href="' . osc_route_admin_url('payment-pro-admin-bank', array('paction' => 'pay', 'pay' => ($aRow['i_status']!=PAYMENT_PRO_COMPLETED), 'id' => $aRow['pk_i_id'])) . '">' . (($aRow['i_status']!=PAYMENT_PRO_COMPLETED)?__('mark as paid', 'payment_pro'):__('mark as unpaid', 'payment_pro')) . '</a>';

                $row['payment_email'] = '<a onclick="javascript:return confirm(\'' . osc_esc_html($msg_confirm_email) . '\');" href="' . osc_route_admin_url('payment-pro-admin-bank', array('paction' => 'payemail', 'pay' => ($aRow['i_status']!=PAYMENT_PRO_COMPLETED), 'id' => $aRow['pk_i_id'])) . '">' . (($aRow['i_status']!=PAYMENT_PRO_COMPLETED)?__('mark as paid & notify user', 'payment_pro'):__('notify user', 'payment_pro')) . '</a>';

                $row['delete'] = '<a onclick="javascript:return confirm(\'' . osc_esc_html(__('Are you sure you want to delete this invoice?', 'payment_pro')) . '\');" href="' . osc_route_admin_url('payment-pro-admin-bank', array('paction' => 'delete', 'id' => $aRow['pk_i_id'])) . '">' . __('delete', 'payment_pro') . '</a>';

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
            case PAYMENT_PRO_CREATED:
                return __('Created', 'payment_pro');
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

    public function totalRows() {
        return $this->total;
    }

    public function totalFilteredRows() {
        return $this->totalFiltered;
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

