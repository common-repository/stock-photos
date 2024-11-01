<?php

$stockphotos_gallery_languages = array('Dansk', 'de' => 'Deutsch', 'en' => 'English', 'es' => 'Español');

add_action('admin_menu', 'stockphotos_add_settings_menu');

function stockphotos_add_settings_menu() {
    add_options_page(__('Stock Photos Settings', 'stockphotos'), __('Stock Photos', 'stockphotos'), 'manage_options', 'stockphotos_settings', 'stockphotos_settings_page');
    add_action('admin_init', 'register_stockphotos_options');
}

function register_stockphotos_options(){
    register_setting('stockphotos_options', 'stockphotos_options', 'stockphotos_options_validate');
    add_settings_section('stockphotos_options_section', '', '', 'stockphotos_settings');
    add_settings_field('language-id', __('Language', 'stockphotos'), 'stockphotos_render_language', 'stockphotos_settings', 'stockphotos_options_section');
    add_settings_field('per_page-id', __('Images Per Page', 'stockphotos'), 'stockphotos_render_per_page', 'stockphotos_settings', 'stockphotos_options_section');
}

function stockphotos_render_language(){
    global $stockphotos_gallery_languages;
    $options = get_option('stockphotos_options');
    $set_lang = substr(get_locale(), 0, 2);

	if (!$options['language']) $options['language'] = $stockphotos_gallery_languages[$set_lang]?$set_lang:'en';
     echo '<select name="stockphotos_options[language]">';
            foreach ($stockphotos_gallery_languages as $k => $v) { echo '<option value="'.$k.'"'.($options['language']==$k?' selected="selected"':'').'>'.$v.'</option>'; }
                echo '</select>';
}

function stockphotos_render_per_page(){
    $options = get_option('stockphotos_options');
    echo '<input name="stockphotos_options[per_page]" type="number" min="10" max="100" value="'.($options['per_page']?$options['per_page']:30).'">';
}

function stockphotos_settings_page() { ?>
    <div class="wrap">
    <h2><?= _e('Stock Photos', 'stockphotos'); ?></h2>
    <form method="post" action="options.php">
        <?php
            settings_fields('stockphotos_options');
            do_settings_sections('stockphotos_settings');
            submit_button();
        ?>
    </form>
    </div>

<?php }

function stockphotos_options_validate($input){
    global $stockphotos_gallery_languages;
    $options = get_option('stockphotos_options');
    if ($stockphotos_gallery_languages[$input['language']]) $options['language'] = $input['language'];
    $per_page = intval($input['per_page']);
    if ($per_page >= 10 and $per_page <= 100) $options['per_page'] = $per_page;

    return $options;
}

?>
