ALTER TABLE `#__xdecaroorganizations_organizations`
  ADD COLUMN `appointment_membership_requirement` VARCHAR(32) NOT NULL DEFAULT 'inherit' AFTER `autonomy_fiscal`;
