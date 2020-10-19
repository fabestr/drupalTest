<?php

namespace Drupal\media_enhancement\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\adimeo_tools\Service\Misc;


/**
 * Plugin implementation of the 'Video Field' field type.
 *
 * @FieldType(
 *   id = "video_field_type",
 *   label = @Translation("Vidéo"),
 *   description = @Translation("A field for video"),
 *   default_widget = "video_field_widget",
 *   default_formatter = "video_field_formatter",
 * )
 */
class VideoFieldType extends FieldItemBase {

  const FIELD_URL = 'url';
  const FIELD_ID = 'id';
  const FIELD_TYPE = 'type';
  const TYPE_YOUTUBE = 'youtube';

  const PATTERN =
    '%^# Match any YouTube URL
        (?:https?://)?  # Optional scheme. Either http or https
        (?:www\.)?      # Optional www subdomain
        (?:             # Group host alternatives
          youtu\.be/    # Either youtu.be,
        | youtube\.com  # or youtube.com
          (?:           # Group path alternatives
            /embed/     # Either /embed/
          | /v/         # or /v/
          | /watch\?v=  # or /watch\?v=
          )             # End path alternatives.
        )               # End host alternatives.
        ([\w-]{10,12})  # Allow 10-12 for 11 char YouTube id.
        $%x';

  protected static $videoTypes = [
    self::TYPE_YOUTUBE,
  ];

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // File reference
    $properties[static::FIELD_URL] = DataDefinition::create('string')
      ->setLabel(t('Url de la video'));

    // Kml file url.
    $properties[static::FIELD_ID] = DataDefinition::create('string')
      ->setLabel(t('Id de la vidéo'));

    $properties[static::FIELD_TYPE] = DataDefinition::create('string')
      ->setLabel(t('Type'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        static::FIELD_URL => [
          'type'   => 'varchar',
          'length' => 255
        ],
        static::FIELD_ID => [
          'type'   => 'varchar',
          'length' => 100
        ],
        static::FIELD_TYPE => [
          'type'   => 'varchar',
          'length' => 20
        ],
      ]
    ];
  }

  /**
   * Return the video type from the video url.
   *
   * @param $url
   * @return null
   */
  public static function getVideoType($url) {
    foreach( static::$videoTypes as $videoType ){
      if( static::isType( $videoType, $url ) ){
        return $videoType;
      }
    }
    return null;
  }


  /**
   * Check if url is of the passed type.
   * @param $videoType
   * @param $url
   */
  public static function isType($videoType, $url) {
    $methodName = 'isType'.Misc::toCamelCase($videoType);
    if( method_exists(__CLASS__, $methodName ) ){
      return static::$methodName($url);
    }
    return FALSE;
  }

  /**
   * Check youtube type for passed url
   */
  public static function isTypeYoutube($url) {
    return preg_match(self::PATTERN, $url) === 1;
  }


  /**
   * Return the video ID.
   * @param $url
   * @param null $videoType
   * @return null
   */
  public static function getVideoId($url, $videoType=null){
    $videoType = is_null($videoType) ? static::getVideoId($url): $videoType;

    $methodName = 'getVideoId'.Misc::toCamelCase($videoType);
    if( method_exists(__CLASS__, $methodName ) ){
      return static::$methodName($url);
    }
    return null;
  }

  /**
   * Return the video youtube id.
   * @param $url
   * @return null
   */
  public static function getVideoIdYoutube( $url ) {
    if( $result = preg_match(self::PATTERN, $url, $matches) ){
      return $matches[1];
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get(static::FIELD_URL)->getValue());
  }


}