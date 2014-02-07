<?php
class DbDriverException extends Exception{};

class DbDriver{

    static $default = '';
    static $configs = array();
    static $instances = array();

    /**
     * @param null|string $id
     * @return DbDriver
     * @throws DbDriverException
     */
    static function get($id=null){
        $id = is_null($id) ? self::$default : $id;
        if(!$id){
            throw new DbDriverException("Config entry not found, no default available.");
        }
        if(!isset(self::$instances[$id])){
            if(!isset(self::$configs[$id])){
                throw new DbDriverException("Config entry not found: $id");
            }
			$class = 'DbDriver';
			if(!empty(self::$configs[$id]['class'])){
				$class = self::$configs[$id]['class'];
				require_once(dirname(__FILE__).'/class.'.$class.'.php');
			}			
            self::$instances[$id] = new $class(self::$configs[$id]);
        }
        return self::$instances[$id];
    }


    /*
     * Funçõess de leitura e escrita de dados em banco mysql
     * utiliza funções mysqli_*
    */

    /**
     * @var mysqli
     */
    var $con = false;
    var $config = array();

    public function __construct($config){
        foreach(array('host','user','pass') as $k){
            if(!isset($config[$k])){
                throw new DbDriverException("Config: missing param '$k'");
            }
        }
        $this->config = $config;
    }

    function connectOnce(){
        if(!$this->con){
            extract($this->config);
            $this->con = mysqli_connect($host, $user, $pass);
            if(!$this->con){
                throw new DbDriverException(mysqli_connect_error());
            }
            mysqli_set_charset($this->con,'utf8');
        }
    }

    // 21/08/2013 - log de queries
    var $logs = array();

    // Todas as funções de leitura&escrita passam por executaQuery
    // Emite ErrorException on failure
    function query($query){
        $this->connectOnce();
        $this->logs[] = $query;
        if(!$res=mysqli_query($this->con, $query)){
            $e = new DbDriverException(mysqli_error($this->con).PHP_EOL.' Query: '.$query);
            throw $e;
        }
        return $res;
    }

    // 27/11/2013
    function fetchRow($rs){
       return mysqli_fetch_assoc($rs);
    }

    function fetchValue($rs){
        if($row = $this->fetchRow($rs)){
           $value = current($row);
            return $value;
        }
    }

    // 24/05/2013 - paginação
    var $page_no;
    var $rows_per_page;

    function setPageNumber($page_no){
        if(!ctype_digit("$page_no")){
            throw new DbDriverException("page_no expected to be digit, value given was: $page_no");
        }
        $this->page_no = $page_no;
        return $this;
    }

    function setRowsPerPage($rows_per_page){
        $this->rows_per_page = $rows_per_page;
        return $this;
    }

    function getCountRows($table, $where='1'){
        return $this->getValue("SELECT COUNT(*) FROM $table WHERE $where");
    }

    function getCountPages($table, $where='1'){
        if(!$this->rows_per_page){
            throw new DbDriverException("Não pode contar número de páginas pois rows_per_page não informado");
        }
        $count_pages = 0;
        $count_rows = $this->getCountRows($table, $where);
        if($count_rows){
            $count_pages = ceil($count_rows / $this->rows_per_page);
        }
        return $count_pages;
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
            $limit = (($this->page_no-1) * $this->rows_per_page) . ", ".$this->rows_per_page;
            $query.=' LIMIT '.$limit;
            // reseta paginação
            $this->page_no = false;
            $this->rows_per_page = false;
        }

        $res = $this->query($query);

        // fetch rows
        $rows = array();
        while($row = mysqli_fetch_assoc($res)){
            // index by col?
            if($indexBy){
                $rows[$row[$indexBy]] = $row;
            } else {
                $rows[] = $row;
            } // end index by col

        } // end fetch rows

