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
namespace Payment\Type;

use Payment\Model\PaymentBase as PaymentBaseModel;
use Payment\Exception\PaymentException;

class PaymentTypeManager
{
    /**
     * List of instances
     *
     * @var array
     */
    private $instances = [];

    /**
     * Model
     *
     * @var \Payment\Model\PaymentBase
     */
    private $model;

    /**
     * Class constructor
     * 
     * @param \Payment\Model\PaymentBase $model
     */
    public function __construct(PaymentBaseModel $model)
    {
        $this->model = $model;
    }

    /**
     * Get an object instance
     *
     * @param string $name
     * @throws \Payment\Exception\PaymentException
     * @return \Payment\Type\PaymentTypeInterface
     */
    public function getInstance($name)
    {
        if (!class_exists($name)) {
            throw new PaymentException(sprintf('The class name "%s" cannot be found', $name));
        }

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }

        $paymentType = new $name($this->model);

        if (!$paymentType instanceof PaymentTypeInterface) {
            throw new PaymentException(sprintf('The file "%s" must be an object implementing Payment\Type\PaymentTypeInterface', $name));
        }

        $this->instances[$name] = $paymentType;

        return $this->instances[$name];
    }
}
