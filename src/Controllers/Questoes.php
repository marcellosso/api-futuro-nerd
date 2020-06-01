<?php 
namespace Controllers;
/*
Classe Questoes
*/
class Questoes{
	private $view;
	private $pdo;
	private $logger;
	private $_table_name = "questoes";

	public function __construct($view, $pdo, $logger)
	{
		$this->view = $view;
		$this->pdo = $pdo;
		$this->logger = $logger;
	}

	public function listAll(){
		//return $this->pdo->query("SELECT * FROM {$this->_table_name} ")->fetchAll(\PDO::FETCH_ASSOC);
		return $this->pdo->query("SELECT {$this->_table_name}.*, series.serie, materias.materia FROM {$this->_table_name} JOIN series ON ( {$this->_table_name}.id_serie = series.id )  JOIN materias ON ( {$this->_table_name}.id_materia = materias.id )")->fetchAll(\PDO::FETCH_ASSOC);
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

	/**
	 * lista questao para responder
	 */
	public function qre($filho,$materia,$serie){
		$sth = $this->pdo->prepare("SELECT * FROM questoes e WHERE e.id_materia = :materia and e.id_serie = :serie and NOT EXISTS (SELECT * FROM pgtas_respondidas r WHERE e.id = r.id_questao and r.id_filho = :filho and r.correto = 1) order by rand() limit 1");
		$sth ->bindValue(':materia',$materia);
		$sth ->bindValue(':serie',$serie);
		$sth ->bindValue(':filho',$filho);
		$sth ->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}
	
	/**
	 * lista questao para responder
	 */
	public function qr($filho,$materia,$serie){
		/*$sth = $this->pdo->prepare("SELECT * FROM questoes e
		WHERE e.id_materia = :materia and e.id_serie = :serie and NOT EXISTS (SELECT * FROM pgtas_respondidas r WHERE e.id = r.id_questao and r.id_filho = :filho) order by rand() limit 1");*/
		$sth = $this->pdo->prepare("SELECT Q.*,PR.correto FROM questoes AS Q
                                    LEFT JOIN pgtas_respondidas AS PR ON (PR.id_questao = Q.id)
                                    WHERE Q.id_materia = :materia AND Q.id_serie = :serie and NOT EXISTS 
                                    (SELECT * FROM pgtas_respondidas PR2 WHERE Q.id = PR2.id_questao AND PR2.id_filho = :filho AND PR2.correto = 1) GROUP BY Q.id order by PR.correto ASC, RAND(); LIMIT 1"); // # MXTera  --
		$sth ->bindValue(':materia',$materia);
		$sth ->bindValue(':serie',$serie);
		$sth ->bindValue(':filho',$filho);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}
}
?>