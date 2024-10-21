<?php
/**
 * Plugin Name:       nbd_custom_Weight_printting_option
 * Plugin URI:        https://cmsmart.net
 * Description:       nbd_custom_Weight_printting_option
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.3
 * Author:            Huy
 * Author URI:        https://cmsmart.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       web-to-print-online-designer
 * Domain Path:       /languages
 */

add_action('nbd_js_config','apne_nbd_js_config_weght');
function apne_nbd_js_config_weght(){
    ?>
        var prt_weight_nbd_js_ct = true;
        var js_check_category_ct = true;
    <?php
    
    $product_id = $_GET['product_id'] ? $_GET['product_id'] : get_the_ID() ;
    $terms = get_the_terms($product_id, 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        $product_categories = array();
        foreach ($terms as $term) {
            $product_categories[] = $term->name;
        }
        ?>
            var productCategories = <?php echo json_encode($product_categories); ?>;
        <?php
    }
}

add_action('nbd_extra_css','custom_style_printting_weght_h');
function custom_style_printting_weght_h(){
    ?>
        <link rel="stylesheet" href="<?= plugin_dir_url(__FILE__) . 'assets/style.css'; ?>">
    <?php
}

add_action( 'wp_enqueue_scripts','add_js_ct_weght_pt',100);
function add_js_ct_weght_pt() {
    wp_add_inline_script( 'nbdesigner', 'const prt_weight_nbd_js_ct = true;', 'before' );
    $pei = $_GET['product_id'] ? $_GET['product_id'] : get_the_ID() ;
    $sr_product = get_post_meta( $pei, 'srapi', true );
}

add_action( 'admin_enqueue_scripts','nbdesigner_admin_enqueue_fs_weght_pt',100);
function nbdesigner_admin_enqueue_fs_weght_pt($hook) {
    wp_add_inline_script( 'admin_nbdesigner', 'const prt_weight_nbd_js_ct = true;', 'before' );
    $custom_js = "
        jQuery(document).ready(function($) {
            $('.woocommerce_order_items #order_line_items table.display_meta tr td > p').each(function(){
                var content = $(this).text;
                if(content.charAt(0) === ',') {
                    $(this).text(content.substring(1));
                }
            });
        });
        ";
    wp_add_inline_script('jquery', $custom_js);
}

//----------------------------------------------------------------

add_action('nbd_add_enable_check_spine','nb_custom_input_duplicate_design');
function nb_custom_input_duplicate_design($option) {
    $srapi = get_post_meta($_GET['post'],'srapi',true) ? get_post_meta($_GET['post'],'srapi',true) : '';
    ?>
    <p class="nbo-form-field" style="display: flex;align-items: center;">
        <label for="_nbo_enable" style="margin-right: 8px;"><?php _e('Product code (sr)', 'web-to-print-online-designer'); ?></label>
        <input type="text" name="srapi" class="srapi" value="<?php echo $srapi; ?>">
    </p>
    <?php
}

add_action( 'save_post', 'my_function_on_save_sr_apui' );
function my_function_on_save_sr_apui( $post_id ) {
    if(isset($_REQUEST['srapi'])) {
        update_post_meta( $post_id, 'srapi', $_REQUEST['srapi'] );
    }
}


function xmlToArray($xmlObject) {
    $out = [];
    
    foreach ($xmlObject as $key => $node) {
        $nodeArray = [];

        foreach ($node->attributes() as $attrKey => $attrValue) {
            $nodeArray['zp'][$attrKey] = (string)$attrValue;
        }

        if (count($node->children()) > 0) {
            $nodeArray['value'] = xmlToArray($node);
        } else {
            $nodeArray['value'] = (string)$node;
        }

        if (isset($out[$key])) {
            if (!is_array($out[$key]) || !array_key_exists(0, $out[$key])) { 
                $out[$key] = [$out[$key]];
            }
            $out[$key][] = $nodeArray;
        } else {
            $out[$key] = $nodeArray;
        }
    }
    return $out;
}


//new
function get_option_name_by_id($id, $index, $data) {
    foreach ($data as $item) {
        if ($item['id'] == $id) {
            return $item['general']['attributes']['options'][$index]['name'];
        }
    }
    return null;
}

