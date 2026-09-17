# Repository Guidelines — Organizations by xdecaro

Organizations owns reusable organization/legal-entity master records, organizational hierarchy and duplicate warnings. It does not own person profiles, memberships, accounting, resources, bookings or domain workflows.

Dependency direction is `Organizations -> Core`. Cross-product integration uses `OrganizationProviderService`, public provider services, Core `CapabilityRegistry` and `EntityReference`; never private cross-product tables.

The hierarchy is internal to Organizations. Parent changes must be validated server-side to prevent self-parenting and cycles. Sensitive fiscal/address fields require `organizations.view_sensitive`.

People birth date/place used for appointment-person disambiguation must come only from the public limited identity-details provider/ACL contract. Organizations must not request the full sensitive People profile for this purpose and must not store birth data in appointment records.

Use Joomla ACL, CSRF, validated input, escaped output, bound queries and `#__`. The canonical package identity is `pkg_organizations`; `pkg_xdecaroorganizations` is legacy migration input only. Updates must verify child `package_id` ownership before retiring a legacy package record.

Organizations is Joomla 6 only. Do not add Joomla 4/5 compatibility unless explicitly requested. Updates preserve existing organization/appointment data and configuration, and install ZIPs must be deterministic.
