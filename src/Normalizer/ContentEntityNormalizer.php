<?php

namespace Drupal\hugo_export\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Entity\File;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
    $data['title'] = $entity->getTitle();
    $data['date'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'html_datetime');
    $data['draft'] = $entity->isPublished() ? 'false' : 'true';
    $data['type'] = $entity->getType();
    $data['url'] = $entity->toUrl()->toString();
    $data['author'] = $entity->getOwner()->getDisplayName();
    if (isset($context['menu'])) {
      $data['menu'] = $context['menu'];
    }
    $data['tags'] = $this->formatTermNames($entity);
    if ($images = $this->getImages($entity, 'field_image')) {
      $data['images'] = $images;
    }
    $data['_close'] = "---\n";
    $data['_body'] = $entity->body->value;
    return $data;
  }

  /**
   * Get tag names on node.
   *
   * @return string
   *   List of terms on node.
   */
  protected function formatTermNames($node) {
    $tags = [];
    if ($node->hasField('field_tags') && !$node->field_tags->isEmpty()) {
      foreach ($node->field_tags->getValue() as $termRef) {
        $term = Term::load($termRef['target_id']);
        if ($term) {
          $tags[] = $term->getName();
        }
      }
    }
    return implode(", ", $tags);
  }

  /**
   * Get list of image files associated with node.
   *
   * @param Entity $entity
   *   Entity.
   * @param string $field
   *   Field name to process for images.
   *
   * @return string || NULL
   *   Filenames or null.
   */
  protected function getImages($entity, $field) {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    $data = [];

    foreach ($entity->get($field)->getValue() as $item) {
      $f = File::load($item['target_id']);
      if ($f) {
        $data[] = $f->getFilename();
      }
    }

  return implode(", ", $data);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === static::FORMAT && $data instanceof ContentEntityInterface;
  }

}