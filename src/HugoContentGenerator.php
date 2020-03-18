<?php

namespace Drupal\hugo_export;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Symfony\Component\Serializer\Serializer;

/**
 * HugoContentGenerator exports the static files.
 */
class HugoContentGenerator {

  use StringTranslationTrait;

  /**
   * @var Serializer
   */
  protected $serializer;

  /**
   * Constructor.
   *
   * @param Symfony\Component\Serializer\Serializer $serializer
   *   Serializer service.
   */
  public function __construct(Serializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer')
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
      $fileData = $this->serializer->serialize($node, 'markdown', ['menu' => $menu]);
      if (file_unmanaged_save_data($fileData, $fileName, FILE_EXISTS_REPLACE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}