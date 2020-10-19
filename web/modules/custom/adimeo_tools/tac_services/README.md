# Tarteaucitron Services

Ce module permet d'utiliser la librairie tarteaucitron.js pour gérer les **préférences de cookies** en accord avec la législation européenne **RGPD**.

Il s'appuye sur les services de tarteaucitron mais permet également de créer les siens.

## Dépendances
- **adimeo_tools** (_class ConfigServiceBase_)

## Configuration
**La configuration des services** (`/admin/config/services/tarteaucitron/configurations`) permet d'**activer les webservices** et de **renseigner les clefs d'API** (_ou autres informations nécessaires à leur fonctionnement_).

**La configuration globale** (`/admin/config/services/tarteaucitron/settings`) permet de gérer les fonctionnalités suivantes :

- **High privacy :** désactive le consentement implicite par navigation ;
- **Orientation :** placement du bandeau en haut ou en bas de la page (_possibilité de surcharger dans votre thème_) ;
- **Adblocker :** permet d'afficher un message si un adblocker est détecté ;
- **Small alert box :** Affiche un petit bandeau en bas à droite de toutes les pages pour permettres aux internautes de modifier leur préférences ;
- **Cookies list :** Afficher la liste des cookies installer à l'internaute.

## Ajouter des webservices tiers
Ce module implémente le type de **plugin _TacService_**.

Ces plugin fournissent :

- le ou les champs, si nécessaire, pour faire fonctionner le webservice (clef d'API par exemple) ;
- un retraitement des données (liés au champs ci-dessus) avant leur enregistrement en configuration ;
- un nom de librairie qui sera intégré sur toutes les pages et qui ajoute le service dans la liste des jobs tarteaucitron visible par l'internaute.
 
Les plugins peuvent s'appuyer sur un services déjà implémenté par la librairie tarteaucitron, ou étendre la liste des service tarteaucitron via le Javascript présent dans la librairie déclarée par le plugin.
 
Les données type clef d'API sont stockés dans la configuration et sont accessible en Javascript via l'objet drupalSettings avec le schémas suivant : `drupalSettings.tacServices.nom_du_plugin.nom_du_champs`
 
Pour gérer des webservices qui ne doivent pas être appelés sur toutes les pages il est nécessaire d'utiliser une seconde librairie qui se chargera de loader le webservice uniquement si l'utilisateur a accepté son utilisation.
 
 
## Bonus
 
L'objet Javascript "TacHelpers" fournit deux méthodes intéressantes :

- **checkCookie :** prend en argument l'id du plugin et renvoie vrai ou faux selon si le service est accepté par l'utilisateur en lisant le cookie généré par tarteaucitron.
- **getPlaceholder :** prend en argument un message (string) et renvoie une string de HTML pour créer des placeholders facilement et surtout contenant un lien ouvrant le panel tarteaucitron permettant la modification des préférences de l'utilisateur et d'accéder à l'élément précédemment bloqué.

##Comment utiliser TAC concrètement : 

1. Activer le module. 

2. Aller dans "configuration > services web > services tarteaucitron > liste des webservices"

3. Activer les webservices nécessaires au projet (ex: Google map api / GTM etc.). 

4. Ne pas oublier d'attacher la librarie du webservice demandé dans le template ayant besoin de TAC 
   (ex: `{{ attach_library('tac_services/google_maps_api') }}`).
   
5. Dans le js, ayant besoin de la verification TAC, on vérifie que l'utilisateur a bien activé les cookies sinon on lui renvoie une phrase lui disant de bien vouloir activer le webservice. 
```
if (typeof(google) === 'undefined') {
    allDynamicMaps.push(self);
    // affichage du placeholder tarteaucitron
    $('#' + self.id).append(TacHelpers.getPlaceholder('Vos préférences en matière de cookies ne vous permet pas de visionner cette <b>carte Google Maps</b>'));
    $('#' + self.id + ' > .TacNoCookieMessage').fadeIn(800);
}
else { //Le webservice est bien activé.}
```   

 

