<?php namespace JModel;

/**
* A model of a WordPress post, the orignal post data is stored in the post attribute.
* Main use is for rendering by mustache
*
*/
class Post {

  /**
   * The WordPress post objects that is passed in the constructor, ideally we'd like to extends off of
   * WP_Post but it is set as final.
   * @var WP_Post
   */
  public $post;

  /**
   * Store a list of popular posts, so we don't fetch it with each function call
   * @var array
   */
  public $popularPostsCache = array();

  /**
   * Holds all of the posts meta data
   * @var array(<mixed>)
   */
  protected $meta;

  /**
   * Adds additional classes for the post
   * @var array
   */
  private $classes = array();

  /**
   * Holds the posts type
   * @const string
   */
  const POST_TYPE = 'post';

  /**
   * Takes a postId or WP_Post object
   * @param WP_Post || postId
   */
  public function __construct($post = null) {

    if (!$post) {
      global $post;
      $post = $post;
    }

    if (!$post) throw new \Exception('Post must be set');

    if (!($post instanceof \WP_Post)) $post = \get_post($post);

    /* Just in case we need any of the template functions */
    \setup_postdata($post);

    $this->post = $post;

  }

  /******************* Template Methods *******************/

  /**
   * Returns the posts ID
   * @return int
   */
  public function getId() {
    return $this->post->ID;
  }

  /**
   * Returns the posts title
   * @return string
   */
  public function getTitle() {
    return \get_the_title($this->post->ID);
  }

  /**
   * Returns the posts content
   * @return string
   */
  public function getContent() {
    ob_start();
    \setup_postdata($this->post);
    \the_content();
    \wp_reset_postdata();
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
  }

  /**
   * Returns the posts edit link
   * @return string
   */
  public function getEditLink() {
    return \get_edit_post_link($this->post->ID, '');
  }

  /**
   * The permalink for the post
   * @return string
   */
  public function getPermalink() {
    return \get_permalink($this->post->ID);
  }

  /**
   * Returns the posts excerpt
   * @return string
   */
  public function getExcerpt() {

    \setup_postdata( $this->post );

    $excerpt = \get_the_excerpt();

    \wp_reset_postdata();

    if (empty($excerpt)) {
      $excerpt = $this->post->post_excerpt;
    }

    return $excerpt;

  }

  /**
   * Returns the posts date
   * @return string
   */
  public function getDate() {
    return \get_the_date();
  }

  /**
   * Returns post the post class
   * @return string
   */
  public function getPostClasses($classes = array()) {
    $wpClasses = \get_post_class('', $this->post->ID);
    return 'class="' . join(' ', array_merge($wpClasses, $classes, $this->classes))  . '"';
  }

  /**
   * Returns whether the post has a thumbnail
   * @return boolean
   */
  public function hasThumbnail() {
    return \has_post_thumbnail($this->post->ID);
  }

  /**
   * Returns the posts thumbnail
   *
   * There must be another way to get thumbnails in a mustache template other than each size having its own
   * function. Perhaps when using the __call magic method we can prefix functions with thmb and then anything
   * after that will be used as the size.
   * So the function call thmbMainFeatured() would call thumbnail('main-featured')
   *
   * @param string $size
   * @param array  $classes
   * @return string
   */
  public function getThumbnail($size = 'full', $classes = array()) {
    return \get_the_post_thumbnail($this->post->ID, $size, $classes);
  }

  /**
   * Convenience method for get author
   * @param  string $size
   * @return string
   */
  public function thumbnail($size = 'full') {
    return $this->getThumbnail($size);
  }

  /**
   * Returns the posts thumbnail url
   * @param  string $size
   * @return string
   */
  public function thumbnailURL($size = 'blog-feed-featured') {
    global $image_sizes;

    $tId = \get_post_thumbnail_id($this->post->ID);

    if ($tId) {
      $a = \wp_get_attachment_image_src(\get_post_thumbnail_id($this->post->ID), $size);
      return $a[0];
    } else {
      return sprintf(
        'http://placehold.it/%1$dx%2$d',
        $image_sizes[$size]['width'],
        $image_sizes[$size]['height']
      );
    }
  }

  /**
   * Returns the comments form for the instance
   * @return String
   */
  public function comments() {
    ob_start();
    setup_postdata($this->post);
    comments_template();
    wp_reset_postdata();
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
  }

