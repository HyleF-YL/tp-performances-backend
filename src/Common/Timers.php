<?php

namespace App\Common;

/**
 * Classe utilitaire pour obtenir des informations de temps sur certains points clés de l'application
 *
 * @example ```php
 * $timer = Timers::getInstance();
 *
 * $timerId = $timer->startTimer('myLabel');
 * myFunctionToMeasure();
 * $timer->endTimer('myLabel', $timerId);
 *
 * header('Server-Timing: ' . Timers::getInstance()->getTimers() );
 * ```
 */
class Timers {
  
  use SingletonTrait;
  private array $timers = [];
  private function __construct () { }
  
  
  /**
   * Démarre un nouveau timer
   *
   * @param string      $name Un nom qui sera utilisé pour regrouper un ou plusieurs timers entre eux
   * @param string|null $label Un label facultatif pour mieux décrire ce qui a été mesuré. Il sera affiché dans le navigateur
   *
   * @return string L'ID du timer qui a été généré
   */
  public function startTimer ( string $name, string $label = null ) : string {
    if ( ! isset( $this->timers[$name] ) )
      $this->timers[$name] = [];
    
    $timerId = uniqid();
    $this->timers[$name][$timerId] = [
      'start' => microtime( true ),
      'desc' => $label,
    ];
    
    return $timerId;
  }
  
  
  /**
   * Arrête un timer
   *
   * @param string $name  Le nom qui a été spécifié dans l'appel de startTimer()
   * @param string $timerId L'ID qui a été retourné par startTimer()
   *
   * @return void
   */
  public function endTimer ( string $name, string $timerId ) : void {
    $this->timers[$name][$timerId]['end'] = microtime( true );
  }
  
  
  /**
   * Calcule la durée de chacun des timers et les formatent pour qu'ils soient écrits en header HTTP
   *
   * @example
   *
   * @return string
   */
  public function getTimers () : string {
    // Si on n'a aucun timer, on sort directement
    if ( empty( $this->timers ) )
      return "";
    
    $metrics = [];
    foreach ( $this->timers as $name => $timers ) {
      // On convertit chaque sous-tableau de timer en temps écoulé
      $timeTaken = array_map( function($timer) {
        return ( $timer['end'] - $timer['start'] ) * 1000;
      }, $timers);
      
      // On additionne tous les temps écoulés du sous-tableau
      $timeTaken = array_sum($timeTaken);
      
      // On formate la durée écoulée
      $output = sprintf( '%s;dur=%f', $name, $timeTaken );
      
      // On ajoute une description optionnelle
      if ( isset($timers[0]['desc']) )
        $output .= sprintf( ';desc="%s"', addslashes( $timers[0]['desc'] ) );
      
      $metrics[] = $output;
    }
    
    return implode( ', ', $metrics );
  }
}