# Adimeo tools
Collection de fonctionnalités très courantes et ne justifiant pas un module dédié.

## ConfigServiceBase
Classe abstraite facilitant la gestion de conf custom (state ou yml)

Si vous passez un tableau de données à setAllValues() (ou set($key,$value) :  
- toutes les clés du tableau getConfAllowedKeysDefaultValues() seront stockées dans conf (.yml).  
- toutes les clés du tableau getStateAllowedKeysDefaultValues() seront stockées dans state (base de données).  
- Les autres clés ne seront pas sauvegardées.  

Exemple d'implémentation dans le module tac_services : `adimeo_tools/tac_services/src/Service/TacGlobalConfigService.php`

## Language Service, outils liés au multilangue :

Loader une entité dans la langue courante : ```LanguageService::load('node', $node);``` (existe en "multiple")  

Traduire une entité dans la langue courante : ```LanguageService::translate($entity);``` (existe en "multiple")  

Obtenir le langcode courant ("fr","en", etc.) : ```LanguageService::getCurrentLanguageId($node);```  

Cacher les liens de menu non traduits en front :  
```
/**
* {@inheritdoc}
*/
function MY_MODULE_preprocess_menu(&$variables) {
 if ($variables['menu_name'] == 'main') {
   LanguageService::filterMenusByCurrentLanguage($variables['items']);
 }
}
```

Et bien plus ... 


## Misc Service
Méthodes utiles comme par exemple :  
```loadVocabularyTree($vid, $parent = 0, $max_depth = NULL)```  
```getParentTerms(Term $term)```  
```getCurrentPageNode()```  

Et bien plus ...  

## Geolocation Service
Pour utiliser le service `Geolocation` de Google : https://developers.google.com/maps/documentation/geolocation/intro

## TwigFilters
Liste de filtres et fonctions Twig custom pour améliorer l'expérience de templating.  
Par exemple :  
```{{ content.field_image|at_imageStyle('style_image') }}```   
```{{ at_getNodeUrl(nodeId) }}``` (=> marche avec l'objet, l'id ou le build array)   
```{% for item in content.field_list|at_children %}```  
```{% for key, item in content.field_list|at_children(true)%}```  
```{% if content.field_list|at_children(true)%}``` (=> pour vérifier qu'un champ n'est pas vide)  

Et bien plus ...

## Drush commands :
**Implémentation de 2 commandes drush :**  
(==> exemple d'une méthode DRY pour assurer la compatibilité drush 8 et drush 9)  

### drush set-module-version (alias smv) :
Set the module version in order to redo hook_updates.

Parameters : 
 - module_name : The module to downgrade
 - version : the version in which you want to downgrade.
 
Example :
    ```drush smv adimeo_tools 8000```

### drush reload-module-config (alias rmc) :
Reload module default configuration

Parameters : 
 - module_name : The module
 
 Example :
     ```drush rmc my_custom_migration```
