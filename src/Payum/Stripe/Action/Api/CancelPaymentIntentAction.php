<?php
/**
 * @license
 * Copyright (C) ATW HANDELSPARTNER HANDELSBOLAG - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Oscar Reimer <oscar.reimer@reimerbrothers.com>, 2019
 *
 * See the LICENSE file in root directory for more information including third
 * party licenses
 */

declare(strict_types=1);

namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Keys;
use Payum\Stripe\Request\Api\CancelPaymentIntent;
use Stripe\Error\Base;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * @author NoResponseMate <noresponsemate@protonmail.com>
 */
class CancelPaymentIntentAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait {
        setApi as _setApi;
    }

    /**
     * @deprecated BC will be removed in 2.x. Use $this->api
     *
     * @var Keys
     */
    protected $keys;

    public function __construct()
    {
        $this->apiClass = Keys::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        $this->_setApi($api);

        // BC. will be removed in 2.x
        $this->keys = $this->api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CancelPaymentIntent */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        if ($model['object'] !== 'payment_intent' && !isset($model['id'])) {
            throw new LogicException('The payment intent id has to be set.');
        }

        try {
            Stripe::setApiKey($this->keys->getSecretKey());

            $intent = PaymentIntent::retrieve($model['id']);
            $intent->cancel();

            $model->replace($intent->__toArray(true));
        } catch (Base $e) {
            $model->replace($e->getJsonBody());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CancelPaymentIntent &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
