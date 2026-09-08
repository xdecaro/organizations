# Repository Guidelines — Organizations by xdecaro

Organizations is a separate Joomla product in the xdecaro ecosystem.

## Domain boundary

Organizations owns organization/legal-entity master records and organizational hierarchy. It does not own person profiles (People), membership lifecycle (Membership), accounting (Finance), resources or bookings.

Core by xdecaro provides only shared infrastructure: Web Asset Manager assets, design tokens, compatibility helpers, diagnostics and public cross-product reference contracts. Do not move Organizations-specific business rules into Core.

Dependency direction is `Organizations -> Core`, never `Core -> Organizations`.

Cross-product integration must use stable public APIs or `Xdecaro\Core\Integration\EntityReference` / `RelationReference`. Never read or write another product's private database tables as an integration mechanism.

## Joomla/security

Use modern Joomla APIs, server-side ACL, CSRF for state-changing operations, filtered/validated input, escaped output, bound database queries and `#__` table prefixes. Do not claim Joomla versions that have not been runtime-tested.

## Releases

Version 0.2.0 establishes the first technical baseline. Keep stable IDs `com_xdecaroorganizations` and `pkg_xdecaroorganizations`. Normal updates must preserve data and configuration. ZIPs must be directly installable in Joomla.
