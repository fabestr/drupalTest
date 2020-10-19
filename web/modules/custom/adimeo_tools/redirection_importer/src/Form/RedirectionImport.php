<?php


namespace Drupal\redirection_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\redirect\Entity\Redirect;

class RedirectionImport extends FormBase
{

    const FORM_ID = 'redirection_importer.redirection_import';
    const FIELD_FILE = 'file';
    const SEPARATOR = ';';
    const WRAPPER = '"';
    /**
     * Returns a unique string identifying the form.
     *
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return static::FORM_ID;
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
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form[static::FIELD_FILE] = [
            '#type'              => 'managed_file',
            '#title'             => t('Fichier'),
            '#upload_validators' => [
                'file_validate_extensions' => ['csv'],
                'file_validate_size'       => array(25600000)
            ],
            '#description' => 'Type: csv
                               <br/>séparateur: ";"
                               <br/>wrapper: """<br/>
                               
                               <br/>Pas d\'entête de colonne,
                               <br/>ordre: "source";"redirection";"language";"status code"',
        ];

        $form['submit'] = [
            '#type'        => 'submit',
            '#value'       => t('Save'),
            '#button_type' => 'primary',
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
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $dataImport = $this->getDataToImport($form_state);

        foreach ($dataImport as $datum) {
            if(is_null($datum)) {
                continue;
            }
            Redirect::create([
                'redirect_source' => $datum[0], // Set your custom URL.
                'redirect_redirect' => $datum[1], // Set internal path to a node for example.
                'language' => $datum[2], // Set the current language or undefined.
                'status_code' => $datum[3], // Set HTTP code.
            ])->save();
        }
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

}