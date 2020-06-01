<?php
namespace Controllers; 
	/*
	Classe cliente
	*/
class Cliente{
	
	private $view;
	private $pdo;
	private $logger;
	private $_table_name = "clientes";

	public function __construct($view, $pdo, $logger)
	{
		$this->view = $view;
		$this->pdo = $pdo;
		$this->logger = $logger;
	}

	public function listAll(){
		return $this->pdo->query("SELECT * FROM {$this->_table_name} ")->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getById($id){
		$sth = $this->pdo->prepare("SELECT * FROM {$this->_table_name} WHERE id = :id");
		$sth->bindValue(':id',$id);
		$sth->execute();
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function insert($data){
		try { 
			if(empty($data)) return false;
			$keys = array_keys($data); 
			$sth = $this->pdo->prepare("INSERT INTO {$this->_table_name} (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")");
			foreach ($data as $key => $value) {
				$sth ->bindValue(':'.$key,$value);
			}
			$sth->execute();
			return $this->pdo->lastInsertId(); 
		} catch(PDOExecption $e) { 
			return false;
		} 
	}
	
	public function update($id, $data){
		try { 
			if(empty($data)) return false;
			$sets = [];
			foreach ($data as $key => $VALUES) {
				$sets[] = $key." = :".$key;
			}
			$sth = $this->pdo->prepare("UPDATE {$this->_table_name} SET ".implode(',', $sets)." WHERE id = :id");
			$sth ->bindValue(':id',$id);
			foreach ($data as $key => $value) {
				$sth ->bindValue(':'.$key,$value);
			}
			return (array)['status'=>$sth->execute()==1]; 
		} catch(PDOExecption $e) { 
			return false;
		} 
	}

	public function delete($id){
		global $app;
		$sth = $this->pdo->prepare("DELETE FROM {$this->_table_name} WHERE id = :id");
		$sth->bindValue(':id',$id);
		return ["data"=>['status'=>$sth->execute()==1]];
	}
}

?>