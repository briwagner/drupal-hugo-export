<?php

namespace Drupal\hugo_export;

use Drupal\hugo_export\HugoContentGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\hugo_export\HugoMenuLoader;

/**
 * Batch operation to output entities to static files.
 */
class HugoExportBatch {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var HugoMenuLoader;
   */
  protected $menuLoader;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param Drupal\hugo_export\HugoMenuLoader $menuLoader
   *   Hugo-export MenuLoader.
   */
  public function __construct($configFactory, $menuLoader) {
    $this->configFactory = $configFactory->get('hugo_export.settings');
    $this->menuLoader = $menuLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $this->configFactory,
      $this->menuLoader
    );
  }

  /**
   * Batch operation to export entity.
   *
   * @param int $id
   *   Entity id.
   * @param string $dir
   *   Name of directory to export content.
   * @param string $menuName
   *   Name of menu name that entity belongs to.
   */
  public static function exportEntity(int $id, string $dir, string $menuName = NULL) {
    $gen = new HugoContentGenerator(
      \Drupal::service('config.factory'),
      \Drupal::service('hugo_export.menu_loader')
    );
    $status = $gen->exportItem($id, $dir, $menuName);
    // TODO: can we use the bool here to show status, success or failure?
  }

}