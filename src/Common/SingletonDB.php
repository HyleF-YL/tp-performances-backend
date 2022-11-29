<?php

namespace App\Common;

use PDO;

class SingletonDB
{
    public PDO $db;
    static private SingletonDB $instance;

    public function __construct()
    {
        $this->db = new PDO( "mysql:host=db;dbname=tp;charset=utf8mb4", "root", "root");
    }

    public static function get () : PDO {
        // Si on n'a pas d'instance initialisée, on en instancie une
        if ( ! isset( self::$instance ) )
            self::$instance = new static();
        return self::$instance->db;
    }
}