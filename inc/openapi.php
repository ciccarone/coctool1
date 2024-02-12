<?php
require __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function generate_qr_code($url, $id, $message, $mobile) {
    if ($url) {
        $qrCode = QrCode::create($url);
    } else {
        $qrCode = QrCode::create('SMSTO:+1'.$mobile.':'.$message);
    }
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $result->saveToFile(__DIR__.'/qrcode--'.$id.'.png');
}

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
                "content" => "Your job is to convince.  Write 2 sentences to sell the contact's services to the inquirer."
            ],
            [
                "role" => "user",
                "content" => "Sell Contact's services in 2 sentences to Inquirer using: {$data}"
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
    $search = '<div class="coc_container">';
    $search .= '<h2>Connect with '.get_the_title().'</h2>';
    $search .= '<form action="#" id="coc_connect">';
    $search .= '
    <input type="text" name="current_name" placeholder="Name" required>
    <input type="text" name="current_industry" placeholder="Industry">
    ';
    $search .= '<input hidden name="current_contact" value="'.get_the_ID().'">';
    $search .= '<button type="submit">Connect</button>';
    $search .= '</form></div>';

    $message = '<div id="coc_message"></div>';
    $loading = '<div id="coc_loading"></div>';
    $contact = query_contact_data(get_the_ID(), true);
    return $search . $message . $loading . $contact;
    ob_start();
    $response = coc_send_data_to_openapi($send_string);
    return ob_get_clean();
}

add_shortcode('coc_tool', 'coc_tool');

function query_contact_data($id = false, $display = false) {
    if (!$id) {
        $id = get_the_ID();
    }
    
    $data['name'] = get_the_title($id);
    $data['company'] = get_field('company', $id)[0]->post_title;
    $taxonomy  = 'service';
    $categories = get_terms($taxonomy, array('hide_empty' => false));
    $category_names = array();
    foreach ($categories as $category) {
        $category_names[] = $category->name;
    }
    $data['category_string'] = implode(', ', $category_names);
    $data['email'] = get_field('email', $id);
    $data['url'] = get_field('url', $id);
    $data['mobile'] = get_field('mobile', $id);
    
    
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    if (isset($_POST['current_name'])) {
        $message_base = 'Hi, ' . $data['name'] .'. '. $user_name . ' let me know about your services.  My name is '. $_POST['current_name'] .' and I am in the ' . $_POST['current_industry'] . ' industry.'."\n\n".'I am interested in connecting.';

        generate_qr_code(false, $id, $message_base, $data['mobile']);
    } else {
        generate_qr_code($data['url'], $id, false, false);
    }

    $data['title'] = get_field('title', $id);
    $data['address'] = get_field('address', $id);
    if (!$display) {
        $send_string = "Contact (Name: " . $data['name'] . "\nCompany: " . $data['company'] . "\nCategories: " . $data['category_string'] . "\nEmail: " . $data['email'] . "\nURL: " . $data['url'] . "\nTitle: " . $data['title'] . "\nAddress: " . $data['address'] . ")\n";
        return $send_string;
    } else {
        $display_string_html = '<script src="https://maps.googleapis.com/maps/api/js?key='.get_field('google_maps_api_key', 'option').'&callback=Function.prototype"></script>
        ';

        $display_string_html .= '<div class="coc-result">';
        $display_string_html .= '<div class="coc-result__inner">';
        $display_string_html .= '<div class="coc-result__main">';
            if (isset($data['address'])) {
                $display_string_html .= '<div class="coc-result__address">';
                    $display_string_html .= '<div class="coc-result__address__inner">';
                        $display_string_html .= '<div class="coc-result__address__name"><h3>' . $data['company'] . '</h3></div>';
                        $display_string_html .= '<div class="coc-result__address__street">' . $data['address']['name'] . '</div>';
                        $display_string_html .= '<div class="coc-result__address__city">' . $data['address']['city'] . ', ' . $data['address']['state'] . ' ' . $data['address']['post_code'] . '</div>';
                    $display_string_html .= '</div>';                    
                    $display_string_html .= '<div class="coc-result__address__qr">';
                        $display_string_html .= '<img src="' . get_stylesheet_directory_uri() . '/inc/qrcode--' . $id . '.png" alt="QR Code for ' . $data['name'] . '">';
                    $display_string_html .= '</div>';
                $display_string_html .= '</div>';
                $display_string_html .= '<div class="acf-map" data-zoom="15">';
                    $display_string_html .= '<div class="marker" data-lat="'.esc_attr($data['address']['lat']).'" data-lng="'.esc_attr($data['address']['lng']).'"></div>';
                $display_string_html .= '</div>';
            }
        $display_string_html .= '</div>';

        $display_string_html .= '<div class="coc-result__aside">';

        $display_string_html .= '<div class="contact-data">';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Name</div>';
            $display_string_html .= '<div class="contact-data__data">' . $data['name'] . '</div>';
        $display_string_html .= '</div>';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Company</div>';
            $display_string_html .= '<div class="contact-data__data">' . $data['company'] . '</div>';
        $display_string_html .= '</div>';        
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Mobile</div>';
            $mobile = $data['mobile'];
            $formattedMobile = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "($1) $2-$3", $mobile);
            $display_string_html .= '<div class="contact-data__data">' . $formattedMobile . '</div>';        
            $display_string_html .= '</div>';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Categories</div>';
            $display_string_html .= '<div class="contact-data__data">' . $data['category_string'] . '</div>';
        $display_string_html .= '</div>';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Email</div>';
            $display_string_html .= '<div class="contact-data__data"><a href="mailto:'.$data['email'].'">' . $data['email'] . '</a></div>';
        $display_string_html .= '</div>';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">URL</div>';
            $display_string_html .= '<div class="contact-data__data"><a href="'.$data['url'].'">' . $data['url'] . '</a></div>';
        $display_string_html .= '</div>';
        $display_string_html .= '<div class="contact-data__row">';
            $display_string_html .= '<div class="contact-data__label">Title</div>';
            $display_string_html .= '<div class="contact-data__data">' . $data['title'] . '</div>';
        $display_string_html .= '</div>';
    $display_string_html .= '</div>';
        $display_string_html .= '</div>';
        $display_string_html .= '</div>';
        $display_string_html .= '</div>';

    return $display_string_html;
    }
}

function collect_contact_data($id = false, $display = false) {
    if (!$id) {
        $id = get_the_ID();
    }
    $contact_data = query_contact_data($id, 'false');

}

function coc_ajax() {
    if (isset($_POST['current_name'])) {
        $current_name = $_POST['current_name'];
        $current_industry = $_POST['current_industry'];
        $current_contact = $_POST['current_contact'];
        $contact_data = query_contact_data($current_contact, false) . "Inquirer (Name: " . $current_name . "\nIndustry: " . $current_industry . ")";
        $response = coc_send_data_to_openapi($contact_data);
        echo $response;
    }
    die();
}
add_action('wp_ajax_coc_ajax', 'coc_ajax');
add_action('wp_ajax_nopriv_coc_ajax', 'coc_ajax');
