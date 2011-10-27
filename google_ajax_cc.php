<?php
/*
Plugin Name: Google AJAX Currency Converter (Convertor)
Description: Currency Converter (Convertor) that uses Google Calculator and update the results with AJAX
Author: nostop8
Version: 1.1
*/

define('_CC_DIR_URL', WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__), '', plugin_basename(__FILE__)));
define('_CC_PAGE_SLUG', 'googleAjaxCC');
define('_CC_CURRENCIES_FILE', dirname(__FILE__) . '/google_ajax_cc.txt');

add_action('widgets_init', 'load_google_ajax_cc');

function load_google_ajax_cc() {
    register_widget('google_ajax_cc');
}

add_action('init', 'google_ajax_cc_init');

function google_ajax_cc_init() {
    if(isset($_POST['google_ajax_cc'])) {
        extract($_POST['google_ajax_cc']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/ig/calculator?q=' . $amount . $from . '=?' . $to);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = array();
        foreach((array) explode(',', str_replace(array('{', '}'), '', $result)) as $item) {
            $row = null;
            $row = explode(':', $item);
            $data[trim($row[0])] = trim($row[1]);
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        print json_encode($data);
        die();
    }

    if($_GET['page'] == _CC_PAGE_SLUG && isset($_POST['google_ajax_cc_list_form'])) {
        $file = fopen(_CC_CURRENCIES_FILE, 'w+');
        $output = str_replace(array('<?php', '<?', '?>'), '', $_POST['google_ajax_cc_list']);
        fwrite($file, $_POST['google_ajax_cc_list']);
        fclose($file);
    }
}

add_action('admin_menu', 'google_ajax_cc_admin');

function google_ajax_cc_admin() {
    add_submenu_page('options-general.php', 'Currency List', 'Currency List', 'manage_options', _CC_PAGE_SLUG, 'google_ajax_cc_list');
}

function google_ajax_cc_list() {
    $file = fopen(_CC_CURRENCIES_FILE, 'r');
    $output = fread($file, filesize(_CC_CURRENCIES_FILE));
    fclose($file);
?>
    <h2>Google Ajax Currency Convertor List</h2>
    <form name="google_ajax_cc_list_form" action="<?php $_SERVER['REQUEST_URI'] ?>" method="POST">
        <input type="hidden" name="google_ajax_cc_list_form" />
        <div>
            <textarea name="google_ajax_cc_list" cols="50" rows="30"><?php print $output; ?></textarea>
        </div>
        <div>
            <input type="submit" value="Save" />
        </div>
    </form>
<?php
}

class google_ajax_cc extends WP_Widget {
    var $curency_list;

    function google_ajax_cc() {
        $this->WP_Widget('google_ajax_cc', __('Google AJAX Currency Converter (Convertor)', 'google_ajax_cc'), array('description' => __('Currency Converter (Convertor) that uses Google Calculator and update the results with AJAX', 'google_ajax_cc')));
        $this->curency_list = $this->_cc_preload_currencies();

        wp_enqueue_script('google_ajax_cc',  _CC_DIR_URL . 'google_ajax_cc.js', array('jquery'));
        wp_enqueue_style('google_ajax_cc',  _CC_DIR_URL . 'google_ajax_cc.css');
    }

    function _cc_simple_str($str) {
        return strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $str));
    }

    function _cc_preload_currencies() {
        $file = fopen(_CC_CURRENCIES_FILE, 'r');
        $output = fread($file, filesize(_CC_CURRENCIES_FILE));
        $rows = explode("\n", $output);
        $currencies = array();
        foreach($rows as $row) {
            $currency = explode('|', $row);
            $currencies[ $currency[0] ] = $currency[1];
        }
        fclose($file);

        return $currencies;
    }

    function _cc_field_params($name) {
        return array(
            'id' => $this->get_field_id('_cc_' . $this->_cc_simple_str($name)),
            'name' => $this->get_field_name('_cc_' . $this->_cc_simple_str($name)),
        );
    }

    function _cc_currency_select_field($label, $name, $default) {
        $field = $this->_cc_field_params($name);
        ?>
            <label for="<?php print $field['id'] ?>"><?php _e($label) ?></label>
            <select id="<?php print $field['id'] ?>" name="<?php print $field['name'] ?>">
                <?php foreach((array) $this->curency_list as $code => $currency) : ?>
                    <option value="<?php print $code ?>" <?php if($code == $default) print 'selected' ?>><?php print $code . ' ' . __($currency) ?></option>
                <?php endforeach; ?>
            </select>
        <?php
    }

    function _cc_amount_field($label, $name, $default) {
        $field = $this->_cc_field_params($name);
        ?>
            <label for="<?php print $field['id'] ?>"><?php print $label ?></label>
            <input id="<?php print $field['id'] ?>" name="<?php print $field['name'] ?>" value="<?php print $default ?>" />
        <?php
    }

    function form( $vars) {
        if(empty($vars)) {
            $_cc_amout = 100;
            $_cc_from = 'USD';
            $_cc_to = 'EUR';
        }
        else
            extract($vars);
        ?>
            <div class="amount">
                <?php $this->_cc_amount_field(__('Default Amount: ', 'google_ajax_cc'), 'amout', $_cc_amout) ?>
            </div>
            <div class="from">
                <?php $this->_cc_currency_select_field(__('Default From: ', 'google_ajax_cc'), 'from', $_cc_from) ?>
            </div>
            <div class="to">
                <?php $this->_cc_currency_select_field(__('Default To: ', 'google_ajax_cc'), 'to', $_cc_to) ?>
            </div>
        <?php
    }

    function widget($args, $vars) {
        extract($vars);
        ?>
            <div class="google_ajax_cc_container">
                <form class="google_ajax_cc" method="POST" action="" name="<?php $this->get_field_name('google_ajax_cc_form'); ?>">
                    <div class="amount row clearfix">
                        <?php print $this->_cc_amount_field(__('Amount: ', 'google_ajax_cc'), 'amount', $_cc_amout) ?>
                    </div>
                    <div class="from row clearfix">
                        <?php $this->_cc_currency_select_field(__('From: ', 'google_ajax_cc'), 'from', $_cc_from) ?>
                    </div>
                    <div class="to row clearfix">
                        <?php $this->_cc_currency_select_field(__('To: ', 'google_ajax_cc'), 'to', $_cc_to) ?>
                    </div>
                    <div class="result row clearfix"><label><?php print _e('Result: ', 'google_ajax_cc') ?></label><span class="inner"></span></div>
                </form>
            </div>
        <?php
    }
}

?>
