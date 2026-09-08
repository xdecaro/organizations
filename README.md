# Organizations by xdecaro

Joomla component for organization master data in the xdecaro ecosystem.

## Technical identity

- Component: `com_xdecaroorganizations`
- Package: `pkg_xdecaroorganizations`
- PHP namespace: `xdecaro\Component\Organizations`
- Database tables: `#__xdecaroorganizations_*`
- Current prerelease line: `0.3.0`

The vendor namespace is intentionally lowercase: `xdecaro`.

Version 0.2.0 established the new Joomla/DB identity. Version 0.3.0 keeps that identity and normalizes the PHP vendor namespace to lowercase `xdecaro` before 1.0.0.

Organizations owns organization records and organizational structure. It integrates with Core by xdecaro for shared infrastructure and public cross-product references, without moving organization-domain logic into Core.
