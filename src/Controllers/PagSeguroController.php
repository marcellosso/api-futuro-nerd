<?php
//use \GuzzleHttp\Pool;
//use \GuzzleHttp\Client;
//use \GuzzleHttp\Psr7\Request;


namespace Controllers;

use mysql_xdevapi\Exception;

class PagSeguroController
{
    private $view;
    private $pdo;
    private $logger;
    private $_table_name = "";

    public function __construct($view, $pdo, $logger)
    {
        $this->view = $view;
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getCreditCardToken($data)
    {
        $options = [
            'form_params' => [
                'sessionId' => $data['sessionId'],
                'amount' => $data['amount'],
                'cardNumber' => $data['cardNumber'],
                'cardBrand' => @$data['cardBrand'],
                'cardCvv' => $data['cardCvv'],
                'cardExpirationMonth' => $data['cardExpirationMonth'],
                'cardExpirationYear' => $data['cardExpirationYear']
            ]
        ];
        $credentials = \PagSeguroConfig::getAccountCredentials();
        $emailAccountCredentials = $credentials->getEmail();
        $tokenAccountCredentials = $credentials->getToken();

        $client = new \GuzzleHttp\Client(['headers', array('Accept' => 'application/x-www-form-urlencoded,application/json', 'Content-Type' => 'application/x-www-form-urlencoded', 'Host' => 'df.uol.com.br', 'User-Agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36')]);
        $response = $client->post("https://df.uol.com.br//v2/cards/?email={$emailAccountCredentials}&token={$tokenAccountCredentials}", $options);

        $transaction = @simplexml_load_string($response->getBody());
        return empty($transaction->token)?null:$transaction->token;
    }

    public function getSessionID()
    {
        \PagSeguroLibrary::init();
        \PagSeguroConfig::init();
        \PagSeguroResources::init();
        //PagSeguro credentials via dot env file
        $credentials = \PagSeguroConfig::getAccountCredentials();
        return \PagSeguroSessionService::getSession($credentials);
    }

    public function store($data)
    {
        $sessionid = empty($data->pagamento_session)?$this->getSessionID():$data->pagamento_session;
        //PagSeguro Configs
        $directPaymentRequest = new \PagSeguroDirectPaymentRequest();
        $directPaymentRequest->setPaymentMode('DEFAULT'); // GATEWAY
        $directPaymentRequest->setPaymentMethod('CREDIT_CARD');
        $directPaymentRequest->setCurrency('BRL');
        $directPaymentRequest->setReference($data->id_prods_pedidos);
        $notificationURL = 'http://api.futuronerd.grupoa2.jamit.digital/pagseguro_notification';
        $directPaymentRequest->setNotificationURL($notificationURL);
        // Add Item
        $itemId = str_pad($data->item_id, 4, "0", STR_PAD_LEFT);
        $item_amount = number_format((float)$data->item_amount, 2, '.', '');
        $valor_total = $data->frete_preco + $data->item_amount;

        $valor_total = number_format((float)$valor_total, 2, '.', '');
        $directPaymentRequest->addItem($itemId, $data->item_description, $data->item_quantity, $valor_total);

        // Set Sender
        $directPaymentRequest->setSender(
            $data->pagamento_sender_nome,
            $data->pagamento_sender_email,
            $data->pagamento_sender_phone_areacode,
            $data->pagamento_sender_phone_number,
            $data->pagamento_sender_document_type,
            $data->pagamento_sender_document_value
        );

        // Shipping type **Optional**
        $serviceCode = \PagSeguroShippingType::getCodeByType($data->frete_nome);
        $directPaymentRequest->setShippingType($serviceCode);
        $directPaymentRequest->setShippingAddress(
            $data->pagamento_shipping_postalcode,
            $data->pagamento_shipping_street,
            $data->pagamento_shipping_number,
            $data->pagamento_shipping_complement,
            $data->pagamento_shipping_district,
            $data->pagamento_shipping_city,
            $data->pagamento_shipping_state,
            $data->pagamento_shipping_country
        );
        // Billing information
        $billingAddress = new \PagSeguroBilling(
            [
                'postalCode' => $data->pagamento_shipping_postalcode,
                'street' => $data->pagamento_shipping_street,
                'number' => $data->pagamento_shipping_number,
                'complement' => $data->pagamento_shipping_complement,
                'district' => $data->pagamento_shipping_district,
                'city' => $data->pagamento_shipping_city,
                'state' => $data->pagamento_shipping_state,
                'country' => $data->pagamento_shipping_country,
            ]
        );
        // Set payment method
        if(empty($data->creditcard_token)) {
            $arr_credit_card = [
                'sessionId' => $sessionid,
                'amount' => $valor_total,
                'cardNumber' => $data->creditcard_numero_cartao,
                'cardBrand' => 'visa',
                'cardCvv' => $data->creditcard_codigo_seguranca,
                'cardExpirationMonth' => $data->creditcard_mes_validade,
                'cardExpirationYear' => $data->creditcard_ano_validade
            ];
            sleep(5);
            $credit_card_token = $this->getCreditCardToken($arr_credit_card);
            if (empty($credit_card_token)) return 'Token Inválido!! '; //throw new Exception('Token inválido!');
        }else{
            $credit_card_token = $data->creditcard_token;
        }

//        $valor_total_installment = number_format((float)$valor_total, 2, '.', '');
        $arr_installment = array(
            "quantity" => "1",
            "value" => "{$valor_total}",
            "noInterestInstallmentQuantity" => 3
        );

        $installment = new \PagSeguroDirectPaymentInstallment($arr_installment);
        $cc_checkout = array(
            'token' => (string) $credit_card_token ,
            'installment' => $installment,
            'holder' => new \PagSeguroCreditCardHolder(
                array(
                    'name' => $data->pagamento_sender_nome,
                    'documents' => array(
                        'type' => $data->pagamento_sender_document_type,
                        'value' => $data->pagamento_sender_document_value
                    ),
//                    'birthDate' => date('01/10/1979'),
                    'areaCode' => $data->pagamento_sender_phone_areacode,
                    'number' => $data->pagamento_sender_phone_number
                )
            ),
            'billing' => $billingAddress
        );
        $creditCardData = new \PagSeguroCreditCardCheckout( $cc_checkout );


        $directPaymentRequest->setCreditCard($creditCardData);
        try {
            $credentials = \PagSeguroConfig::getAccountCredentials();
            $response = $directPaymentRequest->register($credentials);


            $pagseguro_transaction = new PagSeguroTransactionController($this->view, $this->pdo, $this->logger);
            $data_transaction = [
                "code" => $response->getCode(),
                "email" => $response->getSender()->getEmail(),
//                "date" => $response_date->getTimestamp(),
                "date" => $response->getDate(),
                "reference" => $response->getReference(),
                "status" => $response->getStatus()->getValue(),
                "itemsCount" => count($response->getItems()),
                "id_pagamento_prods_pedidos" => $data->id
            ];

            $id_transaction = $pagseguro_transaction->insert($data_transaction);

        } catch (\PagSeguroServiceException $e) {
            return ["status"=>"error", "message"=>$e->getMessage()];
        }
        return ["status"=>"sucess", "data"=>["transaction_id"=>$id_transaction, "directPaymentRequest"=>$response]];
    }


    // Return Session Id PagSeguro
    public function SessionId($req, $res)
    {
        // PagSeguro Libraries
        \PagSeguroLibrary::init();
        \PagSeguroConfig::init();
        \PagSeguroResources::init();
        //PagSeguro credentials via dot env file
        $credentials = \PagSeguroConfig::getAccountCredentials();
        $data = [
            'sessionid' => \PagSeguroSessionService::getSession($credentials),
        ];
        $newResponse = $res->withJson($data);
        return $res->withHeader('Content-type', 'application/json');
    }
}
