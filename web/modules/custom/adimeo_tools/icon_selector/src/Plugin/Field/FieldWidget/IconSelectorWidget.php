<?php

namespace Drupal\icon_selector\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget;

/**
 * Plugin implementation of the 'Icon selector' widget.
 *
 * @FieldWidget(
 *   id = "icon_selector_widget",
 *   label = @Translation("Selection d'icone"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class IconSelectorWidget extends TextfieldWidget {

  const ASSETS_DIR = '/assets/build/svg/';


  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $element['details'] = [
      '#type'       => 'details',
      '#title'      => $element['#title'],
      '#open'       => $this->getSettings()['open'],
      '#attributes' => ['class' => ['icon_selector_details']]
    ];

    $element['#attached']['library'][] = 'icon_selector/icon_selector';

    $default_value = $item->get('value')->getValue();

    // Init fields form
    foreach ($this->getIconeSelection() as $key => $icon) {
      $valuePattern = $this->getSettings()['value_pattern'];
      $value = $icon['file'];
      if ($valuePattern == 'path') {
        //$value = $icon['file'];
      }
      if ($valuePattern == 'name') {
        $value = $icon['label'];
      }
      if ($valuePattern == 'fa') {
        $value = 'fa-'.$icon['label'];
      }


      $element['details'][$key] = [
        '#type'       => 'container',
        'icone'       => [
          '#theme'  => 'image',
          //'#uri'    => $icon['file'],
          '#width'  => '50',
          '#height' => '50',
          '#attributes' => [
            'class' => ['lazy'],
            'data-src' => $icon['file']
          ]
        ],
        'input'       => [
          '#type'       => 'radio',
          '#title'      => $icon['label'],
          '#parents'    => ['value'],
          '#value'      => $value,
          '#attributes' => [
            'value' => $value,
          ],
        ],
        '#attributes' => [
          'class' => ['icon_selector_item']
        ]
      ];
      if ($value == $default_value) {
        $element['details'][$key]['#attributes']['class'][] = 'selected';
        $element['details'][$key]['#default_value'] = $value == $default_value ? $value : 0;
      }
    }

    $element['details']['value'] = [
      '#type'          => 'hidden',
      '#default_value' => $default_value,
    ];
    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as &$value) {
      $value['value'] = $value['details']['value'];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get available dirs.
    $availableDirs = static::getAvailableDirs();
    $element['dir'] = [
      '#type'          => 'select',
      '#title'         => t('Repertoire d\'icones'),
      '#default_value' => $this->getSettings()['dir'],
      '#required'      => TRUE,
      '#options'       => $availableDirs,
    ];
    $element['value_pattern'] = [
      '#type'          => 'select',
      '#title'         => t('Récupérer le chemin complet ou uniquement le nom de l\'icône ?'),
      '#default_value' => $this->getSettings()['value_pattern'],
      '#required'      => TRUE,
      '#options'       => [
        'path' => "Chemin complet avec le .svg",
        'fa'   => 'Font Awesome',
        'name' => 'Nom brut'
      ],
      '#description'   => 'Utiliser le nom brut pour utiliser un système d\'icon-font custom (==> "facebook").<br>
        Font Awesome permet de récupérer le nom de l\'icône préfixé de "fa" (==> "fa-facebook").<br>
        Le chemin complet renvoi le chemin utilisable dans une balise img (==> "/themes/custom/nom_theme/assets/build/svg/icon-social/facebook.svg")'
    ];
    $element['open'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Ouvrir le selecteur par défault'),
      '#default_value' => $this->getSettings()['open'],
    ];
    return $element;
  }


  public static function defaultSettings() {
    $availableDirs = static::getAvailableDirs();
    return [
        'dir'  => reset($availableDirs),
        'open' => FALSE,
        'value_pattern' => 'path'
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Repertoire d\'icone: @dir', ['@dir' => $this->getSettings()['dir']]);

    return $summary;
  }

  protected static function getCurrentThemePath() {
    $themeConfig = \Drupal::config('system.theme');
    return '/' . drupal_get_path('theme', $themeConfig->get('default'));
  }

  protected static function getDirPath() {
    return DRUPAL_ROOT . static::getCurrentThemePath() . static::ASSETS_DIR;
  }

  protected static function getAvailableDirs() {
    $dirs = [];
    foreach (glob(static::getDirPath() . '*') as $file) {
      if (is_dir($file)) {
        if ($name = basename($file)) {
          $dirs[$name] = t($name);
        }
      }
    }
    return $dirs;
  }

  protected function getIconeSelection() {
    $path = static::getDirPath() . $this->getSettings()['dir'];
    $icones = [];
    $url = static::getCurrentThemePath() . static::ASSETS_DIR . $this->getSettings()['dir'] . '/';
    foreach (glob($path . '/*.svg') as $icone) {
      $fileName = basename($icone);
      $icones[$fileName] = [
        'label' => substr($fileName, 0, strrpos($fileName, '.')),
        'file'  => $url . $fileName,
      ];
    }
    return $icones;
  }

}
