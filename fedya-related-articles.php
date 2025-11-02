<?php 
 /**
 * Plugin Name: Fedya Related Articles
 * Plugin URI: https://github.com/fantomas4o/gfedya-related-articles
 * Description: Beautiful related posts with thumbnails and cards. Auto or manual via [fedya_related]. Boost SEO!
 * Version: 3.1
 * Author: Fedya Serafiev
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
        add_shortcode('fedya_related', [$this, 'shortcode']);
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
        $atts = shortcode_atts(['limit' => $this->get_limit()], $atts);
        $post_id = get_the_ID();
        if (!$post_id || !is_singular('post')) return '';

        $related = $this->get_related_articles($post_id, $atts['limit']);
        if (empty($related)) return '';

        $title = get_option('fedya_related_title', __('Може да ви е интересно и:', 'fedya-related'));
        return $this->render_related($related, $title);
    }

    private function get_limit() {
        return max(1, min(10, (int) get_option('fedya_related_limit', 3)));
    }

    private function get_related_articles($post_id, $limit = 3) {
        $current_tags = wp_get_post_tags($post_id);
        $current_cats = wp_get_post_categories($post_id);

        $tag_ids = wp_list_pluck($current_tags, 'term_id');
        $cat_ids = wp_list_pluck($current_cats, 'term_id');

        $tax_query = ['relation' => 'OR'];
        if (!empty($tag_ids)) {
            $tax_query[] = ['taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tag_ids];
        }
        if (!empty($cat_ids)) {
            $tax_query[] = ['taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cat_ids];
        }

        if (count($tax_query) === 1) return [];

        $args = [
            'post_type' => 'post',
            'post__not_in' => [$post_id],
            'posts_per_page' => $limit + 5,
            'tax_query' => $tax_query,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $query = new WP_Query($args);
        return array_slice($query->posts, 0, $limit);
    }

    private function render_related($posts, $title) {
        $css = '
        <style>
        .fedya-related-articles { margin:40px 0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
        .fedya-related-title { margin:0 0 20px; font-size:1.4em; color:#1a1a1a; font-weight:600; padding-bottom:10px; border-bottom:2px solid #0073aa; }
        .fedya-related-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
        .fedya-related-card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); transition:transform .2s,box-shadow .2s; }
        .fedya-related-card:hover { transform:translateY(-4px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
        .fedya-related-link { display:block; text-decoration:none; color:inherit; }
        .fedya-related-thumb { width:100%; height:160px; overflow:hidden; background:#f0f0f0; }
        .fedya-related-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
        .fedya-related-card:hover .fedya-related-thumb img { transform:scale(1.05); }
        .fedya-related-thumb.placeholder { display:flex; align-items:center; justify-content:center; color:#999; font-size:0.9em; background:linear-gradient(135deg,#f5f5f5,#e0e0e0); }
        .fedya-related-info { padding:16px; }
        .fedya-related-post-title { margin:0; font-size:1.1em; line-height:1.4; color:#1a1a1a; font-weight:500; }
        @media (max-width:600px) { .fedya-related-grid { grid-template-columns:1fr; } }
        .fedya-related-debug { background:#fff3cd; color:#856404; padding:12px; border:1px solid #ffe58f; border-radius:6px; font-size:13px; margin:20px 0; }
        </style>';

        $html = $css . '<div class="fedya-related-articles">';
        $html .= '<h3 class="fedya-related-title">' . esc_html($title) . '</h3>';
        $html .= '<div class="fedya-related-grid">';

        foreach ($posts as $post_id) {
            $post_title = get_the_title($post_id);
            $post_link = get_permalink($post_id);
            $thumbnail = get_the_post_thumbnail($post_id, 'medium', ['alt' => $post_title, 'loading' => 'lazy']);

            $html .= '<div class="fedya-related-card">';
            $html .= '<a href="' . esc_url($post_link) . '" class="fedya-related-link">';

            if ($thumbnail) {
                $html .= '<div class="fedya-related-thumb">' . $thumbnail . '</div>';
            } else {
                $html .= '<div class="fedya-related-thumb placeholder">' . esc_html__('Без изображение', 'fedya-related') . '</div>';
            }

            $html .= '<div class="fedya-related-info">';
            $html .= '<h4 class="fedya-related-post-title">' . esc_html($post_title) . '</h4>';
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
                            <p class="description"><?php esc_html_e('Ако е изключено – използвай [fedya_related] ръчно', 'fedya-related'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h3><?php esc_html_e('Shortcode', 'fedya-related'); ?></h3>
            <p><code>[fedya_related]</code> – стандартно</p>
            <p><code>[fedya_related limit="5"]</code> – с 5 статии</p>
        </div>
        <?php
    }
}

new fedya_Related_Articles();