        return $rows;

    }



    function getValueById($field, $from, $id){
        $query = "SELECT $field FROM $from WHERE id=$id";
        return $this->getValue($query);
    }

    function getRow($query){
        // 15/08/2013 - enforce LIMIT 1
        if(!stristr($query,'LIMIT')){
            $query.=" LIMIT 1";
        }
        $rows = $this->getRows($query);
        if(count($rows)==0){
            return null; // not found
        }
        return $rows[0];
    }

    // 03/01/2014
    function getRowById($fields, $from, $id){
        $query = "SELECT $fields FROM $from WHERE id=$id";
        return $this->getRow($query);
    }

    // Retorna valor da primeira coluna do primeiro registro encontrado
    // EX: $count = consultaValor('SELECT COUNT(*) FROM...');
    function getValue($query){
        if($row = $this->getRow($query)){
            $val = current($row);
            return $val;
        }
        return null;
    }

    // Retorna vetor numérico
    // Cada elemento é o valor da primeira coluna de cada registro
    // EX: $ids = consultaValores('SELECT id FROM traca.livros');
    function getValues($query){
        $values = array();
        foreach($this->getRows($query) as $row){
            $value = current($row);
            if($k = next($row)){ // 02/01/2014 - tenta indexar pelo segundo valor
                $values[$k] = $value;
            } else {
                $values[] = $value;
            }
        }
        return $values;
    }


    // 18/05/2013 10:12
    function getRowsGroupBy($query, $groupBy){
        $result = array();
        foreach($this->getRows($query) as $row){
            $result[$row[$groupBy]][] = $row;
        }
        return $result;
    }

    // $dados -> vetor associativo
    function addRow($tabela, $dados){
        $colunas = array_keys($dados);
        $valores = array_values($dados);
        foreach($valores as &$valor){
            $valor = $this->prepDbValue($valor);
        }
        $query = "INSERT INTO $tabela (`".implode('`,`',$colunas)."`) VALUES(".implode(',',$valores).")";
        $this->query($query);
        return mysqli_insert_id($this->con);
    }

    function updateRow($tabela, $dados, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $sql_set = "";
        foreach($dados as $k=>$v){
            $sql_set.="`$k`=".$this->prepDbValue($v).",";
        }
        $sql_set = rtrim($sql_set,',');
        $where = ctype_digit("$where") ? 'id='.$where : $where;
        $query = "UPDATE $tabela SET $sql_set WHERE $where LIMIT 1";
        $this->query($query);
    }

    function updateRows($tabela, $dados, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $sql_set = "";
        foreach($dados as $k=>$v){
            $sql_set.="`$k`=".$this->prepDbValue($v).",";
        }
        $sql_set = rtrim($sql_set,',');
        $query = "UPDATE $tabela SET $sql_set WHERE $where";
        $this->query($query);
    }

    function deleteRow($tabela, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $where = ctype_digit("$where") ? 'id='.$where : $where;
        $this->query("DELETE FROM $tabela WHERE $where LIMIT 1");
    }

    function deleteRows($tabela, $where=''){
        if(!$where) throw new DbDriverException('Parâmetro $where está faltando!');
        $this->query("DELETE FROM $tabela WHERE $where");
    }

    function insertId(){
        return mysqli_insert_id($this->con);
    }

    function escape($valor){
        $this->connectOnce();
        return mysqli_real_escape_string($this->con, $valor);
    }

    function prepDbValue($valor){
        $this->connectOnce();
        if(is_int($valor)||is_float($valor)||in_array($valor,array('NOW()'))){
            return $valor;
        }
        return "'".mysqli_real_escape_string($this->con, $valor)."'";
    }

    function __destruct(){
        if($this->con){
            mysqli_close($this->con);
        }
    }
	
	
	// 05/02/2014 - suporte a transações
	
	function begin(){
		$this->query("BEGIN");
		return $this;
	}
	
	function rollback(){
		$this->query("ROLLBACK");
		return $this;
	}
	
	function commit(){
		$this->query("COMMIT");
		return $this;
	}	

}

include_once(dirname(__FILE__) . '/config/DbDriver.php');

