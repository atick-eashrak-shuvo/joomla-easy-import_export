<?php

defined('_JEXEC') or die;

class Com_EasyimportexportInstallerScript
{
    protected $minimumJoomla = '3.4.0';
    protected $minimumPhp    = '5.6.0';

    public function preflight($type, $parent)
    {
        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            JFactory::getApplication()->enqueueMessage(
                sprintf('Easy Import/Export requires Joomla %s or later.', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            JFactory::getApplication()->enqueueMessage(
                sprintf('Easy Import/Export requires PHP %s or later.', $this->minimumPhp),
                'error'
            );
            return false;
        }

        return true;
    }

    public function install($parent)
    {
        JFactory::getApplication()->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_INSTALL_SUCCESS'), 'message');
    }

    public function update($parent)
    {
        return true;
    }

    public function uninstall($parent)
    {
        return true;
    }
}
