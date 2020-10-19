<?php

namespace Drupal\metatag_importer\Form;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\adimeo_tools\Service\LanguageService;
use Drupal\adimeo_tools\Shared\BatchTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MetatagImport extends FormBase {

  use BatchTrait;

  const FORM_ID = 'metatag_importer.metatag_import';
  const FIELD_FILE = 'file';
  const SEPARATOR = ';';
  const WRAPPER = '"';
  const URL = 'URL de la page';

  protected $currentLine;

  /**
   * La map des colonnes.
   *
   * @var array
   *   Map colonne field.
   */
  protected $columnMap = [
    self::URL     => 0,
    'title'       => 1,
    'description' => 2,
  ];
  /**
   * @var LanguageService
   */
  private $languageService;

  /**
   * Class constructor.
   */
  public function __construct(LanguageService $languageService) {
    $this->languageService = $languageService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('adimeo_tools.language')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $authorised = 'csv';
    $form[static::FIELD_FILE] = [
      '#type'              => 'managed_file',
      '#title'             => t('Fichier'),
      '#upload_validators' => [
        'file_validate_extensions' => [$authorised],
        'file_validate_size'       => array(25600000)
      ],
      '#description'       => $this->getDescription($authorised),
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
    // Récupération des données à importer.
    $dataToImport = $this->getDataToImport($form_state);

    // Initialisation du batch.
    $operations = $this->getBatchOperations(
      '\\' . get_called_class() . '::processLine',
      $dataToImport
    );

    // Launch batch.
    $batch = array(
      'title'      => 'Import des metatags',
      'operations' => $operations,
      'finished'   => '\\' . get_called_class() . '::processEnd',
    );
    batch_set($batch);

  }

  /**
   * Retourne la liste des toutes les données à importer.
   *
   * @return array
   *   La liste de données.
   */
  protected function getDataToImport(FormStateInterface $formState) {
    $nodesDataList = [];

    // On récupère le fichier.
    if ($file = File::load($formState->getValue(static::FIELD_FILE)[0])) {
      // On parse le fichier.
      $path = $file->getFileUri();

      if ($path) {
        if ($handle = fopen($path, 'r')) {
          while (FALSE !== ($data = fgetcsv($handle, NULL, static::SEPARATOR, static::WRAPPER))) {
            $nodesDataList[] = $data;
          }
        }
      }

      return $nodesDataList;
    }

    return [];
  }

  /**
   * Importe une ligne de csv.
   *
   * @param array $data
   *   Données à importer.
   * @param array $context
   *   Context.
   */
  public static function processLine(array $data, array &$context) {

    if (!array_key_exists('errors', $context['results'])) {
      $context['results']['errors'] = [];
    }
    // On stocke la ligne courante, c'est plus simple pour l'accès.
    /** @var static $form */
    $form = new static();
    foreach ($data as $line) {
      $context['results']['imported'] += $form->importLine($line, $context['results']['errors']) ? 1 : 0;
      $context['results']['total']++;
    }
  }

  /**
   * Fin du process batch.
   *
   * @param bool $success
   *   Données de succès.
   * @param array $results
   *   Données de résultat.
   * @param array $operations
   *   Les opérations.
   */
  public static function processEnd($success, array $results, array $operations) {

    if ($results['imported'] < $results['total']) {
      $translation = [
        '@imported' => $results['imported'],
        '@total'    => $results['total'],
      ];

      if ($results['imported'] == 0) {
        \Drupal::messenger()
          ->addError(t('Attention !! Aucun élément n\'a été importé sur @total éléments.',
            $translation));
      }
      else {
        \Drupal::messenger()
          ->addWarning(t('Attention !! Seuls @imported/@total éléments ont été importés.',
            $translation));
      }

      if (count($results['errors']) > 0) {
        // @codingStandardsIgnoreStart
        $loop = implode('</li><li>', $results['errors']);
        \Drupal::messenger()
          ->addWarning(t('Voici les éléments non importés <ul><li>' . $loop . '</li></ul>'));
        // @codingStandardsIgnoreEnd
      }
    }
  }

  /**
   * Importe une ligne.
   *
   * @param array $data
   *   Données à importer.
   * @param array $errors
   *   Tableau d'erreur.
   *
   * @return bool
   *   L'état de l'import.
   */
  public function importLine(array $data, array &$errors) {
    // On stocke la ligne courante, c'est plus simple pour le traitement.
    $this->currentLine = $data;

    if ($entity = $this->getCurrentEntity()) {

      // On récupère le field metatag.
      $metatagFieldNames = $this->getMetatagFieldsNames($entity);

      if (!empty($metatagFieldNames)) {
        foreach ($metatagFieldNames as $metatagFieldName) {
          $this->initFieldNameForCurrentEntity($metatagFieldName, $entity);
        }
        $entity->save();

        return TRUE;
      }
    }

    $url = $this->getValue(static::URL);
    $errors[] = $url;

    return FALSE;
  }

  /**
   * Retourne la valeur de la ligne pour la clé de colonne passée.
   *
   * @param string $key
   *   La clé.
   *
   * @return string
   *   La valeur.
   */
  protected function getValue($key) {
    return $this->currentLine[$this->columnMap[$key]];
  }

  /**
   * Retourne l'objet assoicé à l'url de la ligne courante.
   *
   * @return ContentEntityBase|null
   *   L'entité lié à l'url.
   */
  protected function getCurrentEntity() {
    $url = trim($this->getValue(static::URL));

    // Récupération du path.
    if (strpos($url, 'http') === 0) {
      $path = substr($url, strpos($url, '/', 8));
    }
    else {
      $path = $url;
    }

    $urlObject = \Drupal::service('path.validator')->getUrlIfValid($path);
    if (!$urlObject) {
      $urlObject = $this->getUrlFromRedirect($path);
    }
    $entity = NULL;
    if ($urlObject) {
      switch ($urlObject->getRouteName()) {
        case 'entity.node.canonical':
          $entity = $this->languageService->load('node', $urlObject->getRouteParameters()['node'], NULL, $this->languageService::MODE_NO_ENTITY_IF_NO_TRANSLATION_EXISTS);
          break;

        case 'entity.taxonomy_term.canonical':
          $entity = $this->languageService->load('taxonomy_term', $urlObject->getRouteParameters()['taxonomy_term'], NULL, $this->languageService::MODE_NO_ENTITY_IF_NO_TRANSLATION_EXISTS);
          break;

        default:
          $entity = NULL;
      }
    }

    // Déclaration du hook.
    $hook = 'adimeo_metatag_import_line';
    $info = [
      'currentLine' => $this->currentLine,
    ];
    \Drupal::moduleHandler()
      ->alter($hook, $entity, $info);

    return $entity;
  }

  /**
   * On retourne les noms des champs de type metatag.
   *
   * @param ContentEntityBase $entity
   *   L'entitée.
   *
   * @return array
   *   Le noms des champs mététag.
   */
  protected function getMetatagFieldsNames(ContentEntityBase $entity) {
    $fields = [];
    /** @var \Drupal\field\Entity\FieldConfig $fieldDefinition */
    foreach ($entity->getFieldDefinitions() as $fieldName => $fieldDefinition) {
      if ($fieldDefinition->getType() == 'metatag') {
        $fields[] = $fieldName;
      }
    }

    return $fields;
  }

  /**
   * Initialise le champs passé pour l'entitée passée.
   *
   * @param string $metatagFieldName
   *   Le field.
   * @param ContentEntityBase $entity
   *   L'entité.
   */
  protected function initFieldNameForCurrentEntity($metatagFieldName, ContentEntityBase $entity) {
    // On récupère les valeur précédentes en clair.
    $prevValue = unserialize($entity->get($metatagFieldName)->value);

    // On parse les données, et on les remplace.
    foreach (array_diff(array_keys($this->columnMap), [static::URL]) as $key) {
      $newValue = $this->getValue($key);
      if (!empty($newValue)) {
        $prevValue[$key] = $newValue;
      }
    }

    $entity->set($metatagFieldName, serialize($prevValue));
  }

  /**
   * Retourne la description du champs d'upload de csv.
   *
   * @param string $authorised
   *   Les éléments autorisés.
   *
   * @return string
   *   La description.
   */
  protected function getDescription($authorised) {
    $description = 'Types authorisés : ' . $authorised
      . '<br/>Separateur : "' . static::SEPARATOR . '" <br/>Wrapper : "' . static::WRAPPER . '" <br/><br/>Ex:<br/>';

    $keys = array_flip($this->columnMap);
    ksort($keys);
    $data = [];
    foreach ($keys as $key) {
      $data[] = static::WRAPPER . ($key == static::URL ? '' : 'Meta - ') . $key . static::WRAPPER;
    }
    $description .= implode(static::SEPARATOR, $data);
    return $description;
  }

  /**
   * Retourne l'objet route via redirect.
   *
   * @param string $path
   *   Le path.
   *
   * @return mixed|null
   *   La route si existante.
   */
  protected function getUrlFromRedirect($path) {
    if (\Drupal::moduleHandler()->getModule('redirect')) {

      // Distinction langue / url.
      $path = trim($path, '/');
      $urlData = explode('/', $path);
      if (array_key_exists(reset($urlData), \Drupal::languageManager()
        ->getLanguages())) {
        $search = implode(array_slice($urlData, 1), '/');
      }
      else {
        $search = $path;
      }

      $query = \Drupal::database()->select('redirect', 'r');
      $query->fields('r', ['redirect_redirect__uri']);
      $query->condition('r.redirect_source__path', $search);
      $realUri = $query->execute()->fetchField();

      return \Drupal::service('path.validator')
        ->getUrlIfValid(str_replace('internal:', '', $realUri));
    }
    return NULL;
  }

}
