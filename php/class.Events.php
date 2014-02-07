<?php

class Events{

    /**
     * @var DbDriver
     */
    public static $db;

    public static function add($id_tipo, $params=array())
    {
        $user_id = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $event_data = array(
            'id_evento' => $id_tipo,
            'data' => date('Y-m-d H:i:s'),
            'idUser' => $user_id
        );
        if(!empty($GLOBALS['transaction_id'])){
            $event_data['trans_id'] = $GLOBALS['transaction_id'];
        }
        $id_disparado = static::$db->addRow('eventos.eventos_disparados',$event_data);
        foreach($params as $k => $v){
            static::$db->addRow('eventos.parametros',
                array(
                    'id_disparado' => $id_disparado,
                    'key' => $k,
                    'value' => $v
                ));
        }
        return $id_disparado;
    }

    /*
     * 18/09/2013 atualizaParams()
     * Passe o id do evento disparado e um array associativo com os parametros
     * Será feito um merge dos params antigos com os novos
    */
    static function updateParams($id, $new_params){
        $db = static::$db;
        $old_params_bykey = $db->getRows("SELECT id,`key`,`value` FROM eventos.parametros WHERE id_disparado=$id",'key');
        $result = array();
        foreach($new_params as $k => $v){
            if(isset($old_params_bykey[$k])){
                $id_param = $old_params_bykey[$k]['id'];
                if($v==$old_params_bykey[$k]['value']) continue;
                $db->updateRow("eventos.parametros",array(
                    'value' => $v
                ),$id_param);
                $result['old_params'][$k] = $old_params_bykey[$k]['value'];
            } else {
                $db->addRow("eventos.parametros",array(
                    'key' => $k,
                    'value' => $v,
                    'id_disparado' => $id
                ));
                $result['new_keys'][] = $k;
            }
        }
        return $result;
    }

    static function fetch($select, $where, $order_by='e.data DESC', $limit=null)
    {

        /* 09:47 08/10/2013 get()

              GERA QUERIES DO TIPO:

              SELECT ed.idUser, ed.data
                FROM eventos_disparados ed
                JOIN parametros p on p.id_disparado = ed.id and p.key = 'tipo_produto'
                JOIN parametros p1 on p1.id_disparado = ed.id and p1.key = 'id_produto'
               WHERE e.id = 15 and p.value = '1' and p1.value = '689162'
               GROUP BY p.id_disparado

              SELECT ed.idUser, ed.data, GROUP_CONCAT(p3.value), GROUP_CONCAT(p3.key)
                FROM eventos_disparados ed
                JOIN parametros p ON p.id_disparado = ed.id AND p.key = 'tipo_produto'
                JOIN parametros p1 ON p1.id_disparado = ed.id AND p1.key = 'id_produto'
                JOIN parametros p3 ON p3.id_disparado = ed.id
               WHERE ed.id_evento = 15 AND p.value = '1' AND p1.value = '689162' AND p3.key IN ('preco_novo')
               GROUP BY p3.id_disparado
               ORDER BY p3.key ASC

           Para a segunda query os parametros concatenados são extraídos do resultado e organizados em k=>v

          */

        $CONCAT_SEPARATOR = '::::';

        $select_evento = array();
        $select_params = array();

        foreach(explode(',',$select) as $field){
            $field = trim($field);
            $explode =  explode(' as ', $field);
            $field = $explode[0];
            $alias = !isset($explode[1]) ? $field : $explode[1];
            if(strstr($field, 'e.')){
                $select_evento[$field] = $alias;
            } else {
                $select_params[$field] = $alias;
            }
        }

        $filter_by_params = array();

        $where = preg_replace_callback('/p.([\w]+)/',function($matches) use(&$filter_by_params){
            $key = $matches[1];
            $filter_by_params[] = $key;
            return 'p'.(count($filter_by_params)-1).'.'.$key;
        }, $where);

        if(strstr($order_by,'p.')){
            $order_by = preg_replace_callback('/p.([\w]+)/',function($matches) use(&$filter_by_params){
                $key = $matches[1];
                if(($index=array_search($key, $filter_by_params))===false){
                    $filter_by_params[] = $key;
                    $index = count($filter_by_params)-1;
                }
                return 'p'.$index.'.'.$key;
            }, $order_by);
        }

        $query = "SELECT ";

        foreach($select_evento as $k => $v){
            if($k==$v){
                $query .= "$k,";
            } else {
                $query .= "$k as $v,";
            }
        }

        if(!empty($select_params)){
            $query.= "GROUP_CONCAT(ps.key SEPARATOR '$CONCAT_SEPARATOR') params_keys, GROUP_CONCAT(ps.value SEPARATOR '$CONCAT_SEPARATOR') params_vals";
        } else {
            $query = rtrim($query,',');
        }

        $query .= " FROM eventos.eventos_disparados e";

        if(!empty($filter_by_params)){
            foreach($filter_by_params as $i => $param_k){
                $query .= " JOIN eventos.parametros p$i ON p$i.id_disparado = e.id AND p$i.key = '$param_k'";
                $where = str_replace("p$i.$param_k","p$i.value",$where);
                $order_by = str_replace("p$i.$param_k","p$i.value",$order_by);
            }
        }

        if(!empty($select_params)){
            $query .= " JOIN eventos.parametros ps ON ps.id_disparado = e.id";
            $where .= " AND ps.key IN (";
            foreach($select_params as $k){
                $where .= "'".substr($k,2)."',";
            }

            $where = rtrim($where,',').')';
        }

        $query .= " WHERE $where";

        if(!empty($select_params)){
            $query .= " GROUP BY ps.id_disparado";
        } else if(!empty($filter_by_params)) {
            $query .= " GROUP BY p0.id_disparado";
        }

        $query .= " ORDER BY $order_by";

        if($limit){
            $query .= " LIMIT $limit";
        }

        $result = self::$db->getRows($query);

        if(!empty($select_params)){
            foreach($result as &$row){
                $keys = explode($CONCAT_SEPARATOR,$row['params_keys']); unset($row['params_keys']);
                $vals = explode($CONCAT_SEPARATOR,$row['params_vals']); unset($row['params_vals']);
                foreach($keys as $i => $k){
                    $row[$k] = $vals[$i];
                }
            }
        }

        return $result;
    }

}

require(dirname(__FILE__).'/config/Events.php');
