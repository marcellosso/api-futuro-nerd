<?php 
namespace Controllers;

/*
Classe Loja
*/
class Loja{
    private $view;
	private $pdo;
	private $logger;

	public function __construct($view, $pdo, $logger)
	{
		$this->view = $view;
		$this->pdo = $pdo;
		$this->logger = $logger;
	}
        
    /*
    nova post
    */
    public function solicitar($data){
        $keys = array_keys($data); 
        $sth = $this->pdo->prepare("INSERT INTO prods_pedidos (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")");
        foreach ($data as $key => $value) {
            $sth ->bindValue(':'.$key,$value);
        }
        $sth->execute();
        return ["data"=>['id'=>$this->pdo->lastInsertId()]];
    }

    /*
    Lista
    Listando produtos
    */
    public function verifica($filho,$produto){
        global $app;
        $sth = $this->pdo->prepare("SELECT * FROM prods_pedidos where id_filho = :filho and id_produto = :produto and status_pedido = 1");
        $sth ->bindValue(':filho',$filho);
        $sth ->bindValue(':produto',$produto);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        return ["data"=>$result];
    }

    public function updatePontosAluno($id_filho, $id_produto){
        $sth = $this->pdo->prepare("UPDATE clientes_filhos SET pts = pts - (SELECT preco FROM produtos WHERE id = :id_produto) WHERE id = :id");
        $sth->bindValue(':id', $id_filho);
        $sth->bindValue(':id_produto', $id_produto);
        return (array)['status' => $sth->execute() == 1];
    }
}
?>