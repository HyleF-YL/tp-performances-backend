Vous pouvez utiliser ce [GSheets](https://docs.google.com/spreadsheets/d/13Hw27U3CsoWGKJ-qDAunW9Kcmqe9ng8FROmZaLROU5c/copy?usp=sharing) pour suivre l'évolution de l'amélioration de vos performances au cours du TP 

## Question 2 : Utilisation Server Timing API

**Temps de chargement initial de la page** : 31s

**Choix des méthodes à analyser** :

- `GetCheapestRoom` 16,25s
- `GetReviews` 9s
- `GetMetas` 4,23s



## Question 3 : Réduction du nombre de connexions PDO

**Temps de chargement de la page** : 28,7s

**Temps consommé par `getDB()`** 

- **Avant** 1,26s

- **Après** 2,69ms


## Question 4 : Délégation des opérations de filtrage à la base de données

**Temps de chargement globaux** 

- **Avant** 28,7s

- **Après** 18,7s


#### Amélioration de la méthode `GetReviews` et donc de la méthode `METHOD` :

- **9s** TEMPS

```sql
SELECT * FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review'
```

- **6,16s** TEMPS

```sql
SELECT meta_value FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review'
```



#### Amélioration de la méthode `GetCheapestRoom` :

- **16,26s** TEMPS

```sql
SELECT * FROM wp_posts WHERE post_author = :hotelId AND post_type = 'room'
```

- **10,22s** TEMPS

```sql
SELECT
 posts.ID AS id,
 CAST(MIN(price.meta_value) AS INT) AS valeur


FROM wp_posts AS posts

      INNER JOIN wp_postmeta AS surface ON surface.post_id = posts.ID AND surface.meta_key = 'surface'

      INNER JOIN wp_postmeta AS price ON price.post_id = posts.ID AND price.meta_key = 'price'

      INNER JOIN wp_postmeta AS numberOfRooms ON numberOfRooms.post_id = posts.ID AND numberOfRooms.meta_key='bedrooms_count'

      INNER JOIN wp_postmeta AS numberOfBathrooms ON numberOfBathrooms.post_id = posts.ID AND numberOfBathrooms.meta_key = 'bathrooms_count'

      INNER JOIN wp_postmeta AS typeOfRoom ON typeOfRoom.post_id = posts.ID AND typeOfRoom.meta_key = 'type'
```



#### Amélioration de la méthode `GetMeta` :

- **4s** TEMPS

```sql
SELECT * FROM wp_usermeta
```

- **1,5s** TEMPS

```sql
SELECT * FROM wp_usermeta WHERE user_id = :userID AND meta_key = :metaKey
```



## Question 5 : Réduction du nombre de requêtes SQL pour `METHOD`

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | NOMBRE    | NOMBRE    |
 | Temps de `METHOD`            | TEMPS     | TEMPS     |

## Question 6 : Création d'un service basé sur une seule requête SQL

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | NOMBRE    | NOMBRE    |
| Temps de chargement global   | TEMPS     | TEMPS     |

**Requête SQL**

```SQL
-- GIGA REQUÊTE
-- INDENTATION PROPRE ET COMMENTAIRES SERONT APPRÉCIÉS MERCI !
```

## Question 7 : ajout d'indexes SQL

**Indexes ajoutés**

- `TABLE` : `COLONNES`
- `TABLE` : `COLONNES`
- `TABLE` : `COLONNES`

**Requête SQL d'ajout des indexes** 

```sql
-- REQ SQL CREATION INDEXES
```

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `UnoptimizedService`           | TEMPS       | TEMPS        |
| `OneRequestService`            | TEMPS       | TEMPS        |
[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)




## Question 8 : restructuration des tables

**Temps de chargement de la page**

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `OneRequestService`            | TEMPS       | TEMPS        |
| `ReworkedHotelService`         | TEMPS       | TEMPS        |

[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)

### Table `hotels` (200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `rooms` (1 200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `reviews` (19 700 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```


## Question 13 : Implémentation d'un cache Redis

**Temps de chargement de la page**

| Sans Cache | Avec Cache |
|------------|------------|
| TEMPS      | TEMPS      |
[URL pour ignorer le cache sur localhost](http://localhost?skip_cache)

## Question 14 : Compression GZIP

**Comparaison des poids de fichier avec et sans compression GZIP**

|                       | Sans  | Avec  |
|-----------------------|-------|-------|
| Total des fichiers JS | POIDS | POIDS |
| `lodash.js`           | POIDS | POIDS |

## Question 15 : Cache HTTP fichiers statiques

**Poids transféré de la page**

- **Avant** : POIDS
- **Après** : POIDS

## Question 17 : Cache NGINX

**Temps de chargement cache FastCGI**

- **Avant** : TEMPS
- **Après** : TEMPS

#### Que se passe-t-il si on actualise la page après avoir coupé la base de données ?

REPONSE

#### Pourquoi ?

REPONSE
