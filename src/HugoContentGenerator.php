<?php

namespace Drupal\hugo_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hugo_export\HugoMenuLoader;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * HugoContentGenerator exports the static files.
 */
class HugoContentGenerator {

  use StringTranslationTrait;

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
      $container->get('config.factory'),
      $container->get('hugo_export.menu_loader')
    );
  }

  /**
   * Export entity to static file.
   *
   * @param int $nid
   *   Entity ID.
   * @param string $dir
   *   Name of directory to export content.
   * @param string $menu
   *   Menu name.
   *
   * @return bool
   *   Operation to save file succeeded or not.
   */
  public function exportItem(int $nid, string $dir, string $menu = NULL) {
    // Load node.
    $node = Node::load($nid);
    if ($node && $node->isPublished()) {
      // Prepare directory, sorting by content type ONLY if menu is set.
      if ($menu) {
        $dir = sprintf("public://%s/%s/", $dir, $node->bundle());
      }
      // Entities from a View go in a single directory.
      else {
        $dir = sprintf("public://%s/", $dir);
      }

      file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

      // Do we want .md files or what?
      $fileName = sprintf("%s/%s.md", $dir, $node->id());
      if (file_unmanaged_save_data($this->formatFile($node, $menu), $fileName, FILE_EXISTS_REPLACE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Export to file
   *
   * @param Node $node
   *   Node interface.
   * @param string $menu
   *   Menu name.
   */
  public function formatFile($node, string $menu = NULL) {
    $data = \Drupal::service('serializer')->serialize($node, 'markdown', ['menu' => $menu]);
    return $data;
  }

}