from classi.Analizzatore import Analizzatore

# Diamo il valore alle gravità per gli avverbi di quantita passandole come parametri
an = Analizzatore(0.5, 2)

# Vengono inizializzati i dizionari dei nomi, aggettivi e avverbi e le loro gravità
an.inizializzaParoleTarget() 

# Adesso cominciamo ad analizzare tutti i post degli utenti, utente per utente.
# Dando ad ogni post un punteggio relativo alla sua gravità in relazione all'utente che lo ha pubblicato
an.analizzaPosts()
