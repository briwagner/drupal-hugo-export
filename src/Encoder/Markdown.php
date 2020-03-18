<?php

namespace Drupal\hugo_export\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * https://github.com/e0ipso/entity_markdown/blob/8.x-1.x/src/Encoder/Markdown.php
 */
class Markdown implements EncoderInterface {
  /**
   * Supported formats.
   *
   * @var string
   */
  protected static $format = 'markdown';

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    $lines = [];
    foreach ($data as $k => $item) {
      // Ignore labels that start with underscore.
      if (substr($k, 0, 1) === "_") {
        $lines[] = $item;
      } else {
        $lines[] = $k . ": " . $item;
      }
    }
    return implode("\n", $lines);
  }

}