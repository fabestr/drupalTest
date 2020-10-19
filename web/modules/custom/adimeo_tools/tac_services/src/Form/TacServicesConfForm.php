<?php

namespace Drupal\tac_services\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\tac_services\Service\TacServicePluginManager;
use Drupal\tac_services\Service\TacServicesConfManager;
use Drupal\tac_services\Interfaces\TacServiceInterface;

/**
 * Class TacServicesConfForm.
 *
 * @package Drupal\tac_services\Form
 */
class TacServicesConfForm extends FormBase {

  use MessengerTrait;

  /**
   * Constant which stores the form ID.
   */
  const FORM_ID = 'tac_services.configuration_form';

  /**
   * The plugin manager.
   *
   * @var TacServicePluginManager
   */
  protected $pluginManager;

  /**
   * The conf manager.
   *
   * @var TacServicesConfManager
   */
  protected $confManager;

  /**
   * TacServicesConfForm constructor.
   */
  public function __construct() {
    $this->pluginManager = \Drupal::service(TacServicePluginManager::SERVICE_NAME);
    $this->confManager = \Drupal::service(TacServicesConfManager::SERVICE_NAME);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return str_replace('.', '_', static::FORM_ID);
  }

  /**
   * Build the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *    The form to be builded.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $definitions = $this->pluginManager->getDefinitions();

    $defaultValues = $this->confManager->getTacServicesConf() ?: [];

    $form['tabs'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Tabs'),
    );

    // Initialisation des services.
    foreach ($definitions as $definition) {
      $serviceDefaultValues = array_key_exists($definition['id'], $defaultValues) ? $defaultValues[$definition['id']] : [];
      $this->initServiceForm($definition, $serviceDefaultValues, $form, $form_state);
    }

    $form['submit'] = [
      '#type'        => 'submit',
      '#value'       => t('Save'),
      '#button_type' => 'primary',
      '#weight'      => 1000,
    ];

    return $form;
  }

  /**
   * Submission form method.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $data = [];

    $definitions = $this->pluginManager->getDefinitions();
    uasort($definitions, function ($definition1, $definition2) {
      if ($definition1['weight'] == $definition2['weight']) {
        return 0;
      }
      return $definition1['weight'] < $definition2['weight'] ? -1 : 1;
    });

    foreach ($definitions as $definition) {
      $data[$definition['id']] = [];

      /** @var TacServiceInterface $instance */
      $instance = $this->pluginManager->getInstance($definition);
      $data[$definition['id']]['data'] = $instance->prepareTacServiceConfData($form, $form_state);

      // Is the Service active ?
      $data[$definition['id']]['is_active'] = $form_state->getValue($definition['id'] . '_boolean');

      // Add the library name.
      $data[$definition['id']]['library'] = $instance->getTacServiceLibrary();
    }

    // Store data in conf.
    $this->confManager->setTacServicesConf($data);

    // Add success message.
    $this->messenger()->addMessage(t('The configuration options have been saved.'));
  }

  /**
   * Initialise le sous formulaire d'un service.
   *
   * @param array $definition
   *   Données du services.
   * @param array $serviceDefaultValues
   *   Valeurs par défaut.
   * @param array &$form
   *   Le formulaire.
   * @param FormStateInterface $formstate
   *   Le formstate.
   */
  protected function initServiceForm(array $definition, array $serviceDefaultValues, array &$form, FormStateInterface $formstate) {
    $serviceId = $definition['id'];

    $form[$serviceId] = [
      '#type'   => 'details',
      '#title'  => $definition['label'],
      '#weight' => $definition['weight'],
      '#group' => 'tabs',
    ];

    /** @var TacServiceInterface $instance */
    $instance = $this->pluginManager->getInstance($definition);

    $form[$serviceId][$serviceId . '_boolean'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Activate Service'),
      '#default_value' => array_key_exists('is_active', $serviceDefaultValues) ? $serviceDefaultValues['is_active'] : NULL,
      '#required'      => FALSE,
    ];

    $formFields = $instance->getTacServiceConfForm();
    if (!empty($formFields)) {
      foreach ($formFields as $key => $formField) {
        $form[$serviceId][$key] = $formField;
        $form[$serviceId][$key]['#states'] = [
          'visible'  => [
            ':input[name="' . $serviceId . '_boolean"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="' . $serviceId . '_boolean"]' => ['checked' => TRUE],
          ],
        ];
      }
    }
  }

}
