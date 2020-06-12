<?php

    class ModelPaymentPro extends DAO
    {

        private static $instance ;

        public static function newInstance()
        {
            if( !self::$instance instanceof self ) {
                self::$instance = new self ;
            }
            return self::$instance ;
        }

        function __construct()
        {
            parent::__construct();
        }

        public function getTable_invoice()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_invoice';
        }

        public function getTable_invoice_row()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_invoice_row';
        }

        public function getTable_pending_row()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_pending_row';
        }

        public function getTable_wallet()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_wallet';
        }

        public function getTable_premium()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_premium';
        }

        public function getTable_highlight()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_highlight';
        }

        public function getTable_publish()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_publish';
        }

        public function getTable_prices()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_prices';
        }

        public function getTable_packs()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_packs';
        }

        public function getTable_invoice_extra()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_invoice_extra';
        }

        public function getTable_mail_queue()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_mail_queue';
        }

        public function getTable_subscription()
        {
            return DB_TABLE_PREFIX.'t_payment_pro_subscription';
        }

        public function import($file)
        {
            $sql = file_get_contents($file);

            if(! $this->dao->importSQL($sql) ){
                throw new Exception( "Error importSQL::ModelPaymentPro<br>".$file ) ;
            }
        }

        public function install() {

            $confPath = PAYMENT_PRO_PATH . 'payments/';
            $dir = opendir($confPath);
            while($file = readdir($dir)) {
                if(is_dir($confPath . $file) && $file!='.' && $file!='..') {
                    if(file_exists($confPath . $file . '/load.php')) {
                        include_once $confPath . $file . '/load.php';
                    }
                }
            }
            closedir($dir);
            unset($dir);
            $this->import(PAYMENT_PRO_PATH . 'struct.sql');

            osc_set_preference('version', '340', 'payment_pro', 'INTEGER');
            osc_set_preference('default_premium_cost', '1.0', 'payment_pro', 'STRING');
            osc_set_preference('allow_premium', '0', 'payment_pro', 'BOOLEAN');
            osc_set_preference('default_top_cost', '1.0', 'payment_pro', 'STRING');
            osc_set_preference('allow_top', '0', 'payment_pro', 'BOOLEAN');
            osc_set_preference('default_publish_cost', '1.0', 'payment_pro', 'STRING');
            osc_set_preference('pay_per_post', '0', 'payment_pro', 'BOOLEAN');
            osc_set_preference('premium_days', '7', 'payment_pro', 'INTEGER');
            osc_set_preference('top_hours', '168', 'payment_pro', 'INTEGER');
            osc_set_preference('currency', 'USD', 'payment_pro', 'STRING');
            osc_set_preference('default_highlight_cost', '1.0', 'payment_pro', 'STRING');
            osc_set_preference('allow_highlight', '0', 'payment_pro', 'BOOLEAN');
            osc_set_preference('allow_wallet', '0', 'payment_pro', 'BOOLEAN');
            osc_set_preference('highlight_days', '7', 'payment_pro', 'INTEGER');
            osc_set_preference('highlight_color', 'fff000', 'payment_pro', 'STRING');
            osc_set_preference('renew_days', -1, 'payment_pro', 'INTEGER');

            osc_set_preference('last_purge', time(), 'payment_pro', 'INTEGER');

            osc_run_hook('payment_pro_install');

            $limit = 20000;
            $this->dao->select('COUNT(*) as total') ;
            $this->dao->from(DB_TABLE_PREFIX.'t_item') ;
            $result = $this->dao->get();
            $total = $result->row();
            $total = (int)$total['total'];
            $steps = ceil($total/$limit);
            for($step=0;$step<$steps;$step++) {

                $this->dao->select('pk_i_id, b_enabled');
                $this->dao->from(DB_TABLE_PREFIX . 't_item');
                $this->dao->orderBy('pk_i_id', 'ASC');
                $this->dao->limit($limit, $limit*$step);
                $result = $this->dao->get();
                $query = 'INSERT INTO ' . $this->getTable_publish() . ' (fk_i_item_id, b_paid, b_enabled, dt_date) VALUES ';
                if ($result) {
                    $items = $result->result();
                    $date = date("Y-m-d H:i:s");
                    $values = array();
                    $k = 0;
                    foreach ($items as $key => $item) {
                        $values[] = '(' . $item['pk_i_id'] . ', 1, ' . $item['b_enabled'] . ', "' . $date . '")';
                        $k++;
                        if ($k >= 500) {
                            $this->dao->query($query . implode(",", $values) . ";");
                            $k = 0;
                            $values = array();
                        }
                        unset($items[$key]);
                    }
                    $this->dao->query($query . implode(",", $values) . ";");
                }

            }

            $description[osc_language()]['s_title'] = '{WEB_TITLE} - Publish option for your ad: {ITEM_TITLE}';
            $description[osc_language()]['s_text'] = '<p>Hi {CONTACT_NAME}!</p><p>We just published your item ({ITEM_TITLE}) on {WEB_TITLE}.</p><p>{START_PUBLISH_FEE}</p><p>In order to make your ad available to anyone on {WEB_TITLE}, you should complete the process and pay the publish fee. You could do that on the following link: {PUBLISH_LINK}</p><p>{END_PUBLISH_FEE}</p><p>{START_PREMIUM_FEE}</p><p>You could make your ad premium and make it to appear on top result of the searches made on {WEB_TITLE}. You could do that on the following link: {PREMIUM_LINK}</p><p>{END_PREMIUM_FEE}</p><p>This is an automatic email, if you already did that, please ignore this email.</p><p>Thanks</p>';
            $res = Page::newInstance()->insert(
                array('s_internal_name' => 'payment_pro_email_payment', 'b_indelible' => '1'),
                $description
            );

            $description[osc_language()]['s_title'] = '{WEB_TITLE} - Payment processed: {PAYMENT_CODE}';
            $description[osc_language()]['s_text'] = '<p>Hi {CONTACT_NAME}!</p><p>We just processed your payment with code {PAYMENT_CODE}) on {WEB_TITLE}. The following products have been processed: </p><p>{PRODUCT_LIST}</p><p>This is an automatic email, if you already did that, please ignore this email.</p><p>Thanks</p>';
            $res = Page::newInstance()->insert(
                array('s_internal_name' => 'payment_pro_email_bank_notify', 'b_indelible' => '1'),
                $description
            );

        }

        public function versionUpdate() {
            $version = osc_get_preference('version', 'payment_pro');
            if( $version < 201 ) {
                $this->dao->query(sprintf("CREATE TABLE  %st_payment_pro_subscription (pk_i_id INT NOT NULL AUTO_INCREMENT ,s_code char(40) NOT NULL,dt_date DATETIME NOT NULL,s_concept VARCHAR( 200 ) NOT NULL ,i_amount BIGINT(20) NULL,i_quantity INT NOT NULL DEFAULT 1,i_product_type VARCHAR( 30 ) NOT NULL,s_source_code char(40) NOT NULL,s_source VARCHAR( 10 ) NOT NULL,i_status INT(11) NOT NULL, PRIMARY KEY(pk_i_id),INDEX idx_dt_date (dt_date),INDEX idx_s_code (s_code) ) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX));
                osc_set_preference('version', 201, 'payment_pro', 'INTEGER');
            }
            if( $version < 220 ) {
                // prices
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_prices ADD COLUMN i_tax SMALLINT NOT NULL DEFAULT 0 ;", DB_TABLE_PREFIX));
                // invoice
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice ADD COLUMN i_amount_tax BIGINT(20) NOT NULL DEFAULT 0 ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice ADD COLUMN i_amount_total BIGINT(20) NULL ;", DB_TABLE_PREFIX));
                // invoice row
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice_row ADD COLUMN i_tax SMALLINT NOT NULL DEFAULT 0;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice_row ADD COLUMN i_amount_tax BIGINT(20) NOT NULL DEFAULT 0 ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice_row ADD COLUMN i_amount_total BIGINT(20) NULL ;", DB_TABLE_PREFIX));
                // pending row
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_pending_row ADD COLUMN i_tax SMALLINT NOT NULL DEFAULT 0;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_pending_row ADD COLUMN i_amount_tax BIGINT(20) NOT NULL DEFAULT 0 ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_pending_row ADD COLUMN i_amount_total BIGINT(20) NULL ;", DB_TABLE_PREFIX));
                // subscription
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN s_currency_code VARCHAR(3) NULL;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN i_tax SMALLINT NOT NULL DEFAULT 0;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN i_amount_tax BIGINT(20) NOT NULL DEFAULT 0 ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN i_amount_total BIGINT(20) NULL ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN s_extra TEXT NULL ;", DB_TABLE_PREFIX));

                $this->dao->query(sprintf("UPDATE %st_payment_pro_invoice SET i_amount_total = i_amount ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("UPDATE %st_payment_pro_invoice_row SET i_amount_total = i_amount ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("UPDATE %st_payment_pro_pending_row SET i_amount_total = i_amount ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("UPDATE %st_payment_pro_subscription SET i_amount_total = i_amount ;", DB_TABLE_PREFIX));

                osc_set_preference('version', 220, 'payment_pro', 'INTEGER');
            }
            if( $version < 221 ) {
                // subscription
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN fk_i_invoice_id INT(11) NULL ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN fk_i_invoice_row_id INT(11) NULL ;", DB_TABLE_PREFIX));

                osc_set_preference('version', 221, 'payment_pro', 'INTEGER');
            }

            if( $version < 300 ) {
                osc_set_preference('default_top_cost', '1.0', 'payment_pro', 'STRING');
                osc_set_preference('allow_top', '0', 'payment_pro', 'BOOLEAN');
                osc_set_preference('default_highlight_cost', '1.0', 'payment_pro', 'STRING');
                osc_set_preference('allow_wallet', '0', 'payment_pro', 'BOOLEAN');
                osc_set_preference('allow_highlight', '0', 'payment_pro', 'BOOLEAN');
                osc_set_preference('highlight_days', '7', 'payment_pro', 'INTEGER');
                osc_set_preference('highlight_color', 'fff000', 'payment_pro', 'STRING');

                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_prices ADD COLUMN i_top_cost BIGINT(20) NULL ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_prices ADD COLUMN i_highlight_cost BIGINT(20) NULL ;", DB_TABLE_PREFIX));

                $this->dao->query(sprintf("CREATE TABLE %st_payment_pro_packs (pk_i_id INT NOT NULL AUTO_INCREMENT , i_amount_cost INT NULL , i_amount INT NULL , s_name VARCHAR( 200 ) NOT NULL , PRIMARY KEY (pk_i_id)) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX));

                $this->dao->query(sprintf("CREATE TABLE %st_payment_pro_highlight (fk_i_item_id INT UNSIGNED NOT NULL, dt_date DATETIME NOT NULL , dt_expiration_date DATETIME NOT NULL , fk_i_invoice_id INT NOT NULL, PRIMARY KEY (fk_i_item_id), FOREIGN KEY (fk_i_invoice_id) REFERENCES %st_payment_pro_invoice (pk_i_id)) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX, DB_TABLE_PREFIX));

                osc_set_preference('version', 300, 'payment_pro', 'INTEGER');
            }

            if( $version < 305 ) {
                osc_set_preference('show_taxes', 0, 'payment_pro', 'INTEGER');
                osc_set_preference('version', 305, 'payment_pro', 'INTEGER');
            }

            if( $version < 306 ) {
                osc_set_preference('default_tax', '', 'payment_pro', 'INTEGER');
                osc_set_preference('version', 306, 'payment_pro', 'INTEGER');
            }

            if($version < 307 ) {
                $this->dao->query(sprintf("CREATE TABLE  %st_payment_pro_invoice_extra (fk_s_pending_code char(40) NOT NULL,fk_i_invoice_id INT(11) NULL,  s_source VARCHAR( 10 ) NULL,
s_extra VARCHAR( 250 ) NULL ,PRIMARY KEY(fk_s_pending_code),INDEX idx_fk_s_pending_code (fk_s_pending_code)) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX));
                osc_set_preference('version', 307, 'payment_pro', 'INTEGER');
            }

            if($version < 310 ) {
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_subscription ADD COLUMN i_count INT(11) NULL DEFAULT 1;", DB_TABLE_PREFIX));
                osc_set_preference('version', 310, 'payment_pro', 'INTEGER');
            }

            if($version < 311 ) {
                osc_set_preference('bank_msg', __('Please make a wire transfer to the account: {BANK_ACCOUNT} with the concept "{CODE}" of a total of {AMOUNT}. We are unable to process your payment if the concept does not containg the code.', 'payment_pro'), 'payment_pro', 'STRING');
                osc_set_preference('version', 311, 'payment_pro', 'INTEGER');
            }

            if($version < 318 ) {

                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_packs CHANGE COLUMN `i_amount_cost` `i_amount_cost` BIGINT(20) NULL DEFAULT NULL , CHANGE COLUMN `i_amount` `i_amount` BIGINT(20) NULL DEFAULT NULL ;", DB_TABLE_PREFIX));
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_prices CHANGE COLUMN `i_publish_cost` `i_publish_cost` BIGINT(20) NULL DEFAULT NULL , CHANGE COLUMN `i_premium_cost` `i_premium_cost` BIGINT(20) NULL DEFAULT NULL , CHANGE COLUMN `i_top_cost` `i_top_cost` BIGINT(20) NULL DEFAULT NULL , CHANGE COLUMN `i_highlight_cost` `i_highlight_cost` BIGINT(20) NULL DEFAULT NULL , CHANGE COLUMN `i_image_cost` `i_image_cost` BIGINT(20) NULL DEFAULT NULL ;", DB_TABLE_PREFIX));


                osc_set_preference('version', 318, 'payment_pro', 'INTEGER');
            }

            if($version < 320 ) {
                $mp = ModelPaymentPro::newInstance();
                $prices = $mp->getCategoriesPrices();
                foreach($prices as $p) {
                    $mp->insertPrice(
                        $p['fk_i_category_id'],
                        (!isset($p['i_publish_cost']) || $p['i_publish_cost']=='')?osc_get_preference('default_publish_cost', 'payment_pro'):$p['i_publish_cost'],
                        (!isset($p['i_premium_cost']) || $p['i_premium_cost']=='')?osc_get_preference('default_premium_cost', 'payment_pro'):$p['i_premium_cost'],
                        (!isset($p['i_top_cost']) || $p['i_top_cost']=='')?osc_get_preference('default_top_cost', 'payment_pro'):$p['i_top_cost'],
                        (!isset($p['i_highlight_cost']) || $p['i_highlight_cost']=='')?osc_get_preference('default_highlight_cost', 'payment_pro'):$p['i_highlight_cost'],
                        (!isset($p['i_renew_cost']) || $p['i_renew_cost']=='')?osc_get_preference('default_renew_cost', 'payment_pro'):$p['i_renew_cost']

                    );
                }
                osc_set_preference('version', 320, 'payment_pro', 'INTEGER');
            }

            if($version < 330 ) {
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_prices ADD COLUMN i_renew_cost BIGINT(20) NULL ;", DB_TABLE_PREFIX));

                $this->dao->query(sprintf("UPDATE %st_payment_pro_prices SET i_renew_cost = %d;", DB_TABLE_PREFIX, osc_get_preference('default_publish_cost', 'payment_pro')*1000000));
                osc_set_preference('default_renew_cost', 1.0, 'payment_pro');

                $description[osc_language()]['s_title'] = '{WEB_TITLE} - Payment processed: {PAYMENT_CODE}';
                $description[osc_language()]['s_text'] = '<p>Hi {CONTACT_NAME}!</p><p>We just processed your payment with code {PAYMENT_CODE}) on {WEB_TITLE}. The following products have been processed: </p><p>{PRODUCT_LIST}</p><p>This is an automatic email, if you already did that, please ignore this email.</p><p>Thanks</p>';
                $res = Page::newInstance()->insert(
                    array('s_internal_name' => 'payment_pro_email_bank_notify', 'b_indelible' => '1'),
                    $description
                );

                osc_set_preference('version', 330, 'payment_pro', 'INTEGER');
            }

            if($version < 332 ) {
                osc_set_preference('version', 332, 'payment_pro', 'INTEGER');
                osc_set_preference('top_hours', 168, 'payment_pro', 'INTEGER');
            }

            if($version < 338 ) {
                osc_set_preference('version', 338, 'payment_pro', 'INTEGER');
                osc_set_preference('renew_days', -1, 'payment_pro', 'INTEGER');
                $this->dao->query(sprintf("ALTER TABLE %st_payment_pro_invoice_extra MODIFY s_extra TEXT NULL;", DB_TABLE_PREFIX));
            }

            if($version < 340 ) {
                osc_set_preference('version', 340, 'payment_pro', 'INTEGER');
            }

            osc_reset_preferences();
        }

        public function premiumOff($id) {
            return $this->dao->delete($this->getTable_premium(), array('fk_i_item_id' => $id));
        }

        public function deleteItem($id) {
            $this->premiumOff($id);
            $this->dao->delete($this->getTable_highlight(), array('fk_i_item_id' => $id));
            $this->dao->delete($this->getTable_mail_queue(), array('fk_i_item_id' => $id));
            return $this->dao->delete($this->getTable_publish(), array('fk_i_item_id' => $id));
        }

        public function deletePrices($id) {
            return $this->dao->delete($this->getTable_prices(), array('fk_i_category_id' => $id));
        }

        public function deletePack($id) {
            return $this->dao->delete($this->getTable_packs(), array('pk_i_id' => $id));
        }

        public function disableItem($id) {
            $args = array('b_enabled' => 0, 'dt_date' => date("Y-m-d H:i:s"));
            return $this->dao->update($this->getTable_publish(), $args, array('fk_i_item_id' => $id));
        }

        public function enableItem($id) {
            $args = array('b_enabled' => 1, 'dt_date' => date("Y-m-d H:i:s"));
            if(osc_get_preference('pay_per_post','payment_pro')!=1) {
                $args['b_paid'] = 1;
            }
            return $this->dao->update($this->getTable_publish(), $args, array('fk_i_item_id' => $id));
        }

        public function uninstall()
        {

            $confPath = PAYMENT_PRO_PATH . 'payments/';
            $dir = opendir($confPath);
            while($file = readdir($dir)) {
                if(is_dir($confPath . $file) && $file!='.' && $file!='..') {
                    if(file_exists($confPath . $file . '/load.php')) {
                        include_once $confPath . $file . '/load.php';
                    }
                }
            }
            closedir($dir);

            osc_run_hook('payment_pro_pre_uninstall');


            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_premium()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_highlight()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_publish()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_wallet()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_prices()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_packs()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_invoice_row()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_invoice_extra()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_invoice()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_pending_row()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_mail_queue()) );
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_subscription()) );

            $page = Page::newInstance()->findByInternalName('payment_pro_email_payment');
            if(isset($page['pk_i_id'])) {
                Page::newInstance()->deleteByPrimaryKey($page['pk_i_id']);
            }
            $page = Page::newInstance()->findByInternalName('payment_pro_email_bank_notify');
            if(isset($page['pk_i_id'])) {
                Page::newInstance()->deleteByPrimaryKey($page['pk_i_id']);
            }

            Preference::newInstance()->delete(array('s_section' => 'payment_pro'));

            osc_run_hook('payment_pro_post_uninstall');

        }

        public function getPaymentByCode($code, $source, $status = null) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_invoice());
            $this->dao->where('s_code', $code);
            $this->dao->where('s_source', $source);
            if($status!=null) {
                $this->dao->where('i_status', $status);
            }
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return false;
        }

        public function getPayment($invoiceId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_invoice());
            $this->dao->where('pk_i_id', $invoiceId);
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return false;
        }

        public function getPublishData($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_publish());
            $this->dao->where('fk_i_item_id', $itemId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['fk_i_item_id'])) {
                    return $row;
                }
            }
            return false;
        }

        public function getPremiumData($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_premium());
            $this->dao->where('fk_i_item_id', $itemId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['fk_i_item_id'])) {
                    return $row;
                }
            }
            return false;
        }

        public function getHighlightData($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_highlight());
            $this->dao->where('fk_i_item_id', $itemId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['fk_i_item_id'])) {
                    return $row;
                }
            }
            return array();
        }

        public function isHighlighted($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_highlight());
            $this->dao->where('fk_i_item_id', $itemId);
            $this->dao->where('dt_expiration_date >= \'' . date('Y-m-d H:i:s') . '\'');
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['fk_i_item_id'])) {
                    return true;
                }
            }
            return false;
        }

        public function createItem($itemId, $paid = 0, $date = NULL, $invoiceId = NULL, $enabled = 1) {
            if($date==NULL) { $date = date("Y-m-d H:i:s"); };
            $this->dao->insert($this->getTable_publish(), array('fk_i_item_id' => $itemId, 'dt_date' => $date, 'b_paid' => $paid, 'b_enabled' => $enabled, 'fk_i_invoice_id' => $invoiceId));
        }

        public function getPublishPrice($categoryId) {
            if(osc_get_preference('pay_per_post', 'payment_pro')==0) { return 0; }
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $this->dao->where('fk_i_category_id', $categoryId);
            $result = $this->dao->get();
            if($result) {
                $cat = $result->row();
                if(isset($cat['i_publish_cost'])) {
                    return array('price' => $cat["i_publish_cost"]/1000000, 'tax' => $cat['i_tax']);
                }
            }
            return array('price' => osc_get_preference('default_publish_cost', 'payment_pro'), 'tax' => 0);
        }

        public function getPremiumPrice($categoryId) {
            if(osc_get_preference('allow_premium', 'payment_pro')==0) { return 0; }
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $this->dao->where('fk_i_category_id', $categoryId);
            $result = $this->dao->get();
            if($result) {
                $cat = $result->row();
                if(isset($cat['i_premium_cost'])) {
                    return array('price' => $cat["i_premium_cost"]/1000000, 'tax' => $cat['i_tax']);
                }
            }
            return array('price' => osc_get_preference('default_premium_cost', 'payment_pro'), 'tax' => 0);
        }

        public function getTopPrice($categoryId) {
            if(osc_get_preference('allow_top', 'payment_pro')==0) { return 0; }
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $this->dao->where('fk_i_category_id', $categoryId);
            $result = $this->dao->get();
            if($result) {
                $cat = $result->row();
                if(isset($cat['i_top_cost'])) {
                    return array('price' => $cat["i_top_cost"]/1000000, 'tax' => $cat['i_tax']);
                }
            }
            return array('price' => osc_get_preference('default_top_cost', 'payment_pro'), 'tax' => 0);
        }

        public function getHighlightPrice($categoryId) {
            if(osc_get_preference('allow_highlight', 'payment_pro')==0) { return 0; }
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $this->dao->where('fk_i_category_id', $categoryId);
            $result = $this->dao->get();
            if($result) {
                $cat = $result->row();
                if(isset($cat['i_highlight_cost'])) {
                    return array('price' => $cat["i_highlight_cost"]/1000000, 'tax' => $cat['i_tax']);
                }
            }
            return array('price' => osc_get_preference('default_highlight_cost', 'payment_pro'), 'tax' => 0);
        }

        public function getRenewPrice($categoryId) {
            if(osc_get_preference('allow_renew', 'payment_pro')==0) { return 0; }
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $this->dao->where('fk_i_category_id', $categoryId);
            $result = $this->dao->get();
            if($result) {
                $cat = $result->row();
                if(isset($cat['i_renew_cost'])) {
                    return array('price' => $cat["i_renew_cost"]/1000000, 'tax' => $cat['i_tax']);
                }
            }
            return array('price' => osc_get_preference('default_renew_cost', 'payment_pro'), 'tax' => 0);
        }

        public function getWallet($userId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_wallet());
            $this->dao->where('fk_i_user_id', $userId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                $row['formatted_amount'] = (isset($row['i_amount']) ? $row['i_amount'] : 0) / 1000000;
                return $row;
            }
            return false;
        }

        public function getCategoriesPrices() {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_prices());
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function getPacks() {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_packs());
            $this->dao->orderBy('i_amount_cost', 'ASC');
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function publishFeeIsPaid($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_publish());
            $this->dao->where('fk_i_item_id', $itemId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['b_paid']) && $row['b_paid']==1) {
                    return true;
                }
            }
            return false;
        }

        public function premiumFeeIsPaid($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_premium());
            $this->dao->where('fk_i_item_id', $itemId);
            $this->dao->where("dt_expiration_date >= '" . date('Y-m-d H:i:s') . "'");
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if (isset($row['dt_date'])) {
                    return true;
                }
            }
            return false;
        }

        public function highlightFeeIsPaid($itemId) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_highlight());
            $this->dao->where('fk_i_item_id', $itemId);
            $this->dao->where("dt_expiration_date >= '" . date('Y-m-d H:i:s') . "'");
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if (isset($row['dt_date'])) {
                    return true;
                }
            }
            return false;
        }

        public function isEnabled($itemId) {
            $this->dao->select('b_enabled') ;
            $this->dao->from($this->getTable_publish());
            $this->dao->where('fk_i_item_id', $itemId);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if ($row) {
                    if (isset($row['b_enabled']) && $row['b_enabled'] == 1) {
                        return true;
                    }
                }
            }
            return false;
        }

        public function purgeExpired() {
            // PURGE PREMIUM
            $this->dao->select("fk_i_item_id");
            $this->dao->from($this->getTable_premium());
            $time = time();
            $this->dao->where("dt_expiration_date >= '" . date('Y-m-d H:i:s', (int)osc_get_preference('last_purge', 'payment_pro')-3600) . "'");
            $this->dao->where("dt_expiration_date < '" . date('Y-m-d H:i:s') . "'");
            $result = $this->dao->get();
            if($result) {
                $items = $result->result();
                $mItem = new ItemActions(false);
                foreach($items as $item) {
                    $mItem->premium($item['fk_i_item_id'], false);
                    $this->premiumOff($item['fk_i_item_id']);
                };
            };

            osc_set_preference('last_purge', $time, 'payment_pro');
        }

        public function purgePending() {
            return $this->dao->delete($this->getTable_pending_row(), "dt_date < '" . date('Y-m-d H:i:s', time()-(7*24*3600)) . "'");
        }

        public function purgeSubscriptions() {
            return $this->dao->delete($this->getTable_subscription(), "dt_date < '" . date('Y-m-d H:i:s', time()-(60*24*3600)) . "' AND i_status = " . PAYMENT_PRO_CREATED);
        }

        public function saveInvoice($code, $subtotal, $tax, $total, $status, $currency, $email, $user, $source, $rows) {

            $invoice = array(
                'dt_date' => date("Y-m-d H:i:s"),
                's_code' => $code,
                'i_amount' => $subtotal*1000000,
                'i_amount_tax' => $tax*1000000,
                'i_amount_total' => $total*1000000,
                's_currency_code' => $currency,
                's_email' => $email,
                'fk_i_user_id' => $user,
                's_source' => $source,
                'i_status' => $status
            );
            $this->dao->insert($this->getTable_invoice(), $invoice);
            $invoice_id = $this->dao->insertedId();
            foreach($rows as $row) {
                $row = payment_pro_complete_item($row);
                $this->dao->insert($this->getTable_invoice_row(), array(
                    'fk_i_invoice_id' => $invoice_id,
                    's_concept' => $row['description'],
                    'i_amount' => $row['amount']*1000000,
                    'i_tax' => $row['tax']*100,
                    'i_amount_tax' => $row['amount_tax']*1000000,
                    'i_amount_total' => $row['amount_total']*1000000,
                    'i_quantity' => $row['quantity'],
                    'fk_i_item_id' => @$row['item_id'],
                    'i_product_type' => $row['id']
                ));
                $row_id = $this->dao->insertedId();
                if(isset($row['update_item_sub_id']) && $row['update_item_sub_id']!='' && is_numeric($row['update_item_sub_id'])) {
                    $this->updateSubscriptionItemData($row['update_item_sub_id'], array('fk_i_invoice_id' => $invoice_id, 'fk_i_invoice_row_id' => $row_id));
                }
            }
            $invoice['rows'] = $rows;
            osc_run_hook('payment_pro_invoice_saved', $invoice, $invoice_id);
            return $invoice_id;
        }

        public function pendingInvoice($rows, $length = null) {
            $code = sha1(osc_genRandomPassword(40));
            if($length!=null) {
                $code = substr($code, 0, $length);
            }
            while(true) {
                $this->dao->select("s_code");
                $this->dao->from($this->getTable_pending_row());
                $this->dao->where("s_code", $code);
                $this->dao->limit(1);
                $result = $this->dao->get();
                if($result) {
                    if($result->numRows()>0) {
                        $code = sha1(osc_genRandomPassword(40));
                        if($length!=null) {
                            $code = substr($code, 0, $length);
                        }
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
            $date = date('Y-m-d H:i:s');
            foreach($rows as $row) {
                $row = payment_pro_complete_item($row);
                $this->dao->insert($this->getTable_pending_row(), array(
                    's_code' => $code,
                    'dt_date' => $date,
                    's_concept' => $row['description'],
                    'i_amount' => $row['amount']*1000000,
                    'i_tax' => $row['tax']*100,
                    'i_amount_tax' => $row['amount_tax']*1000000,
                    'i_amount_total' => $row['amount_total']*1000000,
                    'i_quantity' => $row['quantity'],
                    'fk_i_item_id' => @$row['item_id'],
                    'i_product_type' => $row['id']
                ));
            }
            return $code;
        }

        public function getPending($code) {
            $this->dao->select("s_concept as description, i_amount as amount, i_tax as tax, i_amount_tax as amount_tax, i_amount_total as amount_total, i_quantity as quantity, i_product_type as id");
            $this->dao->from($this->getTable_pending_row());
            $this->dao->where("s_code", $code);
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function deletePending($code) {
            return $this->dao->delete($this->getTable_pending_row(), array('s_code' => $code));
        }

        public function createSubscription($rows, $source = "NOSOURCE", $source_code = "NOCODE") {
            $code = sha1(osc_genRandomPassword(40));
            while(true) {
                $this->dao->select("s_code");
                $this->dao->from($this->getTable_subscription());
                $this->dao->where("s_code", $code);
                $this->dao->limit(1);
                $result = $this->dao->get();
                if($result) {
                    if($result->numRows()>0) {
                        $code = sha1(osc_genRandomPassword(40));
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
            $date = date('Y-m-d H:i:s');
            foreach($rows as $row) {
                $row = payment_pro_complete_item($row);
                $this->dao->insert($this->getTable_subscription(), array(
                    's_code' => $code,
                    'dt_date' => $date,
                    's_concept' => $row['description'],
                    'i_amount' => $row['amount']*1000000,
                    'i_tax' => $row['tax']*100,
                    'i_amount_total' => $row['amount_total']*1000000,
                    'i_amount_tax' => $row['amount_tax']*1000000,
                    'i_quantity' => $row['quantity'],
                    'i_product_type' => $row['id'],
                    's_source_code' => $source_code,
                    's_source' => $source,
                    's_currency_code' => (isset($row['currency']) && $row['currency']!='')?$row['currency']:osc_get_preference('currency', 'payment_pro'),
                    'i_status' => (isset($row['status']) && $row['status']!='')?$row['status']:PAYMENT_PRO_CREATED,
                    's_extra' => json_encode(@$row['extra'])
                ));
            }
            osc_run_hook('payment_pro_create_sub', $code, $rows, $source, $source_code);
            return $code;
        }

        public function subscription($code, $status = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('s_code', $code);
            if($status!=null) {
                $this->dao->where('i_status', $status);
            }
            $this->dao->orderBy('pk_i_id', 'ASC');
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function subscriptionItem($code, $id, $status = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('s_code', $code);
            $this->dao->where('i_product_type', $id);
            if($status!=null) {
                $this->dao->where('i_status', $status);
            }
            $this->dao->orderBy('pk_i_id', 'ASC');
            $this->dao->limit(1);
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return array();
        }

        public function subscriptionBySourceCode($code, $status = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('s_source_code', $code);
            if($status!=null) {
                $this->dao->where('i_status', $status);
            }
            $this->dao->orderBy('pk_i_id', 'ASC');
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function subscriptionCount($code) {
            $this->dao->select('i_count');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('s_code', $code);
            $this->dao->orderBy('pk_i_id', 'DESC');
            $this->dao->limit(1);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['i_count'])) {
                    return $row['i_count'];
                }
            }
            return 0;
        }

        public function subscriptionByInvoice($invoice_id) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('fk_i_invoice_id', $invoice_id);
            $this->dao->limit(1);
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return array();
        }

        public function updateSubscriptionStatus($id, $status) {
            return $this->dao->update($this->getTable_subscription(), array('i_status' => $status, 'dt_date' => date('Y-m-d H:i:s')), array('pk_i_id' => $id));
        }

        public function updateSubscriptionStatusBySourceCode($code, $status) {
            return $this->dao->update($this->getTable_subscription(), array('i_status' => $status, 'dt_date' => date('Y-m-d H:i:s')), array('s_source_code' => $code));
        }

        public function updateSubscriptionSourceCode($code, $source_code) {
            return $this->dao->update($this->getTable_subscription(), array('s_source_code' => $source_code, 'dt_date' => date('Y-m-d H:i:s')), array('s_code' => $code));
        }

        public function updateSubscriptionItemData($id, $values) {
            $values['dt_date'] = date('Y-m-d H:i:s');
            return $this->dao->update($this->getTable_subscription(), $values, array('pk_i_id' => $id));
        }

        public function updatePayment($id, $values) {
            $values['dt_date'] = date('Y-m-d H:i:s');
            return $this->dao->update($this->getTable_invoice(), $values, array('pk_i_id' => $id));
        }

        public function updateSubscriptionItem($code, $product_type, $status, $item, $source_code = "NOCODE", $source = "NOSOURCE") {
            $values = array('i_status' => $status, 'dt_date' => date('Y-m-d H:i:s'));
            if($source_code!="NOCODE") {
                $values['s_source_code'] = $source_code;
            }
            if($source!="NOSOURCE") {
                $values['s_source'] = $source;
            }

            $this->dao->select('pk_i_id');
            $this->dao->from($this->getTable_subscription());
            $this->dao->where('s_code', $code);
            $this->dao->where('i_product_type', $product_type);
            $this->dao->where('(i_status = ' . PAYMENT_PRO_CREATED . ' OR i_status = ' . PAYMENT_PRO_PENDING . ')');
            $this->dao->orderBy('pk_i_id', 'ASC');
            $this->dao->limit(1);
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['pk_i_id'])) {
                    $this->dao->update($this->getTable_subscription(), $values, array('pk_i_id' => $row['pk_i_id']));
                    return $row['pk_i_id'];
                }
            }

            if($item!=null) {
                $item = payment_pro_complete_item($item);
                if(!isset($item['sub_count'])) {
                    $item['sub_count'] = 1;
                }
                $res = $this->dao->insert($this->getTable_subscription(), array(
                    's_code' => $code,
                    'dt_date' => date('Y-m-d H:i:s'),
                    's_concept' => $item['description'],
                    'i_amount' => $item['amount']*1000000,
                    'i_tax' => $item['tax']*100,
                    'i_amount_tax' => $item['amount_tax']*1000000,
                    'i_amount_total' => $item['amount_total']*1000000,
                    'i_quantity' => $item['quantity'],
                    'i_product_type' => $product_type,
                    's_source_code' => $source_code,
                    's_source' => $source,
                    'i_status' => $status,
                    's_currency_code' => $item['currency'],
                    'i_count' => $item['sub_count'],
                    's_extra' => json_encode(@$item['extra'])
                ));
                if($res===true) {
                    return $this->dao->insertedId();
                }
            }

            return 0;
        }


        public function invoices($params) {
            $start    = (isset($params['start']) && $params['start']!='' )     ? $params['start']: 0;
            $limit    = (isset($params['limit']) && $params['limit']!='' )      ? $params['limit']: 10;
            $status  = (isset($params['status']) && $params['status']!='')  ? $params['status'] : '';
            $source = (isset($params['source']) && $params['source']!='') ? $params['source'] : '';
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_invoice());
            $this->dao->orderBy('dt_date', 'DESC');
            if($source!='') {
                $this->dao->where('s_source', $source);
            }
            if($status!='') {
                $this->dao->where('i_status', $status);
            }
            $this->dao->limit($limit, $start);
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function invoiceById($id) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_invoice());
            $this->dao->where('pk_i_id', $id);
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return array();
        }

        public function invoicesTotal($params = null) {
            $status  = (isset($params['status']) && $params['status']!='')  ? $params['status'] : '';
            $source = (isset($params['source']) && $params['source']!='') ? $params['source'] : '';
            $this->dao->select('COUNT(*) as total') ;
            $this->dao->from($this->getTable_invoice());
            if($source!='') {
                $this->dao->where('s_source', $source);
            }
            if($status!='') {
                $this->dao->where('i_status', $status);
            }
            $result = $this->dao->get();
            if($result) {
                $row = $result->row();
                if(isset($row['total'])) {
                    return $row['total'];
                }
            }
            return 0;
        }

        public function invoiceDelete($id) {
            $this->dao->delete($this->getTable_invoice_row(), array("fk_i_invoice_id" => $id));
            return $this->dao->delete($this->getTable_invoice(), array("pk_i_id" => $id));
        }

        public function itemsByInvoice($id) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_invoice_row());
            $this->dao->where('fk_i_invoice_id', $id);
            $this->dao->orderBy('pk_i_id', 'ASC');
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function subscriptions($params) {
            $start    = (isset($params['start']) && $params['start']!='' )     ? $params['start']: 0;
            $limit    = (isset($params['limit']) && $params['limit']!='' )      ? $params['limit']: 10;
            $status  = (isset($params['status']) && $params['status']!='')  ? $params['status'] : '';
            $source = (isset($params['source']) && $params['source']!='') ? $params['source'] : '';
            $this->dao->select('s_code, MAX(dt_date) as max_date') ;
            $this->dao->from($this->getTable_subscription());
            $this->dao->orderBy('max_date', 'DESC');
            if($source!='') {
                $this->dao->where('s_source', $source);
            }
            if($status!='') {
                $this->dao->where('i_status', $status);
            }
            $this->dao->groupBy('s_code');
            $this->dao->limit($start, $limit);
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }

        public function subscriptionsTotal() {
            $this->dao->select('COUNT(DISTINCT s_code) as total') ;
            $this->dao->from($this->getTable_subscription());
            $this->dao->groupBy('s_code');
            $result = $this->dao->get();
            if($result) {
                return $result->numRows();
                /*$row = $result->row();
                if(isset($row['total'])) {
                    return $row['total'];
                }*/
            }
            return 0;
        }

        public function itemsBySubscription($code) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_subscription());
            $this->dao->orderBy('dt_date', 'ASC');
            $this->dao->where('s_code', $code);
            $result = $this->dao->get();
            if($result) {
                return $result->result();
            }
            return array();
        }


        public function insertPrice($category, $publish_fee, $premium_fee, $top_fee, $highlight_fee, $renew_fee, $tax = 0) {
            return $this->dao->replace(
                $this->getTable_prices(),
                array(
                    'fk_i_category_id' => $category,
                    'i_publish_cost' => $publish_fee*1000000,
                    'i_premium_cost' => $premium_fee*1000000,
                    'i_top_cost' => $top_fee*1000000,
                    'i_highlight_cost' => $highlight_fee*1000000,
                    'i_renew_cost' => $renew_fee*1000000,
                    'i_tax' => $tax
                ));
        }

        public function insertPack($amount_cost, $amount, $name) {
            return $this->dao->insert($this->getTable_packs(), array('i_amount_cost' => $amount_cost, 'i_amount' => $amount, 's_name' => $name));
        }

        public function updatePack($id, $amount_cost, $amount, $name) {
            return $this->dao->update($this->getTable_packs(), array('i_amount_cost' => $amount_cost, 'i_amount' => $amount, 's_name' => $name), array('pk_i_id' => $id));
        }

        public function pack($id) {
            $this->dao->select('*') ;
            $this->dao->from($this->getTable_packs());
            $this->dao->where('pk_i_id', $id);
            $result = $this->dao->get();
            if($result) {
                return $result->row();
            }
            return array();
        }

        public function payPostItem($itemId) {
            return $this->dao->update($this->getTable_publish(), array('b_paid' => 1), array('fk_i_item_id' => $itemId));
        }

        public function unpayPublishFee($itemId) {
            $paid = $this->getPublishData($itemId);
            if (!$paid) {
                $this->createItem($itemId, 0, date("Y-m-d H:i:s"), 'ADMIN');
            } else {
                $this->dao->update($this->getTable_publish(), array('b_paid' => 0, 'dt_date' => date("Y-m-d H:i:s"), 'fk_i_invoice_id' => 'ADMIN'), array('fk_i_item_id' => $itemId));
            }
            $this->disableItem($itemId);
            $mItems = new ItemActions(false);
            $mItems->disable($itemId);
        }

        public function payPublishFee($itemId, $invoiceId) {
            if(strtoupper($invoiceId)=="ADMIN") {
                $invoiceId = $this->adminInvoice(
                    array(
                        'description' => sprintf(__('Publish fee for listing %d', 'payment_pro'), $itemId),
                        'item_id' => $itemId,
                        'id' => 'PUB00-' . $itemId
                    )
                );
            }

            if($this->isEnabled($itemId)) {
                $paid = $this->getPublishData($itemId);
                if (!$paid) {
                    $this->createItem($itemId, 1, date("Y-m-d H:i:s"), $invoiceId);
                } else {
                    $this->dao->update($this->getTable_publish(), array('b_paid' => 1, 'dt_date' => date("Y-m-d H:i:s"), 'fk_i_invoice_id' => $invoiceId), array('fk_i_item_id' => $itemId));
                }
                if (!OC_ADMIN) {
                    $this->updateQueue($itemId, false);
                }
                $mItems = new ItemActions(false);
                $mItems->enable($itemId);
                return PAYMENT_PRO_ENABLED;
            }
            return PAYMENT_PRO_DISABLED;
        }

        public function payPremiumFee($itemId, $invoiceId, $days = null) {
            if(strtoupper($invoiceId)=="ADMIN") {
                $invoiceId = $this->adminInvoice(
                    array(
                        'description' => sprintf(__('Premium enhancement for listing %d', 'payment_pro'), $itemId),
                        'item_id' => $itemId,
                        'id' => 'PRM00-' . $itemId
                    )
                );
            }
            $paid = $this->getPremiumData($itemId);
            if($days==null) {
                $exp_date = date('Y-m-d H:i:s', max(strtotime(@$paid['dt_expiration_date']), time())+((int)osc_get_preference('premium_days', 'payment_pro')*24*3600));
            } else {
                $exp_date = date('Y-m-d H:i:s', max(strtotime(@$paid['dt_expiration_date']), time())+($days*24*3600));
            }
            if(!$paid) {
                $this->dao->insert($this->getTable_premium(), array('dt_date' => date("Y-m-d H:i:s"), 'dt_expiration_date' => $exp_date, 'fk_i_invoice_id' => $invoiceId, 'fk_i_item_id' => $itemId));
            } else {
                $this->dao->update($this->getTable_premium(), array('dt_date' => date("Y-m-d H:i:s"), 'dt_expiration_date' => $exp_date, 'fk_i_invoice_id' => $invoiceId), array('fk_i_item_id' => $itemId));
            }
            if(!OC_ADMIN || strtoupper($invoiceId)!="ADMIN") {
                $this->updateQueue($itemId, null, false);
                $mItem = new ItemActions(false);
                return $mItem->premium($itemId, true);
            }
            return false;
        }

        public function payHighlightFee($itemId, $invoiceId) {
            if(strtoupper($invoiceId)=="ADMIN") {
                $invoiceId = $this->adminInvoice(
                    array(
                        'description' => sprintf(__('Highlight enhancement for listing %d', 'payment_pro'), $itemId),
                        'item_id' => $itemId,
                        'id' => 'HLT-' . $itemId
                    )
                );
            }
            $paid = $this->getHighlightData($itemId);
            $exp_date = date('Y-m-d H:i:s', max(strtotime(@$paid['dt_expiration_date']), time())+((int)osc_get_preference('highlight_days', 'payment_pro')*24*3600));
            if(!$paid) {
                $this->dao->insert($this->getTable_highlight(), array('dt_date' => date("Y-m-d H:i:s"), 'dt_expiration_date' => $exp_date, 'fk_i_invoice_id' => $invoiceId, 'fk_i_item_id' => $itemId));
            } else {
                $this->dao->update($this->getTable_highlight(), array('dt_date' => date("Y-m-d H:i:s"), 'dt_expiration_date' => $exp_date, 'fk_i_invoice_id' => $invoiceId), array('fk_i_item_id' => $itemId));
            }
        }

        public function unpayHighlightFee($itemId) {
            return $this->dao->delete($this->getTable_highlight(), array('fk_i_item_id' => $itemId));
        }

        public function addWallet($user, $amount) {
            $amount = (int)($amount*1000000);
            $wallet = $this->getWallet($user);
            if(isset($wallet['i_amount'])) {
                return $this->dao->update($this->getTable_wallet(), array('i_amount' => $amount+$wallet['i_amount']), array('fk_i_user_id' => $user));
            } else {
                return $this->dao->insert($this->getTable_wallet(), array('fk_i_user_id' => $user, 'i_amount' => $amount));
            }
        }

        public function getInvoiceSources() {
            $this->dao->select('s_source');
            $this->dao->from($this->getTable_invoice());
            $this->dao->groupBy('s_source');
            $result = $this->dao->get();
            if($result!==false) {
                return $result->result();
            }
            return array();
        }

        public function getSubscriptionSources() {
            $this->dao->select('s_source');
            $this->dao->from($this->getTable_subscription());
            $this->dao->groupBy('s_source');
            $result = $this->dao->get();
            if($result!==false) {
                return $result->result();
            }
            return array();
        }

        public function getQueue($date) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_mail_queue());
            $this->dao->where("dt_send_date <= '" . $date . "'");
            $result = $this->dao->get();
            if($result!==false) {
                return $result->result();
            }
            return array();
        }

        public function addQueue($date, $item, $publish = true, $premium = true) {
            return $this->dao->insert(
                $this->getTable_mail_queue(),
                array(
                    'dt_send_date' => $date,
                    'fk_i_item_id' => $item,
                    'b_publish' => $publish,
                    'b_premium' => $premium
                )
            );
        }

        public function updateQueue($item, $publish = null, $premium = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_mail_queue());
            $this->dao->where('fk_i_item_id', $item);
            $result = $this->dao->get();
            if($result!==false) {
                $queue = $result->row();
                if(isset($queue['b_publish']) && isset($queue['b_premium'])) {

                    $condition = array();
                    if ($publish != null) {
                        $condition[] = array('b_publish' => $publish);
                    }
                    if ($premium != null) {
                        $condition[] = array('b_premium' => $premium);
                    }
                    if ((($publish == null && $queue['b_publish'] == 0) || $publish == false) && (($premium == null && $queue['b_premium'] == 0) || $premium == false)) {
                        return $this->dao->delete($this->getTable_mail_queue(), array('fk_i_item_id' => $item));
                    } else {
                        return $this->dao->update($this->getTable_mail_queue(), $condition, array('fk_i_item_id' => $item));
                    }
                }
            }
            return false;
        }

        public function purgeQueue($date) {
            return $this->dao->delete($this->getTable_mail_queue(), array("dt_send_date <= '" . $date . "'"));
        }

        public function adminInvoice($params = null) {
            $rows = array(
                'description' => 'Some bogus description',
                'amount' => '0',
                'tax' => '0',
                'amount_tax' => '0',
                'amount_total' => '0',
                'quantity' => '1',
                'item_id' => '0',
                'id' => 'BGS-123'
            );
            if($params!=null && is_array($params)) {
                $rows = array_merge($rows, $params);
            }
            return $this->saveInvoice(
                "ADMIN_" . osc_logged_admin_id(),
                $rows['amount'],
                $rows['amount_tax'],
                $rows['amount_total'],
                PAYMENT_PRO_COMPLETED,
                osc_get_preference('currency', 'payment_pro'),
                osc_logged_admin_email(),
                osc_logged_admin_id(),
                "ADMIN",
                array($rows)
            );
        }

        public function pendingExtra($code, $source = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_invoice_extra());
            $this->dao->where("fk_s_pending_code", $code);
            if($source!=null) {
                $this->dao->where("s_source", $source);
            }
            $result = $this->dao->get();
            if($result!==false) {
                return $result->row();
            }
            return array();
        }

        public function invoiceExtra($id, $source = null) {
            $this->dao->select('*');
            $this->dao->from($this->getTable_invoice_extra());
            $this->dao->where("fk_i_invoice_id", $id);
            if($source!=null) {
                $this->dao->where("s_source", $source);
            }
            $result = $this->dao->get();
            if($result!==false) {
                return $result->row();
            }
            return array();
        }

        public function setPendingExtra($code, $extra, $source = 'NOSOURCE') {
            $data = $this->pendingExtra($code);
            if(isset($data['s_extra'])) {
                return $this->dao->update($this->getTable_invoice_extra(), array('fk_i_invoice_id' => 0, 's_extra' => $extra, 's_source' => $source), array('fk_s_pending_code' => $code));
            } else {
                return $this->dao->insert($this->getTable_invoice_extra(), array('fk_i_invoice_id' => 0, 's_extra' => $extra, 'fk_s_pending_code' => $code, 's_source' => $source));
            }
        }

        public function setInvoiceExtra($invoiceId, $extra, $source = 'NOSOURCE') {
            $data = $this->invoiceExtra($invoiceId);
            if(isset($data['s_extra'])) {
                return $this->dao->update($this->getTable_invoice_extra(), array('s_extra' => $extra, 's_source' => $source), array('fk_i_invoice_id' => $invoiceId));
            } else {
                return $this->dao->insert($this->getTable_invoice_extra(), array('s_extra' => $extra, 'fk_i_invoice_id' => $invoiceId, 's_source' => $source));
            }
        }

        public function updatePendingInvoiceExtra($code, $invoiceId, $source = 'NOSOURCE') {
            return $this->dao->update($this->getTable_invoice_extra(), array('fk_i_invoice_id' => $invoiceId), array('fk_s_pending_code' => $code, 's_source' => $source));
        }

        public function disablePaidItems() {
            $this->dao->select('*');
            $this->dao->from($this->getTable_publish());
            $this->dao->where("b_paid", 0);
            $this->dao->where("b_enabled", 1);
            $result = $this->dao->get();
            if($result!==false) {
                $items = $result->result();
                $ids = array();
                foreach($items as $item) {
                    $ids[] = $item['fk_i_item_id'];
                }
                $this->dao->query(sprintf("UPDATE %st_item SET b_enabled = 0 WHERE pk_i_id IN (%s);", DB_TABLE_PREFIX, implode(",", $ids)));
            }
        }

    }

