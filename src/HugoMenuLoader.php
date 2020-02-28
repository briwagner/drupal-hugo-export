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
      $entities = $this->getNodesFromMenuTree($entities, $tree);
    }
    return $entities;
  }

  /**
   * Get entities from menu tree.
   *
   * @param array $entities
   *   List of entity IDs.
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   Menu tree elements.
   */
  protected function getNodesFromMenuTree(&$entities, $tree) {
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

        // Check for child items.
        if ($item->hasChildren) {
          $this->getNodesFromMenuTree($entities, $item->subtree);
        }
      }
    }
    return $entities;
  }

  /**
   * Export menu to Hugo config.
   *
   * @param string $menu_name
   *   Menu name.
   */
  public function exportMenu(string $menu_name) {
    $dirBase = "hugo_export";
    $dir = sprintf("public://%s/config/menus/", $dirBase);
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

    $items = $this->loadMenu($menu_name);
    $menuItems = [];

    $treeService = \Drupal::service('menu.link_tree');
    $tree = $treeService->load($menu_name, new MenuTreeParameters());
    if ($tree) {
      $menuItems = $this->getLinksFromTree($menuItems, $tree);
    }

    // Export menu, using name from Drupal.
    // TODO: we need to make this camelcase it seems. No hyphens allowed.
    $menuName = str_replace("-", "", $menu_name);
    // Build menu structure.
    $m = [$menuName => $menuItems];
    // Convert to yaml format for Hugo.
    $yaml = Yaml::dump($m, 6, 2);
    $filename = $dir . "/menus.yaml";
    file_unmanaged_save_data($yaml, $filename, FILE_EXISTS_REPLACE);
  }

  /**
   * Process current menu item and any child elements
   *
   * @param array $menuItems
   *   Array of menu items for Hugo export.
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   Menu tree elements.
   * @param string $parent
   *   Parent menu item identifier.
   *
   * @return array || bool
   *   Return menu item.
   */
  protected function getLinksFromTree(&$menuItems, $tree, $parent = '') {
    foreach ($tree as $item) {
      /** @var Drupal\menu_link_content\Entity\MenuLinkContent $linkContent */
      $linkContent = $item->link;

      // Skip if link is external.
      if ($linkContent->getUrlObject()->isExternal()) {
        continue;
      }
      // Skip if link is disabled.
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
          // Geneate new item and add to menu list.
          $newItem = $this->buildMenuItem($item, $node->toUrl()->toString(), $parent);
          $menuItems[] = $newItem;
          // Get subtree, if found, passing parent link.
          if ($item->hasChildren) {
            $this->getLinksFromTree($menuItems, $item->subtree, $newItem['identifier']);
          }
        }
      }
    }
    return $menuItems;
  }

  /**
   * Build menu item for Hugo menu.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $item
   *   Menu item.
   * @param string $url
   *   Url for menu item.
   * @param string $parent
   *   Parent menu identifier.
   *
   * @return array
   *   Array of menu item and children.
   */
  protected function buildMenuItem($item, $url, $parent = '') {
    $menuItem = [
      'name' => $item->link->getTitle(),
      'weight' => intval($item->link->getWeight()),
      'url' => $url,
      'identifier' => strtolower($item->link->getTitle()),
    ];
    if ($parent != '') {
      $menuItem['parent'] = $parent;
    }
    return $menuItem;
  }

}
