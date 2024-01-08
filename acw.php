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
                    $field->failed_validation = true;
                    $field->validation_message = 'حداقل کلمه عبور 8 کاراکتر میباشد';
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
    }

    $validation_result['form'] = $form;
    return $validation_result;
}