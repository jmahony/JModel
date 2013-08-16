<?php namespace JModel;

/**
* A model of a WordPress user, extends from WP_User
*
*/
class User extends \WP_User {

  function __construct($userId = null) {

    parent::__construct($userId);

  }

  /**
   * Returns the users displayname
   * @return string
   */
  public function getDisplayName() {
    return \get_the_author_meta('display_name', $this->ID);
  }

  /**
   * Returns the role for the current user
   * @return string | null
   */
  public function getRole() {
    return isset($this->roles[0]) ? $this->roles[0] : null;
  }

  /**
   * Returns the authors post url
   * @return string
   */
  public function getPostsURL() {
    return \get_author_posts_url($this->ID);
  }

  /**
   * Alias of postsURL. Returns the users permalink, aka post url,
   * @return string
   */
  public function getPermalink() {
    return $this->postsURL();
  }

  /**
   * Returns a URL of the author avatar
   * @return string
   */
  public function getAvatar($size = 72) {
    return \get_avatar($this->ID, $size);
  }

  /******************* Static Methods *******************/

  /**
   * Returns the author of the page as an instance of User
   * @return User
   */
  public static function getPostAuthor() {
    $user = get_user_by('slug', get_query_var('author_name'));

    return new User($user->ID);
  }

  /**
   * Returns an instance of the currently logged in user
   * @return User
   */
  public static function getCurrentUser() {
    global $current_user;
    get_currentuserinfo();
    return new User($current_user->ID);
  }

  /**
   * Returns whether the current user is an admin or not
   * @return boolean
   */
  public static function currentUserIsAdmin() {
    $cu = self::getCurrentUser();
    return $cu->getRole() == 'administrator';
  }

  /**
   * Returns an array of users with their assosicated IDs
   * @param  string $role desired role
   * @return array<string>
   */
  public static function getSelectOptions($role = null, $placeholder = 'Select a user') {

    $users = self::all($role);

    $ra = array(
      '0' => $placeholder
    );

    foreach ($users as $u) {
      $ra[$u->ID] = $u->display_name;
    }

    return $ra;

  }

  /**
   * Returns an array of all users with the passed role
   * @param  strubg $role a valid role
   * @return array<WP_User>
   */
  public static function all($role = null) {

    return get_users(array(
      'role' => $role,
    ));

  }

  /**
   * Returns the users role
   * @param  WP_User $u [description]
   * @return string | null
   */
  public static function getUserRole($u = null) {

    if (!$u) return null;

    if (!($u instanceof \WP_User)) $u = new \WP_User($u);

    if (isset($u->roles[0])) return $u->roles[0];

    return null;

  }
}