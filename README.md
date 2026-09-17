# Organizations by xdecaro

Stable Joomla component for reusable organization master data in the xdecaro ecosystem.

- Component: `com_xdecaroorganizations`
- Package: `pkg_organizations` (`pkg_xdecaroorganizations` is migrated as a legacy package identity)
- Namespace: `xdecaro\Component\Organizations`
- Tables: `#__xdecaroorganizations_*`
- Stable candidate version: `1.0.26`
- Requires Core by xdecaro `1.4.0+`
- Platform: Joomla 6 only

Organizations owns organization/legal-entity master records and hierarchy. It does not own People profiles, Membership lifecycle, Finance, Resources or Bookings. Cross-product consumers must use the public provider and Core capability/entity-reference contracts, never direct table access.

For member/appointment person disambiguation, Organizations uses the public People identity-details provider when available. That contract exposes only birth date and birth place under the dedicated People ACL permission; Organizations does not request or store the full sensitive People profile. If the permission or provider is unavailable, the autocomplete falls back to public name-only results.
