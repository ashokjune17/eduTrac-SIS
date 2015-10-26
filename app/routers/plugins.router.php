<?php
if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
/**
 * Plugins Router
 *  
 * @license GPLv3
 * 
 * @since       5.0.0
 * @package     eduTrac SIS
 * @author      Joshua Parker <joshmac3@icloud.com>
 */
/**
 * Before route check.
 */
$app->before('GET|POST', '/plugins.*', function() {
    if (!hasPermission('access_plugin_screen')) {
        redirect(get_base_url() . 'dashboard' . DS);
    }
});

$css = [ 'css/admin/module.admin.page.form_elements.min.css', 'css/admin/module.admin.page.tables.min.css'];
$js = [
    'components/modules/admin/forms/elements/bootstrap-select/assets/lib/js/bootstrap-select.js?v=v2.1.0',
    'components/modules/admin/forms/elements/bootstrap-select/assets/custom/js/bootstrap-select.init.js?v=v2.1.0',
    'components/modules/admin/forms/elements/select2/assets/lib/js/select2.js?v=v2.1.0',
    'components/modules/admin/forms/elements/select2/assets/custom/js/select2.init.js?v=v2.1.0',
    'components/modules/admin/forms/elements/bootstrap-datepicker/assets/lib/js/bootstrap-datepicker.js?v=v2.1.0',
    'components/modules/admin/forms/elements/bootstrap-datepicker/assets/custom/js/bootstrap-datepicker.init.js?v=v2.1.0',
    'components/modules/admin/forms/elements/bootstrap-timepicker/assets/lib/js/bootstrap-timepicker.js?v=v2.1.0',
    'components/modules/admin/forms/elements/bootstrap-timepicker/assets/custom/js/bootstrap-timepicker.init.js?v=v2.1.0',
    'components/modules/admin/tables/datatables/assets/lib/js/jquery.dataTables.min.js?v=v2.1.0',
    'components/modules/admin/tables/datatables/assets/lib/extras/TableTools/media/js/TableTools.min.js?v=v2.1.0',
    'components/modules/admin/tables/datatables/assets/custom/js/DT_bootstrap.js?v=v2.1.0',
    'components/modules/admin/tables/datatables/assets/custom/js/datatables.init.js?v=v2.1.0',
    'components/modules/admin/forms/elements/jCombo/jquery.jCombo.min.js',
    'components/modules/admin/forms/elements/jasny-fileupload/assets/js/bootstrap-fileupload.js?v=v2.1.0'
];

$app->group('/plugins', function() use ($app, $css, $js) {

    $app->get('/', function () use($app, $css, $js) {

        $app->view->display('plugins/index', [
            'title' => 'Plugins',
            'cssArray' => $css,
            'jsArray' => $js
            ]
        );
    });

    $app->get('/activate/', function() use($app) {
        ob_start();

        $pluginName = _filter_input_string(INPUT_GET, 'id');
        $plugin = str_replace('.plugin.php', '', $pluginName);

        if (!file_exists(PLUGINS_DIR . $plugin . '/' . $pluginName)) {
            $file = PLUGINS_DIR . $pluginName;
        } else {
            $file = PLUGINS_DIR . $plugin . '/' . $pluginName;
        }
        if (file_exists($file)) {
            include_once($file);
        }

        /**
         * Fires before a specific plugin is activated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.00
         * @param string $pluginName The plugin's base name.
         */
        do_action('activate_plugin', $pluginName);

        /**
         * Fires as a specifig plugin is being activated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.00
         * @param string $pluginName The plugin's base name.
         */
        do_action('activate_' . $pluginName);

        activate_plugin($pluginName);

        /**
         * Fires after a plugin has been activated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.06
         * @param string $pluginName The plugin's base name.
         */
        do_action('activated_plugin', $pluginName);

        if (ob_get_length() > 0) {
            ob_get_clean();
        }
        ob_end_clean();

        redirect($app->req->server['HTTP_REFERER']);
    });

    $app->get('/deactivate/', function() use($app) {
        $pluginName = _filter_input_string(INPUT_GET, 'id');
        /**
         * Fires before a specific plugin is deactivated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.06
         * @param string $pluginName The plugin's base name.
         */
        do_action('deactivate_plugin', $pluginName);

        /**
         * Fires as a specifig plugin is being deactivated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.00
         * @param string $pluginName The plugin's base name.
         */
        do_action('deactivate_' . $pluginName);

        deactivate_plugin($pluginName);

        /**
         * Fires after a specific plugin has been deactivated.
         * 
         * $pluginName refers to the plugin's
         * name (i.e. moodle.plugin.php).
         * 
         * @since 6.1.06
         * @param string $pluginName The plugin's base name.
         */
        do_action('deactivated_plugin', $pluginName);

        redirect($app->req->server['HTTP_REFERER']);
    });

    $app->match('GET|POST', '/options/', function() use($app, $css, $js) {
        $app->view->display('plugins/options', [
            'title' => 'Plugin Options',
            'cssArray' => $css,
            'jsArray' => $js
            ]
        );
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/install/', function() {
        if (!hasPermission('access_plugin_admin_page')) {
            redirect(get_base_url() . 'dashboard' . DS);
        }
    });

    $app->match('GET|POST', '/install/', function () use($app, $css, $js) {

        if ($app->req->isPost()) {
            $name = explode(".", $_FILES["plugin_zip"]["name"]);
            $accepted_types = [ 'application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];

            foreach ($accepted_types as $mime_type) {
                if ($mime_type == $type) {
                    $okay = true;
                    break;
                }
            }

            $dir = substr($_FILES["plugin_zip"]["name"], 0, -15);
            $continue = strtolower($name[1]) == 'zip' ? true : false;

            if (!$continue) {
                $app->flash('error_message', _t('The file you are trying to upload is not the accepted file type. Please try again.'));
            }
            $target_path = APP_PATH . 'plugins' . DS . $_FILES["plugin_zip"]["name"];
            if (move_uploaded_file($_FILES["plugin_zip"]["tmp_name"], $target_path)) {
                $zip = new \ZipArchive();
                $x = $zip->open($target_path);
                if ($x === true) {
                    $zip->extractTo(APP_PATH . 'plugins' . DS . $dir);
                    $zip->close();
                    unlink($target_path);
                }
                $app->flash('success_message', _t('Your plugin was uploaded and installed properly.'));
            } else {
                $app->flash('error_message', _t('There was a problem uploading your plugin. Please try again or check the plugin package.'));
            }
            redirect($app->req->server['HTTP_REFERER']);
        }

        $app->view->display('plugins/install', [
            'title' => 'Install Plugins',
            'cssArray' => $css,
            'jsArray' => $js
            ]
        );
    });

    $app->setError(function() use($app) {

        $app->res->_format('json', 404);
    });
});
