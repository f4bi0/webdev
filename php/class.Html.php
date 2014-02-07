<?php

class Html{

    static function array2options($array, $select=null){
        $options = "";
        foreach($array as $option){
            if(is_array($option)){
                if(isset($option['value'])&&isset($option['label'])){
                    $value = $option['value'];
                    $label = $option['label'];
                } else {
                    $value = current($option);
                    $label = next($option);
                }
            } else {
                $value = $option;
                $label = $option;
            }
            $selected = "$select"=="$value" ? ' selected' : '';
            $options .= "<option value=\"$value\"$selected>$label</option>";
        }
        return $options;
    }

    static function assoc2options($array, $select=null){
        $options = "";
        foreach($array as $k=>$v){
            $selected = "$select"=="$v" ? ' selected' : '';
            $options .= "<option value=\"$k\"$selected>$v</option>";
        }
        return $options;
    }

    static function dataset2table($dataset, $head_class="bg-head-dark", $item_class="item"){
        if(empty($dataset)){
            return "";
        }
        $html = "<thead class=\"$head_class\">";
        foreach(array_keys($dataset[0]) as $col){
            $heading = ucwords(str_replace('_',' ',$col));
            $html .= "<th class=\"$col\">$col</th>";
        }
        $html .= "</thead><tbody>";
        foreach($dataset as $row){
            $html .= "<tr class=\"$item_class\">";
            foreach($row as $k => $v){
                $html .= "<td class=\"$k\">$v</td>";
            }
            $html .= "</tr>";
        }
        return $html."</tbody>";
    }

    // 02/12/2013
    static function hidden($name,$value){
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
    }

    // 02/12/2013
    static function assoc2hidden(array $assoc){
        $str = '';
        foreach($assoc as $k => $v){
            $str.=self::hidden($k,$v)."\n";
        }
        return $str;
    }



}
