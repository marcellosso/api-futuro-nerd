<?php

namespace Controllers;

use function GuzzleHttp\json_encode;

/**
 * Classe de Produtos
 *
 */
class PagamentoProdutoPaiController
{
    private $view;
    private $pdo;
    private $logger;
    private $_table_name = "pagamento_prods_pedidos";

    public function __construct($view, $pdo, $logger)
    {
        $this->view = $view;
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function listAll()
    {
        return $this->pdo->query("SELECT * FROM {$this->_table_name} ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sth = $this->pdo->prepare("SELECT * FROM {$this->_table_name} WHERE id = :id");
        $sth->bindValue(':id', $id);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        try {
            if (empty($data)) return false;
            $keys = array_keys($data);
            $sth = $this->pdo->prepare("INSERT INTO {$this->_table_name} (" . implode(',', $keys) . ") VALUES (:" . implode(",:", $keys) . ")");
            //$sql = "INSERT INTO {$this->_table_name} (" . implode(',', $keys) . ") VALUES (:" . implode(",:", $keys) . ")";
            foreach ($data as $key => $value) {
                $sth->bindValue(':' . $key, $value);
                //$sql = str_replace(":$key", "\"{$value}\"", $sql);
            }
//            die($sql);
            $sth->execute();
            // die(var_dump($sth->debugDumpParams()));
            //die(var_dump($sth->errorInfo()));
            return $this->pdo->lastInsertId();
        } catch (PDOExecption $e) {
            die(var_dump($e));
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            if (empty($data)) return false;
            $sets = [];
            foreach ($data as $key => $VALUES) {
                $sets[] = $key . " = :" . $key;
            }
            $sth = $this->pdo->prepare("UPDATE {$this->_table_name} SET " . implode(',', $sets) . " WHERE id = :id");
            $sth->bindValue(':id', $id);
            foreach ($data as $key => $value) {
                $sth->bindValue(':' . $key, $value);
            }
            return (array) ['status' => $sth->execute() == 1];
        } catch (PDOExecption $e) {
            return false;
        }
    }

    public function delete($id)
    {
        global $app;
        $sth = $this->pdo->prepare("DELETE FROM {$this->_table_name} WHERE id = :id");
        $sth->bindValue(':id', $id);
        return ["data" => ['status' => $sth->execute() == 1]];
    }

    public function getProdutosSolicitados($id_filho)
    {
        $id_filho = addslashes($id_filho);
        $sql = " 	SELECT  p.id AS  p_id,
		 			    p.nome_produto AS p_nome_produto,
						p.preco AS p_preco,
						p.foto AS p_foto,
						p.descricao AS p_descricao,
						p.id_categoria AS p_id_categoria,
						p.largura AS p_largura,
						p.altura AS p_altura,
						p.comprimento AS p_comprimento,
						p.peso AS p_peso,
						pp.id AS pp_id,
						pp.id_filho AS pp_id_filho,
						pp.id_produto AS pp_id_produto,
						pp.status_pedido AS pp_status_pedido,
						pp.data_pedido AS pp_data_pedido,
						cf.id AS cf_id, 
						cf.id_pai AS cf_id_pai, 
						cf.nome AS cf_nome, 
						cf.email AS cf_email, 
						cf.pts AS cf_pts, 
						cf.id_serie AS cf_id_serie,
						cp.categoria AS cp_categoria
				FROM {$this->_table_name} p JOIN prods_pedidos pp ON p.id = pp.id_produto
						JOIN clientes_filhos cf ON pp.id_filho = cf.id
						JOIN categoria_prod cp ON cp.id = p.id_categoria
				WHERE pp.id_filho = {$id_filho} GROUP BY p.id ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function finalizarPagamentoPedidoPai($obj)
    {
        // return $obj['pagamento']['items'][0];
        $obj_retorno_pagamento_prods_pedidos = $this->inserirPagamento($obj)->data;
        
        @$obj_retorno_pagamento_prods_pedidos->item_amount = intval(@$obj['pagamento']['items'][0]['amount']); //?$obj['pagamento']['items'][0]['amount']:0 ;
        $obj_retorno_pagamento_prods_pedidos->item_description = $obj['pagamento']['items'][0]['description'];
        $obj_retorno_pagamento_prods_pedidos->item_id = $obj['pagamento']['items'][0]['id'];
        $obj_retorno_pagamento_prods_pedidos->item_quantity = $obj['pagamento']['items'][0]['quantity'];

        

        $pagseguro = new PagSeguroController($this->view, $this->pdo, $this->logger);
//        return $pagseguro->getCreditCardToken();
        $obj_pagamento = $pagseguro->store($obj_retorno_pagamento_prods_pedidos);
        


        return $obj_pagamento;
    }

    private function inserirPagamento($obj)
    {
        $objPagamento = new \StdClass();

        $objPagamento->id_prods_pedidos = $obj['produto']['pp_id'];
        $objPagamento->creditcard_ano_validade = $obj['creditCard']['ano_validade'];
        $objPagamento->creditcard_codigo_seguranca = $obj['creditCard']['codigo_seguranca'];
        $objPagamento->creditcard_mes_validade = $obj['creditCard']['mes_validade'];
        $objPagamento->creditcard_numero_cartao = $obj['creditCard']['numero_cartao'];

        $objPagamento->creditcard_brand = $obj['creditCard']['brand'];
        $objPagamento->creditcard_token = $obj['creditCard']['token'];

        $objPagamento->frete_code = $obj['frete']['code'];
        $objPagamento->frete_deadline = $obj['frete']['deadline'];
        $objPagamento->frete_nome = $obj['frete']['name'];
        $objPagamento->frete_preco = round($obj['frete']['price'], 2);

        $objPagamento->pagamento_billing_city = $obj['pagamento']['billing']['city'];
        $objPagamento->pagamento_billing_complement = $obj['pagamento']['billing']['complement'];
        $objPagamento->pagamento_billing_country = $obj['pagamento']['billing']['country'];
        $objPagamento->pagamento_billing_district = $obj['pagamento']['billing']['district'];
        $objPagamento->pagamento_billing_number = $obj['pagamento']['billing']['number'];
        $objPagamento->pagamento_billing_postalcode = $obj['pagamento']['billing']['postalCode'];
        $objPagamento->pagamento_billing_state = $obj['pagamento']['billing']['state'];
        $objPagamento->pagamento_billing_street = $obj['pagamento']['billing']['street'];

        $objPagamento->pagamento_creditcard_maxinstallmentnointerest = $obj['pagamento']['creditCard']['maxInstallmentNoInterest'];
        $objPagamento->pagamento_extraamount = $obj['pagamento']['extraAmount'];
        $objPagamento->pagamento_reference = $obj['pagamento']['reference'];
        $objPagamento->pagamento_sender_document_type = $obj['pagamento']['sender']['document']['type'];
        $objPagamento->pagamento_sender_document_value = $obj['pagamento']['sender']['document']['value'];
        $objPagamento->pagamento_sender_email = $obj['pagamento']['sender']['email'];
        $objPagamento->pagamento_sender_nome = $obj['pagamento']['sender']['name'];
        $objPagamento->pagamento_sender_phone_areacode = $obj['pagamento']['sender']['phone']['areaCode'];
        $objPagamento->pagamento_sender_phone_number = $obj['pagamento']['sender']['phone']['number'];

        $objPagamento->pagamento_session = $obj['pagamento']['session'];
        $objPagamento->pagamento_shipping_city = $obj['pagamento']['shipping']['city'];
        $objPagamento->pagamento_shipping_complement = $obj['pagamento']['shipping']['complement'];
        $objPagamento->pagamento_shipping_cost = round($obj['pagamento']['shipping']['cost'],2);
        $objPagamento->pagamento_shipping_country = $obj['pagamento']['shipping']['country'];
        $objPagamento->pagamento_shipping_district = $obj['pagamento']['shipping']['district'];
        $objPagamento->pagamento_shipping_number = $obj['pagamento']['shipping']['number'];
        $objPagamento->pagamento_shipping_postalcode = $obj['pagamento']['shipping']['postalCode'];
        $objPagamento->pagamento_shipping_state = $obj['pagamento']['shipping']['state'];
        $objPagamento->pagamento_shipping_street = $obj['pagamento']['shipping']['street'];
        $objPagamento->pagamento_shipping_type = $obj['pagamento']['shipping']['type'];
        $objPagamento->status = 1;
        $objPagamento->id = $this->insert((array) $objPagamento);
        if((int) $objPagamento->id>0) {
            $return = new \StdClass();
            $return->status = 'sucesso';
            $return->mensagem = 'cadastro realizado com sucesso!';
            $return->data = $objPagamento;
            return $return;
        }else{
            die(var_dump(($objPagamento)));
        }
    }
}

?><?php
