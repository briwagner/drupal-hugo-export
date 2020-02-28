<?php

namespace Drupal\hugo_export;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\Yaml\Yaml;

/**
 * HugoMenuLoader gets entities from a menu.
 */
class HugoMenuLoader {

  /**
   * MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  public function __construct($menuLinkTree) {
    $this->menuLinkTree = $menuLinkTree;
  }

  /**
   * Load the menu and provide list of entity IDs.
   *
   * @param string $menuName
   *   ID of menu to load.
   *
   * @return array
   *   Array of entity IDs, keyed by entity type.
   */
  public function loadMenu(string $menuName) {
    $entities = [];
    $entities['nodes'] = [];

    $service = \Drupal::service('menu.link_tree');
    $tree = $service->load($menuName, new MenuTreeParameters());
    if ($tree) {
      foreach ($tree as $item) {
        /** @var Drupal\menu_link_content\Entity\MenuLinkContent $linkContent */
        $linkContent = $item->link;
        // Skip if link has absolute path.
        if ($linkContent->getUrlObject()->isExternal()) {
          continue;
        }
        // Skip if link is disabled in menu.
        if (!$linkContent->isEnabled()) {
          continue;
        }
        // TODO: what other type of entities could be here?
        // How to handle <front>?
        if (!empty($linkContent->getPluginDefinition()['metadata'])) {
          $entities['nodes'][] = $linkContent->getPluginDefinition()['route_parameters']['node'];
        }
      }
    }
    else {
      // Failed to load the menu.
    }
    return $entities;
  }

  /**
   * Export menu to Hugo config.
   *
   * @param string $menu
   *   Menu name.
   */
  public function exportMenu(string $menu) {
    $dirBase = "hugo_export";
    $dir = sprintf("public://%s/config/menus/", $dirBase);
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

    $items = $this->loadMenu($menu);
    $menuItems = [];

    $service = \Drupal::service('menu.link_tree');
    $tree = $service->load($menu, new MenuTreeParameters());
    if ($tree) {
      foreach ($tree as $item) {
        /** @var Drupal\menu_link_content\Entity\MenuLinkContent $linkContent */
        $linkContent = $item->link;

        if ($linkContent->getUrlObject()->isExternal()) {
          continue;
        }
        if (!$linkContent->isEnabled()) {
          continue;
        }
        // Get node ID.
        if (!empty($linkContent->getPluginDefinition()['metadata'])) {
          // Get node values from link item.
          $nid = $linkContent->getPluginDefinition()['route_parameters']['node'];
          $node = \Drupal::service('entity_type.manager')->getStorage('node')
            ->load($nid);

          if ($node && $node->isPublished()) {
            $menuItems[] = [
              'name' => $linkContent->getTitle(),
              'weight' => intval($linkContent->getWeight()),
              'url' => $node->toUrl()->toString(),
            ];
          }
        }
      }
    }

    // Export menu, using name from Drupal.
    // TODO: we need to make this camelcase it seems. No hyphens allowed.
    $menuName = str_replace("-", "", $menu);
    $m = [$menuName => $menuItems];
    $yaml = Yaml::dump($m, 6, 2);
    $filename = $dir . "/menus.yaml";
    file_unmanaged_save_data($yaml, $filename, FILE_EXISTS_REPLACE);
  }

}

// https://gohugo.io/content-management/menus/#nesting
// how to deal with nesting in hugo menu
// that's the identifier: ...

/**
 * Todo:
 *
 * - handle nested pages
 * - what to do with weights? are negative weights ok? YES!
 */
