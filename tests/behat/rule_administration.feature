@local @local_themerules
Feature: Theme rules administration
  In order to control which Moodle theme different users see
  As a site administrator
  I need to create, edit, disable, duplicate, delete and simulate theme rules

  # Scope note: these scenarios exercise the plain server-rendered form (the
  # "expressionjson" textarea), not the JavaScript visual builder added in
  # Phase 5 - the textarea is the actual data channel the JS builder writes
  # into (see DECISIONS.md "Phase 5"), and the builder's own DOM behaviour
  # was verified separately via a headless-Chrome harness (DECISIONS.md
  # "Phase 5" testing section), not duplicated here. "Add AND condition" /
  # "add OR group" / "save nested expression" (SPECIFICATIONS.md section 47)
  # are covered by the "nested AND/OR expression" scenario below, which
  # saves a real nested tree through the textarea and confirms it reloads
  # unchanged - the same round trip the visual builder itself relies on.

  Scenario: Administrator creates a rule
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    When I set the field "Rule name" to "Corporate branding"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 123}"
    And I press "Save rule"
    Then I should see "Rule saved."
    And I should see "Corporate branding"
    And I should see "boost"

  Scenario: Administrator edits an existing rule
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Original name"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 123}"
    And I press "Save rule"
    When I click on "Edit" "link" in the "Original name" "table_row"
    And I set the field "Rule name" to "Renamed rule"
    And I press "Save rule"
    Then I should see "Rule saved."
    And I should see "Renamed rule"
    And I should not see "Original name"

  Scenario: Administrator disables and re-enables a rule
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Toggle me"
    And I set the field "Enabled" to "1"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 123}"
    And I press "Save rule"
    Then I should see "Enabled" in the "Toggle me" "table_row"
    When I click on "Disable" "link" in the "Toggle me" "table_row"
    Then I should see "Rule updated."
    And I should see "Disabled" in the "Toggle me" "table_row"
    When I click on "Enable" "link" in the "Toggle me" "table_row"
    Then I should see "Enabled" in the "Toggle me" "table_row"

  Scenario: Administrator duplicates a rule and the copy is disabled
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Duplicate source"
    And I set the field "Enabled" to "1"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 123}"
    And I press "Save rule"
    When I click on "Duplicate" "link" in the "Duplicate source" "table_row"
    Then I should see "Rule duplicated (created disabled)."
    And I should see "Duplicate source (copy)"
    And I should see "Disabled" in the "Duplicate source (copy)" "table_row"
    And I should see "Enabled" in the "Duplicate source" "table_row"

  Scenario: Administrator deletes a rule after confirming
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Delete me"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 123}"
    And I press "Save rule"
    When I click on "Delete" "link" in the "Delete me" "table_row"
    Then I should see "cannot be undone"
    When I press "Continue"
    Then I should see "Rule deleted."
    And I should not see "Delete me"

  Scenario: Administrator saves a nested AND/OR condition expression and it survives a reload
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "FUNDAE branding"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"group\", \"operator\": \"and\", \"children\": [{\"type\": \"condition\", \"condition\": \"coursecategory\", \"operator\": \"in_category\", \"value\": 12, \"includechildren\": true}, {\"type\": \"group\", \"operator\": \"or\", \"children\": [{\"type\": \"condition\", \"condition\": \"cohort\", \"operator\": \"member\", \"value\": 7}, {\"type\": \"condition\", \"condition\": \"cohort\", \"operator\": \"member\", \"value\": 8}]}]}"
    And I press "Save rule"
    Then I should see "Rule saved."
    When I click on "Edit" "link" in the "FUNDAE branding" "table_row"
    Then the field "Condition expression (JSON)" matches value "{\"type\": \"group\", \"operator\": \"and\", \"children\": [{\"type\": \"condition\", \"condition\": \"coursecategory\", \"operator\": \"in_category\", \"value\": 12, \"includechildren\": true}, {\"type\": \"group\", \"operator\": \"or\", \"children\": [{\"type\": \"condition\", \"condition\": \"cohort\", \"operator\": \"member\", \"value\": 7}, {\"type\": \"condition\", \"condition\": \"cohort\", \"operator\": \"member\", \"value\": 8}]}]}"

  Scenario: Administrator simulates a rule that matches
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Matches admin"
    And I set the field "Enabled" to "1"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 2}"
    And I press "Save rule"
    When I press "Simulate"
    And I set the field "User" to "2"
    And I press "Simulate"
    Then I should see "Matches admin"
    And I should see "Result: TRUE"
    And I should see "would select theme"

  Scenario: Administrator simulates a rule that does not match
    Given I log in as "admin"
    And I navigate to "Appearance > Theme rules" in site administration
    And I press "Create rule"
    And I set the field "Rule name" to "Does not match"
    And I set the field "Enabled" to "1"
    And I set the field "Apply theme" to "boost"
    And I set the field "Condition expression (JSON)" to "{\"type\": \"condition\", \"condition\": \"user\", \"operator\": \"is\", \"value\": 999999}"
    And I press "Save rule"
    When I press "Simulate"
    And I set the field "User" to "2"
    And I press "Simulate"
    Then I should see "Does not match"
    And I should see "Result: FALSE"
    And I should see "No rule matches"
