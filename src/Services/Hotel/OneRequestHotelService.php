<?php
    namespace App\Services\Hotel;

    use App\Common\FilterException;
    use App\Common\SingletonDB;
    use App\Common\SingletonTrait;
    use App\Common\Timers;
    use App\Entities\HotelEntity;
    use App\Entities\RoomEntity;
    use App\Services\Room\AbstractRoomService;
    use App\Services\Room\RoomService;
    use PDO;
    use PDOStatement;

    class OneRequestHotelService extends AbstractHotelService {

        use SingletonTrait;

        protected function __construct () {
            parent::__construct( new RoomService() );
        }

        protected function getDB (): PDO
        {
            $timer = Timers::getInstance();
            $timerID = $timer->startTimer('getDB');
            $pdo = SingletonDB::get();
            $timer->endTimer('getDB',$timerID);
            return $pdo;
        }

        /**
         * @throws FilterException
         */
        public function list(array $args = []): array
        {

            $sqlArgs = [];

            if (isset($args['lat']))

                $sqlArgs['lat']  = $args['lat'];

            if (isset($args['lng']))

                $sqlArgs['lng'] = $args['lng'];

            if (isset($args['distance']))

                $sqlArgs['distance'] = $args['distance'];

            $results = [];
            $stmt = $this->buildQuery($args);


            if(!empty($sqlArgs))
                $stmt->execute($sqlArgs);
            else
                $stmt->execute();


            $hotels = $stmt->fetchAll();

            foreach ($hotels as $hotel){
                $results[] = $this->convertEntityFromArray($hotel,$args);
            }
            return $results;

        }

        /**
         * @param array $data
         * @param array $args
         * @return HotelEntity
         * @throws FilterException
         */
        public function convertEntityFromArray(array $data, array $args): HotelEntity
        {

            $hotel = ( new HotelEntity() )
                ->setId( $data['id'] )
                ->setName( $data['name'] );

            // Charge les données meta de l'hôtel
            $metaDatas = [
                'address_1' => $data[2],
                'address_2' => $data[3],
                'address_city' => $data[4],
                'address_zip' => $data[5],
                'address_country' => $data[6]
            ];

            $hotel->setAddress( $metaDatas );

            $hotel->setGeoLat( $data['geoLat'] );
            $hotel->setGeoLng( $data['geoLng'] );
            $hotel->setImageUrl( $data['coverImage'] );
            $hotel->setPhone( $data['phone'] );

            // Définit la note moyenne et le nombre d'avis de l'hôtel

            $hotel->setRating( intval($data['rating']) );
            $hotel->setRatingCount( intval($data['ratingCount']));

            // Charge la chambre la moins chère de l'hôtel

            $cheapestRoom = (new RoomEntity())
                ->setPrice($data[12])
                ->setBathRoomsCount($data[15])
                ->setBedRoomsCount($data[14])
                ->setSurface($data[13])
                ->setId($data[11])
                ->setType($data[16]);


            $hotel->setCheapestRoom($cheapestRoom);

            // Verification de la distance
            if(isset($data['distanceKM'])) {
                $hotel->setDistance($data['distanceKM']);

                if ($hotel->getDistance() > $args['distance'])
                    throw new FilterException("L'hôtel est en dehors du rayon de recherche");
            }


            return $hotel;
        }

        /**
         * @param array $args
         * @return PDOStatement
         */

        protected function buildQuery(array $args) : PDOStatement {


            $db = $this->getDB();

            $sqlQuery = "SELECT
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
                cheapestRoom.ID                    as cheapestRoomid,
                cheapestRoom.price                 as price,
                cheapestRoom.surface               as surface,
                cheapestRoom.bedroom               as bedRoomsCount,
                cheapestRoom.bathroom              as bathRoomsCount,
                cheapestRoom.type                  as type,
                COUNT(reviewData.meta_value)   as ratingCount,
                AVG(reviewData.meta_value)     as rating ";


            if(!empty($args["distance"])){
                $sqlQuery .= ",
                  111.111
                  * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( geoLat.meta_value ))
                  * COS(RADIANS(:lat))
                  * COS(RADIANS( geoLng.meta_value - :lng ))
                  + SIN(RADIANS( geoLat.meta_value ))
                  * SIN(RADIANS( :lat ))))) AS distanceKM";
            }

            $where = "";

            if(!empty($whereClauses)){
                $where = " WHERE " . implode(' AND ', $whereClauses);
            }
            $sqlQuery .= "
                FROM
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
                ) AS cheapestRoom ON users.ID = cheapestRoom.post_author";


            $whereClauses = [];
            if (isset ($args['surface']['min'])){
                $whereClauses[] = " surface >= ". $args['surface']['min'];
            }
            if (isset ($args['surface']['max'])){
                $whereClauses[] =  " surface <= ". $args['surface']['max'];
            }
            if (isset ($args['price']['min'])){
                $whereClauses[] = " price >= ". $args['price']['min'];
            }
            if (isset ($args['price']['max'])){
                $whereClauses[] =  " price <= ". $args['price']['max'];
            }
            if (isset ($args['rooms'])){
                $whereClauses[] =  " bedroom >= ". $args['rooms'];
            }
            if (isset ($args['bathRooms'])){
                $whereClauses[] =  " bathroom >= ". $args['bathRooms'];
            }
            if (isset ($args['types']) && count($args['types']) > 0){
                $whereClauses[] =  ' type IN ("'.implode('","',$args['types']).'")';
            }
            if (!empty($whereClauses)) {
                $sqlQuery .= " WHERE " . implode(' AND ', $whereClauses);
            }


            $sqlQuery .= " GROUP BY users.ID ";
            if(!empty($args["distance"])){
                $sqlQuery .= " \n HAVING distanceKM <= :distance ";
            }
            $sqlQuery .=" ORDER BY `cheapestRoomId` ASC ";
            $stmt = $db->prepare($sqlQuery);

            return $stmt;
        }
    }
