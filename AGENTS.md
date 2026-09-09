# Repository Guidelines — Organizations by xdecaro

Organizations owns reusable organization/legal-entity master records, organizational hierarchy and duplicate warnings. It does not own person profiles, memberships, accounting, resources, bookings or domain workflows.

Dependency direction is `Organizations -> Core`. Cross-product integration uses `OrganizationProviderService`, Core `CapabilityRegistry` and `EntityReference`; never private cross-product tables.

The hierarchy is internal to Organizations. Parent changes must be validated server-side to prevent self-parenting and cycles. Sensitive fiscal/address fields require `organizations.view_sensitive`.

Use Joomla ACL, CSRF, validated input, escaped output, bound queries and `#__`. `1.0.0` is the first stable `com_xdecaroorganizations` / `pkg_xdecaroorganizations` release. Updates preserve data/configuration and ZIPs install directly in Joomla 5/6.
