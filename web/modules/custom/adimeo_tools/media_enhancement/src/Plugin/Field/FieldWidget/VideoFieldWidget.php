<?php

namespace Drupal\media_enhancement\Plugin\Field\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_enhancement\Plugin\Field\FieldType\VideoFieldType;


/**
 * Plugin implementation of the 'Video Field' widget.
 *
 * @FieldWidget(
 *   id = "video_field_widget",
 *   label = @Translation("Video"),
 *   field_types = {
 *     "video_field_type"
 *   }
 * )
 */
class VideoFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $defaultValue = $items[$delta]->get(VideoFieldType::FIELD_URL)->getValue();

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => t('Url de la video '),
      '#default_value' => $defaultValue ? : '',
      '#element_validate' => [[get_called_class(), 'checkUrl']],
    ];

    return $element;
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public static function checkUrl(array $element, FormStateInterface $formState) {
    if( !empty($element['#value']) ){
      $type = VideoFieldType::getVideoType($element['#value']);
      if( !$type ){
        $formState->setError($element, t('L\'url de la vidéo n\'est pas reconnue'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach( $values as &$value ){
      $url = $value[ VideoFieldType::FIELD_URL ];
      if( $type = VideoFieldType::getVideoType($url) ){
        $value[VideoFieldType::FIELD_TYPE] = $type;
        $value[VideoFieldType::FIELD_ID] = VideoFieldType::getVideoId($url, $type);
      }
    }

    return $values;
  }


}
