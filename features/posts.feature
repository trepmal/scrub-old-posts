Feature: Scrub posts

  Scenario: Scrub posts
    Given a WP install

    When I run `wp post generate --count=20 --post_type=post --post_date=2012-12-12-12-12-12`
    And I run `wp scrub posts --date=2012-12-20 --yes`
    Then STDOUT should contain:
      """
      Found 20 posts (of 21) older than 2012-12-20.
      """

    When I try `wp scrub posts --date=2012-12-asdf`
    Then STDERR should contain:
      """
      Error: Invalid date.
      """
    And the return code should be 1

    When I try `wp scrub posts --date=2012-12-12 --post_type=asdf`
    Then STDERR should contain:
      """
      Error: Invalid post type.
      """
    And the return code should be 1

