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
                "content" => "You are salesman, goal to sell contact's services: {$data}"
            ],
            [
                "role" => "user",
                "content" => "Summarize contact's data into third-person 1-sentence business introduction script"
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
    $search = '<h2>Connect with this contact</h2>';
    $search .= '<form action="#" id="coc_connect">';
    $search .= '<textarea name="public" placeholder="Details"></textarea>';
    $search .= '<button type="submit">Connect</button>';
    $search .= '</form>';
    return $search;
    ob_start();
    $response = coc_send_data_to_openapi($send_string);
    echo $response;
    return ob_get_clean();
}

add_shortcode('coc_tool', 'coc_tool');

function collect_contact_data() {
    $id = get_the_ID();
    $name = get_the_title();
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
    
    $send_string = "Name: $name\nCompany: $company\nCategories: $category_string\nEmail: $email\nURL: $url\nTitle: $title\nAddress: $address";
    return $send_string;
}

function coc_ajax() {
    if (isset($_POST['public'])) {
        $public = $_POST['public'];
        $contact_data = collect_contact_data() . "\nPublic: " . $public;
        $response = coc_send_data_to_openapi($contact_data);
        echo $response;
    }
    die();
}
add_action('wp_ajax_coc_ajax', 'coc_ajax');
add_action('wp_ajax_nopriv_coc_ajax', 'coc_ajax');