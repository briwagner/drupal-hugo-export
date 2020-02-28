<?php

namespace Drupal\hugo_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\system\Plugin\Core\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Hugo Export settings.
 */
class HugoExportSettings extends ConfigFormBase {

  /**
   * Configuration settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityMgr;

  /**
   * Entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, QueryFactory $entity_query) {
    $this->entityMgr = $entity_manager;
    $this->entityQuery = $entity_query;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'hugo_export';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hugo_export.settings'];
  }

  /**
   * Hugo Export settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get module configuration.
    $config = $this->config('hugo_export.settings');

    $form['hugo_menu'] = [
      '#type' => 'select',
      '#title' => $this->t("Hugo Menu"),
      '#description' => $this->t("Export content in this menu for Hugo."),
      '#default_value' => $config->get('hugo_menu'),
      '#options' => $this->getOptions(),
      '#required' => TRUE,
      '#weight' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Generate options for configuration form.
   *
   * @return array
   *   Array of menu options.
   */
  protected function getOptions() {
    $options = [];

    $q = $this->entityQuery->get('menu');
    $menuIds = $q->execute();
    $menus = $this->entityMgr->getStorage('menu')
      ->loadMultiple($menuIds);

    // Filter for non-system menu items.
    foreach ($menus as $menu) {
      if (!$menu->isLocked()) {
        $options[$menu->id()] = $menu->label();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formVal = $form_state->getvalue('hugo_menu');
    $menu = \Drupal::service('entity_type.manager')
      ->getStorage('menu')
      ->load($formVal);

    if (!$menu) {
      $form_state->setErrorByName('hugo_menu', $this->t(
        'Menu @m does not exist. Enter the machine name for a valid menu.', [
        '@m' => $formVal,
      ]));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formVal = $form_state->getvalue('hugo_menu');
    $menu = \Drupal::service('entity_type.manager')
      ->getStorage('menu')
      ->load($formVal);

    $this->configFactory->getEditable('hugo_export.settings')
      ->set('hugo_menu', $form_state->getvalue('hugo_menu'))
      ->save();

    \Drupal::service('messenger')->addMessage(
      $this->t("Saved menu: @menu. <a href='@link'> Configure the links</a>.", [
        "@menu" => $menu->label(),
        "@link" => Url::fromRoute(
          'entity.menu.edit_form',
          ['menu' => $formVal])->toString(),
      ])
    );

    parent::submitForm($form, $form_state);
  }

}
