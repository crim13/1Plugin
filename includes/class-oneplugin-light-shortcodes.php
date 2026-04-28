<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Shortcodes {
    private static $instance = null;
    private $get_setting;

    public static function instance($get_setting = null) {
        if (self::$instance === null) {
            self::$instance = new self($get_setting);
        }

        return self::$instance;
    }

    private function __construct($get_setting) {
        $this->get_setting = is_callable($get_setting) ? $get_setting : static function ($key, $default = '') {
            return $default;
        };
    }

    private function get_setting($key, $default = '') {
        return call_user_func($this->get_setting, $key, $default);
    }

    public function init() {
        add_action('init', [$this, 'register_shortcodes']);
    }

    public function register_shortcodes() {
        $value_shortcodes = [
            'foretag' => 'company_name',
            'gata' => 'street_address',
            'postkod' => 'postal_code',
            'ort' => 'city',
            'mobil1' => 'phone_primary',
            'orgnr' => 'organization_number',
            'mail' => 'email',
        ];

        foreach ($value_shortcodes as $shortcode => $setting_key) {
            add_shortcode($shortcode, function () use ($setting_key) {
                return esc_html($this->get_setting($setting_key));
            });
        }

        add_shortcode('kontakt', [$this, 'shortcode_contact']);
        add_shortcode('formular', [$this, 'shortcode_form']);
        add_shortcode('kundens_mail', [$this, 'shortcode_customer_mail']);
        add_shortcode('kundens_epost', [$this, 'shortcode_form_email']);
        add_shortcode('kundens_foretag', [$this, 'shortcode_company']);
        add_shortcode('kundens_adress', [$this, 'shortcode_address']);
        add_shortcode('kundens_telefon', [$this, 'shortcode_phone']);
        add_shortcode('karta', [$this, 'shortcode_map']);
        add_shortcode('hemsida', [$this, 'shortcode_website_button']);
        add_shortcode('kundens_hemsida', [$this, 'shortcode_website_link']);
        add_shortcode('kundens_facebook', [$this, 'shortcode_social_facebook']);
        add_shortcode('kundens_instagram', [$this, 'shortcode_social_instagram']);
        add_shortcode('kundens_linkedin', [$this, 'shortcode_social_linkedin']);
        add_shortcode('kundens_youtube', [$this, 'shortcode_social_youtube']);
        add_shortcode('kundens_x', [$this, 'shortcode_social_x']);
        add_shortcode('kundens_reddit', [$this, 'shortcode_social_reddit']);
        add_shortcode('kundens_bokadirekt', [$this, 'shortcode_social_booking']);
    }

    public function shortcode_contact() {
        $company = $this->get_setting('company_name');
        $address = $this->get_setting('street_address');
        $postal_code = $this->get_setting('postal_code');
        $city = $this->get_setting('city');
        $phone = $this->get_setting('phone_primary');
        $email = $this->get_setting('email');

        ob_start();
        ?>
        <div id="sidebar-kontakt">
            <?php if ($company) : ?>
                <h3 class="widget-title"><?php echo esc_html($company); ?></h3>
            <?php endif; ?>

            <?php if ($address || $postal_code || $city) : ?>
                <div class="top-col">
                    <i class="map black" aria-hidden="true"></i>
                    <div>
                        <?php echo esc_html($address); ?><br />
                        <?php echo esc_html(trim($postal_code . ' ' . $city)); ?><br />
                        <?php echo esc_html__('Sverige', 'oneplugin-light-site-tools'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($phone) : ?>
                <div class="top-col">
                    <i class="phone black" aria-hidden="true"></i>
                    <div><a href="<?php echo esc_url('tel:' . preg_replace('/\s+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></div>
                </div>
            <?php endif; ?>

            <?php if ($email) : ?>
                <div class="top-col">
                    <i class="mail black" aria-hidden="true"></i>
                    <div><?php echo esc_html($email); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return trim(ob_get_clean());
    }

    public function shortcode_form($atts) {
        $atts = shortcode_atts(['id' => ''], $atts, 'formular');
        $email = $this->get_setting('email');

        if (empty($email) || empty($atts['id'])) {
            return '';
        }

        return do_shortcode('[formidable id="' . sanitize_text_field($atts['id']) . '"]');
    }

    public function shortcode_customer_mail() {
        $email = $this->get_setting('email');
        if (!$email) {
            return '';
        }

        return '<a href="' . esc_url('mailto:' . $email) . '">' . esc_html($email) . '</a>';
    }

    public function shortcode_form_email() {
        return esc_html($this->get_setting('form_email'));
    }

    public function shortcode_company() {
        return esc_html($this->get_setting('company_name'));
    }

    public function shortcode_address() {
        $address = $this->get_setting('street_address');
        $postal_code = $this->get_setting('postal_code');
        $city = $this->get_setting('city');
        $full_address = trim($address . ', ' . trim($postal_code . ' ' . $city), ' ,');

        return esc_html($full_address);
    }

    public function shortcode_phone() {
        return esc_html($this->get_setting('phone_primary'));
    }

    public function shortcode_map() {
        $address = $this->get_setting('street_address');
        $postal_code = $this->get_setting('postal_code');
        $city = $this->get_setting('city');

        if (!$address || !$postal_code || !$city) {
            return '';
        }

        $query = urlencode($address . ', ' . $postal_code . ' ' . $city . ', Sverige');

        return '<iframe width="100%" height="400" src="https://maps.google.com/maps?q=' . esc_attr($query) . '&t=&z=13&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" loading="lazy"></iframe>';
    }

    public function shortcode_website_button() {
        $website = $this->get_setting('website');
        if (!$website) {
            return '';
        }

        return '<a class="besok-hemsidan" href="' . esc_url($website) . '" target="_blank" rel="noopener">' . esc_html__('Besok hemsidan', 'oneplugin-light-site-tools') . '</a>';
    }

    public function shortcode_website_link() {
        $website = $this->get_setting('website');
        if (!$website) {
            return '';
        }

        return '<a href="' . esc_url($website) . '" target="_blank" rel="noopener">' . esc_html($website) . '</a>';
    }

    public function shortcode_social_facebook() {
        return $this->render_social_shortcode_link('facebook_url', 'Facebook');
    }

    public function shortcode_social_instagram() {
        return $this->render_social_shortcode_link('instagram_url', 'Instagram');
    }

    public function shortcode_social_linkedin() {
        return $this->render_social_shortcode_link('linkedin_url', 'LinkedIn');
    }

    public function shortcode_social_youtube() {
        return $this->render_social_shortcode_link('youtube_url', 'YouTube');
    }

    public function shortcode_social_x() {
        return $this->render_social_shortcode_link('x_url', 'X');
    }

    public function shortcode_social_reddit() {
        return $this->render_social_shortcode_link('reddit_url', 'Reddit');
    }

    public function shortcode_social_booking() {
        return $this->render_social_shortcode_link('booking_url', 'BokaDirekt');
    }

    private function render_social_shortcode_link($setting_key, $label) {
        $url = $this->get_setting($setting_key);
        if (!$url) {
            return '';
        }

        return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>';
    }
}

