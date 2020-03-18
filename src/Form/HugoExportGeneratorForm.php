<?php

namespace Drupal\hugo_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * HugoExportGenerator creates a form to generate Hugo content.
 */
class HugoExportGeneratorForm extends FormBase {

  /**
   * @var ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var HugoMenuLoader
   */
  protected $hugoMenuLoader;

  /**
   * @var HugoContentGenerator
   */
  protected $hugoGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct($configFactory, $menuLoader, $hugoGenerator) {
    $this->config = $configFactory->get('hugo_export.settings');
    $this->hugoMenuLoader = $menuLoader;
    $this->hugoGenerator = $hugoGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('hugo_export.menu_loader'),
      $container->get('hugo_export.content_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hugo_export_content_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build = [];

    // Get menu label to display.
    $menuId = $this->config('hugo_export.settings')->get('hugo_menu');
    $menu = \Drupal::service('entity_type.manager')->getStorage('menu')
      ->load($menuId);

    // Todo: process menu to show how many pages would be created.

    $build['menu_name'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("Generate content for menu: @menu.", [
        '@menu' => $menu ? $menu->label() : 'No menu selected',
      ]),
    ];
    $build['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Generate',
    ];

    return $build;
  }

  /**
   * Generate batch operation to export items to static files.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get menu name from module config.
    $menuName = $this->config('hugo_export.settings')->get('hugo_menu');
    // Get list of NIDs contained in menu.
    $nids = $this->hugoMenuLoader->loadMenu($menuName)['nodes'];
    // Create a batch operation to process each item.
    $batch = [
      'title' => 'Exporting content for Hugo.',
      'operations' => [],
      'init_message' => 'Beginning Hugo export',
      'progress_message' => 'Processed @current out of @total',
      'error_message' => 'Error during Hugo export.',
    ];

    // TODO: this should be config, or a form value?
    $dirBase = 'hugo_export/content';

    // Send NIDs to batch operation.
    foreach ($nids as $id) {
      $batch['operations'][] = [
        '\Drupal\hugo_export\Batch\HugoExportBatch::exportEntity',
        [$id, $dirBase, $menuName]
      ];
    }

    batch_set($batch);

    // Export menu as Hugo menu-config.
    $this->hugoMenuLoader->exportMenu($menuName);

    \Drupal::service('messenger')->addMessage(
      $this->t("Saved @count pages.", [
      '@count' => count($nids),
      ])
    );
  }

}