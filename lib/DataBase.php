<?php
class DataBase {
  private $host = "";
  private $user = "";
  private $pass = "";
  private $database = "";

  protected $db;
  protected $stmt;
  private $error;
  private $last_id;

  public function __construct($database = ""){
    $this->db = new mysqli($this->host, $this->user, $this->pass, $database);
    $this->database = $database;
    $this->db->set_charset("utf8");
  }

  /**
   * Funzione che esegue e restituisce risultati di una query
   * @param String $query
   * @param String|null $types   Lista dei tipi dei parametri
   * @param Array|null $values   Array contenente i valori
   */
  public function MyExecute(String $query, String $types = NULL, Array $values = NULL, $NoResultSet = false){
    try {
      $this->error = null;
      if($types != null && $values != null){
        // Volontà delprogrammatore è eseguire la Query attraverso le preparated
        if(strlen($types) != count($values)) {
          // Un errore è che il programmatore abbia inserito un numero di tipi diverso da quello di valori
          $this->error = "Il numero di tipi passati è diverso dal numero di valori passati. Controlla la chiamata!";
          $this->ShowError();
          return;
        }
        $this->MyPrepare($query) or die('Unable to prepare MySQL statement (check your syntax) - ' . $this->db->error);
        $this->MyBind($types, $values);
        $this->stmt->execute() or die('Unable to execute MySQL statement - ' . $this->db->error);
        if($NoResultSet === false){
          $result = $this->getResult($this->stmt->get_result());
          $this->stmt->close();
          if(empty($result)) return false;
          return $result;
        }
        $this->stmt->close();
        $this->last_id = $this->db->insert_id;
      } else {
        // Esegue la query senza contare la prepare
        return $this->MyQuery($query, $NoResultSet);
      }
    } catch (mysqli_sql_exception | Error $e){
        $this->error = $query." ".$e;
        return;
    }
  }

  public function GetLastId(){
      return $this->last_id;
  }

  public function ShowError(){
    return $this->error;
  }

  public function close(){
    $this->db->close();
  }
  /**
   * [Effettua il prepare della query]
   * @param  String $sql [Codice SQL da eseguire]
   * @return mysqli_stmt|false
   */
  private function MyPrepare(String $sql){
    $this->stmt = $this->db->prepare($sql);
    return $this->stmt;
  }

  /**
   * [Effettua il bind della query]
   * @param  String $types  [i: int, d: double, s: string, b: blob]
   * @param  Array  $values [Array contenete i valori che andranno a inserirsi nella query]
   * @return boolean
   */
  private function MyBind(String $types, Array $values){
    $bind_names[0] = &$types;
    for($i=1; $i<=count($values); $i++){
      $bind_names[$i] = &$values[$i-1];
    }
    call_user_func_array(array($this->stmt, 'bind_param'), $bind_names);
  }

  /**
   * @return Array|boolean
   */
  private function getResult(mysqli_result $result){
    $array = array();
    while($row = $result->fetch_assoc()){
      array_push($array, $row);
    }
    return $array;
  }

  /**
   * [Esequzione Query SQL non preparata]
   * @param  String $sql  [Istruzione Query SQL]
   * @return Array|boolean
   */
  private function MyQuery(String $sql, Bool $NoResultSet){
    if($NoResultSet) return $this->db->query($sql);
    return $this->getResult($this->db->query($sql));
  }
}

?>
