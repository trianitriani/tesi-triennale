import string                           # usato per la punteggiatura
import nltk
from nltk.corpus import stopwords       # usato per le parole comuni 
import spacy                            # usato per ridurre parole alla loro forma base
from classi.Database import Database

class Analizzatore:
    def __init__(self, v_little, v_very):
        # Assicurati di aver scaricato le risorse necessarie di nltk
        nltk.download('stopwords')
        # caricare un modello di NLP
        self.nlp = spacy.load('en_core_web_lg')                                              

        # dizionari che contengono parole target come key e il loro livello di gravità come value
        self.nounce = {}
        self.adjective = {}
        self.adverb = {}
        self.multiple = {}
        self.verb = {}
        self.verb_no_target = {}
        
        # Dizionario dei modificatori per avverbi
        self.gravity_adv = {
            "low": v_little,
            "high": v_very
        }

        # Dizionario che memorizza per ogni post il numero di parole targte trovate
        self.numero_parole = {
            "low": 0,
            "medium": 0,
            "high": 0
        }       

        self.id = 0
        self.post_target_numeric = [15368, 15372, 15377]
        self.post_target_string = ["15368", "15372", "15377"]
        
    def inizializzaParoleTarget(self):
        db = Database()
        query = """
                SELECT Nome, Gravita, Tipo
                FROM parola_target 
                """
        rows = db.myExecute(query) 
        for row in rows:
            name = row[0]
            gravity = row[1]
            type = row[2]
            
            if type == "nome":
                self.nounce[name] = gravity
            elif type == "aggettivo":
                self.adjective[name] = gravity
            elif type == "avverbio":
                self.adverb[name] = self.gravity_adv[gravity]
            elif type == "multipla":
                self.multiple[name] = gravity
        
        db.close()            

    def pre_elaborazione(self):
        # testo minuscolo
        self.post_attuale = self.post_attuale.lower()  
        # eliminazione della punteggiatura                                 
        self.post_attuale = self.post_attuale.translate(str.maketrans('', '', string.punctuation))  
        # rimuove i numeri            
        self.post_attuale = ''.join([i for i in self.post_attuale if not i.isdigit()])                          
        
        # divisione in parole
        parole = self.post_attuale.split()
        # ottenere un set di stop word                                                          
        stop_words = set(stopwords.words('english'))     
        # elimare le stop word                               
        self.post_attuale = ' '.join([parola for parola in parole if parola not in stop_words])     
        
        # applicare il modello al post                                                
        doc = self.nlp(self.post_attuale)          
        # sostituire tipo running con run, etc                                                 
        testo = ' '.join([token.lemma_ for token in doc])                               
        # rimuovere spazi vuoti
        testo = ' '.join(testo.split())     

        return testo
    
    def derivate_from_nounce(self, verb):
        # Controlla se il nome deriva dal verbo aggiungendo suffissi comuni
        suffissi = ["", "er", "ing", "ed", "ion", "ment", "ance", "ence"]
        for nome, gravita in self.nounce.items():
            for suffisso in suffissi:
                if (verb + suffisso == nome):
                    return gravita
        return False

    def calcolaPunteggioParola(self, parola, gravita, parole_post_adv = False):
        if self.id in self.post_target_numeric or self.id in self.post_target_string:
           print(parola+": "+gravita+"\n")

        if(parole_post_adv == False or parola not in parole_post_adv):
            # Se non stiamo su una parola che si trova dopo un avverbio di quantita
            # Allora incremento il numero di parole di quella gravita
            self.numero_parole[gravita]+=1
        else:
            # Altrimenti la considero a metà o doppia dato che si trova dentro l'array
            self.numero_parole[gravita] = self.numero_parole[gravita] + parole_post_adv[parola]
        
    def calcolaPunteggioPost(self, testo):
        doc = self.nlp(testo)                 
        parole_post_adv = {}                # lista di verbi, aggettivi che seguono un avverbio
        for token in doc:
            parola = token.text             # parola da analizzare
            type = token.pos_               # categorizzazione della parola
            if type == "VERB":
                # controlla prima se si trova all'interno del dizionario
                if parola in self.verb:
                    # allora considero la parola come target
                    self.calcolaPunteggioParola(parola, self.verb[parola], parole_post_adv)
                elif parola not in self.verb_no_target:
                    # allora la parola potrebbe essere target ma ancora non inserita nel dizionario dei verbi target
                    # controllo allora che derivi da un nome tra quelli target, se si lo metto nel dizionario dei verbi
                    gravity = self.derivate_from_nounce(parola)
                    if(gravity != False):
                        # allora è target e lo inserisco nel dizionario dei verbi target
                        self.verb[parola] = gravity
                        self.calcolaPunteggioParola(parola, self.verb[parola], parole_post_adv)
                    else:
                        # lo inserisco nel dizionario dei verbi no target così che non devo ogni volta controllare i suffizzi
                        self.verb_no_target[parola] = -1
                
                # se si trova la parola all'interno della lista di quelle post adv allora la rimuovo
                if parola in parole_post_adv:
                    del parole_post_adv[parola] 

            elif type == "NOUN":
                # controllo se fa parte del dizionario dei nomi target
                if parola in self.nounce:
                    # allora considero la parola come target
                    self.calcolaPunteggioParola(parola, self.nounce[parola])
                
            elif type == "ADJ":
                # controllo se fa parte del dizionario degli aggettivi target
                if parola in self.adjective:
                    # allora considero la parola come target
                    self.calcolaPunteggioParola(parola, self.adjective[parola], parole_post_adv)

                # se si trova la parola all'interno della lista di quelle post adv allora la rimuovo
                if parola in parole_post_adv:
                    del parole_post_adv[parola]

            elif type == "ADV":
                if parola in self.adverb:
                    # allora considero la parola come target
                    # in questo caso però la cosa è più complicata, devo controllare che la parola successiva sia un aggettivo
                    # e modificare la sua gravità in funzione dell'avverbio che lo procede
                    parole_post_adv[token.head.text] = self.adverb[parola]

    def punteggioParoleMultiple(self):
        self.post_attuale = self.post_attuale.lower()
        for frase, gravita in self.multiple.items():
            # Ricercare tale parola multipla all'interno del testo
            if frase in self.post_attuale:
                if self.id in self.post_target_numeric or self.id in self.post_target_string:
                    print(frase+": "+gravita+"\n")
                # Allora considero il punteggio e la rimuovo dal testo, così che le parole da cui è
                # composta non siano considerate
                self.numero_parole[gravita]+=1
                self.post_attuale = self.post_attuale.replace(frase, "")
    
    def analizzaPosts(self):
        # Analisi verrà fatta utente per utente (?)
        db = Database()
        query = """
                SELECT IdAutore
                FROM autore
                """
        autori = db.myExecute(query)
        for autore in autori:
            # Adesso andiamo a prendere tutti i post in ordine cronologico dell'utente
            query = """
                    SELECT Testo, IdPost
                    FROM post
                    WHERE FkAutore = %s
                    GROUP BY Testo, Data
                    ORDER BY Data, IdPost
                    """
            posts = db.myExecute(query, [autore[0]])
            for post in posts:
                self.id = post[1]
                if self.id in self.post_target_numeric or self.id in self.post_target_string:
                    print(str(self.id)+"\n")
        
                # Adesso analizziamo effettivamente ogni post
                # Partiamo dalle parole multiple, una pre-elaborazione potrebbe togliere parte delle frasi da ricercare
                self.post_attuale = post[0]
                # Consideriamo il punteggio relativo alle varie parole target multiple
                self.punteggioParoleMultiple()
                # Iniziamo con la fase di pre-elaborazione
                post_elaborato = self.pre_elaborazione()
                # Calcoliamo il punteggio del post rispetto alle parole che contiene
                self.calcolaPunteggioPost(post_elaborato)
                # Segnamo il numero delle parole target trovate all'interno del post nel database
                query = """
                        UPDATE post
                        SET nParoleLow = %s, nParoleMedium = %s, nParoleHigh = %s
                        WHERE IdPost = %s
                        """
                nLow, self.numero_parole["low"] = self.numero_parole["low"], 0
                nMedium, self.numero_parole["medium"] =  self.numero_parole["medium"], 0
                nHigh, self.numero_parole["high"] = self.numero_parole["high"], 0
                db.myExecute(query, [nLow, nMedium, nHigh, post[1]], False)