<?php

namespace Controllers;
/**
 * Classe de Produtos
 *
 */
class Produtos
{
    private $view;
    private $pdo;
    private $logger;
    private $_table_name = "produtos";

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
            foreach ($data as $key => $value) {
                $sth->bindValue(':' . $key, $value);
            }
            $sth->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOExecption $e) {
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
            return (array)['status' => $sth->execute() == 1];
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
						cp.categoria AS cp_categoria, 
						ppp.status AS status_pagamento
				FROM {$this->_table_name} p JOIN prods_pedidos pp ON p.id = pp.id_produto
						JOIN clientes_filhos cf ON pp.id_filho = cf.id
						JOIN categoria_prod cp ON cp.id = p.id_categoria
						LEFT JOIN pagamento_prods_pedidos ppp ON ppp.id_prods_pedidos = pp.id
				WHERE pp.id_filho = {$id_filho} GROUP BY p.id ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosSolicitadosPorPai()
    {
//        $id_pai = addslashes($id_pai);
        /*
         * p.id AS  p_id,
		 			    p.nome_produto AS p_nome_produto,
//						p.preco AS p_preco,
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
						cp.categoria AS cp_categoria,
						cp2.nome AS cp2_nome*/
        $sql = " 	SELECT  
 	                    p.id AS  p_id,
		 			    p.nome_produto AS p_nome_produto,
						p.id_categoria AS p_id_categoria,
						pp.id AS pp_id,
						pp.status_pedido AS pp_status_pedido,
						ppp.status AS ppp_status_pagamento,
						date_format(pp.data_pedido, '%d/%m/%Y') AS pp_data_pedido,
						cf.nome AS cf_nome, 
						cp.categoria AS cp_categoria,
						cp2.nome AS cp2_nome
				FROM {$this->_table_name} p JOIN prods_pedidos pp ON p.id = pp.id_produto
						JOIN clientes_filhos cf ON pp.id_filho = cf.id
						JOIN categoria_prod cp ON cp.id = p.id_categoria
						JOIN clientes_pais cp2 on cf.id_pai = cp2.id
						JOIN pagamento_prods_pedidos ppp ON pp.id = ppp.id_prods_pedidos
				 GROUP BY p.id"; //WHERE pp.id_filho IN (SELECT id FROM clientes_filhos WHERE id_pai = {$id_pai})
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function getPedidoById($pp_id)
    {

        $sql = " 	SELECT  
 	                    p.id AS  p_id,
		 			    p.nome_produto AS p_nome_produto,
						p.id_categoria AS p_id_categoria,
						pp.id AS pp_id,
						pp.status_pedido AS pp_status_pedido,
						ppp.status AS ppp_status_pagamento,
						date_format(pp.data_pedido, '%d/%m/%Y') AS pp_data_pedido,
						cf.nome AS cf_nome, 
						cp.categoria AS cp_categoria,
						cp2.nome AS cp2_nome, 
						ppp.*
				FROM {$this->_table_name} p JOIN prods_pedidos pp ON p.id = pp.id_produto
						JOIN clientes_filhos cf ON pp.id_filho = cf.id
						JOIN categoria_prod cp ON cp.id = p.id_categoria
						JOIN clientes_pais cp2 on cf.id_pai = cp2.id
						JOIN pagamento_prods_pedidos ppp ON pp.id = ppp.id_prods_pedidos
				WHERE pp.id = :id
				 GROUP BY p.id";

        $sth = $this->pdo->prepare($sql);
        $sth->bindValue(':id', $pp_id);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $data)
    {
        $status_pedido = $this->updateStatusPedido($id, $data["status_pedido"]);
        $status_pagamento = $this->updateStatusPagamento($id, $data["status_pagamento"]);
        return $status_pedido && $status_pagamento;
    }

    private function updateStatusPedido($id, $id_status_pedido){
        $sth = $this->pdo->prepare("UPDATE prods_pedidos SET status_pedido = :id_status_pedido WHERE id = :id");
        $sth->bindValue(':id', $id);
        $sth->bindValue(':id_status_pedido', $id_status_pedido);
        return (array)['status' => $sth->execute() == 1];
    }

    private function updateStatusPagamento($id, $id_status_pagamento){
        $sth = $this->pdo->prepare("UPDATE pagamento_prods_pedidos SET status = :id_status_pagamento WHERE id_prods_pedidos = :id");
        $sth->bindValue(':id', $id);
        $sth->bindValue(':id_status_pagamento', $id_status_pagamento);
        return (array)['status' => $sth->execute() == 1];
    }

    public function deletePedido($id)
    {
        global $app;
        $sth = $this->pdo->prepare("DELETE FROM prods_pedidos WHERE id = :id");
        $sth->bindValue(':id', $id);
        return ["data" => ['status' => $sth->execute() == 1]];
    }

}

?>
