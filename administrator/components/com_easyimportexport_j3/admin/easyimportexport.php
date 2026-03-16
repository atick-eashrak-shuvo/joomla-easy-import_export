<?php

defined('_JEXEC') or die;

$controller = JControllerLegacy::getInstance('Easyimportexport');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
