<?php

namespace Drupal\hugo_export\Batch;

use Drupal\hugo_export\HugoContentGenerator;

/**
 * Batch operation to output entities to static files.
 */
class HugoExportBatch {

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
      \Drupal::service('serializer')
    );
    $status = $gen->exportItem($id, $dir, $menuName);
    // TODO: can we use the bool here to show status, success or failure?
  }

}