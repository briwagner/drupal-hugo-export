<?php

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hugo_export\HugoMenuLoader;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\node\Entity\Node;

$entityMgr = \Drupal::service('entity_type.manager');

$menuName = 'hugo_export';

if (1) {
  $node = Node::load(1);
  print_r($node->getEntityTypeId());
}

if (0) {
  $q = \Drupal::service('entity.query')->get('menu');
  $menuIds = $q->execute();
  $menus = $entityMgr->getStorage('menu')->loadMultiple($menuIds);
  foreach ($menus as $menu) {
    if ($menu->isLocked()) {
      continue;
    }
    var_dump($menu->label());
  }
}

if (0) {
  $hml = new HugoMenuLoader();
  // $links = $hml->loadMenu($menuName);
  // var_dump($links);
}

if (0) {
  $m = $entityMgr->getStorage('menu')->load($menuName);
  $url = Url::fromRoute('entity.menu.edit_form', ['menu' => $menuName]);
  var_dump($url->toString());
}