<?php

namespace App\Services\Hotel;

use App\Common\FilterException;
use App\Common\SingletonDB;
use App\Common\SingletonTrait;
use App\Common\Timers;
use App\Entities\HotelEntity;
use App\Entities\RoomEntity;
use App\Services\Room\RoomService;
use Exception;
use PDO;

/**
 * Une classe utilitaire pour récupérer les données des magasins stockés en base de données
 */
class UnoptimizedHotelService extends AbstractHotelService {

  private SingletonDB $PDO;

  use SingletonTrait;
  
  protected function __construct () {
    parent::__construct( new RoomService() );
  }
  
  
  /**
   * Récupère une nouvelle instance de connexion à la base de donnée
   *
   * @return SingletonDB
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getDB (): PDO
  {
    $timer = Timers::getInstance();
    $timerID = $timer->startTimer('getDB');
    $pdo = SingletonDB::get();
    $timer->endTimer('getDB',$timerID);
    return $pdo;
  }
  
  
  /**
   * Récupère une méta-donnée de l'instance donnée
   *
   * @param int    $userId
   * @param string $key
   *
   * @return string|null
   */
  protected function getMeta ( int $userId, string $key ) : ?string {
    $stmt = $this->getDB()->prepare( "SELECT * FROM wp_usermeta WHERE user_id = :userID AND meta_key = :metaKey" );
    $stmt->execute(['userID' => $userId, 'metaKey' => $key]);
    
    $result = $stmt->fetch( PDO::FETCH_ASSOC )['meta_value'];
    
    return $result;
  }
  
  
  /**
   * Récupère toutes les meta données de l'instance donnée
   *
   * @param HotelEntity $hotel
   *
   * @return array
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getMetas ( HotelEntity $hotel ) : array {
    $metaDatas = [
      'address' => [
        'address_1' => $this->getMeta( $hotel->getId(), 'address_1' ),
        'address_2' => $this->getMeta( $hotel->getId(), 'address_2' ),
        'address_city' => $this->getMeta( $hotel->getId(), 'address_city' ),
        'address_zip' => $this->getMeta( $hotel->getId(), 'address_zip' ),
        'address_country' => $this->getMeta( $hotel->getId(), 'address_country' ),
      ],
      'geo_lat' =>  $this->getMeta( $hotel->getId(), 'geo_lat' ),
      'geo_lng' =>  $this->getMeta( $hotel->getId(), 'geo_lng' ),
      'coverImage' =>  $this->getMeta( $hotel->getId(), 'coverImage' ),
      'phone' =>  $this->getMeta( $hotel->getId(), 'phone' ),
    ];
    
    return $metaDatas;
  }
  
  
  /**
   * Récupère les données liées aux évaluations des hotels (nombre d'avis et moyenne des avis)
   *
   * @param HotelEntity $hotel
   *
   * @return array{rating: int, count: int}
   * @noinspection PhpUnnecessary-LocalVariableInspection
   */
  protected function getReviews ( HotelEntity $hotel ) : array {
    // Récupère tous les avis d'un hotel
    $stmt = $this->getDB()->prepare( "SELECT meta_value FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review'" );
    $stmt->execute( [ 'hotelId' => $hotel->getId() ] );
    $reviews = $stmt->fetchAll( PDO::FETCH_ASSOC );
    
    // Sur les lignes, ne garde que la note de l'avis
    /*$reviews = array_map( function ( $review ) {
      return intval( $review['meta_value'] );
    }, $reviews );*/
    
    $output = [
      'rating' => round( array_sum( $reviews ) / count( $reviews ) ),
      'count' => count( $reviews ),
    ];
    
    return $output;
  }


