<?php
/*
Plugin Name: ACW plugin
Plugin URI: https://github.com/mojtabafallah
Description: افزودن امکانات به سایت
Version: 1.1.0
Requires PHP: 7.4
Author: Mojtaba Fallah
Author URI: https://github.com/mojtabafallah
*/
require 'vendor/autoload.php';

//add assets
add_action('wp_enqueue_scripts', 'acw_add_assets');

function acw_add_assets()
{
    wp_enqueue_script('acw_script', plugins_url('assets/acw-js.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_style('acw_style', plugins_url('assets/acw-styles.css', __FILE__), array(), '1.0.0');
}

add_action('gform_after_submission_8', 'set_post_content', 10, 2);
function set_post_content($entry, $form)
{
    unset($_SESSION['acw_validation']);
}

add_action('gform_post_paging_8', 'alert_user', 10, 3);
function alert_user($form, $source_page_number, $current_page_number)
{
    if ($current_page_number == 2 && $source_page_number == 1) {
        $_SESSION['acw_validation'] = 1;
    }
}

add_filter('gform_validation', 'custom_validation');
function custom_validation($validation_result)
{
    $userName = '';
    $password = '';
    $rePassword = '';
    $name = '';
    $lastName = '';
    $email = '';
    $mobile = '';

    $form = $validation_result['form'];

    if ($form['id'] == 2 || $form['id'] == 8)//form register artist
    {
        $fields = $form['fields'];
        $current_page = rgpost('gform_source_page_number_' . $form['id']) ? rgpost('gform_source_page_number_' . $form['id']) : 1;

        foreach ($fields as $field) {
            if ($field['inputName'] == 'acw_first_name') {
                $name = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_last_name') {
                $lastName = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_password') {
                $password = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_repassword') {
                $rePassword = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_mobile') {
                $mobile = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_username') {
                $userName = RGFormsModel::get_field_value($field);
                if ($current_page == 1) {
                    //check username exist
                    if (username_exists($userName)) {
                        // set the form validation to false
                        $validation_result['is_valid'] = false;
                        $field->failed_validation = true;
                        if ($form['id'] == 2) {
                            $field->validation_message = 'نام کاربری از قبل وجود دارد';
                        } elseif ($form['id'] == 8) {
                            $field->validation_message = 'Username already exists';
                        }
                    }
                }
            }

            if ($field['inputName'] == 'acw_email') {
                $email = RGFormsModel::get_field_value($field);
            }
        }

        //check password
        if ($password !== $rePassword) {
            // set the form validation to false
            $validation_result['is_valid'] = false;
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $field->failed_validation = true;
                    if ($form['id'] == 2) {
                        $field->validation_message = 'کلمه عبور یکسان نیست';
                    } elseif ($form['id'] == 8) {
                        $field->validation_message = 'The password is not the same';
                    }
                }

                if ($field['inputName'] == 'acw_repassword') {
                    $field->failed_validation = true;
                    if ($form['id'] == 2) {
                        $field->validation_message = 'کلمه عبور یکسان نیست';
                    } elseif ($form['id'] == 8) {
                        $field->validation_message = 'The password is not the same';
                    }
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $password = RGFormsModel::get_field_value($field);
                    if (strlen($password) < 8) {
                        $validation_result['is_valid'] = false;
                        $field->failed_validation = true;
                        if ($form['id'] == 2) {
                            $field->validation_message = 'حداقل کلمه عبور 8 کاراکتر میباشد';
                        } elseif ($form['id'] == 8) {
                            $field->validation_message = 'The minimum password is 8 characters';
                        }
                    }
                }
            }
        }

        if ($current_page == 1) {
            if ($validation_result['is_valid']) {
                //register user
                $user_id = wp_insert_user(array(
                    'user_login' => $userName,
                    'user_pass' => $password,
                    'user_email' => $email,
                    'first_name' => $name,
                    'last_name' => $lastName,
                    'display_name' => $name . ' ' . $lastName,
                    'role' => 'artist'
                ));
                if (is_wp_error($user_id)) {
                    $validation_result['is_valid'] = false;
                } else {
                    //send Email
                    if ($form['id'] == 2) {
                        $subject = 'ثبت نام سایت گروه دنیای دست سازه های هنری';
                        $body = '<p>خانم/آقای ' .
                            $name . ' ' . $lastName . ' ورود شما را به گروه دنیای دست سازه های هنری آریایی خوشامد میگوییم. عضویت شما با کد اشتراک ' . $user_id . ' در سایت این مجموعه ثبت گردید </p>';
                        $headers = array('From: گروه دنیای دست سازه های هنری   <info@acwgp.com> Content-Type: text/html; charset=UTF-8');

                        wp_mail($email, $subject, $body);

                        //send sms
                        sendSMS($mobile, 'register', [
                            'token' => $lastName,
                            'token2' => $user_id
                        ]);

                    } elseif ($form['id'] == 8) {
                        $subject = 'Register site Art Craft World Group';
                        $body = '<p>' .
                            $name . ' ' . $lastName . ' Dear, your registration was successful. Your user code: ' . $user_id . '</p>';
                        $headers = array('From: گروه دنیای دست سازه های هنری   <info@acwgp.com> Content-Type: text/html; charset=UTF-8');

                        wp_mail($email, $subject, $body);
                    }

                    wp_set_auth_cookie($user_id);
                }
            }
        }


    }//form register artist

    if ($form['id'] == 4 || $form['id'] == 9)//form register
    {
        $fields = $form['fields'];
        foreach ($fields as $field) {
            if ($field['inputName'] == 'acw_first_name') {
                $name = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_last_name') {
                $lastName = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_password') {
                $password = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_repassword') {
                $rePassword = RGFormsModel::get_field_value($field);
            }

            if ($field['inputName'] == 'acw_mobile') {
                $mobile = RGFormsModel::get_field_value($field);
            }


            if ($field['inputName'] == 'acw_username') {
                $userName = RGFormsModel::get_field_value($field);
                //check username exist
                if (username_exists($userName)) {
                    // set the form validation to false
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    if ($form['id'] == 4)
                        $field->validation_message = 'نام کاربری از قبل وجود دارد';
                    elseif ($form['id'] == 9)
                        $field->validation_message = 'Username already exists';
                }
            }

            if ($field['inputName'] == 'acw_email') {
                $email = RGFormsModel::get_field_value($field);
                if (email_exists($email)) {
                    // set the form validation to false
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    if ($form['id'] == 4)
                        $field->validation_message = 'ایمیل از قبل وجود دارد';
                    elseif ($form['id'] == 9)
                        $field->validation_message = 'Email already exists';
                }
            }
        }

        //check password

        if ($password !== $rePassword) {
            // set the form validation to false
            $validation_result['is_valid'] = false;
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $field->failed_validation = true;
                    if ($form['id'] == 4)
                        $field->validation_message = 'کلمه عبور یکسان نیست';
                    elseif ($form['id'] == 9)
                        $field->validation_message = 'The password is not the same';
                }

                if ($field['inputName'] == 'acw_repassword') {
                    $field->failed_validation = true;
                    if ($form['id'] == 4)
                        $field->validation_message = 'کلمه عبور یکسان نیست';
                    elseif ($form['id'] == 9)
                        $field->validation_message = 'The password is not the same';
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $password = RGFormsModel::get_field_value($field);
                    if (strlen($password) < 8) {
                        $field->failed_validation = true;
                        if ($form['id'] == 4) {
                            $field->validation_message = 'حداقل کلمه عبور 8 کاراکتر میباشد';
                        } elseif ($form['id'] == 9) {
                            $field->validation_message = 'The minimum password is 8 characters';
                        }
                    }
                }
            }
        }

        if ($validation_result['is_valid']) {
            //register user
            $user_id = wp_insert_user(array(
                'user_login' => $userName,
                'user_pass' => $password,
                'user_email' => $email,
                'first_name' => $name,
                'last_name' => $lastName,
                'display_name' => $name . ' ' . $lastName,
                'role' => 'customer'
            ));

            if (is_wp_error($user_id)) {
                $validation_result['is_valid'] = false;
            } else {
                //send Email
                if ($form['id'] == 4) {
                    $subject = 'ثبت نام سایت گروه دنیای دست سازه های هنری';
                    $body = '<p>' .
                        $name . ' ' . $lastName . ' عزیز ثبت نام شما با موفقیت انجام شد کد کاربری شما: ' . $user_id . '</p>';
                    $headers = array('From: گروه دنیای دست سازه های هنری   <artcraftworld2021@gmail.com> Content-Type: text/html; charset=UTF-8');

                    wp_mail($email, $subject, $body);

                    //send sms
                    sendSMS($mobile, 'register', [
                        'token' => $lastName,
                        'token2' => $user_id
                    ]);

                    //update digits
                    $mobile = substr($mobile, 1);
                    update_user_meta($user_id, 'digits_phone_no', $mobile);
                    update_user_meta($user_id, 'digits_phone', '+98' . $mobile);


                } elseif ($form['id'] == 9) {
                    wp_mail($email, 'Register site Art Craft World Group ',
                        '<p>' . $name . ' ' . $lastName . ' Dear, your registration was successful. Your user code: ' . $user_id . '</p>');
                }

                wp_set_auth_cookie($user_id);
            }

        }
    }

    if ($form['id'] == 12 || $form['id'] == 13)//فرم ثبت نام اگر از قبل لاگین باشه
    {
        $userId = get_current_user_id();
        $userItem = get_user_by('id', $userId);
        $userItem->add_role('artist');

        //farsi
        if ($form['id'] == 12) {
            //send email
            $subject = 'ثبت نام سایت گروه دنیای دست سازه های هنری';
            $body = '<p>' . $userItem->display_name . ' عزیز ثبت نام شما با موفقیت انجام شد:  </p>';
            $headers = array('From: گروه دنیای دست سازه های هنری   <artcraftworld2021@gmail.com> Content-Type: text/html; charset=UTF-8');
            wp_mail($email, $subject, $body);
        }

        //english
        if ($form['id'] == 13) {
            //send email
            $subject = 'Registering the site Art Craft World Group';
            $body = '<p>' . $userItem->display_name . ' Dear, your registration was successful.  </p>';
            $headers = array('From: Art Craft World Group   <artcraftworld2021@gmail.com> Content-Type: text/html; charset=UTF-8');
            wp_mail($email, $subject, $body);
        }
    }
    $validation_result['form'] = $form;
    return $validation_result;
}

