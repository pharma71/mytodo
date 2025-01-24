<?php
/**
 * Plugin Name: MY Todo
 * Description: Ein Plugin zur Verwaltung von Todos.
 * Version: 1.0
 * Author: Kristian Knorr
 */

// Sicherheitscheck
if (!defined('ABSPATH')) {
    exit;
}

define( 'MYTODO__VERSION', '1.0.0' );
define( 'MYTODO__MINIMUM_WP_VERSION', '6.0' );
define( 'MYTODO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYTODO__RESSOURCES_DIR', plugin_dir_url( __FILE__ ) . 'assets/');
define( 'MYTODO__IMAGES_DIR', plugin_dir_url( __FILE__ ) . 'assets/img/');
define( 'MYTODO__PLUGIN_URL', plugins_url() . 'mytodo');


// Plugin-Aktivierung
register_activation_hook(__FILE__, 'my_todo_create_table');
function my_todo_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_todo';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id MEDIUMINT NOT NULL AUTO_INCREMENT,
        titel VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        prioritaet ENUM('Hoch', 'Mittel', 'Niedrig') NOT NULL DEFAULT 'Mittel',
        status ENUM('Offen', 'Erledigt', 'Blockiert') NOT NULL DEFAULT 'Offen',
        datum DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Plugin-Deaktivierung
register_deactivation_hook(__FILE__, 'my_todo_remove_table');
function my_todo_remove_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_todo';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Plugin-Deinstallation
register_uninstall_hook(__FILE__, 'my_todo_uninstall');
function my_todo_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_todo';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Admin-Menü hinzufügen
add_action('admin_menu', 'my_todo_menu');
function my_todo_menu() {
    add_menu_page('MY Todo', 'MY Todo', 'manage_options', 'my-todo', 'my_todo_page', 'dashicons-list-view');
}

// Admin-Seite rendern
function my_todo_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_todo';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['new_todo'])) {
            $wpdb->insert($table_name, [
                'titel' => sanitize_text_field($_POST['titel']),
                'content' => sanitize_textarea_field($_POST['content']),
                'prioritaet' => sanitize_text_field($_POST['prioritaet']),
                'status' => sanitize_text_field($_POST['status'])
            ]);
        } elseif (isset($_POST['delete_todo'])) {
            $wpdb->delete($table_name, ['id' => intval($_POST['todo_id'])]);
        } elseif (isset($_POST['update_status'])) {
            $wpdb->update(
                $table_name,
                ['status' => sanitize_text_field($_POST['status'])],
                ['id' => intval($_POST['todo_id'])]
            );
        }
    }

    $todos = $wpdb->get_results("SELECT * FROM $table_name ORDER BY datum DESC");
    
    echo '  <h2>MY Todo Plugin</h2>
            <div class="wrap">
            <form method="post" class="mb-4 p-4 border rounded bg-light">
                <div class="form-group">
                    <label for="titel">Titel</label>
                    <input type="text" class="form-control" id="titel" name="titel" placeholder="Todo-Titel eingeben" required>
                </div>
                <div class="form-group">
                    <label for="content">Inhalt</label>
                    <textarea class="form-control" id="content" name="content" placeholder="Details zum Todo" required></textarea>
                </div>
                <div class="form-group">
                    <label for="prioritaet">Priorität</label>
                    <select class="form-control" id="prioritaet" name="prioritaet">
                        <option value="Hoch">Hoch</option>
                        <option value="Mittel" selected>Mittel</option>
                        <option value="Niedrig">Niedrig</option>
                    </select>
                </div>
                <button type="submit" name="new_todo" class="btn btn-primary">Hinzufügen</button>
                <button type="button" class="btn btn-success" onclick="location.reload();">Refresh</button>
            </form>


            <h3>Todos</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Inhalt</th>
                        <th>Priorität</th>
                        <th>Status</th>
                        <th>Datum</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($todos as $todo) {
        $row_class = '';
        if ($todo->prioritaet === 'Hoch') {
            $row_class = 'table-danger';
        } elseif ($todo->prioritaet === 'Mittel') {
            $row_class = 'table-warning';
        } elseif ($todo->prioritaet === 'Niedrig') {
            $row_class = 'table-success';
        }
         echo '<tr class="' . esc_attr($row_class) . '">
                <td>' . esc_html($todo->titel) . '</td>
                <td>' . esc_html($todo->content) . '</td>
                <td>' . esc_html($todo->prioritaet) . '</td>
                 <td>
                    <form method="post" style="display:inline; id="my_todo_status"">
                        <input type="hidden" name="todo_id" value="' . intval($todo->id) . '">
                        <input type="hidden" name="update_status" value="true">
                        <select name="status" class="form-control" style="width:auto; display:inline;" onchange="this.form.submit()">
                            <option value="Offen"' . selected($todo->status, 'Offen', false) . '>Offen</option>
                            <option value="Erledigt"' . selected($todo->status, 'Erledigt', false) . '>Erledigt</option>
                            <option value="Blockiert"' . selected($todo->status, 'Blockiert', false) . '>Blockiert</option>
                        </select>
                    </form>
                </td>
                <td>' . esc_html($todo->datum) . '</td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="todo_id" value="' . intval($todo->id) . '">
                        <button type="submit" name="delete_todo" class="btn btn-danger btn-sm">Löschen</button>
                    </form>               
                </td>
            </tr>';
    }

    echo '    </tbody>
            </table>
          </div>';

    // Bootstrap einbinden
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>';

}

