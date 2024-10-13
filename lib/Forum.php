<?php
require_once (__DIR__."/DataBase.php");
require_once (__DIR__."/TabellaDataBase.Model.php");

class Forum extends TabellaDataBase {
    public ?int $Id;
    public ?string $Nome;

    public function __construct($Id = null){
        if(!is_null($Id)){
            $this->Id = $Id;
            $db = new DataBase();
            $query = "SELECT Nome
                      FROM Forum
                      WHERE IdForum = ?";
            $result = $db->MyExecute($query, "i", [$this->Id]);
            if(!$result){
                $this->Nome = $result[0]["Nome"];
            }
        } else {
            $this->Id = null;
            $this->Nome = null;
        }
    }

    public function save(): Forum {
        if(is_null($this->Nome))
            throw new InvalidArgumentException("Stai inserendo un Autore con Username vuoto");

        $db = new DataBase();
        if(is_null($this->Id)){
            // Inserimento nel database
            $query = "INSERT INTO Forum(Nome) VALUES (?)";
            $db->MyExecute($query, "s", [$this->Nome], true);
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
            $this->Id = $db->GetLastId();
        } else {
            // Modifica del database
            $query = "UPDATE Forum
                      SET Nome = ?
                      WHERE IdForum = ?";
            $db->MyExecute($query, "si", [$this->Nome, $this->Id], true);
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
        }
        return $this;
    }

    public function remove(int $Id): void {
        if(is_null($Id))
            throw new InvalidArgumentException("Non hai inserito l'id del forum da eliminare");

        parent::setNomeTabella("Forum");
        parent::removeRow($Id);
    }
    
    public static function getByNome(string $nome) : ?Forum {
        $db = new DataBase();
        $query = "SELECT IdForum
                  FROM Forum
                  WHERE Nome = ?";
        $result = $db->MyExecute($query, "s", [$nome]);
        if($result){
            return new Forum($result[0]["IdForum"]);
        } else return null;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->Id;
    }

    /**
     * @param int $Id
     */
    public function setId(int $Id): void
    {
        $this->Id = $Id;
    }

    /**
     * @return string
     */
    public function getNome(): string
    {
        return $this->Nome;
    }

    /**
     * @param string $Nome
     */
    public function setNome(string $Nome): void
    {
        $this->Nome = $Nome;
    }
}