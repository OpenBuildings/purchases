<?php

namespace Test\Omnipay\Dummy;

use Omnipay\Dummy\Gateway;

class ExtendedGateway extends Gateway
{
	public function purchase(array $parameters = array())
	{
		return $this->createRequest(__NAMESPACE__.'\Message\RedirectCreditCardRequest', $parameters);
	}
}
