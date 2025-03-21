<?php

namespace Drupal\custom_cache_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a block that displays articles from the user's preferred category.
 *
 * @Block(
 *   id = "preferred_category_articles",
 *   admin_label = @Translation("Articles from Preferred Category"),
 * )
 */
class PreferredCategoryArticlesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PreferredCategoryArticlesBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $articles = [];
    $preferred_category_id = $this->getUserPreferredCategory();

    if ($preferred_category_id) {
      $nids = $this->getArticlesByCategory($preferred_category_id);
      if (!empty($nids)) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
          $articles[] = $node->toLink()->toString();
        }
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $articles ?: [$this->t('No articles found.')],
      '#title' => $this->t('Articles from Your Preferred Category'),
      '#cache' => [
        'contexts' => ['preferred_category'], // Custom cache context
        'tags' => ['node_list:article'], // Invalidate when articles change
      ],
    ];
  }

  /**
   * Get the current user's preferred category ID.
   */
  protected function getUserPreferredCategory() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    return $user->get('field_preferred_category')->target_id ?? NULL;
  }

  /**
   * Fetches article node IDs by category.
   */
  protected function getArticlesByCategory($category_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'article')
      ->condition('field_category', $category_id)
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE);

    return $query->execute();
  }
}