<?php

namespace Drupal\tac_services\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\adimeo_tools\Service\LanguageService;
use Drupal\tac_services\Service\TacGlobalConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TacSettingsForm.
 *
 * @package Drupal\tac_services\Form
 */
class TacSettingsForm extends FormBase {

  use MessengerTrait;

  /**
   * Constant which stores the form ID.
   */
  const FORM_ID = 'tac_services.settings_form';

  /**
   * The conf storage service.
   *
   * @var TacGlobalConfigService
   */
  private $config;

  /**
   * @var LanguageService
   */
  private $languageService;

  /**
   * Class constructor.
   */
  public function __construct(LanguageService $languageService, TacGlobalConfigService $configService) {
    $this->languageService = $languageService;
    $this->config = $configService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('adimeo_tools.language'),
      $container->get('tac_services.settings_manager')
    );
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
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $defaultValues = $this->config->getAllValues();

    $form[$this->config::HIGH_PRIVACY] = [
      '#type'          => 'checkbox',
      '#title'         => t('High privacy'),
      '#default_value' => $defaultValues[$this->config::HIGH_PRIVACY],
      '#description'   => t('Désactiver le consentement implicite (en naviguant) ?'),
    ];

    $form[$this->config::ALLOWED_BUTTON] = [
      '#type'          => 'checkbox',
      '#title'         => t('Bouton d\'acceptation des cookies'),
      '#default_value' => $defaultValues[$this->config::ALLOWED_BUTTON],
      '#description'   => t('Ce bouton s\'active uniquement si la case "High privacy" est cochée.'),
    ];

    $form[$this->config::ORIENTATION] = [
      '#type'          => 'radios',
      '#title'         => t('Orientation'),
      '#default_value' => $defaultValues[$this->config::ORIENTATION],
      '#description'   => t('le bandeau doit être en haut ou en bas ?'),
      '#options'       => [
        'top'    => t('En haut'),
        'bottom' => t('En bas'),
      ],
    ];

    $form[$this->config::ADBLOCKER] = [
      '#type'          => 'checkbox',
      '#title'         => t('Adblocker'),
      '#default_value' => $defaultValues[$this->config::ADBLOCKER],
      '#description'   => t('Afficher un message si un adblocker est détecté ?'),
    ];

    $form[$this->config::SHOW_ALERT_SMALL] = [
      '#type'          => 'checkbox',
      '#title'         => t('Small alert box'),
      '#default_value' => $defaultValues[$this->config::SHOW_ALERT_SMALL],
      '#description'   => t('Afficher le petit bandeau en bas à droite ?'),
    ];

    $form[$this->config::COOKIE_LIST] = [
      '#type'          => 'checkbox',
      '#title'         => t('Cookies list'),
      '#default_value' => $defaultValues[$this->config::COOKIE_LIST],
      '#description'   => t('Afficher la liste des cookies installés ?'),
    ];

    $default = $this->config->getAlertLabel();
    $form[$this->config::ALERT_LABEL] = [
      '#type'          => 'text_format',
      '#title'         => t('Message de l\'encart d\'alert'),
      '#default_value' => $default ? $default['value'] : '',
      '#format'        => $default ? $default['format'] : 'full_html',
    ];

    $form['submit'] = [
      '#type'        => 'submit',
      '#value'       => t('Save'),
      '#button_type' => 'primary',
      '#weight'      => 1000,
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValues();

    // Modification des données "languagée".
    $data[$this->config::ALERT_LABEL] = $this->config->get($this->config::ALERT_LABEL);
    $data[$this->config::ALERT_LABEL][$this->languageService->getCurrentLanguageId()] = $form_state->getValue($this->config::ALERT_LABEL);
    $this->config->setAllValues($data);

    // Add success message.
    $this->messenger()->addMessage(t('The configuration options have been saved.'));
  }

}
