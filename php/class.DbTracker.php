<?php
class DbTrackerException extends Exception{};
require_once(dirname(__FILE__).'/class.DbDriver.php');

DbTracker::$db = DbDriver::get();

class DbTracker{

    static $instances = array();

    /**
     * @param null|string $id - id da conexao
     * @return DbTracker|DbDriver
     */
    static function get($id=null){
        $id = $id ?: DbDriver::$default;
        if(!isset(self::$instances[$id])){
            self::$instances[$id] = new self(DbDriver::get($id));
            self::$instances[$id]->connect_id = $id;
        }
        return self::$instances[$id];
    }


    static $trans_id;

    /**
     * @var DbDriver
     */
    static $db;
    static $tables = array('transactions','operations','inserts','updates','deletes');

    var $connect_id;

    /**
     * @var DbDriver
     */
    var $dbTarget;

    static function compileDbTrackerTableName($name){
        return 'eventos.dbtracker_'.$name;
    }

    function __construct(DbDriver $dbTarget){
        $this->dbTarget = $dbTarget;
    }

    static $onTransactionId = array();
    static function getTransactionId(){
        if(!self::$trans_id){
            $user_id = null;
            if(!empty($_SESSION['user_id'])){
                $user_id = $_SESSION['user_id'];
            }
            self::$db->addRow(self::compileDbTrackerTableName('transactions'),
                array(
                    'date'=>'NOW()',
                    'request_uri'=>$_SERVER['REQUEST_URI'],
                    'request_vars'=>json_encode(array('POST'=>$_POST,'GET'=>$_GET)),
                    'user_id'=>$user_id
                ));
            self::$trans_id = self::$db->insertId();
            $GLOBALS['transaction_id'] = self::$trans_id;
        }
        return self::$trans_id;
    }

    function addRow($table,$data){
        $trans_id = self::getTransactionId();
        $this->dbTarget->addRow($table,$data);
        $insert_id = $this->dbTarget->insertId();
        self::$db->addRow(self::compileDbTrackerTableName('inserts'),
            array(
                'table' => $table,
                'data' => json_encode($data),
                'insert_id' => $insert_id
            ));
        $op_id = self::$db->insertId();
        self::$db->addRow(self::compileDbTrackerTableName('operations'),
            array(
                'trans_id' => $trans_id,
                'op_id' => $op_id,
                'op_type' => 'insert',
                'connect_id' => $this->connect_id
            ));
        return $insert_id;
    }

    function updateRow($table, $data, $row_id, $strict=true){
        $trans_id = self::getTransactionId();
        $cols = '`'.implode('`,`',array_keys($data)).'`';
        $old_data = $this->dbTarget->getRow("SELECT $cols FROM $table WHERE id=$row_id");
        if($old_data){
            $found = 'Y';
            $this->dbTarget->updateRow($table,$data,'id='.$row_id);
        } else {
            if($strict){ // 25/11/2013
                throw new DbTrackerException("Could not update '$table', row not found: $row_id");
            }
            $found = 'N';
            $old_data = array();
        }
        self::$db->addRow(self::compileDbTrackerTableName('updates'),
            array(
                'table' => $table,
                'data' => json_encode($data),
                'old_data' => json_encode($old_data),
                'row_id' => $row_id,
                'found' => $found
            ));
        $op_id = self::$db->insertId();
        self::$db->addRow(self::compileDbTrackerTableName('operations'),
            array(
                'trans_id' => $trans_id,
                'op_id' => $op_id,
                'op_type' => 'update',
                'connect_id' => $this->connect_id
            ));
    }

    function updateRows($table, $data, $where, $limit=50){
        $ids = $this->dbTarget->getValues("SELECT id FROM $table WHERE $where");
        if(($count=count($ids))>$limit){
            throw new DbTrackerException("Update $table WHERE $where returns too many rows ($count > \$limit = $limit)");
        }
        foreach($ids as $id){
            $this->updateRow($table,$data,$id);
        }
        return $ids;
    }

    function updateRowWhere($table, $data, $where_etc, $strict=true){
        $ids = $this->dbTarget->getValues("SELECT id FROM $table WHERE $where_etc");
        if(count($ids)>1){
            throw new DbTrackerException("Could not update $table WHERE $where_etc: more than one row was found!");
        }
        if(count($ids)==0){
            if($strict){
                throw new DbTrackerException("Could not update '$table', row not found where: $where_etc");
            } else {
                return;
            }
        }
        $this->updateRow($table,$data,$ids[0],$strict);
    }

