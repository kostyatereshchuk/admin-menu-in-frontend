<?php
/**
 * Plugin Name: Admin Menu in Frontend
 * Description: Allows to show admin menu when viewing site.
 * Version: 1.1.1
 * Author: Kostya Tereshchuk
 * Author URI: https://wordpress.org/support/users/kostyatereshchuk/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: admin-menu-in-frontend
*/


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * @class Admin_Menu_In_Frontend
 */

class Admin_Menu_In_Frontend {

    /**
     * Single instance of the class.
     *
     * @var Admin_Menu_In_Frontend
     */
    protected static $_instance = null;

    /**
     * Admin_Menu_In_Frontend instance.
     *
     * @static
     * @return Admin_Menu_In_Frontend - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Admin_Menu_In_Frontend Constructor.
     */
    public function __construct() {
        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'plugin_actions' ), 10 );

        add_action( 'wp_head', array( $this, 'set_html_classes' ), 1 );

        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 1000 );

        add_action( 'admin_footer', array($this, 'admin_footer_scripts'), 10 );

        add_action( 'wp_ajax_amf_save_admin_menu_html', array( $this, 'save_admin_menu_html' ), 10 );

        add_action( 'wp_ajax_amf_save_collapse_admin_menu', array( $this, 'save_collapse_admin_menu' ), 10 );
        add_action( 'wp_ajax_nopriv_amf_save_collapse_admin_menu', array( $this, 'save_collapse_admin_menu' ), 10 );

        add_action( 'wp_ajax_amf_save_fixate_admin_menu', array( $this, 'save_fixate_admin_menu' ), 10 );
        add_action( 'wp_ajax_nopriv_amf_save_fixate_admin_menu', array( $this, 'save_fixate_admin_menu' ), 10 );

        add_action( 'wp_footer', array( $this, 'show_admin_menu_in_frontend' ), 10 );

        add_action( 'show_user_profile', array( $this, 'add_options_to_profile' ), 10 );
        add_action( 'edit_user_profile', array( $this, 'add_options_to_profile' ), 10 );
        add_action( 'personal_options_update', array( $this, 'save_profile_options' ), 10 );
        add_action( 'edit_user_profile_update', array( $this, 'save_profile_options' ), 10 );

        add_action( 'admin_bar_menu', array( $this, 'close_admin_panel' ), 0 );
    }

    /**
     * Add plugin actions links.
     * @param array $actions
     * @return array
     */
    public function plugin_actions( $actions ) {
        array_unshift($actions, '<a href="'.admin_url( 'profile.php' ).'#admin_bar_front">'.esc_html__("Your profile").'</a>');

        return $actions;
    }

    /**
     * Set html classes using script in head tag.
     */
    public function set_html_classes() {
        $user_id = get_current_user_id();
        if (!is_admin() && $user_id) {
            if ($show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1)) {
                if ($admin_menu_html = get_user_meta($user_id, '_amf_admin_menu_html', 1)) {
                    $html_classes = '';
                    if ($collapse_admin_menu = get_user_meta($user_id, '_amf_collapse_admin_menu', 1)) {
                        $html_classes .= ' folded';
                    }
                    $fixate_admin_menu = get_user_meta($user_id, '_amf_fixate_admin_menu');
                    $fixate_admin_menu = isset($fixate_admin_menu[0]) ? $fixate_admin_menu[0] : 1;
                    if ($fixate_admin_menu) {
                        $html_classes .= ' fixate-admin-menu';
                    }
                    if (is_rtl()) {
                        $html_classes .= ' amf-rtl';
                    }
                    if ($html_classes) {
                        ?>
                        <script>
                            var amf_html = document.getElementsByTagName( 'html' )[0];
                            amf_html.className += '<?php echo $html_classes ?>';
                        </script>
                        <?php
                    }
                }
            }
        }
    }

    /**
     * Load Scripts.
     */
    public function wp_enqueue_scripts() {
        $user_id = get_current_user_id();
        if (!is_admin() && $user_id)  {
            if ($show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1)) {
                if ($admin_menu_html = get_user_meta( $user_id, '_amf_admin_menu_html', 1 )) {
                    wp_enqueue_style('admin-menu-in-frontend', plugins_url('assets/css/admin-menu-in-frontend.css', __FILE__));

                    wp_register_script('admin-menu-in-frontend', plugins_url('assets/js/admin-menu-in-frontend.js', __FILE__), array('jquery'));
                    wp_localize_script('admin-menu-in-frontend', 'admin_menu_vars', array(
                        'admin_url' => admin_url('/'),
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'collapse_nonce' => wp_create_nonce( "collapse-admin-menu" ),
                        'fixate_nonce' => wp_create_nonce( "fixate-admin-menu" ),
                        'folded' => get_user_meta($user_id, '_amf_collapse_admin_menu', 1),
                        'fixate_admin_menu' => get_user_meta($user_id, '_amf_fixate_admin_menu', 1)
                    ));
                    wp_enqueue_script('admin-menu-in-frontend');
                }
            }
        }
    }

    /**
     * Scripts to send admin menu HTML via AJAX and to set close admin bar url.
     */
    public function admin_footer_scripts() {

        $user_id = get_current_user_id();
        if (is_admin() && $user_id) {
            if ($show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1)) {
                $admin_menu_html = (get_user_meta( $user_id, '_amf_admin_menu_html', 1 ));

                ?>
                <div class="amf-prev-admin-menu" style="display: none"><?php echo $admin_menu_html ?></div>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {

                        // Send admin menu HTML via AJAX
                        $(window).load(function () {

                            var prev_admin_menu = $(".amf-prev-admin-menu");
                            var prev_admin_menu_compare_text = prev_admin_menu.text().replace(/\s/g,'');
                            prev_admin_menu.remove();

                            var admin_menu = $('#adminmenuwrap');
                            var admin_menu_compare_text = admin_menu.text().replace(/\s/g,'');

                            if (admin_menu_compare_text != prev_admin_menu_compare_text) {
                                var admin_menu_html = admin_menu.html();

                                var data = {
                                    action: 'amf_save_admin_menu_html',
                                    security: '<?php echo wp_create_nonce("admin-menu-html") ?>',
                                    user_id: <?php echo get_current_user_id() ?>,
                                    admin_menu_html: admin_menu_html
                                };
                                $.post(ajaxurl, data, function (response) {
                                    //console.log(response);
                                });
                            }

                        });


                        // Set close admin bar url.
                        function get_cookie(name) {
                            name += "=";
                            var decodedCookie = decodeURIComponent(document.cookie);
                            var ca = decodedCookie.split(';');
                            for(var i = 0; i <ca.length; i++) {
                                var c = ca[i];
                                while (c.charAt(0) == ' ') {
                                    c = c.substring(1);
                                }
                                if (c.indexOf(name) == 0) {
                                    return c.substring(name.length, c.length);
                                }
                            }
                            return "";
                        }
                        var recent_page_url = get_cookie('amf_recent_page_url');
                        if (recent_page_url) {
                            $('#wp-admin-bar-amf-close-admin-panel a').attr('href', recent_page_url);
                        }

                    });
                </script>
                <?php
            }
        }
    }

    /**
     * Save admin menu HTML in user meta "_amf_admin_menu_html"
     */
    public function save_admin_menu_html() {

        check_ajax_referer( 'admin-menu-html', 'security' );

        $admin_menu_html = isset( $_POST['admin_menu_html'] ) ? wp_filter_post_kses($_POST['admin_menu_html']) : '';
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ($admin_menu_html && $user_id && $user_id == get_current_user_id()) {
            $admin_menu_html = str_replace('wp-has-current-submenu', 'wp-not-current-submenu', $admin_menu_html);
            $admin_menu_html = str_replace('wp-menu-open', 'menu-top-first', $admin_menu_html);
            update_user_meta($user_id, '_amf_admin_menu_html', $admin_menu_html);
            echo 'updated "_amf_admin_menu_html"';
        }

        wp_die();
    }

    /**
     * Save admin menu collapse option "_amf_collapse_admin_menu"
     */
    public function save_collapse_admin_menu() {
        check_ajax_referer( 'collapse-admin-menu', 'security' );

        $user_id = get_current_user_id();

        if (isset($_POST['collapse_admin_menu'])) {
            $collapse_admin_menu = intval($_POST['collapse_admin_menu']) ? '1' : '';
            update_user_meta($user_id, '_amf_collapse_admin_menu', $collapse_admin_menu);
        }

        wp_die();
    }

    /**
     * Save admin menu fixate option "_amf_fixate_admin_menu"
     */
    public function save_fixate_admin_menu() {
        check_ajax_referer( 'fixate-admin-menu', 'security' );

        $user_id = get_current_user_id();

        if (isset($_POST['fixate_admin_menu'])) {
            $fixate_admin_menu = intval($_POST['fixate_admin_menu']) ? '1' : '';
            update_user_meta($user_id, '_amf_fixate_admin_menu', $fixate_admin_menu);
        }

        wp_die();
    }

    /**
     * Show admin menu html in frontend
     */
    public function show_admin_menu_in_frontend() {
        $user_id = get_current_user_id();
        if (!is_admin() && $user_id) {
            if ($show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1)) {
                if ($admin_menu_html = get_user_meta( $user_id, '_amf_admin_menu_html', 1 )) {
                    $admin_menu_html = '<div id="adminmenumain" class="admin-menu-in-frontend amf-hidden" style="display: none"><div id="adminmenuwrap">'.$admin_menu_html.'</div></div>';
                    echo $admin_menu_html;

                }
            }
        }
    }

    /**
     * Add options to "Edit User" page
     * @param object $user
     */
    public function add_options_to_profile( $user ) {

        $user_id = $user->ID;

        $show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1) ? 1 : 0;

        ?>

        <table class="form-table" style="display: none">
            <tr class="show-admin-menu user-admin-menu-front-wrap">
                <th scope="row">Admin Menu</th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span>Admin Menu</span></legend>
                        <label for="amf_show_admin_menu">
                            <input name="amf_show_admin_menu" type="checkbox" id="amf_show_admin_menu" value="1"<?php echo $show_admin_menu ? ' checked="checked"' : '' ?>>
                            Show Admin Menu when viewing site
                        </label><br>
                    </fieldset>
                </td>
            </tr>
        </table>
        <script>
            jQuery(function($) {
                $('.show-admin-menu').insertAfter('.show-admin-bar');
                $('#amf_show_admin_menu').change(function() {
                    if ($(this).attr('checked')) {
                        $('#admin_bar_front').attr('checked', true);
                    }
                });
                $('#admin_bar_front').change(function() {
                    if (!$(this).attr('checked')) {
                        $('#amf_show_admin_menu').attr('checked', false);
                    }
                });
            });
        </script>

        <?php
    }

    /**
     * Save profile options
     * @param int $user_id
     * @return bool
     */
    function save_profile_options( $user_id ) {

        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        $show_admin_menu = '';
        if (isset($_POST['amf_show_admin_menu'])) {
            $show_admin_menu = intval($_POST['amf_show_admin_menu']) ? 1 : '';
        }
        update_user_meta( $user_id, '_amf_show_admin_menu', $show_admin_menu );

        return true;
    }

    /**
     * Close admin panel button on the toolbar
     * @param Wp_Admin_Bar $wp_admin_bar
     */
    function close_admin_panel($wp_admin_bar) {
        $user_id = get_current_user_id();
        if (is_admin() && $user_id) {
            if ($show_admin_menu = get_user_meta($user_id, '_amf_show_admin_menu', 1)) {
                if ($admin_menu_html = get_user_meta( $user_id, '_amf_admin_menu_html', 1 )) {
                    $wp_admin_bar->add_menu(
                        array(
                            'id'     => 'amf-close-admin-panel',
                            'title'  => '<span class="dashicons-before dashicons-no" style="display: inline-block; margin-top: 5px; margin-bottom: -5px"></span>',
                            'parent' => 'top-secondary',
                            'href'   => site_url(),
                            'group'  => false,
                            'meta'  => array(
                                'title' => __('Close the admin panel'),
                            ),
                        )
                    );
                }
            }
        }
    }

}

Admin_Menu_In_Frontend::instance();



