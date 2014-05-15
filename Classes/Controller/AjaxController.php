<?php
namespace JL\Pmajaxsubmit\Controller;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use \TYPO3\CMS\Extbase\Utility\DebuggerUtility as DU;

/**
 * AjaxController
 * @author Johannes Lumpe <johannes@lum.pe>
 * @package Ajaxsubmit
 */
class AjaxController extends \TYPO3\CMS\Extbase\MVC\Controller\ActionController implements \TYPO3\CMS\Core\SingletonInterface {

    const AJAX_FIELD_ID     = 9999999;
    const AJAX_FIELD_NAME   = 'pmajaxsubmit_ajax';

    protected $controller;

    protected $forms;

    protected $isAjaxSubmit = false;

    /**
     * Signalhandler for formActionBeforeRenderView (Slot is called before the form is rendered)
     * Processes errors or injects the ajax field into the form
     */
    public function formActionBeforeRenderView($forms, $controller) {
        $this->forms = $forms;
        $this->controller = $controller;
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(get_class_methods($controller->objectManager));
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($controller->objectManager);
        // $localization = $controller->objectManager->get('\TYPO3\CMS\Extbase\Utility\LocalizationUtility');
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($localization);
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(func_get_args());
        // die;
        $request = $controller->getControllerContext()->getRequest()->getOriginalRequest();
        if ($request) {
            $arguments = $request->getArguments();
            if ($this->isAjaxRequest($arguments['field'])) {
                $this->processErrors();
            }
        } else {
            $this->addAjaxField();
        }
    }

    public function isAjaxRequest($field) {
        if (isset($field[self::AJAX_FIELD_ID]) &&
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->isAjaxSubmit = true;
        }

        return $this->isAjaxSubmit;
    }

    /**
     * Signalhandler for createActionBeforeRenderView (Slot is called before the answered are stored and the mails are sent)
     */
    public function createActionBeforeRenderView($field, $form, $mail, $controller) {
        $this->controller = $controller;
        $this->isAjaxRequest($field);
    }

    /**
     * Signalhandler for createActionAfterSubmitView (Slot is called after the thx message was rendered (Only if no redirect was activated))
     */
    public function createActionAfterSubmitView($field, $form, $mail, $controller, $newMail) {
        if ($this->isAjaxSubmit) {
            $this->sendJSONResponse(array(
                'message' => $controller->view->render()
            ));
        }
    }

    /**
     * adds a field to the form to detect an ajax submit
     */
    protected function addAjaxField() {
        $ajaxField = $this->controller->objectManager->get('Tx_Powermail_Domain_Model_Fields');
        $ajaxField->setType('hidden');
        $ajaxField->setTitle(self::AJAX_FIELD_NAME);
        $ajaxField->setMarker(self::AJAX_FIELD_NAME);
        $ajaxField->setPrefillValue('1');
        // set an abnormally high uid for our field, so we can identify it later
        $ajaxField->_setProperty('uid', self::AJAX_FIELD_ID);

        $this->forms->getFirst()->getPages()->current()->addField($ajaxField);
    }

    /**
     * Processes all errors and returns a proper response
     */
    protected function processErrors() {
        $context                = $this->controller->getControllerContext();
        $fieldRepository        = $this->controller->objectManager->get('Tx_Powermail_Domain_Repository_FieldsRepository');
        $validationResults      = $context->getRequest()->getOriginalRequestMappingResults();
        $errors                 = $validationResults->getFlattenedErrors();
        $errorResults           = array();

        $response = $context->getResponse();

        // if we have any errors we return send a response and bail out
        if ($errors['field']) {
            foreach ($errors['field'] as $error) {
                $field = $fieldRepository->findByUid($error->getCode());
                $key = 'powermail_field_' . str_replace(' ', '', strtolower($field->getTitle()));
                $errorResults[$key] = LocalizationUtility::translate('validationerror_' . $error->getMessage(), 'Powermail');
            }
            $statusCode = 400;
        } else {
            $errorResults = null;
            $statusCode = 200;
        }
        $this->sendJSONResponse(array('errors' => $errorResults), $statusCode);
    }

    protected function sendJSONResponse($content, $statusCode = 200) {
        $response = $this->controller->getControllerContext()->getResponse();
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($content));
        $response->setStatus($statusCode);
        $response->send();
        die;
    }

}
