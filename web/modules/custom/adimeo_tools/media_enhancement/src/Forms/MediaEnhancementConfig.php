<?php

namespace Drupal\media_enhancement\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_enhancement\Service\MediaEnhancementManager;

/**
 * Class MediaEnhancementConfig.
 *
 * @package Drupal\media_enhancement\Forms
 */
class MediaEnhancementConfig extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_enhancement.conf';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[MediaEnhancementManager::CONF_SRCSET_ENABLE] = [
      '#type'          => 'checkbox',
      '#title'         => t('Activer la mise en place des srcset'),
      '#default_value' => MediaEnhancementManager::me()
        ->get(MediaEnhancementManager::CONF_SRCSET_ENABLE),
    ];

    $form['submit'] = [
      '#type'        => 'submit',
      '#value'       => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    MediaEnhancementManager::me()->setAllValues($form_state->getValues());
  }

}
