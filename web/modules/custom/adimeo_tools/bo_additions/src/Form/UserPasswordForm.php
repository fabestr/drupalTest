<?php

namespace Drupal\bo_additions\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPasswordForm;

class AlteredUserPasswordForm extends UserPasswordForm {

  protected $hasError;

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->hasError) {
      parent::submitForm($form, $form_state);
    }
    $form_state->setRedirect('user.pass');

    // Suppression messages d'erreurs pouvant indiquer qu'un compte existe avec ce login.
    \Drupal::messenger()->deleteAll();
    \Drupal::messenger()
      ->addMessage(t('Si l’adresse email renseignée correspond à un compte existant, un email sera envoyé à cette adresse, contenant un lien de récupération de mot de passe.'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // On marque le formulaire comme ayant des erreurs.
    if (count($form_state->getErrors()) > 0) {
      $this->hasError = TRUE;
    }

    // On clear les errors pour ne pas donner d'indication.
    $form_state->clearErrors();
  }

}
