<?php

namespace Omnipay\Paybox\Message;

/**
 * Paybox System Authorize Request
 */
class SystemAuthorizeRequest extends AbstractRequest
{
    protected $onlyAuthorize = true;
    /**
     * Transaction time in timezone format e.g 2011-02-28T11:01:50+01:00.
     *
     * @var string
     */
    protected $time;

    /**
     * Get time of the transaction.
     *
     * @return string
     */
    public function getTime()
    {
        return (!empty($this->time)) ? $this->time : date('c');
    }

    /**
     * Setter for time (of transaction).
     *
     * @param string $time
     *  Time in 'c' format - e.g 2011-02-28T11:01:50+01:00
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    public function getData()
    {
        foreach ($this->getRequiredCoreFields() as $field) {
            $this->validate($field);
        }
        $this->validateCardFields();
        $data = $this->getBaseData() + $this->getTransactionData() + $this->getURLData() + $this->getAbonne();
        if ($this->onlyAuthorize) {
            $data['PBX_AUTOSEULE'] = 'O';
        }

        $data['PBX_HMAC'] = $this->generateSignature($data);

        return $data;
    }

    public function sendData($data) {
        return $this->response = new SystemResponse($this, $data, $this->getEndpoint());
    }

    protected function createResponse($data) {
        return $this->response = new SystemResponse($this, $data, $this->getEndpoint());
    }

    public function getSite()
    {
        return $this->getParameter('site');
    }

    public function setSite($value)
    {
        return $this->setParameter('site', $value);
    }

    public function getRang()
    {
        return $this->getParameter('rang');
    }

    public function setRang($value)
    {
        return $this->setParameter('rang', $value);
    }

    public function getIdentifiant()
    {
        return $this->getParameter('identifiant');
    }

    public function setIdentifiant($value)
    {
        return $this->setParameter('identifiant', $value);
    }

    public function getRequiredCoreFields()
    {
        return [
            'amount',
            'currency',
        ];
    }

    public function getRequiredCardFields()
    {
        return [
            'email',
        ];
    }

    public function getTransactionData()
    {
        return [
            'PBX_HASH' => 'SHA512',
            'PBX_TOTAL' => $this->getAmountInteger(),
            'PBX_DEVISE' => $this->getCurrencyNumeric(),
            'PBX_CMD' => $this->getTransactionId(),
            'PBX_PORTEUR' => $this->getCard()->getEmail(),
            // liste des lettres : (page 9) https://www.ca-moncommerce.com/wp-content/uploads/2018/08/tableau_correspondance_sips_atos-paybox_v4.pdf
            'PBX_RETOUR' => 'Mt:M;Id:R;Ref:A;Erreur:E'
                . ($this->getWantAbonne() ? ';Abo:U;CardType:C;CardEmpreinte:H;Card2LastNumber:J;Card6FirstNumber:N' : '')
                . ($this->getToken() ? ';AboUse:B' : '')
                . ';3d:G;sign:K',
            'PBX_TIME' => $this->getTime(),
        ];
    }

    public function getAbonne()
    {
        $return = [];

        if ($this->getRefAbonne() && $this->getToken()) {
            $return = [
                'PBX_REFABONNE' => $this->getRefAbonne(),
                'PBX_TOKEN' => $this->getToken(),
            ];

            if ($this->getDateval()) {
                $return['PBX_DATEVAL'] = $this->getDateval();
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getBaseData()
    {
        return [
            'PBX_SITE' => $this->getSite(),
            'PBX_RANG' => $this->getRang(),
            'PBX_IDENTIFIANT' => $this->getIdentifiant(),
        ];
    }

    /**
     * Get values for IPN and browser return urls.
     *
     * Browser return urls should all be set or non set.
     */
    public function getURLData()
    {
        $data = [];
        if ($this->getNotifyUrl()) {
            $data['PBX_REPONDRE_A'] = $this->getNotifyUrl();
        }
        if ($this->getReturnUrl()) {
            $data['PBX_EFFECTUE'] = $this->getReturnUrl();
            $data['PBX_REFUSE'] = $this->getReturnUrl();
            $data['PBX_ANNULE'] = $this->getCancelUrl();
            $data['PBX_ATTENTE'] = $this->getReturnUrl();
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getUniqueID()
    {
        return uniqid();
    }

    /**
     * @return string
     * http://www1.paybox.com/wp-content/uploads/2014/02/ManuelIntegrationPayboxSystem_V6.2_EN.pdf
     */
    public function getEndpoint()
    {
        if ($this->getTestMode()) {
            return 'https://preprod-tpeweb.e-transactions.fr/cgi/MYchoix_pagepaiement.cgi';
        } else {
            return 'https://tpeweb.e-transactions.fr/cgi/MYchoix_pagepaiement.cgi';
        }
    }

    public function getPaymentMethod()
    {
        return 'card';
    }

    public function getTransactionType()
    {
        return '00001';
    }
}
