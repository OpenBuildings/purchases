<?php

namespace Test\Omnipay\Dummy\Message;

use Omnipay\Dummy\Message\Response;

class RedirectResponse extends Response
{
	public function isSuccessful()
	{
		return false;
	}

	public function isRedirect()
	{
		return true;
	}
}
