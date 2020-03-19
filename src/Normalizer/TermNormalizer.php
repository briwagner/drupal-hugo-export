<?php

namespace Drupal\hugo_export\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class TermNormalizer extends SerializerAwareNormalizer implements NormalizerInterface {

  const FORMAT = 'markdown';

  /**
   * {@inheritdoc}
   */
  public function normalize($term, $format = NULL, array $context = []) {
    $t = Term::load($term->getValue()['target_id']);
    if ($t) {
      return $t->getName();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === static::FORMAT && $data instanceof EntityReferenceItem && $data->getFieldDefinition()->getSetting('target_type') === 'taxonomy_term';
  }

}