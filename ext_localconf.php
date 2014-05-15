<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

$signalSlotDispatcher->connect(
    'Tx_Powermail_Controller_FormsController',
    'createActionAfterSubmitView',
    '\JL\Pmajaxsubmit\Controller\AjaxController',
    'createActionAfterSubmitView'
);

$signalSlotDispatcher->connect(
    'Tx_Powermail_Controller_FormsController',
    'createActionBeforeRenderView',
    '\JL\Pmajaxsubmit\Controller\AjaxController',
    'createActionBeforeRenderView'
);

$signalSlotDispatcher->connect(
    'Tx_Powermail_Controller_FormsController',
    'formActionBeforeRenderView',
    '\JL\Pmajaxsubmit\Controller\AjaxController',
    'formActionBeforeRenderView'
);