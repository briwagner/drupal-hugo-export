<?php

namespace Drupal\hugo_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * HugoExportGeneratorViewForm creates a form to generate Hugo content from a View.
 */
class HugoExportGeneratorViewForm extends FormBase {

  /**
   * @var HugoContentGenerator
   */
  protected $hugoGenerator;


  /**
   * {@inheritdoc}
   */
  public function __construct($hugoGenerator) {
    $this->hugoGenerator = $hugoGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hugo_export.content_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hugo_export_view_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_name = NULL, $view_display = NULL) {
    $build = [];

    $build['export_view'] = [
      '#type' => 'select',
      '#title' => $this->t("Select a view"),
      '#description' => $this->t("Export content from this View."),
      '#options' => $this->getOptions(),
      '#default_value' => $view_name,
      '#required' => TRUE,
      '#weight' => -2,
      '#ajax' => [
        'callback' => '::updateViewSelection',
        'wrapper' => 'edit-view-display-wrapper',
      ],
    ];

    $defaultDisplay = $form_state->getValue('export_view', $view_name);
    $build['export_view_display'] = [
      '#type' => 'select',
      '#title' => $this->t("Display"),
      '#description' => $this->t("Choose a display for this View."),
      '#options' => $this->getDisplayOptions($view_name),
      '#default_value' => $view_display,
      '#required' => TRUE,
      '#prefix' => '<div id="edit-view-display-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE, // Better way to avoid 'illegal selection' here?
      '#weight' => 0,
    ];

    $build['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Generate',
      '#weight' => 2,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formView = $form_state->getvalue('export_view');
    $formDisplay = $form_state->getvalue('export_view_display');

    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView($formView);
    $view->setDisplay($formDisplay);
    $ok = $view->execute();
    if ($ok) {
      // Create a batch operation to process each item.
      $batch = [
        'title' => 'Exporting content for Hugo.',
        'operations' => [],
        'init_message' => 'Beginning Hugo export',
        'progress_message' => 'Processed @current out of @total',
        'error_message' => 'Error during Hugo export.',
      ];

      // Set directory.
      $dirName = 'hugo_export/view/' . $formView;

      // Try to get entity from row and ensure it's a node type.
      foreach ($view->result as $row) {
        $entity = $row->_entity;
        if ($entity && $entity->getEntityTypeId() == 'node') {
          $batch['operations'][] = [
            '\Drupal\hugo_export\HugoExportBatch::exportEntity',
            [$entity->id(), $dirName, null],
          ];
        }
      }

      batch_set($batch);
    }
  }

  /**
   * Get a list of enabled Views.
   *
   * @return array
   *   Array of Views labels, keyed by View ID.
   */
  protected function getOptions() {
    $opts = [];
    $views = Views::getEnabledViews();
    foreach ($views as $view) {
      $opts[$view->id()] = $view->label();
    }

    return $opts;
  }

  /**
   * todo
   */
  protected function getDisplayOptions($view_name) {
    $options = [
      '' => $this->t('- Select -'),
    ];
    if ($view_name) {
      $view = Views::getView($view_name);
      $displays = $view->storage->get('display');
      foreach ($displays as $display) {
        $options[$display['id']] = $display['display_title'];
      }
    }
    return $options;
  }

  /**
   * Handles switching the View selector.
   */
  public function updateViewSelection($form, FormStateInterface $form_state) {
    $selection = $form_state->getValue('export_view');
    $form['export_view_display']['#options'] = $this->getDisplayOptions($selection);
    return $form['export_view_display'];
  }

}