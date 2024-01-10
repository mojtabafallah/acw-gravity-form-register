<?php
/*
Plugin Name: ACW plugin
Plugin URI: https://github.com/mojtabafallah
Description: افزودن امکانات به سایت
Version: 1.0.0
Requires PHP: 7.4
Author: Mojtaba Fallah
Author URI: https://github.com/mojtabafallah
*/

//add assets
add_action('wp_enqueue_scripts', 'acw_add_assets');

function acw_add_assets()
{
    wp_enqueue_script('acw_script', plugins_url('assets/acw-js.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_style('acw_style', plugins_url('assets/acw-styles.css', __FILE__), array(), '1.0.0');
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

    $form = $validation_result['form'];
    if ($form['id'] == 2)//form register artist
    {
        $fields = $form['fields'];
        foreach ($fields as $field) {
            if ($field['inputName'] == 'acw_first_name') {
                $name = $value = RGFormsModel::get_field_value($field);
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

            if ($field['inputName'] == 'acw_username') {
                $userName = RGFormsModel::get_field_value($field);
                //check username exist
                if (username_exists($userName)) {
                    // set the form validation to false
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = 'نام کاربری از قبل وجود دارد';
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
                    $field->validation_message = 'کلمه عبور یکسان نیست';
                }

                if ($field['inputName'] == 'acw_repassword') {
                    $field->failed_validation = true;
                    $field->validation_message = 'کلمه عبور یکسان نیست';
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $password = RGFormsModel::get_field_value($field);
                    if (strlen($password) < 8) {
                        $field->failed_validation = true;
                        $field->validation_message = 'حداقل کلمه عبور 8 کاراکتر میباشد';
                    }
                }
            }
        }

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
        wp_set_auth_cookie($user_id);
    }

    if ($form['id'] == 4)//form register
    {
        $fields = $form['fields'];
        foreach ($fields as $field) {
            if ($field['inputName'] == 'acw_first_name') {
                $name = $value = RGFormsModel::get_field_value($field);
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

            if ($field['inputName'] == 'acw_username') {
                $userName = RGFormsModel::get_field_value($field);
                //check username exist
                if (username_exists($userName)) {
                    // set the form validation to false
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = 'نام کاربری از قبل وجود دارد';
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
                    $field->validation_message = 'کلمه عبور یکسان نیست';
                }

                if ($field['inputName'] == 'acw_repassword') {
                    $field->failed_validation = true;
                    $field->validation_message = 'کلمه عبور یکسان نیست';
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($field['inputName'] == 'acw_password') {
                    $password = RGFormsModel::get_field_value($field);
                    if (strlen($password) < 8) {
                        $field->failed_validation = true;
                        $field->validation_message = 'حداقل کلمه عبور 8 کاراکتر میباشد';
                    }
                }
            }
        }

        //register user
        $user_id = wp_insert_user(array(
            'user_login' => $userName,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $name,
            'last_name' => $lastName,
            'display_name' => $name . ' ' . $lastName,
            'role' => 'subscriber'
        ));
        wp_set_auth_cookie($user_id);
    }

    $validation_result['form'] = $form;
    return $validation_result;
}

//add role artist
function acw_add_role_artist()
{
    add_role('artist', 'هنرمند', get_role('subscriber')->capabilities);
}

add_action('init', 'acw_add_role_artist');

add_action('init', 'acw_add_short_codes');

function acw_add_short_codes()
{
    add_shortcode('acw-register-artist', function () {
        if (is_user_logged_in() && current_user_can('artist')) {
            echo '<h5>شما از قبل ثبت نام کرده اید.</h5>';
        } else {
            echo do_shortcode(' [gravityform id="2" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
        }
    });

    add_shortcode('acw-register', function () {
        if (is_user_logged_in()) {
            echo '<h5>شما از قبل ثبت نام کرده اید.</h5>';
        } else {
            echo do_shortcode(' [gravityform id="4" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice" theme="orbital"]');
        }
    });

}

add_action('boldlab_action_before_page_header_inner', function () {
    ?>
    <div class="acw-top-menu">
        <?php if (is_user_logged_in()): ?>
            <div class="acw-welcome">
                <?php if (is_rtl()): ?>
                    <span>سلام </span>
                <?php else: ?>
                    <span>Hi </span>
                <?php endif; ?>
                <span>
            <?php
            $userItem = get_user_by('id', get_current_user_id());
            echo $userItem->display_name;
            ?>
            </span>
            </div>
        <?php else: ?>
            <?php if (is_rtl()): ?>
                <a href="/register">ورود</a>
            <?php else: ?>
                <a href="/register">Login</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
});
