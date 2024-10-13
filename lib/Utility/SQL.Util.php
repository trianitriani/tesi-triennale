<?php

class SQLUtil {

    /**
     * @param array $orderByArray
     * @param string $divided
     * @return string
     */
    public static function transformArrayIntoStringDividedBy(array $orderByArray, string $divided = ", "): string{
        $orderBySQL = "";
        // transform an array of order into string SQL languages that can interpreted from MySql to get an order
        for($i=0; $i<count($orderByArray); $i++){
            $orderBySQL .= $orderByArray[$i];
            if($i < (count($orderByArray)-1)) $orderBySQL .= $divided;
        }
        return $orderBySQL;
    }

    private static function transformArrayIntoStringOfWhere(array $arrayWhere){
        $s = self::transformArrayIntoStringDividedBy($arrayWhere, " = ?");
        return $s." = ?";
    }

    /**
     * Return the correct method for represent timestamp in mysql
     * @param string $giorno
     * @param string $ora
     * @return string
     */
    public static function transformIntoTimestamp(string $giorno, string $ora): string {
       return $giorno." ".$ora.":00";
    }

    /**
     * @param string $tabella name of db tables
     * @param array $orderByArray
     * @param string $methodOrderBy
     * @return array|void
     */
    public static function getAllRowsOfTable(string $tabella, array $orderByArray, string $methodOrderBy){
        if($tabella == "") return;
        $orderBySQL = self::transformArrayIntoStringDividedBy($orderByArray);
        $db = new DataBase();
        $query = "SELECT id
                  FROM $tabella
                  ORDER BY $orderBySQL $methodOrderBy";
        $results = $db->MyExecute($query);
        $util = array();
        foreach ($results as $result){
            switch ($tabella){
                case "evento":
                    require_once (__DIR__."/../Model/Evento.Model.php");
                    array_push($util, new Evento($result["id"]));
                    break;
                case "homo":
                    require_once (__DIR__."/../Model/Persona.Model.php");
                    array_push($util, new Persona($result["id"]));
                    break;
                case "ekskurso":
                    require_once (__DIR__."/../Model/Gita.Model.php");
                    array_push($util, new Gita($result["id"]));
                    break;
                case "evento_aro":
                    require_once (__DIR__."/../Model/GruppoEvento.Model.php");
                    array_push($util, new GruppoEvento($result["id"]));
                    break;
                case "chambro":
                    require_once (__DIR__."/../Model/Sala.Model.php");
                    array_push($util, new Sala($result["id"]));
                    break;
                case "ekstera":
                    require_once (__DIR__."/../Model/Esterno.Model.php");
                    array_push($util, new Esterno($result["id"]));
                    break;
            }
        }
        return $util;
    }

    /**
     * @param array $select
     * @param string $table
     * @param array $where
     * @param array $typeWhere
     * @param array $valueWhere
     * @return array
     */
    public static function selectFromTablesWhere(array $select, string $table, array $where, string $typeWhere, array $valueWhere) : bool|array {
        $selectString = self::transformArrayIntoStringDividedBy($select, ", ");
        $whereString = self::transformArrayIntoStringOfWhere($where, " = ?, ");
        $db = new DataBase();
        $query = "SELECT $selectString
                  FROM $table
                  WHERE $whereString";
        $results = $db->MyExecute($query, $typeWhere, $valueWhere);
        if(!$result)
            return false;
        
        if(is_a($db->ShowError(), "mysqli_sql_exception"))
            throw new mysqli_sql_exception($query." ".$db->ShowError()->getMessage());
        
        return $results;
    }
}

?>