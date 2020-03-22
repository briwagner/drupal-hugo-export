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
    $viewName = $form_state->getvalue('export_view');
    $viewDisplay = $form_state->getvalue('export_view_display');

    // Create a batch operation to process each item.
    $batch = [
      'title' => 'Exporting content for Hugo.',
      'operations' => [],
      'init_message' => 'Beginning Hugo export',
      'progress_message' => 'Processed @current out of @total',
      'error_message' => 'Error during Hugo export.',
    ];

    // Set directory for exported files.
    $dirName = 'hugo_export/view/' . $viewName;

    // Get entity IDs to add to batch.
    $ids = [];
    $this->addBatchItems($ids, $viewName, $viewDisplay, 0);
    // Build batch operations from entity IDs.
    $batch['operations'] = array_map(function($row) use ($dirName) {
      return [
        '\Drupal\hugo_export\Batch\HugoExportBatch::exportEntity',
        [$row, $dirName, NULL]
      ];
    }, $ids);

    batch_set($batch);
  }

  /**
   * Recursive call to get View content using pager.
   *
   * @param array $entityIDs
   *   Entity IDs.
   * @param string $viewName
   *   Name of View.
   * @param string $dispName
   *   Name of View display.
   * @param int $page
   *   View page number.
   */
  protected function addBatchItems(&$entityIDs, $viewName, $dispName, $page = 0) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView($viewName);
    $view->setDisplay($dispName);
    $view->setCurrentPage($page);
    $ok = $view->execute();
    // Exit if View fails or no results are found.
    if (!$ok || $view->result == []) {
      return;
    }
    // Iterate over rows to obtain entity ID.
    foreach ($view->result as $row) {
      $entity = $row->_entity;
      if ($entity && $entity->getEntityTypeId() == 'node') {
        $entityIDs[] = $entity->id();
      }
    }
    // Increment page and recurse.
    $page++;
    $this->addBatchItems($entityIDs, $viewName, $dispName, $page);
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