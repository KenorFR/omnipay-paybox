<?php

namespace Omnipay\Paybox\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Paybox Complete Authorize Response
 */
class SystemCompleteAuthorizeResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return isset($this->data['x_response_code']) && '1' === $this->data['x_response_code'];
    }

    public function getTransactionReference()
    {
        return isset($this->data['x_trans_id']) ? $this->data['x_trans_id'] : null;
    }

    public function getMessage()
    {
        return isset($this->data['x_response_reason_text']) ? $this->data['x_response_reason_text'] : null;
    }
}
