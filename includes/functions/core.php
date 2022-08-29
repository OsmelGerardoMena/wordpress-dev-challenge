<?php

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

function adding_custom_meta_boxes($post_type, $post)
{
    add_meta_box(
        'my-meta-box',
        __('Citation'),
        'render_citation_meta_box',
        'post',
        'normal',
        'default'
    );
}

function render_citation_meta_box($post)
{
    $citation = get_post_meta($post->ID, 'citation', true);

    $args = array(
        'quicktags' => false,
        'textarea_rows' => 5,
        'media_buttons' => false
    );
    $editor_id = 'citation';
    wp_editor($citation, $editor_id, $args);
}

function citation_save_data($post_id)
{
    // disable autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    $old_citation = get_post_meta($post_id, 'citation', true);
    $citation =  $_POST['citation'];
    update_post_meta($post_id, 'citation', $citation, $old_citation);
}
add_action('save_post', 'citation_save_data');

function shortcode_view_citation($atts)
{

    $post = get_post();
    $post_id = $post->ID;

    $p = shortcode_atts(
        array(
            'post_id' => $post_id
        ),
        $atts,
        'mc-citacion'
    );

    $citation = get_post_meta($p['post_id'], 'citation', true);

    return apply_filters('the_content', $citation);
}

function menu_link_init()
{
    global $wpdb;
    $db_table_name = $wpdb->prefix . 'link_errors';  // table name
    $charset_collate = $wpdb->get_charset_collate();

    //Check to see if the table exists already, if not, then create it
    if ($wpdb->get_var("show tables like '$db_table_name'") != $db_table_name) {
        $sql = "CREATE TABLE $db_table_name (
                id int(11) NOT NULL auto_increment,
                url varchar(255) NOT NULL,
                type varchar(100) NOT NULL,
                origin int(11) NOT NULL,
                created_at datetime NOT NULL,
                status INT DEFAULT 0 NULL,
                UNIQUE KEY id (id)
        ) $charset_collate;";

        // The dbDelta function that allows us to create tables safely is defined in the file upgrade.php that is included below
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function menu_link_error()
{
    add_menu_page(
        "Link error",
        "Link error",
        "manage_options",
        "menu_link_error",
        "menu_links_admin",
        "dashicons-feedback",
        75
    );
}

//validate url
function filterUrl($valor)
{
    $res['sta']=0;
    $res['cod']=999;
    $text1 = 'http';
    $text3 = 'https';
    $text2 = 'www';

    if (trim($valor) == '') {
        return 0;
    } else {
        $pos = strpos($valor, "%20");
        if($pos !== false){
            //option Enlace malformado;
            $res['sta']=3;
            return $res;
            exit;
        }else{
            $pos = strpos($valor, $text1);        
            if (($pos !== false) and (strpos($valor, $text3) === false)) {
                //option enlace inseguro
                $res['sta']=1;
                return $res;
                exit;
            } else {
                $pos2 = strpos($valor, $text3);
                if ($pos2 === false) {
                    //option sin protocolo                
                    $res['sta']=2;
                    return $res;
                    exit;
                } else {
                    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|](\.)[a-z]{2}/i", $valor)) {
                        //option Enlace malformado;
                        $res['sta']=3;
                        return $res;
                        exit;
                    } else {
                        $handle = curl_init($valor);
                        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
                        $response = curl_exec($handle);
                        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                        if ($httpCode >= 200 && $httpCode < 300) {
                            $res['sta']=5;
                            return $res;
                        } else {
                            $res['sta']=4;
                            $res['cod']=intval($httpCode);
                            return $res;
                        }
                        curl_close($handle);
                        exit;
                    }
                }
            }
        }
    }
}

function links_insert($url = null, $status = null, $origin = null, $codstatus = 0)
{
    global $wpdb;

    if ($status == 1) {
        $status = 'Enlace inseguro';
    }
    if ($status == 2) {
        $status = 'Protocolo no especificado';
    }
    if ($status == 3) {
        $status = 'Enlace malformado ';
    }
    if ($status == 4) {
        $status = 'Enlace que retorna un Status Code incorrecto '.$codstatus;
    }

    $tabla_link = $wpdb->prefix . 'link_errors';
    $created_at = date('Y-m-d H:i:s');
    $wpdb->insert(
        $tabla_link,
        array(
            'url' => $url,
            'type' => $status,
            'created_at' => $created_at,
            'origin'  => $origin,
            'status'  => "$codstatus",
        )
    );
}

//url check function to call from cron
function verified_url()
{
    global $wpdb;
    //we consult the posts to know if the content has a url and verify the same
    $tabla_posts = $wpdb->prefix . 'posts';
    $posts = $wpdb->get_results("SELECT * FROM $tabla_posts WHERE post_status='publish' and post_type='post' ");

    $text1 = 'href=';
    foreach ($posts as $post_) {
        $status = '0';
        $status = get_post_meta($post_->ID, '__verified_content', true);
        if ($status != '1') {
            update_post_meta($post_->ID, '__verified_content', '1');
            if (strpos($post_->post_content, $text1) !== false) {
                $origen = $post_->ID;
                $content = apply_filters('the_content', $post_->post_content);
                $content = str_replace(']]>', ']]&gt;', $content);
                $ancla = explode("<a ", $content);
                foreach ($ancla as $anc) {
                    if (strpos($anc, '</a>')) {
                        $texto = explode("</a>", $anc);
                        $texto = explode(">", $texto[0]);
                        $primer_texto = $texto[0];
                        $url = str_replace('href=', '', $primer_texto);
                        $url = str_replace('\'', '', $url);
                        $url = str_replace('"', '', $url);
                        $url = str_replace(' ', '%20', $url);
                        //we call the function that checks url                        
                        $estado = filterUrl($url);
                        //insert url with error
                        if ($estado['sta'] != 5) {
                            links_insert($url, $estado['sta'], $origen, $estado['cod']);
                        }
                    }
                }
            }
        }
    }
}

function menu_links_admin()
{
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'link_errors';
    $links_error = $wpdb->get_results("SELECT * FROM $tabla_proyectos");

?>
    <div class="wrap">
        <h1>Link con error</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Estado</th>
                    <th>Origen</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php
                foreach ($links_error as $link_error) {
                    $post = get_post($link_error->origin); 
                    $title = $post->post_name;
                    $url = esc_textarea($link_error->url);
                    $status = esc_textarea($link_error->type); ?>
                    <tr>
                        <td><?php echo $url; ?></td>
                        <td><b style='color:coral;'><?php echo $status; ?></b></td>
                        <td><a href="<?php echo get_permalink($link_error->origin);?>"><?php echo $title; ?></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php
}

function custom_cron($schedules)
{
    $schedules['five_seconds'] = array(
        'interval' => 5,
        'display'  => esc_html__('Every Five Seconds'),
    );
    return $schedules;
}
