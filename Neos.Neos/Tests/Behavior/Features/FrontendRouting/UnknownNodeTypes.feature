@flowEntities @contentrepository
Feature: Basic routing functionality (match & resolve nodes with unknown types)

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

  Scenario:
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | workspaceName               | "live"                        |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "shernode-homes"              |
      | nodeTypeName                | "Neos.Neos:Test.Routing.Page" |
      | parentNodeAggregateId       | "lady-eleonode-rootford"      |
      | nodeAggregateClassification | "regular"                     |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                 |
      | workspaceName               | "live"                                                                |
      | contentStreamId             | "cs-identifier"                                                       |
      | nodeAggregateId             | "sir-david-nodenborough"                                              |
      | nodeTypeName                | "Neos.Neos:Test.Routing.Page"                                         |
      | parentNodeAggregateId       | "shernode-homes"                                                      |
      | nodeAggregateClassification | "regular"                                                             |
      | initialPropertyValues       | {"uriPathSegment": {"type": "string", "value": "david-nodenborough"}} |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                           |
      | workspaceName               | "live"                                                          |
      | contentStreamId             | "cs-identifier"                                                 |
      | nodeAggregateId             | "unknown-nodetype"                                              |
      | nodeTypeName                | "Neos.Neos:Test.Routing.NonExisting"                            |
      | parentNodeAggregateId       | "shernode-homes"                                                |
      | nodeAggregateClassification | "regular"                                                       |
      | initialPropertyValues       | {"uriPathSegment": {"type": "string", "value": "non-existing"}} |
    Then I expect the documenturipath table to contain exactly:
      | uripath              | nodeaggregateidpath                                            | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                         | isPlaceholder |
      | ""                   | "lady-eleonode-rootford"                                       | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"                    | false         |
      | ""                   | "lady-eleonode-rootford/shernode-homes"                        | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page"        | false         |
      | "david-nodenborough" | "lady-eleonode-rootford/shernode-homes/sir-david-nodenborough" | "sir-david-nodenborough" | "shernode-homes"         | null                     | "unknown-nodetype"        | "Neos.Neos:Test.Routing.Page"        | false         |
      | "non-existing"       | "lady-eleonode-rootford/shernode-homes/unknown-nodetype"       | "unknown-nodetype"       | "shernode-homes"         | "sir-david-nodenborough" | null                      | "Neos.Neos:Test.Routing.NonExisting" | true          |
