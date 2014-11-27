<?php

namespace Test\Omnipay\Dummy;

use Omnipay\Dummy\Gateway;

class ExtendedGateway extends Gateway
{
	public function authorize(array $parameters = array())
    {
        return $this->createRequest(__NAMESPACE__.'\Message\RedirectAuthorizeRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
    	return parent::authorize($parameters);
    }

    public function refund(array $parameters = array())
    {
        return $this->createRequest(__NAMESPACE__.'\Message\RefundRequest', $parameters);
    }
}
