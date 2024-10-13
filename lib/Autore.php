<?php
require_once (__DIR__."/DataBase.php");
require_once (__DIR__."/TabellaDataBase.Model.php");

class Autore extends TabellaDataBase {
    public ?int $Id;
    public ?string $Username;

    public function __construct($Id = null){
        if(!is_null($Id)){
            $this->Id = $Id;
            $db = new DataBase();
            $query = "SELECT Username
                      FROM Autore
                      WHERE IdAutore = ?";
            $result = $db->MyExecute($query, "i", [$this->Id]);
            if(!$result){
                $this->Username = $result[0]["Username"];
            }
        } else {
            $this->Id = null;
            $this->Username = null;
        }
    }

    public function save(): Autore {
        if(is_null($this->Username))
            throw new InvalidArgumentException("Stai inserendo un Autore con Username vuoto");

        $db = new DataBase();
        if(is_null($this->Id)){
            // Inserimento nel database
            $query = "INSERT INTO Autore(Username) VALUES (?)";
            $db->MyExecute($query, "s", [$this->Username], true);
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
            $this->Id = $db->GetLastId();
        } else {
            // Modifica del database
            $query = "UPDATE Autore
                      SET Username = ?
                      WHERE IdAutore = ?";
            $db->MyExecute($query, "si", [$this->Username, $this->Id], true);
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
        }
        return $this;
    }

    public function remove(int $Id): void {
        if(is_null($Id))
            throw new InvalidArgumentException("Non hai inserito l'id dell'utente da eliminare");

        parent::setNomeTabella("Autore");
        parent::removeRow($Id);
    }

    public static function getByUsername(string $username) : ?Autore {
        $db = new DataBase();
        $query = "SELECT IdAutore
                  FROM Autore
                  WHERE Username = ?";
        $result = $db->MyExecute($query, "s", [$username]);
        if($result){
            return new Autore($result[0]["IdAutore"]);
        } else return null;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->Id;
    }

    /**
     * @param int $Id
     */
    public function setId(int $Id): void {
        $this->Id = $Id;
    }

    /**
     * @return String
     */
    public function getUsername(): string {
        return $this->Username;
    }

    /**
     * @param String $Username
     */
    public function setUsername(string $Username): void {
        $this->Username = $Username;
    }


}