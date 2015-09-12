<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Payment\PagePrivacy;

use Page\PagePrivacy\PageAbstractPagePrivacy;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class PaymentBuyItemsPrivacy extends PageAbstractPagePrivacy
{
    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentWidget
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Payment\Model\PaymentWidget
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentWidget');
        }

        return $this->model;
    }

    /**
     * Is allowed view page
     * 
     * @param array $privacyOptions
     * @param boolean $trustedData
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false)
    {
        // get a news id from the route or params
        if (!$trustedData) {
            $transactionId = $this->objectId 
                ? $this->objectId
                : RouteParamUtility::getParam('slug', -1);

            // check an existing transaction
            if (null == ($transactionInfo = 
                    $this->getModel()->getTransactionInfo($transactionId, true, 'slug'))) {

                return false;
            }

            if ($transactionInfo['amount'] <= 0) {
                return false;
            }

            if (null == ($paymentsTypes =
                    $this->getModel()->getPaymentsTypes(false, true))) {

                return false;
            }

            // check count of transaction's items
            if (!count($this->getModel()->getAllTransactionItems($transactionInfo['id']))) {
                return false;
            }
        }

        return true;
    }
}