add_filter('woocommerce_add_to_cart_validation','check_validate_quantity_api',10,3);
function check_validate_quantity_api($validate, $product_id, $quantity) {
    $class = new NBD_FRONTEND_PRINTING_OPTIONS();
    $post_data = $_POST;
    $product = wc_get_product($product_id);
    $option_id = $class->get_product_option($product_id);
    if( !$option_id ){
        return $validate;
    }
    if( isset($post_data['nbd-field']) || isset($post_data['nbo-add-to-cart']) ){
        $options = $class->get_option( $option_id );
        $option_fields  = unserialize( $options['fields'] );
        $nbd_field      = isset( $post_data['nbd-field'] ) ? $post_data['nbd-field'] : array();
        $qtys           = $_REQUEST['nbb-qty-fields'];
        $bulk_fields = $_REQUEST['nbb-fields'];
        $first_field    = reset($bulk_fields);

        $nbb_fields = array();
        for( $i=0; $i < count($first_field); $i++ ){
            $arr = array();
            foreach($nbd_field as $field_id => $field_value){
                if( !isset($bulk_fields[$field_id]) ){
                    $arr[$field_id] = $field_value;
                }
            }
            foreach($bulk_fields as $field_id => $bulk_field){
                $arr[$field_id] = $bulk_field[$i];
            }
            $nbb_fields[] = $arr;
        }

        $arrField = $option_fields['fields'];
        $final_result = [];
        foreach ($nbb_fields as $index => $selection) {
            $output = [];
            foreach ($selection as $id => $option_index) {
                $name = get_option_name_by_id($id, $option_index, $arrField);
                foreach ($arrField as $item) {
                    if ($item['id'] == $id) {
                        $output[$item['general']['title']] = $name;
                    }
                }
            }
            $output['quantity'] = $qtys[$index];
            $final_result[] = $output;
        }

        $coloractive = $final_result[0]['Color'];

        if( !empty($_FILES) && isset($_FILES["nbd-field"]) ) {
            foreach( $_FILES["nbd-field"]['name'] as $field_id => $file ){
                if( !isset($nbd_field[$field_id]) ){
                    $nbd_upload_field = $class->upload_file( $_FILES["nbd-field"], $field_id );
                    if( !empty($nbd_upload_field) ){
                        $nbd_field[$field_id] = $nbd_upload_field[$field_id];
                    }
                }
            }
        }
        
        $original_price = (float)$product->get_price('edit');
        $option_price   = $class->option_processing( $options, $original_price, $nbd_field, $quantity, null, $product );
        if( $option_price['manual_pm'] ){
            $original_price   = $option_price['original_price'];
        }
        $sr_product = get_post_meta( $product_id, 'srapi', true );
        $total = null;
        
        if($sr_product) {
            $data = extractColorAndSize(get_data_4_warehouses($sr_product));
            $fields = $option_price['fields'];

            foreach ($data as $item1) {

                if ($item1['total'] == 0 || ($item1['total'] - 10) <= 0) {
                    continue;
                }

                foreach ($final_result as $item2) {
                    if (strcasecmp($item1['color'], $item2['Color']) == 0 && strcasecmp($item1['size'], $item2['Size']) == 0 && ($item1['total'] - 10) < $item2['quantity']) {
                        wc_add_notice('There is not enough stock on '.$item1['size'].' size on this color, please select another color for this size selection.', 'error');
                        $validate = false;
                    }
                }
            }

        }

    }
    return $validate;

}


function get_data_4_warehouses($sr_product) {
    $url = "https://www.alphabroder.com/cgi-bin/online/xml/inv-request.w?sr=".$sr_product."&pr=y&zp=&rg=y&userName=ap15286&password=sEd65As2";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "ap15286:sEd65As2");
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Lỗi cURL: ' . curl_error($ch);
        curl_close($ch);
        exit();
    }
    curl_close($ch);
    $xmlObject = simplexml_load_string($response);
    xmlToArray($xmlObject);
    $arrayData = xmlToArray($xmlObject);
    $codesToKeep = ['CC', 'PH', 'KC', 'MA'];
    foreach ($arrayData['item'] as &$item) {
        foreach ($item['value']['whse'] as $key => $whse) {
            if (!in_array($whse['zp']['code'], $codesToKeep)) {
                unset($item['value']['whse'][$key]);
            }
        }
        $item['value']['whse'] = array_values($item['value']['whse']);
    }
    header("Content-Type: application/json");
    return $arrayData;
}

function extractColorAndSize($jsondata) {
    $result = [];
    foreach ($jsondata['item'] as $item) {
        $description = $item['zp']['description'];
        $parts = explode(' ', $description);

        $total = 0;
        foreach ($item['value']['whse'] as $key => $whse) {
           $total += $whse['value'];
        }

        if (count($parts) >= 3) {

            $color = $parts[1];
            $size = $parts[2];

            $result[] = [
                'color' => $color,
                'size'  => $size,
                'total' => $total
            ];
        }
    }

    return $result;
}

