<?php

namespace Drupal\custom_cache_block\Plugin\Block;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Create a 'Custom Latest cache Articles' Block.
 *
 * @Block(
 *   id= "latest_articles_block",
 *   admin_label= @Translation("Custom Latest Articles Block"),
 * )
 */

/**
 * Defines a cache context for the current user's preferred category.
 */
class PreferredCategoryCacheContext implements CacheContextInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the cache context.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    if ($user && $user->hasField('field_preferred_category') && !$user->get('field_preferred_category')->isEmpty()) {
      return $user->get('field_preferred_category')->target_id;
    }

    return 'none'; // Default when no preferred category is set
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User preferred category');
  }
}
