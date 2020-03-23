<?php

namespace Drupal\hugo_export\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;


/**
 * https://github.com/e0ipso/entity_markdown/blob/8.x-1.x/src/Normalizer/ContentEntityNormalizer.php
 */
class ContentEntityNormalizer extends SerializerAwareNormalizer implements NormalizerInterface {

  use StringTranslationTrait;

  const FORMAT = 'markdown';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $data['_start'] = "---";
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $data['title'] = '"' . $entity->getTitle() . '"';
    $data['date'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'html_datetime');
    $data['last_edit'] = \Drupal::service('date.formatter')->format($entity->getChangedTime(), 'html_datetime');
    $data['draft'] = $entity->isPublished() ? 'false' : 'true';
    $data['type'] = $entity->getType();
    $data['url'] = $entity->toUrl()->toString();
    $data['author'] = $entity->getOwner()->getDisplayName();
    if (isset($context['menu'])) {
      $data['menu'] = $context['menu'];
    }

    // Send designated fields for serialization.
    foreach ($context['field_list'] as $field) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        $data[$field] = $this->serializer->normalize($entity->get($field), $format, $context);
      }
    }

    $data['_close'] = "---\n";
    $data['_body'] = $entity->body->value;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === static::FORMAT && $data instanceof ContentEntityInterface;
  }

}