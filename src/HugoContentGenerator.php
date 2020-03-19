<?php

namespace Drupal\hugo_export;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
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
   * @param string $dirName
   *   Name of directory to export content.
   * @param string $menu
   *   Menu name.
   *
   * @return bool
   *   Operation to save file succeeded or not.
   */
  public function exportItem(int $nid, string $dirName, string $menu = NULL) {
    // Load node.
    $node = Node::load($nid);
    if ($node && $node->isPublished()) {
      // Prepare directory, sorting by content type ONLY if menu is set.
      if ($menu) {
        $dir = sprintf("public://%s/%s/", $dirName, $node->bundle());
      }
      // Entities from a View go in a single directory.
      else {
        $dir = sprintf("public://%s/", $dirName);
      }

      file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

      // Do we want .md files or what?
      $fileName = sprintf("%s/%s.md", $dir, $node->id());
      // TODO: consider adding field list to context at this point?
      $fieldList = [
        'field_image',
        'field_tags'
      ];
      $fileData = $this->serializer->serialize($node, 'markdown', [
        'menu' => $menu,
        'field_list' => $fieldList
      ]);
      if (file_unmanaged_save_data($fileData, $fileName, FILE_EXISTS_REPLACE)) {

        // Prepare directory.
        $publicDir = "public://hugo_export/public/";
        \Drupal::service('file_system')
          ->prepareDirectory($publicDir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

        // Export images from image field.
        if ($node->hasField('field_image') && !$node->field_image->isEmpty()) {
          $images = $node->field_image->getValue();
          // Iterate over items. Default config allows one value only.
          foreach ($images as $img) {
            $f = File::load($img['target_id']);
            if ($f) {
              $filename = $publicDir . $f->getFilename();
              if (!file_exists($filename)) {
                // Copy file to directory.
                file_copy($f, $filename);
              }
            }
          }
        }
        return TRUE;
      }
    }
    return FALSE;
  }

}