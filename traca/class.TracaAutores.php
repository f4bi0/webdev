<?php

class TracaAutores{

    public static function extraiLetrasIniciais($autor)
    {
        $normaliza_spaco = function($string)
        {

            $patterns = array("/\s+/", "/\s([?.!])/");
            $replacer = array(" ", "$1");
            return preg_replace($patterns, $replacer, $string);
        };

        $remove_from_array = function($array, $value)
        {

            while (in_array($value, $array)) {
                $i = array_search($value, $array);
                unset($array[$i]);

            }
            return $array;
        };

        $palavras_negadas = array(
            "coautor",
            "org",
            "organização",
            "adaptação",
            "adapt",
            "coord",
            "coordenação",
            "trad",
            "tradução",
            "tradutor",
            "coaut"

        );

        $autor = $normaliza_spaco(strtolower($autor));
        $autor = preg_replace("/(^[eE]\s|[^a-zA-Z\s.])/", '', $autor);
        $splited = explode(" ", $autor);
        foreach($palavras_negadas as $k=>$v){
            $splited = $remove_from_array($splited,$v);
        }
        if (in_array("e", $splited)) {
            $new_splited = $remove_from_array($splited, "e.");
            $values = array_values($new_splited);
            //print_r($values);
            $pos = array_search("e", $values);
            $last = $values[$pos - 1];
            //echo "<br>===>".$last[0]."<br>";
            if (strstr($last, ".") !== false) {
                if (count($values) > 2) {
                    return $values[$pos - 2][0].$values[$pos - 2][1];
                } else {
                    return $last[0].$last[1];
                }
            } else {
                return $last[0].$last[1];
            }

        } else {
            $last = end($splited);
            reset($splited);
            if (strlen($last) >= 2) {
                return $last[0].$last[1];
            } elseif (strstr($last, ".") !== false) {

                $splited_count = count($splited);
                if ($splited_count > 2) {
                    return $splited[$splited_count - 2][0].$splited[$splited_count - 2][1];
                } else {
                    return $last[0].$last[1];
                }
            }
        }
    }

}