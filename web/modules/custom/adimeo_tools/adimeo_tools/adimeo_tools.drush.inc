<?php

use \Drupal\adimeo_tools\Drush\AdimeoToolsCommandsDrush8;

function adimeo_tools_drush_command() {

  /**
   * ATTENTION !!!
   *
   * Les commandes drush doivent être disponibles pour les version 8 et 9, pour gérer
   * une retrocompat.
   * Donc ce fichier gère uniquement l'interface pour drush 8.
   * Il ne doit pas y avoir de contenu ici, car il ne sera pas lu par Drush 9.
   *
   * Il faut créer vos fonctions au format Drush 9 (avec déclarations des variables via dockblocks)
   * dans le fichier \AdimeoToolsCommandsTrait.
   * Ensuite vous déclarez vos commandes via l'interface Drush 8 ci-dessous.
   * Et faire appel à la classe AdimeoToolsCommandsDrush8 qui utilise le trait précédemment cité.
   */
  $commandList = [
    'set_module_version'   => [
      'description' => 'Set the module version in order to redo hook_updates.',
      'aliases'     => ['smv'],
      'arguments'   => [
        'moduleName' => 'Module name',
        'version'    => 'Update Id',
      ],
    ],
    'reload-module-config' => [
      'description' => 'Reload module default configuration',
      'aliases'   => ['rmc'],
      'arguments' => [
        'module' => 'Module name',
      ],
    ]
  ];

  return $commandList;
}

/**
 * Reload module default config
 *
 * @param $module
 */
function drush_adimeo_tools_reload_module_config($module) {
  /** @var \Drupal\adimeo_tools\Drush\AdimeoToolsCommandsDrush8 $adimeoToolsDrushCommands8 */
  $adimeoToolsDrushCommands8 = \Drupal::service(AdimeoToolsCommandsDrush8::SERVICE_NAME);
  $adimeoToolsDrushCommands8->reloadModuleConfig($module);
}

/**
 * Set the module version in order to redo hook_updates
 * @param null $moduleName
 * @param null $version
 */
function drush_adimeo_tools_set_module_version($moduleName = NULL, $version = NULL) {
  /** @var \Drupal\adimeo_tools\Drush\AdimeoToolsCommandsDrush8 $adimeoToolsDrushCommands8 */
  $adimeoToolsDrushCommands8 = \Drupal::service(AdimeoToolsCommandsDrush8::SERVICE_NAME);
  $adimeoToolsDrushCommands8->setModuleVersion($moduleName, $version);
}
