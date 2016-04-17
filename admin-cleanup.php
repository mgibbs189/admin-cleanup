<?php
/*
Plugin Name: Admin Cleanup
Plugin URI: https://facetwp.com/
Description: Clean up the admin sidebar menu
Version: 0.1
Author: Matt Gibbs
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: admin-cleanup
*/

class Admin_Cleanup
{

    public $menu;
    public $settings;


    function __construct() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }


    function admin_init() {

        if ( is_admin() ) {
            add_action( 'admin_head', array( $this, 'hide_menu_items' ) );
            add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 999 );
        }

        // Save settings
        if ( isset( $_POST['item'] ) && current_user_can( 'manage_options' ) ) {
            update_option( 'admin_cleanup_settings', json_encode( $_POST['item'] ) );
        }

        // Load settings
        $settings = get_option( 'admin_cleanup_settings' );
        if ( ! empty( $settings ) ) {
            $settings = json_decode( $settings, true );
        }
        $this->settings = (array) $settings;
    }


    function admin_menu() {
        add_options_page( 'Admin Cleanup', 'Admin Cleanup', 'manage_options', 'admin-cleanup', array( $this, 'settings_page' ) );
    }


    function parse_menus() {
        global $menu, $submenu;

        $temp_menu = array();
        foreach ( $menu as $key => $data ) {
            $id = $data[2];
            $temp_menu[ $id ] = $data;
            if ( isset( $submenu[ $id ] ) ) {
                $temp_menu[ $id ]['children'] = $submenu[ $id ];
            }
        }

        // Build the final menu
        foreach ( $temp_menu as $id => $data ) {
            if ( isset( $data[5] ) ) {
                $id = $data[5]; // use the CSS ID if available
            }
            $this->menu[ $id ] = $data;
        }
    }


    function admin_bar_menu( $wp_admin_bar ) {
        $this->parse_menus();

        $args = array(
            'id'        => 'admin-cleanup',
            'title'     => 'Menu',
            'parent'    => false,
            'href'      => '',
            'meta'      => array(),
        );
        $wp_admin_bar->add_node( $args );

        foreach ( $this->settings as $key => $val ) {
            if ( 'move' == $val ) {
                $the_menu = $this->menu[ $key ];
                $the_href = menu_page_url( $the_menu[2], false );
                $the_href = empty( $the_href ) ? $the_menu[2] : $the_href;
                $the_id = /*'ac-' . */$key;

                $args = array(
                    'id'        => $the_id,
                    'title'     => $the_menu[0],
                    'parent'    => 'admin-cleanup',
                    'href'      => $the_href,
                );
                $wp_admin_bar->add_node( $args );

                if ( isset( $the_menu['children'] ) ) {
                    foreach ( $the_menu['children'] as $key => $child ) {
                        $the_href = menu_page_url( $child[2], false );
                        $the_href = empty( $the_href ) ? $child[2] : $the_href;

                        $args = array(
                            'id'        => $the_id . '-' . $key,
                            'title'     => $child[0],
                            'parent'    => $the_id,
                            'href'      => $the_href,
                        );
                        $wp_admin_bar->add_node( $args );
                    }
                }
            }
        }
    }


    function hide_menu_items() {
?>
    <script>
    (function($) {
        $(function() {
            var settings = <?php echo json_encode( $this->settings ); ?>;
            $.each(settings, function(key, val) {
                if ('move' == val || 'hide' == val) {
                    if ('separator' == key.substr(0, 9)) {
                        var num = parseInt(key.substr(9)) - 1;
                        $('#adminmenu li.wp-menu-separator:eq(' + num + ')').addClass('hidden');
                    }
                    else {
                        $('#adminmenu li#' + key).addClass('hidden');
                    }
                }
            });
        });
    })(jQuery);
    </script>
<?php
    }


    function settings_page() {
        global $menu;
?>
    <style>
    .wrap td, .wrap th { text-align: left; }
    </style>

    <div class="wrap">
        <h1>Admin Cleanup</h1>

        <form method="post" action="">
        <table>
            <tr>
                <th style="width:50px">Show</th>
                <th style="width:50px">Move</th>
                <th style="width:50px">Hide</th>
                <th><!-- Menu --></td>
            </tr>
<?php
foreach ( $menu as $data ) {
    if ( 'wp-menu-separator' == $data[4] ) {
        $data[0] = '---';
        $data[5] = $data[2];
    }

    echo '<tr>';

    $choices = array( 'show', 'move', 'hide' );
    foreach ( $choices as $choice ) {
        $the_val = isset( $this->settings[ $data[5] ] ) ?
            $this->settings[ $data[5] ] : 'show';

        $checked = ( $choice == $the_val ) ? ' checked' : '';
        echo '<td><input type="radio" name="item[' . $data[5] . ']" value="' . $choice . '"' . $checked . ' /></td>';
    }

    echo '<td>' . $data[0] . '</td>';
    echo '</tr>';
}
?>
            </table>
            <p><input type="submit" class="button-primary" value="Save Changes" /></p>
        </form>
    </div>
<?php
    }
}

new Admin_Cleanup();