<?php

require_once(dirname(__FILE__).'/class.DbDriver.php');

class DbDriverPostgre extends DbDriver{

    /**
     * @var resource
     */
    var $con = false;
    var $config = array();

    public function __construct($config){
        foreach(array('host','user','pass','dbname') as $k){
            if(!isset($config[$k])){
                throw new DbDriverException("Config: missing param '$k'");
            }
        }
        $this->config = $config;
    }

    function connectOnce(){
        if(!$this->con){
            extract($this->config);
            $this->con = pg_connect("host=$host user=$user password=$pass dbname=$dbname");
            if(!$this->con){
                throw new DbDriverException(pg_last_error());
            }			
            pg_set_client_encoding($this->con,'utf8');
        }
    }

    // 21/08/2013 - log de queries
    var $logs = array();

    // Todas as funções de leitura&escrita passam por executaQuery
    // Emite ErrorException on failure
    function query($query){
        $this->connectOnce();
        $this->logs[] = $query;
        if(!$res=pg_query($this->con, $query)){
            $e = new DbDriverException(pg_last_error($this->con).PHP_EOL.' Query: '.$query);
            throw $e;
        }
        return $res;
    }

    // 27/11/2013
    function fetchRow($rs){
        return pg_fetch_assoc($rs);
    }

    function getRows($query, $indexBy=null){

        // 24/05/2013 - paginação
        if($this->page_no||$this->rows_per_page){
            if(!$this->page_no||!$this->rows_per_page){
                throw new DbDriverException("Paginação mal setada: faltando page_no OU rows_per_page");
            }
            if(strstr($query, 'LIMIT')){
                throw new DbDriverException("Paginação não pode ser integrada pois a query já tem um LIMIT: $query");
            }
            $limit = $this->rows_per_page.' OFFSET '.(($this->page_no-1) * $this->rows_per_page);
            $query.=' LIMIT '.$limit;
            // reseta paginação
            $this->page_no = false;
            $this->rows_per_page = false;
        }

        $res = $this->query($query);

        // fetch rows
        $rows = array();
        while($row = $this->fetchRow($res)){
            // index by col?
            if($indexBy){
                $rows[$row[$indexBy]] = $row;
            } else {
                $rows[] = $row;
            } // end index by col

        } // end fetch rows

        return $rows;

    }

    // $dados -> vetor associativo
    function addRow($tabela, $dados){
        $colunas = array_keys($dados);
        $valores = array_values($dados);
        foreach($valores as &$valor){
            $valor = $this->prepDbValue($valor);
        }
        $query = "INSERT INTO $tabela (\"".implode('","',$colunas)."\") VALUES(".implode(',',$valores).") RETURNING id; ";
        $rs = $this->query($query);
		return $this->fetchValue($rs);
    }

    function updateRow($tabela, $dados, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $sql_set = "";
        foreach($dados as $k=>$v){
            $sql_set.="\"$k\"=".$this->prepDbValue($v).",";
        }
        $sql_set = rtrim($sql_set,',');
        $where = ctype_digit("$where") ? 'id='.$where : $where;
        $query = "UPDATE $tabela SET $sql_set WHERE id IN (SELECT id FROM $tabela WHERE $where LIMIT 1)";
        $this->query($query);
    }

    function deleteRow($tabela, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $where = ctype_digit("$where") ? 'id='.$where : $where;
        $this->query("DELETE FROM $tabela WHERE id IN (SELECT id FROM $tabela WHERE $where LIMIT 1)");
    }	
	
    function updateRows($tabela, $dados, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $sql_set = "";
        foreach($dados as $k=>$v){
            $sql_set.="\"$k\"=".$this->prepDbValue($v).",";
        }
        $sql_set = rtrim($sql_set,',');
        $query = "UPDATE $tabela SET $sql_set WHERE $where";
        $this->query($query);
    }

    function escape($valor){
        $this->connectOnce();
        return pg_escape_string($this->con, $valor);
    }

    function prepDbValue($valor){
        $this->connectOnce();
        if(is_int($valor)||is_float($valor)||in_array($valor,array('NOW()'))){
            return $valor;
        }
        return "'".pg_escape_string($this->con, $valor)."'";
    }

    function __destruct(){
        if($this->con){
            pg_close($this->con);
        }
    }

}