add_action('wp_head', 'print_product_categories_js');
function print_product_categories_js() {
    if (is_product()) {
        global $post;
        $product_id = $post->ID;
        $sr_product = get_post_meta( $product_id, 'srapi', true );
        // $sr_product = 'G500';
        
        if($sr_product) {
            $data = extractColorAndSize(get_data_4_warehouses($sr_product));
            if($data) {
                wp_enqueue_script('custom-js', get_template_directory_uri() . '/js/custom.js', array(), '1.0', true);
                wp_localize_script('custom-js', 'productDataapi', array(
                    'data' => $data
                ));
            }
        }
    }
}


/////////////////////////////////////////////

add_action('ndb_modal_preview_check','modalPreviewCheckcart');
function modalPreviewCheckcart(){
    ?>
       <div id="myModal" class="modal fade nbdesigner_modal">
            <div class="modal-dialog">

                <div class="modal-content tren an">
                    <div class="modal-header" style="">
                        <div class="popup-top">
                            <svg fill="#000000" xmlns="http://www.w3.org/2000/svg" 
                                 width="50px" height="50px" viewBox="0 0 52 52" enable-background="new 0 0 52 52" xml:space="preserve">
                                <g>
                                    <path d="M20.1,26H44c0.7,0,1.4-0.5,1.5-1.2l4.4-15.4c0.3-1.1-0.5-2-1.5-2H11.5l-0.6-2.3c-0.3-1.1-1.3-1.8-2.3-1.8
                                        H4.6c-1.3,0-2.5,1-2.6,2.3C1.9,7,3.1,8.2,4.4,8.2h2.3l7.6,25.7c0.3,1.1,1.2,1.8,2.3,1.8h28.2c1.3,0,2.5-1,2.6-2.3
                                        c0.1-1.4-1.1-2.6-2.4-2.6H20.2c-1.1,0-2-0.7-2.3-1.7v-0.1C17.4,27.5,18.6,26,20.1,26z"/>
                                    <circle cx="20.6" cy="44.6" r="4"/>
                                    <circle cx="40.1" cy="44.6" r="4"/>
                                </g>
                            </svg>
                        </div>
                        <button style="margin-top: 0;position: relative;top: -40px;font-size: 30px;" type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    
                    <div class="ct-total">
                        <div class="infos">
                            <span>Please review your design and product summary carefully before placing your order.</span>
                            <span>By checking the box below and submitting your order, you agree to our terms and conditions,including our cancellation and return policy.</span>
                        </div>
                        <div class="checkCf">
                            <input type="checkbox" type="checkbox" class="check-ct" id="check-ct">
                            <label class="conf" for="check-ct">I have reviewed and approved my design and product.</label>
                        </div>
                        <div class="ct-total_process">
                            <button class="ct-process no-click" ng-click="saveData()">Add To Cart</button>
                        </div>
                    </div>
                    </div>


                    <div class="modal-content duoi hien">
                        <div class="modal-header" style="">
                            <div class="popup-top">
                                <img src="<?= plugin_dir_url(__FILE__) . 'assets/success.gif'; ?>" alt="">
                            </div>
                            <button style="margin-top: 0;position: relative;top: -40px;font-size: 30px;" type="button" class="close" data-dismiss="modal" aria-hidden="true">
                                ×
                            </button>
                        </div>
                        
                        <div class="ct-total">
                            <div class="infos">
                                <span>your item has been added to the cart.</span>
                                <span>What would you like to do next?</span>
                            </div>
                            <div class="ct-total_process duoi">
                                <button class="ct-process">
                                    <a href="<?php echo home_url(); ?>">Go to Homepage</a>
                                </button>
                                <button class="ct-process">
                                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>">View Cart</a>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php
}

add_filter('nbd_editor_process_action','nbod_ct_customize_process');
function nbod_ct_customize_process($prrocess_action) {
    // if( !wp_is_mobile() ) {
        $prrocess_action = 'showPreview()';
    // } else {
        // $prrocess_action = 'saveData();';
    // }
    return $prrocess_action;
}

//add_filter('change_url_ct_show_popup','change_url_spopup_funt',10,2);
function change_url_spopup_funt($url,$pr_id) {
    $url = add_query_arg( array(
        'product_id'    => $pr_id,
    ), getUrlPageNBD( 'create' ) );
    return $url;
}

