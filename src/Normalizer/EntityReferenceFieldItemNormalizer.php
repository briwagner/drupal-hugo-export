<?php

namespace Drupal\hugo_export\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldItemNormalizer extends SerializerAwareNormalizer implements NormalizerInterface {

  const FORMAT = 'markdown';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_list, $format = NULL, array $context = []) {
    // Format taxonomy terms as comma-separate string of names.
    switch ($field_list->getFieldDefinition()->getSetting('target_type')) {
      case "taxonomy_term":
      case "file":
        $items = [];
        foreach ($field_list as $item) {
          $items[] = $this->serializer->normalize($item, $format, $context);
        }
        return implode(", ", $items);
      break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === static::FORMAT && $data instanceof FieldItemListInterface;
  }

}
