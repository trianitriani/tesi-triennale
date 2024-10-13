<?php
require_once (__DIR__."/DataBase.php");
require_once (__DIR__."/Utility/SQL.Util.php");

class TabellaDataBase {
    protected static string $nomeTabella;

    protected function __construct(string $nomeTabella){
        self::$nomeTabella = $nomeTabella;
    }

    protected static function getAll(array $orderByArray = ["id"], string $methodOrderBy = "ASC"){
        return SQLUtil::getAllRowsOfTable(self::$nomeTabella, $orderByArray, $methodOrderBy);
    }

    protected function removeRow(int $id): void{
        if(!is_null($id)){
            $db = new DataBase();
            $query = "DELETE FROM ".self::$nomeTabella."
                      WHERE id = ?";
            $db->MyExecute($query, "i", [$id], true);
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
        }
    }

    protected static function setNomeTabella(string $nomeTabella){
        self::$nomeTabella = $nomeTabella;
    }
}

?>