add_action( 'wp_enqueue_scripts', 'my_todo_enqueue_scripts' );
function my_todo_enqueue_scripts() {
   //Make sure it is not loaded on every site
    wp_enqueue_script(
        'mytodo-script', //handle
        MYTODO__RESSOURCES_DIR . '/js/mytodo.js' // JS URL
    );
}

// Shortcode für das Frontend-Formular
add_shortcode('my_todo_form', 'my_todo_frontend_form');
function my_todo_frontend_form() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_todo';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['frontend_new_todo'])) {
        $wpdb->insert($table_name, [
            'titel' => sanitize_text_field($_POST['titel']),
            'content' => sanitize_textarea_field($_POST['content']),
            'prioritaet' => sanitize_text_field($_POST['prioritaet'])
        ]);
    }

    $output = '<img src="'. MYTODO__IMAGES_DIR . "blumenwiese.jpg".'" alt="Blumenwiese" style="display: block; margin: 0 auto; width: 100%; height: 100px; object-fit: cover; margin-bottom: 15px;">
        <div id="mytodo_frontend_form">    
            <h2>Möchtest du dir ein paar Todos merken?</h2>
            <form method="post" class="mb-4 p-4 border rounded bg-light">
                <div class="form-group">
                    <label for="frontend_titel">Titel</label>
                    <input type="text" class="form-control" id="frontend_titel" name="titel" placeholder="Todo-Titel eingeben" required>
                </div>
                <div class="form-group">
                    <label for="frontend_content">Inhalt</label>
                    <textarea class="form-control" id="frontend_content" name="content" placeholder="Details zum Todo" required></textarea>
                </div>
                <div class="form-group">
                    <label for="frontend_prioritaet">Priorität</label>
                    <select class="form-control" id="frontend_prioritaet" name="prioritaet">
                        <option value="Hoch">Hoch</option>
                        <option value="Mittel" selected>Mittel</option>
                        <option value="Niedrig">Niedrig</option>
                    </select>
                </div>
                <button type="submit" name="frontend_new_todo" class="btn btn-primary">Hinzufügen</button>
            </form>
        </div>    
        ';

    // Bootstrap einbinden
    $output .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>';

    return $output;
}

// Filter nur auf Seiten mit dem Shortcode aktivieren
add_filter('the_content', 'my_todo_content_filter');
function my_todo_content_filter($content) {
    if (has_shortcode($content, 'my_todo_form')) {
        $original_content = $content; // preserve the original ...
        $add_before_content = ''; // This will be added before the content.. 
        $add_after_content = '<div>Thank you for using awesome MY Todo Plugin</div>'; // This will be added after the content.. 
        $content = $add_before_content . $original_content . $add_after_content;
    }

    // Returns the content.
    return $content;
}