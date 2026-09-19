# Organizations by xdecaro

Stable Joomla component for reusable organization master data in the xdecaro ecosystem.

- Component: `com_xdecaroorganizations`
- Package: `pkg_organizations` (`pkg_xdecaroorganizations` is migrated as a legacy package identity)
- Namespace: `xdecaro\Component\Organizations`
- Tables: `#__xdecaroorganizations_*`
- Stable candidate version: `1.1.2`
- Requires Core by xdecaro `1.4.0+`
- Platform: Joomla 6 only

Organizations owns organization/legal-entity master records, hierarchy and organization appointments. It does not own People profiles, Membership lifecycle, Finance, Resources or Bookings. Cross-product consumers must use public services and Core capability/entity-reference contracts, never direct table access.

For member/appointment person disambiguation, Organizations uses the public People identity-details provider when available. That contract exposes only birth date and birth place under the dedicated People ACL permission; Organizations does not request or store the full sensitive People profile. If the permission or provider is unavailable, the autocomplete falls back to public name-only results.

Organizations 1.0.27 adds the read-only Core capability `organizations.people_appointments` and public `PersonAppointmentsService::getAppointmentsByPersonUuid()` contract. Access is controlled by `organizations.view_appointments` or `core.admin`. The public response is limited to organization identity, role, dates, cessation reason and derived appointment status; appointment notes and sensitive organization fields are not exposed.

Organizations 1.0.28 adds a generic institutional profile for multi-level organizations: organizational level, territorial scope, operational status and separate legal/management/administrative/tax/fiscal autonomy flags. These are organization-owned facts; meeting, voting and deliberation workflow remains outside Organizations.

Organizations 1.0.29 adds reusable organization bodies/internal structures and optional body-linked appointments. Bodies describe static organizational structure only; meeting, quorum, voting, agenda and resolution workflows remain the responsibility of a future Governance component.

Organizations 1.0.30 adds organization delegations/assignments linked to existing appointments, with scope, period and history. Ending an appointment closes any longer/open delegation on the same date, while appointments referenced by delegations cannot be physically deleted. Governance workflows remain outside Organizations.

Organizations 1.0.31 clarifies the distinction between parent organizations/territorial structures and internal bodies. The organization editor now includes a hierarchy context tab showing the root-to-current path and all descendant organizations, while the parent selector displays organization levels to reduce accidental use of councils/boards as parent organizations.

Organizations 1.0.32 fixes future appointment classification: appointments whose start date is after today are shown as Scheduled rather than Active and remain separate from appointment history.

Organizations 1.1.0 adds an inheritable appointment membership requirement. A parent organization such as ENS can require active Membership status (optionally with the annual fee current), child organizations can inherit the rule, and appointment selection shows eligibility without duplicating Membership data. Membership remains optional unless an organization explicitly enables a membership requirement.

Organizations 1.1.1 updates the optional Membership adapter for Membership 1.7.0. The current `membership.eligibility` contract is preferred, while the previous 1.6.x history contract remains supported as a compatibility fallback. No direct Membership database access is introduced.

Organizations 1.1.2 binds delegations to the effective end of their linked appointment. A delegation cannot start or end beyond the mandate boundary; when no delegation-specific end is provided, the UI shows the mandate limit. Existing appointment termination already closes linked delegations transactionally and remains unchanged.
