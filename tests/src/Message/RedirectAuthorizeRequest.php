<?php

namespace Test\Omnipay\Dummy\Message;

use Omnipay\Dummy\Message\AuthorizeRequest;

class RedirectAuthorizeRequest extends AuthorizeRequest
{
	public function sendData($data)
	{
	    $data['reference'] = uniqid();
	    $data['success'] = 0 === substr($this->getCard()->getNumber(), -1, 1) % 2;
	    $data['message'] = $data['success'] ? 'Success' : 'Failure';

	    return $this->response = new RedirectResponse($this, $data);
	}
}
