<?php

namespace Drupal\adimeo_tools\Service;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Class LanguageService.
 *
 * Service allowing to get load and translate elements.
 *
 * @package Drupal\adimeo_tools\Service
 */
class LanguageService {

  /**
   * Service name.
   */
  const SERVICE_NAME = 'adimeo_tools.language';

  /**
   * Taxonomy entity id.
   */
  const TERM = 'taxonomy_term';

  /**
   * Node entity id.
   */
  const NODE = 'node';

  /**
   * Mode of translation: default if not exists.
   *
   * In this mode, the translations returns the entity in its default language
   * if no translation exists for defined languages.
   */
  const MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS = 'DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS';

  /**
   * Mode of translation: no entity if no languages exists.
   *
   * In this mode, the translations returns null if no translation
   * exists for defined languages.
   */
  const MODE_NO_ENTITY_IF_NO_TRANSLATION_EXISTS = 'NO_ENTITY_IF_NO_TRANSLATION_EXISTS';

  /**
   * Cache.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * The injected services :
   *
   *
   *
   * @var EntityTypeManager
   */
  private $entityTypeManager;
  /**
   * @var EntityRepository
   */
  private $entityRepository;
  /**
   * @var LanguageManagerInterface
   */
  private $languageManager;

  /**
   * Retourne le singleton (quand pas d'injection de dépendances possible)
   *
   * @return static
   *   Le singleton.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * LanguageService constructor.
   *
   * @param EntityRepository $entityRepository
   *
   * @param LanguageManager $language_manager
   *   The language manager service.
   *
   * @param EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManager $entityTypeManager,EntityRepository $entityRepository, LanguageManager $languageManager) {
      $this->entityTypeManager = $entityTypeManager;
      $this->entityRepository = $entityRepository;
      $this->languageManager = $languageManager;
  }

  /**
   * Translate the entity if needed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to translate.
   * @param string $languageId
   *   The language in which the entity should be translated.
   * @param string $mode
   *   Mode of recovery of the translated entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The translated entity.
   */
  public function translate(EntityInterface $entity = NULL, $languageId = NULL, $mode = self::MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS) {

    if ($entity) {
      $languageId = isset($languageId) ? $languageId : $this->getCurrentLanguageId();

      // Création du cache de language.
      if (!array_key_exists($languageId, $this->cache)) {
        $this->cache[$languageId] = [];
      }

      // Création de l'id de cache.
      $cacheId = $entity->getEntityTypeId() . '_' . $entity->id() . '_' . $mode;

      // Si l'id de cache n'existe pas, c'est que l'entité n'a pas été loadée :
      if (!array_key_exists($cacheId, $this->cache[$languageId])) {
        $resultEntity = $entity;

        // Translate only if language is the not the default one.
        if ($entity->language()->getId() !== $languageId) {
          $resultEntity = $this->entityRepository->getTranslationFromContext($entity, $languageId);
        }

        if ($mode == self::MODE_NO_ENTITY_IF_NO_TRANSLATION_EXISTS && $resultEntity->language()
          ->getId() != $languageId
        ) {
          $resultEntity = NULL;
        }

        $this->cache[$languageId][$cacheId] = $resultEntity;
      }

      return $this->cache[$languageId][$cacheId];
    }

    return NULL;
  }

  /**
   * Translate a list of entities if needed.
   *
   * @param array $entities
   *   The list of entities to translate.
   * @param string $languageId
   *   The language in which the entity should be translated.
   * @param string $mode
   *   Mode of recovery of the translated entity.
   *
   * @return array
   *   The list of translated entities
   */
  public function translateMultiple(array $entities, $languageId = NULL, $mode = self::MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS) {
    $languageId = isset($languageId) ? $languageId : $this->getCurrentLanguageId();

    // Translate only if language is the not the default one.
    $translatedEntities = [];
    foreach ($entities as $key => $entity) {
      $translatedEntities[$key] = $this->translate($entity, $languageId, $mode);
    }
    $translatedEntities = array_filter($translatedEntities);
    return $translatedEntities;
  }

  /**
   * Load an entity by id in a current|specific language.
   *
   * @param string $type
   *   The entity type to load.
   * @param string|int $id
   *   The entity to load.
   * @param string $languageId
   *   The language in which the entity should be translated.
   * @param string $mode
   *   Mode of recovery of the translated entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The translated entity
   */
  public function load($type, $id, $languageId = NULL, $mode = self::MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS) {
    $languageId = isset($languageId) ? $languageId : $this->getCurrentLanguageId();

    // Translate only if language is the not the default one.
    if ($entityManager = $this->getEntityManager($type)) {
      $entityToTranslate = $entityManager->load($id);
      return $this->translate($entityToTranslate, $languageId, $mode);
    }

    return NULL;
  }

