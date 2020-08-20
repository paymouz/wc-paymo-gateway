<?php
/*
 * Plugin Name: WooCommerce PAYMO Payment Gateway
 * Plugin URI: https://paymo.uz
 * Description: Payment from Humo and UzCard bank cards
 * Author: Ilkhom Idiev
 * Author URI: https://paymo.uz
 * Version: 1.0.1
 *
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;


add_filter('woocommerce_payment_gateways', 'paymo_add_gateway_class');
function paymo_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Paymo_Gateway'; // your class name is here
    return $gateways;
}


add_action('plugins_loaded', 'paymo_init_gateway_class');
function paymo_init_gateway_class()
{

    // Do nothing, if WooCommerce is not available
    if (!class_exists('WC_Payment_Gateway'))
        return;

    class WC_Paymo_Gateway extends WC_Payment_Gateway
    {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {
            $this->id = 'paymo'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'PAYMO Gateway';
            $this->method_description = 'Онлайн оплата с карт UzCard и Humo'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            $lang_codes = ['ru_RU' => 'ru', 'en_US' => 'en', 'uz_UZ' => 'uz'];
            $this->lang = isset($lang_codes[get_locale()]) ? $lang_codes[get_locale()] : 'en';


            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->store_id = $this->testmode ? $this->get_option('test_store_id') : $this->get_option('store_id');
            $this->api_key = $this->testmode ? $this->get_option('test_api_key') : $this->get_option('api_key');
            $this->theme = $this->get_option('theme');
            $this->colors[] = $this->get_option('color1');
            $this->colors[] = $this->get_option('color2');
            $this->colors[] = $this->get_option('color3');
            $this->colors[] = $this->get_option('color4');
            $this->colors[] = $this->get_option('color5');
            $this->colors[] = $this->get_option('color6');
            $this->colors[] = $this->get_option('color7');
            $this->colors[] = $this->get_option('color8');
            $this->colors[] = $this->get_option('color9');
            $this->colors[] = $this->get_option('color10');

            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // You can also register a webhook here
             add_action( 'woocommerce_api_paymo_callback', array( $this, 'webhook' ) );
        }

        public function admin_options()
        {
            ?>
            <h2>Параметры интеграции со шлюзом PAYMO</h2>

            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paymo_callback_url">Ваш callback-адрес </label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Ваш callback-адрес</span></legend>
                            <input class="input-text regular-input " type="input" disabled value="<?= site_url('/?wc-api=paymo_callback'); ?>">
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Состояние',
                    'label' => 'Включить метод платежа PAYMO Gateway',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Метод платежа',
                    'type' => 'text',
                    'default' => 'Оплата онлайн (UzCard или Humo)',
                ),
                'description' => array(
                    'title' => 'Описание метода платежа',
                    'type' => 'textarea',
                    'default' => 'Оплата на сайте с помощью банковской карты',
                ),
                'theme' => array(
                    'title' => 'Оформление виджета',
                    'type' => 'select',
                    'options' => [
                        'grey' => 'По-умолчанию (серый)',
//                        'orange' => 'Оранжевый',
//                        'pink' => 'Розовый',
//                        'blue' => 'Синий',
//                        'green' => 'Зеленый',
                        'custom' => 'Свой (укажите цвета ниже)'
                    ]
                ),
                'color1' => array(
                    'title' => 'Цвет фона',
                    'type' => 'text',
                    'default' => '#ffffff',
                ),
                'color2' => array(
                    'title' => 'Цвет текста',
                    'type' => 'text',
                    'default' => '#0a0a0f',
                ),
                'color3' => array(
                    'title' => 'Фон шапки',
                    'type' => 'text',
                    'default' => '#604b6a',
                ),
                'color4' => array(
                    'title' => 'Текст шапки',
                    'type' => 'text',
                    'default' => '#ffffff',
                ),
                'color5' => array(
                    'title' => 'Фон карты',
                    'type' => 'text',
                    'default' => '#eeeeee',
                ),
                'color6' => array(
                    'title' => 'Текст карты',
                    'type' => 'text',
                    'default' => '#596164',
                ),
                'color7' => array(
                    'title' => 'Фон кнопки оплаты',
                    'type' => 'text',
                    'default' => '#2a8100',
                ),
                'color8' => array(
                    'title' => 'Текст кнопки оплаты',
                    'type' => 'text',
                    'default' => '#ffffff',
                ),
                'color9' => array(
                    'title' => 'Фон кнопки "Назад"',
                    'type' => 'text',
                    'default' => '#eef3f6',
                ),
                'color10' => array(
                    'title' => 'Текст кнопки "Назад"',
                    'type' => 'text',
                    'default' => '#0a0a0f',
                ),
                'testmode' => array(
                    'title' => 'Тестирование',
                    'label' => 'Включить режим тестирования',
                    'type' => 'checkbox',
                    'description' => 'Запросы будут отправлять на тестовый стенд PAYMO',
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                'test_store_id' => array(
                    'title' => 'Test store_id',
                    'type' => 'text'
                ),
                'test_api_key' => array(
                    'title' => 'Test api_key',
                    'type' => 'password',
                ),
                'store_id' => array(
                    'title' => 'Production store_id',
                    'type' => 'text'
                ),
                'api_key' => array(
                    'title' => 'Production api_key',
                    'type' => 'password'
                )
            );
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= '. Включен тестовый режим.';
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */
        public function payment_scripts()
        {
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            // no reason to enqueue JavaScript if API keys are not set
            if (empty($this->store_id) || empty($this->api_key)) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!$this->testmode && !is_ssl()) {
                return;
            }

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script('paymo_js', 'https://cdn.pays.uz/checkout/js/v1.0.1/'.($this->testmode ? 'test-checkout.js' : 'checkout.js'));

            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script('woocommerce_paymo', plugins_url('paymo.js', __FILE__), array('jquery', 'paymo_js'));

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script('woocommerce_paymo', 'paymo_params', array(
                'store_id' => $this->store_id,
                'total' => $this->get_order_total() * 100,
            ));

            wp_enqueue_script('woocommerce_paymo');
        }

        public function receipt_page($order_id)
        {
            $order = new WC_Order($order_id);

            $total = $order->get_total() * 100;
            $sign = hash('sha256', $this->store_id.$total.$order_id.$this->api_key);
            $success_redirect = $this->get_return_url( $order );
            $colors = json_encode($this->colors);

            echo '<div id="paymo-parent-frame"></div>';
            echo '<p>Нажмите кнопку "Оплатить", чтобы перейти к оплате.</p>';
            echo '<div class="form-row form-row-wide">';
            echo "<input type='button' class='btn btn-default button alt' value='Оплатить' onclick='PaymoPaymentForm(\"{$this->lang}\", \"{$order->get_id()}\", \"{$sign}\", \"{$this->theme}\", {$colors}, \"{$success_redirect}\");' />";
            echo '</div>';
        }

        /*
          * Fields validation, more in Step 5
         */
        public function validate_fields()
        {

        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment($order_id)
        {

            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);

            return [
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order_pay',
                    $order->get_id(),
                    add_query_arg('key', $order->get_order_key(), $order->get_checkout_payment_url(true))
                )
            ];

