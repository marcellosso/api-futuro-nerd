<?php
namespace Controllers;
/*
 * Classe Pais
 */
class Pais{

	private $view;
	private $pdo;
	private $logger;
	private $_table_name = "clientes_pais";

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
				$sth ->bindValue(':'.$key,trim($value));
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

	// ##### METODOS DO PAI ######

	/**
	 * Modifica plano pais
	 */
	public function modificaPlano($data){
		$pai = $data['id'];
		$plano = $data['plano'];
		$sth = $this->pdo->prepare("UPDATE clientes_pais SET plano=:plano WHERE id = :id");
		$sth->bindParam(':id', $pai);
		$sth->bindParam(':plano', $plano);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

	/**
	 * Modifica Dados pais
	 */
	public function modifica($id, $data){
		$sets = [];
		foreach ($data as $key => $VALUES) {
			$sets[] = $key." = :".$key;
		}
		$sth = $this->pdo->prepare("UPDATE clientes_pais SET ".implode(',', $sets)." WHERE id = :id");
		$sth ->bindValue(':id',$id);
		foreach ($data as $key => $value) {
			$sth ->bindValue(':'.$key,$value);
		}
		return ["data"=>['status'=>$sth->execute()==1]];
	}

	/*
	Lista Pai unico
	*/
	public function listaPai($data){
		$sth = $this->pdo->prepare("SELECT * FROM clientes_pais WHERE id=:id");
		$sth->bindValue(':id',$data['id']);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

	/*
	Login Pai
	*/
	public function login($data){
		$sth = $this->pdo->prepare("SELECT * FROM clientes_pais WHERE email=:email and senha=:senha");
		$sth->bindParam(':email', $data['email']);
		$sth->bindParam(':senha', $data['senha']);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

	/*
	Cadastra Pai
	*/
	public function cadastra($data){
		$keys = array_keys($data);
		$sth = $this->pdo->prepare("INSERT INTO clientes_pais (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")");
		foreach ($data as $key => $value) {
			$sth ->bindValue(':'.$key, trim($value));
		}
		$sth->execute();
		return ["data"=>['id'=>$this->pdo->lastInsertId()]];
	}

	// ##### METODOS DO FILHO ######

	/*
	Login Filho
	*/
	public function loginFilho($data){
		$sth = $this->pdo->prepare("SELECT * FROM clientes_filhos WHERE email=:email and senha=:senha");
		$sth->bindParam(':email', $data['email']);
		$sth->bindParam(':senha', $data['senha']);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

    /*
    GET Filho By Login Filho
    */
    public function getFilhoByLogin($login){
        $sth = $this->pdo->prepare("SELECT cf.nome, cf.email, cf.senha FROM clientes_filhos cf JOIN clientes_pais cp on cf.id_pai = cp.id WHERE cf.email=:email LIMIT 1");
        $sth->bindParam(':email', $login);
        $sth->execute();
        $resultado = $sth->fetch(\PDO::FETCH_ASSOC);
        return ["data"=>$resultado];
    }

    /*
   GET Filho By Login Filho
   */
    public function getPaiByLogin($login){
        $sth = $this->pdo->prepare("SELECT * FROM clientes_pais WHERE email=:email LIMIT 1");
        $sth->bindParam(':email', $login);
        $sth->execute();
        $resultado = $sth->fetch(\PDO::FETCH_ASSOC);
        return ["data"=>$resultado];
    }

	/**
	 * Modifica filho
	 */
	public function modificaFilho($id, $data){
		$sets = [];
		foreach ($data as $key => $VALUES) {
			$sets[] = $key." = :".$key;
		}
		$sth = $this->pdo->prepare("UPDATE clientes_filhos SET ".implode(',', $sets)." WHERE id = :id");
		$sth ->bindValue(':id',$id);
		foreach ($data as $key => $value) {
			$sth ->bindValue(':'.$key,$value);
		}
		return ["data"=>['status'=>$sth->execute()==1]];
	}

	public function tempoAtivoFilho($id_filho, $tempo_em_segundos = 5){
		$retorno = false;
		$existe_tempo_ativo_filho_corrente = $this->verificaTempoAtivoFilhoExiste($id_filho);

		if($existe_tempo_ativo_filho_corrente){
			$retorno = $this->atualizarTempoAtivoFilhoExiste($id_filho, $tempo_em_segundos);
		}else{
			$retorno = $this->inserirTempoAtivoFilho($id_filho, $tempo_em_segundos);
		}
		return ["data"=>$retorno];
	}

	// # MXTera --
	public function tempoAtivoFilhoTotal($id_filho){
		$sql = "SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(tempo))),'%H:%i') AS tempo_ativo 
				FROM tempo_ativo_filho 
				WHERE id_filho = :id_filho";
		$sth = $this->pdo->prepare($sql);
		$sth->bindParam(':id_filho', $id_filho);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return $resultado['tempo_ativo'];
	}
	//-- #

	protected function verificaTempoAtivoFilhoExiste($id_filho){
		$sql = "SELECT IFNULL(COUNT('x'), 0) AS qtd 
				FROM tempo_ativo_filho
				WHERE id_filho = :id_filho
				AND data = CURDATE();";
		$sth = $this->pdo->prepare($sql);
		$sth->bindParam(':id_filho', $id_filho);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		// var_dump($resultado['qtd']);
		return $resultado['qtd']>0;
	}

	protected function inserirTempoAtivoFilho($id_filho, $tempo_em_segundos = 5){
		$sql = "INSERT INTO tempo_ativo_filho (id_filho, data, tempo) VALUES (:id_filho, CURDATE(), :tempo_em_segundos);";
		$sth = $this->pdo->prepare($sql);
		$sth->bindValue(':id_filho',$id_filho);
		$sth->bindValue(':tempo_em_segundos',$tempo_em_segundos);
		return $sth->execute()==1;
	}

	protected function atualizarTempoAtivoFilhoExiste($id_filho, $tempo_em_segundos = 5){
		$sql = "UPDATE tempo_ativo_filho SET tempo = ADDTIME(tempo, SEC_TO_TIME(:tempo_em_segundos)) WHERE id_filho = :id_filho AND data = CURDATE();";
		$sth = $this->pdo->prepare($sql);
		$sth->bindValue(':id_filho',$id_filho);
		$sth->bindValue(':tempo_em_segundos',$tempo_em_segundos);
		return $sth->execute()==1;
	}





	/*
	Lista filho unico
	*/
	public function listaFilho($data){
		$sth = $this->pdo->prepare("SELECT * FROM clientes_filhos WHERE id=:id");
		$sth->bindValue(':id',$data['id_filho']);
		$sth->execute();
		$resultado = $sth->fetch(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

    public function getPtsFilho($id_filho){
        $sth = $this->pdo->prepare("SELECT pts FROM clientes_filhos WHERE id=:id");
        $sth->bindValue(':id',$id_filho);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

	/*
	lista filhos
	*/
	public function listaFilhos($id_filho){
		$sth = $this->pdo->prepare("SELECT nome,id_pai,id,id_serie FROM clientes_filhos WHERE id_pai = :id");
		$sth ->bindValue(':id',$id_filho);
		$sth->execute();
		$resultado = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$qtd = $sth->rowCount();
		return ["data"=>$resultado];
	}

	/*
	Cadastra Filho
	*/
	public function cadastraFilho($data){
		$keys = array_keys($data);
		$sth = $this->pdo->prepare("INSERT INTO clientes_filhos (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")");
		foreach ($data as $key => $value) {
			$sth->bindValue(':'.$key,trim($value));
		}
		$sth->execute();
		return ["data"=>['id'=>$this->pdo->lastInsertId()]];
	}


	// ##### METODOS DO CLIENTE ######

	/*
	editar
	param $id
	Editando cliente
	*/
	public function editar($id, $data){
		$sets = [];
		foreach ($data as $key => $VALUES) {
			$sets[] = $key." = :".$key;
		}

		$sth = $this->pdo->prepare("UPDATE clientes SET ".implode(',', $sets)." WHERE id = :id");
		$sth->bindValue(':id',$id);
		foreach ($data as $key => $value) {
			$sth->bindValue(':'.$key,$value);
		}
		return ["data"=>['status'=>$sth->execute()==1]];
	}

	/*
	excluir
	param $id
	Excluindo cliente
	*/
	public function excluir($id){
		$sth = $this->pdo->prepare("DELETE FROM clientes WHERE id = :id");
		$sth->bindValue(':id',$id);
		return ["data"=>['status'=>$sth->execute()==1]];
	}

	// ##### METODOS DO PERGUNTAS RESPONDIDAS ######

	/**
	 * pega o total de questoes respondidas por materia e serie
	 */
	public function questoesErradas($filho,$materia,$serie){
		/*$sth = $this->pdo->prepare("SELECT * from pgtas_respondidas AS PG_R
                                    WHERE PG_R.id_filho = :filho and PG_R.id_serie = :serie and PG_R.id_materia = :materia and PG_R.correto = 0 
                                    AND PG_R.id_questao NOT IN (SELECT pr2.id_questao FROM pgtas_respondidas pr2 WHERE pr2.correto = 1 AND pr2.id_filho = PG_R.id_filho)
                                    GROUP BY PG_R.id_filho, PG_R.id_questao, PG_R.id_serie, PG_R.id_materia");*/
		/*$sth = $this->pdo->prepare("SELECT * FROM pgtas_respondidas AS PR
										INNER JOIN questoes AS Q ON (Q.id = PR.id_questao)
										WHERE PR.id_filho = :filho and Q.id_serie = :serie and Q.id_materia = :materia and PR.correto = 0");*/ // # MXTera --
		$sth = $this->pdo->prepare("SELECT * FROM pgtas_respondidas AS PR
										INNER JOIN questoes AS Q ON (Q.id = PR.id_questao)
										WHERE PR.id_filho = :filho and Q.id_serie = :serie and Q.id_materia = :materia and PR.correto = 0
										and NOT EXISTS 
										(SELECT * FROM pgtas_respondidas PR2 WHERE Q.id = PR2.id_questao and PR2.id_filho = :filho AND PR2.correto = 1)
										GROUP BY PR.id_questao");

        $sth ->bindValue(':materia',$materia);
        $sth ->bindValue(':serie',$serie);
        $sth ->bindValue(':filho',$filho);
		$sth ->execute();
		$total = $sth->rowCount();

		return ["total"=>$total];
	}

	/**
	 * pega o total de questoes respondidas por materia e serie - Perguntas, Respostas e Seleção da Resposta
	 */
	public function questoesErradasPergRespSel($filho,$materia,$serie){
		$sth = $this->pdo->prepare("SELECT 
                                    PR.*,
                                    Q.titulo, 
                                    Q.resposta_errada, 
                                    Q.resposta_errada1, 
                                    Q.resposta_errada2, 
                                    Q.resposta_correta
                                    FROM 
                                    pgtas_respondidas AS PR 
                                    INNER JOIN questoes AS Q ON(Q.id = PR.id_questao)
                                    WHERE PR.id_filho = :filho and PR.id_serie = :serie and PR.id_materia = :materia and PR.correto = 0
                                    AND PR.id_questao NOT IN (SELECT pr2.id_questao FROM pgtas_respondidas pr2 WHERE pr2.correto = 1 AND pr2.id_filho = PR.id_filho)
                                    GROUP BY PR.id_filho, PR.id_questao, PR.id_serie, PR.id_materia");
        $sth ->bindValue(':materia',$materia);
        $sth ->bindValue(':serie',$serie);
        $sth ->bindValue(':filho',$filho);
		$sth ->execute();
        $resultado = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return ["data"=>$resultado];
	}

	/**
	 * pega o total de questoes respondidas por materia e serie
	 */
	public function questoesRespondidas($filho,$materia,$serie){
		/*$sth = $this->pdo->prepare("SELECT * from pgtas_respondidas WHERE id_filho = :filho and id_serie = :serie and id_materia = :materia and correto = 1");*/
		$sth = $this->pdo->prepare("SELECT * FROM pgtas_respondidas AS PR
										INNER JOIN questoes AS Q ON (Q.id = PR.id_questao)
										WHERE PR.id_filho = :filho and Q.id_serie = :serie and Q.id_materia = :materia and PR.correto = 1"); // # MXTera --
		$sth ->bindValue(':materia',$materia);
		$sth ->bindValue(':serie',$serie);
		$sth ->bindValue(':filho',$filho);
		$sth ->execute();
		$total = $sth->rowCount();
		return ["total"=>$total];
	}

	// ##### METODOS DE QUESTOES ######
	/**
	 * pega o total de questoes por materia e serie
	 */
	public function totalQuestoes($materia,$serie){
		$sth = $this->pdo->prepare("SELECT * from questoes WHERE id_materia = :materia and id_serie = :serie");
		$sth ->bindValue(':materia',$materia);
		$sth ->bindValue(':serie',$serie);
		$sth ->execute();
		$total = $sth->rowCount();
		return ["total"=>$total];
	}

	public function relatorioQuestoesCompletadas($id_serie, $id_filho){
		$sql = 'SELECT id_materia, materia, SUM(qtd_questoes) AS qtd_questoes, SUM(qtd_respondidas) AS qtd_respondidas FROM
				(
					SELECT q.id_materia, m.materia, count(\'x\') AS qtd_questoes, 0 AS qtd_respondidas   
					FROM questoes q JOIN materias m ON q.id_materia = m.id
					WHERE id_serie = :id_serie
					GROUP BY q.id_materia, m.materia
					UNION ALL
					SELECT pr.id_materia, m.materia, 0 as qtd_questoes, count(\'x\') AS qtd_respondidas 
					FROM pgtas_respondidas pr JOIN materias m ON pr.id_materia = m.id
					WHERE id_filho = :id_filho
					AND id_serie = :id_serie2
					AND correto = 1
					GROUP BY pr.id_materia, m.materia
				) AS x
				GROUP BY id_materia, materia
				ORDER BY materia DESC;';
		$sth = $this->pdo->prepare($sql);
		$sth->bindValue(':id_serie',$id_serie);
		$sth->bindValue(':id_serie2',$id_serie);
		$sth->bindValue(':id_filho',$id_filho);
		$sth->execute();
		$resultado = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return ["data"=>$resultado];
	}

}

?>