  /**
   * Load multiple entities in a current|specific language.
   *
   * @param string $type
   *   The entity type to load.
   * @param array $ids
   *   The list of ids to load.
   * @param string $languageId
   *   The language in which the entity should be translated.
   * @param string $mode
   *   Mode of recovery of the translated entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of translated entites.
   */
  public function loadMultiple($type, array $ids, $languageId = NULL, $mode = self::MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS) {
    $languageId = isset($languageId) ? $languageId : $this->getCurrentLanguageId();

    // Translate only if language is the not the default one.
    if ($entityManager = $this->getEntityManager($type)) {
      $entitiesToTranslate = $entityManager->loadMultiple($ids);
      return $this->translateMultiple($entitiesToTranslate, $languageId, $mode);
    }

    return NULL;
  }

  /**
   * Return the entityManager.
   *
   * @param string $type
   *   The type of the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage interface.
   *
   * @throws \Exception
   */
  protected function getEntityManager($type) {
    if ($entityManager = $this->entityTypeManager->getStorage($type)) {
      return $entityManager;
    }
    throw new \Exception('Unable to load entity type manager for \'' . $type . '\'');
  }

  /**
   * Return the current Language.
   *
   * @return string
   *   The current language id.
   */
  public function getCurrentLanguageId() {
    return $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
  }

  /**
   * Check if the current language is the default language.
   *
   * @return bool
   *   Whether the current language is the default language or not.
   */
  public function currentLanguageIsDefault() {
    return $this->languageIdIsDefault($this->getCurrentLanguageId());
  }

  /**
   * Check if the passed language id is the default language.
   *
   * @param string $languageId
   *   The language id to check.
   *
   * @return bool
   *   Whether the current language is the default language or not.
   */
  public function languageIdIsDefault($languageId) {
    return $languageId == $this->languageManager
      ->getDefaultLanguage()
      ->getId();
  }

  /**
   * Filter menus items by current language.
   *
   * @param array $items
   *   Menu items.
   */
  public function filterMenusByCurrentLanguage(array &$items) {
    $language = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    foreach ($items as $key => $item) {
      if (!$items[$key] = $this->checkForMenuItemTranslation($item, $language)) {
        unset($items[$key]);
      }
    }
  }

  /**
   * Filter admin menus items by current language.
   *
   * @param array $form
   *   Original form.
   *
   * @see admin/structure/menu/manage/main
   */
  public function filterFormMenusByCurrentLanguage(array &$form) {
    $language = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    foreach ($form['links']['links'] as $key => $link) {
      if (preg_match('/^menu_plugin_id:menu_link_content:(.*)$/', $key, $matches)) {
        $menuLinkContent = $this->entityRepository
          ->loadEntityByUuid('menu_link_content', $matches[1]);
        $languages = $menuLinkContent->getTranslationLanguages();
        if (!array_key_exists($language, $languages)) {
          unset($form['links']['links'][$key]);
        }
      }
    }
  }

  /**
   * Private function.
   *
   * @param array $item
   *   Item.
   * @param string $language
   *   Language.
   *
   * @return bool|null
   *   Result.
   */
  protected function checkForMenuItemTranslation(array $item, $language) {
    $menuLinkEntity = $this->loadLinkEntityByLink($item['original_link']);

    if ($menuLinkEntity != NULL) {
      $languages = $menuLinkEntity->getTranslationLanguages();
      if (!array_key_exists($language, $languages)) {
        return FALSE;
      }
      if (count($item['below']) > 0) {
        foreach ($item['below'] as $subkey => $subitem) {
          if (!$item['below'][$subkey] = $this->checkForMenuItemTranslation($subitem, $language)) {
            unset($item['below'][$subkey]);
          }
        }
      }
      return $item;
    }

    return NULL;
  }

  /**
   * Load entity link.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menuLinkContentPlugin
   *   MenuLinkPlugin.
   *
   * @return null|EntityBase
   *   Result.
   */
  protected function loadLinkEntityByLink(MenuLinkInterface $menuLinkContentPlugin) {
    if ($menuLinkContentPlugin instanceof MenuLinkContent) {
      $menu_link = explode(':', $menuLinkContentPlugin->getPluginId(), 2);
      $uuid = $menu_link[1];
      $entity = $this->entityRepository->loadEntityByUuid('menu_link_content', $uuid);
      return $entity;
    }
    return NULL;
  }

}
