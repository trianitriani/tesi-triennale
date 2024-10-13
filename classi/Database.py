import pymysql as db

class Database:
    def __init__(self):
        # Connessione al database MySQL
        self.conn = db.connect(
            host='localhost',
            user='root',
            password='Onepiece1*',
            database='tesi'
        )
        self.c = self.conn.cursor()
        
    def myExecute(self, query, params=None, ret=True):
        # Controllare se l'utente ha inserito parametri o meno
        if params:
            self.c.execute(query, params)
        else:
            self.c.execute(query)
        
        self.conn.commit()

        if ret == False:
            return None
        else: 
            return self.c.fetchall()

    def close(self):
        # Chiude la connessione al database
        self.c.close()
        self.conn.close()
        
                

