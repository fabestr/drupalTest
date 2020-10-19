<?php

namespace Drupal\adimeo_tools\Drush;

/**
 * Class AdimeoToolsCommandDrush8.
 *
 * Classe tampon pour utiliser le trait.
 * !Attention, si vous déclarez des fonctions ici, elles ne seront pas
 * utilisables en Drush 9.
 * Ajoutez vos fonction dans le AdimeoToolsCommandsTrait.
 * Elles seront alors dispo dans les deux versions de drush.
 *
 * @package Drupal\adimeo_tools\Drush
 */
class AdimeoToolsCommandsDrush8 {

  const SERVICE_NAME = 'adimeo_tools.commands_drush_8';

  use AdimeoToolsCommandsTrait;

}
