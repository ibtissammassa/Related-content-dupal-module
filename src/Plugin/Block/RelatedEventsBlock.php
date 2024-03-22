<?php

/**
 * @file
 * Creates a block that displays the related content
 */

 namespace Drupal\related_content\Plugin\Block;

 use Drupal\Core\Block\BlockBase;
 use Drupal\Core\Block\Attribute\Block;
 use Drupal\Core\StringTranslation\TranslatableMarkup;
 use Drupal\Core\Session\AccountInterface;
 use Drupal\Core\Access\AccessResult;

 /**
 * Provides the related content block.
 */
#[Block(
    id: "related_content_block",
    admin_label: new TranslatableMarkup("The related content Block")
  )]

  class RelatedEventsBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
      // Get the current node
      $node = \Drupal::routeMatch()->getParameter('node');
  
      $related_content = [];
  
      // Check if the current page is a node
    if ($node && $node instanceof \Drupal\node\NodeInterface && $node->getType()=='event') {
        $content_type = $node->getType();
        $taxonomy_field_name = 'field_event_category';
  
        // Get the term IDs associated with the current node
        $term_ids = [];
        foreach ($node->get($taxonomy_field_name) as $term_reference) {
          $term_ids[] = $term_reference->target_id;
        }
  
        // Query related nodes
        $query = \Drupal::entityQuery('node')
          ->accessCheck(TRUE)
          ->condition('type', $content_type)
          ->condition('status', 1) // Published
          ->condition('nid', $node->id(), '<>') // Exclude the current node
          ->condition($taxonomy_field_name, $term_ids, 'IN')
          ->range(0, 3) // three related nodes
          ->sort('created', 'DESC'); // Sort by creation date
  
        $related_node_nids = $query->execute();
  
        // Load the related nodes
        $related_content = \Drupal\node\Entity\Node::loadMultiple($related_node_nids);
      }
  
      // Render
      $build = [];
      if (!empty($related_content)) {
        foreach ($related_content as $related_node) {
          $build[] = [
            '#theme' => 'node',
            '#node' => $related_node,
            '#view_mode' => 'teaser',
          ];
        }
      }
      else {
        $build['#markup'] = $this->t('No related content found.');
      }
  
      return $build;
    }
  
  }