# Organizations by xdecaro

Stable Joomla component for reusable organization master data in the xdecaro ecosystem.

- Component: `com_xdecaroorganizations`
- Package: `pkg_organizations` (`pkg_xdecaroorganizations` is migrated as a legacy package identity)
- Namespace: `xdecaro\Component\Organizations`
- Tables: `#__xdecaroorganizations_*`
- Stable candidate version: `1.2.9`
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

Organizations 1.1.3 refines the System and Publishing tabs. The System tab now shows localized Joomla dates plus Created by and Modified by audit users, while making clear that the last-modified timestamp belongs only to the organization record. Publishing notes are explicitly labeled as publishing-specific notes. No schema changes are required.

Organizations 1.2.0 adds a public Joomla frontend with an organizations directory and individual organization profiles. Public output respects organization state, Joomla access levels and language. Appointment holder names are opt-in through `show_on_frontend`, which defaults to disabled for existing and new records. The frontend never exposes Membership eligibility, fee state, UUID/audit data, PEC, fiscal identifiers or other sensitive organization fields.

Organizations 1.2.1 improves frontend menu selection and visibility. The single-organization menu selector now shows organization name plus operational status, keeps inactive/unpublished organizations visible but disabled, and the public directory/profile only expose operationally active organizations. Public badge contrast is improved for light and dark templates. No schema changes are required.

Organizations 1.2.2 fixes the single-organization Joomla menu route by replacing the ambiguous request field `id` with `organization_id`. The site model resolves `organization_id` first while preserving direct public-directory links that still use `id`. Existing menu items should be reopened, the organization reselected, and saved once after updating.

Organizations 1.2.3 fixes publishing-language handling. The language selector now lives in the Publishing tab, defaults to All languages (`*`) for new organizations, and no longer changes automatically when the country changes. Blank legacy language values are normalized to `*` without overwriting explicit language selections such as `en-GB` or `it-IT`. The public frontend continues to respect Joomla content language filtering.


Organizations 1.2.7 reorganizes the organization editor without changing stored data: Country and Logo now belong to Identity, while the Contacts tab is visually split into Contacts and Headquarters. Address, postal code, city, province and region remain the same canonical database fields; Country is shown only once, in Identity.

Organizations 1.2.4 refines the organization editor after real ENS Roma data-entry testing. It separates Province and Region without rewriting existing geography data, shows the status date as day/month/year, adds concise help for the five autonomy flags, renames the ambiguous Members tab to Appointments/Incarichi, and allows permanent deletion of an internal body only when no child bodies or appointments reference it. Public organization type badges also use theme-aware contrast in light and dark mode.

Organizations 1.2.8 improves affiliation selection for large organization datasets. The affiliation editor now searches organizations by name or acronym instead of loading a long dropdown, prioritizes federations for sports affiliations, and caps results for responsive use. The organizations list also shows a dedicated Acronym/Sigla column on desktop and tablet. No schema changes are required.

Organizations 1.2.9 improves the administrator organizations list: publication state uses Joomla's compact icon control, active affiliation relationships are counted in a dedicated column, and a per-browser Columns menu lets users show or hide list columns while keeping Name fixed. No schema changes are required.
