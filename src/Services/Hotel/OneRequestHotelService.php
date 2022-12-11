<?php
    namespace App\Services\Hotel;

    use App\Common\SingletonDB;
    use App\Common\Timers;
    use App\Entities\HotelEntity;
    use App\Services\Room\AbstractRoomService;
    use PDO;
    use PDOStatement;

    class OneRequestHotelService extends AbstractHotelService {

        protected function getDB (): PDO
        {
            $timer = Timers::getInstance();
            $timerID = $timer->startTimer('getDB');
            $pdo = SingletonDB::get();
            $timer->endTimer('getDB',$timerID);
            return $pdo;
        }

        public function list(array $args = []): array
        {
            // TODO: Implement list() method.
        }

        /**
         * @param array $args
         * @return PDOStatement
         */

        protected function buildQuery(array $args) : PDOStatement {

        }
    }