  /**
   * Returns a list of popular posts
   * TODO: this doesn't actually return popular posts, just the 2 newest, what is a popular post??
   * @return Array<Post>
   */
  public function getPopularPosts() {

    if (empty($this->popularPostsCache)) {
      $posts = get_posts(array(
        'post_type'      => 'post',
        'posts_per_page' => 2
      ) );
      foreach ($posts as $p) {
        $this->popularPostsCache[] = new Post($p);
      }
    }

    return $this->popularPostsCache;

  }

  /**
   * Add a new class to the post, will be appended to the WordPress get_post_class array
   * @param string
   */
  public function addClass($class) {
    $this->classes[] = $class;
  }

  /******************* Author Functions *******************/

  /**
   * Returns the author as a User object
   * @return User
   */
  public function getAuthor() {
    return new User(get_the_author_meta('ID'));
  }

  /**
   * Convenience method for getAuthor
   * @return User
   */
  public function author() {
    return $this->getAuthor();
  }

  /******************* Magic Methods *******************/

  /**
   * Returns the posts meta
   * @param  string $k meta_key
   * @return string    meta_value
   */
  public function __get($k = null) {
    $this->meta();
    return isset($this->meta[$k][0]) ? $this->meta[$k][0] : null;
  }

  /**
   * Returns whether there is meta data or not for the post
   * @param  string  $k meta_key
   * @return boolean    whether the key exists in the meta array
   */
  public function __isset($k) {
    return $this->hasMeta($k);
  }

  /**
   * Calls the getThumbnail method, for example if a method call is made like thmb_main_featured,
   * The method call getThumbnail('main-featured') would be made.
   * @param  string $name
   * @param  array $args
   * @return Mixed
   */

  /******************* Meta Functions *******************/

  /**
   * Returns weather the given keys meta data exists
   * @param  string  $k the meta datas key
   * @return boolean    whether the meta data exists
   */
  public function hasMeta($k = null) {
    return array_key_exists($k, $this->meta());
  }

  /**
   * Populate the posts meta values
   * @return Array<mixed>
   */
  protected function meta() {
    return $this->meta = empty($this->meta) ? get_post_meta($this->post->ID) : $this->meta;
  }

  /**
   * Returns an array of terms for the current post, add in the permalink
   * @param  string $taxonomy The taxonomies name
   * @return array || null
   */
  protected function initTerm($taxonomy = null) {

    $a = \get_the_terms( $this->post->ID, $taxonomy );

    if (is_array($a)) {
      foreach ( (array) $a as $o) {
        $o->permalink = get_term_link( $o->slug, $o->taxonomy );
      }
      return array_values( $a ); // Needs to reset keys for mustache
    } else {
      return null;
    }

  }

  /******************* Static Methods *******************/

  /**
   * Returns an array of posts in the format post_id => post_title
   * @param  string $postType
   * @return array
   *
   */
  public static function getKeyValueArray($placeholder = 'Select') {

    $posts = get_posts(array(
      'post_type'   => static::POST_TYPE,
      'numberposts' => -1
    ));

    $ra = array(
      '0' => $placeholder
    );

    foreach ($posts as $p) {
      $ra[$p->ID] = $p->post_title;
    }

    return $ra;

  }

  /**
   * Returns a post, but only if the post type matches the static objects type
   * @param  integer $postId the posts id
   * @return WP_Post but only if it is of the correct post type
   */
  public static function get($postId = 0) {

    if (!$postId) return null;

    $post = get_post(intval(abs($postId)));

    if (!($post instanceof \WP_Post)) return null;

    if ($post->post_type != static::POST_TYPE) return null;

    return $post;

  }

  /**
   * Returns a set of posts, but only if the post type matches the static objects type
   * @param  array  $args , merges with default
   * @return WP_Post but only if it is of the correct post type
   */
  public static function getAll($args = array()) {

    $args = array_merge(array(
      'post_type'      => static::POST_TYPE,
      'posts_per_page' => -1
    ), $args);

    $posts = get_posts();

    $ra = array();

    $class = get_called_class();

    foreach ($posts as $p) {
      $ra[] = new $class($p);
    }

    return $ra;

  }

}