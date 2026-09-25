-- Organizations 1.2.11
-- Normalize active sports affiliations so clubs point to federations.
-- If the correct reverse relationship already exists, deactivate the wrong duplicate first.

UPDATE `#__xdecaroorganizations_affiliations` AS wrong
INNER JOIN `#__xdecaroorganizations_organizations` AS source_org
  ON source_org.id = wrong.organization_id
INNER JOIN `#__xdecaroorganizations_organizations` AS target_org
  ON target_org.id = wrong.target_organization_id
INNER JOIN `#__xdecaroorganizations_affiliations` AS correct
  ON correct.organization_id = wrong.target_organization_id
 AND correct.target_organization_id = wrong.organization_id
 AND correct.relation_type = wrong.relation_type
 AND correct.state = 1
 AND correct.status = 'active'
 AND correct.id <> wrong.id
SET wrong.status = 'inactive',
    wrong.state = 0
WHERE wrong.relation_type = 'sports_affiliation'
  AND wrong.state = 1
  AND wrong.status = 'active'
  AND source_org.type = 'federation'
  AND target_org.type = 'club';

UPDATE `#__xdecaroorganizations_affiliations` AS wrong
INNER JOIN `#__xdecaroorganizations_organizations` AS source_org
  ON source_org.id = wrong.organization_id
INNER JOIN `#__xdecaroorganizations_organizations` AS target_org
  ON target_org.id = wrong.target_organization_id
SET wrong.organization_id = target_org.id,
    wrong.target_organization_id = source_org.id
WHERE wrong.relation_type = 'sports_affiliation'
  AND wrong.state = 1
  AND wrong.status = 'active'
  AND source_org.type = 'federation'
  AND target_org.type = 'club';
