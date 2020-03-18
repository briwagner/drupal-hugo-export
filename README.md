# Hugo Export module for Drupal 8

This module is intended to output Drupal content into a format and structure that can be readily used by a Hugo site.

There are two options for exporting content:
  * export a menu
  * export a View

### Export a Menu

This option allows you to manage your menu structure from Drupal and export the content and hierarchy to Hugo. The nesting will be preserved, as the export generates a menu file for Hugo to use in building its menu.

Content is exported into directories based on content-type. Each item also maintains a reference to the menu.

### Export a View

This option allows you to export a set of content items, leveraging Views ability to filter. It does not rely on field configuration used by Views.

### Output Folder

Content and menu files are exported to the hugo_export folder in the public files directory.