//            /*
//              * Array with parameters for API interaction
//             */
//            $args = array();
//
//            /*
//             * Your API interaction could be built with wp_remote_post()
//             */
//            $response = wp_remote_post('{payment processor endpoint}', $args);
//
//
//            if (!is_wp_error($response)) {
//
//                $body = json_decode($response['body'], true);
//
//                // it could be different depending on your payment processor
//                if ($body['response']['responseCode'] == 'APPROVED') {
//
//                    // we received the payment
//                    $order->payment_complete();
//                    $order->reduce_order_stock();
//
//                    // some notes to customer (replace true with false to make it private)
//                    $order->add_order_note('Hey, your order is paid! Thank you!', true);
//
//                    // Empty cart
//                    $woocommerce->cart->empty_cart();
//
//                    // Redirect to the thank you page
//                    return array(
//                        'result' => 'success',
//                        'redirect' => $this->get_return_url($order)
//                    );
//
//                } else {
//                    wc_add_notice('Please try again.', 'error');
//                    return;
//                }
//
//            } else {
//                wc_add_notice('Connection error.', 'error');
//                return;
//            }
        }

        /*
         * Callback
         */
        public function webhook()
        {
            $payload = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->answer(0,'Can not parse input message');
            }

            $sign = md5($this->store_id.$payload['transaction_id'].$payload['invoice'].$payload['amount'].$this->api_key);
            if ($sign != $payload['sign']) {
                $this->answer(0,'Auth failed: sign is incorrect');
            }

            try {
                $order = new WC_Order($payload['invoice']);
            } catch (Exception $ex) {
                $this->answer(0, "Инвойс {$payload['invoice']} не найден");
            }


            if ($order->is_paid()) {
                $this->answer(0, "Инвойс {$order->get_id()} уже оплачен");
            }

            $order->payment_complete($payload['transaction_id']);
            $order->add_order_note('Оплата успешно принята. ID-транзакции PAYMO: '.$payload['transaction_id'], true);

//            update_option('webhook_debug', $payload);
            $this->answer(1, "Инвойс успешно оплачен");
        }

        public function answer($status, $message)
        {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }

            die(json_encode([
                'status' => $status,
                'message' => $message
            ]));
        }
    }
}