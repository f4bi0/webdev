<?php

class DatasetException extends Exception{}

class Datasets{

    static function select($dataset, $cols){
        $cols = is_string($cols) ? preg_split('/,|;/' ,$cols) : $cols;
        if(!is_array($cols)){
            throw new DatasetException('$cols must be array or csv string');
        }
        $result = array();
        foreach($dataset as $i => $row){
            $tmp_row = array();
            foreach($cols as $col){
                if(!isset($row[$col])){
                    throw new DatasetException("Dataset row doesnt have expected column '$col'");
                }
                $tmp_row[$col] = $row[$col];
            }
            $result[$i] = $tmp_row;
        }
        return $result;
    }

    static function pluck($dataset, $col, $default='__NULL__'){
        $array = array();
        foreach ($dataset as $row) {
            if(!isset($row[$col])){
                if($default==='__NULL__'){
                    throw new DatasetException("Dataset row doesnt have expected col '$col'");
                } else {
                    $row[$col] = $default;
                }
            }
            $array[] = $row[$col];
        }
        return $array;

    }

    static function indexBy($dataset, $col){

    }

    static function groupBy($dataset, $col){

    }

    static function render($dataset, $template, $prefix='%', $sufix=''){
        $output = '';
        foreach($dataset as $row){
            $sub_output = $template;
            foreach($row as $k=>$v){
                $sub_output = str_replace($prefix.$k.$sufix, $v, $sub_output);
            }
            $output.=$sub_output;
        }
        return $output;
    }

    /**
     * @var SQLite3
     */
    static $db;

    static function getDb(){
        if(!self::$db){
            self::$db = new SQLite3(':memory:',SQLITE3_OPEN_READWRITE);
        }
        return self::$db;
    }

    static $tables = array();
    static $last_table = '';
    static function query($query){
        ob_start();
        $result = self::getDb()->query($query);
        $error = ob_get_clean();
        if($error){
            throw new ErrorException("
                Could not run query: $query. $error
            ");
        }
        return $result;
    }

    static function createTable($dataset, $name=null){
        $name = $name ?: 'tmp'.count(self::$tables);
        self::$tables[] = $name;
        self::$last_table = $name;
        $cols = array_keys($dataset[0]);
        $q = "CREATE TABLE $name (";
        foreach($cols as $col){
            $q .= "$col STRING,";
        }
        self::getDb()->exec(rtrim($q,",").")");
        foreach($dataset as $row){
            $cols = implode(',',array_keys($row));
            $vals = "'".implode("','",array_values($row))."'";
            self::getDb()->exec("INSERT INTO $name ($cols) VALUES ($vals)");
        }
        return $name;
    }

    static function queryRows($dataset, $select, $where_etc='', $indexBy=null){
        $table = self::createTable($dataset);
        $result = self::query("SELECT $select FROM $table $where_etc");
        $rows = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)){
            if($indexBy){
                $rows[$row[$indexBy]] = $row;
            } else {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    static function queryRow($dataset, $select, $where_etc='', $strict=true){
        if(!strstr($where_etc,'LIMIT')){
            $where_etc .= ' LIMIT 1';
        }
        $result = self::queryRows($dataset, $select, $where_etc);
        if(count($result)==0){
            if($strict){
                throw new ErrorException("Row not found where $where_etc");
            }
            return $strict;
        }
        return $result[0];
    }

    static function queryValues($dataset, $col, $where_etc=''){
        $result = self::queryRows($dataset, $col, $where_etc);
        $values = array();
        foreach($result as $each){
            $values[] = current($each);
        }
        return $values;
    }

    static function queryValue($dataset, $col, $where_etc='',$strict=true){
        $row = self::queryRow($dataset, $col, $where_etc, $strict);
        if($row){
            return $row[$col];
        }
        return $strict;
    }


}