  /**
   * Récupère les données liées à la chambre la moins chère des hotels
   *
   * @param HotelEntity $hotel
   * @param array{
   *   search: string | null,
   *   lat: string | null,
   *   lng: string | null,
   *   price: array{min:float | null, max: float | null},
   *   surface: array{min:int | null, max: int | null},
   *   rooms: int | null,
   *   bathRooms: int | null,
   *   types: string[]
   * }                  $args Une liste de paramètres pour filtrer les résultats
   *
   * @throws FilterException
   * @return RoomEntity
   */
  protected function getCheapestRoom ( HotelEntity $hotel, array $args = [] ) : RoomEntity {

    // On charge toutes les chambres de l'hôtel
      /*$stmt = $this->getDB()->prepare( "SELECT * FROM wp_posts WHERE post_author = :hotelId AND post_type = 'room'" );
      $stmt->execute( ['hotelId' => $hotel->getId() ] );*/

      /**
       * On convertit les lignes en instances de chambres (au passage ça charge toutes les données).
       *
       * @var RoomEntity[] $rooms ;
       */
      /*$rooms = array_map( function ( $row ) {
          return $this->getRoomService()->get( $row['ID'] );
      }, $stmt->fetchAll( PDO::FETCH_ASSOC ) );*/

      // On exclut les chambres qui ne correspondent pas aux critères
      $whereClause = ["posts.post_author = " . htmlspecialchars($hotel->getId()), "post_type = 'room'"];

      if ( isset( $args['surface']['min'] ) /*&& $room->getSurface() < $args['surface']['min'] */) {
          $whereClause[] = " surface.meta_value >= " . htmlspecialchars($args['surface']['min']);

      }

      if ( isset( $args['surface']['max'] ) /*&& $room->getSurface() > $args['surface']['max'] */)
          $whereClause[] = " surface.meta_value   <= " . htmlspecialchars($args['surface']['max']);

      if ( isset( $args['price']['min'] ) /*&& intval( $room->getPrice() ) < $args['price']['min'] */) {
          $whereClause[] = " price.meta_value >= " . htmlspecialchars($args['price']['min']);

      }

      if ( isset( $args['price']['max'] ) /*&& intval( $room->getPrice() ) > $args['price']['max'] */) {
          $whereClause[] = " price.meta_value <= " . htmlspecialchars($args['price']['max']);
      }

      if ( isset( $args['rooms'] ) /*&& $room->getBedRoomsCount() < $args['rooms']*/ ) {
          $whereClause[] = " numberOfRooms.meta_value = " . htmlspecialchars($args['rooms'] );

      }

      if ( isset( $args['bathRooms'] ) /*&& $room->getBathRoomsCount() < $args['bathRooms']*/ ) {
          $whereClause[] = "numberOfBathrooms.meta_value = " . htmlspecialchars($args['bathRooms']);

      }

      if ( isset( $args['types'] ) && ! empty( $args['types'] ) /*&& ! in_array( $room->getType(), $args['types'] )*/ ) {

          $whereClause[] = "typeOfRoom.meta_value IN('" . implode("', '", $args["types"]) . "')";

      }

      // Si aucune chambre ne correspond aux critères, alors on déclenche une exception pour retirer l'hôtel des résultats finaux de la méthode list().
     /* if ( count( $filteredRooms ) < 1 )
          throw new FilterException( "Aucune chambre ne correspond aux critères" );*/


      // Trouve le prix le plus bas dans les résultats de recherche
      /*$cheapestRoom = null;
      foreach ( $filteredRooms as $room ) :
          if ( ! isset( $cheapestRoom ) ) {
              $cheapestRoom = $room;
              continue;
          }

          if ( intval( $room->getPrice() ) < intval( $cheapestRoom->getPrice() ) )
              $cheapestRoom = $room;
      endforeach;*/

      $where = "";
      if(count($whereClause) > 1)
          $where = "WHERE " . implode(' AND ', $whereClause);


      $query = "SELECT 
                posts.ID AS id,
                CAST(MIN(price.meta_value) AS INT) AS valeur
               
                
                FROM wp_posts AS posts
                
                INNER JOIN wp_postmeta AS surface ON surface.post_id = posts.ID AND surface.meta_key = 'surface'
                
                INNER JOIN wp_postmeta AS price ON price.post_id = posts.ID AND price.meta_key = 'price' 
                
                INNER JOIN wp_postmeta AS numberOfRooms ON numberOfRooms.post_id = posts.ID AND numberOfRooms.meta_key='bedrooms_count'
                
                INNER JOIN wp_postmeta AS numberOfBathrooms ON numberOfBathrooms.post_id = posts.ID AND numberOfBathrooms.meta_key = 'bathrooms_count'
                
                INNER JOIN wp_postmeta AS typeOfRoom ON typeOfRoom.post_id = posts.ID AND typeOfRoom.meta_key = 'type'" . $where;


      $stmt = $this->getDB()->prepare($query);
      $stmt->execute();
      $cheapestRoom = $stmt->fetchAll();



      if (!empty($cheapestRoom[0]['id']))
          $cheapestRoom = $this->getRoomService()->get($cheapestRoom[0]['id']);
      else

          throw new FilterException("erreur");
      
      return $cheapestRoom;
  }
  
  
  /**
   * Calcule la distance entre deux coordonnées GPS
   *
   * @param $latitudeFrom
   * @param $longitudeFrom
   * @param $latitudeTo
   * @param $longitudeTo
   *
   * @return float|int
   */
  protected function computeDistance ( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo ) : float|int {
    return ( 111.111 * rad2deg( acos( min( 1.0, cos( deg2rad( $latitudeTo ) )
          * cos( deg2rad( $latitudeFrom ) )
          * cos( deg2rad( $longitudeTo - $longitudeFrom ) )
          + sin( deg2rad( $latitudeTo ) )
          * sin( deg2rad( $latitudeFrom ) ) ) ) ) );
  }
  
  
  /**
   * Construit une ShopEntity depuis un tableau associatif de données
   *
   * @throws Exception
   */
  protected function convertEntityFromArray ( array $data, array $args ) : HotelEntity {
    $hotel = ( new HotelEntity() )
      ->setId( $data['ID'] )
      ->setName( $data['display_name'] );
    
    // Charge les données meta de l'hôtel
    $timer = Timers::getInstance();
    $timerID = $timer->startTimer('getMetas');
    $metasData = $this->getMetas( $hotel );
    $timer->endTimer('getMetas',$timerID);
    $hotel->setAddress( $metasData['address'] );
    $hotel->setGeoLat( $metasData['geo_lat'] );
    $hotel->setGeoLng( $metasData['geo_lng'] );
    $hotel->setImageUrl( $metasData['coverImage'] );
    $hotel->setPhone( $metasData['phone'] );
    
    // Définit la note moyenne et le nombre d'avis de l'hôtel

        $timerID = $timer->startTimer('getReviews');
        $reviewsData = $this->getReviews( $hotel );
        $timer->endTimer('getReviews',$timerID);
    $hotel->setRating( $reviewsData['rating'] );
    $hotel->setRatingCount( $reviewsData['count'] );
    
    // Charge la chambre la moins chère de l'hôtel
    $timerID = $timer->startTimer('getCheapest');
    $cheapestRoom = $this->getCheapestRoom( $hotel, $args );
    $timer->endTimer('getCheapest',$timerID);
    $hotel->setCheapestRoom($cheapestRoom);
    
    // Verification de la distance
    if ( isset( $args['lat'] ) && isset( $args['lng'] ) && isset( $args['distance'] ) ) {
      $hotel->setDistance( $this->computeDistance(
        floatval( $args['lat'] ),
        floatval( $args['lng'] ),
        floatval( $hotel->getGeoLat() ),
        floatval( $hotel->getGeoLng() )
      ) );
      
      if ( $hotel->getDistance() > $args['distance'] )
        throw new FilterException( "L'hôtel est en dehors du rayon de recherche" );
    }
    
    return $hotel;
  }
  
  
  /**
   * Retourne une liste de boutiques qui peuvent être filtrées en fonction des paramètres donnés à $args
   *
   * @param array{
   *   search: string | null,
   *   lat: string | null,
   *   lng: string | null,
   *   price: array{min:float | null, max: float | null},
   *   surface: array{min:int | null, max: int | null},
   *   bedrooms: int | null,
   *   bathrooms: int | null,
   *   types: string[]
   * } $args Une liste de paramètres pour filtrer les résultats
   *
   * @throws Exception
   * @return HotelEntity[] La liste des boutiques qui correspondent aux paramètres donnés à args
   */
  public function list ( array $args = [] ) : array {

    $stmt = $this->getDB()->prepare( "SELECT * FROM wp_users" );
    $stmt->execute();
    
    $results = [];
    foreach ( $stmt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
      try {
        $results[] = $this->convertEntityFromArray( $row, $args );
      } catch ( FilterException ) {
        // Des FilterException peuvent être déclenchées pour exclure certains hotels des résultats
      }
    }
    
    
    return $results;
  }
}