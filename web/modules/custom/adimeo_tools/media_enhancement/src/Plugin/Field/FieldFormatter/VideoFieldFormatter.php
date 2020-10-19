<?php

namespace Drupal\media_enhancement\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_enhancement\Plugin\Field\FieldType\VideoFieldType;

/**
 * Plugin implementation of the 'Default' formatter for 'Video Field' fields.
 *
 * @FieldFormatter(
 *   id = "video_field_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "video_field_type"
 *   }
 * )
 */
class VideoFieldFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if( !is_array($items) ){
      $items = [$items];
    }

    foreach ($items as $item){
      if( $item->first() ) {
        $elements[] = [
          '#' . VideoFieldType::FIELD_URL  => $item->first()
            ->get(VideoFieldType::FIELD_URL)
            ->getValue(),
          '#' . VideoFieldType::FIELD_ID   => $item->first()
            ->get(VideoFieldType::FIELD_ID)
            ->getValue(),
          '#' . VideoFieldType::FIELD_TYPE => $item->first()
            ->get(VideoFieldType::FIELD_TYPE)
            ->getValue(),
        ];
      }
    }

    return $elements;
  }

}
