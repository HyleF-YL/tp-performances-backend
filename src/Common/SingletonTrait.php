<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace App\Common;

use PDO;

/**
 * Trait utilitaire pour rendre qu'une classe soit un singleton
 *
 * @exemple ```php
 * class MyClass {
 *
 *  use SingletonTrait;
 *
 *  protected function __construct() {
 *    // TODO initialization stuff
 *  }
 * }
 * ```
 */
trait SingletonTrait {

    /**
   * L'instance du singleton
   * @var static
   */
  static private $instance;
  
  
  /**
   * Récupère l'instance unique du singleton
   *
   * @return static
   */
  public static function getInstance () : static {
    // Si on n'a pas d'instance initialisée, on en instancie une
    if ( ! isset( self::$instance ) )
      self::$instance = new static();
    
    return self::$instance;
  }
}