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


#### Amélioration de la méthode `GetReviews` et donc de la méthode `convertEntityFromArray` :

- **9s** TEMPS

```sql
SELECT * FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review'
```

- **6,16s** TEMPS

```sql
SELECT COUNT(meta_value), AVG(meta_value) FROM wp_posts JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id WHERE wp_posts.post_author = :hotelId AND meta_key = 'rating' AND post_type = 'review'
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
SELECT meta_value FROM wp_usermeta WHERE user_id = :userID AND meta_key = :metaKey
```



## Question 5 : Réduction du nombre de requêtes SQL pour `METHOD`

|                            | **Avant** | **Après** |
|----------------------------|-----------|-----------|
| Nombre d'appels de `getDB` | 2201      | 601       |
 | Temps de `getMeta`         | 1,40s     | 1,11s     |

## Question 6 : Création d'un service basé sur une seule requête SQL

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | 601       | 1         |
| Temps de chargement global   | 18 à 20s  | 3 à 4s    |

**Requête SQL**

```SQL

SELECT
    
   /*Données de l'hôtel*/
   users.ID AS id,
   users.display_name AS name,
   addresse_1.meta_value       as addresse_1,
   addresse_2.meta_value       as addresse_2,
   addresse_city.meta_value    as addresse_city,
   addresse_zip.meta_value     as addresse_zip,
   addresse_country.meta_value as addresse_country,
   geoLat.meta_value         as geoLat,
   geoLng.meta_value         as geoLng,
   phoneData.meta_value           as phone,
   coverImageData.meta_value      as coverImage,
   
   /*Données de la chambre la moins cher*/
   cheapestRoom.ID                    as cheapestRoomid,
   cheapestRoom.price                 as price,
   cheapestRoom.surface               as surface,
   cheapestRoom.bedroom               as bedRoomsCount,
   cheapestRoom.bathroom              as bathRoomsCount,
   cheapestRoom.type                  as type,
   
   /* Les reviews*/
   COUNT(reviewData.meta_value)   as ratingCount,
   AVG(reviewData.meta_value)     as rating ,
   
   /*Localisation de l'hôtel*/
   111.111
    * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( geoLat.meta_value ))
                               * COS(RADIANS(:lat))
                               * COS(RADIANS( geoLng.meta_value - :lng ))
    + SIN(RADIANS( geoLat.meta_value ))
                               * SIN(RADIANS( :lat ))))) AS distanceKM


    
  FROM
   /*Recherche des données de l'hôtel*/   
   wp_users AS users
    INNER JOIN wp_usermeta as addresse_1       ON addresse_1.user_id       = users.ID     AND addresse_1.meta_key       = 'address_1'
    INNER JOIN wp_usermeta as addresse_2       ON addresse_2.user_id       = users.ID     AND addresse_2.meta_key       = 'address_2'
    INNER JOIN wp_usermeta as addresse_city    ON addresse_city.user_id    = users.ID     AND addresse_city.meta_key    = 'address_city'
    INNER JOIN wp_usermeta as addresse_zip     ON addresse_zip.user_id     = users.ID     AND addresse_zip.meta_key     = 'address_zip'
    INNER JOIN wp_usermeta as addresse_country ON addresse_country.user_id = users.ID     AND addresse_country.meta_key = 'address_country'
    INNER JOIN wp_usermeta as geoLat         ON geoLat.user_id         = users.ID     AND geoLat.meta_key         = 'geo_lat'
    INNER JOIN wp_usermeta as geoLng         ON geoLng.user_id         = users.ID     AND geoLng.meta_key         = 'geo_lng'
    INNER JOIN wp_usermeta as coverImageData      ON coverImageData.user_id      = users.ID     AND coverImageData.meta_key      = 'coverImage'
    INNER JOIN wp_usermeta as phoneData           ON phoneData.user_id           = users.ID     AND phoneData.meta_key           = 'phone'
    INNER JOIN wp_posts    as rating_postData     ON rating_postData.post_author = users.ID     AND rating_postData.post_type    = 'review'
    INNER JOIN wp_postmeta as reviewData          ON reviewData.post_id = rating_postData.ID   AND reviewData.meta_key          = 'rating'

  /*Recherche de la cheapest room*/
  INNER JOIN (SELECT
               posts.ID,
               posts.post_author,
               MIN(CAST(price.meta_value AS UNSIGNED)) AS price,
               CAST(surface.meta_value  AS UNSIGNED) AS surface,
               CAST(numberOfBedrooms.meta_value AS UNSIGNED) AS bedroom,
               CAST(numberOfBathrooms.meta_value AS UNSIGNED) AS bathroom,
               type.meta_value AS type
              FROM
               tp.wp_posts AS posts
                INNER JOIN tp.wp_postmeta AS price ON posts.ID = price.post_id AND price.meta_key = 'price'
                INNER JOIN wp_postmeta as surface ON surface.post_id = posts.ID AND surface.meta_key = 'surface'
                INNER JOIN wp_postmeta as numberOfBedrooms ON numberOfBedrooms.post_id = posts.ID AND numberOfBedrooms.meta_key = 'bedrooms_count'
                INNER JOIN wp_postmeta as numberOfBathrooms ON numberOfBathrooms.post_id = posts.ID AND numberOfBathrooms.meta_key = 'bathrooms_count'
                INNER JOIN wp_postmeta as type ON type.post_id = posts.ID AND type.meta_key = 'type' WHERE posts.post_type = 'room'

              GROUP BY
               posts.ID
 ) AS cheapestRoom ON users.ID = cheapestRoom.post_author WHERE  surface >= :surfaceMin AND  surface <= :surfaceMax AND  price >= :minPrice AND  price <= :maxPrice AND  bedroom >= :numberOfBedrooms AND  bathroom >= numberOfBathrooms AND  type IN :types GROUP BY users.ID
    HAVING distanceKM <= :distance  ORDER BY `cheapestRoomId` ASC
```

## Question 7 : ajout d'indexes SQL

**Indexes ajoutés**

- `wp_postmeta` : `post_id`
- `wp_postmeta` : `user_id`
- `wp_posts` : `post_author`

**Requête SQL d'ajout des indexes** 

```sql
ALTER TABLE wp_postmeta ADD INDEX(post_id);
ALTER TABLE wp_usermeta ADD INDEX(user_id);
ALTER TABLE wp_posts ADD INDEX(post_author);
```

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `UnoptimizedService`           | 18 à 20s    | 1,37s        |
| `OneRequestService`            | 1,27s       | 0,90s        |
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
