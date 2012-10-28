<?php
/**
 * PHP Version 5.3
 *
 * @category Plugins
 * @package  plugin_manager
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License
 * @version  GIT: <git_id>
 * @link     http://github.com/roundcube/plugin-manager
 */

use Composer\Factory;
use Composer\Package\Locker;
use Composer\Config;
use Composer\Json\JsonFile;
use Composer\Repository\RepositoryManager;
use Composer\Installer;

/**
 * @category Plugins
 * @package  plugin_manager
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License
 * @version  GIT: <git_id>
 * @link     http://github.com/roundcube/plugin-manager
 */
class plugin_manager extends rcube_plugin
{
    /**
     * Ensure this plugin is only used here!
     * @var string
     */
    public $task = 'settings';

    public $noajax  = true;
    public $noframe = true;

    /**
     * Store some data we need!
     */
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
            'root'   => INSTALL_PATH,
            'rcmail' => rcmail::get_instance(),
            'io'     => new ArrayInterface,
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
        $locker   = $this->setupLocker();
        $repo     = $locker->getLockedRepository();
        $packages = $repo->getPackages();
        if ($repo->count() == 0) {
            $this->env['rcmail']->output->command(
                'display_message',
                'Nothing installed via composer.',
                'error'
            );
            return;
        }
        $roundcubeDeps = array();
        $plugins       = array();
        foreach ($packages as $package) {
            if ('roundcube-plugin' === $package->getType()) {
                $plugins[] = $package;
                continue;
            }
            $roundcubeDeps[] = $package;
        }
        $table = $this->getTable($plugins);

        $response  = html::tag('h3', array(), 'Plugin Manager');
        $response .= html::tag('p', array(), 'The following plugins are currently installed:');
        $response .= $table->show();

        $response .= '<a href="./?_task=settings&_action=plugin.plugin_manager.updates">See updates.</a>';

        $link = html::tag('a', array('href' => 'http://plugins.roundcube.net/', 'target' => '_blank'), 'Click here to find more plugins!');
        $response .= html::tag('p', array(), 'Need more? ' . $link);
        $response .= html::tag('p', array(), 'To enable more plugins, just add them to your `composer.json` and `./composer.phar update`');

        return $response;
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
        $response = html::tag('h3', array(), 'Plugin Manager - See available updates');
        try {
            $composer  = $this->setupComposer();
            $installer = Installer::create($this->env['io'], $composer);
            $status    = $installer->setDryRun(true)->setUpdate(true)->run();
            if (false === $status) {
                $this->env['rcmail']->output->command(
                    'display_message',
                    'Possible error trying to figure out updates.',
                    'error'
                );
                return $response;
            }
            if (count($GLOBALS['COMPOSER_MESSAGES']) == 0) {
                $this->env['rcmail']->output->command(
                    'display_message',
                    'There are no updates at this time.',
                    'success'
                );
                return $response;
            }
            $response .= html::tag('p', array(), 'The following plugins could use an update (this is a cheap trick/wip):');
            $response .= '<ul>';
            $response .= var_export($GLOBALS['COMPOSER_MESSAGES'], true);
            foreach ($GLOBALS['COMPOSER_MESSAGES'] as $message) {
                $response .= html::tag('li', array(), $message);
            }
            $response .= '</ul>';
            $response .= html::tag(
                'p',
                array(),
                html::tag('b', array(), 'Next steps:') . sprintf(
                    'Go into "%s" and issue: `./composer.phar update`',
                    INSTALL_PATH
                )
            );
            return $response;

        } catch (\Exception $e) {
            $this->env['rcmail']->output->command(
                'display_message',
                'Error occurred: ' . $e->getMessage(),
                'error'
            );
            return;
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

    /**
     * Setup Composer's locker. The locker allows us to query what is currently installed
     * via composer.
     *
     * @return Locker
     */
    protected function setupLocker()
    {
        $config = Factory::createConfig();

        $json = new JsonFile($this->env['root'] . '/composer.lock');
        $rm   = $this->setupRepositoryManager($this->env['io'], $config);
        $im   = new Installer\InstallationManager($config->get('vendor-dir'));

        return new Locker($json, $rm, $im, '');
    }

    /**
     * Setup Composer's repository manager.
     *
     * @param IOInterface $io
     * @param Config      $config
     *
     * @return RepositoryManager
     */
    protected function setupRepositoryManager($io, $config)
    {
        $rm = new RepositoryManager($io, $config);
        $rm->setRepositoryClass('composer', 'Composer\Repository\ComposerRepository');
        $rm->setRepositoryClass('vcs', 'Composer\Repository\VcsRepository');
        $rm->setRepositoryClass('package', 'Composer\Repository\PackageRepository');
        $rm->setRepositoryClass('pear', 'Composer\Repository\PearRepository');
        $rm->setRepositoryClass('git', 'Composer\Repository\VcsRepository');
        $rm->setRepositoryClass('svn', 'Composer\Repository\VcsRepository');
        $rm->setRepositoryClass('hg', 'Composer\Repository\VcsRepository');
        return $rm;
    }

    /**
     * Draw a table of composer packages.
     *
     * @param array $packages
     *
     * @return html_table
     */
    protected function getTable(array $packages)
    {
        $table = new html_table(array('cols' => 4, 'cellpadding' => 4, 'width' => 500,));
        $table->add_header('', 'Name');
        $table->add_header('', 'Version');
        $table->add_header('', 'Source');
        $table->add_header('', 'Authors');

        foreach ($packages as $package) {
            $table->add('', Q($package->getName()));
            $table->add(array('nowrap' => 'nowrap'), Q($package->getPrettyVersion()));
            $table->add('', Q($package->getHomepage()));

            $authors = $package->getAuthors();
            if (count($authors) == 0) {
                $table->add('', 'Unknown authors');
                continue;
            }
            $authors_html = '';
            foreach ($authors as $author) {
                $authors_html .= $author['name'];
            }
            $table->add('', $authors_html);
        }
        return $table;
    }
}
