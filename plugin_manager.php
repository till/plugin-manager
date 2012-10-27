<?php
use Composer\Factory;
use Composer\Installer;

/**
 * @category Plugins
 * @package  plugin_manager
 * @author   Till Klampaeckel <till@php.net>
 * @license
 * @version GIT: <git_id>
 * @link    http://github.com/roundcube/plugin-manager
 */
class plugin_manager extends rcube_plugin
{
    public $task = 'settings';

    public $noajax  = true;
    public $noframe = true;

    protected $env;

    public function init()
    {
        // these are dumb hacks until we figure out autoload!
        require_once INSTALL_PATH . '/vendor/autoload.php';
        require_once __DIR__ . '/src/ArrayInterface.php';

        $this->add_texts('localization/', array('plugin_manager'));
        $this->register_action('plugin.plugin_manager.show', array($this, 'actionShowInstalledPlugins'));
        $this->register_action('plugin.plugin_manager.updates', array($this, 'actionShowRequiredUpdates'));
        $this->include_script('plugin_manager.js');

        $this->env = array(
            'root' => INSTALL_PATH,
            'io'   => new ArrayInterface,
        );
    }

    /**
     * Show all plugins installed through composer.
     *
     * @return void
     */
    public function actionShowInstalledPlugins()
    {
        $this->register_handler('plugin.body', array($this, 'viewInstalledPlugins'));
        rcmail::get_instance()->output->send('plugin');
    }

    public function actionShowRequiredUpdates()
    {
        $this->register_handler('plugin.body', array($this, 'viewUpdates'));
        rcmail::get_instance()->output->send('plugin');
    }

    /**
     * Show all plugins installed through composer.
     *
     * @return string
     */
    public function viewInstalledPlugins()
    {
        $composer = $this->setupComposer();
        return var_export($composer, true);
        // maybe create a table here - show installed plugins
        // show if there are enabled (crosscheck to the config object)
        return 'plugins here';
    }

    /**
     * View updates available.
     *
     * This is a prototype and not considered even alpha quality.
     *
     * @return string
     */
    public function viewUpdates()
    {
        try {
            $composer  = $this->setupComposer();
            $installer = Installer::create($this->env['io'], $composer);
            $status    = $installer->setDryRun(true)->setUpdate(true)->run();
            if (false === $status) {
                return 'Possible error trying to figure out updates.';
            }
            if (count($GLOBALS['COMPOSER_MESSAGES']) == 0) {
                return 'There are no updates at this time.';
            }
            $response = '<ul>';
            foreach ($GLOBALS['COMPOSER_MESSAGES'] as $message) {
                $response .= '<li>' . $message . '</li>';
            }
            $response .= '</ul>';
            return $response;

            //$rcmail = rcmail::get_instance();
        } catch (\Exception $e) {
            return (string) 'Error occurred';
        }
    }

    /**
     * @return Composer\Composer
     */
    protected function setupComposer()
    {
        $factory  = new Factory;
        $composer = $factory->createComposer($this->env['io'], $this->env['root'] . '/composer.json');
        return $composer;
    }
}
