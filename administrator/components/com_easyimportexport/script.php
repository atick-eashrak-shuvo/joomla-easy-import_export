<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;

class Com_EasyimportexportInstallerScript
{
    protected $minimumPhp = '8.1';
    protected $minimumJoomla = '4.0.0';

    public function preflight($type, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('This extension requires PHP %s or newer.', $this->minimumPhp),
                'error'
            );
            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('This extension requires Joomla %s or newer.', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        return true;
    }

    public function install(InstallerAdapter $adapter): bool
    {
        Factory::getApplication()->enqueueMessage(
            Text::_('COM_EASYIMPORTEXPORT_INSTALL_SUCCESS'),
            'message'
        );
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight($type, InstallerAdapter $adapter): bool
    {
        return true;
    }
}
