<?php 

require_once("../../../wp-load.php");

$querystr = "
    SELECT $wpdb->posts.* 
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_title LIKE '%".$_GET['term']."%'
    AND $wpdb->posts.post_status = 'publish' 
    AND $wpdb->posts.post_type = 'post'
    GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_title ASC
 ";
 $pageposts = $wpdb->get_results($querystr, OBJECT);
?>
<?php $title = array(); ?>
<?php if ($pageposts): ?>
  <?php global $post; ?>
  <?php foreach ($pageposts as $post): ?>
    <?php setup_postdata($post); ?>
    <?php $values = '"'.$post->post_title.'"'; ?>
    <?php array_push($title,$values); ?>
  <?php endforeach; ?>
<?php endif; ?>
<?php $list = implode(",", $title); ?>
<?php echo '['.$list.']'; ?>