// prt_adpop_design_js

add_action('nbd_adop_custom_popup_wn','nbd_adop_custom_popup_wn_ft');
function nbd_adop_custom_popup_wn_ft() {
    ?>
        <style type="text/css">
            #myModal{
               display: block;
               visibility: hidden; 
               transition: .4s;
                width: 700px;
                height: 382px;
                background: #3333333d;
                position: absolute;
                top: 20%;
                z-index: 99999999;
            }

            .in {
               visibility: visible !important;
            }

            .fade.in{
               opacity: 1;
               transition: visibility .5s, opacity .5s linear;
            }

            .fade.in .modal-content {
               top: 35px;
            }

            .modal-content {
               width: 700px;
               left: 0%;
               /* top: -180px;*/
               position: absolute;
               color: black;
               transition: all .4s;
            }

            .lietke {
                display: flex;
                flex-direction: column;
               /* align-items: flex-start;
                margin-left: 95px;*/
            }

            .checkCfỉm {
                margin: 20px 0px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .hd {
                font-size: 16px;
            }

            .lietke > b {
                font-weight: bold;
            }

            #check-ctdt {
                width: 16px;
                height: 16px;
                cursor: pointer;
            }

            .conf {
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
            }

            .smit span {
                text-transform: uppercase;
                border-radius: 25px;
                padding: 10px 30px;
                background: aliceblue;
                cursor: pointer;
            }

            .bttn.noclick {
                opacity: .6;
                pointer-events: none;
            }

            .closepp {
                position: absolute;
                top: -35px;
                right: 15px;
                background: transparent;
                font-size: 26px;
                padding: 6px;
                cursor: pointer;
            }

            @media screen and (max-width: 767px) {
                #nbd-m-upload-design-wrap #myModal,
                #nbd-m-upload-design-wrap #myModal .modal-content 
                {
                    width: 99%;
                    height: 450px;
                }
            }
        </style>
        <div id="myModal" class="modal fade nbdesigner_modal">
            <div class="modal-content">
                <span class="closepp">
                    ×
                </span>
                <svg width="35px" height="35px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <rect x="0" fill="none" width="20" height="20"/><g>
                <path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z"/></g></svg>
                <p class="hd">Before proceeding, please ensure that your design meets the following requirements:</p>
                <div class="lietke">
                    <div><b>Size:</b> <span>The file must match the product dimensions.</span></div>
                    <div><b>Quality:</b> <span>Ensure a minimum resolution of 300 DPI for optimal print quality.</span></div>
                    <div><b>Color Mode:</b> <span>Convert your design to CMYK color mode.</span></div>
                    <div><b>Size:</b> <span>Convert all text layers to outlines or curves to avoid font issues during printing.</span></div>
                </div>
                <div class="checkCfỉm">
                    <input type="checkbox" type="checkbox" class="check-ctdt" id="check-ctdt">
                    <label class="conf" for="check-ctdt">Your design meets the above requirements.</label>
                </div>

                <div class="smit">
                    <span class="bttn noclick" onclick="hideUploadFrameCt()">Submit</span>
                </div>
            </div>
        </div>
    <?php
}

/////////////////////////////////////////////

//add_action('nbod_attributes_field_add_weight','nbod_attributes_field_add_weight_ft');
function nbod_attributes_field_add_weight_ft() {
    ?>
        <div ng-hide="field.nbd_type != 'size'" class="atrr-custom" style="display: grid;margin-top: 10px;">
            <span>Add weight(g)</span>
            <input name="options[fields][{{fieldIndex}}][general][attributes][options][{{opIndex}}][weightct]" class="nbd-short-ip" type="text" ng-model="op.weightct"/>
        </div>
    <?php
}

//add_filter( 'woocommerce_add_cart_item_data', 'add_cart_item_data_ct_weight', 30, 4 );
function add_cart_item_data_ct_weight($cart_item_data, $product_id, $variation_id, $quantity) {
    if(isset( $_REQUEST['ct_weight'] )) {
        $price_vat = $_REQUEST['ct_weight'];
        $cart_item_data['ct_weight'] = $price_vat;
    }
    return $cart_item_data;
}

