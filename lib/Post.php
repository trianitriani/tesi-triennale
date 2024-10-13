<?php
require_once (__DIR__."/DataBase.php");
require_once (__DIR__."/TabellaDataBase.Model.php");

class Post extends TabellaDataBase {
    public ?int $Id;
    public ?string $Data;
    public ?string $Testo;
    public ?Autore $Autore;
    public ?Forum $Forum;
    public ?Post $Discussione;

    public function __construct($Id = null){
        if(!is_null($Id)){
            $this->Id = $Id;
            $db = new DataBase();
            $query = "SELECT *
                      FROM Post
                      WHERE IdPost = ?";
            $result = $db->MyExecute($query, "i", [$this->Id]);
            if(!$result){
                $this->Data = $result[0]["Data"];
                $this->Testo = $result[0]["Testo"];
                $this->Autore = new Autore($result[0]["FkAutore"]);
                $this->Forum = new Forum($result[0]["FkForum"]);
                $this->Discussione = new Post($result[0]["FkDiscussione"]);
            }
        } else {
            $this->Id = null;
            $this->Data = null;
            $this->Testo = null;
            $this->Autore = null;
            $this->Forum = null;
            $this->Discussione = null;
        }
    }
    
    public function save(string $UsernameAutore, string $NomeForum): Post {
        if(is_null($this->Data) || is_null($this->Testo))
            throw new InvalidArgumentException("Stai inserendo informazioni nulle relative ad un post");

        $db = new DataBase();
        if(is_null($this->Id)){
            // Inserimento nel database

            // Inserire Autore se non è messo già nel db
            if(is_null(Autore::getByUsername($UsernameAutore))){
                // Allora inseriscilo
                $this->Autore->Username = $UsernameAutore;
                $this->Autore->save();
            } else {
                $this->setAutore(Autore::getByUsername($UsernameAutore));
            }
            
            // Inserire Forum se non è messo già nel db
            if(is_null(Forum::getByNome($NomeForum))){
                // Allora inseriscilo
                $this->Forum->Nome = $NomeForum;
                $this->Forum->save();
            } else {
                $this->setForum(Forum::getByNome($NomeForum));
            }
            
            // Inserire Post se non è già messo nel db
            $query = "CALL InserisciPost(?, ?, ?, ?, ?)";
            $result = $db->MyExecute($query, "ssiii", [$this->Data, $this->Testo, $this->Autore->Id, $this->Forum->Id, (!is_null($this->Discussione)) ? $this->Discussione->Id : null])[0]["IdPost"];
            if(is_a($db->ShowError(), "mysqli_sql_exception"))
                throw new mysqli_sql_exception($db->ShowError()->getMessage());
            if(!is_null($result))
                $this->Id = $result;
            if($db->ShowError()){
                echo $db->ShowError();
                echo $this->getTesto();
            }
        }
        return $this;
    }

    public function remove(int $Id): void {
        if(is_null($Id))
            throw new InvalidArgumentException("Non hai inserito l'id del post da eliminare");

        parent::setNomeTabella("Post");
        parent::removeRow($Id);
    }

    public static function getById(int $id) : ?Post {
        $result = SQLUtil::selectFromTablesWhere(["IdPost"], "Post", ["IdPost"], "i", [$id]);
        if($result){
            return new Post($result[0]["IdPost"]);
        } else return null;
    }

    public static function getByTestoAuthor(string $testo, int $idAuthor) : ?Post {
        $result = SQLUtil::selectFromTablesWhere(["IdPost"], "Post", ["Testo", "FkAutore"], "si", [$testo, $idAuthor]);
        if($result){
            return new Post($result[0]["IdPost"]);
        } else return null;
    }

    /**
     * @return int
     */
    public function getId(): ?int
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
    public function getData(): string
    {
        return $this->Data;
    }

    /**
     * @param string $Data
     */
    public function setData(string $Data): void
    {
        $this->Data = $Data;
    }

    /**
     * @return string
     */
    public function getTesto(): string
    {
        return $this->Testo;
    }

    /**
     * @param string $Testo
     */
    public function setTesto(string $Testo): void
    {
        $this->Testo = $Testo;
    }

    /**
     * @return Autore
     */
    public function getAutore(): Autore
    {
        return $this->Autore;
    }

    /**
     * @param Autore $Autore
     */
    public function setAutore(Autore $Autore): void
    {
        $this->Autore = $Autore;
    }

    /**
     * @return Forum
     */
    public function getForum(): Forum
    {
        return $this->Forum;
    }

    /**
     * @param Forum $Forum
     */
    public function setForum(Forum $Forum): void
    {
        $this->Forum = $Forum;
    }

    /**
     * @return Post
     */
    public function getDiscussione(): Post
    {
        return $this->Discussione;
    }

    /**
     * @param Post $Discussione
     */
    public function setDiscussione(Post $Discussione): void
    {
        $this->Discussione = $Discussione;
    }
}