add_filter('gform_validation_message', 'change_message', 10, 2);
function change_message($message, $form)
{
    if ($form['id'] == 4 || $form['id'] == 1 || $form['id'] == 2 || $form['id'] == 12) {
        return "<div class='gform_validation_errors'>خطا! لطفا اطلاعات را بررسی کنید</div>";
    }

    if ($form['id'] == 9) {
        return "<div class='gform_validation_errors'>Error! Please check the information</div>";
    }

    return $message;

}

//add role artist
function acw_add_role_artist()
{
    add_role('artist', 'هنرمند', get_role('subscriber')->capabilities);
    add_role('gallery_owner', 'گالری دار', get_role('subscriber')->capabilities);
}

add_action('init', 'acw_add_role_artist');

add_action('init', 'acw_add_short_codes');

function acw_add_short_codes()
{
    add_shortcode('acw-register-artist', function () {
        if (is_user_logged_in() && (current_user_can('artist') || current_user_can('seller'))) {

            if (is_rtl())
                echo do_shortcode('[woocommerce_my_account]');
            else
                echo do_shortcode('[woocommerce_my_account]');
        } else {
            if (is_user_logged_in()) {
                echo do_shortcode(' [gravityform id="12" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            } else {
                echo do_shortcode(' [gravityform id="2" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            }
        }
    });

    //gallery
    add_shortcode('acw-register-gallery', function () {
        if (is_user_logged_in() && (current_user_can('gallery'))) {

            if (is_rtl())
                echo do_shortcode('[woocommerce_my_account]');
            else
                echo do_shortcode('[woocommerce_my_account]');
        } else {
            if (is_user_logged_in()) {
                //TODO باید فرم جدید ک اسم و مشخصات نخاد رو نایش بدیم
                echo do_shortcode(' [gravityform id="12" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            } else {
                echo do_shortcode(' [gravityform id="5" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            }
        }
    });

    add_shortcode('acw-register', function () {
        if (is_user_logged_in()) {
            if (is_rtl())
                echo do_shortcode('[woocommerce_my_account]');
            else
                echo do_shortcode('[woocommerce_my_account]');
        } else {
            echo do_shortcode(' [gravityform id="4" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
        }
    });

    //English

    add_shortcode('acw-register-artist-en', function () {
        if (is_user_logged_in() && current_user_can('artist')) {
            echo do_shortcode('[woocommerce_my_account]');

        } else {
            if (is_user_logged_in()) {
                echo do_shortcode(' [gravityform id="13" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            } else {
                echo do_shortcode(' [gravityform id="8" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
            }
        }
    });


    add_shortcode('acw-register-en', function () {
        if (is_user_logged_in()) {
            echo do_shortcode('[woocommerce_my_account]');
        } else {
            echo do_shortcode(' [gravityform id="9" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
        }
    });
}

add_filter('wp_mail_content_type', function ($content_type) {
    return 'text/html';
});

function sendSMS($phoneNumber, $pattern, $args)
{
    $arr = [
        'receptor' => $phoneNumber,
        'template' => $pattern,
        'sender' => '10008663'
    ];
    $arr = array_merge($arr, $args);
    $str = '';

    foreach ($args as $token => $value) {
        $str .= $token . '=' . $value . '&';
    }

    $str = rtrim($str, "&");
    wp_remote_get("https://api.kavenegar.com/v1/635A64724F5130414F6A574555517949327A7A4F74354C4E7454583273713037757842565638306B754F383D/verify/lookup.json?receptor={$phoneNumber}&template={$pattern}&sender=10008663&{$str}");
}

add_action('boldlab_action_before_page_header_inner', function () {
    ?>
    <div class="acw-top-menu">
        <?php if (is_user_logged_in()): ?>
            <div class="acw-welcome">
                <?php if (is_rtl()): ?>
                    <a class="acw-top-link" href="/my-account">
                    <span>
                        <?php
                        $userItem = get_user_by('id', get_current_user_id());
                        echo $userItem->display_name;
                        ?>
                    </span>
                    </a>
                <?php else: ?>
                    <a class="acw-top-link" href="/en/my-account">
                    <span>
                        <?php
                        $userItem = get_user_by('id', get_current_user_id());
                        echo $userItem->display_name;
                        ?>
                    </span>
                    </a>
                <?php endif; ?>

                <?php if (current_user_can('seller') || current_user_can('administrator')): ?>
                    <?php if (is_rtl()): ?>
                        <a class="acw-top-link" href="/dashboard">داشبورد</a>
                    <?php else: ?>
                        <a class="acw-top-link" href="/en/dashboard">Dashboard</a>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <?php if (is_rtl()): ?>
                <div>
                    <a class="acw-top-link" href="/register">ثبت نام</a>
                    <a class="acw-top-link" href="/my-account">ورود</a>
                </div>

            <?php else: ?>
                <div>
                    <a class="acw-top-link" href="/register-en">Register</a>
                    <a class="acw-top-link" href="/en/my-account/">Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
});

add_filter('woocommerce_get_cart_page_permalink', function ($url) {
    if (!is_rtl()) {
        return '/en/' . $url;
    }
    return $url;
}, 99);

add_filter('woocommerce_get_checkout_page_permalink', function ($url) {
    if (!is_rtl()) {
        return '/en/' . $url;
    }
    return $url;
}, 99);

add_action('wp_head', function () {
    ?>
    <style>
        /*Dokan*/

        .dokan-dashboard-wrap .dokan-form-group #insert-media-button,
        input[type="submit"].dokan-btn-theme, a.dokan-btn-theme, .dokan-btn-theme {
            color: #FFFFFF !important;
            background-color: #34ABDB !important;
            border-color: #2AA1D1 !important;
        }

        .dokan-btn-theme.active,
        .open .dropdown-toggle.dokan-btn-theme,
        .open .dropdown-togglea.dokan-btn-theme,
        input[type="submit"].dokan-btn-theme:hover,
        input[type="submit"].dokan-btn-theme:focus,
        input[type="submit"].dokan-btn-theme:active,
        a.dokan-btn-theme:hover, .dokan-btn-theme:hover,
        a.dokan-btn-theme:focus, .dokan-btn-theme:focus,
        a.dokan-btn-theme:active, .dokan-btn-theme:active,
        .dokan-geo-filters-column .dokan-geo-product-search-btn,
        .open .dropdown-toggleinput[type="submit"].dokan-btn-theme,
        .dokan-dashboard-wrap .dokan-subscription-content .pack_price,
        .dokan-dashboard-wrap .dokan-dashboard-content .wpo_wcpdf:hover,
        .dashboard-content-area .woocommerce-importer .wc-actions a.button,
        .dokan-dashboard-wrap .dokan-form-group #insert-media-button:hover,
        input[type="submit"].dokan-btn-theme.active, a.dokan-btn-theme.active,
        .dokan-dashboard-wrap .dokan-modal-content .modal-footer .inner button,
        .dashboard-content-area .woocommerce-importer .wc-actions button.button-next,
        .wc-setup .wc-setup-content .checkbox input[type=checkbox]:checked + label::before,
        .dokan-dashboard-wrap .dokan-dashboard-content .my_account_quotes td:last-child a:hover,
        .dokan-dashboard-wrap .dokan-dashboard-content .dokan-btn:not(.disconnect, .wc-pao-remove-option, .dokan-btn-success):hover,
        .dokan-dashboard-wrap .dokan-dashboard-content .dokan-btn:not(.disconnect, .wc-pao-remove-option, .dokan-btn-success):focus,
        .dokan-dashboard-wrap .dokan-dashboard-content #delivery-time-calendar .fc-button-primary:not(.fc-button-active):not(:disabled):hover {
            color: #FFFFFF !important;
            border-color: #2C98C3 !important;
            background-color: #2FA3D1 !important;
        }

        #dokan-store-listing-filter-wrap .right .toggle-view .active,
        .dokan-dashboard-wrap .dokan-settings-area .dokan-page-help p a,
        .dokan-dashboard-wrap .dokan-dashboard-header .entry-title small a,
        .dokan-dashboard-wrap .dokan-settings-area .dokan-ajax-response + a,
        .dokan-dashboard-wrap .dokan-settings-area .dokan-pa-all-addons div a,
        .dokan-dashboard-wrap .dokan-subscription-content .seller_subs_info p span,
        .dokan-table.product-listing-table .product-advertisement-th i.fa-stack-2x,
        .dokan-dashboard-wrap .dokan-stuffs-content .entry-title span.dokan-right a,
        .dokan-dashboard-wrap .dokan-settings-area #dokan-shipping-zone .router-link-active,
        .dokan-dashboard-wrap .dokan-settings-area .dokan-ajax-response ~ .dokan-text-left p a,
        .dokan-dashboard-wrap .dokan-settings-area .dokan-pa-create-addons .back-to-addon-lists-btn,
        .dokan-dashboard-wrap .dokan-withdraw-content .dokan-panel-inner-container .dokan-w8 strong a,
        .dokan-dashboard-wrap .dokan-settings-area #dokan-shipping-zone .dokan-form-group .limit-location-link,
        .dokan-dashboard-wrap .dashboard-content-area .woocommerce-importer .woocommerce-importer-done::before,
        .product-edit-new-container .dokan-proudct-advertisement .dokan-section-heading h2 span.fa-stack i.fa-stack-2x {
            color: #34ABDB !important;
        }

        .dokan-dashboard .dokan-dash-sidebar,
        .wc-setup .wc-setup-steps li.done::before,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu,
        .dokan-dashboard-wrap .dashboard-widget .dokan-dashboard-announce-unread,
        .dokan-dashboard-wrap .dokan-dashboard-content #vendor-own-coupon .code:hover {
            background-color: #38748C !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li a,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active a,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li a:hover {
            color: #99C7DA !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li a:hover,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active a {
            color: #FFFFFF !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li:hover,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.dokan-common-links a:hover {
            background-color: #34ABDB !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu ul.navigation-submenu,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li ul.navigation-submenu li {
            background: #38748Ced !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu ul.navigation-submenu li a {
            color: #99C7DA !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu ul.navigation-submenu li:hover a {
            font-weight: 800 !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu ul.navigation-submenu li a:focus {
            outline: none !important;
            background: none !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active ul.navigation-submenu {
            border-bottom: 0.5px solid #34ABDB !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li:hover:not(.active) ul.navigation-submenu {
            background: #38748Ced !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li:hover:not(.active).has-submenu:after {
            border-color: transparent #38748Ced transparent transparent;
            border-left-color: #38748Ced;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li ul.navigation-submenu li:hover:before,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li ul.navigation-submenu li.current:before {
            border-color: #FFFFFF !important;
        }

        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li ul.navigation-submenu li:hover a,
        .dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li ul.navigation-submenu li.current a {
            color: #FFFFFF !important;
        }

        .dokan-dashboard-wrap .dokan-booking-wrapper ul.dokan_tabs .active {
            border-top: 2px solid #34ABDB !important;
        }

        .dokan-dashboard-wrap a:focus {
            outline-color: #34ABDB !important;
        }

        .wc-setup .wc-setup-steps li.done,
        .wc-setup .wc-setup-steps li.active,
        .wc-setup .wc-setup-steps li.done::before,
        .wc-setup .wc-setup-steps li.active::before,
        .dokan-dashboard-wrap .dashboard-content-area .wc-progress-steps li.done,
        .dokan-dashboard-wrap .dashboard-content-area .wc-progress-steps li.active,
        .dokan-dashboard-wrap .dashboard-content-area .wc-progress-steps li.done::before,
        .dokan-dashboard-wrap .dashboard-content-area .wc-progress-steps li.active::before,
        .store-lists-other-filter-wrap .range-slider-container input[type="range"]::-webkit-slider-thumb,
        .dokan-geolocation-location-filters .dokan-range-slider-value + input[type="range"]::-webkit-slider-thumb {
            color: #2FA3D1 !important;
            border-color: #2FA3D1 !important;
        }

        .dokan-subscription-content .pack_content_wrapper .product_pack_item.current_pack {
            border-color: #2FA3D1 !important;
        }

    </style>
    <?php
}, 9999);

add_filter('dokan_get_navigation_url', function ($url) {

    if (!is_rtl()) {
        $url = str_replace('/dashboard', '/en/dashboard', $url);
    }
    return $url;
});