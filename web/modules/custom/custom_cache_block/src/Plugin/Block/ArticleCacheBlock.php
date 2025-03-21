<?php

namespace Drupal\custom_cache_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;



/**
 * Create a 'Custom Latest cache Articles' Block.
 *
 * @Block(
 *   id= "latest_articles_block",
 *   admin_label= @Translation("Custom Latest Articles Block"),
 * )
 */

class ArticleCacheBlock extends BlockBase implements ContainerFactoryPluginInterface{

/**
   * The entity type manager.
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
   * Constructs a new LatestArticlesBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
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

 public function build() {
    $article_titles = [];
    $nids = $this->getLatestArticleNids();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($nodes as $node) {
        $article_titles[] = $node->toLink()->toString();
      }
    }
    // Get the current user's email.
    $user_email = $this->currentUser->getEmail();
    //\Drupal::service('cache_tags.invalidator')->invalidateTags(['node:5']); //Invalidate a Single Node Cache

    // $nids = 5;
    return [
      '#theme' => 'item_list',
      // '#items' => $article_titles,
      '#items' => array_merge(["User Email: $user_email"], $article_titles),
      '#title' => $this->t('Latest Articles'),
      '#cache' => [
        'tags' => array_map(fn($nid) => "node:$nid", $nids), // Invalidate when specific nodes change
        'contexts' => ['user'], // Cache varies per user
        // 'contexts' => ['user.roles:admin'], // Cache varies per user
      ],
    ];
  }

  /**
   * Fetches the latest three published article node IDs.
   *
   * @return array
   *   An array of node IDs.
   */
  protected function getLatestArticleNids() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1) // Published only
      ->condition('type', 'article') // Only articles
      ->sort('created', 'DESC') // Newest first
      ->range(0, 3) // Limit to 3 articles
      ->accessCheck(TRUE);

    return $query->execute();
  }

}
