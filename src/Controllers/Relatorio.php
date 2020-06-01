<?php
namespace Controllers;
/*
 * Classe Relatorio
 */

use DateTime;

class Relatorio{

	private $view;
	private $pdo;
	private $logger;
	private $data_atual;

	public function __construct($view, $pdo, $logger)
	{
		$this->view = $view;
		$this->pdo = $pdo;
		$this->logger = $logger;
		$this->data_atual = date('Y-m-d');
	}

	public function get_Pai_EstatisticaFilhos_Atual(){

	    //$this->data_atual = '2019-09-07'; // Alterado - Para realizar os testes
        echo $this->data_atual;
	    $_P_F = Array();
	    $qry_tempo_ativo = $this->pdo->query("SELECT * FROM tempo_ativo_filho WHERE data = DATE('".$this->data_atual."')");
        $Q_S1 = (empty($qry_tempo_ativo))? array() : $qry_tempo_ativo->fetchAll(\PDO::FETCH_ASSOC);

	    foreach($Q_S1 as $K => $R1){

	        echo '#'.$K.'<br/>';

	        // Dados do Filho e Dados Pai para Relação única
            $Q_S2 = $this->pdo->query("SELECT 
                                        CF.id AS id_filho,
                                        CF.nome AS filho_nome,
                                        CF.email AS filho_email,
                                        CF.pts AS filho_pontos,
                                        CF.id_serie AS filho_serie,
                                        CP.id AS id_pai,
                                        CP.nome AS pai_nome,
                                        CP.email AS pai_email,
                                        CP.plano AS pai_plano
                                        FROM clientes_filhos AS CF
                                        INNER JOIN clientes_pais AS CP ON (CP.id = CF.id_pai)
                                        WHERE CF.id = ".$R1['id_filho'])->fetchAll(\PDO::FETCH_ASSOC);



            // --- Acertos
            $Q_S3_acertos = $this->pdo->query("SELECT 
                                        PG_R.*,
                                        M.materia 
                                        FROM pgtas_respondidas AS PG_R 
                                        INNER JOIN materias AS M ON (M.id = PG_R.id_materia)
                                        WHERE PG_R.id_filho = ".$R1['id_filho']." 
                                            AND DATE_FORMAT(PG_R.data,'%Y-%m-%d') = '".$this->data_atual."' 
                                            AND PG_R.correto = 1
                                            #AND PG_R.id_questao NOT IN (SELECT pr2.id_questao FROM pgtas_respondidas pr2 WHERE pr2.correto = 0 AND pr2.id_filho = PG_R.id_filho)
                                            GROUP BY id_filho, id_questao, id_materia, id_serie;")->fetchAll(\PDO::FETCH_ASSOC);

            // --- Erros
            $Q_S3_erros = $this->pdo->query("SELECT 
                                        PG_R.*,
                                        M.materia 
                                        FROM pgtas_respondidas AS PG_R 
                                        INNER JOIN materias AS M ON (M.id = PG_R.id_materia)
                                        WHERE PG_R.id_filho = ".$R1['id_filho']." 
                                            AND DATE_FORMAT(PG_R.data,'%Y-%m-%d') = '".$this->data_atual."' 
                                            AND PG_R.correto = 0
                                            AND PG_R.id_questao NOT IN (SELECT pr2.id_questao FROM pgtas_respondidas pr2 WHERE pr2.correto = 1 AND pr2.id_filho = PG_R.id_filho)
                                            GROUP BY id_filho, id_questao, id_materia, id_serie;")->fetchAll(\PDO::FETCH_ASSOC);
            
            // 1 Registro - Aproveitando a Query
            foreach ($Q_S2 as $R2){

                if(!isset($_P_F[$R2['id_pai']])){
                    $_P_F[$R2['id_pai']]['data_atual']  = date('d/m/Y', strtotime($this->data_atual));
                    $_P_F[$R2['id_pai']]['pai_nome']    = $R2['pai_nome'];
                    $_P_F[$R2['id_pai']]['pai_email']   = $R2['pai_email'];
                    $_P_F[$R2['id_pai']]['pai_plano']   = $R2['pai_plano'];
                }
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['filho_nome']      = $R2['filho_nome'];
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['filho_email']     = $R2['filho_email'];
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['filho_pontos']    = $R2['filho_pontos'];
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['filho_serie']     = $R2['filho_serie'];
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['tempo']           = $R1['tempo'];
                $D = new DateTime($R1['tempo']);
                $Minutos = ( $D->format('H') * 60 ) + $D->format('i');
                $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['tempo_minutos']   = ($Minutos?$Minutos:0);

                if(!empty($Q_S3_acertos)){
                    foreach($Q_S3_acertos as $K => $R3){
                        $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['titulo']  = $R3['materia'];
                        if($K == 0){
                            $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['acertos'] = 0;
                        }
                        $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['acertos'] += 1;
                    }
                }

                if(!empty($Q_S3_erros)){
                    foreach($Q_S3_erros as $K => $R3){
                        $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['titulo'] = $R3['materia'];
                        if($K == 0){
                            $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['erros'] = 0;
                        }
                        $_P_F[$R2['id_pai']]['filhos'][$R2['id_filho']]['materias'][$R3['id_materia']]['erros']  += 1;
                    }
                }

            }



            //print_r($Q_S2);
        }

        //print_r($_P_F);
        return $_P_F;

	}

}

?>