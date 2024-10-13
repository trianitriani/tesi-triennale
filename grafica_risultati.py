import numpy as np
import pandas as pd
import seaborn as sns
import matplotlib.pyplot as plt
import matplotlib.colors as mcolors
from classi.Database import Database
    
db = Database()
query = """
        SELECT IdAutore, Username
        FROM autore A
             INNER JOIN
             post P ON A.IdAutore = P.FkAutore
        GROUP BY IdAutore
        HAVING COUNT(*) > 10
        """
autori = db.myExecute(query)
j = 0

for autore in autori:
    idAutore = autore[0]
    username = autore[1]
    
    # Adesso prendo tutti i post degli autori e li grafico rispetto al loro punteggio
    query = """
            SELECT D1.nParoleLow, D1.nParoleMedium, D1.nParoleHigh, D1.FkDiscussione, D1.IdPost, D1.Data, D1.DataDiscussione
            FROM 
            (
                SELECT P.*, D.IdPost AS IdDiscussione, D.Data AS DataDiscussione
                FROM (
                    SELECT P.*
                    FROM post P
                        INNER JOIN
                        autore A ON P.FkAutore = A.IdAutore 
                    WHERE 
                        A.IdAutore = %s AND P.FkDiscussione IS NULL
                ) AS D
                INNER JOIN
                post P ON P.FkDiscussione = D.IdPost OR P.IdPost = D.IdPost
                ORDER BY D.Data ASC, P.Data ASC, P.IdPost ASC
            ) AS D1
            """
    posts = db.myExecute(query, [idAutore])
    n_post = len(posts)
    x = []
    y = []
    z = []
    start_discussioni = []
    _max = [0, 0, 0]
    i = 0

    for post in posts:
        # Inserimento nel vettore del numero di parole target per ogni post, per ogni gravita
        x.append(post[0])
        y.append(post[1])
        z.append(post[2])
        if post[3] is None:
            start_discussioni.append(i)

        for _i in range(3):
            if post[_i] > _max[_i]:
                _max[_i] = post[_i]
        i+=1

    ############ PLOT DELLO SCONTRINO EMOTIVO ################
    heatmap = True
    if heatmap :
        m = []
        m.append(x)
        m.append(y)
        m.append(z)
        m_normalized = []
        m_normalized.append([x / 3 for x in m[0]])
        m_normalized.append([y / 2 for y in m[1]])
        m_normalized.append([z for z in m[2]])
        # Convertiamo i dati in un DataFrame per facilitarne la manipolazione
        df = pd.DataFrame(m_normalized) 
        etichette_riga = ["low gravity word", "medium gravity word", "high gravity word"]
        # Disegnamo rettangoli attorno ad ogni gruppo di colonne che fa parte della stessa discussione
        # Aggiungiamo colonne di NaN per separare i blocchi
        new_df = pd.DataFrame()
        annot_df = pd.DataFrame()
        _last = 0
        for disc in start_discussioni:
            new_df = pd.concat([new_df, df.iloc[:, _last:disc], pd.DataFrame(np.nan, index=df.index, columns=[f'Sep_{disc}'])], axis=1)
            annot_df = pd.concat([annot_df, df.iloc[:, _last:disc], pd.DataFrame(np.nan, index=df.index, columns=[f'Sep_{disc}'])], axis=1)
            _last = disc

        # Aggiungiamo l'ultimo blocco
        new_df = pd.concat([new_df, df.iloc[:, _last:len(df.columns)]], axis=1)
        annot_df = pd.concat([annot_df, df.iloc[:, _last:len(df.columns)]], axis=1)
        
        # Creiamo la heatmap emotiva dell'utente
        cell_size = 0.5
        fig, ax = plt.subplots(figsize=(cell_size * len(x), cell_size * 3))
        bounds = [0, 0.001, 1, 2, 3, 4, 20]
        colors = ['blue', 'yellow', 'gold', 'orange', 'red', 'purple']
        cmap = mcolors.ListedColormap(colors)
        norm = mcolors.BoundaryNorm(bounds, cmap.N)
        sns.heatmap(new_df, cmap=cmap, norm=norm, vmin=0, vmax=20, square=True, xticklabels=False, yticklabels=False)
        
        # plt.title(f'Heatmap emotivo di {username}')
        fig.savefig('grafici/heatmap/'+username+'.png')
        fig.clf()
        plt.close(fig)