    function deleteRow($table, $row_id, $strict=false){
        $trans_id = self::getTransactionId();
        $old_data = $this->dbTarget->getRow("SELECT * FROM $table WHERE id=$row_id");
        if($old_data){
            $found = 'Y';
            $this->dbTarget->deleteRow($table,'id='.$row_id);
        } else {
            if($strict){ // 25/11/2013
                throw new DbTrackerException("Could not delete '$table', row not found: $row_id");
            }
            $found = 'N';
            $old_data = array();
        }
        self::$db->addRow(self::compileDbTrackerTableName('deletes'),
            array(
                'table' => $table,
                'old_data' => json_encode($old_data),
                'row_id' => $row_id,
                'found' => $found
            ));
        $op_id = self::$db->insertId();
        self::$db->addRow(self::compileDbTrackerTableName('operations'),
            array(
                'trans_id' => $trans_id,
                'op_id' => $op_id,
                'op_type' => 'delete',
                'connect_id' => $this->connect_id
            ));
    }

    // 06/11/2013
    function deleteRowWhere($tabela, $where_etc, $strict=false){
        $ids = $this->dbTarget->getValues("SELECT ids FROM $tabela WHERE $where_etc");
        if(($count=count($ids))>1){
            throw new DbTrackerException("Could not DELETE FROM $tabela WHERE $where_etc: more than one row returned");
        }
        if($count==0){
            if($strict){
                throw new DbTrackerException("Could not DELETE FROM $tabela WHERE $where_etc: nothing was found");
            } else {
                return;
            }
        }

        $this->deleteRow($tabela, $ids[0]);
        return $ids[0];

    }

    function deleteRows($table, $where_etc, $limit=50){
        $ids = $this->dbTarget->getValues("SELECT id FROM $table WHERE $where_etc");
        if(($count=count($ids))>$limit){
            throw new DbTrackerException("DELETE FROM $table WHERE $where_etc returns too many rows ($count > \$limit = $limit)");
        }
        foreach($ids as $id){
            $this->deleteRow($table,$id);
        }
        return $ids;
    }

    static function undoOperation($id){
        $row = self::$db->getRow("SELECT op_id, op_type, connect_id FROM ".self::compileDbTrackerTableName('operations')." WHERE id=$id");
        if(!$row) return;
        $op_table = self::compileDbTrackerTableName($row['op_type'].'s');
        $op_data = self::$db->getRow("SELECT * FROM $op_table WHERE id=$row[op_id]");
        if(!$op_data) return;
        $dbTarget = DbDriver::get($row['connect_id']);
        switch($row['op_type']){
            case 'insert' :
                $dbTarget->deleteRow($op_data['table'],$op_data['insert_id']);
                break;
            case 'update' :
                if($op_data['found']=='N') return;
                $dbTarget->updateRow($op_data['table'],json_decode($op_data['old_data'],true),'id='.$op_data['row_id']);
                break;
            case 'delete' :
                if($op_data['found']=='N') return;
                $dbTarget->addRow($op_data['table'],json_decode($op_data['old_data'],true),'id='.$op_data['row_id']);
                break;
            case 'select' :
                // can't be undone
                break;
        }
        self::$db->updateRow(self::compileDbTrackerTableName('operations'),array('undone'=>'Y'),'id='.$id);
    }


    static function undoTransaction($trans_id){
        $ops = self::$db->getRows("SELECT id, connect_id FROM ".self::compileDbTrackerTableName('operations')." WHERE trans_id=$trans_id ORDER BY id DESC");
        foreach($ops as $op){
            self::undoOperation($op['id']);
        }
        self::$db->updateRow(self::compileDbTrackerTableName('transactions'),array('undone'=>'Y'),$trans_id);
    }

    static function getTransactionData($trans_id){
        $trans = self::$db->getRow('SELECT * FROM '.self::compileDbTrackerTableName('transactions').' WHERE id='.$trans_id);
        $trans['operations'] = array();
        $rows = self::$db->getRows('SELECT * FROM '.self::compileDbTrackerTableName('operations').' WHERE trans_id='.$trans_id);
        foreach($rows as $row){
            $op_table = self::compileDbTrackerTableName($row['op_type'].'s');
            $op_meta = self::$db->getRow("SELECT * FROM $op_table WHERE id=$row[op_id]");
            if(!empty($op_meta['data'])){
                $op_meta['data'] = json_decode($op_meta['data'],true);
            }
            if(!empty($op_meta['old_data'])){
                $op_meta['old_data'] = json_decode($op_meta['old_data'],true);
            }
            $row['meta'] = $op_meta;
            $trans['operations'][] = $row;
        }
        return $trans;
    }

    static function getTransactionsByDate($date=null){
        $date = $date ? $date : date('Y-m-d');
        return self::$db->getRows("
			SELECT * FROM ".self::compileDbTrackerTableName('transactions')
            ." WHERE date LIKE  '$date%' ORDER BY date DESC
		");
    }

    static function compileDbTrackerQuery($query){
        foreach(self::$tables as $table){
            $query = str_replace($table, self::compileDbTrackerTableName($table), $query);
        }
        return $query;
    }

    function __call($method, $args){
        if(!method_exists($this->dbTarget,$method)){
            throw new ErrorException('DbTracker says: call to undefined dbTarget method: '.$method);
        }
        return call_user_func_array(array($this->dbTarget,$method), $args);
    }



}


?>