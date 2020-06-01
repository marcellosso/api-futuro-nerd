<?php
namespace Controllers;
/*
Classe Jogo
*/
class Jogo{

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
	 * Cadastra Jogada do filho
	 */
	public function cadastra($data){
		$keys = array_keys($data); //Paga as chaves do array
		$sth = $this->pdo->prepare("INSERT INTO pgtas_respondidas (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")");
		foreach ($data as $key => $value) {
			$sth->bindValue(':'.$key,$value);
		}
		$sth->execute();
		return ["data"=>['id'=>$this->pdo->lastInsertId()]];
	}

	/**
	 * aumenta pontuacao filho
	 */
	public function cadastraPTS($filho_id){
		$sth = $this->pdo->prepare("UPDATE clientes_filhos SET pts= pts+10 WHERE id = :id");
		$sth->bindValue(':id',$filho_id);
		return ["data"=>['status'=>$sth->execute()==1]];
	}
	
}
?>