//add_action('woocommerce_cart_loaded_from_session','change_price_final_cart',999999);
function change_price_final_cart( $cart ) { 
    $class = new NBD_FRONTEND_PRINTING_OPTIONS();
    foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
        if($cart_item['ct_weight'] != '') {
            $variation_id   = $cart_item['variation_id'];
            $product_id     = $cart_item['product_id'];
            $product        = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
            $options        = $cart_item['nbo_meta']['options'];
            $fields         = $cart_item['nbo_meta']['field'];
            $original_price = (float)$product->get_price( 'edit' );
            $quantity       = $cart_item['quantity'];
            $totalWeight = $cart_item['ct_weight'] * $cart_item['quantity'];
            $option_price   = $class->option_processing( $options, $original_price, $fields, $totalWeight, $cart_item_key, $product );
            if( $option_price['manual_pm'] ){
                $original_price   = $option_price['original_price'];
            }
            if( isset( $cart_item['nbo_meta']['nbdpb'] ) ){
                $path   = NBDESIGNER_CUSTOMER_DIR . '/' . $cart_item['nbo_meta']['nbdpb'] . '/preview';
                $images = Nbdesigner_IO::get_list_images( $path, 1 );
                if( count( $images ) ){
                    ksort( $images );
                    $option_price['cart_image'] = Nbdesigner_IO::wp_convert_path_to_url( end( $images ) );
                }
            }
            $adjusted_price = $original_price + $option_price['total_price'] - $option_price['discount_price'];
            $adjusted_price = $adjusted_price > 0 ? $adjusted_price : 0;
            WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $adjusted_price );
            // WC()->cart->cart_contents[ $cart_item_key ]['nbo_meta']['option_price'] = $option_price;
            // $adjusted_price = apply_filters( 'nbo_adjusted_price', $adjusted_price, $cart_item );
            // WC()->cart->cart_contents[ $cart_item_key ]['nbo_meta']['price'] = $adjusted_price;
            // $needed_change  = apply_filters( 'nbo_need_change_cart_item_price', true, WC()->cart->cart_contents[ $cart_item_key ] );
            // if( $needed_change ) WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $adjusted_price ); 
        }
    }
    // $item['data']->set_price($price);
    // $cart->set_session();
}

add_action('nbd_modern_extra_stages','nbd_modern_extra_stages_ft');
function nbd_modern_extra_stages_ft(){
    ?>
        <div class="ctai-cart">
            <div class="fn-pr">
                <svg fill="#333333" width="20px" height="20px" viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg"><path d="M 20.0079 39.6485 L 47.3596 39.6485 C 48.2735 39.6485 49.0703 38.8985 49.0703 37.8907 C 49.0703 36.8829 48.2735 36.1328 47.3596 36.1328 L 20.4063 36.1328 C 19.0704 36.1328 18.2501 35.1953 18.0391 33.7656 L 17.6641 31.3047 L 47.4062 31.3047 C 50.8281 31.3047 52.5859 29.1953 53.0783 25.8438 L 54.9532 13.4453 C 55.0003 13.1407 55.0468 12.7656 55.0468 12.5547 C 55.0468 11.4297 54.2030 10.6563 52.9142 10.6563 L 14.6407 10.6563 L 14.1954 7.6797 C 13.9610 5.8750 13.3048 4.9609 10.9141 4.9609 L 2.6876 4.9609 C 1.7501 4.9609 .9532 5.7813 .9532 6.7188 C .9532 7.6797 1.7501 8.5000 2.6876 8.5000 L 10.6094 8.5000 L 14.3594 34.2344 C 14.8516 37.5625 16.6094 39.6485 20.0079 39.6485 Z M 51.0623 14.1953 L 49.3987 25.4219 C 49.2110 26.8750 48.4377 27.7656 47.0548 27.7656 L 17.1485 27.7891 L 15.1563 14.1953 Z M 21.8594 51.0391 C 23.9688 51.0391 25.6563 49.375 25.6563 47.2422 C 25.6563 45.1328 23.9688 43.4453 21.8594 43.4453 C 19.7266 43.4453 18.0391 45.1328 18.0391 47.2422 C 18.0391 49.375 19.7266 51.0391 21.8594 51.0391 Z M 43.7735 51.0391 C 45.9062 51.0391 47.5939 49.375 47.5939 47.2422 C 47.5939 45.1328 45.9062 43.4453 43.7735 43.4453 C 41.6641 43.4453 39.9532 45.1328 39.9532 47.2422 C 39.9532 49.375 41.6641 51.0391 43.7735 51.0391 Z"/></svg>
                <span class="price-final_sc"></span>
            </div>
            <div class="ct-adcart">
                <span ng-click="saveData();">Add to cart</span>
            </div>
        </div>
    <?php
}