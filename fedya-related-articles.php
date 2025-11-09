<?php
/**
 * Plugin Name: Fedya Related Articles
 * Plugin URI: https://urocibg.eu/
 * Description: Красиви свързани статии с миниатюри, cards дизайн и пълен контрол. Автоматично или с shortcode [fedya_related] или [fedya]. Подобрява SEO и задържане на посетители.
 * Version: 3.4
 * Author: fedya Serafiev
 * Author URI: https://urocibg.eu/
 * License: GPL v2 or later
 * Text Domain: fedya-related
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

class fedya_Related_Articles {
    public function __construct() {
        add_action('wp', [$this, 'init']);
        add_filter('the_content', [$this, 'add_related_articles'], 99);
        
        // Двата shortcode-а водят към същата функция
        add_shortcode('fedya_related', [$this, 'shortcode']);
        add_shortcode('fedya', [$this, 'shortcode']); // НОВИЯТ shortcode
        
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        load_plugin_textdomain('fedya-related', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function init() {
        // Няма нужда от enqueue – CSS е вграден в HTML
    }

    public function add_related_articles($content) {
        if (get_option('fedya_related_auto', 'yes') !== 'yes') {
            return $content;
        }

        if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        $limit = $this->get_limit();
        $related = $this->get_related_articles($post_id, $limit);

        if (empty($related)) {
            if (current_user_can('edit_posts')) {
                $content .= $this->debug_message();
            }
            return $content;
        }

        $title = get_option('fedya_related_title', __('Може да ви е интересно и:', 'fedya-related'));
        $content .= $this->render_related($related, $title);

        return $content;
    }

    public function shortcode($atts) {
        // Подобрени атрибути: limit, title, columns
        $atts = shortcode_atts([
            'limit' => $this->get_limit(),
            'title' => get_option('fedya_related_title', __('Може да ви е интересно и:', 'fedya-related')),
            'columns' => '' // auto, 2, 3, 4
        ], $atts);
        
        $post_id = get_the_ID();
        if (!$post_id || !is_singular('post')) return '';

        $related = $this->get_related_articles($post_id, $atts['limit']);
        if (empty($related)) return '';

        return $this->render_related($related, $atts['title'], $atts['columns']);
    }

    private function get_limit() {
        return max(1, min(10, (int) get_option('fedya_related_limit', 3)));
    }

    private function get_related_articles($post_id, $limit = 3) {
        // Кеширане за по-добра производителност
        $cache_key = 'fedya_related_' . $post_id . '_' . $limit;
        $cached = wp_cache_get($cache_key, 'fedya_related');
        
        if ($cached !== false) {
            return $cached;
        }

        $current_tags = wp_get_post_tags($post_id);
        $current_cats = wp_get_post_categories($post_id);

        $tag_ids = wp_list_pluck($current_tags, 'term_id');
        $cat_ids = wp_list_pluck($current_cats, 'term_id');

        $result = [];

        // НИВО 1: Търси по тагове И категории (най-силна връзка)
        if (!empty($tag_ids) && !empty($cat_ids)) {
            $result = $this->query_related($post_id, [
                'relation' => 'AND',
                ['taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tag_ids],
                ['taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cat_ids]
            ], $limit);
        }

        // НИВО 2: Ако не стига, търси само по тагове
        if (count($result) < $limit && !empty($tag_ids)) {
            $needed = $limit - count($result);
            $by_tags = $this->query_related($post_id, [
                ['taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tag_ids]
            ], $needed, $result);
            $result = array_merge($result, $by_tags);
        }

        // НИВО 3: Ако пак не стига, търси само по категории
        if (count($result) < $limit && !empty($cat_ids)) {
            $needed = $limit - count($result);
            $by_cats = $this->query_related($post_id, [
                ['taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cat_ids]
            ], $needed, $result);
            $result = array_merge($result, $by_cats);
        }

        // НИВО 4: Ако още не стига, вземи последни статии (fallback)
        if (count($result) < $limit) {
            $needed = $limit - count($result);
            $recent = $this->query_related($post_id, [], $needed, $result);
            $result = array_merge($result, $recent);
        }

        // Кешираме за 1 час
        wp_cache_set($cache_key, $result, 'fedya_related', 3600);
        
        return array_slice($result, 0, $limit);
    }

    private function query_related($post_id, $tax_query, $limit, $exclude = []) {
        $exclude[] = $post_id; // Винаги изключваме текущата статия
        
        $args = [
            'post_type' => 'post',
            'post__not_in' => $exclude,
            'posts_per_page' => $limit * 2, // Вземаме повече за по-добър избор
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true
        ];

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    private function render_related($posts, $title, $columns = '') {
        // Динамични колони според параметър
        $grid_class = 'fedya-related-grid';
        if ($columns === '2') {
            $grid_class .= ' fedya-cols-2';
        } elseif ($columns === '3') {
            $grid_class .= ' fedya-cols-3';
        } elseif ($columns === '4') {
            $grid_class .= ' fedya-cols-4';
        }

        $css = '
        <style>
        .fedya-related-articles { margin:40px 0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
        .fedya-related-title { margin:0 0 20px; font-size:1.4em; color:#1a1a1a; font-weight:600; padding-bottom:10px; border-bottom:2px solid #0073aa; }
        .fedya-related-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
        .fedya-related-grid.fedya-cols-2 { grid-template-columns:repeat(2,1fr); }
        .fedya-related-grid.fedya-cols-3 { grid-template-columns:repeat(3,1fr); }
        .fedya-related-grid.fedya-cols-4 { grid-template-columns:repeat(4,1fr); }
        .fedya-related-card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); transition:transform .2s,box-shadow .2s; }
        .fedya-related-card:hover { transform:translateY(-4px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
        .fedya-related-link { display:block; text-decoration:none; color:inherit; }
        .fedya-related-thumb { width:100%; height:160px; overflow:hidden; background:#f0f0f0; position:relative; }
        .fedya-related-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
        .fedya-related-card:hover .fedya-related-thumb img { transform:scale(1.05); }
        .fedya-related-thumb.placeholder { display:flex; align-items:center; justify-content:center; color:#999; font-size:0.9em; background:linear-gradient(135deg,#f5f5f5,#e0e0e0); }
        .fedya-related-info { padding:16px; }
        .fedya-related-post-title { margin:0; font-size:1.1em; line-height:1.4; color:#1a1a1a; font-weight:500; }
        .fedya-related-excerpt { margin:8px 0 0; font-size:0.9em; color:#666; line-height:1.5; }
        @media (max-width:768px) { 
            .fedya-related-grid.fedya-cols-3, 
            .fedya-related-grid.fedya-cols-4 { grid-template-columns:repeat(2,1fr); } 
        }
        @media (max-width:600px) { 
            .fedya-related-grid, 
            .fedya-related-grid.fedya-cols-2,
            .fedya-related-grid.fedya-cols-3,
            .fedya-related-grid.fedya-cols-4 { grid-template-columns:1fr; } 
        }
        .fedya-related-debug { background:#fff3cd; color:#856404; padding:12px; border:1px solid #ffe58f; border-radius:6px; font-size:13px; margin:20px 0; }
        </style>';

        $html = $css . '<div class="fedya-related-articles">';
        $html .= '<h3 class="fedya-related-title">' . esc_html($title) . '</h3>';
        $html .= '<div class="' . esc_attr($grid_class) . '">';

        foreach ($posts as $post_id) {
            $post_title = get_the_title($post_id);
            $post_link = get_permalink($post_id);
            $thumbnail = get_the_post_thumbnail($post_id, 'medium', ['alt' => $post_title, 'loading' => 'lazy']);
            
            // Опционален excerpt за по-богата информация
            $show_excerpt = get_option('fedya_related_excerpt', 'no') === 'yes';
            $excerpt = $show_excerpt ? wp_trim_words(get_the_excerpt($post_id), 15) : '';

            $html .= '<div class="fedya-related-card">';
            $html .= '<a href="' . esc_url($post_link) . '" class="fedya-related-link">';

            if ($thumbnail) {
                $html .= '<div class="fedya-related-thumb">' . $thumbnail . '</div>';
            } else {
                $html .= '<div class="fedya-related-thumb placeholder">' . esc_html__('Без изображение', 'fedya-related') . '</div>';
            }

            $html .= '<div class="fedya-related-info">';
            $html .= '<h4 class="fedya-related-post-title">' . esc_html($post_title) . '</h4>';
            
            if ($excerpt) {
                $html .= '<p class="fedya-related-excerpt">' . esc_html($excerpt) . '</p>';
            }
            
            $html .= '</div></a></div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    private function debug_message() {
        return '<div class="fedya-related-debug">' . esc_html__('fedya Related: Няма свързани статии. Добавете общи тагове или категории.', 'fedya-related') . '</div>';
    }

    // === НАСТРОЙКИ ===
    public function add_settings_page() {
        add_options_page(
            __('fedya Related Articles', 'fedya-related'),
            __('Свързани статии', 'fedya-related'),
            'manage_options',
            'fedya-related',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('fedya_related_group', 'fedya_related_limit');
        register_setting('fedya_related_group', 'fedya_related_title');
        register_setting('fedya_related_group', 'fedya_related_auto');
        register_setting('fedya_related_group', 'fedya_related_excerpt');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('fedya Related Articles', 'fedya-related'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('fedya_related_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Заглавие', 'fedya-related'); ?></th>
                        <td><input type="text" name="fedya_related_title" value="<?php echo esc_attr(get_option('fedya_related_title', __('Може да ви е интересно и:', 'fedya-related'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Брой статии', 'fedya-related'); ?></th>
                        <td><input type="number" name="fedya_related_limit" value="<?php echo esc_attr(get_option('fedya_related_limit', 3)); ?>" min="1" max="10" style="width:60px;" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Автоматично показване', 'fedya-related'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="fedya_related_auto" value="yes" <?php checked(get_option('fedya_related_auto', 'yes'), 'yes'); ?> />
                                <?php esc_html_e('Показвай в края на всяка статия', 'fedya-related'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Ако е изключено – използвай [fedya_related] или [fedya] ръчно', 'fedya-related'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Показвай извадка', 'fedya-related'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="fedya_related_excerpt" value="yes" <?php checked(get_option('fedya_related_excerpt', 'no'), 'yes'); ?> />
                                <?php esc_html_e('Показвай кратко описание под заглавието', 'fedya-related'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h3><?php esc_html_e('Shortcode примери', 'fedya-related'); ?></h3>
            <p><code>[fedya]</code> – кратък вариант (НОВО!)</p>
            <p><code>[fedya_related]</code> – стандартен (работи както преди)</p>
            <p><code>[fedya limit="5"]</code> – с 5 статии</p>
            <p><code>[fedya title="Прочети още"]</code> – собствено заглавие</p>
            <p><code>[fedya columns="3"]</code> – точно 3 колони</p>
            <p><code>[fedya limit="6" columns="3"]</code> – 6 статии в 3 колони</p>
        </div>
        <?php
    }
}

new fedya_Related_Articles();
