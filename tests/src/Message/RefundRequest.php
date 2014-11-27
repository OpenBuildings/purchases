<?php

namespace Test\Omnipay\Dummy\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Dummy\Message\Response;

class RefundRequest extends AbstractRequest
{
	public function getReason()
	{
	    return $this->getParameter('reason');
	}

	/**
	 * @param string $value
	 */
	public function setReason($value)
	{
	    return $this->setParameter('reason', $value);
	}

	public function getData()
	{
	    $this->validate('transactionReference', 'reason', 'amount');

	    return array('amount' => $this->getAmount());
	}

	public function sendData($data)
	{
	    $data['success'] = (strpos($this->getReason(), 'Fail') === FALSE);
	    $data['message'] = $data['success'] ? 'Success' : 'Failure';

	    return $this->response = new Response($this, $data);
	}
}
