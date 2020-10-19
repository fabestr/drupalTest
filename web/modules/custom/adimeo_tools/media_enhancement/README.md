# Media Enhancer

Media enhancer permet de gérer les medias en facilitant l'intégration via la fonction Twig `buildImageTagFormMedia` et permet aussi
d'ajouter des srcsets.


## buildImageTagFromMedia
Exemple simple: 
```twig
{{ buildImageTagFromMedia(content.field_media,{
  'image_style':'news_teaser',
  'srcset': {'2x': 'default', '1x':'720w'},
  'srcset_main_rule': '1x',
}) }}
```  

Avec en paramètre : 
- le média : soit en buildArray, soit l'entité
- options : 
    - image_style: le style d'image (défaut: `NULL`)
    - class: les classes à ajouter à l'image (défaut: `NULL`)
    - item_prop: l'itemprop ajoute l'item prop (défaut: `FALSE`)
    - srcset: les options de srcset (défaut: `[]`)
    - srcset_main_rule: la règle par défaut. Dans l'ex ci-dessus, la src sera donc une image correspondant à la règle 1x (défaut: `NULL`)
    - remove_dim_attrs: si true, les attributs `width` et `height` seront supprimés de la balise `<img/>` (défaut: `FALSE`)
    - lazyload: si true, l'image est chargée de manière différée (défaut: `FALSE`)
    - image_field_name: le nom du champ du media contenant l'image que l'on veut afficher (défaut: `field_media_image`)

## Options `srcset`
On peut définir une liste de srcset pour l'image pour charger des images plus petites.  
La génération de srcset peut-être désactiver dans Admin > Configuration > Média > Media Enhancement. 

ex: `{{ 'srcset': {'2x':'default', '1x':'400w'}) }}`  
Ceci va charger l'image par défaut pour les écrans en densité 2x.  
En revanche pour les densités de pixels en 1x, on va charger l'image de base avec une largeur maximum de 400px, en conservant le ratio.  
Attention, si l'image par défaut est plus petite que les 400px requis, il n'y aura pas de génération d'image.  

## Option `lazyload`
Charge le template `image-lazy.html.twig` et le wrappe autour de l'image.  
Le script javascript va mettre en chargement les images dès leur ajout dans le DOM. Le process s'accroche sur ce behavior : `Drupal.behaviors.lazyloading`.
  
Pour modifier l'arrière-plan du placeholder, il faut surcharger la classe CSS `.lazyload-image-content`
