<?php

namespace Drupal\hugo_export\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\Entity\File;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class FileNormalizer extends SerializerAwareNormalizer implements NormalizerInterface {

  const FORMAT = 'markdown';

  /**
   * {@inheritdoc}
   */
  public function normalize($item, $format = NULL, array $context = []) {
    $f = File::load($item->getValue()['target_id']);
    if ($f) {
      return $f->getFilename();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === static::FORMAT && $data instanceof EntityReferenceItem && $data->getFieldDefinition()->getSetting('target_type') === 'file';
  }

}