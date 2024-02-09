<?php

function coc_send_data_to_openapi($data)
{
    $url = 'https://api.openai.com/v1/chat/completions';
    $openapi_secret_key = get_field('openapi_key', 'option');
    $ch = curl_init($url);
    $postData = array(
        'model' => 'gpt-4',
        'messages' => [
            [
                "role" => "system",
                "content" => "Summarize contact's data into third-person 1-sentence business introduction script"
            ],
            [
                "role" => "user",
                "content" => "You are salesman, goal to sell Contact services to Inquirer specifically geared towards Inquirer's name and industry: {$data}"
            ]
        ]
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openapi_secret_key
    ));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $response;
}

function coc_tool() {
    $send_string = collect_contact_data();
    $search = '<h2>Connect with '.get_the_title().'</h2>';
    $search .= '<form action="#" id="coc_connect">';
    $search .= '
    <input type="text" name="current_name" placeholder="Name" required>
    <input type="text" name="current_industry" placeholder="Industry">
    ';
    $search .= '<input hidden name="current_contact" value="'.get_the_ID().'">';
    $search .= '<button type="submit">Connect</button>';
    $search .= '</form>';

    $message = '<div id="coc_message"></div>';
    $loading = '<div id="coc_loading"></div>';
    return $search . $message . $loading;
    ob_start();
    // $response = coc_send_data_to_openapi($send_string);
    echo 'text';
    return ob_get_clean();
}

add_shortcode('coc_tool', 'coc_tool');

function collect_contact_data($id = false) {
    if (!$id) {
        $id = get_the_ID();
    }
    $name = get_the_title($id);
    $company = get_field('company', $id)[0]->post_title;
    $taxonomy  = 'service';
    $categories = get_terms($taxonomy, array('hide_empty' => false));
    $category_names = array();
    foreach ($categories as $category) {
        $category_names[] = $category->name;
    }
    $category_string = implode(', ', $category_names);
    $email = get_field('email', $id);
    $url = get_field('url', $id);
    $title = get_field('title', $id);
    $address = get_field('address', $id);
    
    $send_string = "Contact (Name: $name\nCompany: $company\nCategories: $category_string\nEmail: $email\nURL: $url\nTitle: $title\nAddress: $address)\n";
    return $send_string;
}

function coc_ajax() {
    if (isset($_POST['current_name'])) {
        $current_name = $_POST['current_name'];
        $current_industry = $_POST['current_industry'];
        $current_contact = $_POST['current_contact'];
        $contact_data = collect_contact_data($current_contact) . "Inquirer (Name: " . $current_name . "\nIndustry: " . $current_industry . ")";
        $response = coc_send_data_to_openapi($contact_data);
        echo $response;
    }
    die();
}
add_action('wp_ajax_coc_ajax', 'coc_ajax');
add_action('wp_ajax_nopriv_coc_ajax', 'coc_ajax');