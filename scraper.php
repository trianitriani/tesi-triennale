<?php

// Include la libreria SimpleHTMLDom
include_once 'lib/parser/simple_html_dom.php';

// Include classi create da me per seplificare il codice del main
require_once (__DIR__."/lib/Autore.php");
require_once (__DIR__."/lib/Forum.php");
require_once (__DIR__."/lib/Post.php");

// Rimuovo il limite di tempo di esecuzione dell'algoritmo
set_time_limit(0);

// Scorrere tutte le pagine relative alla sezione depressione
$page = 1;
while($html = file_get_html("https://forums.beyondblue.org.au/t5/depression/bd-p/c1-sc2-b2/page/$page")){
  // Trova tutti i tag div con una determinata classe per ottenere tutte le discussioni
  $patherDIV = $html->find('div.custom-message-list.all-discussions')[0];
  if(!is_null($patherDIV))
    // Ottengo tutti i blocchi per ogni discussione
    $articles = $patherDIV->find("article.custom-message-tile");

  $url = "";
  // Cicliamo per ogni articolo (discussione)
  foreach ($articles as $article) {
    // Ottenimento dei titoli e del link per la pagina relativa
    $aTAGs = $article->find('a[title]');
    foreach ($aTAGs as $aTAG) {
      if($aTAG->title != "View profile"){
        // Titolo del post
        $titolo = $aTAG->title;
        $url = $aTAG->href;
      }
    }
    
    // Adesso scrapiamo la singola pagina, vogliamo ottenere i dati anche delle risposte (ma solo alcune)
    $page_ = 1;
    // non devo considerare la domanda
    $reply_ = -1; 
    $AutorePrincipale = "";
    // Le domande potrebbero trovarsi su piu' pagine, la variabile finale $page_ seleziona il numero di pagina della sezione risposte
    while($html_ = file_get_html("https://forums.beyondblue.org.au/$url/page/$page_")){
        $replysDIV = $html_->find("div.MessageView");
        // Otteniamo il numero delle risposte a quella discussione
        $num_reply = $html_->find("span[class='lia-hidden']")[0]->innertext;
        if($reply_ >= intval($num_reply))  
            break;

        // Contiene testo relativo a date e testo vario che deve essere rimosso
        $parole_bandite_data = array("<span class=\"local-date\">", "</span>", "&lrm;");
        $parole_bandite_testo = array("<div class=\"lia-message-body-content\">");
        // Contiene le emoji principali che si possono trovare e le rimuove per 
        $pattern_emoji = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u';
        
        // Ciclo per tutti i post della discussione corrente
        foreach ($replysDIV as $replyDIV){
            $nameUser = $replyDIV->find("img[class='lia-user-avatar-message']")[0]->title;
            if(is_null($replyDIV->find("span[class='local-friendly-date']")[0]))
              $date = $replyDIV->find("span[class='local-date']")[0]->outertext;
            else 
              $date = $replyDIV->find("span[class='local-friendly-date']")[0]->title;
            
            // Rimuove dalla data tutti i caratteri non stampabili
            $date = preg_replace('/[^\x20-\x7E]/', '', str_replace($parole_bandite_data, "", trim($date)));
            // Rimuove i vari tag html all'interno della stringa e dopo ciò rimuovo le emoji
            $messaggio = preg_replace('/<[^>]*>/', ' ', str_replace($parole_bandite_testo, " ", $replyDIV->find("div[class='lia-message-body-content']")[0]->outertext));
            $messaggio = preg_replace($pattern_emoji, '', $messaggio);
            $messaggio = html_entity_decode($messaggio);

            try {
              // Creo l'istanza autore del post e gli assegno l'username
              $Autore = new Autore();
              $Autore->setUsername($nameUser);
              
              // Creo l'istanza per il forum e gli assegno il nome della depressione 
              // questo e' sempre uguale poiche' il forum preso in considerazione e' sempre riguardo la depressione
              $Forum = new Forum();
              $Forum->setNome("depression");
              
              // Creo l'istanza per il post e gli assegno la data, il testo, il forum di appartenenza e l'autore
              $Post = new Post();
              $datetime = new DateTime($date);          
              $date = $datetime->format("Y-m-d");
              $Post->setData($date);
              $Post->setTesto($messaggio);
              $Post->setForum($Forum);
              $Post->setAutore($Autore);

              // Se il post non e' il primo della discussione gli assegno anche la discussione a cui fa riferimento
              if($reply_ != -1)
                $Post->setDiscussione(new Post($IdDiscussione));
              
              // Lo inserirò all'interno del database solo però se ha come autore il creatore della discussione
              // gli altri post non mi interessano
              if($AutorePrincipale == "" || $AutorePrincipale == $nameUser)
                $Post->save($nameUser, "depression");

                
            } catch (InvalidArgumentException $e) {
              echo $e;
              return;
            } catch (mysqli_sql_exception $e) {
              echo $e;
              return;
            }
            
            // Se e' il primo post della discussione allora memorizzo il suo id e l'username del suo autore
            if($reply_ == -1){
              $IdDiscussione = $Post->getId(); 
              $AutorePrincipale = $nameUser;
            }
            $reply_ ++;
        }
        $page_++;
    }
  }
  $page++;
}

?>
